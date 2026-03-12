<?php
/**
 * SystemSettings — إدارة إعدادات النظام
 * قراءة/كتابة مع تشفير القيم الحساسة
 *
 * @property int $id
 * @property string $setting_group
 * @property string $setting_key
 * @property string $setting_value
 * @property int $is_encrypted
 * @property string $description
 * @property int $updated_by
 * @property string $updated_at
 */

namespace common\models;

use Yii;
use yii\db\ActiveRecord;

class SystemSettings extends ActiveRecord
{
    /** @var string Encryption method */
    const CIPHER = 'aes-256-cbc';

    public static function tableName()
    {
        return '{{%system_settings}}';
    }

    public function rules()
    {
        return [
            [['setting_group', 'setting_key'], 'required'],
            [['setting_value', 'description'], 'string'],
            [['is_encrypted', 'updated_by'], 'integer'],
            [['updated_at'], 'safe'],
            [['setting_group'], 'string', 'max' => 50],
            [['setting_key'], 'string', 'max' => 100],
            [['description'], 'string', 'max' => 255],
            [['setting_group', 'setting_key'], 'unique', 'targetAttribute' => ['setting_group', 'setting_key']],
        ];
    }

    // ─────────────────────────────────────────────────────────
    //  Static API
    // ─────────────────────────────────────────────────────────

    private static array $_cache = [];

    /**
     * Get a single setting value
     */
    public static function get(string $group, string $key, $default = null)
    {
        $cacheKey = $group . '::' . $key;
        if (array_key_exists($cacheKey, static::$_cache)) {
            return static::$_cache[$cacheKey] ?? $default;
        }

        $model = static::find()
            ->where(['setting_group' => $group, 'setting_key' => $key])
            ->one();

        if (!$model || $model->setting_value === null || $model->setting_value === '') {
            static::$_cache[$cacheKey] = null;
            return $default;
        }

        $val = $model->is_encrypted ? static::decrypt($model->setting_value) : $model->setting_value;
        static::$_cache[$cacheKey] = $val;
        return $val;
    }

    /**
     * Set a single setting value
     */
    public static function set(string $group, string $key, $value, bool $encrypt = false, ?string $description = null): bool
    {
        $model = static::find()
            ->where(['setting_group' => $group, 'setting_key' => $key])
            ->one();

        if (!$model) {
            $model = new static();
            $model->setting_group = $group;
            $model->setting_key = $key;
        }

        $model->setting_value = $encrypt ? static::encrypt($value) : $value;
        $model->is_encrypted = $encrypt ? 1 : 0;

        if ($description !== null) {
            $model->description = $description;
        }

        $model->updated_by = Yii::$app->user->id ?? null;
        $model->updated_at = date('Y-m-d H:i:s');

        $ok = $model->save(false);
        if ($ok) {
            unset(static::$_cache[$group . '::' . $key], static::$_cache['_grp::' . $group]);
        }
        return $ok;
    }

    /**
     * Get all settings for a group as key => value array
     */
    public static function getGroup(string $group): array
    {
        $groupCacheKey = '_grp::' . $group;
        if (array_key_exists($groupCacheKey, static::$_cache)) {
            return static::$_cache[$groupCacheKey];
        }

        $models = static::find()
            ->where(['setting_group' => $group])
            ->all();

        $result = [];
        foreach ($models as $model) {
            $val = $model->setting_value;
            if ($model->is_encrypted && $val) {
                $val = static::decrypt($val);
            }
            $result[$model->setting_key] = $val;
            static::$_cache[$group . '::' . $model->setting_key] = $val;
        }

        static::$_cache[$groupCacheKey] = $result;
        return $result;
    }

    /**
     * Save an entire group from array
     */
    public static function setGroup(string $group, array $data, array $encryptedKeys = []): bool
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            foreach ($data as $key => $value) {
                $encrypt = in_array($key, $encryptedKeys);
                static::set($group, $key, $value, $encrypt);
            }
            $transaction->commit();
            return true;
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::error("SystemSettings::setGroup failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Test Google Cloud connection with given credentials
     */
    public static function testGoogleConnection(array $creds): array
    {
        try {
            if (empty($creds['client_email']) || empty($creds['private_key'])) {
                return ['success' => false, 'error' => 'البريد الإلكتروني أو المفتاح الخاص فارغ'];
            }

            // Build JWT
            $header = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $now = time();
            $claims = base64_encode(json_encode([
                'iss' => $creds['client_email'],
                'scope' => 'https://www.googleapis.com/auth/cloud-vision',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 300,
            ]));

            $signatureInput = $header . '.' . $claims;
            $privateKey = openssl_pkey_get_private($creds['private_key']);
            if (!$privateKey) {
                return ['success' => false, 'error' => 'المفتاح الخاص غير صالح (تأكد من النسخ الكامل)'];
            }

            $signature = '';
            openssl_sign($signatureInput, $signature, $privateKey, OPENSSL_ALGO_SHA256);
            $jwt = $signatureInput . '.' . rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

            // Exchange for token
            $ch = curl_init('https://oauth2.googleapis.com/token');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query([
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $jwt,
                ]),
                CURLOPT_TIMEOUT => 15,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);

            if ($curlError) {
                return ['success' => false, 'error' => 'خطأ اتصال: ' . $curlError];
            }

            $tokenData = json_decode($response, true);

            if ($httpCode === 200 && isset($tokenData['access_token'])) {
                return [
                    'success' => true,
                    'message' => 'الاتصال ناجح! تم الحصول على Access Token',
                    'project_id' => $creds['project_id'] ?? '',
                    'token_type' => $tokenData['token_type'] ?? 'Bearer',
                    'expires_in' => $tokenData['expires_in'] ?? 0,
                ];
            }

            $errMsg = $tokenData['error_description'] ?? $tokenData['error'] ?? "HTTP {$httpCode}";
            return ['success' => false, 'error' => 'فشل المصادقة: ' . $errMsg];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ─────────────────────────────────────────────────────────
    //  Encryption helpers
    // ─────────────────────────────────────────────────────────

    private static function getEncryptionKey(): string
    {
        $salt = Yii::$app->params['encryptionSalt'] ?? Yii::$app->request->cookieValidationKey ?? '';
        return substr(hash('sha256', $salt), 0, 32);
    }

    public static function encrypt(string $value): string
    {
        $key = static::getEncryptionKey();
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(self::CIPHER));
        $encrypted = openssl_encrypt($value, self::CIPHER, $key, 0, $iv);
        return base64_encode($iv . '::' . $encrypted);
    }

    public static function decrypt(string $value): string
    {
        try {
            $data = base64_decode($value);
            if ($data === false) return $value;
            $parts = explode('::', $data, 2);
            if (count($parts) !== 2) return $value;

            $key = static::getEncryptionKey();
            $decrypted = openssl_decrypt($parts[1], self::CIPHER, $key, 0, $parts[0]);
            return $decrypted !== false ? $decrypted : $value;
        } catch (\Exception $e) {
            return $value;
        }
    }
}

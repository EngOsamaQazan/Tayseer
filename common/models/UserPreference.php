<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $user_id
 * @property string $setting_key
 * @property string|null $setting_value
 * @property string $updated_at
 */
class UserPreference extends ActiveRecord
{
    const KEY_THEME_MODE  = 'theme_mode';
    const KEY_THEME_COLOR = 'theme_color';

    const MODE_LIGHT = 'light';
    const MODE_DARK  = 'dark';

    const DEFAULT_MODE  = self::MODE_LIGHT;
    const DEFAULT_COLOR = 'burgundy';

    const VALID_MODES  = ['light', 'dark'];
    const VALID_COLORS = ['burgundy', 'ocean', 'forest', 'royal', 'sunset', 'slate'];

    public static function tableName()
    {
        return '{{%user_preferences}}';
    }

    public function rules()
    {
        return [
            [['user_id', 'setting_key'], 'required'],
            [['user_id'], 'integer'],
            [['setting_key'], 'string', 'max' => 50],
            [['setting_value'], 'string', 'max' => 255],
            [['updated_at'], 'safe'],
            [['user_id', 'setting_key'], 'unique', 'targetAttribute' => ['user_id', 'setting_key']],
        ];
    }

    private static array $_cache = [];

    /**
     * Get a preference value for the current user.
     */
    public static function get(string $key, ?string $default = null, ?int $userId = null): ?string
    {
        $userId = $userId ?? (Yii::$app->user->id ?? null);
        if (!$userId) {
            return $default;
        }

        $cacheKey = $userId . '::' . $key;
        if (array_key_exists($cacheKey, static::$_cache)) {
            return static::$_cache[$cacheKey] ?? $default;
        }

        $val = static::find()
            ->select('setting_value')
            ->where(['user_id' => $userId, 'setting_key' => $key])
            ->scalar();

        static::$_cache[$cacheKey] = $val !== false ? $val : null;
        return static::$_cache[$cacheKey] ?? $default;
    }

    /**
     * Set a preference value for the current user.
     */
    public static function set(string $key, string $value, ?int $userId = null): bool
    {
        $userId = $userId ?? (Yii::$app->user->id ?? null);
        if (!$userId) {
            return false;
        }

        $model = static::find()
            ->where(['user_id' => $userId, 'setting_key' => $key])
            ->one();

        if (!$model) {
            $model = new static();
            $model->user_id = $userId;
            $model->setting_key = $key;
        }

        $model->setting_value = $value;
        $model->updated_at = date('Y-m-d H:i:s');

        $ok = $model->save(false);
        if ($ok) {
            static::$_cache[$userId . '::' . $key] = $value;
        }
        return $ok;
    }

    /**
     * Get the theme settings for the current user (or defaults).
     */
    public static function getTheme(?int $userId = null): array
    {
        $mode  = static::get(self::KEY_THEME_MODE, self::DEFAULT_MODE, $userId);
        $color = static::get(self::KEY_THEME_COLOR, self::DEFAULT_COLOR, $userId);

        if (!in_array($mode, self::VALID_MODES)) {
            $mode = self::DEFAULT_MODE;
        }
        if (!in_array($color, self::VALID_COLORS)) {
            $color = self::DEFAULT_COLOR;
        }

        return ['mode' => $mode, 'color' => $color];
    }
}

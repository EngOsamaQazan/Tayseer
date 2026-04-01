<?php

namespace common\helper;

use common\models\SystemSettings;
use Yii;

/**
 * Multi-provider SMS dispatcher.
 * Reads provider config from SystemSettings (sms_api group) with fallback to params-local.php.
 */
class SMSHelper
{
    const SMS_API_KEY = '6228d7a5b7236';
    const SMS_SENDER_ID = '962797707059';

    /**
     * إرسال SMS عبر المزوّد المُعدّ في إعدادات النظام.
     * @return array ['success' => bool, 'raw' => string, 'error' => string|null]
     */
    public static function send(string $to, string $message): array
    {
        $provider  = SystemSettings::get('sms_api', 'provider', '');
        $senderId  = SystemSettings::get('sms_api', 'sender_id', '');
        $apiUrl    = SystemSettings::get('sms_api', 'api_url', '');
        $apiKey    = SystemSettings::get('sms_api', 'api_key', '');
        $apiSecret = SystemSettings::get('sms_api', 'api_secret', '');
        $username  = SystemSettings::get('sms_api', 'username', '');
        $password  = SystemSettings::get('sms_api', 'password', '');

        if (empty($provider)) {
            $provider = 'smsapril';
            $senderId = $senderId ?: (Yii::$app->params['sender'] ?? '');
            $username = $username ?: (Yii::$app->params['user'] ?? '');
            $password = $password ?: (Yii::$app->params['pass'] ?? '');
        }

        switch ($provider) {
            case 'smsapril':   return self::viaSmsApril($to, $message, $senderId, $username, $password);
            case 'twilio':     return self::viaTwilio($to, $message, $senderId, $apiKey, $apiSecret);
            case 'vonage':     return self::viaVonage($to, $message, $senderId, $apiKey, $apiSecret);
            case 'unifonic':   return self::viaUnionic($to, $message, $senderId, $apiKey);
            case 'taqnyat':    return self::viaTaqnyat($to, $message, $senderId, $apiKey);
            case 'gateway_sa': return self::viaGatewaySa($to, $message, $senderId, $apiKey, $username, $password);
            case 'msegat':     return self::viaMsegat($to, $message, $senderId, $apiKey, $username);
            case 'other':      return self::viaCustom($to, $message, $senderId, $apiUrl, $apiKey, $apiSecret, $username, $password);
            default:           return ['success' => false, 'raw' => '', 'error' => "مزوّد غير معروف: {$provider}"];
        }
    }

    // ─────────────────────────────────────────────
    //  SMS April
    // ─────────────────────────────────────────────
    private static function viaSmsApril($to, $msg, $sender, $user, $pass): array
    {
        if (empty($user) || empty($pass)) {
            return ['success' => false, 'raw' => '', 'error' => 'SMS April: اسم المستخدم أو كلمة المرور فارغ'];
        }

        $url = 'http://www.smsapril.com/api.php?comm=sendsms';
        $output = self::curlPost($url, ['to' => $to, 'sender' => $sender, 'user' => $user, 'pass' => $pass, 'message' => $msg]);

        $errors = ['-100'=>'معلومات ناقصة','-110'=>'بيانات دخول خاطئة','-111'=>'حساب غير مفعّل','-112'=>'حساب مُجمَّد','-113'=>'لا يوجد رصيد','-114'=>'خدمة غير متوفرة','-115'=>'المرسل غير متوفر','-116'=>'اسم مرسل غير صالح'];
        $trimmed = trim($output);
        if (isset($errors[$trimmed])) {
            return ['success' => false, 'raw' => $output, 'error' => $errors[$trimmed]];
        }
        return ['success' => true, 'raw' => $output, 'error' => null];
    }

    // ─────────────────────────────────────────────
    //  Twilio
    // ─────────────────────────────────────────────
    private static function viaTwilio($to, $msg, $from, $sid, $token): array
    {
        if (empty($sid) || empty($token)) {
            return ['success' => false, 'raw' => '', 'error' => 'Twilio: Account SID أو Auth Token فارغ'];
        }

        $url = "https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json";
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15, CURLOPT_POST => true,
            CURLOPT_USERPWD => "{$sid}:{$token}",
            CURLOPT_POSTFIELDS => http_build_query(['To' => $to, 'From' => $from, 'Body' => $msg]),
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $data = json_decode($resp, true);

        if ($code === 201 || $code === 200) {
            return ['success' => true, 'raw' => $resp, 'error' => null];
        }
        return ['success' => false, 'raw' => $resp, 'error' => $data['message'] ?? "HTTP {$code}"];
    }

    // ─────────────────────────────────────────────
    //  Vonage (Nexmo)
    // ─────────────────────────────────────────────
    private static function viaVonage($to, $msg, $from, $key, $secret): array
    {
        if (empty($key) || empty($secret)) {
            return ['success' => false, 'raw' => '', 'error' => 'Vonage: API Key أو Secret فارغ'];
        }

        $url = 'https://rest.nexmo.com/sms/json';
        $resp = self::curlPost($url, ['api_key' => $key, 'api_secret' => $secret, 'to' => $to, 'from' => $from, 'text' => $msg, 'type' => 'unicode']);
        $data = json_decode($resp, true);

        if (isset($data['messages'][0]['status']) && $data['messages'][0]['status'] === '0') {
            return ['success' => true, 'raw' => $resp, 'error' => null];
        }
        $err = $data['messages'][0]['error-text'] ?? 'خطأ غير معروف';
        return ['success' => false, 'raw' => $resp, 'error' => "Vonage: {$err}"];
    }

    // ─────────────────────────────────────────────
    //  Unifonic
    // ─────────────────────────────────────────────
    private static function viaUnionic($to, $msg, $sender, $appSid): array
    {
        if (empty($appSid)) {
            return ['success' => false, 'raw' => '', 'error' => 'Unifonic: App SID فارغ'];
        }

        $url = 'https://el.cloud.unifonic.com/rest/SMS/messages/send';
        $resp = self::curlPost($url, ['AppSid' => $appSid, 'Recipient' => $to, 'SenderID' => $sender, 'Body' => $msg]);
        $data = json_decode($resp, true);

        if (!empty($data['success'])) {
            return ['success' => true, 'raw' => $resp, 'error' => null];
        }
        $err = $data['message'] ?? $data['errorCode'] ?? 'خطأ';
        return ['success' => false, 'raw' => $resp, 'error' => "Unifonic: {$err}"];
    }

    // ─────────────────────────────────────────────
    //  Taqnyat (تقنيات)
    // ─────────────────────────────────────────────
    private static function viaTaqnyat($to, $msg, $sender, $bearer): array
    {
        if (empty($bearer)) {
            return ['success' => false, 'raw' => '', 'error' => 'Taqnyat: Bearer Token فارغ'];
        }

        $url = 'https://api.taqnyat.sa/v1/messages';
        $body = json_encode(['recipients' => [$to], 'body' => $msg, 'sender' => $sender]);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15, CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', "Authorization: Bearer {$bearer}"],
        ]);
        $resp = curl_exec($ch);
        $data = json_decode($resp, true);

        if (isset($data['statusCode']) && $data['statusCode'] === 201) {
            return ['success' => true, 'raw' => $resp, 'error' => null];
        }
        $err = $data['message'] ?? 'خطأ';
        return ['success' => false, 'raw' => $resp, 'error' => "Taqnyat: {$err}"];
    }

    // ─────────────────────────────────────────────
    //  Gateway.sa
    // ─────────────────────────────────────────────
    private static function viaGatewaySa($to, $msg, $sender, $apiId, $user, $pass): array
    {
        if (empty($apiId)) {
            return ['success' => false, 'raw' => '', 'error' => 'Gateway.sa: API ID فارغ'];
        }

        $url = 'https://gw.hisms.ws/sendsms.php';
        $resp = self::curlPost($url, ['apiid' => $apiId, 'user' => $user, 'pass' => $pass, 'to' => $to, 'sender' => $sender, 'msg' => $msg]);

        return ['success' => strpos($resp, 'ERROR') === false, 'raw' => $resp, 'error' => strpos($resp, 'ERROR') !== false ? "Gateway: {$resp}" : null];
    }

    // ─────────────────────────────────────────────
    //  Msegat
    // ─────────────────────────────────────────────
    private static function viaMsegat($to, $msg, $sender, $apiKey, $user): array
    {
        if (empty($apiKey)) {
            return ['success' => false, 'raw' => '', 'error' => 'Msegat: API Key فارغ'];
        }

        $url = 'https://www.msegat.com/gw/sendsms.php';
        $body = json_encode(['apiKey' => $apiKey, 'userName' => $user, 'numbers' => $to, 'userSender' => $sender, 'msg' => $msg]);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15, CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        ]);
        $resp = curl_exec($ch);
        $data = json_decode($resp, true);

        if (isset($data['code']) && $data['code'] === '1') {
            return ['success' => true, 'raw' => $resp, 'error' => null];
        }
        $err = $data['message'] ?? 'خطأ';
        return ['success' => false, 'raw' => $resp, 'error' => "Msegat: {$err}"];
    }

    // ─────────────────────────────────────────────
    //  Custom / Other — POST form data to api_url
    // ─────────────────────────────────────────────
    private static function viaCustom($to, $msg, $sender, $apiUrl, $apiKey, $apiSecret, $user, $pass): array
    {
        if (empty($apiUrl)) {
            return ['success' => false, 'raw' => '', 'error' => 'أدخل رابط API'];
        }

        $params = ['to' => $to, 'message' => $msg, 'sender' => $sender];
        if (!empty($apiKey))    $params['api_key']    = $apiKey;
        if (!empty($apiSecret)) $params['api_secret'] = $apiSecret;
        if (!empty($user))      $params['username']   = $user;
        if (!empty($pass))      $params['password']   = $pass;

        $resp = self::curlPost($apiUrl, $params);
        return ['success' => true, 'raw' => $resp, 'error' => null];
    }

    // ─────────────────────────────────────────────
    //  WhatsBox (legacy)
    // ─────────────────────────────────────────────
    public static function sendWhatsboxSMS($to, $message)
    {
        $url = "https://whatsbox.net/v2/send?type=text&api-key=" . self::SMS_API_KEY
             . "&sender-id=" . self::SMS_SENDER_ID . "&to={$to}&text={$message}";

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $resp = curl_exec($curl);
        return $resp;
    }

    // ─────────────────────────────────────────────
    //  curl helper
    // ─────────────────────────────────────────────
    private static function curlPost(string $url, array $fields): string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($fields),
        ]);
        $resp = curl_exec($ch);
        return $resp ?: '';
    }
}
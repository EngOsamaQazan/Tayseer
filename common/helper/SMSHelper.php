<?php

namespace common\helper;

class SMSHelper
{
    const SMS_API_KEY = '6228d7a5b7236';
    const SMS_SENDER_ID = '962797707059';

    public static function sendWhatsboxSMS($to, $message)
    {
        $smsApiKey = self::SMS_API_KEY;
        $smsSenderID = self::SMS_SENDER_ID;

        $url = "https://whatsbox.net/v2/send?type=text&api-key={$smsApiKey}&sender-id={$smsSenderID}&to={$to}&text={$message}";

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $resp = curl_exec($curl);
        return $resp;
    }
}
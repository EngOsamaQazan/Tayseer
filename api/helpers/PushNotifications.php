<?php

namespace api\helpers;

use Yii;

class PushNotifications {

    // (Android)API access key from Google API's Console.
    private static $_API_ACCESS_KEY = 'AAAAFpRf20U:APA91bERS00GyhK-xiC6AtaHxVnPK2bHSD-QCHmifEgFTflhht3r0vVBwhP3G21K8XPk4-eO4uYbXfvOSh2JIQPfP8O-M7XjpjM9tndAXHvS8GHHEWa_Kz-hm01T1hiJ1fvxJAZjaNK-';
    
    // (iOS) Private key's passphrase.
    private static $_passphrase = '123';
    // (Windows Phone 8) The name of our push channel.
    private static $_channelName = '';

    // Change the above three vriables as per your app.
    public function __construct() {
        exit('Init function is not allowed');
    }

    // Sends Push notification for Android users
    public static function android($data, $reg_id) {
        $url = 'https://fcm.googleapis.com/fcm/send';
        $message = array(
            'body' => $data['message'],
            'title' => 'GHP Managment System',
            'subtitle' => @$data['subtitle'],
            'tickerText' => @$data['tickerText'],
            'vibrate' => 1,
            'sound' => 'default',
            'largeIcon' => 'large_icon',
            'smallIcon' => 'small_icon'
        );

        $headers = array(
            'Authorization: key=' . self::$_API_ACCESS_KEY,
            'Content-Type: application/json'
        );

        $fields = array(
            'registration_ids' => array($reg_id),
            'notification' => $message,
        );

        return self::useCurl($url, $headers, json_encode($fields));
    }

    // Sends Push's toast notification for Windows Phone 8 users
    public static function WP($data, $uri) {
        $delay = 2;
        $msg = "<?xml version=\"1.0\" encoding=\"utf-8\"?>" .
                "<wp:Notification xmlns:wp=\"WPNotification\">" .
                "<wp:Toast>" .
                "<wp:Text1>" . htmlspecialchars($data['mtitle']) . "</wp:Text1>" .
                "<wp:Text2>" . htmlspecialchars($data['mdesc']) . "</wp:Text2>" .
                "</wp:Toast>" .
                "</wp:Notification>";

        $sendedheaders = array(
            'Content-Type: text/xml',
            'Accept: application/*',
            'X-WindowsPhone-Target: toast',
            "X-NotificationClass: $delay"
        );

        $response = self::useCurl($uri, $sendedheaders, $msg);

        $result = array();
        foreach (explode("\n", $response) as $line) {
            $tab = explode(":", $line, 2);
            if (count($tab) == 2)
                $result[$tab[0]] = trim($tab[1]);
        }

        return $result;
    }

    // Sends Push notification for iOS users
    public static function ios($data, $devicetoken) {
        $is_dev = (strpos($_SERVER['SERVER_NAME'], 'dev') || strpos($_SERVER['SERVER_NAME'], 'qa'));
        $is_demo = (strpos($_SERVER['SERVER_NAME'], 'dotdemos'));


        $iosLink = self::getIosPushLink();
        $certificate = self::getIosCertificate();

        $deviceToken = $devicetoken;


        $ctx = stream_context_create();
        @stream_context_set_option($ctx, 'ssl', 'local_cert', $certificate);
        @stream_context_set_option($ctx, 'ssl', 'passphrase', self::$_passphrase);
        // Open a connection to the APNS server
        @$fp = stream_socket_client($iosLink, $err, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);
        if (!$fp)
            exit("Failed to connect: $err $errstr" . PHP_EOL);
        // Create the payload body
        $body['aps'] = array(
            'alert' => array(
                'title' => @$data['title'],
                'message' => $data['message'],
                'subtitle' => @$data['subtitle'],
                'tickerText' => @$data['tickerText'],
                'msgcnt' => 1,
                'vibrate' => 1
            ),
            'sound' => 'default'
        );
        // Encode the payload as JSON
        $payload = json_encode($body);
        // Build the binary notification


        @$msg = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($payload)) . $payload;
        // Send it to the server
        @$result = fwrite($fp, $msg, strlen($msg));

        // Close the connection to the server
        @fclose($fp);
        /* if (!$result)
          return false;
          else
          return true; */
    }

    // Curl 
    private static function useCurl($url, $headers, $fields = null) {
        // Open connection
        $ch = curl_init();
        if ($url) {
            // Set the url, number of POST vars, POST data
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            // Disabling SSL Certificate support temporarly
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            if ($fields) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
            }
            // Execute post
            $result = curl_exec($ch);
            if ($result === FALSE) {
                die('Curl failed: ' . curl_error($ch));
            }
            return $result;
        }
    }

    public static function getIosPushLink() {
        $isDev = (strpos($_SERVER['SERVER_NAME'], 'dev') || strpos($_SERVER['SERVER_NAME'], 'qa'));
        return (($isDev) ? 'ssl://gateway.sandbox.push.apple.com:2195' : 'ssl://gateway.push.apple.com:2195');
    }

    public static function getIosCertificate() {
        $isDev = (strpos($_SERVER['SERVER_NAME'], 'dev') || strpos($_SERVER['SERVER_NAME'], 'qa'));
        $isDemo = (strpos($_SERVER['SERVER_NAME'], 'dotdemos'));

        $certificate = '/push/live.pem';
        if ($isDev)
            $certificate = '/push/dev.pem';
        elseif ($isDemo)
            $certificate = '/push/prod.pem';
        return Yii::$app->getBasePath() . $certificate;
    }

}

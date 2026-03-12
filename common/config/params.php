<?php
return [
    /**
     * مفتاح Google Maps API — لخريطة تتبع الموظفين (نظام الحضور والانصراف).
     * احصل على المفتاح من: https://console.cloud.google.com/ → APIs & Services → Credentials
     * فعّل "Maps JavaScript API" ثم أنشئ مفتاح (API key).
     * يمكن تعيينه هنا أو في params-local.php: 'googleMapsApiKey' => 'AIza...',
     */
    'googleMapsApiKey' => null,

    /**
     * قاعدة روابط صور العملاء (ImageManager).
     * إذا مُعرّف: تُحمّل كل الصور من هذا العنوان (مثلاً من سيرفر جادل).
     * الطريقة القديمة كانت: https://jadal.aqssat.co/images/imagemanager/{id}_{fileHash}.{ext}
     * على نماء يمكن تعيين: 'customerImagesBaseUrl' => 'https://jadal.aqssat.co' في params-local
     */
    'customerImagesBaseUrl' => null,
];

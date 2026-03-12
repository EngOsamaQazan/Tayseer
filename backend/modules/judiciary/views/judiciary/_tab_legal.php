<?php
/**
 * تبويب المحولين للشكوى — يُعرض عبر AJAX داخل الشاشة الموحدة
 */
use yii\helpers\Url;

$legalUrl = Url::to(['/contracts/contracts/index-legal-department', '_iframe' => 1]);
?>

<script>
$('#lh-badge-legal').text('<?= $dataCount ?>');
</script>

<div style="margin:-16px;position:relative">
    <div id="lh-legal-loading" style="display:none;position:absolute;top:0;left:0;right:0;bottom:0;background:rgba(255,255,255,.8);z-index:5;display:flex;align-items:center;justify-content:center">
        <div class="lh-loader"><i class="fa fa-spinner"></i><span>جاري التحميل...</span></div>
    </div>
    <iframe id="lh-legal-iframe" src="<?= $legalUrl ?>" style="width:100%;border:none;min-height:700px;display:block"></iframe>
</div>

<script>
(function(){
    var iframe = document.getElementById('lh-legal-iframe');
    var loading = document.getElementById('lh-legal-loading');

    function resize() {
        try { iframe.style.height = Math.max(700, iframe.contentWindow.document.body.scrollHeight + 20) + 'px'; } catch(e) {}
    }

    iframe.addEventListener('load', function() {
        loading.style.display = 'none';
        resize();
    });

    setInterval(resize, 2000);
})();
</script>

<?php

use yii\helpers\Html;
use yii\bootstrap\Modal;

/* @var $this \yii\web\View */
/* @var $content string */

?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
    <head>
        <meta charset="<?= Yii::$app->charset ?>"/>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <?= Html::csrfMetaTags() ?>
        <?php $this->head() ?>
    </head>
    <body class="hold-transition skin-blue sidebar-mini layout-print">
        <?php $this->beginBody() ?>
            <?= $this->render('content.php', ['content' => $content]) ?>
        <?php $this->endBody() ?>
    </body>
</html>
<?php $this->endPage() ?>
<?php

use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model backend\models\Employee */
?>
<div class="employee-view">
 
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'username',
            'email:email',
            'password_hash',
            'auth_key',
            'confirmed_at',
            'unconfirmed_email:email',
            'blocked_at',
            'registration_ip',
            'created_at',
            'updated_at',
            'flags',
            'last_login_at',
            'verification_token',
            'name',
            'public_email:email',
            'gravatar_email:email',
            'gravatar_id',
            'location',
            'website',
            'bio:ntext',
            'timezone',
            'middle_name',
            'last_name',
            'employee_type',
            'employee_status',
            'date_of_hire',
            'department',
            'job_title',
            'reporting_to',
            'mobile',
            'nationality',
            'gender',
            'marital_status',
        ],
    ]) ?>

</div>

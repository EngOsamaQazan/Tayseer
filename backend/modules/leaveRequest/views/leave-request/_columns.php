<?php


return [

    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'Reason',
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'start_at',
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'end_at',
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'leave_policy',
        'value' => 'leavePolicy.title'
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'status',
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'created_by',
        'value'=>'createdBy.username'
    ],


];
        
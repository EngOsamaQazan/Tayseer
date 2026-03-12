<?php


namespace common\helper;

use  backend\modules\court\models\Court;
use  backend\modules\judiciary\models\Judiciary;
use  backend\modules\lawyers\models\Lawyers;

class FindJudicary
{
    public static function findYearJudicary($judiciary_id)
    {
        $model = Judiciary::findOne(['id' => $judiciary_id]);
        if (empty($model->year)) {
            return 'لا يوجد ';
        } else {
            return $model->year;
        }

    }

    public static function findJudiciaryNumberJudicary($judiciary_id)
    {
        $model = Judiciary::findOne(['id' => $judiciary_id]);
        if (empty($model->judiciary_number)) {
            return 'لا يوجد  ';
        } else {
            return $model->judiciary_number;
        }
    }

    public static function findCourtJudicary($judiciary_id)
    {
        $model = Judiciary::findOne(['id' => $judiciary_id]);

        $court = Court::findOne(['id' => $model->court_id]);
        if (empty($court)) {
            return 'لا يوجد  ';
        } else {
            return $court->name;
        }
    } public static function findLawyerJudicary($judiciary_id)
    {
        $model = Judiciary::findOne(['id' => $judiciary_id]);

        $lawyer = Lawyers::findOne(['id' => $model->lawyer_id]);
        if (empty($lawyer)) {
            return 'لا يوجد  ';
        } else {
            return $lawyer->name;
        }
    }
    public static function findJudiciaryContract($judiciary_id)
    {
        $model = Judiciary::findOne(['id' => $judiciary_id]);
        if (empty($model->contract_id)) {
            return 'لا يوجد  ';
        } else {
            return $model->contract_id;
        }
    }
}
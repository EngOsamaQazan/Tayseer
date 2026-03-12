<?php


namespace common\components;


class customersInformation
{
    public static function getStatus($id){
        $staus =\backend\modules\status\models\Status::find()->where(['id'=>$id])->all();
        foreach ($staus as $stau){
            return $stau->name;
        }
    }
    public static function getCitys($id){
        $cities =\backend\modules\city\models\City::find()->where(['id'=>$id])->all();
        foreach ($cities as $citiy){
            return $citiy->name;
        }
    }
    public static function getCitizen($id){
        $citizen =\backend\modules\citizen\models\Citizen::find()->where(['id'=>$id])->all();
        foreach ($citizen as $citizen){
            return $citizen->name;
        }
    }
    public static function getJobs($id){
        $models =\backend\modules\jobs\models\Jobs::find()->where(['id'=>$id])->all();
        foreach ($models as $model){
            return $model->name;
        }
    }
    public static function getHearAboutUs($id){
        $hearAboutUs =\backend\modules\hearAboutUs\models\HearAboutUs::find()->where(['id'=>$id])->all();
        foreach ($hearAboutUs as $hearAbout){
            return $hearAbout->name;
        }
    }
    public static function getBank($id){
       $banks = \backend\modules\bancks\models\Bancks::find()->where(['id'=>$id])->all();
        foreach ($banks as $bank){
            return $bank->name;
        }
    }
    public static function getRealEstate($id){
        $models =\backend\modules\realEstate\models\RealEstate::find()->where(['customer_id'=>$id])->all();
        $realEstate = [];
        foreach ($models as $model){
            $realEstate['property_type'.$model->id] = $model->property_type;
            $realEstate['property_number'.$model->id] = $model->property_number;
         }
        return $realEstate;


    }

    public static function getSex($id){
        if ($id == 1){
             return 'انثى';
        }else{
            return 'ذكر';
        }
    }

}
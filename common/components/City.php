<?php


namespace common\components;


class City
{
 public static function findMyCity($id){
     $city = \backend\modules\city\models\City::find()->where(['id'=>$id])->all();
     foreach ($city as $myCity){

         return $myCity->name;
     }
 }
}
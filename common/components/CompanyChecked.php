<?php


namespace common\components;

use backend\modules\bancks\Bancks;
use backend\modules\companies\models\Companies;
use backend\modules\companyBanks\models\CompanyBanks;

class CompanyChecked
{
    public $id;

    private static ?Companies $_primaryCompany = null;
    private static bool $_primaryLoaded = false;

    private static function loadPrimary(): ?Companies
    {
        if (!static::$_primaryLoaded) {
            static::$_primaryCompany = Companies::find()
                ->where(['is_primary_company' => 1])
                ->limit(1)
                ->one();
            static::$_primaryLoaded = true;
        }
        return static::$_primaryCompany;
    }

    function therePrimaryCompany()
    {
        return static::loadPrimary() ? 0 : 1;
    }

    function findPrimaryCompany()
    {
        return static::loadPrimary() ?: '';
    }

    function findPrimaryCompanyBancks()
    {
        $primary = static::loadPrimary();
        if (!$primary) return '';

        $bancks = CompanyBanks::find()
            ->alias('cb')
            ->innerJoin('{{%bancks}} b', 'b.id = cb.bank_id')
            ->select(['b.name as bank_name', 'cb.bank_number'])
            ->where(['cb.company_id' => $primary->id])
            ->asArray()
            ->all();

        $parts = [];
        foreach ($bancks as $b) {
            $parts[] = ($b['bank_name'] ?? '') . ' رقم الحساب ' . ($b['bank_number'] ?? '');
        }
        return implode(',', $parts);
    }

    function is_primary_company()
    {
        $primary = static::loadPrimary();
        return $primary ? $primary->id : 0;
    }

    function findCompany()
    {
        return Companies::find()->where(['id' => $this->id])->one();
    }
}
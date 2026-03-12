<?php

namespace backend\modules\followUp\interfaces;


interface contract
{

    public $model;
    public function is_active($contract_id);
}

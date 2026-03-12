<?php

// namespace noam148\imagemanager\components;

namespace backend\widgets;

use yii\base\Widget;


class CardsWidget extends Widget
{


    public $value,$icone,$text,$type ;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        return $this->render('cards_widgets/'.$this->type, [
            'value' => $this->value,
            'text' => $this->text
        ]);
    }

    
}

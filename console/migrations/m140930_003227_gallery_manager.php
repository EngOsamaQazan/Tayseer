<?php

use yii\db\Migration;
class m140930_003227_gallery_manager extends Migration
{

    public function up()
    {

        $this->createTable(
            '{{%gallery_image}}',
           [
                'id' => $this->primaryKey(10)->unsigned(),
                'type' =>  $this->string(128)->notNull(),
                'ownerId' =>  $this->string(128)->notNull(),
                'rank' => $this->integer(10)->notNull()->defaultValue(0),
                'name' => $this->string(128),
                'description' => $this->text(),
            ]
        );
    }

    public function down()
    {
        $this->dropTable('{{%gallery_image}}');
    }
}
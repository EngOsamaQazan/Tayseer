<?php

use yii\db\Migration;

/**
 * Class m230120_150838_create_judiciary_inform_address
 */
class m230120_150838_create_judiciary_inform_address extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        
        $this->createTable('{{%judiciary_inform_address}}', [
            'id' => $this->primaryKey(),
            'address'=>$this->string(),
            'created_at'=>$this->integer(),
            'updated_at'=>$this->integer(),
            'created_by'=>$this->integer(),
            'is_deleted'=>$this->boolean()
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('judiciary_inform_address', 'is_deleted');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m230120_150838_add_is_deleted_to_judiciary_inform_address cannot be reverted.\n";

        return false;
    }
    */
}

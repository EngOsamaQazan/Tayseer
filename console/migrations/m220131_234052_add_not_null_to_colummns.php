<?php

use yii\db\Migration;

/**
 * Class m220131_234052_add_not_null_to_colummns
 */
class m220131_234052_add_not_null_to_colummns extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn('{{%customers}}','city',$this->string()->notNull());
        $this->alterColumn('{{%customers}}','sex',$this->integer()->notNull());
        $this->alterColumn('{{%customers}}','birth_date',$this->date()->notNull());
        $this->alterColumn('{{%customers}}','id_number',$this->string()->notNull());
        $this->alterColumn('{{%customers}}','job_title',$this->integer()->notNull());
        $this->alterColumn('{{%customers}}','hear_about_us',$this->text()->notNull());
        $this->alterColumn('{{%customers}}','citizen',$this->string(50)->notNull());
        $this->alterColumn('{{%customers}}','is_social_security',$this->tinyInteger(1)->notNull());
        $this->alterColumn('{{%customers}}','primary_phone_number',$this->text()->notNull());
        $this->alterColumn('{{%customers}}','do_have_any_property',$this->tinyInteger(1)->notNull());


        $this->alterColumn('{{%jobs}}','job_type',$this->integer()->notNull());
        $this->alterColumn('{{%jobs}}','name',$this->string()->notNull());

        $this->alterColumn('{{%contracts}}','company_id',$this->integer()->notNull());
        $this->alterColumn('{{%contracts}}','first_installment_value',$this->double()->defaultValue(0));
        $this->alterColumn('{{%contracts}}','commitment_discount',$this->double()->defaultValue(0));
        $this->alterColumn('{{%contracts}}','loss_commitment',$this->integer()->defaultValue(0));
        $this->alterColumn('{{%contracts}}','monthly_installment_value',$this->double()->defaultValue(0));
        $this->alterColumn('{{%contracts}}','monthly_installment_value',$this->double()->defaultValue(0));

        $this->alterColumn('{{%address}}','customers_id',$this->integer());
        $this->alterColumn('{{%address}}','address',$this->string());
        $this->alterColumn('{{%address}}','address_type',$this->integer());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m220131_234052_add_not_null_to_colummns cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220131_234052_add_not_null_to_colummns cannot be reverted.\n";

        return false;
    }
    */
}

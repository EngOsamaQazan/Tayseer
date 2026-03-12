<?php

use yii\db\Migration;

/**
 * Class m230610_190214_deduction_document
 */
class m230610_190214_deduction_document extends Migration
{
    public function up()
    {
        $this->createTable('{{%deduction_document}}', [
            'id' => $this->primaryKey(),
            'judiciary_id' => $this->integer()->notNull(),
            'document_date' => $this->date()->notNull(),
            'document_number' => $this->string()->notNull(),
            /* we have 2 types of documents: 1-enQuiry document
             2- vehicle detention document 3- departure detention document
             4- bank account detention document 5- other detention document
             */
            'document_type' => $this->tinyInteger()->notNull(),
            'document_status' => $this->string()->notNull(),
            'document_notes' => $this->text(),
            'document_image' => $this->string(),
            'created_at' => $this->dateTime()->notNull(),
            'updated_at' => $this->dateTime(),

        ]);

        // Add foreign key constraint
        $this->addForeignKey(
            'fk-deduction_document-judiciary_id',
            '{{%deduction_document}}',
            'judiciary_id',
            '{{%os_judiciary}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    public function down()
    {
        $this->dropForeignKey('fk-deduction_document-judiciary_id', '{{%deduction_document}}');
        $this->dropTable('{{%deduction_document}}');
    }
}
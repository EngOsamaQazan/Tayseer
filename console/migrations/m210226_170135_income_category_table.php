<?php

use yii\db\Migration;

/**
 * Class m210226_170135_income_category_table
 */
class m210226_170135_income_category_table extends Migration {

    /**
     * {@inheritdoc}
     */
    public function safeUp() {
        $this->createTable('{{%income_category}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(100),
            'created_at' => $this->integer()->notNull(),
            'created_by' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
            'last_updated_by' => $this->integer()->notNull(),
            'is_deleted' => $this->integer(1)->notNull(),
            'description' => $this->text(500)->notNull()
        ]);
        $this->createIndex('index_income_category_id', '{{%income_category}}', 'id');
        $this->addForeignKey('fk_income_category_created_by', '{{%income_category}}', 'created_by', '{{%user}}', 'id');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown() {
        $this->dropTable('{{%income_category}}');
    }
}

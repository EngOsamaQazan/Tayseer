<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%update_updated_at_in_curt}}`.
 */
class m210128_153739_create_update_updated_at_in_curt_table extends Migration {

    /**
     * {@inheritdoc}
     */
    public function safeUp() {
        $this->dropColumn('{{%court}}', 'updates_at');
        $this->addColumn('{{%court}}', 'updated_at', $this->integer()->notNull());

        $this->dropColumn('{{%court}}', 'last_update_by');
        $this->addColumn('{{%court}}', 'last_updated_by', $this->integer()->notNull());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown() {
        
    }

}

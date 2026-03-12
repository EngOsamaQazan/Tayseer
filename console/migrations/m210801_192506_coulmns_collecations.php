<?php

use yii\db\Migration;

/**
 * Class m210801_192506_coulmns_collecations
 */
class m210801_192506_coulmns_collecations extends Migration {

    /**
     * {@inheritdoc}
     */
    public function safeUp() {

        $this->execute("ALTER TABLE `os_income` CHANGE `_by` `_by` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;");
        $this->execute("ALTER TABLE `os_income` CHANGE `notes` `notes` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;");
 
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown() {
        echo "m210801_192506_coulmns_collecations cannot be reverted.\n";

        return false;
    }

    /*
      // Use up()/down() to run migration code without a transaction.
      public function up()
      {

      }

      public function down()
      {
      echo "m210801_192506_coulmns_collecations cannot be reverted.\n";

      return false;
      }
     */
}

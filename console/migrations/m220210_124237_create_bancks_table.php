<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%bancks}}`.
 */
class m220210_124237_create_bancks_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%bancks}}', [
            'id' => $this->primaryKey(),
            'name'=>$this->string()->notNull(),
            'created_at'=>$this->integer(),
            'updated_at'=>$this->integer(),
            'created_by'=>$this->integer(),
            'last_updated_by'=>$this->integer(),
            'is_deleted'=>$this->integer()->defaultValue(0)
        ]);
        $this->insert('{{%bancks}}',['name'=>'البنك العربي الإسلامي الدولي']);
        $this->insert('{{%bancks}}',['name'=>'البنك الإسلامي الأردني']);
        $this->insert('{{%bancks}}',['name'=> 'بنك صفوة الإسلامي']);
        $this->insert('{{%bancks}}',['name'=> 'مصرف الراجحي']);
        $this->insert('{{%bancks}}',['name'=>  'البنك العربي']);
        $this->insert('{{%bancks}}',['name'=> 'بنك المؤسسة العربية المصرفية (الأردن)']);
        $this->insert('{{%bancks}}',['name'=>'بنك الأردن']);
        $this->insert('{{%bancks}}',['name'=>'بنك القاهرة عمان']);
        $this->insert('{{%bancks}}',['name'=>'بنك المال الأردني']);
        $this->insert('{{%bancks}}',['name'=>'البنك التجاري الأردني']);
        $this->insert('{{%bancks}}',['name'=>'البنك الأردني الكويتي']);
        $this->insert('{{%bancks}}',['name'=>'البنك الأهلي الأردني']);
        $this->insert('{{%bancks}}',['name'=> 'بنك الإسكان للتجارة والتمويل']);
        $this->insert('{{%bancks}}',['name'=>'بنك الاستثمار العربي الأردني']);
        $this->insert('{{%bancks}}',['name'=>'البنك الاستثماري']);
        $this->insert('{{%bancks}}',['name'=>'بنك سوسيته جنرال (الأردن)']);
        $this->insert('{{%bancks}}',['name'=>'بنك الاتحاد']);
        $this->insert('{{%bancks}}',['name'=>'ستاندرد تشارترد']);
        $this->insert('{{%bancks}}',['name'=>'البنك العقاري المصري العربي']);
        $this->insert('{{%bancks}}',['name'=>'سيتي بنك']);
        $this->insert('{{%bancks}}',['name'=> 'مصرف الرافدين']);
        $this->insert('{{%bancks}}',['name'=> 'بنك الكويت الوطني']);
        $this->insert('{{%bancks}}',['name'=>'بنك لبنان والمهجر']);
        $this->insert('{{%bancks}}',['name'=>'بنك عوده']);
        $this->insert('{{%bancks}}',['name'=>'بنك أبو ظبي الوطني']);
        $this->insert('{{%bancks}}',['name'=>'لا يوجد']);
        $this->insert('{{%bancks}}',['name'=>'بنك الإئتمان العسكري']);

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%bancks}}');
    }
}

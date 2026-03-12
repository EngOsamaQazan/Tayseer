<?php

use yii\db\Migration;

class m260224_100000_expand_address_fields extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%address}}', 'address_city', $this->string(255)->null()->after('address'));
        $this->addColumn('{{%address}}', 'address_area', $this->string(255)->null()->after('address_city'));
        $this->addColumn('{{%address}}', 'address_street', $this->string(500)->null()->after('address_area'));
        $this->addColumn('{{%address}}', 'address_building', $this->string(255)->null()->after('address_street'));
        $this->addColumn('{{%address}}', 'postal_code', $this->string(20)->null()->after('address_building'));
        $this->addColumn('{{%address}}', 'plus_code', $this->string(20)->null()->after('postal_code'));
        $this->addColumn('{{%address}}', 'latitude', $this->decimal(11, 8)->null()->after('plus_code'));
        $this->addColumn('{{%address}}', 'longitude', $this->decimal(11, 8)->null()->after('latitude'));
    }

    public function safeDown()
    {
        $this->dropColumn('{{%address}}', 'longitude');
        $this->dropColumn('{{%address}}', 'latitude');
        $this->dropColumn('{{%address}}', 'plus_code');
        $this->dropColumn('{{%address}}', 'postal_code');
        $this->dropColumn('{{%address}}', 'address_building');
        $this->dropColumn('{{%address}}', 'address_street');
        $this->dropColumn('{{%address}}', 'address_area');
        $this->dropColumn('{{%address}}', 'address_city');
    }
}

<?php

use yii\db\Migration;

/**
 * Fix existing stock movement dates for contract_sale movements.
 * Sets created_at to match the contract's Date_of_sale instead of the time
 * the movement record was inserted.
 * Also fixes serial numbers sold_at to match Date_of_sale.
 */
class m260318_300000_fix_stock_movement_dates_from_contracts extends Migration
{
    public function safeUp()
    {
        $prefix = $this->db->tablePrefix;

        // Fix stock movements: contract_sale OUT movements
        $affected = $this->db->createCommand("
            UPDATE {$prefix}stock_movements sm
            INNER JOIN {$prefix}contracts c ON sm.reference_id = c.id
            SET sm.created_at = UNIX_TIMESTAMP(c.Date_of_sale)
            WHERE sm.reference_type = 'contract_sale'
              AND c.Date_of_sale IS NOT NULL
        ")->execute();
        echo "    > Updated {$affected} stock movement(s) (contract_sale) dates.\n";

        // Fix serial numbers: sold_at should match Date_of_sale
        $affectedSerials = $this->db->createCommand("
            UPDATE {$prefix}inventory_serial_numbers sn
            INNER JOIN {$prefix}contracts c ON sn.contract_id = c.id
            SET sn.sold_at = UNIX_TIMESTAMP(c.Date_of_sale)
            WHERE sn.status = 'sold'
              AND sn.contract_id IS NOT NULL
              AND c.Date_of_sale IS NOT NULL
        ")->execute();
        echo "    > Updated {$affectedSerials} serial number(s) sold_at dates.\n";
    }

    public function safeDown()
    {
        echo "    > This migration cannot be reverted (dates already overwritten).\n";
        return false;
    }
}

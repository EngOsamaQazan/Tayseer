<?php

namespace backend\dto;

/**
 * Lightweight data transfer object for unified entity resolution.
 * Represents a resolved entity from any source table (banks, jobs, authorities).
 */
class EntityDTO
{
    public $type;
    public $source_id;
    public $display_name;
    public $source_table;

    public function __construct(string $type, int $source_id, string $display_name, string $source_table)
    {
        $this->type = $type;
        $this->source_id = $source_id;
        $this->display_name = $display_name;
        $this->source_table = $source_table;
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'source_id' => $this->source_id,
            'display_name' => $this->display_name,
            'source_table' => $this->source_table,
        ];
    }
}

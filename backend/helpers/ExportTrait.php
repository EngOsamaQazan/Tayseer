<?php

namespace backend\helpers;

/**
 * Trait to add export actions to any controller.
 * Usage: `use ExportTrait;` then define getExportConfig() or call exportData() directly.
 */
trait ExportTrait
{
    /**
     * @param \yii\data\DataProviderInterface $dataProvider
     * @param array $config Same as ExportHelper::toExcel / toPdf
     * @param string $format 'excel' or 'pdf'
     * @return \yii\web\Response
     */
    protected function exportData($dataProvider, array $config, string $format = 'excel')
    {
        $dataProvider->pagination = false;
        $models = $dataProvider->getModels();

        $config['rows'] = $models;

        if ($format === 'pdf') {
            return ExportHelper::toPdf($config);
        }
        return ExportHelper::toExcel($config);
    }

    /**
     * Exports from raw array data (no DataProvider needed).
     */
    protected function exportArrayData(array $rows, array $config, string $format = 'excel')
    {
        $config['rows'] = $rows;

        if ($format === 'pdf') {
            return ExportHelper::toPdf($config);
        }
        return ExportHelper::toExcel($config);
    }
}

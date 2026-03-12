<?php

namespace backend\modules\contractDocumentFile\models;

use Yii;

/**
 * This is the model class for table "os_contract_document_file".
 *
 * @property int $id
 * @property string|null $document_type
 * @property int|null $contract_id
 */
class ContractDocumentFile extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'os_contract_document_file';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['contract_id'], 'integer'],
            [['document_type'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'document_type' => Yii::t('app', 'Document Type'),
            'contract_id' => Yii::t('app', 'Contract ID'),
        ];
    }
}

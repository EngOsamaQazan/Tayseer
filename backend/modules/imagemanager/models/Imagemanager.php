<?php

namespace backend\modules\imagemanager\models;

use Yii;

/**
 * This is the model class for table "os_imagemanager".
 *
 * @property int $id
 * @property string $fileName
 * @property string $fileHash
 * @property string|null $contractId
 * @property string|null $groupName
 * @property string $created
 * @property string|null $modified
 * @property int|null $createdBy
 * @property int|null $modifiedBy
 */
class Imagemanager extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'os_ImageManager';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['fileName', 'fileHash', 'created'], 'required'],
            [['created', 'modified'], 'safe'],
            [['createdBy', 'modifiedBy'], 'integer'],
            [['fileName'], 'string', 'max' => 128],
            [['fileHash'], 'string', 'max' => 32],
            [['contractId', 'groupName'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'fileName' => 'File Name',
            'fileHash' => 'File Hash',
            'contractId' => 'Contract ID',
            'groupName' => 'Group Name',
            'created' => 'Created',
            'modified' => 'Modified',
            'createdBy' => 'Created By',
            'modifiedBy' => 'Modified By',
        ];
    }
}

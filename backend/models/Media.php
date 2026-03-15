<?php

namespace backend\models;

use Yii;
use backend\helpers\MediaHelper;

/**
 * ActiveRecord for table "os_ImageManager".
 * Standalone replacement for noam148\imagemanager\models\ImageManager.
 *
 * @property int $id
 * @property string $fileName
 * @property string $fileHash
 * @property string|null $contractId
 * @property int|null $customer_id
 * @property string|null $groupName
 * @property string $created
 * @property string|null $modified
 * @property int|null $createdBy
 * @property int|null $modifiedBy
 */
class Media extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return 'os_ImageManager';
    }

    public function rules()
    {
        return [
            [['fileName', 'fileHash', 'created'], 'required'],
            [['created', 'modified'], 'safe'],
            [['createdBy', 'modifiedBy', 'customer_id'], 'integer'],
            [['fileName'], 'string', 'max' => 128],
            [['fileHash'], 'string', 'max' => 32],
            [['contractId', 'groupName'], 'string', 'max' => 50],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id'         => 'ID',
            'fileName'   => 'اسم الملف',
            'fileHash'   => 'Hash',
            'contractId' => 'العقد',
            'customer_id'=> 'العميل',
            'groupName'  => 'نوع المستند',
            'created'    => 'تاريخ الإنشاء',
            'modified'   => 'تاريخ التعديل',
            'createdBy'  => 'أنشئ بواسطة',
            'modifiedBy' => 'عُدّل بواسطة',
        ];
    }

    /** Web-relative URL */
    public function getUrl(): string
    {
        return MediaHelper::url($this->id, $this->fileHash, $this->fileName);
    }

    /** Absolute URL (uses customerImagesBaseUrl when set) */
    public function getAbsoluteUrl(): string
    {
        return MediaHelper::absoluteUrl($this->id, $this->fileHash, $this->fileName);
    }

    /** Smart Media thumbnail URL */
    public function getThumbUrl(): string
    {
        return MediaHelper::thumbUrl($this->id, $this->fileHash, $this->fileName);
    }

    /** Filesystem path */
    public function getFilePath(): string
    {
        return MediaHelper::filePath($this->id, $this->fileHash, $this->fileName);
    }

    /** File extension */
    public function getExtension(): string
    {
        return pathinfo($this->fileName, PATHINFO_EXTENSION);
    }

    /** Check if physical file exists */
    public function fileExists(): bool
    {
        return MediaHelper::exists($this->id, $this->fileHash, $this->fileName);
    }
}

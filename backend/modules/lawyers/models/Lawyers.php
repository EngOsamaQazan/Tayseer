<?php

namespace backend\modules\lawyers\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use yii\behaviors\BlameableBehavior;
use yii2tech\ar\softdelete\SoftDeleteBehavior;
use yii2tech\ar\softdelete\SoftDeleteQueryBehavior;
use \common\models\User;
use backend\modules\LawyersImage\models\LawyersImage;
use yii\web\UploadedFile;
use yii\helpers\FileHelper;
/**
 * This is the model class for table "os_lawyers".
 *
 * @property int $id
 * @property string $name
 * @property string $address
 * @property string $phone_number
 * @property int $status
 * @property int $created_at
 * @property int $updated_at
 * @property int $created_by
 * @property int $last_update_by
 * @property int|null $is_deleted
 * @property string $notes
  * @property string $type
 * @property string $image
 * @property User $createdBy
 * @property Judiciary[] $judiciaries
 */
class Lawyers extends \yii\db\ActiveRecord {

    const STATUS_ACTIVE = 0;
    const STATUS_NONE_ACTIVE = 1;
    public $number_row;
    public $image;
    public $type;
    
    /**
     * {@inheritdoc}
     */
    public static function tableName() {
        return 'os_lawyers';
    }

    public function behaviors() {
        return [
            [
                'class' => BlameableBehavior::className(),
                'createdByAttribute' => 'created_by',
                'updatedByAttribute' => 'last_update_by',
            ],
            [
                'class' => TimestampBehavior::className(),
                'value' => new Expression('UNIX_TIMESTAMP()'),
            ],
            'softDeleteBehavior' => [
                'class' => SoftDeleteBehavior::className(),
                'softDeleteAttributeValues' => [
                    'is_deleted' => true
                ],

                'replaceRegularDelete' => true // mutate native `delete()` method
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules() {
        return [
            [['name', 'status'], 'required'],
            [['status', 'created_at', 'updated_at', 'created_by', 'last_update_by', 'is_deleted'], 'integer'],
            [['notes','type'], 'string'],
            [['name', 'address', 'phone_number'], 'string', 'max' => 255],
            [['image'], 'file',  'mimeTypes' => 'image/*', 'skipOnError' => false, 'extensions'=>'jpg,jpeg,png', 'maxFiles' => 2],
            [['created_by'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['created_by' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels() {
        return [
            'id' => Yii::t('app', 'ID'),
            'name' => Yii::t('app', 'Name'),
            'address' => Yii::t('app', 'Address'),
            'phone_number' => Yii::t('app', 'Phone Number'),
            'status' => Yii::t('app', 'Status'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
            'created_by' => Yii::t('app', 'Created By'),
            'last_update_by' => Yii::t('app', 'Last Update By'),
            'is_deleted' => Yii::t('app', 'Is Deleted'),
            'notes' => Yii::t('app', 'Notes'),
        ];
    }

    /**
     * Gets query for [[CreatedBy]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCreatedBy() {
        return $this->hasOne(\common\models\User::className(), ['id' => 'created_by']);
    }

    /**
     * Gets query for [[Judiciaries]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getJudiciaries() {
        return $this->hasMany(Judiciary::className(), ['lawyer_id' => 'id']);
    }
    public static function find()
    {
        $query = parent::find();
        $query->attachBehavior('softDelete', SoftDeleteQueryBehavior::className());
        return $query->notDeleted();
    }
    public function uploadeMultipleImag($model){
        if (!file_exists('images')) {
            mkdir('images', 0777, true);
        }
        if (!file_exists('images/lawar_images')) {
            mkdir('images/lawar_images', 0777, true);
        }
        if (!empty($model->image)) {
            $model->image = UploadedFile::getInstances($model, 'image');
      
            foreach ($model->image as $file) {
           
                if ($file->saveAs('images/lawar_images/' . $file->baseName . '.' . $file->extension)) {
                   
                    $lawyerImageModel = new LawyersImage();
                    $lawyerImageModel->lawyer_id = $model->id;
                    $lawyerImageModel->image = 'images/lawar_images/' . $file->baseName . '.' . $file->extension;
             
                     $lawyerImageModel->save();
                 } }
         
        }
    }

  
public static function  getLawyerImage($id){

    $lawyer_images = LawyersImage::find()->where(['lawyer_id'=>$id])->all();
    return $lawyer_images;
}
}

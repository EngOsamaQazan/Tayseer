<?php

namespace backend\controllers;

// use backend\controllers\LanguagesController as BaseLanguagesController;
use Yii;
use noam148\imagemanager\controllers\ManagerController as BaseManagerController;
use noam148\imagemanager\models\ImageManager;
// use noam148\imagemanager\assets\ImageManagerModuleAsset; 
use backend\assets\ImageManagerModuleAsset;
use backend\modules\imagemanager\models\ImageManagerSearch;
use noam148\imagemanager\components;
use yii\helpers\Url;
use yii\helpers\Json;
use yii\imagine\Image;
use Imagine\Image\Box;
use Imagine\Image\Palette\RGB;
use Imagine\Image\Point;
use yii\helpers\BaseFileHelper;
use yii\filters\VerbFilter;
use \yii\web\Response;
use yii\web\NotFoundHttpException;
use yii\base\ErrorException;
use backend\modules\imagemanager\Module;

/**
 * LanguagesController implements the CRUD actions for Languages model.
 */
class ImageManagerController extends BaseManagerController {

    public function actionIndex() {

        ImageManagerModuleAsset::register($this->view);

        //get iframe parameters
        $viewMode = Yii::$app->request->get("view-mode", "page");
        $selectType = Yii::$app->request->get("select-type", "input");
        $inputFieldId = Yii::$app->request->get("input-id");
        $cropAspectRatio = Yii::$app->request->get("aspect-ratio");
        $cropViewMode = Yii::$app->request->get("crop-view-mode", 1);
        $defaultImageId = Yii::$app->request->get("image-id");
        $groupName = Yii::$app->request->get("group-name");
        $contractId = Yii::$app->request->get("contract-id");

        if ($viewMode == "iframe") {
            //set layout
            $this->layout = "@backend/views/imagemanager/layouts/blank";


            $aCssFiles = \Yii::$app->getModule('imagemanager')->cssFiles;
            if (is_array($aCssFiles) && count($aCssFiles) > 0) {
                //if exists loop through files and add them to iframe mode
                foreach ($aCssFiles AS $cssFile) {
                    //registrate file
                    $this->view->registerCssFile($cssFile, ['depends' => 'yii\bootstrap\BootstrapAsset']);
                }
            }
        }

        //set baseUrl from image manager
        $sBaseUrl = Url::to(['/imagemanager']);
        //set base url
        $this->view->registerJs("imageManagerModule.baseUrl = '" . $sBaseUrl . "';", 3);
        $this->view->registerJs("imageManagerModule.defaultImageId = '" . $defaultImageId . "';", 3);
        $this->view->registerJs("imageManagerModule.fieldId = '" . $inputFieldId . "';", 3);
        $this->view->registerJs("imageManagerModule.cropRatio = '" . $cropAspectRatio . "';", 3);
        $this->view->registerJs("imageManagerModule.cropViewMode = '" . $cropViewMode . "';", 3);
        $this->view->registerJs("imageManagerModule.selectType = '" . $selectType . "';", 3);
        // $this->view->registerJs("imageManagerModule.groupName = '" . $groupName . "';", 3);
        $this->view->registerJs("imageManagerModule.message = " . Json::encode([
                    'deleteMessage' => Yii::t('imagemanager', 'Are you sure you want to delete this image?'),
                ]) . ";", 3);

        $searchModel = new ImageManagerSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        //render template
        return $this->render(
                        'manager/index', [
                    'searchModel' => $searchModel,
                    'dataProvider' => $dataProvider,
                    'viewMode' => $viewMode,
                    'selectType' => $selectType,
                    'groupName' => $groupName,
                    'contractId' => $contractId,
        ]);
    }

    /**
     * Creates a new ImageManager model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionUpload() {
        //set response header
        Yii::$app->getResponse()->format = Response::FORMAT_JSON;
        // Check if the user is allowed to upload the image
        if (\Yii::$app->getModule('imagemanager')->canUploadImage == false) {
            // Return the response array to prevent from the action being executed any further
            return [];
        }
        // Create the transaction and set the success variable
        $transaction = Yii::$app->db->beginTransaction();
        $bSuccess = false;

        //disable Csrf
        Yii::$app->controller->enableCsrfValidation = false;
        //return default
        $return = $_FILES;
        //set media path
        $sMediaPath = \Yii::$app->imagemanager->mediaPath;
        //create the folder
        BaseFileHelper::createDirectory($sMediaPath);

        //check file isset
        if (isset($_FILES['imagemanagerFiles']['tmp_name'])) {
            //loop through each uploaded file
            foreach ($_FILES['imagemanagerFiles']['tmp_name'] AS $key => $sTempFile) {
                //collect variables
                $sFileName = $_FILES['imagemanagerFiles']['name'][$key];
                $sFileExtension = pathinfo($sFileName, PATHINFO_EXTENSION);
                $iErrorCode = $_FILES['imagemanagerFiles']['error'][$key];
                //if uploaded file has no error code  than continue;
                if ($iErrorCode == 0) {
                    //create a file record
                    $model = new ImageManager();
                    $model->fileName = str_replace("_", "-", $sFileName);
                    $model->fileHash = Yii::$app->getSecurity()->generateRandomString(32);
                    $model->groupName = Yii::$app->request->post('groupName');
                    $model->contractId = Yii::$app->request->post('contractId');
                    //if file is saved add record
                    if ($model->save()) {
                        //move file to dir
                        $sSaveFileName = $model->id . "_" . $model->fileHash . "." . $sFileExtension;
                        //move_uploaded_file($sTempFile, $sMediaPath."/".$sFileName);
                        //save with Imagine class
                        Image::getImagine()->open($sTempFile)->save($sMediaPath . "/" . $sSaveFileName);
                        $bSuccess = true;
                    }
                }
            }
        }

        if ($bSuccess) {
            // The upload action went successful, save the transaction
            $transaction->commit();
        } else {
            // There where problems during the upload, kill the transaction
            $transaction->rollBack();
        }

        //echo return json encoded
        return $return;
    }

    /**
     * Crop image and create new ImageManager model.
     * @return mixed
     */
    public function actionCrop() {
        //return 
        $return = null;
        //disable Csrf
        Yii::$app->controller->enableCsrfValidation = false;
        //set response header
        Yii::$app->getResponse()->format = Response::FORMAT_JSON;
        //set media path
        $sMediaPath = \Yii::$app->imagemanager->mediaPath;
        //get post
        $ImageManager_id = Yii::$app->request->post("ImageManager_id");
        $aCropData = Yii::$app->request->post("CropData");
        //get details
        $modelOriginal = $this->findModel($ImageManager_id);
        //check if path is not null
        if ($modelOriginal->imagePathPrivate !== null && $aCropData !== null) {
            //dimension
            $iDimensionWidth = round($aCropData['width']);
            $iDimensionHeight = round($aCropData['height']);
            //collect variables
            $sFileNameReplace = preg_replace("/_crop_\d+x\d+/", "", $modelOriginal->fileName);
            $sFileName = pathinfo($sFileNameReplace, PATHINFO_FILENAME);
            $sFileExtension = pathinfo($sFileNameReplace, PATHINFO_EXTENSION);
            $sDisplayFileName = $sFileName . "_crop_" . $iDimensionWidth . "x" . $iDimensionHeight . "." . $sFileExtension;

            //start transaction
            $transaction = Yii::$app->db->beginTransaction();
            $bCropSuccess = false;

            //create a file record
            $model = new ImageManager();
            $model->fileName = $sDisplayFileName;
            $model->fileHash = Yii::$app->getSecurity()->generateRandomString(32);
            if (!empty($modelOriginal->groupName))
                $model->groupName = $modelOriginal->groupName;

            if (!empty($modelOriginal->contractId))
                $model->contractId = $modelOriginal->contractId;
            //if file is saved add record
            if ($model->save()) {

                //do crop in try catch
                try {
                    // create file name
                    $sSaveFileName = $model->id . "_" . $model->fileHash . "." . $sFileExtension;

                    // get current/original image data
                    $imageOriginal = Image::getImagine()->open($modelOriginal->imagePathPrivate);
                    $imageOriginalSize = $imageOriginal->getSize();
                    $imageOriginalWidth = $imageOriginalSize->getWidth();
                    $imageOriginalHeight = $imageOriginalSize->getHeight();
                    $imageOriginalPositionX = 0;
                    $imageOriginalPositionY = 0;

                    // create/calculate a canvas size (if canvas is out of the box)
                    $imageCanvasWidth = $imageOriginalWidth;
                    $imageCanvasHeight = $imageOriginalHeight;

                    // update canvas width if X position of croparea is lower than 0 
                    if ($aCropData['x'] < 0) {
                        //set x postion to Absolute value
                        $iAbsoluteXpos = abs($aCropData['x']);
                        //set x position of image
                        $imageOriginalPositionX = $iAbsoluteXpos;
                        //add x position to canvas size
                        $imageCanvasWidth += $iAbsoluteXpos;
                        //update canvas width if croparea is biger than original image
                        $iCropWidthWithoutAbsoluteXpos = ($aCropData['width'] - $iAbsoluteXpos);
                        if ($iCropWidthWithoutAbsoluteXpos > $imageOriginalWidth) {
                            //add ouside the box width
                            $imageCanvasWidth += ($iCropWidthWithoutAbsoluteXpos - $imageOriginalWidth);
                        }
                    } else {
                        // add if crop partly ouside image
                        $iCropWidthWithXpos = ($aCropData['width'] + $aCropData['x']);
                        if ($iCropWidthWithXpos > $imageOriginalWidth) {
                            //add ouside the box width
                            $imageCanvasWidth += ($iCropWidthWithXpos - $imageOriginalWidth);
                        }
                    }

                    // update canvas height if Y position of croparea is lower than 0 
                    if ($aCropData['y'] < 0) {
                        //set y postion to Absolute value
                        $iAbsoluteYpos = abs($aCropData['y']);
                        //set y position of image
                        $imageOriginalPositionY = $iAbsoluteYpos;
                        //add y position to canvas size
                        $imageCanvasHeight += $iAbsoluteYpos;
                        //update canvas height if croparea is biger than original image
                        $iCropHeightWithoutAbsoluteYpos = ($aCropData['height'] - $iAbsoluteYpos);
                        if ($iCropHeightWithoutAbsoluteYpos > $imageOriginalHeight) {
                            //add ouside the box height
                            $imageCanvasHeight += ($iCropHeightWithoutAbsoluteYpos - $imageOriginalHeight);
                        }
                    } else {
                        // add if crop partly ouside image
                        $iCropHeightWithYpos = ($aCropData['height'] + $aCropData['y']);
                        if ($iCropHeightWithYpos > $imageOriginalHeight) {
                            //add ouside the box height
                            $imageCanvasHeight += ($iCropHeightWithYpos - $imageOriginalHeight);
                        }
                    }

                    // round values
                    $imageCanvasWidthRounded = round($imageCanvasWidth);
                    $imageCanvasHeightRounded = round($imageCanvasHeight);
                    $imageOriginalPositionXRounded = round($imageOriginalPositionX);
                    $imageOriginalPositionYRounded = round($imageOriginalPositionY);
                    $imageCropWidthRounded = round($aCropData['width']);
                    $imageCropHeightRounded = round($aCropData['height']);
                    // set postion to 0 if x or y is less than 0
                    $imageCropPositionXRounded = $aCropData['x'] < 0 ? 0 : round($aCropData['x']);
                    $imageCropPositionYRounded = $aCropData['y'] < 0 ? 0 : round($aCropData['y']);

//                       
                    //todo: check if rotaded resize canvas (http://stackoverflow.com/questions/9971230/calculate-rotated-rectangle-size-from-known-bounding-box-coordinates)
                    // merge current image in canvas, crop image and save
                    $imagineRgb = new RGB();
                    $imagineColor = $imagineRgb->color('#FFF', 0);
                    // create image
                    Image::getImagine()->create(new Box($imageCanvasWidthRounded, $imageCanvasHeightRounded), $imagineColor)
                            ->paste($imageOriginal, new Point($imageOriginalPositionXRounded, $imageOriginalPositionYRounded))
                            ->crop(new Point($imageCropPositionXRounded, $imageCropPositionYRounded), new Box($imageCropWidthRounded, $imageCropHeightRounded))
                            ->save($sMediaPath . "/" . $sSaveFileName);

                    //set boolean crop success to true
                    $bCropSuccess = true;

                    //set return id
                    $return = $model->id;

                    // Check if the original image must be delete
                    if (\Yii::$app->getModule('imagemanager')->deleteOriginalAfterEdit) {
                        $modelOriginal->delete();
                    }
                } catch (ErrorException $e) {
                    
                }
            }

            //commit transaction if boolean is true
            if ($bCropSuccess) {
                $transaction->commit();
            }
        }

        //echo return json encoded
        return $return;
    }

    /**
     * Deletes an existing ImageManager model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @return mixed
     */
    public function actionDelete() {
        //return 
        $return = ['delete' => false];
        //set response header
        Yii::$app->getResponse()->format = Response::FORMAT_JSON;
//
        if ($this->canRemoveImage(Yii::$app->request->post("ImageManager_id")) == false) {
            // User can not remove this image, return false status
            return $return;
        }

        //get post
        $ImageManager_id = Yii::$app->request->post("ImageManager_id");
        //get details
        $model = $this->findModel($ImageManager_id);

        //delete record
        if ($model->delete()) {
            $return['delete'] = true;
        }
        return $return;
    }

    protected function canRemoveImage($ImageManager_id) {
        $selected_image_contracts = \backend\modules\contracts\models\Contracts::find()->select('selected_image')->where(['selected_image' => $ImageManager_id])->one();
        $selected_image_customers = \backend\modules\customers\models\Customers::find()->select('selected_image')->where(['selected_image' => $ImageManager_id])->one();
        if ($selected_image_contracts != null || $selected_image_customers != null) {
            return false;
        }
        return true;
    }

}

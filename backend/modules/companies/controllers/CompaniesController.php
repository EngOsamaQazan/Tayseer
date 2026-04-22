<?php

namespace backend\modules\companies\controllers;

use backend\modules\address\models\Address;
use backend\modules\contracts\models\Contracts;
use backend\modules\inventoryItemQuantities\models\InventoryItemQuantities;
use backend\modules\inventoryStockLocations\models\InventoryStockLocations;
use backend\modules\notification\models\Notification;
use common\models\Model;
use Yii;
use backend\modules\companies\models\Companies;
use backend\modules\companies\models\CompaniesSearch;
use \backend\modules\companyBanks\models\CompanyBanks;
use \backend\modules\companyBanks\models\CompanyBanksSearch;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use \yii\web\Response;
use yii\helpers\Html;
use yii\web\UploadedFile;
use yii2tech\ar\softdelete\SoftDeleteBehavior;
use backend\helpers\PdfToImageHelper;
use yii\filters\AccessControl;
use common\helper\Permissions;
use common\services\media\MediaContext;

/**
 * CompaniesController implements the CRUD actions for Companies model.
 */
class CompaniesController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'actions' => ['index', 'view', 'get-items', 'search-suggest'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            return Permissions::can(Permissions::COMP_VIEW);
                        },
                    ],
                    [
                        'actions' => ['create'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            return Permissions::can(Permissions::COMP_CREATE);
                        },
                    ],
                    [
                        'actions' => ['update'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            return Permissions::can(Permissions::COMP_UPDATE);
                        },
                    ],
                    [
                        'actions' => ['delete', 'bulk-delete'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            return Permissions::can(Permissions::COMP_DELETE);
                        },
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'bulk-delete' => ['post'],
                ],
            ],
        ];
    }

    /**
     * AJAX autocomplete for unified search.
     */
    public function actionSearchSuggest($q = '')
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $q = trim($q);
        if (mb_strlen($q) < 2) return ['results' => []];

        $db = Yii::$app->db;
        $nameNorm = "REPLACE(REPLACE(REPLACE(REPLACE(name, 'ة', 'ه'), 'أ', 'ا'), 'إ', 'ا'), 'ى', 'ي')";
        $nameNormNoSpace = "REPLACE($nameNorm, ' ', '')";

        $words = preg_split('/\s+/u', $q, -1, PREG_SPLIT_NO_EMPTY);
        $nameParams = [];
        $nameClauses = [];
        foreach ($words as $i => $w) {
            $wNorm = str_replace(['أ', 'إ', 'آ'], 'ا', $w);
            $wNorm = str_replace('ة', 'ه', $wNorm);
            $wNorm = str_replace('ى', 'ي', $wNorm);
            $p1 = ':nw' . $i . 'a';
            $p2 = ':nw' . $i . 'b';
            $likeVal = '%' . $wNorm . '%';
            $nameClauses[] = "($nameNorm LIKE $p1 OR $nameNormNoSpace LIKE $p2)";
            $nameParams[$p1] = $likeVal;
            $nameParams[$p2] = $likeVal;
        }
        $nameClause = implode(' AND ', $nameClauses);

        $rows = $db->createCommand(
            "SELECT id, name, phone_number
             FROM {{%companies}}
             WHERE ($nameClause)
                OR phone_number LIKE :qLikePhone
             ORDER BY id DESC
             LIMIT 10",
            array_merge([':qLikePhone' => '%' . $q . '%'], $nameParams)
        )->queryAll();

        $results = [];
        foreach ($rows as $r) {
            $results[] = [
                'id'    => $r['id'],
                'title' => $r['name'],
                'sub'   => $r['phone_number'] ?: '',
                'icon'  => 'fa-building',
            ];
        }
        return ['results' => $results];
    }

    /**
     * Lists all Companies models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new CompaniesSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $searchCounter = $searchModel->searchCounter(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'searchCounter' => $searchCounter

        ]);
    }


    /**
     * Displays a single Companies model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        $request = Yii::$app->request;
        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title' => "مُستثمر #" . $id,
                'content' => $this->renderAjax('view', [
                    'model' => $this->findModel($id),
                ]),
                'footer' => Html::button('Close', ['class' => 'btn btn-secondary pull-left', 'data-dismiss' => "modal"]) .
                    Html::a('Edit', ['update', 'id' => $id], ['class' => 'btn btn-primary', 'role' => 'modal-remote'])
            ];
        } else {
            return $this->render('view', [
                'model' => $this->findModel($id),
            ]);
        }
    }

    /**
     * Creates a new Companies model.
     * For ajax request will return json object
     * and for non-ajax request if creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $request = Yii::$app->request;
        $model = new Companies();
        $modelsCompanieBanks = [new CompanyBanks];

        /*
        *   Process for non-ajax request
        */
        if ($model->load($request->post())) {

            $model->created_by = Yii::$app->user->id;

            $logoUpload = UploadedFile::getInstance($model, 'logo');
            if ($logoUpload) {
                $logoPath = $this->saveCompanyLogo($logoUpload);
                if ($logoPath !== null) {
                    $model->logo = $logoPath;
                }
            } else {
                // Phase 6.2: a unified MediaUploader may have already
                // uploaded the logo via /media/upload — pick it up
                // before nulling the AR attribute.
                $adoptedLogoUrl = $this->adoptUploadedLogo(null);
                if ($adoptedLogoUrl !== null) {
                    $model->logo = $adoptedLogoUrl;
                } else {
                    // Avoid the AR setter inheriting an UploadedFile that
                    // would fail validation; null means "no change".
                    $model->logo = null;
                }
            }

            $this->handleDocumentUploads($model);
            $this->adoptUploadedDocuments($model);

            $modelsCompanieBanks = Model::createMultiple(CompanyBanks::classname());
            Model::loadMultiple($modelsCompanieBanks, Yii::$app->request->post());
            $valid = $model->validate();
            $valid = Model::validateMultiple($modelsCompanieBanks);

            if ($valid) {
                $transaction = \Yii::$app->db->beginTransaction();
                try {

                    if ($flag = $model->save(false)) {
                        foreach ($modelsCompanieBanks as $modelsCompanieBank) {
                            $modelsCompanieBank->company_id = $model->id;

                            if (!($companieBankFlag = $modelsCompanieBank->save())) {
                                $transaction->rollBack();
                                break;
                            }
                        }

                        if ($flag && $companieBankFlag) {
                            $transaction->commit();
                        }
                    }

                } catch (Exception $e) {
                    $transaction->rollBack();
                }
            }

            Yii::$app->cache->set(Yii::$app->params['key_company'], Yii::$app->db->createCommand(Yii::$app->params['company_query'])->queryAll(), Yii::$app->params['time_duration']);
            Yii::$app->cache->set(Yii::$app->params['key_company_name'], Yii::$app->db->createCommand(Yii::$app->params['company_name_query'])->queryAll(), Yii::$app->params['time_duration']);

            $this->redirect(['index']);
        } else {
            return $this->render('create', [
                'model' => $model,
                'modelsCompanieBanks' => (empty($modelsCompanieBanks)) ? [new CompanyBanks] : $modelsCompanieBanks,
            ]);
        }


    }

    /**
     * Updates an existing Companies model.
     * For ajax request will return json object
     * and for non-ajax request if update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $request = Yii::$app->request;
        $model = $this->findModel($id);
        $modelsCompanieBanks = CompanyBanks::find()->where(['company_id' => $id])->all();
        $logo = $model->logo;
        $existingRegister = $model->commercial_register;
        $existingLicense = $model->trade_license;
        $createdBy = $model->created_by;
        if ($model->load($request->post())) {
            $logoUpload = UploadedFile::getInstance($model, 'logo');
            if ($logoUpload) {
                $logoPath = $this->saveCompanyLogo($logoUpload, (int)$model->id);
                $model->logo = $logoPath !== null ? $logoPath : $logo;
            } else {
                $adoptedLogoUrl = $this->adoptUploadedLogo((int)$model->id);
                $model->logo = $adoptedLogoUrl !== null ? $adoptedLogoUrl : $logo;
            }

            $model->commercial_register = $existingRegister;
            $model->trade_license = $existingLicense;
            $this->handleDocumentUploads($model);
            $this->adoptUploadedDocuments($model);


            $oldIDs = yii\helpers\ArrayHelper::map($modelsCompanieBanks, 'id', 'id');

            $modelsCompanieBanks = Model::createMultiple(CompanyBanks::classname(), $modelsCompanieBanks);

            Model::loadMultiple($modelsCompanieBanks, Yii::$app->request->post());

            $deletedIDs = array_diff($oldIDs, array_filter(yii\helpers\ArrayHelper::map($modelsCompanieBanks, 'id', 'id')));

            $valid = $model->validate();
            $valid = Model::validateMultiple($modelsCompanieBanks) && $valid;

            if ($valid) {
                $transaction = \Yii::$app->db->beginTransaction();
                try {

                    if ($flag = $model->save(false)) {
                        if (!empty($deletedIDs)) {
                            CompanyBanks::deleteAll(['id' => $deletedIDs]);
                        }

                        foreach ($modelsCompanieBanks as $modelsCompanieBank) {

                            $modelsCompanieBank->company_id = $model->id;

                            if (!($companieBankFlag = $modelsCompanieBank->save())) {

                                $transaction->rollBack();
                                var_dump($modelsCompanieBank->getErrors());
                                break;
                            }
                        }
                        if ($flag) {
                            $transaction->commit();
                            return $this->redirect(['index']);
                        }

                    }

                } catch (Exception $e) {
                    $transaction->rollBack();
                }
            }

            Yii::$app->cache->set(Yii::$app->params['key_company'], Yii::$app->db->createCommand(Yii::$app->params['company_query'])->queryAll(), Yii::$app->params['time_duration']);
            Yii::$app->cache->set(Yii::$app->params['key_company_name'], Yii::$app->db->createCommand(Yii::$app->params['company_name_query'])->queryAll(), Yii::$app->params['time_duration']);

            $this->redirect(['index']);
        } else {
            return $this->render('update', [
                'model' => $model,
                'modelsCompanieBanks' => (empty($modelsCompanieBanks)) ? [new CompanyBanks] : $modelsCompanieBanks,

            ]);
        }

    }

    /**
     * Delete an existing Companies model.
     * For ajax request will return json object
     * and for non-ajax request if deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $request = Yii::$app->request;
        $this->findModel($id)->delete();
        CompanyBanks::updateAll(['is_deleted' => 1], ['company_id' => $id]);
        if ($request->isAjax) {
            /*
            *   Process for ajax request
            */
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ['forceClose' => true, 'forceReload' => '#crud-datatable-pjax'];
        } else {
            /*
            *   Process for non-ajax request
            */
            return $this->redirect(['index']);
        }


    }


    public function actionGetItems($company_id, $model_id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $arr = [];
        $selected = [];
        $quantitiesItems = InventoryItemQuantities::find()
            ->joinWith(['locations'])
            ->andWhere(['company_id' => $company_id])
            ->all();
        if ($quantitiesItems) {
            foreach ($quantitiesItems as $quantitiesItem) {
                if (isset($quantitiesItem->item)) {
                    $arr[$quantitiesItem->item->id] = $quantitiesItem->item->item_name;
                }
            }
        }
        if ($model_id) {
            $contract = Contracts::find()->andWhere(['id' => $model_id])->one();
            if (isset($contract->inventoryItemValue) && !empty($contract->inventoryItemValue)) {
                $selected = $contract->inventoryItemValue;
            }
        }
        return [
            'items' => $arr,
            'selected' => $selected
        ];

    }

    /**
     * Delete multiple existing Companies model.
     * For ajax request will return json object
     * and for non-ajax request if deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionBulkDelete()
    {
        $request = Yii::$app->request;
        $pks = explode(',', $request->post('pks')); // Array or selected records primary keys
        foreach ($pks as $pk) {
            $model = $this->findModel($pk);
            $model->delete();
        }

        if ($request->isAjax) {
            /*
            *   Process for ajax request
            */
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ['forceClose' => true, 'forceReload' => '#crud-datatable-pjax'];
        } else {
            /*
            *   Process for non-ajax request
            */
            return $this->redirect(['index']);
        }

    }

    /**
     * Finds the Companies model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Companies the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Companies::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    /**
     * Persist commercial register + trade licence documents through
     * the unified MediaService.
     *
     * Phase 3.7 migration notes:
     *   • The DB columns stay JSON-encoded `[{path, name}, …]` for
     *     full back-compat with `getCommercialRegisterList()` /
     *     `getTradeLicenseList()` and every view that reads them.
     *     Phase 5 will introduce a M5 backfill that decomposes the
     *     JSON into individual `os_ImageManager` rows; until then
     *     the new uploads land in BOTH places (the unified row +
     *     the JSON column) so reports keep working unchanged.
     *   • PdfToImageHelper still runs on every PDF so the existing
     *     thumbnail cache stays warm — even though the unified
     *     pipeline will eventually own thumbnail generation, the
     *     old admin views read from `PdfToImageHelper::convertAndCache`
     *     and we don't want a UI regression mid-deprecation.
     */
    protected function handleDocumentUploads($model)
    {
        $this->ingestCompanyDocuments(
            $model,
            'commercial_register_files',
            'commercial_register',
            'reg_'
        );
        $this->ingestCompanyDocuments(
            $model,
            'trade_license_files',
            'trade_license',
            'lic_'
        );

        // Keep the legacy PDF→image cache warm for both lists. We
        // intentionally read back from the *getter* (not the raw
        // UploadedFile arrays) so any newly-stored document is
        // also processed.
        foreach ($model->getCommercialRegisterList() as $doc) {
            if (isset($doc['name']) && strtolower(pathinfo($doc['name'], PATHINFO_EXTENSION)) === 'pdf') {
                PdfToImageHelper::convertAndCache($doc['path']);
            }
        }
        foreach ($model->getTradeLicenseList() as $doc) {
            if (isset($doc['name']) && strtolower(pathinfo($doc['name'], PATHINFO_EXTENSION)) === 'pdf') {
                PdfToImageHelper::convertAndCache($doc['path']);
            }
        }
    }

    /**
     * Ingest one bucket of company documents (register OR licence)
     * into MediaService and append the resulting URLs to the JSON
     * column on the model.
     *
     * @param Companies $model
     * @param string $field         e.g. 'commercial_register_files'
     * @param string $jsonAttribute e.g. 'commercial_register'
     * @param string $idPrefix      e.g. 'reg_' (kept for log/debug parity)
     */
    private function ingestCompanyDocuments($model, string $field, string $jsonAttribute, string $idPrefix): void
    {
        $files = UploadedFile::getInstances($model, $field);
        if (empty($files)) {
            return;
        }

        $listGetter = $jsonAttribute === 'commercial_register'
            ? 'getCommercialRegisterList'
            : 'getTradeLicenseList';
        $existing = $model->$listGetter();

        foreach ($files as $file) {
            try {
                $entityId = $model->id ? (int)$model->id : null;
                $ctx = new MediaContext(
                    entityType:  'company',
                    entityId:    $entityId,
                    groupName:   $jsonAttribute,
                    uploadedVia: 'company_form',
                    userId:      Yii::$app->user->isGuest ? null : (int)Yii::$app->user->id,
                );
                $result = Yii::$app->media->store($file, $ctx);
            } catch (\Throwable $e) {
                Yii::error("Companies {$idPrefix}upload failed: " . $e->getMessage(), __METHOD__);
                continue;
            }

            $existing[] = [
                // Legacy `path` shape: relative-to-webroot, no leading
                // slash, e.g. 'uploads/investors/reg_xxx.pdf'. The
                // unified URL we get back is rooted at '/images/…'
                // so we strip the leading slash for back-compat with
                // call sites doing `Url::to(['/' . $doc['path']])`.
                'path' => ltrim($result->url, '/'),
                'name' => $file->baseName . '.' . $file->extension,
            ];
        }

        $model->$jsonAttribute = json_encode($existing, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Persist a company logo through MediaService. Returns the URL
     * to write into the `logo` column (without leading slash to
     * match the historical `Url::to(['/' . $model->logo])` callers),
     * or null on failure (caller decides whether to keep the old
     * logo or surface a flash error).
     */
    private function saveCompanyLogo(UploadedFile $file, ?int $companyId = null): ?string
    {
        try {
            $ctx = new MediaContext(
                entityType:  'company',
                entityId:    $companyId,
                groupName:   'company_logo',
                uploadedVia: 'company_form',
                userId:      Yii::$app->user->isGuest ? null : (int)Yii::$app->user->id,
            );
            $result = Yii::$app->media->store($file, $ctx);
            return ltrim($result->url, '/');
        } catch (\Throwable $e) {
            Yii::error('Company logo upload failed: ' . $e->getMessage(), __METHOD__);
            Yii::$app->session->setFlash('error',
                'تعذّر حفظ الشعار: تحقّق من الحجم ونوع الملف.');
            return null;
        }
    }

    /**
     * Phase 6.2 — adopt a company logo uploaded async via the unified
     * MediaUploader. Returns the URL string ready to be assigned to
     * the `logo` column (no leading slash, matching legacy callers),
     * or null if no logo was uploaded via the new path.
     */
    protected function adoptUploadedLogo(?int $companyId): ?string
    {
        $body = (array)Yii::$app->request->post('Companies', []);
        $mediaId = (int)($body['adopted_logo_id'] ?? 0);
        if ($mediaId <= 0) {
            return null;
        }

        if ($companyId !== null && $companyId > 0) {
            if (!Yii::$app->media->adopt($mediaId, 'company', $companyId)) {
                Yii::warning("Companies adopt logo #$mediaId failed for company #$companyId", __METHOD__);
                return null;
            }
        }
        // For brand-new records the row stays an orphan until the
        // company is saved; the next form post (or a backfill cron)
        // will adopt it. The URL itself is already correct.

        try {
            return ltrim(Yii::$app->media->url($mediaId), '/');
        } catch (\Throwable $e) {
            Yii::error('Companies adopt logo url() failed: ' . $e->getMessage(), __METHOD__);
            return null;
        }
    }

    /**
     * Phase 6.2 — adopt N register/licence documents uploaded async
     * via the unified MediaUploader. Each id arrives under
     * Companies[adopted_register_ids][] / Companies[adopted_license_ids][].
     * We adopt them and append their URLs to the matching JSON column
     * so getCommercialRegisterList()/getTradeLicenseList() keep working.
     */
    protected function adoptUploadedDocuments(Companies $model): void
    {
        $body = (array)Yii::$app->request->post('Companies', []);
        $this->adoptUploadedDocumentBucket(
            $model,
            (array)($body['adopted_register_ids'] ?? []),
            'commercial_register'
        );
        $this->adoptUploadedDocumentBucket(
            $model,
            (array)($body['adopted_license_ids'] ?? []),
            'trade_license'
        );
    }

    private function adoptUploadedDocumentBucket(Companies $model, array $ids, string $jsonAttribute): void
    {
        if (empty($ids)) {
            return;
        }

        $listGetter = $jsonAttribute === 'commercial_register'
            ? 'getCommercialRegisterList'
            : 'getTradeLicenseList';
        $existing = $model->$listGetter();

        foreach ($ids as $rawId) {
            $mediaId = (int)$rawId;
            if ($mediaId <= 0) continue;

            if ($model->id) {
                if (!Yii::$app->media->adopt($mediaId, 'company', (int)$model->id)) {
                    Yii::warning("Companies adopt $jsonAttribute #$mediaId failed", __METHOD__);
                    continue;
                }
            }

            try {
                $url = Yii::$app->media->url($mediaId);
            } catch (\Throwable $e) {
                Yii::error("Companies adopt $jsonAttribute url() failed: " . $e->getMessage(), __METHOD__);
                continue;
            }

            $existing[] = [
                'path' => ltrim($url, '/'),
                'name' => basename(parse_url($url, PHP_URL_PATH) ?: ('media-' . $mediaId)),
            ];
        }

        $model->$jsonAttribute = json_encode($existing, JSON_UNESCAPED_UNICODE);
    }
}

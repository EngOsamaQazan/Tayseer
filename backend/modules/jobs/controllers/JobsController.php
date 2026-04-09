<?php

namespace backend\modules\jobs\controllers;

use Yii;
use backend\modules\jobs\models\Jobs;
use backend\modules\jobs\models\JobsSearch;
use backend\modules\jobs\models\JobsPhone;
use backend\modules\jobs\models\JobsWorkingHours;
use backend\modules\jobs\models\JobsRating;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\Response;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use backend\helpers\ExportHelper;
use backend\helpers\ExportTrait;

/**
 * JobsController implements the CRUD actions for Jobs model.
 */
class JobsController extends Controller
{
    use ExportTrait;
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['post'],
                    'bulk-delete' => ['post'],
                    'delete-phone' => ['post'],
                    'delete-rating' => ['post'],
                ],
            ],
        ];
    }

    /**
     * Lists all Jobs models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new JobsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Jobs model with all related data.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        $phones = $model->getPhones()->all();
        $workingHours = $model->getWorkingHours()->all();
        $ratings = $model->getRatings()->all();

        return $this->render('view', [
            'model' => $model,
            'phones' => $phones,
            'workingHours' => $workingHours,
            'ratings' => $ratings,
        ]);
    }

    /**
     * Creates a new Jobs model.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Jobs();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            $this->saveWorkingHours($model);
            $this->savePhonesFromPost($model);
            $this->updateJobsCache();

            Yii::$app->session->setFlash('success', 'تم إنشاء جهة العمل بنجاح');
            Yii::$app->session->setFlash('job_broadcast', json_encode(['id' => $model->id, 'name' => $model->name]));
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Jobs model.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            $this->saveWorkingHours($model);
            $this->savePhonesFromPost($model);
            $this->updateJobsCache();

            Yii::$app->session->setFlash('success', 'تم تحديث بيانات جهة العمل بنجاح');
            Yii::$app->session->setFlash('job_broadcast', json_encode(['id' => $model->id, 'name' => $model->name]));
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Soft deletes an existing Jobs model.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        $model->softDelete();

        $this->updateJobsCache();

        Yii::$app->session->setFlash('success', 'تم حذف جهة العمل');
        return $this->redirect(['index']);
    }

    // ========================
    // Export Actions
    // ========================

    public function actionExportExcel()
    {
        $searchModel = new JobsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->exportData($dataProvider, $this->getExportConfig());
    }

    public function actionExportPdf()
    {
        $searchModel = new JobsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->exportData($dataProvider, $this->getExportConfig(), 'pdf');
    }

    protected function getExportConfig()
    {
        return [
            'title' => 'جهات العمل',
            'headers' => ['#', 'اسم جهة العمل', 'نوع الجهة', 'المدينة', 'أرقام الهواتف', 'العملاء', 'التقييم', 'الحالة'],
            'keys' => [
                '#',
                'name',
                function ($model) {
                    return $model->jobType ? $model->jobType->name : '-';
                },
                function ($model) {
                    return $model->address_city ?: '-';
                },
                function ($model) {
                    $phones = $model->getPhones()->all();
                    if (empty($phones)) return '-';
                    return implode(' / ', array_map(function ($p) {
                        return $p->phone_number;
                    }, $phones));
                },
                function ($model) {
                    return $model->getCustomersCount();
                },
                function ($model) {
                    $avg = $model->getAverageRating();
                    return $avg !== null ? number_format($avg, 1) : 'لا يوجد';
                },
                function ($model) {
                    return $model->status ? 'فعال' : 'غير فعال';
                },
            ],
            'widths' => [6, 30, 20, 18, 28, 12, 12, 14],
            'filename' => 'جهات_العمل',
        ];
    }

    // ========================
    // Select2 AJAX search — real-time job list
    // ========================

    /**
     * AJAX: returns job meta (updated_at, job_type) for the smart onboarding form.
     */
    public function actionJobInfo($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $job = Jobs::find()->where(['id' => (int) $id])->one();
        if (!$job) {
            return ['success' => false];
        }

        $timestamp = $job->updated_at ?: $job->created_at;
        $dateLabel = $job->updated_at ? 'آخر تحديث للبيانات' : 'تاريخ الإنشاء (لم تُحدَّث سابقاً)';
        $displayDate = null;
        $months = null;

        if ($timestamp) {
            $displayDate = date('Y-m-d H:i', $timestamp);
            $months = (int) round((time() - $timestamp) / (30 * 86400));
        }

        return [
            'success'      => true,
            'display_date' => $displayDate,
            'date_label'   => $dateLabel,
            'months_since' => $months,
            'job_type_id'  => $job->job_type,
            'job_type_name' => $job->jobType ? $job->jobType->name : null,
        ];
    }

    /**
     * AJAX endpoint for Select2: search jobs by name (case-insensitive, partial match).
     * Returns {results:[{id,text}]} format compatible with Select2.
     */
    public function actionSearchList()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $q = trim((string) Yii::$app->request->get('q', ''));

        $query = Jobs::find()
            ->select(['id', 'name'])
            ->andWhere(['is_deleted' => 0])
            ->orderBy(['name' => SORT_ASC])
            ->limit(30);

        if ($q !== '') {
            $query->andWhere(['like', 'LOWER(name)', mb_strtolower($q, 'UTF-8')]);
        }

        $items = [];
        foreach ($query->asArray()->all() as $row) {
            $items[] = ['id' => $row['id'], 'text' => $row['name']];
        }

        return ['results' => $items];
    }

    /**
     * AJAX: returns all job types for the quick-create modal.
     */
    public function actionJobTypesList()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $types = \backend\modules\jobs\models\JobsType::find()->asArray()->all();
        return ['results' => $types];
    }

    /**
     * Quick-create a new job via AJAX (name + optional fields).
     * Returns {success, id, text} so Select2 can select the new entry.
     */
    public function actionQuickCreate()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!Yii::$app->request->isPost) {
            return ['success' => false, 'message' => 'POST only'];
        }

        $name = trim((string) Yii::$app->request->post('name', ''));
        if ($name === '') {
            return ['success' => false, 'message' => 'الاسم مطلوب'];
        }

        $existing = Jobs::find()
            ->where(['LOWER(name)' => mb_strtolower($name, 'UTF-8')])
            ->andWhere(['is_deleted' => 0])
            ->one();

        if ($existing) {
            return ['success' => true, 'id' => $existing->id, 'text' => $existing->name, 'existing' => true];
        }

        $model = new Jobs();
        $model->name = $name;
        $model->job_type = (int) Yii::$app->request->post('job_type', 0) ?: 0;
        $model->address_city = trim((string) Yii::$app->request->post('address_city', '')) ?: null;
        $model->address_area = trim((string) Yii::$app->request->post('address_area', '')) ?: null;
        $model->status = Jobs::STATUS_ACTIVE;

        if ($model->save(false)) {
            $phone = trim((string) Yii::$app->request->post('phone', ''));
            if ($phone !== '') {
                $phoneModel = new JobsPhone();
                $phoneModel->job_id = $model->id;
                $phoneModel->phone_number = $phone;
                $phoneModel->phone_type = 'office';
                $phoneModel->save(false);
            }
            $this->updateJobsCache();
            return ['success' => true, 'id' => $model->id, 'text' => $model->name, 'existing' => false];
        }

        return ['success' => false, 'message' => 'فشل في الحفظ', 'errors' => $model->errors];
    }

    // ========================
    // Similar Name Search (AJAX) — duplicate detection
    // ========================

    /**
     * Search for jobs with similar names (AJAX).
     * Used on the create/update form to warn about duplicates.
     * @return array
     */
    public function actionSearchSimilar()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $q = trim((string)Yii::$app->request->get('q', ''));
        $excludeId = (int) Yii::$app->request->get('exclude', 0);

        if ($q === '' || mb_strlen($q) < 2) {
            return ['results' => []];
        }

        $query = Jobs::find()
            ->select(['id', 'name', 'address_city', 'job_type'])
            ->where(['like', 'name', $q])
            ->andFilterWhere(['!=', 'id', $excludeId ?: null])
            ->limit(8);

        $rows = [];
        foreach ($query->all() as $job) {
            $rows[] = [
                'id'   => $job->id,
                'name' => $job->name,
                'city' => $job->address_city ?: '',
                'type' => $job->jobType ? $job->jobType->name : '',
            ];
        }

        return ['results' => $rows];
    }

    // ========================
    // Phone Actions (AJAX)
    // ========================

    /**
     * Add a phone number to a job (AJAX)
     * @param integer $jobId
     * @return array
     */
    public function actionAddPhone($jobId)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $model = new JobsPhone();
        $model->job_id = $jobId;

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return ['success' => true, 'message' => 'تم إضافة رقم الهاتف بنجاح'];
        }

        return ['success' => false, 'errors' => $model->errors];
    }

    /**
     * Delete a phone number (AJAX soft delete)
     * @param integer $id
     * @return array
     */
    public function actionDeletePhone($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $model = JobsPhone::findOne($id);
        if ($model) {
            $model->softDelete();
            return ['success' => true, 'message' => 'تم حذف رقم الهاتف'];
        }

        return ['success' => false, 'message' => 'لم يتم العثور على الرقم'];
    }

    // ========================
    // Rating Actions (AJAX)
    // ========================

    /**
     * Add a rating to a job (AJAX)
     * @param integer $jobId
     * @return array
     */
    public function actionAddRating($jobId)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $model = new JobsRating();
        $model->job_id = $jobId;
        $model->rated_by = Yii::$app->user->id;
        $model->rated_at = date('Y-m-d H:i:s');

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return ['success' => true, 'message' => 'تم إضافة التقييم بنجاح'];
        }

        return ['success' => false, 'errors' => $model->errors];
    }

    /**
     * Delete a rating (AJAX soft delete)
     * @param integer $id
     * @return array
     */
    public function actionDeleteRating($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $model = JobsRating::findOne($id);
        if ($model) {
            $model->softDelete();
            return ['success' => true, 'message' => 'تم حذف التقييم'];
        }

        return ['success' => false, 'message' => 'لم يتم العثور على التقييم'];
    }

    // ========================
    // Working Hours Actions
    // ========================

    /**
     * Save working hours from POST data
     * @param Jobs $model
     */
    protected function saveWorkingHours($model)
    {
        $hoursData = Yii::$app->request->post('WorkingHours', []);
        if (empty($hoursData)) {
            return;
        }

        // Delete existing hours for this job
        JobsWorkingHours::deleteAll(['job_id' => $model->id]);

        foreach ($hoursData as $dayData) {
            if (!isset($dayData['day_of_week'])) {
                continue;
            }
            $hour = new JobsWorkingHours();
            $hour->job_id = $model->id;
            $hour->day_of_week = $dayData['day_of_week'];
            $hour->opening_time = $dayData['opening_time'] ?? null;
            $hour->closing_time = $dayData['closing_time'] ?? null;
            $hour->is_closed = !empty($dayData['is_closed']) ? 1 : 0;
            $hour->notes = $dayData['notes'] ?? null;
            $hour->save();
        }
    }

    // ========================
    // Save inline phones from form
    // ========================

    /**
     * Save phone rows submitted from the create/update form.
     * POST key: Phones[0][phone_number], Phones[0][employee_name], etc.
     */
    protected function savePhonesFromPost($model)
    {
        $phonesData = Yii::$app->request->post('Phones', []);
        if (empty($phonesData)) return;

        foreach ($phonesData as $row) {
            $number = trim($row['phone_number'] ?? '');
            if ($number === '') continue;

            $phone = new JobsPhone();
            $phone->job_id = $model->id;
            $phone->phone_number = $number;
            $phone->phone_type = $row['phone_type'] ?? 'office';
            $phone->employee_name = trim($row['employee_name'] ?? '') ?: null;
            $phone->employee_position = trim($row['employee_position'] ?? '') ?: null;
            $phone->save(false);
        }
    }

    /**
     * Unified location resolver: handles Google short URLs, Plus Codes, and text addresses.
     */
    public function actionResolveLocation()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $raw = trim(Yii::$app->request->get('q', ''));
        if (empty($raw)) {
            return ['success' => false];
        }

        // 1) Google Maps short URL
        if (preg_match('#^https?://(maps\.app\.goo\.gl|goo\.gl/maps)/#i', $raw)) {
            $result = $this->resolveGoogleShortUrl($raw);
            if ($result) {
                return ['success' => true, 'lat' => $result['lat'], 'lng' => $result['lng']];
            }
            return ['success' => false];
        }

        // 2) Full Plus Code (e.g. 8G3QXW26+XX)
        if (preg_match('/^[23456789CFGHJMPQRVWX]{8,}\+[23456789CFGHJMPQRVWX]*$/i', $raw)) {
            $result = $this->decodePlusCode($raw);
            if ($result) {
                return ['success' => true, 'lat' => $result['lat'], 'lng' => $result['lng']];
            }
        }

        // 3) Short Plus Code with city reference (e.g. "22HC+4M الرصيفة")
        if (preg_match('/^([23456789CFGHJMPQRVWX]{2,6}\+[23456789CFGHJMPQRVWX]{0,3})\s*[,،\s]\s*(.+)$/iu', $raw, $m)) {
            $shortCode = strtoupper(trim($m[1]));
            $cityRef = trim($m[2]);
            $geo = $this->nominatimGeocode($cityRef);
            if ($geo) {
                $result = $this->recoverAndDecodePlusCode($shortCode, $geo['lat'], $geo['lng']);
                if ($result) {
                    return ['success' => true, 'lat' => $result['lat'], 'lng' => $result['lng']];
                }
            }
        }

        // 4) Fallback: Nominatim text search
        $geo = $this->nominatimGeocode($raw);
        if ($geo) {
            return [
                'success' => true,
                'lat' => $geo['lat'],
                'lng' => $geo['lng'],
                'display_name' => $geo['display_name'] ?? null,
            ];
        }

        return ['success' => false];
    }

    private function resolveGoogleShortUrl($url)
    {
        $currentUrl = $url;

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $ch = curl_init($currentUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_HEADER => true,
                CURLOPT_NOBODY => false,
                CURLOPT_TIMEOUT => 12,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_HTTPHEADER => [
                    'Accept: text/html,application/xhtml+xml',
                    'Accept-Language: ar,en;q=0.9',
                ],
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

            if ($response === false) break;

            $headers = substr($response, 0, $headerSize);
            $body = substr($response, $headerSize);

            if ($httpCode >= 301 && $httpCode <= 308) {
                if (preg_match('/^Location:\s*(.+)$/mi', $headers, $lm)) {
                    $currentUrl = trim($lm[1]);
                    continue;
                }
            }

            $coords = $this->extractCoordsFromText($currentUrl);
            if ($coords) return $coords;

            $coords = $this->extractCoordsFromText($body);
            if ($coords) return $coords;

            if (preg_match('/content=["\']?\d+;\s*url=([^"\'>\s]+)/i', $body, $mr)) {
                $currentUrl = html_entity_decode($mr[1]);
                continue;
            }
            if (preg_match('/(?:window\.location|location\.href)\s*=\s*["\']([^"\']+)/i', $body, $jr)) {
                $currentUrl = html_entity_decode($jr[1]);
                continue;
            }

            break;
        }

        return null;
    }

    private function extractCoordsFromText($text)
    {
        if (empty($text)) return null;
        if (preg_match('/@(-?\d+\.\d+),(-?\d+\.\d+)/', $text, $m)) {
            return ['lat' => (float)$m[1], 'lng' => (float)$m[2]];
        }
        if (preg_match('/!3d(-?\d+\.\d+).*?!4d(-?\d+\.\d+)/', $text, $m)) {
            return ['lat' => (float)$m[1], 'lng' => (float)$m[2]];
        }
        if (preg_match('/[?&]q=(-?\d+\.\d+),(-?\d+\.\d+)/', $text, $m)) {
            return ['lat' => (float)$m[1], 'lng' => (float)$m[2]];
        }
        if (preg_match('/center=(-?\d+\.\d+)%2C(-?\d+\.\d+)/', $text, $m)) {
            return ['lat' => (float)$m[1], 'lng' => (float)$m[2]];
        }
        if (preg_match('/ll=(-?\d+\.\d+),(-?\d+\.\d+)/', $text, $m)) {
            return ['lat' => (float)$m[1], 'lng' => (float)$m[2]];
        }
        return null;
    }

    public function actionSearchPlaces()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $q = trim(Yii::$app->request->get('q', ''));
        $lat = (float)Yii::$app->request->get('lat', 31.95);
        $lng = (float)Yii::$app->request->get('lng', 35.91);
        if (empty($q)) return ['results' => []];

        $apiKey = \common\models\SystemSettings::get('google_maps', 'api_key', null)
            ?? Yii::$app->params['googleMapsApiKey'] ?? null;

        if ($apiKey) {
            $url = 'https://places.googleapis.com/v1/places:searchText';
            $body = json_encode([
                'textQuery' => $q,
                'locationRestriction' => [
                    'rectangle' => [
                        'low' => ['latitude' => 29.1, 'longitude' => 34.8],
                        'high' => ['latitude' => 33.4, 'longitude' => 39.3],
                    ],
                ],
                'languageCode' => 'ar',
                'maxResultCount' => 15,
            ]);

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'X-Goog-Api-Key: ' . $apiKey,
                    'X-Goog-FieldMask: places.displayName,places.formattedAddress,places.location,places.types',
                ],
                CURLOPT_TIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            unset($ch);

            if ($httpCode === 200 && $response) {
                $data = json_decode($response, true);
                if (!empty($data['places'])) {
                    $results = [];
                    foreach ($data['places'] as $p) {
                        $pLat = $p['location']['latitude'] ?? null;
                        $pLng = $p['location']['longitude'] ?? null;
                        if ($pLat === null) continue;
                        $results[] = [
                            'name' => $p['displayName']['text'] ?? '',
                            'addr' => $p['formattedAddress'] ?? '',
                            'lat' => (float)$pLat,
                            'lng' => (float)$pLng,
                            'types' => $p['types'] ?? [],
                        ];
                    }
                    return ['results' => $results, 'source' => 'google'];
                }
            }
        }

        return ['results' => [], 'source' => 'none'];
    }

    private function nominatimGeocode($query)
    {
        $url = 'https://nominatim.openstreetmap.org/search?' . http_build_query([
            'q' => $query,
            'format' => 'json',
            'limit' => 1,
            'accept-language' => 'ar',
            'viewbox' => '34.8,33.4,39.3,29.1',
            'bounded' => 1,
        ]);
        $ctx = stream_context_create(['http' => [
            'header' => "User-Agent: TayseerApp/1.0\r\n",
            'timeout' => 8,
        ]]);
        $json = @file_get_contents($url, false, $ctx);
        if ($json === false) return null;
        $data = json_decode($json, true);
        if (empty($data)) return null;
        return [
            'lat' => (float)$data[0]['lat'],
            'lng' => (float)$data[0]['lon'],
            'display_name' => $data[0]['display_name'] ?? '',
        ];
    }

    private function decodePlusCode($code)
    {
        $charset = '23456789CFGHJMPQRVWX';
        $code = strtoupper(trim($code));
        $code = str_replace('+', '', $code);
        if (strlen($code) < 2) return null;

        for ($i = 0; $i < strlen($code); $i++) {
            if (strpos($charset, $code[$i]) === false) return null;
        }

        $lat = 0.0;
        $lng = 0.0;
        $res = [20.0, 1.0, 0.05, 0.0025, 0.000125];
        $numPairs = min(5, intdiv(strlen($code), 2));

        for ($i = 0; $i < $numPairs; $i++) {
            $lat += strpos($charset, $code[$i * 2]) * $res[$i];
            $lng += strpos($charset, $code[$i * 2 + 1]) * $res[$i];
        }

        $latRes = $res[$numPairs - 1];
        $lngRes = $latRes;

        for ($i = 10; $i < strlen($code); $i++) {
            $latRes /= 5;
            $lngRes /= 4;
            $v = strpos($charset, $code[$i]);
            $lat += intdiv($v, 4) * $latRes;
            $lng += ($v % 4) * $lngRes;
        }

        return [
            'lat' => round($lat - 90 + $latRes / 2, 8),
            'lng' => round($lng - 180 + $lngRes / 2, 8),
        ];
    }

    private function recoverAndDecodePlusCode($shortCode, $refLat, $refLng)
    {
        $charset = '23456789CFGHJMPQRVWX';
        $short = strtoupper(trim($shortCode));
        $sep = strpos($short, '+');
        if ($sep === false || $sep >= 8) return null;

        $paddingLen = 8 - $sep;
        $pairs = intdiv($paddingLen, 2);
        $res = [20.0, 1.0, 0.05, 0.0025, 0.000125];

        $aLat = $refLat + 90;
        $aLng = $refLng + 180;

        $prefix = '';
        $tLat = $aLat;
        $tLng = $aLng;
        for ($i = 0; $i < $pairs; $i++) {
            $ld = min(19, (int)floor($tLat / $res[$i]));
            $gd = min(19, (int)floor($tLng / $res[$i]));
            $prefix .= $charset[$ld] . $charset[$gd];
            $tLat -= $ld * $res[$i];
            $tLng -= $gd * $res[$i];
        }

        $body = str_replace('+', '', $short);
        $full = $prefix . $body;
        $fullCode = substr($full, 0, 8) . '+' . substr($full, 8);

        $decoded = $this->decodePlusCode($fullCode);
        if (!$decoded) return null;

        $best = $decoded;
        $bestDist = pow($decoded['lat'] - $refLat, 2) + pow($decoded['lng'] - $refLng, 2);

        $bigRes = $res[min(4, $pairs - 1)] * 20;
        foreach ([[-1,0],[1,0],[0,-1],[0,1]] as $offset) {
            $nLat = $aLat + $offset[0] * $bigRes;
            $nLng = $aLng + $offset[1] * $bigRes;
            if ($nLat < 0 || $nLat >= 180 || $nLng < 0 || $nLng >= 360) continue;

            $np = '';
            $t1 = $nLat;
            $t2 = $nLng;
            for ($i = 0; $i < $pairs; $i++) {
                $ld2 = min(19, (int)floor($t1 / $res[$i]));
                $gd2 = min(19, (int)floor($t2 / $res[$i]));
                $np .= $charset[$ld2] . $charset[$gd2];
                $t1 -= $ld2 * $res[$i];
                $t2 -= $gd2 * $res[$i];
            }

            $nf = $np . $body;
            $nfCode = substr($nf, 0, 8) . '+' . substr($nf, 8);
            $nd = $this->decodePlusCode($nfCode);
            if ($nd) {
                $dist = pow($nd['lat'] - $refLat, 2) + pow($nd['lng'] - $refLng, 2);
                if ($dist < $bestDist) {
                    $best = $nd;
                    $bestDist = $dist;
                }
            }
        }

        return $best;
    }

    // ========================
    // Helper Methods
    // ========================

    /**
     * Update jobs cache
     */
    protected function updateJobsCache()
    {
        if (isset(Yii::$app->params['key_jobs']) && isset(Yii::$app->params['jobs_query'])) {
            Yii::$app->cache->set(
                Yii::$app->params['key_jobs'],
                Yii::$app->db->createCommand(Yii::$app->params['jobs_query'])->queryAll(),
                Yii::$app->params['time_duration']
            );
        }
        if (isset(Yii::$app->params['key_job_title']) && isset(Yii::$app->params['job_title_query'])) {
            Yii::$app->cache->set(
                Yii::$app->params['key_job_title'],
                Yii::$app->db->createCommand(Yii::$app->params['job_title_query'])->queryAll(),
                Yii::$app->params['time_duration']
            );
        }
    }

    /**
     * Finds the Jobs model based on its primary key value.
     * @param integer $id
     * @return Jobs the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Jobs::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('الصفحة المطلوبة غير موجودة.');
    }
}

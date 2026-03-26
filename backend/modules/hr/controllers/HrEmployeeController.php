<?php

namespace backend\modules\hr\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use common\helper\Permissions;
use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;
use yii\data\SqlDataProvider;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use backend\modules\hr\models\HrAnnualIncrement;
use backend\modules\hr\models\HrEmployeeExtended;
use backend\modules\hr\models\HrEmergencyContact;
use backend\modules\hr\models\HrEmployeeDocument;
use backend\modules\hr\models\HrAttendance;
use backend\modules\hr\models\HrEmployeeSalary;
use common\models\User;

/**
 * HrEmployeeController — Full CRUD for extended employee data
 */
class HrEmployeeController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['login', 'error'],
                        'allow' => true,
                    ],
                    [
                        'actions' => ['index', 'view', 'export', 'statement', 'user-info'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function () {
                            return Permissions::can(Permissions::EMP_VIEW);
                        },
                    ],
                    [
                        'actions' => ['create'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function () {
                            return Permissions::can(Permissions::EMP_CREATE);
                        },
                    ],
                    [
                        'actions' => ['update', 'toggle-status'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function () {
                            return Permissions::can(Permissions::EMP_UPDATE);
                        },
                    ],
                    [
                        'actions' => ['delete'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function () {
                            return Permissions::can(Permissions::EMP_DELETE);
                        },
                    ],
                    [
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function () {
                            return Permissions::hasAnyPermission(Permissions::getHrPermissions());
                        },
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                    'toggle-status' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * List employees with search (GridView).
     * Queries os_user joined with os_hr_employee_extended, os_department, os_designation.
     * Supports search by name, employee_code, department, status.
     *
     * @return string
     */
    public function actionIndex()
    {
        $request = Yii::$app->request;
        $searchName = $request->get('search_name', '');
        $searchCode = $request->get('search_code', '');
        $searchDepartment = $request->get('search_department', '');
        $searchStatus = $request->get('search_status', '');

        $query = (new Query())
            ->select([
                'u.id',
                'u.username',
                'u.name',
                'u.email',
                'u.mobile',
                'u.employee_type',       // activation state: Active/Suspended
                'u.employee_status',     // employment type: Full_time/Part_time
                'u.date_of_hire',
                'u.avatar',
                'ext.employee_code',
                'ext.contract_type',
                'ext.is_field_staff',
                'ext.id as extended_id',
                'd.title as department_name',
                'des.title as designation_name',
            ])
            ->from('{{%user}} u')
            ->leftJoin('{{%hr_employee_extended}} ext', 'ext.user_id = u.id AND ext.is_deleted = 0')
            ->leftJoin('{{%department}} d', 'd.id = u.department')
            ->leftJoin('{{%designation}} des', 'des.id = u.job_title');

        // Apply search filters
        if (!empty($searchName)) {
            $query->andWhere(['or',
                ['like', 'u.username', $searchName],
                ['like', 'u.name', $searchName],
            ]);
        }
        if (!empty($searchCode)) {
            $query->andWhere(['like', 'ext.employee_code', $searchCode]);
        }
        if (!empty($searchDepartment)) {
            $query->andWhere(['u.department' => $searchDepartment]);
        }
        if (!empty($searchStatus)) {
            // employee_type stores activation state (Active/Suspended)
            $query->andWhere(['u.employee_type' => $searchStatus]);
        }

        $query->andWhere(['not', ['u.confirmed_at' => null]]);
        $query->orderBy(['u.id' => SORT_DESC]);

        $allData = $query->all();

        // ترتيب: النشطين أولاً ثم المعطلين
        $activeData = [];
        $suspendedData = [];
        foreach ($allData as $row) {
            if (($row['employee_type'] ?? '') === 'Suspended') {
                $suspendedData[] = $row;
            } else {
                $activeData[] = $row;
            }
        }
        $sortedData = array_merge($activeData, $suspendedData);
        $suspendedCount = count($suspendedData);
        $activeCount = count($activeData);

        $dataProvider = new ArrayDataProvider([
            'allModels' => $sortedData,
            'pagination' => ['pageSize' => 50],
            'key' => 'id',
        ]);

        // Department list for filter dropdown
        $departments = ArrayHelper::map(
            (new Query())->select(['id', 'title'])->from('{{%department}}')->where(['status' => 'active'])->all(),
            'id',
            'title'
        );

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'activeCount' => $activeCount,
            'suspendedCount' => $suspendedCount,
            'searchName' => $searchName,
            'searchCode' => $searchCode,
            'searchDepartment' => $searchDepartment,
            'searchStatus' => $searchStatus,
            'departments' => $departments,
        ]);
    }

    /**
     * View employee profile with tabs (info, documents, emergency contacts,
     * attendance summary, salary, field info).
     *
     * @param int $id User ID
     * @return string
     */
    public function actionView($id)
    {
        $user = User::findOne($id);
        if ($user === null) {
            throw new NotFoundHttpException('الموظف المطلوب غير موجود.');
        }

        $extended = HrEmployeeExtended::find()
            ->where(['user_id' => $id, 'is_deleted' => 0])
            ->one();

        $documents = HrEmployeeDocument::find()
            ->where(['user_id' => $id, 'is_deleted' => 0])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();

        $emergencyContacts = HrEmergencyContact::find()
            ->where(['user_id' => $id, 'is_deleted' => 0])
            ->all();

        // Current month attendance summary
        $currentMonth = date('Y-m');
        $attendanceSummary = Yii::$app->db->createCommand("
            SELECT
                COUNT(*) as total_days,
                SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days,
                SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_days,
                SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_days,
                SUM(CASE WHEN status = 'on_leave' THEN 1 ELSE 0 END) as leave_days,
                SUM(COALESCE(total_hours, 0)) as total_hours,
                SUM(COALESCE(overtime_hours, 0)) as overtime_hours
            FROM {{%hr_attendance}}
            WHERE user_id = :userId
              AND attendance_date LIKE :month
              AND is_deleted = 0
        ", [':userId' => $id, ':month' => $currentMonth . '%'])->queryOne();

        // Salary structure
        $salaryComponents = HrEmployeeSalary::find()
            ->where([
                'user_id' => $id,
                'is_deleted' => 0,
            ])
            ->andWhere(['or',
                ['effective_to' => null],
                ['>=', 'effective_to', date('Y-m-d')],
            ])
            ->orderBy(['effective_from' => SORT_DESC])
            ->all();

        // العلاوات السنوية لهذا الموظف
        try {
            $increments = HrAnnualIncrement::find()
                ->where(['user_id' => $id, 'is_deleted' => 0])
                ->orderBy(['increment_year' => SORT_DESC, 'id' => SORT_DESC])
                ->all();
        } catch (\Exception $e) {
            $increments = [];
        }

        return $this->render('view', [
            'user' => $user,
            'extended' => $extended,
            'documents' => $documents,
            'emergencyContacts' => $emergencyContacts,
            'attendanceSummary' => $attendanceSummary,
            'salaryComponents' => $salaryComponents,
            'increments' => $increments,
        ]);
    }

    /**
     * Create new extended employee record.
     * Supports two modes:
     *   1. Select an existing user without an extended record
     *   2. Create a brand-new user inline (name, username, email, password, mobile)
     *
     * @return string|Response
     */
    public function actionCreate()
    {
        $model = new HrEmployeeExtended();
        $request = Yii::$app->request;

        // Ensure lookup tables have default data
        \backend\models\UserCategory::ensureTablesExist();
        \backend\models\UserCategory::seedDefaults();
        \backend\modules\department\models\Department::seedDefaults();
        \backend\modules\designation\models\Designation::seedDefaults();

        // Users without extended records — الفعالون فقط (نشط وغير محظور)
        $usersWithoutExtended = (new Query())
            ->select(['u.id', 'u.username', 'u.name', 'u.middle_name', 'u.last_name'])
            ->from('{{%user}} u')
            ->leftJoin('{{%hr_employee_extended}} ext', 'ext.user_id = u.id AND ext.is_deleted = 0')
            ->where(['ext.id' => null])
            ->andWhere(['u.employee_type' => 'Active'])
            ->andWhere(['u.blocked_at' => null])
            ->andWhere(['not', ['u.confirmed_at' => null]])
            ->all();

        // عرض موحّد: الاسم الكامل (username). مصدر الاسم: حقل name في os_user (من input new_user_name عند إنشاء مستخدم جديد)
        $userList = ArrayHelper::map($usersWithoutExtended, 'id', function ($row) {
            $fullName = trim(implode(' ', array_filter([
                $row['name'] ?? '',
                $row['middle_name'] ?? '',
                $row['last_name'] ?? '',
            ])));
            $username = $row['username'] ?? '';
            if ($fullName !== '' && strpos($fullName, '@') === false) {
                return $fullName . ' (' . $username . ')';
            }
            if (strpos($username, '@') !== false) {
                return 'الاسم غير محدد (' . $username . ')';
            }
            return $username;
        });

        $isPost = $request->isPost;
        $modelLoaded = $model->load($request->post());

        // Determine categories and employee flag from POST regardless of model load
        $selectedCategories = $request->post('user_categories', []);
        $employeeSlugs = ['employee', 'sales_employee', 'branch_manager', 'court_agent', 'manager'];
        $isEmployee = false;
        if (!empty($selectedCategories)) {
            $empCats = \backend\models\UserCategory::find()
                ->where(['slug' => $employeeSlugs, 'is_active' => 1])
                ->select('id')
                ->column();
            $isEmployee = !empty(array_intersect($empCats, $selectedCategories));
        }

        $createNewUser = (int)$request->post('create_new_user', 0);

        if ($isPost && ($modelLoaded || $createNewUser)) {

            // ─── Mode: Create new user inline ───
            if ($createNewUser) {
                $newName     = trim($request->post('new_user_name', ''));
                $newUsername  = trim($request->post('new_user_username', ''));
                $newEmail    = trim($request->post('new_user_email', ''));
                $newPassword = $request->post('new_user_password', '');
                $newMobile   = trim($request->post('new_user_mobile', ''));

                // Validation
                $errors = [];
                if ($newName === '')     $errors[] = 'الاسم الكامل مطلوب';
                if ($newUsername === '')  $errors[] = 'اسم المستخدم مطلوب';
                if ($newEmail === '')    $errors[] = 'البريد الإلكتروني مطلوب';
                if (strlen($newPassword) < 6) $errors[] = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل';

                if ($newUsername && User::find()->where(['username' => $newUsername])->exists()) {
                    $errors[] = 'اسم المستخدم "' . $newUsername . '" مستخدم مسبقاً';
                }
                if ($newEmail && User::find()->where(['email' => $newEmail])->exists()) {
                    $errors[] = 'البريد الإلكتروني "' . $newEmail . '" مستخدم مسبقاً';
                }

                if (!empty($errors)) {
                    Yii::$app->session->setFlash('error', implode('<br>', $errors));
                    return $this->render('create', ['model' => $model, 'userList' => $userList]);
                }

                $now = time();
                $passwordHash = Yii::$app->security->generatePasswordHash($newPassword);
                $authKey = Yii::$app->security->generateRandomString(32);

                // تحديد نوع الدوام والمسمى الوظيفي حسب الفئة
                $employeeStatus = $isEmployee ? 'Full_time' : 'Freelance';
                $designationId = $isEmployee ? ($request->post('user_designation') ?: null) : null;

                // تحديد نوع الدوام حسب الفئة لغير الموظفين
                $catSlugs = $this->getCategorySlugs($selectedCategories);
                if (!$isEmployee) {
                    if (in_array('vendor', $catSlugs)) $employeeStatus = 'Vendor';
                    elseif (in_array('investor', $catSlugs)) $employeeStatus = 'Investor';
                    elseif (in_array('branch_manager', $catSlugs)) $employeeStatus = 'Full_time';
                    else $employeeStatus = 'Freelance';
                }

                $transaction = Yii::$app->db->beginTransaction();
                try {
                    // الاسم الكامل يُخزّن في عمود name بجدول os_user (من حقل النموذج new_user_name)
                    $userData = [
                        'username'      => $newUsername,
                        'email'         => $newEmail,
                        'password_hash' => $passwordHash,
                        'auth_key'      => $authKey,
                        'name'          => $newName,
                        'mobile'        => $newMobile ?: null,
                        'confirmed_at'  => $now,
                        'created_at'    => $now,
                        'updated_at'    => $now,
                        'created_by'    => Yii::$app->user->id,
                        'employee_type' => 'Active',
                        'employee_status' => $employeeStatus,
                        'gender'        => 'Male',
                        'marital_status' => 'Single',
                        'flags'         => 0,
                    ];
                    if ($designationId) {
                        $userData['job_title'] = (int)$designationId;
                    }

                    Yii::$app->db->createCommand()->insert('{{%user}}', $userData)->execute();

                    $newUserId = Yii::$app->db->getLastInsertID();

                    // Save user categories
                    if (!empty($selectedCategories)) {
                        \backend\models\UserCategoryMap::syncUserCategories($newUserId, $selectedCategories, Yii::$app->user->id);
                    }

                    // ربط الفرع — فقط لموظف مبيعات (فئة sales_employee)
                    if (in_array('sales_employee', $catSlugs)) {
                        $locId = $request->post('user_location');
                        $locVal = ($locId !== null && $locId !== '') ? (int)$locId : null;
                        Yii::$app->db->createCommand()->update('{{%user}}', ['location' => $locVal, 'updated_at' => $now], ['id' => $newUserId])->execute();
                    }

                    // Only create HrEmployeeExtended if "employee" category is selected
                    if ($isEmployee) {
                        $model->user_id = $newUserId;
                        if (empty($model->employee_code)) {
                            $maxCode = (new Query())->from('{{%hr_employee_extended}}')->max('id');
                            $model->employee_code = 'EMP-' . str_pad(($maxCode ?? 0) + 1, 4, '0', STR_PAD_LEFT);
                        }
                        $model->created_at = $now;
                        $model->updated_at = $now;
                        $model->created_by = Yii::$app->user->id;
                        $model->updated_by = Yii::$app->user->id;

                        if (!$model->save(false)) {
                            throw new \Exception('فشل حفظ بيانات الموظف: ' . implode(', ', $model->getFirstErrors()));
                        }

                        // مزامنة البيانات المشتركة إلى os_user
                        $this->syncEmployeeFieldsToUser($newUserId, $model, $request);
                    }

                    // إسناد الصلاحيات تلقائياً
                    $this->autoAssignPermissions($newUserId, $catSlugs, $designationId);

                    $transaction->commit();
                    $msg = $isEmployee ? 'تم إنشاء المستخدم وملف الموظف وإسناد الصلاحيات بنجاح.' : 'تم إنشاء المستخدم وإسناد الصلاحيات بنجاح.';
                    Yii::$app->session->setFlash('success', $msg);
                    return $this->redirect($isEmployee ? ['view', 'id' => $newUserId] : ['index']);
                } catch (\Exception $e) {
                    $transaction->rollBack();
                    Yii::$app->session->setFlash('error', 'حدث خطأ: ' . $e->getMessage());
                    return $this->render('create', ['model' => $model, 'userList' => $userList]);
                }
            }

            // ─── Mode: Select existing user OR non-employee with existing user ───
            $userId = $model->user_id ?: $request->post('HrEmployeeExtended', [])['user_id'] ?? null;
            $catSlugs = $this->getCategorySlugs($selectedCategories);
            $designationId = $isEmployee ? ($request->post('user_designation') ?: null) : null;

            if (!$userId && !$isEmployee) {
                Yii::$app->session->setFlash('error', 'يجب اختيار مستخدم أو إنشاء مستخدم جديد');
                return $this->render('create', ['model' => $model, 'userList' => $userList]);
            }

            $transaction = Yii::$app->db->beginTransaction();
            try {
                if ($isEmployee && $modelLoaded) {
                    $model->created_at = time();
                    $model->updated_at = time();
                    $model->created_by = Yii::$app->user->id;
                    $model->updated_by = Yii::$app->user->id;

                    if (empty($model->employee_code)) {
                        $maxCode = (new Query())->from('{{%hr_employee_extended}}')->max('id');
                        $model->employee_code = 'EMP-' . str_pad(($maxCode ?? 0) + 1, 4, '0', STR_PAD_LEFT);
                    }

                    if (!$model->save()) {
                        throw new \Exception('فشل حفظ بيانات الموظف: ' . implode(', ', $model->getFirstErrors()));
                    }
                    $userId = $model->user_id;

                    // مزامنة البيانات المشتركة إلى os_user
                    $this->syncEmployeeFieldsToUser($userId, $model, $request);
                }

                // Save user categories
                if ($userId && !empty($selectedCategories)) {
                    \backend\models\UserCategoryMap::syncUserCategories($userId, $selectedCategories, Yii::$app->user->id);
                }

                // ربط الفرع — فقط لموظف مبيعات (فئة sales_employee)
                if ($userId) {
                    $this->syncUserLocation($userId, $request, $catSlugs);
                }

                // إسناد الصلاحيات تلقائياً
                if ($userId) {
                    $this->autoAssignPermissions($userId, $catSlugs, $designationId);
                }

                $transaction->commit();
                Yii::$app->session->setFlash('success', 'تم الحفظ وإسناد الصلاحيات بنجاح.');
                return $this->redirect($isEmployee ? ['view', 'id' => $userId] : ['index']);
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', $e->getMessage());
            }
        }

        return $this->render('create', ['model' => $model, 'userList' => $userList]);
    }

    /**
     * Update employee extended data.
     *
     * @param int $id HrEmployeeExtended ID
     * @return string|Response
     */
    public function actionUpdate($id)
    {
        \backend\models\UserCategory::ensureTablesExist();
        \backend\models\UserCategory::seedDefaults();
        \backend\modules\department\models\Department::seedDefaults();
        \backend\modules\designation\models\Designation::seedDefaults();

        $model = $this->findModel($id);
        $request = Yii::$app->request;

        if ($model->load($request->post())) {
            $model->updated_at = time();
            $model->updated_by = Yii::$app->user->id;

            $transaction = Yii::$app->db->beginTransaction();
            try {
                if (!$model->save()) {
                    throw new \Exception('فشل تحديث بيانات الموظف: ' . implode(', ', $model->getFirstErrors()));
                }

                // تحديث بيانات المستخدم الأساسية + مزامنة الحقول المشتركة
                if ($model->user_id) {
                    $userUpdate = [];

                    $editName = trim($request->post('edit_user_name', ''));
                    $editUsername = trim($request->post('edit_user_username', ''));
                    $editEmail = trim($request->post('edit_user_email', ''));
                    $editPassword = $request->post('edit_user_password', '');
                    $editMobile = trim($request->post('edit_user_mobile', ''));

                    if ($editName !== '') $userUpdate['name'] = $editName;
                    if ($editUsername !== '') {
                        $exists = User::find()->where(['username' => $editUsername])->andWhere(['!=', 'id', $model->user_id])->exists();
                        if ($exists) throw new \Exception('اسم المستخدم "' . $editUsername . '" مستخدم مسبقاً');
                        $userUpdate['username'] = $editUsername;
                    }
                    if ($editEmail !== '') {
                        $exists = User::find()->where(['email' => $editEmail])->andWhere(['!=', 'id', $model->user_id])->exists();
                        if ($exists) throw new \Exception('البريد الإلكتروني "' . $editEmail . '" مستخدم مسبقاً');
                        $userUpdate['email'] = $editEmail;
                    }
                    if ($editPassword !== '' && strlen($editPassword) >= 6) {
                        $userUpdate['password_hash'] = Yii::$app->security->generatePasswordHash($editPassword);
                    }
                    $userUpdate['mobile'] = $editMobile ?: null;

                    // مزامنة: contract_start → date_of_hire
                    if ($model->contract_start) {
                        $userUpdate['date_of_hire'] = $model->contract_start;
                    }

                    // مزامنة: المسمى الوظيفي
                    $designationId = $request->post('user_designation');
                    if ($designationId) {
                        $userUpdate['job_title'] = (int)$designationId;
                    }

                    // مزامنة: القسم
                    $departmentId = $request->post('user_department');
                    if ($departmentId) {
                        $userUpdate['department'] = (int)$departmentId;
                    }

                    // الفرع — فقط لموظف مبيعات (فئة sales_employee)
                    $selectedCats = $request->post('user_categories', []);
                    $updateCatSlugs = $this->getCategorySlugs($selectedCats);
                    if (in_array('sales_employee', $updateCatSlugs)) {
                        $locationId = $request->post('user_location');
                        $userUpdate['location'] = ($locationId !== null && $locationId !== '') ? (int)$locationId : null;
                    } else {
                        $userUpdate['location'] = null;
                    }

                    $userUpdate['updated_at'] = time();

                    if (!empty($userUpdate)) {
                        Yii::$app->db->createCommand()->update('{{%user}}', $userUpdate, ['id' => $model->user_id])->execute();
                    }
                }

                // Sync user categories
                $selectedCategories = $request->post('user_categories', []);
                if ($model->user_id) {
                    \backend\models\UserCategoryMap::syncUserCategories($model->user_id, $selectedCategories, Yii::$app->user->id);
                }

                $transaction->commit();
                Yii::$app->session->setFlash('success', 'تم تحديث بيانات الموظف بنجاح.');
                return $this->redirect(['view', 'id' => $model->user_id]);
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', $e->getMessage());
            }
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Soft delete employee extended record.
     *
     * @param int $id HrEmployeeExtended ID
     * @return Response
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        $request = Yii::$app->request;

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $model->is_deleted = 1;
            $model->updated_at = time();
            $model->updated_by = Yii::$app->user->id;
            if (!$model->save(false)) {
                throw new \Exception('فشل حذف سجل الموظف.');
            }
            $transaction->commit();
            Yii::$app->session->setFlash('success', 'تم حذف سجل الموظف بنجاح.');
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::$app->session->setFlash('error', $e->getMessage());
        }

        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ['forceClose' => true, 'forceReload' => '#crud-datatable-pjax'];
        }

        return $this->redirect(['index']);
    }

    /**
     * Toggle employee active/suspended status.
     * Sets employee_status and blocked_at accordingly.
     *
     * @param int $id User ID (os_user.id)
     * @return Response
     */
    public function actionToggleStatus($id)
    {
        $user = User::findOne($id);
        if ($user === null) {
            throw new NotFoundHttpException('الموظف المطلوب غير موجود.');
        }

        $success = false;

        $transaction = Yii::$app->db->beginTransaction();
        try {
            // Note: employee_type stores activation state ('Active'/'Suspended')
            //       employee_status stores employment type ('Full_time'/'Part_time')
            if ($user->employee_type === 'Suspended' || $user->blocked_at !== null) {
                // ── تفعيل الموظف ──
                $user->employee_type = 'Active';
                $user->blocked_at = null;
                $flashMessage = 'تم تفعيل الموظف "' . ($user->name ?: $user->username) . '" بنجاح.';
                $flashType = 'success';
            } else {
                // ── تعطيل الموظف ──
                $user->employee_type = 'Suspended';
                $user->blocked_at = time();
                $flashMessage = 'تم تعطيل الموظف "' . ($user->name ?: $user->username) . '" بنجاح.';
                $flashType = 'warning';
            }

            $user->updated_at = time();

            if (!$user->save(false)) {
                throw new \Exception('فشل تحديث حالة الموظف: ' . implode(', ', $user->getFirstErrors()));
            }

            $transaction->commit();
            $success = true;
            Yii::$app->session->setFlash($flashType, $flashMessage);
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::$app->session->setFlash('error', $e->getMessage());
        }

        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'success' => $success,
                'forceReload' => '#crud-datatable-pjax',
            ];
        }

        return $this->redirect(['index']);
    }

    /**
     * Export employees to CSV.
     *
     * @return void
     */
    public function actionExport()
    {
        $rows = (new Query())
            ->select([
                'u.id',
                'u.username',
                'u.name',
                'u.email',
                'u.mobile',
                'u.employee_status',
                'u.date_of_hire',
                'ext.employee_code',
                'ext.national_id',
                'ext.employment_type',
                'd.name as department_name',
                'des.name as designation_name',
            ])
            ->from('{{%user}} u')
            ->leftJoin('{{%hr_employee_extended}} ext', 'ext.user_id = u.id AND ext.is_deleted = 0')
            ->leftJoin('{{%department}} d', 'd.id = u.department')
            ->leftJoin('{{%designation}} des', 'des.id = u.job_title')
            ->orderBy(['u.id' => SORT_ASC])
            ->all();

        $filename = 'employees_export_' . date('Y-m-d_His') . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        // BOM for Excel Arabic support
        echo "\xEF\xBB\xBF";

        $fp = fopen('php://output', 'w');

        // Header row
        fputcsv($fp, [
            'ID',
            'اسم المستخدم',
            'الاسم',
            'البريد الإلكتروني',
            'الهاتف',
            'الحالة',
            'تاريخ التوظيف',
            'رقم الموظف',
            'رقم الهوية',
            'نوع التوظيف',
            'القسم',
            'المسمى الوظيفي',
        ]);

        foreach ($rows as $row) {
            fputcsv($fp, [
                $row['id'],
                $row['username'],
                $row['name'],
                $row['email'],
                $row['mobile'],
                $row['employee_status'],
                $row['date_of_hire'],
                $row['employee_code'],
                $row['national_id'],
                $row['employment_type'],
                $row['department_name'],
                $row['designation_name'],
            ]);
        }

        fclose($fp);
        Yii::$app->end();
    }

    /**
     * كشف حساب الموظف السنوي — Employee Annual Statement
     *
     * @param int $id User ID
     * @param int|null $year
     * @return string
     */
    public function actionStatement($id, $year = null)
    {
        $user = User::findOne($id);
        if (!$user) {
            throw new NotFoundHttpException('الموظف غير موجود.');
        }

        $year = $year ?: (int) date('Y');

        // Get all payslips for this employee for the year
        $payslips = (new Query())
            ->select([
                'ps.id as payslip_id',
                'ps.basic_salary',
                'ps.total_earnings',
                'ps.total_deductions',
                'ps.net_salary',
                'ps.working_days',
                'ps.present_days',
                'ps.absent_days',
                'ps.leave_days',
                'ps.overtime_hours',
                'ps.status as payslip_status',
                'pr.period_month',
                'pr.period_year',
                'pr.run_code',
                'pr.status as run_status',
            ])
            ->from('{{%hr_payslip}} ps')
            ->innerJoin('{{%hr_payroll_run}} pr', 'pr.id = ps.payroll_run_id AND pr.is_deleted = 0')
            ->where([
                'ps.user_id' => $id,
                'ps.is_deleted' => 0,
                'pr.period_year' => $year,
            ])
            ->orderBy(['pr.period_month' => SORT_ASC])
            ->all();

        // Get payslip lines for each payslip
        $payslipIds = array_column($payslips, 'payslip_id');
        $lines = [];
        if (!empty($payslipIds)) {
            $allLines = (new Query())
                ->select(['payslip_id', 'component_type', 'description', 'amount', 'sort_order'])
                ->from('{{%hr_payslip_line}}')
                ->where(['payslip_id' => $payslipIds])
                ->orderBy(['payslip_id' => SORT_ASC, 'sort_order' => SORT_ASC])
                ->all();

            foreach ($allLines as $line) {
                $lines[$line['payslip_id']][] = $line;
            }
        }

        // Calculate yearly totals
        $yearlyTotals = [
            'total_earnings' => 0,
            'total_deductions' => 0,
            'total_net' => 0,
            'total_basic' => 0,
        ];
        foreach ($payslips as $ps) {
            $yearlyTotals['total_earnings'] += (float)$ps['total_earnings'];
            $yearlyTotals['total_deductions'] += (float)$ps['total_deductions'];
            $yearlyTotals['total_net'] += (float)$ps['net_salary'];
            $yearlyTotals['total_basic'] += (float)$ps['basic_salary'];
        }

        return $this->render('statement', [
            'user' => $user,
            'year' => $year,
            'payslips' => $payslips,
            'lines' => $lines,
            'yearlyTotals' => $yearlyTotals,
        ]);
    }

    /**
     * AJAX: return user info + assigned categories for a given user ID.
     */
    public function actionUserInfo($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $user = User::findOne((int)$id);
        if (!$user) {
            return ['success' => false, 'error' => 'المستخدم غير موجود'];
        }

        $categoryIds = (new Query())
            ->select('category_id')
            ->from('{{%user_category_map}}')
            ->where(['user_id' => $user->id])
            ->column();

        $categorySlugs = [];
        if (!empty($categoryIds)) {
            $categorySlugs = (new Query())
                ->select('slug')
                ->from('{{%user_categories}}')
                ->where(['id' => $categoryIds, 'is_active' => 1])
                ->column();
        }

        return [
            'success' => true,
            'user' => [
                'id'         => $user->id,
                'name'       => $user->name,
                'username'   => $user->username,
                'email'      => $user->email,
                'mobile'     => $user->mobile,
                'department' => $user->department,
                'job_title'  => $user->job_title,
                'location'   => $user->location ?? null,
            ],
            'category_ids'   => array_map('intval', $categoryIds),
            'category_slugs' => $categorySlugs,
        ];
    }

    /**
     * Finds the HrEmployeeExtended model based on its primary key.
     *
     * @param int $id
     * @return HrEmployeeExtended
     * @throws NotFoundHttpException
     */
    protected function findModel($id)
    {
        $model = HrEmployeeExtended::find()
            ->where(['id' => $id, 'is_deleted' => 0])
            ->one();

        if ($model !== null) {
            return $model;
        }

        throw new NotFoundHttpException('سجل الموظف المطلوب غير موجود.');
    }

    /**
     * مزامنة الحقول المشتركة من HrEmployeeExtended إلى os_user
     * contract_start → date_of_hire, department, job_title
     */
    protected function syncEmployeeFieldsToUser($userId, $model, $request)
    {
        $sync = [];

        // contract_start → date_of_hire
        if ($model->contract_start) {
            $sync['date_of_hire'] = $model->contract_start;
        }

        // المسمى الوظيفي
        $designationId = $request->post('user_designation');
        if ($designationId) {
            $sync['job_title'] = (int)$designationId;
        }

        // القسم
        $departmentId = $request->post('user_department');
        if ($departmentId) {
            $sync['department'] = (int)$departmentId;
        }

        // الفرع — فقط لموظف مبيعات (يُحدَّث من syncUserLocation عند وجود فئة sales_employee)

        if (!empty($sync)) {
            $sync['updated_at'] = time();
            Yii::$app->db->createCommand()->update('{{%user}}', $sync, ['id' => $userId])->execute();
        }
    }

    /**
     * مزامنة حقل الفرع (os_user.location) — فقط عندما تكون فئة "موظف مبيعات" (sales_employee) مختارة
     */
    protected function syncUserLocation($userId, $request, array $catSlugs = [])
    {
        $value = null;
        if (in_array('sales_employee', $catSlugs)) {
            $locationId = $request->post('user_location');
            $value = ($locationId !== null && $locationId !== '') ? (int)$locationId : null;
        }
        Yii::$app->db->createCommand()->update('{{%user}}', [
            'location' => $value,
            'updated_at' => time(),
        ], ['id' => $userId])->execute();
    }

    /**
     * جلب slugs الفئات المختارة من IDs
     */
    protected function getCategorySlugs(array $categoryIds)
    {
        if (empty($categoryIds)) return [];
        return (new Query())
            ->select('slug')
            ->from('{{%user_categories}}')
            ->where(['id' => $categoryIds, 'is_active' => 1])
            ->column();
    }

    /**
     * خريطة ربط فئات المستخدمين (slug) مع أدوار RBAC
     */
    protected static function getCategoryRoleMap()
    {
        return [
            'vendor'         => 'مورّد أجهزة',
            'investor'       => 'مستثمر',
            'manager'        => 'مدير النظام',
        ];
    }

    /**
     * خريطة ربط المسميات الوظيفية (title) مع أدوار RBAC
     */
    protected static function getDesignationRoleMap()
    {
        return [
            'مدير عام'           => 'مدير النظام',
            'مدير مبيعات'        => 'مدير مبيعات',
            'محاسب'              => 'محاسب',
            'موظف متابعة'        => 'موظفة متابعه',
            'محامي'              => 'محامي',
            'موظف مبيعات'        => 'موظف مبيعات',
            'مندوب محكمة'        => 'مندوب محكمة',
            'مورّد أجهزة'        => 'مورّد أجهزة',
            'أمين مخزن'          => 'موظف مبيعات',
            'مدير مالي'          => 'محاسب',
            'مسؤول موارد بشرية'  => 'مدير النظام',
        ];
    }

    /**
     * إسناد صلاحيات RBAC تلقائياً بناءً على الفئة أو المسمى الوظيفي
     */
    protected function autoAssignPermissions($userId, array $catSlugs, $designationId = null)
    {
        $auth = Yii::$app->authManager;
        if (!$auth) return;

        $roleName = null;

        // أولاً: البحث عن الدور من المسمى الوظيفي (للموظفين)
        if ($designationId) {
            $desTitle = (new Query())
                ->select('title')
                ->from('{{%designation}}')
                ->where(['id' => $designationId])
                ->scalar();

            if ($desTitle) {
                $desMap = static::getDesignationRoleMap();
                $roleName = $desMap[$desTitle] ?? null;
            }
        }

        // ثانياً: إذا لم يتحدد من المسمى، نبحث من الفئة (للموردين/المستثمرين/المندوبين)
        if (!$roleName && !empty($catSlugs)) {
            $catMap = static::getCategoryRoleMap();
            foreach ($catSlugs as $slug) {
                if (isset($catMap[$slug])) {
                    $roleName = $catMap[$slug];
                    break;
                }
            }
        }

        if (!$roleName) return;

        // جلب صلاحيات الدور
        $role = $auth->getRole($roleName);
        if (!$role) {
            Yii::warning("الدور '$roleName' غير موجود في جدول الصلاحيات. تأكد من تشغيل 'إنشاء الأدوار الافتراضية' أولاً.", __METHOD__);
            return;
        }

        $rolePerms = $auth->getChildren($roleName);
        $assignedCount = 0;

        foreach ($rolePerms as $child) {
            $perm = $auth->getPermission($child->name);
            if ($perm) {
                try {
                    $auth->assign($perm, $userId);
                    $assignedCount++;
                } catch (\Exception $e) {
                    // تجاهل إذا كانت الصلاحية مسندة مسبقاً
                }
            }
        }

        Yii::info("تم إسناد {$assignedCount} صلاحية للمستخدم #{$userId} من دور '{$roleName}'", __METHOD__);
    }
}

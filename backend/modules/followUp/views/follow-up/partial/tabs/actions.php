<?php
use yii\helpers\Url;
use yii\helpers\Html;
use backend\modules\followUp\helper\ContractCalculations;
use backend\modules\contractInstallment\models\ContractInstallment;
use common\helper\Permissions;

?>
<div class="card radius-10 w-100">
    <div class="card-body">
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-xl-3 row-cols-xxl-3 g-3">
            <div class="col">
                <div class="card radius-10 border shadow-none mb-0">
                    <div class="card-body">
                        <div class="text-center">
                            <div class="fs-3 text-tiffany">
                                <ion-icon name="people-circle-sharp" role="img" class="md hydrated" aria-label="archive sharp"></ion-icon>
                            </div>
                            <h6 class="mb-0 mt-2">
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exampleModalCenter2">
                                    <?=Yii::t('app', 'صور العملاء')?>
                                </button>

                            </h6>

                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card radius-10 border shadow-none mb-0">
                    <div class="card-body">
                        <div class="text-center">
                            <div class="fs-3 text-danger">
                                <ion-icon name="create-sharp" role="img" class="md hydrated" aria-label="bookmarks sharp"></ion-icon>
                            </div>
                            <h6 class="mb-0 mt-2">
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#changeStatse">
                                    <?=Yii::t('app', 'تغيير حالة العقد')?>
                                </button>

                            </h6>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card radius-10 border shadow-none mb-0">
                    <div class="card-body">
                        <div class="text-center">
                            <div class="fs-3 text-warning">
                                <ion-icon name="folder-sharp" role="img" class="md hydrated" aria-label="folder sharp"></ion-icon>
                            </div>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exampleModal">
                                <?=Yii::t('app', ' للتدقيق')?>
                            </button>

                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card radius-10 border shadow-none mb-0">
                    <div class="card-body">
                        <div class="text-center">
                            <div class="fs-3 text-success">
                                <ion-icon name="file-tray-sharp" role="img" class="md hydrated" aria-label="file tray sharp"></ion-icon>
                            </div>
                            <h6 class="mb-0 mt-2">      <?= Html::a(Yii::t('app', 'كشف حساب'), Url::to(['printer', 'contract_id' => $contract_id]), ['class' => 'btn btn-primary', 'target' => '_blank']) ?>
                            </h6>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card radius-10 border shadow-none mb-0">
                    <div class="card-body">
                        <div class="text-center">
                            <div class="fs-3 text-primary">
                                <ion-icon name="file-tray-sharp" role="img" class="md hydrated" aria-label="logo windows"></ion-icon>
                            </div>
                            <h6 class="mb-0 mt-2">      <?= Html::a(Yii::t('app', 'برائة الذمة'), Url::to(['clearance', 'contract_id' => $contract_id]), ['class' => 'btn btn-primary', 'target' => '_blank']) ?>
                            </h6>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card radius-10 border shadow-none mb-0">
                    <div class="card-body">
                        <div class="text-center">
                            <div class="fs-3 text-info">
                                <ion-icon name="call-sharp" role="img" class="md hydrated" aria-label="logo github"></ion-icon>
                            </div>
                            <h6 class="mb-0 mt-2">      <?php if (Yii::$app->user->can(Permissions::MANAGER)) { ?>
                                    <?php if ($contractCalculations->contract_model->is_can_not_contact == 1) {
                                        ?>

                                            <?= Html::a(Yii::t('app', 'يوجد ارقام هواتف'), Url::to(['/contracts/contracts/is-connect', 'contract_id' => $contract_id]), ['class' => 'btn btn-primary']); ?>

                                        <?php
                                    } else {
                                        ?>

                                            <?= Html::a(Yii::t('app', 'لا يوجد ارقام هواتف'), Url::to(['/contracts/contracts/is-not-connect', 'contract_id' => $contract_id]), ['class' => 'btn btn-primary']); ?>

                                        <?php
                                    }
                                    ?>
                                <?php } ?></h6>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card radius-10 border shadow-none mb-0">
                    <div class="card-body">
                        <div class="text-center">
                            <div class="fs-3 text-warning">
                                <ion-icon name="person-circle-sharp" role="img" class="md hydrated" aria-label="file tray sharp"></ion-icon>
                            </div>
                            <h6 class="mb-0 mt-2">      <?= Html::a( Yii::t('app', 'طلب مراجعة المدير') , ['/contracts/contracts/convert-to-manager', 'id' => $contract_id], ['class' => 'btn btn-primary']) ?>
                            </h6>
                        </div>
                    </div>
                </div>
            </div>
            <?php
            if (!( $contractCalculations->contract_model->status == 'judiciary' ||  $contractCalculations->contract_model->status == 'legal_department')) {
            ?>
            <div class="col">
                <div class="card radius-10 border shadow-none mb-0">
                    <div class="card-body">
                        <div class="text-center">
                            <div class="fs-3 text-info">
                                <ion-icon name="briefcase-sharp" role="img" class="md hydrated" aria-label="file tray sharp"></ion-icon>
                            </div>
                            <h6 class="mb-0 mt-2">      <?= Html::a( Yii::t('app', 'التحويل للدائرة القانونية') ,  ['/contracts/contracts/to-legal-department', 'id' => $contract_id], ['class' => 'btn btn-primary']) ?>
                            </h6>
                        </div>
                    </div>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>
</div>
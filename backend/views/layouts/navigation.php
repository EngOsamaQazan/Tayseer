<?php

use yii\helpers\Html;
use app\modules\notifications\models\notifications;
use yii\helpers\Url;
use yii\widgets\Pjax;
use app\components\CompanyChecked;
use yii\widgets\Menu;
use app\helper\Permissions;


?>

<aside class="sidebar-wrapper" data-simplebar="init">
    <div class="simplebar-wrapper" style="margin: 0px;">
        <div class="simplebar-height-auto-observer-wrapper">
            <div class="simplebar-height-auto-observer"></div>
        </div>
        <div class="simplebar-mask">
            <div class="simplebar-offset" style="left: 0px; bottom: 0px;">
                <div class="simplebar-content-wrapper" style="height: 100%; overflow: hidden scroll;">
                    <div class="simplebar-content " style="padding: 0px;">
                        <div class="sidebar-header">
                            <div>
                                <?=Html::img(Url::to(['/g.jpg']),['class'=>'logo-icon'])?>
                             </div>
                            <div>
                                <h4 class="logo-text" style="font-size: 16px"><?=$companyName?></h4>
                            </div>
                            <div class="toggle-icon ms-auto">
                                <ion-icon name="menu-sharp" role="img" class="md hydrated"
                                          aria-label="menu sharp"></ion-icon>
                            </div>
                        </div>
                        <!--navigation-->
                        <ul class="metismenu mm-show" id="menu">
                            <?php if (Permissions::groupsPermissions('inventory')) { ?>
                                <li>
                                    <a href="javascript:;" class="has-arrow">
                                        <div class="parent-icon">
                                            <ion-icon name="home-sharp" role="img" class="md hydrated"
                                                      aria-label="home sharp"></ion-icon>
                                        </div>
                                        <div class="menu-title"><?= Yii::t('app', 'Inventory') ?></div>
                                    </a>
                                    <ul class="mm-collapse">
                                        <?= Permissions::showItems(Permissions::INVENTORY_ITEMS, Yii::t('app', 'Inventory Items'), '/inventoryItems/inventory-items') ?>
                                        <?= Permissions::showItems(Permissions::INVENTORY_STOCK_LOCATIONS, Yii::t('app', 'Stock Locations'), '/inventoryStockLocations/inventory-stock-locations') ?>
                                        <?= Permissions::showItems(Permissions::INVENTORY_SUPPLIERS, Yii::t('app', 'suppliers'), '/inventorySuppliers/inventory-suppliers') ?>
                                        <?= Permissions::showItems(Permissions::INVENTORY_IEMS_QUERY, Yii::t('app', 'Item Quantities'), '/inventoryItemQuantities/inventory-item-quantities') ?>
                                        <?= Permissions::showItems(Permissions::INVENTORY_INVOICES, Yii::t('app', 'Inventory Invoices'), '/inventoryInvoices/inventory-invoices') ?>

                                    </ul>
                                </li>
                            <?php } ?>
                            <?php if( Permissions::groupsPermissions('employees manage') ){?>
                            <li>
                                <a href="javascript:;" class="has-arrow">
                                    <div class="parent-icon">
                                        <ion-icon name="bag-handle-sharp" role="img" class="md hydrated"
                                                  aria-label="bag handle sharp"></ion-icon>
                                    </div>
                                    <div class="menu-title"><?= Yii::t('app', 'ادارة الموظفين') ?></div>
                                </a>
                                <ul class="mm-collapse">
                                    <?= Permissions::showItems(Permissions::EMPLOYEE, Yii::t('app', 'Employees'), '/employee/employee') ?>
                                    <?= Permissions::showItems(Permissions::HOLIDAYS, Yii::t('app', 'Holidays'), '/holidays/holidays') ?>
                                    <?= Permissions::showItems(Permissions::LEAVE_POLICY, Yii::t('app', 'Leave Policy'), '/leavePolicy/leave-policy') ?>
                                    <?= Permissions::showItems(Permissions::LEAVE_TYPES, Yii::t('app', 'Leave Types'), '/leaveTypes/leave-types') ?>
                                    <?= Permissions::showItems(Permissions::WORKDAYS, Yii::t('app', 'Workdays'), '/workdays/workdays') ?>
                                    <?= Permissions::showItems(Permissions::LEAVE_REQUEST, Yii::t('app', 'Leave Request'), '/leaveRequest/leave-request') ?>

                                </ul>
                            </li>
                            <?php } ?>
                            <?php if( Permissions::groupsPermissions('legal department') ){?>
                            <li>
                                <a href="javascript:;" class="has-arrow">
                                    <div class="parent-icon">
                                        <ion-icon name="briefcase-sharp" role="img" class="md hydrated"
                                                  aria-label="briefcase sharp"></ion-icon>
                                    </div>
                                    <div class="menu-title"><?= Yii::t('app', 'legal department') ?></div>
                                </a>
                                <ul class="mm-collapse">
                                    <?= Permissions::showItems(Permissions::TRANSFER_TO_LEGAL_DEPARTMENT, Yii::t('app', 'Transfer to legal department'), '/contracts/contracts/legal-department') ?>
                                    <?= Permissions::showItems(Permissions::JUDICIARY, Yii::t('app', 'Judiciary'), '/judiciary/judiciary') ?>
                                    <?= Permissions::showItems(Permissions::JUDICIARY_CUSTOMERS_ACTION, Yii::t('app', 'Judiciary Customers Actions'), '/judiciaryCustomersActions/judiciary-customers-actions') ?>
                                    <?= Permissions::showItems(Permissions::JUDICIARY, 'المراسلات والتبليغات', '/diwan/diwan/correspondence-index') ?>
                                    <?= Permissions::showItems(Permissions::COLLECTION, Yii::t('app', 'Collection'), '/collection/collection') ?>

                                </ul>
                            </li>
                            <?php } ?>
                            <?php if( Permissions::groupsPermissions('reports') ){?>
                            <li >
                                <a class="has-arrow" href="javascript:;">
                                    <div class="parent-icon">
                                        <ion-icon name="newspaper-sharp" role="img" class="md hydrated"
                                                  aria-label="newspaper sharp"></ion-icon>
                                    </div>
                                    <div class="menu-title"><?= Yii::t('app', 'Reports') ?></div>
                                </a>
                                <ul class="mm-collapse mm-show">
                                    <?= Permissions::showItems(Permissions::JUDICIARY, Yii::t('app', 'Total customer payments'), '/reports/reports/total-customer-payments-index') ?>
                                    <?= Permissions::showItems(Permissions::JUDICIARY_CUSTOMERS_ACTION, Yii::t('app', 'judiciary report'), '/reports/reports/judiciary-index') ?>
                                    <?= Permissions::showItems(Permissions::COLLECTION, Yii::t('app', 'Follow Up Reports'), '/reports/reports/index2') ?>
                                </ul>
                            </li>
                            <?php } ?>
                            <?php if( Permissions::groupsPermissions('permissions') ){?>
                            <li>
                                <a class="has-arrow" href="javascript:;">
                                    <div class="parent-icon">
                                        <ion-icon name="gift-sharp" role="img" class="md hydrated"
                                                  aria-label="gift sharp"></ion-icon>
                                    </div>
                                    <div class="menu-title"><?= Yii::t('app', 'الصلاحيات') ?></div>
                                </a>
                                <ul class="mm-collapse">
                                    <?= Permissions::showItems(Permissions::PERMISSION, Yii::t('app', 'Permission'), '/admin/permission') ?>
                                    <?= Permissions::showItems(Permissions::ROLE, Yii::t('app', 'Role'), '/admin/role') ?>
                                    <?= Permissions::showItems(Permissions::ROUTE, Yii::t('app', 'Route'), '/admin/route') ?>
                                    <?= Permissions::showItems(Permissions::ASSIGNMENT, Yii::t('app', 'Assignment'), '/admin/assignment') ?>

                                </ul>
                            </li>
                            <?php }?>
                            <!-- ═══ قسم الديوان ═══ -->
                            <li>
                                <a class="has-arrow" href="javascript:;">
                                    <div class="parent-icon">
                                        <ion-icon name="document-text-sharp" role="img" class="md hydrated"
                                                  aria-label="document text sharp"></ion-icon>
                                    </div>
                                    <div class="menu-title"><?= Yii::t('app', 'قسم الديوان') ?></div>
                                </a>
                                <ul class="mm-collapse">
                                    <li><a href="<?= Url::to(['/diwan/diwan/index']) ?>"><ion-icon name="speedometer-outline"></ion-icon><?= Yii::t('app', 'لوحة المعلومات') ?></a></li>
                                    <li><a href="<?= Url::to(['/diwan/diwan/create']) ?>"><ion-icon name="add-circle-outline"></ion-icon><?= Yii::t('app', 'معاملة جديدة') ?></a></li>
                                    <li><a href="<?= Url::to(['/diwan/diwan/transactions']) ?>"><ion-icon name="swap-horizontal-outline"></ion-icon><?= Yii::t('app', 'المعاملات') ?></a></li>
                                    <li><a href="<?= Url::to(['/diwan/diwan/search']) ?>"><ion-icon name="search-outline"></ion-icon><?= Yii::t('app', 'بحث الوثائق') ?></a></li>
                                    <li><a href="<?= Url::to(['/diwan/diwan/reports']) ?>"><ion-icon name="stats-chart-outline"></ion-icon><?= Yii::t('app', 'التقارير') ?></a></li>
                                </ul>
                            </li>
                            <?php if( Permissions::groupsPermissions('changing') ){?>
                            <li>
                                <a class="has-arrow" href="javascript:;">
                                    <div class="parent-icon">
                                        <ion-icon name="bar-chart-sharp" role="img" class="md hydrated"
                                                  aria-label="bar chart sharp"></ion-icon>
                                    </div>
                                    <div class="menu-title"><?= Yii::t('app', 'ادارة المتغيرات') ?></div>
                                </a>
                                <ul class="mm-collapse">
                                    <?= Permissions::showItems(Permissions::STATUS, Yii::t('app', 'Status'), '/status/status/index') ?>
                                    <?= Permissions::showItems(Permissions::Document_STATUS, Yii::t('app', 'Document Status'), '/documentStatus/document-status/index') ?>
                                    <?= Permissions::showItems(Permissions::COUSINS, Yii::t('app', 'Cousins'), '/cousins/cousins/index') ?>
                                    <?= Permissions::showItems(Permissions::CITIZEN, Yii::t('app', 'Citizen'), '/citizen/citizen/index') ?>

                                    <?= Permissions::showItems(Permissions::BANCKS, Yii::t('app', 'Bancks'), '/bancks/bancks/index') ?>
                                    <?= Permissions::showItems(Permissions::HEAR_ABOUT_US, Yii::t('app', 'Hear About Us'), '/hearAboutUs/hear-about-us/index') ?>
                                    <?= Permissions::showItems(Permissions::CITY, Yii::t('app', 'City'), '/city/city/index') ?>
                                    <?= Permissions::showItems(Permissions::PAYMENT_TYPE, Yii::t('app', 'Payment Type'), '/paymentType/payment-type/index') ?>

                                    <?= Permissions::showItems(Permissions::FEELINGS, Yii::t('app', 'feelings'), '/feelings/feelings/index') ?>
                                    <?= Permissions::showItems(Permissions::CONTACT_TYPE, Yii::t('app', 'Contact Type'), '/contactType/contact-type/index') ?>
                                    <?= Permissions::showItems(Permissions::CONNECTION_RESPONSE, Yii::t('app', 'Connection Response'), '/connectionResponse/connection-response/index') ?>
                                    <?= Permissions::showItems(Permissions::DOCYUMENT_TYPE, Yii::t('app', 'Document Type'), '/documentType/document-type/index') ?>

                                    <?= Permissions::showItems(Permissions::JUDICIARY_ACTION, Yii::t('app', 'Judiciary Actions'), '/judiciaryActions/judiciary-actions') ?>
                                    <?= Permissions::showItems(Permissions::JUDICIARY_TYPE, Yii::t('app', 'Judiciary Type'), '/judiciaryType/judiciary-type') ?>
                                    <?= Permissions::showItems(Permissions::LAWYERS, 'المفوضين والوكلاء', '/lawyers/lawyers') ?>
                                    <?= Permissions::showItems(Permissions::COURT, Yii::t('app', 'Court'), '/court/court') ?>

                                    <?= Permissions::showItems(Permissions::MASSAGING, Yii::t('app', 'Massages'), '/sms/sms') ?>
                                    <?= Permissions::showItems(Permissions::JOBS, Yii::t('app', 'Jobs'), '/jobs/jobs') ?>
                                    <?= Permissions::showItems(Permissions::EXPENSE_CATEGORIES, Yii::t('app', 'Expense Categories'), '/expenseCategories/expense-categories') ?>
                                    <?= Permissions::showItems(Permissions::JUDICIARY, 'الجهات الرسمية', '/judiciaryAuthorities/judiciary-authorities') ?>
                                    <?= Permissions::showItems(Permissions::JUDICIARY, 'العطل الرسمية', '/officialHolidays/official-holidays') ?>
                                    <?= Permissions::showItems(Permissions::JUDICIARY, 'قوالب الطلبات', '/judiciaryRequestTemplates/judiciary-request-templates') ?>


                                </ul>
                            </li>
                            <?php } ?>

                            <?php if(Yii::$app->user->can(Permissions::CUSTOMERS)){?>
                                <li>
                                    <?= Html::a('  <div class="parent-icon">
                                        <ion-icon name="people-circle-sharp" role="img" class="md hydrated"
                                                  aria-label="person circle sharp"></ion-icon>
                                    </div>
                                    <div class="menu-title">'.Yii::t('app','Customers').'</div>',Url::to(['/customers/index']))?>

                                </li>
                            <?php } ?>
                            <?php if(Yii::$app->user->can(Permissions::COMPAINES)){?>
                                <li>
                                    <?= Html::a('  <div class="parent-icon">
                                        <ion-icon name="list-sharp" role="img" class="md hydrated"
                                                  aria-label="person circle sharp"></ion-icon>
                                    </div>
                                    <div class="menu-title">المُستثمرين</div>',Url::to(['/companies/companies/index']))?>

                                </li>
                            <?php } ?>
                            <?php if(Yii::$app->user->can(Permissions::CONTRACTS)){?>
                                <li>
                                    <?= Html::a('  <div class="parent-icon">
                                        <ion-icon name="file-tray-sharp" role="img" class="md hydrated"
                                                  aria-label="person circle sharp"></ion-icon>
                                    </div>
                                    <div class="menu-title">'.Yii::t('app','Contracts').'</div>',Url::to(['/contracts/contracts/index']))?>

                                </li>
                            <?php } ?>
                            <?php if(Yii::$app->user->can(Permissions::FOLLOW_UP_REPORTS)){?>
                                <li>
                                    <?= Html::a('  <div class="parent-icon">
                                        <ion-icon name="call-sharp" role="img" class="md hydrated"
                                                  aria-label="person circle sharp"></ion-icon>
                                    </div>
                                    <div class="menu-title">'.Yii::t('app','follow Up Report').'</div>',Url::to(['/followUpReport/follow-up-report/index']))?>

                                </li>
                            <?php } ?>
                            <?php if(Yii::$app->user->can(Permissions::INCOME)){?>
                                <li>
                                    <?= Html::a('  <div class="parent-icon">
                                        <ion-icon name="arrow-down-circle-sharp" role="img" class="md hydrated"
                                                  aria-label="person circle sharp"></ion-icon>
                                    </div>
                                    <div class="menu-title">'.Yii::t('app','Income').'</div>',Url::to(['/income/income/income-list']))?>

                                </li>
                            <?php } ?>
                            <?php if(Yii::$app->user->can(Permissions::EMPLOYEE)){?>
                                <li>
                                    <?= Html::a('  <div class="parent-icon">
                                        <ion-icon name="people-circle-sharp" role="img" class="md hydrated"
                                                  aria-label="person circle sharp"></ion-icon>
                                    </div>
                                    <div class="menu-title">'.Yii::t('app','Employee').'</div>',Url::to(['/employee/employee/index']))?>

                                </li>
                            <?php } ?>
                            <?php if(Yii::$app->user->can(Permissions::FINANCIAL_TRANSACTION)){?>
                                <li>
                                    <?= Html::a('  <div class="parent-icon">
                                        <ion-icon name="trending-up-sharp" role="img" class="md hydrated"
                                                  aria-label="person circle sharp"></ion-icon>
                                    </div>
                                    <div class="menu-title">'.Yii::t('app','Financial Transaction').'</div>',Url::to(['/financialTransaction/financial-transaction/index']))?>

                                </li>
                            <?php } ?>
                            <?php if(Yii::$app->user->can(Permissions::EXPENSES)){?>
                                <li>
                                    <?= Html::a('  <div class="parent-icon">
                                        <ion-icon name="arrow-up-circle-sharp" role="img" class="md hydrated"
                                                  aria-label="person circle sharp"></ion-icon>
                                    </div>
                                    <div class="menu-title">'.Yii::t('app','Expenses').'</div>',Url::to(['/expenses/expenses/index']))?>

                                </li>
                            <?php } ?>
                            <?php if(Yii::$app->user->can(Permissions::LOAN_SCHEDULING)){?>
                                <li>
                                    <?= Html::a('  <div class="parent-icon">
                                        <ion-icon name="bar-chart-sharp" role="img" class="md hydrated"
                                                  aria-label="person circle sharp"></ion-icon>
                                    </div>
                                    <div class="menu-title">'.Yii::t('app','Loan Scheduling').'</div>',Url::to(['/loanScheduling/loan-scheduling/index']))?>

                                </li>
                            <?php } ?>
                            <?php if(Yii::$app->user->can(Permissions::Notification)){?>
                                <li>
                                    <?= Html::a('  <div class="parent-icon">
                                        <ion-icon name="notifications-sharp" role="img" class="md hydrated"
                                                  aria-label="person circle sharp"></ion-icon>
                                    </div>
                                    <div class="menu-title">'.Yii::t('app','Notification').'</div>',Url::to(['/notification/notification/index']))?>

                                </li>
                            <?php } ?>
                        </ul>
                        <!--end navigation-->
                    </div>
                </div>
            </div>
        </div>
        <div class="simplebar-placeholder" style="width: auto; height: 1449px;"></div>
    </div>
    <div class="simplebar-track simplebar-horizontal" style="visibility: hidden;">
        <div class="simplebar-scrollbar" style="width: 0px; display: none;"></div>
    </div>
    <div class="simplebar-track simplebar-vertical" style="visibility: visible;">
        <div class="simplebar-scrollbar"
             style="height: 350px; transform: translate3d(0px, 0px, 0px); display: block;"></div>
    </div>
</aside>



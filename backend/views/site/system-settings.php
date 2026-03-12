<?php
/**
 * إعدادات النظام — System Settings
 * @var yii\web\View $this
 * @var array $googleCloud
 * @var array $usageStats
 * @var array $smsSettings
 * @var array $waSettings
 * @var string $activeTab
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'إعدادات النظام';

// Register CSS
$this->registerCssFile(Yii::$app->request->baseUrl . '/css/system-settings.css?v=' . time());
?>

<div class="sys-settings-page">
    <!-- ════════════ Header ════════════ -->
    <div class="sys-header">
        <div class="sys-header-right">
            <h1><i class="fa fa-cogs"></i> إعدادات النظام</h1>
            <p class="sys-header-desc">إدارة الإعدادات العامة وتكامل الخدمات الخارجية</p>
        </div>
        <div class="sys-header-left">
            <a href="<?= Url::to(['/site/index']) ?>" class="sys-back-btn">
                <i class="fa fa-arrow-right"></i> العودة للوحة التحكم
            </a>
        </div>
    </div>

    <!-- ════════════ Body ════════════ -->
    <div class="sys-body">
        <!-- Sidebar Tabs -->
        <div class="sys-sidebar">
            <div class="sys-nav-list">
                <a href="#" class="sys-nav-item <?= $activeTab === 'general' ? 'active' : '' ?>" data-tab="general">
                    <div class="sys-nav-icon"><i class="fa fa-sliders"></i></div>
                    <div class="sys-nav-text">
                        <span class="sys-nav-label">الإعدادات العامة</span>
                        <span class="sys-nav-sub">المتغيرات · الجداول المرجعية</span>
                    </div>
                    <span class="sys-nav-count">20</span>
                </a>
                <a href="#" class="sys-nav-item <?= in_array($activeTab, ['google_cloud', 'google_maps', 'google_apis']) ? 'active' : '' ?>" data-tab="google_apis">
                    <div class="sys-nav-icon"><i class="fa fa-google"></i></div>
                    <div class="sys-nav-text">
                        <span class="sys-nav-label">خدمات Google</span>
                        <span class="sys-nav-sub">Vision API · Maps API · التكاليف</span>
                    </div>
                    <?php
                    $gcActive = !empty($googleCloud['has_private_key']) && !empty($googleCloud['client_email']);
                    $gmActive = !empty($googleMaps['configured']);
                    ?>
                    <?php if ($gcActive && $gmActive): ?>
                        <span class="sys-nav-badge active"><i class="fa fa-check-circle"></i></span>
                    <?php elseif ($gcActive || $gmActive): ?>
                        <span class="sys-nav-badge" style="background:#f59e0b;color:#fff"><i class="fa fa-adjust"></i></span>
                    <?php else: ?>
                        <span class="sys-nav-badge inactive"><i class="fa fa-times-circle"></i></span>
                    <?php endif; ?>
                </a>
                <a href="#" class="sys-nav-item <?= $activeTab === 'messaging' ? 'active' : '' ?>" data-tab="messaging">
                    <div class="sys-nav-icon"><i class="fa fa-comments"></i></div>
                    <div class="sys-nav-text">
                        <span class="sys-nav-label">خدمات الرسائل</span>
                        <span class="sys-nav-sub">SMS API · WhatsApp Business</span>
                    </div>
                    <?php
                    $smsActive = !empty($smsSettings['enabled']) && $smsSettings['enabled'] === '1';
                    $waActive  = !empty($waSettings['enabled']) && $waSettings['enabled'] === '1';
                    ?>
                    <?php if ($smsActive && $waActive): ?>
                        <span class="sys-nav-badge active"><i class="fa fa-check-circle"></i></span>
                    <?php elseif ($smsActive || $waActive): ?>
                        <span class="sys-nav-badge" style="background:#f59e0b;color:#fff"><i class="fa fa-adjust"></i></span>
                    <?php else: ?>
                        <span class="sys-nav-badge inactive"><i class="fa fa-times-circle"></i></span>
                    <?php endif; ?>
                </a>
                <a href="#" class="sys-nav-item disabled" data-tab="notifications">
                    <div class="sys-nav-icon"><i class="fa fa-bell"></i></div>
                    <div class="sys-nav-text">
                        <span class="sys-nav-label">الإشعارات</span>
                        <span class="sys-nav-sub">قريباً</span>
                    </div>
                </a>
                <a href="#" class="sys-nav-item <?= $activeTab === 'backup' ? 'active' : '' ?>" data-tab="backup">
                    <div class="sys-nav-icon"><i class="fa fa-cloud-download"></i></div>
                    <div class="sys-nav-text">
                        <span class="sys-nav-label">النسخ الاحتياطي</span>
                        <span class="sys-nav-sub">قاعدة البيانات · الملفات · الإعدادات</span>
                    </div>
                    <span class="sys-nav-badge" style="background:#27ae60;color:#fff"><i class="fa fa-shield"></i></span>
                </a>
                <hr style="border-color:#eee;margin:8px 0;" />
                <a href="<?= Url::to(['/site/image-manager']) ?>" class="sys-nav-item">
                    <div class="sys-nav-icon"><i class="fa fa-image"></i></div>
                    <div class="sys-nav-text">
                        <span class="sys-nav-label">إدارة صور العملاء</span>
                        <span class="sys-nav-sub">مراجعة · تصحيح · تصدير</span>
                    </div>
                    <span class="sys-nav-badge" style="background:var(--gold);color:#fff;"><i class="fa fa-wrench"></i></span>
                </a>
            </div>
        </div>

        <!-- Content Area -->
        <div class="sys-content">
            <?php foreach (Yii::$app->session->getAllFlashes() as $type => $message): ?>
                <div class="alert alert-<?= $type === 'error' ? 'danger' : $type ?> alert-dismissible sys-alert">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <i class="fa fa-<?= $type === 'success' ? 'check-circle' : 'exclamation-triangle' ?>"></i>
                    <?= $message ?>
                </div>
            <?php endforeach; ?>

            <!-- ═══════════ Google APIs Tab (merged with inner tabs) ═══════════ -->
            <div class="sys-tab-content <?= in_array($activeTab, ['google_cloud', 'google_maps', 'google_apis']) ? 'active' : '' ?>" id="tab-google_apis">

                <?php
                    $innerTab = 'vision';
                    if ($activeTab === 'google_maps') $innerTab = 'maps';
                ?>
                <!-- Inner Tabs Bar -->
                <div class="g-inner-tabs">
                    <button type="button" class="g-inner-tab <?= $innerTab === 'vision' ? 'active' : '' ?>" data-inner="vision">
                        <i class="fa fa-eye"></i> Vision API
                    </button>
                    <button type="button" class="g-inner-tab <?= $innerTab === 'maps' ? 'active' : '' ?>" data-inner="maps">
                        <i class="fa fa-map"></i> Maps API
                    </button>
                    <button type="button" class="g-inner-tab" data-inner="costs">
                        <i class="fa fa-line-chart"></i> التكاليف والإحصائيات
                    </button>
                </div>

                <!-- ── Inner Panel: Vision API ── -->
                <div class="g-inner-panel <?= $innerTab === 'vision' ? 'active' : '' ?>" id="g-panel-vision">
                <form method="post" action="<?= Url::to(['system-settings']) ?>" id="gc-settings-form">
                    <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
                    <input type="hidden" name="settings_tab" value="google_cloud">

                    <!-- Connection Status Card -->
                    <div class="sys-card sys-status-card">
                        <div class="sys-card-header">
                            <div class="sys-card-title">
                                <i class="fa fa-signal"></i> حالة الاتصال
                            </div>
                            <button type="button" class="sys-test-btn" id="btn-test-connection" onclick="testGoogleConnection()">
                                <i class="fa fa-plug"></i> اختبار الاتصال
                            </button>
                        </div>
                        <div class="sys-card-body">
                            <div class="sys-connection-status" id="connection-status">
                                <?php
                                $hasCredentials = !empty($googleCloud['has_private_key']) && !empty($googleCloud['client_email']);
                                $isEnabled = !empty($googleCloud['enabled']) && $googleCloud['enabled'] === '1';
                                ?>
                                <?php if ($hasCredentials && $isEnabled): ?>
                                    <div class="sys-status-indicator configured">
                                        <i class="fa fa-check-circle fa-2x"></i>
                                        <div>
                                            <strong>تم التكوين</strong>
                                            <p>بيانات الاعتماد محفوظة — اضغط "اختبار الاتصال" للتحقق</p>
                                        </div>
                                    </div>
                                <?php elseif ($hasCredentials && !$isEnabled): ?>
                                    <div class="sys-status-indicator configured" style="border-right-color:#f59e0b">
                                        <i class="fa fa-pause-circle fa-2x" style="color:#f59e0b"></i>
                                        <div>
                                            <strong>مكوّن لكن معطّل</strong>
                                            <p>بيانات الاعتماد محفوظة — فعّل الخدمة أو اضغط "اختبار الاتصال" للتفعيل التلقائي</p>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="sys-status-indicator not-configured">
                                        <i class="fa fa-exclamation-circle fa-2x"></i>
                                        <div>
                                            <strong>غير مكوّن</strong>
                                            <p>أدخل بيانات اعتماد حساب الخدمة لتفعيل التكامل مع Google Cloud</p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Toggle Enable -->
                    <div class="sys-card">
                        <div class="sys-card-body">
                            <div class="sys-toggle-row">
                                <div class="sys-toggle-info">
                                    <i class="fa fa-power-off sys-toggle-icon"></i>
                                    <div>
                                        <strong>تفعيل Google Cloud Vision API</strong>
                                        <p>عند التفعيل، سيتم استخدام التصنيف الذكي لتحليل مستندات العملاء تلقائياً</p>
                                    </div>
                                </div>
                                <label class="sys-switch">
                                    <input type="hidden" name="gc_enabled" value="0">
                                    <input type="checkbox" name="gc_enabled" value="1" <?= (!empty($googleCloud['enabled']) && $googleCloud['enabled'] === '1') ? 'checked' : '' ?>>
                                    <span class="sys-switch-slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- ═══════════ Setup Guide (Collapsible) ═══════════ -->
                    <div class="sys-card sys-guide-card">
                        <div class="sys-card-header sys-guide-toggle" onclick="toggleGuide()">
                            <div class="sys-card-title">
                                <i class="fa fa-graduation-cap"></i> دليل الإعداد خطوة بخطوة
                            </div>
                            <div class="sys-guide-toggle-hint">
                                <span id="guide-toggle-text">عرض الدليل</span>
                                <i class="fa fa-chevron-down" id="guide-chevron"></i>
                            </div>
                        </div>
                        <div class="sys-guide-body" id="setup-guide" style="display:none;">

                            <!-- Step Progress -->
                            <div class="gc-steps-progress">
                                <div class="gc-step-dot active" data-step="1"><span>1</span></div>
                                <div class="gc-step-line"></div>
                                <div class="gc-step-dot" data-step="2"><span>2</span></div>
                                <div class="gc-step-line"></div>
                                <div class="gc-step-dot" data-step="3"><span>3</span></div>
                                <div class="gc-step-line"></div>
                                <div class="gc-step-dot" data-step="4"><span>4</span></div>
                                <div class="gc-step-line"></div>
                                <div class="gc-step-dot" data-step="5"><span>5</span></div>
                                <div class="gc-step-line"></div>
                                <div class="gc-step-dot" data-step="6"><span>6</span></div>
                            </div>

                            <!-- ──── Step 1: Create Project ──── -->
                            <div class="gc-step active" id="gc-step-1">
                                <div class="gc-step-header">
                                    <div class="gc-step-number">1</div>
                                    <div>
                                        <h3>إنشاء مشروع في Google Cloud</h3>
                                        <p>أول خطوة هي إنشاء مشروع جديد أو استخدام مشروع موجود</p>
                                    </div>
                                </div>

                                <!-- Console Mockup -->
                                <div class="gc-console-mockup">
                                    <div class="gc-console-topbar">
                                        <div class="gc-console-logo">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="#fff"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                                            <span>Google Cloud</span>
                                        </div>
                                        <div class="gc-console-breadcrumb">Console → New Project</div>
                                    </div>
                                    <div class="gc-console-content">
                                        <div class="gc-console-sidebar-mini">
                                            <div class="gc-sidebar-item">IAM & Admin</div>
                                            <div class="gc-sidebar-item">APIs & Services</div>
                                            <div class="gc-sidebar-item active-item">Manage Resources</div>
                                        </div>
                                        <div class="gc-console-main">
                                            <div class="gc-console-title-bar">
                                                <span class="gc-console-page-title">New Project</span>
                                            </div>
                                            <div class="gc-mock-form">
                                                <div class="gc-mock-field">
                                                    <label>Project name *</label>
                                                    <div class="gc-mock-input gc-highlight-field">tayseer-erp</div>
                                                </div>
                                                <div class="gc-mock-field">
                                                    <label>Project ID</label>
                                                    <div class="gc-mock-input">tayseer-erp-438712 <span class="gc-mock-edit">Edit</span></div>
                                                    <div class="gc-mock-hint">
                                                        <i class="fa fa-arrow-up gc-arrow-blink"></i>
                                                        <span class="gc-highlight-text">هذا هو Project ID الذي ستنسخه لاحقاً</span>
                                                    </div>
                                                </div>
                                                <div class="gc-mock-field">
                                                    <label>Organization</label>
                                                    <div class="gc-mock-input">No organization</div>
                                                </div>
                                                <div class="gc-mock-field">
                                                    <label>Location</label>
                                                    <div class="gc-mock-input">No organization</div>
                                                </div>
                                                <div class="gc-mock-btn-row">
                                                    <button class="gc-mock-btn primary" type="button">CREATE</button>
                                                    <button class="gc-mock-btn" type="button">CANCEL</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="gc-step-instructions">
                                    <div class="gc-instruction-item">
                                        <span class="gc-inst-num">1</span>
                                        <span>اذهب إلى <a href="https://console.cloud.google.com/projectcreate" target="_blank" class="gc-link">console.cloud.google.com/projectcreate</a></span>
                                    </div>
                                    <div class="gc-instruction-item">
                                        <span class="gc-inst-num">2</span>
                                        <span>أدخل اسم المشروع (مثلاً: <code>tayseer-erp</code>)</span>
                                    </div>
                                    <div class="gc-instruction-item">
                                        <span class="gc-inst-num">3</span>
                                        <span>سيتم توليد <strong>Project ID</strong> تلقائياً — <em>احفظه، ستحتاجه في الخطوة الأخيرة</em></span>
                                    </div>
                                    <div class="gc-instruction-item">
                                        <span class="gc-inst-num">4</span>
                                        <span>اضغط <strong>CREATE</strong></span>
                                    </div>
                                </div>

                                <div class="gc-step-nav">
                                    <div></div>
                                    <button type="button" class="gc-next-btn" onclick="goToStep(2)">الخطوة التالية <i class="fa fa-arrow-left"></i></button>
                                </div>
                            </div>

                            <!-- ──── Step 2: Enable Vision API ──── -->
                            <div class="gc-step" id="gc-step-2">
                                <div class="gc-step-header">
                                    <div class="gc-step-number">2</div>
                                    <div>
                                        <h3>تفعيل Vision API</h3>
                                        <p>تفعيل خدمة Cloud Vision API في مشروعك</p>
                                    </div>
                                </div>

                                <div class="gc-console-mockup">
                                    <div class="gc-console-topbar">
                                        <div class="gc-console-logo">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="#fff"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                                            <span>Google Cloud</span>
                                        </div>
                                        <div class="gc-console-breadcrumb">APIs & Services → Library</div>
                                    </div>
                                    <div class="gc-console-content">
                                        <div class="gc-console-main" style="max-width:100%">
                                            <div class="gc-console-title-bar">
                                                <span class="gc-console-page-title">API Library</span>
                                            </div>
                                            <div class="gc-mock-search">
                                                <i class="fa fa-search"></i>
                                                <span>Cloud Vision API</span>
                                            </div>
                                            <div class="gc-api-card">
                                                <div class="gc-api-card-icon">
                                                    <svg width="40" height="40" viewBox="0 0 24 24" fill="#4285f4"><circle cx="12" cy="12" r="3.2"/><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" fill="none" stroke="#4285f4" stroke-width="1.5"/></svg>
                                                </div>
                                                <div class="gc-api-card-info">
                                                    <strong>Cloud Vision API</strong>
                                                    <p>Integrates Google Vision features including image labeling, face, logo, and landmark detection, optical character recognition (OCR)...</p>
                                                    <div class="gc-api-card-meta">Google Enterprise API</div>
                                                </div>
                                                <button class="gc-mock-btn primary gc-enable-btn" type="button">
                                                    <i class="fa fa-check"></i> ENABLE
                                                </button>
                                            </div>
                                            <div class="gc-mock-hint" style="margin-top:10px;">
                                                <i class="fa fa-arrow-up gc-arrow-blink"></i>
                                                <span class="gc-highlight-text">اضغط ENABLE لتفعيل الخدمة</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="gc-step-instructions">
                                    <div class="gc-instruction-item">
                                        <span class="gc-inst-num">1</span>
                                        <span>اذهب إلى <a href="https://console.cloud.google.com/apis/library/vision.googleapis.com" target="_blank" class="gc-link">APIs & Services → Library</a></span>
                                    </div>
                                    <div class="gc-instruction-item">
                                        <span class="gc-inst-num">2</span>
                                        <span>ابحث عن <code>Cloud Vision API</code></span>
                                    </div>
                                    <div class="gc-instruction-item">
                                        <span class="gc-inst-num">3</span>
                                        <span>اضغط <strong>ENABLE</strong> لتفعيل الخدمة</span>
                                    </div>
                                    <div class="gc-instruction-item gc-note-item">
                                        <i class="fa fa-info-circle"></i>
                                        <span>أول 1,000 طلب شهرياً <strong>مجاني</strong> — بعدها $1.50 لكل 1,000 طلب</span>
                                    </div>
                                </div>

                                <div class="gc-step-nav">
                                    <button type="button" class="gc-prev-btn" onclick="goToStep(1)"><i class="fa fa-arrow-right"></i> السابق</button>
                                    <button type="button" class="gc-next-btn" onclick="goToStep(3)">الخطوة التالية <i class="fa fa-arrow-left"></i></button>
                                </div>
                            </div>

                            <!-- ──── Step 3: Create Service Account ──── -->
                            <div class="gc-step" id="gc-step-3">
                                <div class="gc-step-header">
                                    <div class="gc-step-number">3</div>
                                    <div>
                                        <h3>إنشاء حساب خدمة (Service Account)</h3>
                                        <p>حساب الخدمة يسمح للنظام بالتواصل مع Google Cloud بدون تدخل بشري</p>
                                    </div>
                                </div>

                                <div class="gc-console-mockup">
                                    <div class="gc-console-topbar">
                                        <div class="gc-console-logo">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="#fff"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                                            <span>Google Cloud</span>
                                        </div>
                                        <div class="gc-console-breadcrumb">IAM & Admin → Service Accounts</div>
                                    </div>
                                    <div class="gc-console-content">
                                        <div class="gc-console-main" style="max-width:100%">
                                            <div class="gc-console-title-bar">
                                                <span class="gc-console-page-title">Service Accounts</span>
                                                <button class="gc-mock-btn primary gc-create-sa-btn" type="button">
                                                    <i class="fa fa-plus"></i> CREATE SERVICE ACCOUNT
                                                </button>
                                            </div>
                                            <div class="gc-mock-form" style="margin-top:12px;">
                                                <div class="gc-mock-field">
                                                    <label>Service account name *</label>
                                                    <div class="gc-mock-input gc-highlight-field">tayseer-vision</div>
                                                </div>
                                                <div class="gc-mock-field">
                                                    <label>Service account ID *</label>
                                                    <div class="gc-mock-input">tayseer-vision@tayseer-erp.iam.gserviceaccount.com</div>
                                                    <div class="gc-mock-hint">
                                                        <i class="fa fa-arrow-up gc-arrow-blink"></i>
                                                        <span class="gc-highlight-text">هذا هو Client Email الذي ستنسخه لاحقاً</span>
                                                    </div>
                                                </div>
                                                <div class="gc-mock-field">
                                                    <label>Service account description</label>
                                                    <div class="gc-mock-input">Vision API for document classification</div>
                                                </div>
                                                <div class="gc-mock-btn-row">
                                                    <button class="gc-mock-btn primary" type="button">CREATE AND CONTINUE</button>
                                                    <button class="gc-mock-btn" type="button">CANCEL</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="gc-step-instructions">
                                    <div class="gc-instruction-item">
                                        <span class="gc-inst-num">1</span>
                                        <span>اذهب إلى <a href="https://console.cloud.google.com/iam-admin/serviceaccounts" target="_blank" class="gc-link">IAM & Admin → Service Accounts</a></span>
                                    </div>
                                    <div class="gc-instruction-item">
                                        <span class="gc-inst-num">2</span>
                                        <span>اضغط <strong>+ CREATE SERVICE ACCOUNT</strong> في الأعلى</span>
                                    </div>
                                    <div class="gc-instruction-item">
                                        <span class="gc-inst-num">3</span>
                                        <span>أدخل اسم حساب الخدمة (مثلاً: <code>tayseer-vision</code>)</span>
                                    </div>
                                    <div class="gc-instruction-item">
                                        <span class="gc-inst-num">4</span>
                                        <span>اضغط <strong>CREATE AND CONTINUE</strong></span>
                                    </div>
                                    <div class="gc-instruction-item">
                                        <span class="gc-inst-num">5</span>
                                        <span>في "Grant this service account access" — اختر الدور: <code>Cloud Vision AI Service Agent</code> أو <code>Editor</code></span>
                                    </div>
                                    <div class="gc-instruction-item">
                                        <span class="gc-inst-num">6</span>
                                        <span>اضغط <strong>DONE</strong></span>
                                    </div>
                                </div>

                                <div class="gc-step-nav">
                                    <button type="button" class="gc-prev-btn" onclick="goToStep(2)"><i class="fa fa-arrow-right"></i> السابق</button>
                                    <button type="button" class="gc-next-btn" onclick="goToStep(4)">الخطوة التالية <i class="fa fa-arrow-left"></i></button>
                                </div>
                            </div>

                            <!-- ──── Step 4: Create JSON Key ──── -->
                            <div class="gc-step" id="gc-step-4">
                                <div class="gc-step-header">
                                    <div class="gc-step-number">4</div>
                                    <div>
                                        <h3>إنشاء مفتاح JSON</h3>
                                        <p>تحميل ملف المفتاح الذي يحتوي على بيانات الاعتماد المطلوبة</p>
                                    </div>
                                </div>

                                <div class="gc-console-mockup">
                                    <div class="gc-console-topbar">
                                        <div class="gc-console-logo">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="#fff"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                                            <span>Google Cloud</span>
                                        </div>
                                        <div class="gc-console-breadcrumb">Service Account → Keys</div>
                                    </div>
                                    <div class="gc-console-content">
                                        <div class="gc-console-main" style="max-width:100%">
                                            <div class="gc-sa-tabs">
                                                <span>DETAILS</span>
                                                <span>PERMISSIONS</span>
                                                <span class="active-tab">KEYS</span>
                                            </div>
                                            <div class="gc-console-title-bar" style="margin-top:10px;">
                                                <span class="gc-console-page-title">Keys</span>
                                                <button class="gc-mock-btn primary" type="button">
                                                    <i class="fa fa-plus"></i> ADD KEY ▾
                                                </button>
                                            </div>
                                            <div class="gc-dropdown-menu">
                                                <div class="gc-dropdown-item gc-highlight-field">
                                                    <i class="fa fa-key"></i> Create new key
                                                </div>
                                                <div class="gc-dropdown-item">
                                                    <i class="fa fa-upload"></i> Upload existing key
                                                </div>
                                            </div>
                                            <div class="gc-key-type-dialog">
                                                <div class="gc-dialog-title">Create private key</div>
                                                <div class="gc-dialog-body">
                                                    <div class="gc-radio-option selected">
                                                        <div class="gc-radio-circle selected"></div>
                                                        <div>
                                                            <strong>JSON</strong>
                                                            <span class="gc-recommended-badge">Recommended</span>
                                                            <p>Downloads a JSON file with the key</p>
                                                        </div>
                                                    </div>
                                                    <div class="gc-radio-option">
                                                        <div class="gc-radio-circle"></div>
                                                        <div>
                                                            <strong>P12</strong>
                                                            <p>For backward compatibility with code using P12 format</p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="gc-mock-btn-row" style="border-top:1px solid #e0e0e0; padding-top:12px;">
                                                    <button class="gc-mock-btn primary" type="button">CREATE</button>
                                                    <button class="gc-mock-btn" type="button">CANCEL</button>
                                                </div>
                                            </div>
                                            <div class="gc-mock-hint" style="margin-top:10px;">
                                                <i class="fa fa-arrow-up gc-arrow-blink"></i>
                                                <span class="gc-highlight-text">اختر JSON ثم اضغط CREATE — سيتم تحميل الملف تلقائياً</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="gc-step-instructions">
                                    <div class="gc-instruction-item">
                                        <span class="gc-inst-num">1</span>
                                        <span>اضغط على حساب الخدمة الذي أنشأته في الخطوة السابقة</span>
                                    </div>
                                    <div class="gc-instruction-item">
                                        <span class="gc-inst-num">2</span>
                                        <span>اذهب إلى تبويب <strong>KEYS</strong></span>
                                    </div>
                                    <div class="gc-instruction-item">
                                        <span class="gc-inst-num">3</span>
                                        <span>اضغط <strong>ADD KEY</strong> → <strong>Create new key</strong></span>
                                    </div>
                                    <div class="gc-instruction-item">
                                        <span class="gc-inst-num">4</span>
                                        <span>اختر <strong>JSON</strong> ثم اضغط <strong>CREATE</strong></span>
                                    </div>
                                    <div class="gc-instruction-item gc-note-item gc-warning-item">
                                        <i class="fa fa-exclamation-triangle"></i>
                                        <span>سيتم تحميل ملف <code>.json</code> تلقائياً — <strong>احفظه في مكان آمن!</strong> لن تتمكن من تحميله مرة أخرى</span>
                                    </div>
                                </div>

                                <div class="gc-step-nav">
                                    <button type="button" class="gc-prev-btn" onclick="goToStep(3)"><i class="fa fa-arrow-right"></i> السابق</button>
                                    <button type="button" class="gc-next-btn" onclick="goToStep(5)">الخطوة التالية <i class="fa fa-arrow-left"></i></button>
                                </div>
                            </div>

                            <!-- ──── Step 5: Read JSON File ──── -->
                            <div class="gc-step" id="gc-step-5">
                                <div class="gc-step-header">
                                    <div class="gc-step-number">5</div>
                                    <div>
                                        <h3>قراءة ملف JSON واستخراج البيانات</h3>
                                        <p>افتح الملف الذي تم تحميله واستخرج الحقول المطلوبة</p>
                                    </div>
                                </div>

                                <!-- JSON File Mockup -->
                                <div class="gc-json-viewer">
                                    <div class="gc-json-toolbar">
                                        <div class="gc-json-filename">
                                            <i class="fa fa-file-code-o"></i> tayseer-erp-438712-a1b2c3d4e5f6.json
                                        </div>
                                        <span class="gc-json-size">2.3 KB</span>
                                    </div>
                                    <div class="gc-json-content" dir="ltr">
                                        <div class="gc-json-line"><span class="gc-json-brace">{</span></div>
                                        <div class="gc-json-line gc-json-dim">  <span class="gc-json-key">"type"</span>: <span class="gc-json-string">"service_account"</span>,</div>
                                        <div class="gc-json-line gc-json-highlight" data-field="project_id">
                                            <div class="gc-json-marker" data-label="1">
                                                <i class="fa fa-arrow-left gc-arrow-blink"></i> انسخ هذا → <strong>Project ID</strong>
                                            </div>
                                            &nbsp;&nbsp;<span class="gc-json-key">"project_id"</span>: <span class="gc-json-string gc-json-copyable" onclick="copyJsonValue(this, 'gc_project_id')">"tayseer-erp-438712"</span>,
                                        </div>
                                        <div class="gc-json-line gc-json-dim">  <span class="gc-json-key">"private_key_id"</span>: <span class="gc-json-string">"a1b2c3d4..."</span>,</div>
                                        <div class="gc-json-line gc-json-highlight" data-field="private_key">
                                            <div class="gc-json-marker" data-label="3">
                                                <i class="fa fa-arrow-left gc-arrow-blink"></i> انسخ هذا → <strong>المفتاح الخاص</strong>
                                            </div>
                                            &nbsp;&nbsp;<span class="gc-json-key">"private_key"</span>: <span class="gc-json-string">"-----BEGIN RSA PRIVATE KEY-----\nMIIE..."</span>,
                                        </div>
                                        <div class="gc-json-line gc-json-highlight" data-field="client_email">
                                            <div class="gc-json-marker" data-label="2">
                                                <i class="fa fa-arrow-left gc-arrow-blink"></i> انسخ هذا → <strong>Client Email</strong>
                                            </div>
                                            &nbsp;&nbsp;<span class="gc-json-key">"client_email"</span>: <span class="gc-json-string gc-json-copyable" onclick="copyJsonValue(this, 'gc_client_email')">"tayseer-vision@tayseer-erp.iam.gserviceaccount.com"</span>,
                                        </div>
                                        <div class="gc-json-line gc-json-dim">  <span class="gc-json-key">"client_id"</span>: <span class="gc-json-string">"1234567890"</span>,</div>
                                        <div class="gc-json-line gc-json-dim">  <span class="gc-json-key">"auth_uri"</span>: <span class="gc-json-string">"https://accounts.google.com/o/oauth2/auth"</span>,</div>
                                        <div class="gc-json-line gc-json-dim">  <span class="gc-json-key">"token_uri"</span>: <span class="gc-json-string">"https://oauth2.googleapis.com/token"</span>,</div>
                                        <div class="gc-json-line gc-json-dim">  <span class="gc-json-key">"..."</span>: <span class="gc-json-string">"..."</span></div>
                                        <div class="gc-json-line"><span class="gc-json-brace">}</span></div>
                                    </div>
                                </div>

                                <!-- Field Mapping -->
                                <div class="gc-field-mapping">
                                    <div class="gc-mapping-title"><i class="fa fa-exchange"></i> ربط الحقول</div>
                                    <div class="gc-mapping-grid">
                                        <div class="gc-mapping-row">
                                            <div class="gc-mapping-from">
                                                <span class="gc-mapping-num">1</span>
                                                <code>"project_id"</code>
                                            </div>
                                            <i class="fa fa-long-arrow-left gc-mapping-arrow"></i>
                                            <div class="gc-mapping-to">معرّف المشروع (Project ID)</div>
                                        </div>
                                        <div class="gc-mapping-row">
                                            <div class="gc-mapping-from">
                                                <span class="gc-mapping-num">2</span>
                                                <code>"client_email"</code>
                                            </div>
                                            <i class="fa fa-long-arrow-left gc-mapping-arrow"></i>
                                            <div class="gc-mapping-to">البريد الإلكتروني (Client Email)</div>
                                        </div>
                                        <div class="gc-mapping-row">
                                            <div class="gc-mapping-from">
                                                <span class="gc-mapping-num">3</span>
                                                <code>"private_key"</code>
                                            </div>
                                            <i class="fa fa-long-arrow-left gc-mapping-arrow"></i>
                                            <div class="gc-mapping-to">المفتاح الخاص (Private Key)</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="gc-step-instructions">
                                    <div class="gc-instruction-item">
                                        <span class="gc-inst-num">1</span>
                                        <span>افتح ملف JSON بأي محرر نصوص (Notepad أو VS Code)</span>
                                    </div>
                                    <div class="gc-instruction-item">
                                        <span class="gc-inst-num">2</span>
                                        <span>انسخ قيمة كل حقل (بدون علامات التنصيص <code>" "</code>) إلى الحقل المقابل في النموذج أدناه</span>
                                    </div>
                                    <div class="gc-instruction-item gc-note-item">
                                        <i class="fa fa-lightbulb-o"></i>
                                        <span>يمكنك أيضاً لصق محتوى ملف JSON كاملاً في المربع أدناه وسيتم استخراج البيانات تلقائياً</span>
                                    </div>
                                </div>

                                <div class="gc-step-nav">
                                    <button type="button" class="gc-prev-btn" onclick="goToStep(4)"><i class="fa fa-arrow-right"></i> السابق</button>
                                    <button type="button" class="gc-next-btn" onclick="goToStep(6)">الخطوة الأخيرة <i class="fa fa-arrow-left"></i></button>
                                </div>
                            </div>

                            <!-- ──── Step 6: Paste & Fill ──── -->
                            <div class="gc-step" id="gc-step-6">
                                <div class="gc-step-header">
                                    <div class="gc-step-number">6</div>
                                    <div>
                                        <h3>لصق البيانات وحفظ الإعدادات</h3>
                                        <p>الصق محتوى ملف JSON أو أدخل الحقول يدوياً</p>
                                    </div>
                                </div>

                                <!-- JSON Upload / Paste -->
                                <div class="gc-auto-parse">
                                    <div class="gc-auto-parse-header">
                                        <i class="fa fa-magic"></i>
                                        <strong>استيراد بيانات الاعتماد من ملف JSON</strong>
                                        <span class="gc-optional-badge">اختياري</span>
                                    </div>
                                    <div class="gc-auto-parse-body">
                                        <div class="json-upload-zone" id="gc-json-dropzone">
                                            <input type="file" id="gc-json-file" accept=".json,application/json" class="json-upload-input">
                                            <div class="json-upload-content">
                                                <i class="fa fa-cloud-upload json-upload-icon"></i>
                                                <p class="json-upload-title">اسحب ملف JSON هنا أو اضغط للاختيار</p>
                                                <p class="json-upload-hint">ملف Service Account الذي تم تنزيله من Google Cloud Console</p>
                                            </div>
                                            <div class="json-upload-success" id="gc-upload-success" style="display:none">
                                                <i class="fa fa-check-circle"></i>
                                                <span id="gc-upload-filename"></span>
                                            </div>
                                        </div>
                                        <div class="json-upload-divider">
                                            <span>أو الصق المحتوى يدوياً</span>
                                        </div>
                                        <textarea id="gc-json-paste" class="gc-json-paste-area" dir="ltr" rows="4" placeholder='الصق محتوى ملف JSON هنا...'></textarea>
                                        <button type="button" class="gc-parse-btn" onclick="parseJsonCredentials()">
                                            <i class="fa fa-magic"></i> استخراج البيانات
                                        </button>
                                        <div id="gc-parse-result" class="gc-parse-result" style="display:none;"></div>
                                    </div>
                                </div>

                                <div class="gc-step-instructions">
                                    <div class="gc-instruction-item gc-note-item gc-success-item">
                                        <i class="fa fa-check-circle"></i>
                                        <span>بعد ملء الحقول أدناه، اضغط <strong>"حفظ الإعدادات"</strong> ثم <strong>"اختبار الاتصال"</strong> للتأكد</span>
                                    </div>
                                </div>

                                <div class="gc-step-nav">
                                    <button type="button" class="gc-prev-btn" onclick="goToStep(5)"><i class="fa fa-arrow-right"></i> السابق</button>
                                    <button type="button" class="gc-next-btn gc-done-btn" onclick="toggleGuide()"><i class="fa fa-check"></i> إغلاق الدليل</button>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- ═══════════ Credentials Card ═══════════ -->
                    <div class="sys-card">
                        <div class="sys-card-header">
                            <div class="sys-card-title">
                                <i class="fa fa-key"></i> بيانات اعتماد حساب الخدمة
                            </div>
                            <span class="sys-card-badge"><i class="fa fa-lock"></i> مشفّرة</span>
                        </div>
                        <div class="sys-card-body">
                            <!-- JSON File Upload -->
                            <div class="json-upload-zone" id="gc-json-dropzone-main">
                                <input type="file" id="gc-json-file-main" accept=".json,application/json" class="json-upload-input">
                                <div class="json-upload-content" id="gc-upload-content-main">
                                    <i class="fa fa-cloud-upload json-upload-icon"></i>
                                    <p class="json-upload-title">ارفع ملف Service Account JSON</p>
                                    <p class="json-upload-hint">اسحب الملف هنا أو اضغط للاختيار — سيتم ملء جميع الحقول تلقائياً</p>
                                </div>
                                <div class="json-upload-success" id="gc-upload-success-main" style="display:none">
                                    <i class="fa fa-check-circle"></i>
                                    <span id="gc-upload-filename-main"></span>
                                </div>
                            </div>
                            <div id="gc-parse-result-main" class="gc-parse-result" style="display:none;margin-top:8px"></div>
                            <div class="json-upload-divider">
                                <span>أو أدخل البيانات يدوياً</span>
                            </div>
                            <div class="sys-form-grid">
                                <div class="sys-field">
                                    <label class="sys-label" for="gc_project_id">
                                        <i class="fa fa-folder-open"></i> معرّف المشروع (Project ID)
                                        <span class="gc-json-field-tag">"project_id"</span>
                                    </label>
                                    <input type="text" class="sys-input" id="gc_project_id" name="gc_project_id"
                                           value="<?= Html::encode($googleCloud['project_id'] ?? '') ?>"
                                           placeholder="my-project-123456"
                                           dir="ltr">
                                </div>

                                <div class="sys-field">
                                    <label class="sys-label" for="gc_client_email">
                                        <i class="fa fa-envelope"></i> البريد الإلكتروني للخدمة (Client Email)
                                        <span class="gc-json-field-tag">"client_email"</span>
                                    </label>
                                    <input type="email" class="sys-input" id="gc_client_email" name="gc_client_email"
                                           value="<?= Html::encode($googleCloud['client_email'] ?? '') ?>"
                                           placeholder="service-account@project.iam.gserviceaccount.com"
                                           dir="ltr">
                                </div>

                                <div class="sys-field sys-field-full">
                                    <label class="sys-label" for="gc_private_key">
                                        <i class="fa fa-shield"></i> المفتاح الخاص (Private Key)
                                        <span class="gc-json-field-tag">"private_key"</span>
                                    </label>
                                    <div class="sys-key-wrapper">
                                        <textarea class="sys-textarea" id="gc_private_key" name="gc_private_key"
                                                  rows="4" dir="ltr"
                                                  placeholder="-----BEGIN RSA PRIVATE KEY-----&#10;MIIEpAIBAAKCAQ...&#10;-----END RSA PRIVATE KEY-----"><?= $googleCloud['has_private_key'] ? '••••••••••' : '' ?></textarea>
                                        <?php if (!empty($googleCloud['has_private_key'])): ?>
                                            <div class="sys-key-notice">
                                                <i class="fa fa-check-circle"></i>
                                                مفتاح خاص محفوظ ومشفّر — اتركه كما هو للاحتفاظ بالمفتاح الحالي، أو الصق مفتاحاً جديداً
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Usage & Limits Card -->
                    <div class="sys-card">
                        <div class="sys-card-header">
                            <div class="sys-card-title">
                                <i class="fa fa-bar-chart"></i> الاستخدام والحدود
                            </div>
                            <span class="sys-card-subtitle"><?= date('F Y') ?></span>
                        </div>
                        <div class="sys-card-body">
                            <div class="sys-stats-grid">
                                <div class="sys-stat-box">
                                    <div class="sys-stat-value"><?= number_format($usageStats['total_requests']) ?></div>
                                    <div class="sys-stat-label">إجمالي الطلبات</div>
                                </div>
                                <div class="sys-stat-box success">
                                    <div class="sys-stat-value"><?= number_format($usageStats['success_count'] ?? 0) ?></div>
                                    <div class="sys-stat-label">ناجحة</div>
                                </div>
                                <div class="sys-stat-box danger">
                                    <div class="sys-stat-value"><?= number_format($usageStats['fail_count'] ?? 0) ?></div>
                                    <div class="sys-stat-label">فاشلة</div>
                                </div>
                                <div class="sys-stat-box info">
                                    <div class="sys-stat-value">$<?= number_format($usageStats['total_cost'], 4) ?></div>
                                    <div class="sys-stat-label">التكلفة</div>
                                </div>
                            </div>

                            <!-- Usage bar -->
                            <div class="sys-usage-bar-wrapper">
                                <div class="sys-usage-bar-header">
                                    <span>الاستخدام الشهري</span>
                                    <span><?= $usageStats['total_requests'] ?> / <?= number_format($usageStats['monthly_limit']) ?></span>
                                </div>
                                <div class="sys-usage-bar">
                                    <div class="sys-usage-bar-fill <?= $usageStats['usage_percent'] > 80 ? 'warning' : '' ?> <?= $usageStats['usage_percent'] > 95 ? 'danger' : '' ?>"
                                         style="width: <?= min($usageStats['usage_percent'], 100) ?>%"></div>
                                </div>
                                <div class="sys-usage-bar-footer">
                                    <span>المتبقي: <?= number_format($usageStats['remaining']) ?> طلب</span>
                                    <span><?= $usageStats['usage_percent'] ?>%</span>
                                </div>
                            </div>

                            <div class="sys-form-grid" style="margin-top: 20px;">
                                <div class="sys-field">
                                    <label class="sys-label" for="gc_monthly_limit">
                                        <i class="fa fa-tachometer"></i> الحد الشهري (عدد الطلبات)
                                    </label>
                                    <input type="number" class="sys-input" id="gc_monthly_limit" name="gc_monthly_limit"
                                           value="<?= Html::encode($googleCloud['monthly_limit'] ?? '1000') ?>"
                                           min="0" step="100">
                                </div>
                                <div class="sys-field">
                                    <label class="sys-label" for="gc_cost_per_request">
                                        <i class="fa fa-usd"></i> التكلفة لكل طلب ($)
                                    </label>
                                    <input type="number" class="sys-input" id="gc_cost_per_request" name="gc_cost_per_request"
                                           value="<?= Html::encode($googleCloud['cost_per_request'] ?? '0.0015') ?>"
                                           min="0" step="0.0001">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Save Button -->
                    <div class="sys-form-actions">
                        <button type="submit" class="sys-save-btn">
                            <i class="fa fa-save"></i> حفظ الإعدادات
                        </button>
                        <button type="button" class="sys-cancel-btn" onclick="window.location.reload()">
                            <i class="fa fa-undo"></i> إلغاء التغييرات
                        </button>
                    </div>
                </form>
                </div><!-- /g-panel-vision -->

                <!-- ── Inner Panel: Maps API ── -->
                <div class="g-inner-panel <?= $innerTab === 'maps' ? 'active' : '' ?>" id="g-panel-maps">
                <form method="post" action="<?= Url::to(['system-settings']) ?>">
                    <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
                    <input type="hidden" name="settings_tab" value="google_maps">

                    <div class="sys-card sys-status-card">
                        <div class="sys-card-header">
                            <div class="sys-card-title">
                                <i class="fa fa-map-marker"></i> حالة التكوين
                            </div>
                            <?php if (!empty($googleMaps['configured'])): ?>
                            <button type="button" class="sys-test-btn" id="btn-test-maps" onclick="testMapsConnection()">
                                <i class="fa fa-plug"></i> اختبار المفتاح
                            </button>
                            <?php endif; ?>
                        </div>
                        <div class="sys-card-body">
                            <div class="sys-connection-status" id="gm-connection-status">
                                <?php if (!empty($googleMaps['configured'])): ?>
                                    <div class="sys-status-indicator configured">
                                        <i class="fa fa-check-circle fa-2x"></i>
                                        <div>
                                            <strong>تم التكوين</strong>
                                            <p>مفتاح Google Maps API محفوظ — اضغط "اختبار المفتاح" للتحقق من صلاحيته</p>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="sys-status-indicator not-configured">
                                        <i class="fa fa-exclamation-circle fa-2x"></i>
                                        <div>
                                            <strong>غير مكوّن</strong>
                                            <p>أدخل مفتاح Google Maps API لتفعيل خريطة Google في صفحة تتبع الموظفين (معالم، محلات، مستشفيات)</p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- مفتاح API -->
                    <div class="sys-card">
                        <div class="sys-card-body">
                            <div class="sys-form-group">
                                <label for="gm_api_key" class="sys-label">
                                    <i class="fa fa-key"></i> مفتاح Google Maps API
                                </label>
                                <input type="text"
                                       id="gm_api_key"
                                       name="gm_api_key"
                                       class="sys-input"
                                       placeholder="<?= !empty($googleMaps['configured']) ? 'اتركه فارغاً للإبقاء على المفتاح الحالي' : 'AIza...' ?>"
                                       value=""
                                       autocomplete="off">
                                <p class="sys-field-hint">احصل على المفتاح من <a href="https://console.cloud.google.com/apis/credentials" target="_blank" rel="noopener">Google Cloud Console</a> — فعّل «Maps JavaScript API» ثم أنشئ مفتاح API.</p>
                            </div>

                            <div class="json-upload-divider">
                                <span>أو استورد من ملف JSON</span>
                            </div>
                            <div class="json-upload-zone json-upload-zone-sm" id="gm-json-dropzone">
                                <input type="file" id="gm-json-file" accept=".json,application/json" class="json-upload-input">
                                <div class="json-upload-content">
                                    <i class="fa fa-cloud-upload json-upload-icon"></i>
                                    <p class="json-upload-title">اسحب ملف JSON يحتوي على API Key</p>
                                    <p class="json-upload-hint">يبحث عن حقل api_key أو key أو maps_api_key</p>
                                </div>
                                <div class="json-upload-success" id="gm-upload-success" style="display:none">
                                    <i class="fa fa-check-circle"></i>
                                    <span id="gm-upload-filename"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- دليل مختصر (قابل للطي) -->
                    <div class="sys-card sys-guide-card">
                        <div class="sys-card-header sys-guide-toggle" onclick="toggleGmGuide()">
                            <div class="sys-card-title">
                                <i class="fa fa-graduation-cap"></i> كيف تحصل على المفتاح؟
                            </div>
                            <div class="sys-guide-toggle-hint">
                                <span id="gm-guide-toggle-text">عرض الدليل</span>
                                <i class="fa fa-chevron-down" id="gm-guide-chevron"></i>
                            </div>
                        </div>
                        <div class="sys-guide-body" id="gm-setup-guide" style="display:none;">
                            <div class="gc-step active">
                                <div class="gc-step-header">
                                    <div class="gc-step-number">1</div>
                                    <div>
                                        <h3>إنشاء مفتاح API</h3>
                                        <p>ادخل إلى <a href="https://console.cloud.google.com/apis/credentials" target="_blank" rel="noopener">APIs & Services → Credentials</a></p>
                                    </div>
                                </div>
                                <div class="gc-step-instructions">
                                    <div class="gc-instruction-item"><span class="gc-inst-num">1</span><span>من القائمة اختر <strong>Create Credentials</strong> → <strong>API key</strong></span></div>
                                    <div class="gc-instruction-item"><span class="gc-inst-num">2</span><span>فعّل <strong>Maps JavaScript API</strong> من قسم Library إن لم يكن مفعّلاً</span></div>
                                    <div class="gc-instruction-item"><span class="gc-inst-num">3</span><span>انسخ المفتاح (يبدأ بـ <code>AIza</code>) والصقه في الحقل أعلاه</span></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- أزرار الحفظ -->
                    <div class="sys-card sys-actions-card">
                        <div class="sys-card-body">
                            <button type="submit" class="sys-save-btn">
                                <i class="fa fa-save"></i> حفظ الإعدادات
                            </button>
                            <button type="button" class="sys-cancel-btn" onclick="window.location.reload()">
                                <i class="fa fa-undo"></i> إلغاء التغييرات
                            </button>
                        </div>
                    </div>
                </form>
                </div><!-- /g-panel-maps -->

                <!-- ── Inner Panel: Costs & Statistics ── -->
                <div class="g-inner-panel" id="g-panel-costs">

                    <!-- ╌╌╌╌╌ Vision API Stats ╌╌╌╌╌ -->
                    <div class="sys-card cost-api-card">
                        <div class="sys-card-header">
                            <div class="sys-card-title">
                                <span class="cost-api-icon" style="background:linear-gradient(135deg,#ea4335,#fbbc04)"><i class="fa fa-eye"></i></span>
                                Vision API — <?= date('F Y') ?>
                            </div>
                            <div class="cost-src-tabs">
                                <button type="button" class="cost-src-tab active" data-cost-group="vision" data-cost-src="local">
                                    <i class="fa fa-database"></i> رصد النظام
                                </button>
                                <button type="button" class="cost-src-tab" data-cost-group="vision" data-cost-src="google">
                                    <i class="fa fa-google"></i> Google Cloud
                                </button>
                            </div>
                        </div>
                        <div class="sys-card-body">
                            <!-- Vision: Local -->
                            <div class="cost-src-panel" data-cost-group="vision" data-cost-panel="local">
                                <div class="sys-stats-grid">
                                    <div class="sys-stat-box">
                                        <div class="sys-stat-value"><?= number_format($usageStats['total_requests']) ?></div>
                                        <div class="sys-stat-label">إجمالي الطلبات</div>
                                    </div>
                                    <div class="sys-stat-box success">
                                        <div class="sys-stat-value"><?= number_format($usageStats['success_count'] ?? 0) ?></div>
                                        <div class="sys-stat-label">ناجحة</div>
                                    </div>
                                    <div class="sys-stat-box danger">
                                        <div class="sys-stat-value"><?= number_format($usageStats['fail_count'] ?? 0) ?></div>
                                        <div class="sys-stat-label">فاشلة</div>
                                    </div>
                                    <div class="sys-stat-box info">
                                        <div class="sys-stat-value">$<?= number_format($usageStats['total_cost'], 4) ?></div>
                                        <div class="sys-stat-label">التكلفة (تقدير)</div>
                                    </div>
                                </div>
                                <div class="sys-usage-bar-wrapper">
                                    <div class="sys-usage-bar-header">
                                        <span>الاستخدام الشهري</span>
                                        <span><?= $usageStats['total_requests'] ?> / <?= number_format($usageStats['monthly_limit']) ?></span>
                                    </div>
                                    <div class="sys-usage-bar">
                                        <div class="sys-usage-bar-fill <?= $usageStats['usage_percent'] > 80 ? 'warning' : '' ?> <?= $usageStats['usage_percent'] > 95 ? 'danger' : '' ?>"
                                             style="width: <?= min($usageStats['usage_percent'], 100) ?>%"></div>
                                    </div>
                                    <div class="sys-usage-bar-footer">
                                        <span>المتبقي: <?= number_format($usageStats['remaining']) ?> طلب مجاني</span>
                                        <span><?= $usageStats['usage_percent'] ?>%</span>
                                    </div>
                                </div>
                                <div class="cost-free-tier-note">
                                    <i class="fa fa-gift"></i> Google تمنح <strong>1,000 طلب/شهر مجاناً</strong> لـ Vision API — بعدها $1.50 لكل 1,000 طلب
                                </div>
                            </div>
                            <!-- Vision: Google Cloud -->
                            <div class="cost-src-panel" data-cost-group="vision" data-cost-panel="google" style="display:none">
                                <div class="sys-stats-grid">
                                    <div class="sys-stat-box">
                                        <div class="sys-stat-value" id="v-gc-total">—</div>
                                        <div class="sys-stat-label">طلبات Google</div>
                                    </div>
                                    <div class="sys-stat-box success">
                                        <div class="sys-stat-value" id="v-gc-free">—</div>
                                        <div class="sys-stat-label">مجانية (Free Tier)</div>
                                    </div>
                                    <div class="sys-stat-box" style="border-right-color:#fbbc04">
                                        <div class="sys-stat-value" id="v-gc-billable">—</div>
                                        <div class="sys-stat-label">قابلة للفوترة</div>
                                    </div>
                                    <div class="sys-stat-box info">
                                        <div class="sys-stat-value" id="v-gc-cost">—</div>
                                        <div class="sys-stat-label">التكلفة الفعلية</div>
                                    </div>
                                </div>
                                <div class="sys-usage-bar-wrapper">
                                    <div class="sys-usage-bar-header">
                                        <span>Vision API — Free Tier</span>
                                        <span id="v-gc-bar-label">— / 1,000</span>
                                    </div>
                                    <div class="sys-usage-bar">
                                        <div class="sys-usage-bar-fill" id="v-gc-bar" style="width:0%"></div>
                                    </div>
                                    <div class="sys-usage-bar-footer">
                                        <span id="v-gc-remaining-label">المتبقي: —</span>
                                        <span id="v-gc-pct">0%</span>
                                    </div>
                                </div>
                                <div id="v-gc-status" class="cost-gc-status">
                                    <i class="fa fa-spinner fa-spin"></i> جاري الاتصال بـ Google Cloud...
                                </div>
                                <div id="v-gc-breakdown"></div>
                            </div>
                        </div>
                    </div>

                    <!-- ╌╌╌╌╌ Maps API Stats ╌╌╌╌╌ -->
                    <div class="sys-card cost-api-card">
                        <div class="sys-card-header">
                            <div class="sys-card-title">
                                <span class="cost-api-icon" style="background:linear-gradient(135deg,#34a853,#4285f4)"><i class="fa fa-map"></i></span>
                                Maps API — <?= date('F Y') ?>
                            </div>
                            <div class="cost-src-tabs">
                                <button type="button" class="cost-src-tab active" data-cost-group="maps" data-cost-src="info">
                                    <i class="fa fa-info-circle"></i> معلومات
                                </button>
                                <button type="button" class="cost-src-tab" data-cost-group="maps" data-cost-src="google">
                                    <i class="fa fa-google"></i> Google Cloud
                                </button>
                            </div>
                        </div>
                        <div class="sys-card-body">
                            <!-- Maps: Info -->
                            <div class="cost-src-panel" data-cost-group="maps" data-cost-panel="info">
                                <div class="cost-maps-info">
                                    <div class="cost-maps-services">
                                        <h4><i class="fa fa-list"></i> الخدمات المستخدمة</h4>
                                        <div class="cost-service-row">
                                            <span class="cost-service-name"><i class="fa fa-map"></i> Maps JavaScript API</span>
                                            <span class="cost-service-free">28,000 تحميل/شهر مجاناً</span>
                                        </div>
                                        <div class="cost-service-row">
                                            <span class="cost-service-name"><i class="fa fa-search"></i> Places API</span>
                                            <span class="cost-service-free">بحسب الجلسات — $0 أول $200</span>
                                        </div>
                                        <div class="cost-service-row">
                                            <span class="cost-service-name"><i class="fa fa-map-marker"></i> Geocoding API</span>
                                            <span class="cost-service-free">40,000 طلب/شهر — $5 لكل 1,000 إضافي</span>
                                        </div>
                                    </div>
                                    <div class="cost-free-tier-note">
                                        <i class="fa fa-gift"></i> Google تمنح <strong>رصيد $200/شهر مجاناً</strong> لجميع خدمات Maps — يكفي غالبية الاستخدامات
                                    </div>
                                </div>
                            </div>
                            <!-- Maps: Google Cloud -->
                            <div class="cost-src-panel" data-cost-group="maps" data-cost-panel="google" style="display:none">
                                <div class="sys-stats-grid">
                                    <div class="sys-stat-box">
                                        <div class="sys-stat-value" id="m-gc-total">—</div>
                                        <div class="sys-stat-label">إجمالي الطلبات</div>
                                    </div>
                                    <div class="sys-stat-box success">
                                        <div class="sys-stat-value" id="m-gc-free-credit">$200</div>
                                        <div class="sys-stat-label">الرصيد المجاني/شهر</div>
                                    </div>
                                    <div class="sys-stat-box" style="border-right-color:#fbbc04">
                                        <div class="sys-stat-value" id="m-gc-used">—</div>
                                        <div class="sys-stat-label">المستهلك</div>
                                    </div>
                                    <div class="sys-stat-box info">
                                        <div class="sys-stat-value" id="m-gc-remaining">—</div>
                                        <div class="sys-stat-label">المتبقي من الرصيد</div>
                                    </div>
                                </div>
                                <div class="sys-usage-bar-wrapper">
                                    <div class="sys-usage-bar-header">
                                        <span>استهلاك الرصيد المجاني ($200)</span>
                                        <span id="m-gc-bar-label">$0 / $200</span>
                                    </div>
                                    <div class="sys-usage-bar">
                                        <div class="sys-usage-bar-fill" id="m-gc-bar" style="width:0%"></div>
                                    </div>
                                </div>
                                <div id="m-gc-status" class="cost-gc-status">
                                    <i class="fa fa-spinner fa-spin"></i> جاري الاتصال بـ Google Cloud...
                                </div>
                                <div id="m-gc-breakdown"></div>
                            </div>
                        </div>
                    </div>

                </div><!-- /g-panel-costs -->
            </div>

            <!-- ═══════════ Messaging Services Tab ═══════════ -->
            <div class="sys-tab-content <?= $activeTab === 'messaging' ? 'active' : '' ?>" id="tab-messaging">

                <?php $msgInnerTab = 'sms'; ?>
                <!-- Inner Tabs Bar -->
                <div class="g-inner-tabs">
                    <button type="button" class="g-inner-tab active" data-inner="sms">
                        <i class="fa fa-mobile"></i> SMS API
                    </button>
                    <button type="button" class="g-inner-tab" data-inner="whatsapp">
                        <i class="fa fa-whatsapp"></i> WhatsApp Business
                    </button>
                </div>

                <!-- ════════════════════════════════════════════════ -->
                <!--  SMS API Panel                                  -->
                <!-- ════════════════════════════════════════════════ -->
                <div class="g-inner-panel active" id="g-panel-sms">
                <form method="post" action="<?= Url::to(['system-settings']) ?>" id="sms-settings-form">
                    <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
                    <input type="hidden" name="settings_tab" value="sms_api">

                    <!-- Connection Status -->
                    <div class="sys-card sys-status-card">
                        <div class="sys-card-header">
                            <div class="sys-card-title">
                                <i class="fa fa-signal"></i> حالة الاتصال
                            </div>
                            <button type="button" class="sys-test-btn" id="btn-test-sms" onclick="testSmsConnection()">
                                <i class="fa fa-plug"></i> اختبار الاتصال
                            </button>
                        </div>
                        <div class="sys-card-body">
                            <div class="sys-connection-status" id="sms-connection-status">
                                <?php if (!empty($smsSettings['enabled']) && $smsSettings['enabled'] === '1' && !empty($smsSettings['has_api_key'])): ?>
                                    <div class="sys-status-indicator configured">
                                        <i class="fa fa-check-circle fa-2x"></i>
                                        <div>
                                            <strong>تم التكوين</strong>
                                            <p>بيانات SMS API محفوظة — اضغط "اختبار الاتصال" للتحقق</p>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="sys-status-indicator not-configured">
                                        <i class="fa fa-exclamation-circle fa-2x"></i>
                                        <div>
                                            <strong>غير مكوّن</strong>
                                            <p>أدخل بيانات مزوّد خدمة الرسائل النصية لتفعيل إرسال SMS</p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Toggle Enable -->
                    <div class="sys-card">
                        <div class="sys-card-body">
                            <div class="sys-toggle-row">
                                <div class="sys-toggle-info">
                                    <i class="fa fa-power-off sys-toggle-icon"></i>
                                    <div>
                                        <strong>تفعيل خدمة الرسائل النصية SMS</strong>
                                        <p>عند التفعيل، سيتم إرسال رسائل SMS للعملاء (تذكيرات، إشعارات، تأكيدات)</p>
                                    </div>
                                </div>
                                <label class="sys-switch">
                                    <input type="hidden" name="sms_enabled" value="0">
                                    <input type="checkbox" name="sms_enabled" value="1" <?= (!empty($smsSettings['enabled']) && $smsSettings['enabled'] === '1') ? 'checked' : '' ?>>
                                    <span class="sys-switch-slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- ═══════════ Setup Guide (Collapsible) ═══════════ -->
                    <div class="sys-card sys-guide-card">
                        <div class="sys-card-header sys-guide-toggle" onclick="toggleSmsGuide()">
                            <div class="sys-card-title">
                                <i class="fa fa-graduation-cap"></i> دليل الإعداد خطوة بخطوة
                            </div>
                            <div class="sys-guide-toggle-hint">
                                <span id="sms-guide-toggle-text">عرض الدليل</span>
                                <i class="fa fa-chevron-down" id="sms-guide-chevron"></i>
                            </div>
                        </div>
                        <div class="sys-guide-body" id="sms-setup-guide" style="display:none;">

                            <!-- Step Progress -->
                            <div class="gc-steps-progress">
                                <div class="gc-step-dot active" data-step="s1"><span>1</span></div>
                                <div class="gc-step-line"></div>
                                <div class="gc-step-dot" data-step="s2"><span>2</span></div>
                                <div class="gc-step-line"></div>
                                <div class="gc-step-dot" data-step="s3"><span>3</span></div>
                                <div class="gc-step-line"></div>
                                <div class="gc-step-dot" data-step="s4"><span>4</span></div>
                            </div>

                            <!-- Step 1: Choose Provider -->
                            <div class="gc-step active" id="sms-step-1">
                                <div class="gc-step-header">
                                    <div class="gc-step-number">1</div>
                                    <div>
                                        <h3>اختيار مزوّد خدمة SMS</h3>
                                        <p>اختر مزوّد خدمة رسائل نصية يدعم منطقتك</p>
                                    </div>
                                </div>

                                <div class="gc-step-instructions">
                                    <div class="gc-instruction-item">
                                        <span class="gc-inst-num">1</span>
                                        <span>اختر أحد المزوّدين المعروفين مثل:
                                            <strong>Twilio</strong> · <strong>Vonage (Nexmo)</strong> · <strong>Unifonic</strong> · <strong>Gateway.sa</strong> · <strong>Taqnyat</strong>
                                        </span>
                                    </div>
                                    <div class="gc-instruction-item">
                                        <span class="gc-inst-num">2</span>
                                        <span>أنشئ حساباً جديداً في موقع المزوّد</span>
                                    </div>
                                    <div class="gc-instruction-item">
                                        <span class="gc-inst-num">3</span>
                                        <span>فعّل حسابك واشحن رصيداً (بعض المزوّدين يوفرون رصيداً تجريبياً مجانياً)</span>
                                    </div>
                                    <div class="gc-instruction-item gc-note-item">
                                        <i class="fa fa-info-circle"></i>
                                        <span>المزوّدون المحليون مثل <strong>Unifonic</strong> و <strong>Taqnyat</strong> يوفّرون أسعاراً أفضل للرسائل داخل المملكة العربية السعودية والأردن</span>
                                    </div>
                                </div>

                                <div class="gc-step-nav">
                                    <div></div>
                                    <button type="button" class="gc-next-btn" onclick="goToSmsStep(2)">الخطوة التالية <i class="fa fa-arrow-left"></i></button>
                                </div>
                            </div>

                            <!-- Step 2: Get API Credentials -->
                            <div class="gc-step" id="sms-step-2">
                                <div class="gc-step-header">
                                    <div class="gc-step-number">2</div>
                                    <div>
                                        <h3>الحصول على بيانات API</h3>
                                        <p>استخرج بيانات الاعتماد من لوحة تحكم المزوّد</p>
                                    </div>
                                </div>

                                <div class="gc-step-instructions">
                                    <div class="gc-instruction-item">
                                        <span class="gc-inst-num">1</span>
                                        <span>سجّل الدخول إلى لوحة تحكم المزوّد</span>
                                    </div>
                                    <div class="gc-instruction-item">
                                        <span class="gc-inst-num">2</span>
                                        <span>اذهب إلى قسم <strong>API Settings</strong> أو <strong>Developer</strong> أو <strong>Integrations</strong></span>
                                    </div>
                                    <div class="gc-instruction-item">
                                        <span class="gc-inst-num">3</span>
                                        <span>انسخ <strong>API Key</strong> (أو Account SID) و <strong>API Secret</strong> (أو Auth Token)</span>
                                    </div>
                                    <div class="gc-instruction-item">
                                        <span class="gc-inst-num">4</span>
                                        <span>انسخ <strong>رابط API</strong> (API URL/Endpoint) — عادةً يكون بالشكل: <code>https://api.provider.com/v1/messages</code></span>
                                    </div>
                                </div>

                                <div class="gc-step-nav">
                                    <button type="button" class="gc-prev-btn" onclick="goToSmsStep(1)"><i class="fa fa-arrow-right"></i> السابق</button>
                                    <button type="button" class="gc-next-btn" onclick="goToSmsStep(3)">الخطوة التالية <i class="fa fa-arrow-left"></i></button>
                                </div>
                            </div>

                            <!-- Step 3: Configure Sender ID -->
                            <div class="gc-step" id="sms-step-3">
                                <div class="gc-step-header">
                                    <div class="gc-step-number">3</div>
                                    <div>
                                        <h3>إعداد اسم المرسل (Sender ID)</h3>
                                        <p>تسجيل اسم المرسل الذي سيظهر للعملاء عند استلام الرسالة</p>
                                    </div>
                                </div>

                                <div class="gc-step-instructions">
                                    <div class="gc-instruction-item">
                                        <span class="gc-inst-num">1</span>
                                        <span>من لوحة تحكم المزوّد، اذهب إلى <strong>Sender IDs</strong> أو <strong>اسم المرسل</strong></span>
                                    </div>
                                    <div class="gc-instruction-item">
                                        <span class="gc-inst-num">2</span>
                                        <span>سجّل اسم المرسل (مثلاً: <code>TAYSEER</code> أو اسم شركتك)</span>
                                    </div>
                                    <div class="gc-instruction-item">
                                        <span class="gc-inst-num">3</span>
                                        <span>انتظر الموافقة (قد تستغرق 1-3 أيام عمل في بعض الدول)</span>
                                    </div>
                                    <div class="gc-instruction-item gc-note-item gc-warning-item">
                                        <i class="fa fa-exclamation-triangle"></i>
                                        <span>في السعودية والأردن، يجب تسجيل Sender ID رسمياً عبر المزوّد — الأسماء غير المسجّلة قد تُحجب</span>
                                    </div>
                                </div>

                                <div class="gc-step-nav">
                                    <button type="button" class="gc-prev-btn" onclick="goToSmsStep(2)"><i class="fa fa-arrow-right"></i> السابق</button>
                                    <button type="button" class="gc-next-btn" onclick="goToSmsStep(4)">الخطوة الأخيرة <i class="fa fa-arrow-left"></i></button>
                                </div>
                            </div>

                            <!-- Step 4: Fill Form -->
                            <div class="gc-step" id="sms-step-4">
                                <div class="gc-step-header">
                                    <div class="gc-step-number">4</div>
                                    <div>
                                        <h3>إدخال البيانات وحفظ الإعدادات</h3>
                                        <p>أدخل جميع البيانات التي حصلت عليها في النموذج أدناه</p>
                                    </div>
                                </div>

                                <div class="gc-step-instructions">
                                    <div class="gc-instruction-item gc-note-item gc-success-item">
                                        <i class="fa fa-check-circle"></i>
                                        <span>بعد ملء الحقول أدناه، اضغط <strong>"حفظ الإعدادات"</strong> ثم <strong>"اختبار الاتصال"</strong> للتأكد من صحة البيانات</span>
                                    </div>
                                </div>

                                <div class="gc-step-nav">
                                    <button type="button" class="gc-prev-btn" onclick="goToSmsStep(3)"><i class="fa fa-arrow-right"></i> السابق</button>
                                    <button type="button" class="gc-next-btn gc-done-btn" onclick="toggleSmsGuide()"><i class="fa fa-check"></i> إغلاق الدليل</button>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- ═══════════ Credentials Card ═══════════ -->
                    <div class="sys-card">
                        <div class="sys-card-header">
                            <div class="sys-card-title">
                                <i class="fa fa-key"></i> بيانات اعتماد SMS API
                            </div>
                            <span class="sys-card-badge"><i class="fa fa-lock"></i> مشفّرة</span>
                        </div>
                        <div class="sys-card-body">
                            <div class="sys-form-grid">
                                <div class="sys-field">
                                    <label class="sys-label" for="sms_provider">
                                        <i class="fa fa-building"></i> مزوّد الخدمة (Provider)
                                    </label>
                                    <select class="sys-input" id="sms_provider" name="sms_provider">
                                        <option value="">— اختر المزوّد —</option>
                                        <option value="twilio" <?= ($smsSettings['provider'] ?? '') === 'twilio' ? 'selected' : '' ?>>Twilio</option>
                                        <option value="vonage" <?= ($smsSettings['provider'] ?? '') === 'vonage' ? 'selected' : '' ?>>Vonage (Nexmo)</option>
                                        <option value="unifonic" <?= ($smsSettings['provider'] ?? '') === 'unifonic' ? 'selected' : '' ?>>Unifonic</option>
                                        <option value="taqnyat" <?= ($smsSettings['provider'] ?? '') === 'taqnyat' ? 'selected' : '' ?>>Taqnyat (تقنيات)</option>
                                        <option value="gateway_sa" <?= ($smsSettings['provider'] ?? '') === 'gateway_sa' ? 'selected' : '' ?>>Gateway.sa</option>
                                        <option value="msegat" <?= ($smsSettings['provider'] ?? '') === 'msegat' ? 'selected' : '' ?>>Msegat</option>
                                        <option value="other" <?= ($smsSettings['provider'] ?? '') === 'other' ? 'selected' : '' ?>>مزوّد آخر</option>
                                    </select>
                                </div>

                                <div class="sys-field">
                                    <label class="sys-label" for="sms_sender_id">
                                        <i class="fa fa-id-card"></i> اسم المرسل (Sender ID)
                                    </label>
                                    <input type="text" class="sys-input" id="sms_sender_id" name="sms_sender_id"
                                           value="<?= Html::encode($smsSettings['sender_id'] ?? '') ?>"
                                           placeholder="TAYSEER"
                                           dir="ltr">
                                </div>

                                <div class="sys-field sys-field-full">
                                    <label class="sys-label" for="sms_api_url">
                                        <i class="fa fa-link"></i> رابط API (Endpoint URL)
                                    </label>
                                    <input type="url" class="sys-input" id="sms_api_url" name="sms_api_url"
                                           value="<?= Html::encode($smsSettings['api_url'] ?? '') ?>"
                                           placeholder="https://api.provider.com/v1/messages"
                                           dir="ltr">
                                    <p class="sys-field-hint">رابط إرسال الرسائل الأساسي الخاص بالمزوّد</p>
                                </div>

                                <div class="sys-field">
                                    <label class="sys-label" for="sms_api_key">
                                        <i class="fa fa-key"></i> مفتاح API (API Key / Account SID)
                                    </label>
                                    <input type="text" class="sys-input" id="sms_api_key" name="sms_api_key"
                                           value="<?= !empty($smsSettings['has_api_key']) ? '••••••••••' : '' ?>"
                                           placeholder="أدخل مفتاح API"
                                           dir="ltr">
                                    <?php if (!empty($smsSettings['has_api_key'])): ?>
                                        <div class="sys-key-notice">
                                            <i class="fa fa-check-circle"></i>
                                            مفتاح محفوظ ومشفّر — اتركه كما هو للاحتفاظ بالمفتاح الحالي
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="sys-field">
                                    <label class="sys-label" for="sms_api_secret">
                                        <i class="fa fa-shield"></i> كلمة سر API (API Secret / Auth Token)
                                    </label>
                                    <input type="text" class="sys-input" id="sms_api_secret" name="sms_api_secret"
                                           value="<?= !empty($smsSettings['has_api_secret']) ? '••••••••••' : '' ?>"
                                           placeholder="أدخل كلمة سر API"
                                           dir="ltr">
                                    <?php if (!empty($smsSettings['has_api_secret'])): ?>
                                        <div class="sys-key-notice">
                                            <i class="fa fa-check-circle"></i>
                                            كلمة السر محفوظة ومشفّرة
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="sys-field">
                                    <label class="sys-label" for="sms_username">
                                        <i class="fa fa-user"></i> اسم المستخدم (اختياري)
                                    </label>
                                    <input type="text" class="sys-input" id="sms_username" name="sms_username"
                                           value="<?= Html::encode($smsSettings['username'] ?? '') ?>"
                                           placeholder="اسم المستخدم لدى المزوّد"
                                           dir="ltr">
                                    <p class="sys-field-hint">بعض المزوّدين يطلبون اسم مستخدم بالإضافة إلى مفتاح API</p>
                                </div>

                                <div class="sys-field">
                                    <label class="sys-label" for="sms_password">
                                        <i class="fa fa-lock"></i> كلمة المرور (اختياري)
                                    </label>
                                    <input type="password" class="sys-input" id="sms_password" name="sms_password"
                                           value="<?= !empty($smsSettings['has_password']) ? '••••••••••' : '' ?>"
                                           placeholder="كلمة المرور لدى المزوّد"
                                           dir="ltr">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Save Button -->
                    <div class="sys-form-actions">
                        <button type="submit" class="sys-save-btn">
                            <i class="fa fa-save"></i> حفظ الإعدادات
                        </button>
                        <button type="button" class="sys-cancel-btn" onclick="window.location.reload()">
                            <i class="fa fa-undo"></i> إلغاء التغييرات
                        </button>
                    </div>
                </form>
                </div><!-- /g-panel-sms -->

                <!-- ════════════════════════════════════════════════ -->
                <!--  WhatsApp Business API Panel                    -->
                <!-- ════════════════════════════════════════════════ -->
                <div class="g-inner-panel" id="g-panel-whatsapp">
                <form method="post" action="<?= Url::to(['system-settings']) ?>" id="wa-settings-form">
                    <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
                    <input type="hidden" name="settings_tab" value="whatsapp_api">

                    <!-- Connection Status -->
                    <div class="sys-card sys-status-card">
                        <div class="sys-card-header">
                            <div class="sys-card-title">
                                <i class="fa fa-signal"></i> حالة الاتصال
                            </div>
                            <button type="button" class="sys-test-btn" id="btn-test-whatsapp" onclick="testWhatsappConnection()">
                                <i class="fa fa-plug"></i> اختبار الاتصال
                            </button>
                        </div>
                        <div class="sys-card-body">
                            <div class="sys-connection-status" id="wa-connection-status">
                                <?php if (!empty($waSettings['enabled']) && $waSettings['enabled'] === '1' && !empty($waSettings['has_access_token'])): ?>
                                    <div class="sys-status-indicator configured">
                                        <i class="fa fa-check-circle fa-2x"></i>
                                        <div>
                                            <strong>تم التكوين</strong>
                                            <p>بيانات WhatsApp Business API محفوظة — اضغط "اختبار الاتصال" للتحقق</p>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="sys-status-indicator not-configured">
                                        <i class="fa fa-exclamation-circle fa-2x"></i>
                                        <div>
                                            <strong>غير مكوّن</strong>
                                            <p>أدخل بيانات Meta WhatsApp Business API لتفعيل إرسال رسائل WhatsApp</p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Toggle Enable -->
                    <div class="sys-card">
                        <div class="sys-card-body">
                            <div class="sys-toggle-row">
                                <div class="sys-toggle-info">
                                    <i class="fa fa-power-off sys-toggle-icon"></i>
                                    <div>
                                        <strong>تفعيل خدمة WhatsApp Business API</strong>
                                        <p>عند التفعيل، سيتم إرسال رسائل WhatsApp للعملاء عبر واجهة Meta الرسمية</p>
                                    </div>
                                </div>
                                <label class="sys-switch">
                                    <input type="hidden" name="wa_enabled" value="0">
                                    <input type="checkbox" name="wa_enabled" value="1" <?= (!empty($waSettings['enabled']) && $waSettings['enabled'] === '1') ? 'checked' : '' ?>>
                                    <span class="sys-switch-slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- ═══════════ Setup Guide (Collapsible) ═══════════ -->
                    <div class="sys-card sys-guide-card">
                        <div class="sys-card-header sys-guide-toggle" onclick="toggleWaGuide()">
                            <div class="sys-card-title">
                                <i class="fa fa-graduation-cap"></i> دليل الإعداد خطوة بخطوة
                            </div>
                            <div class="sys-guide-toggle-hint">
                                <span id="wa-guide-toggle-text">عرض الدليل</span>
                                <i class="fa fa-chevron-down" id="wa-guide-chevron"></i>
                            </div>
                        </div>
                        <div class="sys-guide-body" id="wa-setup-guide" style="display:none;">

                            <!-- Step Progress -->
                            <div class="gc-steps-progress">
                                <div class="gc-step-dot active" data-step="w1"><span>1</span></div>
                                <div class="gc-step-line"></div>
                                <div class="gc-step-dot" data-step="w2"><span>2</span></div>
                                <div class="gc-step-line"></div>
                                <div class="gc-step-dot" data-step="w3"><span>3</span></div>
                                <div class="gc-step-line"></div>
                                <div class="gc-step-dot" data-step="w4"><span>4</span></div>
                                <div class="gc-step-line"></div>
                                <div class="gc-step-dot" data-step="w5"><span>5</span></div>
                                <div class="gc-step-line"></div>
                                <div class="gc-step-dot" data-step="w6"><span>6</span></div>
                            </div>

                            <!-- Step 1: Create Meta Business Account -->
                            <div class="gc-step active" id="wa-step-1">
                                <div class="gc-step-header">
                                    <div class="gc-step-number">1</div>
                                    <div>
                                        <h3>إنشاء حساب Meta Business</h3>
                                        <p>تحتاج حساب أعمال في Meta (Facebook) لاستخدام WhatsApp Business API</p>
                                    </div>
                                </div>

                                <div class="gc-step-instructions">
                                    <div class="gc-instruction-item">
                                        <span class="gc-inst-num">1</span>
                                        <span>اذهب إلى <a href="https://business.facebook.com/" target="_blank" class="gc-link">business.facebook.com</a></span>
                                    </div>
                                    <div class="gc-instruction-item">
                                        <span class="gc-inst-num">2</span>
                                        <span>أنشئ حساب أعمال جديد أو استخدم حساباً موجوداً</span>
                                    </div>
                                    <div class="gc-instruction-item">
                                        <span class="gc-inst-num">3</span>
                                        <span>أكمل عملية التحقق من النشاط التجاري (Business Verification) — قد تستغرق 1-3 أيام</span>
                                    </div>
                                    <div class="gc-instruction-item gc-note-item">
                                        <i class="fa fa-info-circle"></i>
                                        <span>التحقق مطلوب لإرسال رسائل لأرقام غير محفوظة وللحصول على حصة إرسال أعلى</span>
                                    </div>
                                </div>

                                <div class="gc-step-nav">
                                    <div></div>
                                    <button type="button" class="gc-next-btn" onclick="goToWaStep(2)">الخطوة التالية <i class="fa fa-arrow-left"></i></button>
                                </div>
                            </div>

                            <!-- Step 2: Create App in Meta Developers -->
                            <div class="gc-step" id="wa-step-2">
                                <div class="gc-step-header">
                                    <div class="gc-step-number">2</div>
                                    <div>
                                        <h3>إنشاء تطبيق في Meta for Developers</h3>
                                        <p>أنشئ تطبيقاً من نوع "Business" لربط WhatsApp API</p>
                                    </div>
                                </div>

                                <div class="gc-step-instructions">
                                    <div class="gc-instruction-item">
                                        <span class="gc-inst-num">1</span>
                                        <span>اذهب إلى <a href="https://developers.facebook.com/apps/" target="_blank" class="gc-link">developers.facebook.com/apps</a></span>
                                    </div>
                                    <div class="gc-instruction-item">
                                        <span class="gc-inst-num">2</span>
                                        <span>اضغط <strong>Create App</strong> → اختر نوع <strong>"Business"</strong></span>
                                    </div>
                                    <div class="gc-instruction-item">
                                        <span class="gc-inst-num">3</span>
                                        <span>أدخل اسم التطبيق (مثلاً: <code>Tayseer WhatsApp</code>) واربطه بحساب الأعمال</span>
                                    </div>
                                    <div class="gc-instruction-item">
                                        <span class="gc-inst-num">4</span>
                                        <span>من لوحة التطبيق، اضغط <strong>Add Product</strong> → اختر <strong>WhatsApp</strong> → <strong>Set Up</strong></span>
                                    </div>
                                </div>

                                <div class="gc-step-nav">
                                    <button type="button" class="gc-prev-btn" onclick="goToWaStep(1)"><i class="fa fa-arrow-right"></i> السابق</button>
                                    <button type="button" class="gc-next-btn" onclick="goToWaStep(3)">الخطوة التالية <i class="fa fa-arrow-left"></i></button>
                                </div>
                            </div>

                            <!-- Step 3: Add Phone Number -->
                            <div class="gc-step" id="wa-step-3">
                                <div class="gc-step-header">
                                    <div class="gc-step-number">3</div>
                                    <div>
                                        <h3>إضافة رقم هاتف</h3>
                                        <p>ربط رقم هاتف بحساب WhatsApp Business</p>
                                    </div>
                                </div>

                                <div class="gc-step-instructions">
                                    <div class="gc-instruction-item">
                                        <span class="gc-inst-num">1</span>
                                        <span>من لوحة التطبيق → <strong>WhatsApp</strong> → <strong>Getting Started</strong></span>
                                    </div>
                                    <div class="gc-instruction-item">
                                        <span class="gc-inst-num">2</span>
                                        <span>اضغط <strong>Add Phone Number</strong> وأدخل رقم الهاتف الذي تريد إرسال الرسائل منه</span>
                                    </div>
                                    <div class="gc-instruction-item">
                                        <span class="gc-inst-num">3</span>
                                        <span>أكمل عملية التحقق عبر SMS أو مكالمة هاتفية</span>
                                    </div>
                                    <div class="gc-instruction-item gc-note-item gc-warning-item">
                                        <i class="fa fa-exclamation-triangle"></i>
                                        <span>الرقم يجب أن <strong>لا يكون مربوطاً</strong> بتطبيق WhatsApp عادي أو WhatsApp Business على الهاتف</span>
                                    </div>
                                </div>

                                <div class="gc-step-nav">
                                    <button type="button" class="gc-prev-btn" onclick="goToWaStep(2)"><i class="fa fa-arrow-right"></i> السابق</button>
                                    <button type="button" class="gc-next-btn" onclick="goToWaStep(4)">الخطوة التالية <i class="fa fa-arrow-left"></i></button>
                                </div>
                            </div>

                            <!-- Step 4: Get Access Token -->
                            <div class="gc-step" id="wa-step-4">
                                <div class="gc-step-header">
                                    <div class="gc-step-number">4</div>
                                    <div>
                                        <h3>الحصول على Access Token</h3>
                                        <p>إنشاء رمز وصول دائم (Permanent Token) للتطبيق</p>
                                    </div>
                                </div>

                                <div class="gc-step-instructions">
                                    <div class="gc-instruction-item">
                                        <span class="gc-inst-num">1</span>
                                        <span>من لوحة التطبيق → <strong>WhatsApp</strong> → <strong>API Setup</strong></span>
                                    </div>
                                    <div class="gc-instruction-item">
                                        <span class="gc-inst-num">2</span>
                                        <span>ستجد <strong>Temporary Access Token</strong> — هذا صالح لـ 24 ساعة فقط (للاختبار)</span>
                                    </div>
                                    <div class="gc-instruction-item">
                                        <span class="gc-inst-num">3</span>
                                        <span>للحصول على <strong>Permanent Token</strong>:
                                            اذهب إلى <a href="https://business.facebook.com/settings/system-users" target="_blank" class="gc-link">Business Settings → System Users</a>
                                        </span>
                                    </div>
                                    <div class="gc-instruction-item">
                                        <span class="gc-inst-num">4</span>
                                        <span>أنشئ <strong>System User</strong> جديد → اختر صلاحية <strong>Admin</strong></span>
                                    </div>
                                    <div class="gc-instruction-item">
                                        <span class="gc-inst-num">5</span>
                                        <span>اضغط <strong>Generate New Token</strong> → اختر التطبيق → فعّل صلاحيات: <code>whatsapp_business_management</code> و <code>whatsapp_business_messaging</code></span>
                                    </div>
                                    <div class="gc-instruction-item gc-note-item gc-warning-item">
                                        <i class="fa fa-exclamation-triangle"></i>
                                        <span>انسخ Token فوراً — <strong>لن يظهر مرة أخرى!</strong></span>
                                    </div>
                                </div>

                                <div class="gc-step-nav">
                                    <button type="button" class="gc-prev-btn" onclick="goToWaStep(3)"><i class="fa fa-arrow-right"></i> السابق</button>
                                    <button type="button" class="gc-next-btn" onclick="goToWaStep(5)">الخطوة التالية <i class="fa fa-arrow-left"></i></button>
                                </div>
                            </div>

                            <!-- Step 5: Get IDs -->
                            <div class="gc-step" id="wa-step-5">
                                <div class="gc-step-header">
                                    <div class="gc-step-number">5</div>
                                    <div>
                                        <h3>نسخ المعرّفات المطلوبة</h3>
                                        <p>استخرج Phone Number ID و WhatsApp Business Account ID</p>
                                    </div>
                                </div>

                                <!-- Field Mapping -->
                                <div class="gc-field-mapping">
                                    <div class="gc-mapping-title"><i class="fa fa-exchange"></i> ربط الحقول من لوحة Meta</div>
                                    <div class="gc-mapping-grid">
                                        <div class="gc-mapping-row">
                                            <div class="gc-mapping-from">
                                                <span class="gc-mapping-num">1</span>
                                                <code>Phone Number ID</code>
                                            </div>
                                            <i class="fa fa-long-arrow-left gc-mapping-arrow"></i>
                                            <div class="gc-mapping-to">من صفحة API Setup → القسم العلوي</div>
                                        </div>
                                        <div class="gc-mapping-row">
                                            <div class="gc-mapping-from">
                                                <span class="gc-mapping-num">2</span>
                                                <code>WABA ID</code>
                                            </div>
                                            <i class="fa fa-long-arrow-left gc-mapping-arrow"></i>
                                            <div class="gc-mapping-to">WhatsApp Business Account ID — من API Setup</div>
                                        </div>
                                        <div class="gc-mapping-row">
                                            <div class="gc-mapping-from">
                                                <span class="gc-mapping-num">3</span>
                                                <code>App ID</code>
                                            </div>
                                            <i class="fa fa-long-arrow-left gc-mapping-arrow"></i>
                                            <div class="gc-mapping-to">من Settings → Basic في لوحة التطبيق</div>
                                        </div>
                                        <div class="gc-mapping-row">
                                            <div class="gc-mapping-from">
                                                <span class="gc-mapping-num">4</span>
                                                <code>App Secret</code>
                                            </div>
                                            <i class="fa fa-long-arrow-left gc-mapping-arrow"></i>
                                            <div class="gc-mapping-to">من Settings → Basic → App Secret (اضغط Show)</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="gc-step-instructions">
                                    <div class="gc-instruction-item">
                                        <span class="gc-inst-num">1</span>
                                        <span>من <strong>WhatsApp → API Setup</strong>: انسخ <strong>Phone Number ID</strong> و <strong>WhatsApp Business Account ID</strong></span>
                                    </div>
                                    <div class="gc-instruction-item">
                                        <span class="gc-inst-num">2</span>
                                        <span>من <strong>App Settings → Basic</strong>: انسخ <strong>App ID</strong> و <strong>App Secret</strong></span>
                                    </div>
                                </div>

                                <div class="gc-step-nav">
                                    <button type="button" class="gc-prev-btn" onclick="goToWaStep(4)"><i class="fa fa-arrow-right"></i> السابق</button>
                                    <button type="button" class="gc-next-btn" onclick="goToWaStep(6)">الخطوة الأخيرة <i class="fa fa-arrow-left"></i></button>
                                </div>
                            </div>

                            <!-- Step 6: Fill & Save -->
                            <div class="gc-step" id="wa-step-6">
                                <div class="gc-step-header">
                                    <div class="gc-step-number">6</div>
                                    <div>
                                        <h3>إدخال البيانات وحفظ الإعدادات</h3>
                                        <p>أدخل جميع البيانات في النموذج أدناه واضغط حفظ</p>
                                    </div>
                                </div>

                                <div class="gc-step-instructions">
                                    <div class="gc-instruction-item gc-note-item gc-success-item">
                                        <i class="fa fa-check-circle"></i>
                                        <span>بعد ملء الحقول أدناه، اضغط <strong>"حفظ الإعدادات"</strong> ثم <strong>"اختبار الاتصال"</strong> للتأكد</span>
                                    </div>
                                    <div class="gc-instruction-item gc-note-item">
                                        <i class="fa fa-info-circle"></i>
                                        <span>أول <strong>1,000 محادثة/شهر مجانية</strong> (Service Conversations) — بعدها تُحسب حسب نوع المحادثة والبلد</span>
                                    </div>
                                </div>

                                <div class="gc-step-nav">
                                    <button type="button" class="gc-prev-btn" onclick="goToWaStep(5)"><i class="fa fa-arrow-right"></i> السابق</button>
                                    <button type="button" class="gc-next-btn gc-done-btn" onclick="toggleWaGuide()"><i class="fa fa-check"></i> إغلاق الدليل</button>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- ═══════════ Credentials Card ═══════════ -->
                    <div class="sys-card">
                        <div class="sys-card-header">
                            <div class="sys-card-title">
                                <i class="fa fa-key"></i> بيانات اعتماد WhatsApp Business API
                            </div>
                            <span class="sys-card-badge"><i class="fa fa-lock"></i> مشفّرة</span>
                        </div>
                        <div class="sys-card-body">
                            <div class="sys-form-grid">
                                <div class="sys-field">
                                    <label class="sys-label" for="wa_phone_number_id">
                                        <i class="fa fa-phone"></i> معرّف رقم الهاتف (Phone Number ID)
                                    </label>
                                    <input type="text" class="sys-input" id="wa_phone_number_id" name="wa_phone_number_id"
                                           value="<?= Html::encode($waSettings['phone_number_id'] ?? '') ?>"
                                           placeholder="123456789012345"
                                           dir="ltr">
                                    <p class="sys-field-hint">من WhatsApp → API Setup في لوحة Meta Developers</p>
                                </div>

                                <div class="sys-field">
                                    <label class="sys-label" for="wa_waba_id">
                                        <i class="fa fa-building"></i> معرّف حساب الأعمال (WABA ID)
                                    </label>
                                    <input type="text" class="sys-input" id="wa_waba_id" name="wa_waba_id"
                                           value="<?= Html::encode($waSettings['waba_id'] ?? '') ?>"
                                           placeholder="123456789012345"
                                           dir="ltr">
                                    <p class="sys-field-hint">WhatsApp Business Account ID — من نفس صفحة API Setup</p>
                                </div>

                                <div class="sys-field sys-field-full">
                                    <label class="sys-label" for="wa_access_token">
                                        <i class="fa fa-ticket"></i> رمز الوصول (Access Token)
                                    </label>
                                    <div class="sys-key-wrapper">
                                        <textarea class="sys-textarea" id="wa_access_token" name="wa_access_token"
                                                  rows="3" dir="ltr"
                                                  placeholder="EAAxxxxxxxx..."><?= !empty($waSettings['has_access_token']) ? '••••••••••' : '' ?></textarea>
                                        <?php if (!empty($waSettings['has_access_token'])): ?>
                                            <div class="sys-key-notice">
                                                <i class="fa fa-check-circle"></i>
                                                Access Token محفوظ ومشفّر — اتركه كما هو للاحتفاظ بالرمز الحالي، أو الصق رمزاً جديداً
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <p class="sys-field-hint">استخدم Permanent Token من System User — الرمز المؤقت ينتهي خلال 24 ساعة</p>
                                </div>

                                <div class="sys-field">
                                    <label class="sys-label" for="wa_app_id">
                                        <i class="fa fa-cube"></i> معرّف التطبيق (App ID)
                                    </label>
                                    <input type="text" class="sys-input" id="wa_app_id" name="wa_app_id"
                                           value="<?= Html::encode($waSettings['app_id'] ?? '') ?>"
                                           placeholder="123456789012345"
                                           dir="ltr">
                                    <p class="sys-field-hint">من App Settings → Basic في لوحة Meta Developers</p>
                                </div>

                                <div class="sys-field">
                                    <label class="sys-label" for="wa_app_secret">
                                        <i class="fa fa-shield"></i> سر التطبيق (App Secret)
                                    </label>
                                    <input type="text" class="sys-input" id="wa_app_secret" name="wa_app_secret"
                                           value="<?= !empty($waSettings['has_app_secret']) ? '••••••••••' : '' ?>"
                                           placeholder="أدخل App Secret"
                                           dir="ltr">
                                    <?php if (!empty($waSettings['has_app_secret'])): ?>
                                        <div class="sys-key-notice">
                                            <i class="fa fa-check-circle"></i>
                                            App Secret محفوظ ومشفّر
                                        </div>
                                    <?php endif; ?>
                                    <p class="sys-field-hint">من App Settings → Basic → App Secret (اضغط Show)</p>
                                </div>

                                <div class="sys-field">
                                    <label class="sys-label" for="wa_webhook_verify_token">
                                        <i class="fa fa-check-square"></i> رمز تحقق Webhook (Verify Token)
                                    </label>
                                    <input type="text" class="sys-input" id="wa_webhook_verify_token" name="wa_webhook_verify_token"
                                           value="<?= Html::encode($waSettings['webhook_verify_token'] ?? '') ?>"
                                           placeholder="أي نص عشوائي تختاره أنت"
                                           dir="ltr">
                                    <p class="sys-field-hint">نص عشوائي تختاره — يُستخدم عند إعداد Webhook في لوحة Meta</p>
                                </div>

                                <div class="sys-field">
                                    <label class="sys-label" for="wa_api_version">
                                        <i class="fa fa-code-fork"></i> إصدار API (API Version)
                                    </label>
                                    <select class="sys-input" id="wa_api_version" name="wa_api_version">
                                        <option value="v21.0" <?= ($waSettings['api_version'] ?? 'v21.0') === 'v21.0' ? 'selected' : '' ?>>v21.0 (مستقر)</option>
                                        <option value="v20.0" <?= ($waSettings['api_version'] ?? '') === 'v20.0' ? 'selected' : '' ?>>v20.0</option>
                                        <option value="v19.0" <?= ($waSettings['api_version'] ?? '') === 'v19.0' ? 'selected' : '' ?>>v19.0</option>
                                    </select>
                                    <p class="sys-field-hint">إصدار Graph API — استخدم الأحدث ما لم يكن لديك سبب محدد</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Save Button -->
                    <div class="sys-form-actions">
                        <button type="submit" class="sys-save-btn">
                            <i class="fa fa-save"></i> حفظ الإعدادات
                        </button>
                        <button type="button" class="sys-cancel-btn" onclick="window.location.reload()">
                            <i class="fa fa-undo"></i> إلغاء التغييرات
                        </button>
                    </div>
                </form>
                </div><!-- /g-panel-whatsapp -->

            </div><!-- /tab-messaging -->

            <!-- ═══════════ General Settings Tab ═══════════ -->
            <div class="sys-tab-content <?= $activeTab === 'general' ? 'active' : '' ?>" id="tab-general">

                <!-- Search -->
                <div class="gs-search-box">
                    <i class="fa fa-search"></i>
                    <input type="text" id="gs-search" class="gs-search-input" placeholder="ابحث في الإعدادات..." oninput="filterSettings(this.value)">
                </div>

                <!-- ── 1. إعدادات عامة ── -->
                <div class="gs-category" data-category="general">
                    <div class="gs-category-header">
                        <div class="gs-category-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <i class="fa fa-cog"></i>
                        </div>
                        <div>
                            <h3>إعدادات عامة</h3>
                            <p>الحالات وأنواع المستندات وطرق الدفع</p>
                        </div>
                    </div>
                    <div class="gs-items-grid">
                        <a href="<?= Url::to(['/status/status/index']) ?>" class="gs-item" data-search="الحالات status">
                            <div class="gs-item-icon"><i class="fa fa-toggle-on"></i></div>
                            <div class="gs-item-text">
                                <span class="gs-item-label">الحالات</span>
                                <span class="gs-item-desc">إدارة حالات السجلات</span>
                            </div>
                            <i class="fa fa-chevron-left gs-item-arrow"></i>
                        </a>
                        <a href="<?= Url::to(['/documentStatus/document-status/index']) ?>" class="gs-item" data-search="حالات المستندات document status">
                            <div class="gs-item-icon"><i class="fa fa-file-o"></i></div>
                            <div class="gs-item-text">
                                <span class="gs-item-label">حالات المستندات</span>
                                <span class="gs-item-desc">حالات الوثائق والملفات</span>
                            </div>
                            <i class="fa fa-chevron-left gs-item-arrow"></i>
                        </a>
                        <a href="<?= Url::to(['/documentType/document-type/index']) ?>" class="gs-item" data-search="أنواع المستندات document type">
                            <div class="gs-item-icon"><i class="fa fa-files-o"></i></div>
                            <div class="gs-item-text">
                                <span class="gs-item-label">أنواع المستندات</span>
                                <span class="gs-item-desc">تصنيف أنواع الوثائق</span>
                            </div>
                            <i class="fa fa-chevron-left gs-item-arrow"></i>
                        </a>
                        <a href="<?= Url::to(['/paymentType/payment-type/index']) ?>" class="gs-item" data-search="طرق الدفع payment type">
                            <div class="gs-item-icon"><i class="fa fa-credit-card"></i></div>
                            <div class="gs-item-text">
                                <span class="gs-item-label">طرق الدفع</span>
                                <span class="gs-item-desc">وسائل الدفع المتاحة</span>
                            </div>
                            <i class="fa fa-chevron-left gs-item-arrow"></i>
                        </a>
                        <a href="<?= Url::to(['/expenseCategories/expense-categories/index']) ?>" class="gs-item" data-search="فئات المصروفات expense categories">
                            <div class="gs-item-icon"><i class="fa fa-tags"></i></div>
                            <div class="gs-item-text">
                                <span class="gs-item-label">فئات المصروفات</span>
                                <span class="gs-item-desc">تصنيف المصاريف</span>
                            </div>
                            <i class="fa fa-chevron-left gs-item-arrow"></i>
                        </a>
                        <a href="<?= Url::to(['/bancks/bancks/index']) ?>" class="gs-item" data-search="البنوك banks">
                            <div class="gs-item-icon"><i class="fa fa-university"></i></div>
                            <div class="gs-item-text">
                                <span class="gs-item-label">البنوك</span>
                                <span class="gs-item-desc">المصارف وحساباتها</span>
                            </div>
                            <i class="fa fa-chevron-left gs-item-arrow"></i>
                        </a>
                    </div>
                </div>

                <!-- ── 2. الموقع الجغرافي ── -->
                <div class="gs-category" data-category="geo">
                    <div class="gs-category-header">
                        <div class="gs-category-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                            <i class="fa fa-globe"></i>
                        </div>
                        <div>
                            <h3>الموقع الجغرافي</h3>
                            <p>المدن والجنسيات</p>
                        </div>
                    </div>
                    <div class="gs-items-grid">
                        <a href="<?= Url::to(['/city/city/index']) ?>" class="gs-item" data-search="المدن city">
                            <div class="gs-item-icon"><i class="fa fa-map-marker"></i></div>
                            <div class="gs-item-text">
                                <span class="gs-item-label">المدن</span>
                                <span class="gs-item-desc">قائمة المدن والمناطق</span>
                            </div>
                            <i class="fa fa-chevron-left gs-item-arrow"></i>
                        </a>
                        <a href="<?= Url::to(['/citizen/citizen/index']) ?>" class="gs-item" data-search="الجنسيات citizen nationality">
                            <div class="gs-item-icon"><i class="fa fa-flag"></i></div>
                            <div class="gs-item-text">
                                <span class="gs-item-label">الجنسيات</span>
                                <span class="gs-item-desc">قائمة الجنسيات</span>
                            </div>
                            <i class="fa fa-chevron-left gs-item-arrow"></i>
                        </a>
                    </div>
                </div>

                <!-- ── 3. إعدادات العملاء ── -->
                <div class="gs-category" data-category="customers">
                    <div class="gs-category-header">
                        <div class="gs-category-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                            <i class="fa fa-users"></i>
                        </div>
                        <div>
                            <h3>إعدادات العملاء</h3>
                            <p>بيانات التواصل والقرابة والمصادر</p>
                        </div>
                    </div>
                    <div class="gs-items-grid">
                        <a href="<?= Url::to(['/cousins/cousins/index']) ?>" class="gs-item" data-search="صلة القرابة cousins">
                            <div class="gs-item-icon"><i class="fa fa-sitemap"></i></div>
                            <div class="gs-item-text">
                                <span class="gs-item-label">صلة القرابة</span>
                                <span class="gs-item-desc">أنواع العلاقات الأسرية</span>
                            </div>
                            <i class="fa fa-chevron-left gs-item-arrow"></i>
                        </a>
                        <a href="<?= Url::to(['/hearAboutUs/hear-about-us/index']) ?>" class="gs-item" data-search="مصدر التعرف علينا hear about us">
                            <div class="gs-item-icon"><i class="fa fa-bullhorn"></i></div>
                            <div class="gs-item-text">
                                <span class="gs-item-label">مصدر التعرف علينا</span>
                                <span class="gs-item-desc">كيف عرفوا عنّا</span>
                            </div>
                            <i class="fa fa-chevron-left gs-item-arrow"></i>
                        </a>
                        <a href="<?= Url::to(['/feelings/feelings/index']) ?>" class="gs-item" data-search="المشاعر feelings">
                            <div class="gs-item-icon"><i class="fa fa-smile-o"></i></div>
                            <div class="gs-item-text">
                                <span class="gs-item-label">المشاعر</span>
                                <span class="gs-item-desc">حالات العملاء</span>
                            </div>
                            <i class="fa fa-chevron-left gs-item-arrow"></i>
                        </a>
                        <a href="<?= Url::to(['/contactType/contact-type/index']) ?>" class="gs-item" data-search="أنواع الاتصال contact type">
                            <div class="gs-item-icon"><i class="fa fa-phone-square"></i></div>
                            <div class="gs-item-text">
                                <span class="gs-item-label">أنواع الاتصال</span>
                                <span class="gs-item-desc">طرق التواصل مع العملاء</span>
                            </div>
                            <i class="fa fa-chevron-left gs-item-arrow"></i>
                        </a>
                        <a href="<?= Url::to(['/connectionResponse/connection-response/index']) ?>" class="gs-item" data-search="ردود الاتصال connection response">
                            <div class="gs-item-icon"><i class="fa fa-reply"></i></div>
                            <div class="gs-item-text">
                                <span class="gs-item-label">ردود الاتصال</span>
                                <span class="gs-item-desc">نتائج محاولات الاتصال</span>
                            </div>
                            <i class="fa fa-chevron-left gs-item-arrow"></i>
                        </a>
                    </div>
                </div>

                <!-- ── 4. القسم القضائي ── -->
                <div class="gs-category" data-category="legal">
                    <div class="gs-category-header">
                        <div class="gs-category-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                            <i class="fa fa-gavel"></i>
                        </div>
                        <div>
                            <h3>القسم القضائي</h3>
                            <p>الإجراءات والمحاكم والمحامون</p>
                        </div>
                    </div>
                    <div class="gs-items-grid">
                        <a href="<?= Url::to(['/judiciaryActions/judiciary-actions/index']) ?>" class="gs-item" data-search="الإجراءات القضائية judiciary actions">
                            <div class="gs-item-icon"><i class="fa fa-legal"></i></div>
                            <div class="gs-item-text">
                                <span class="gs-item-label">الإجراءات القضائية</span>
                                <span class="gs-item-desc">أنواع الإجراءات القانونية</span>
                            </div>
                            <i class="fa fa-chevron-left gs-item-arrow"></i>
                        </a>
                        <a href="<?= Url::to(['/judiciaryType/judiciary-type/index']) ?>" class="gs-item" data-search="أنواع القضايا judiciary type">
                            <div class="gs-item-icon"><i class="fa fa-folder-open"></i></div>
                            <div class="gs-item-text">
                                <span class="gs-item-label">أنواع القضايا</span>
                                <span class="gs-item-desc">تصنيفات القضايا</span>
                            </div>
                            <i class="fa fa-chevron-left gs-item-arrow"></i>
                        </a>
                        <a href="<?= Url::to(['/lawyers/lawyers/index']) ?>" class="gs-item" data-search="المحامون lawyers">
                            <div class="gs-item-icon"><i class="fa fa-briefcase"></i></div>
                            <div class="gs-item-text">
                                <span class="gs-item-label">المحامون</span>
                                <span class="gs-item-desc">قائمة المحامين المعتمدين</span>
                            </div>
                            <i class="fa fa-chevron-left gs-item-arrow"></i>
                        </a>
                        <a href="<?= Url::to(['/court/court/index']) ?>" class="gs-item" data-search="المحاكم court">
                            <div class="gs-item-icon"><i class="fa fa-institution"></i></div>
                            <div class="gs-item-text">
                                <span class="gs-item-label">المحاكم</span>
                                <span class="gs-item-desc">المحاكم والجهات القضائية</span>
                            </div>
                            <i class="fa fa-chevron-left gs-item-arrow"></i>
                        </a>
                        <a href="<?= Url::to(['/JudiciaryInformAddress/judiciary-inform-address/index']) ?>" class="gs-item" data-search="عناوين التبليغ inform address">
                            <div class="gs-item-icon"><i class="fa fa-map-signs"></i></div>
                            <div class="gs-item-text">
                                <span class="gs-item-label">عناوين التبليغ</span>
                                <span class="gs-item-desc">عناوين التبليغ القضائي</span>
                            </div>
                            <i class="fa fa-chevron-left gs-item-arrow"></i>
                        </a>
                    </div>
                </div>

                <!-- ── 5. الوظائف والرسائل ── -->
                <div class="gs-category" data-category="other">
                    <div class="gs-category-header">
                        <div class="gs-category-icon" style="background: linear-gradient(135deg, #a18cd1 0%, #fbc2eb 100%);">
                            <i class="fa fa-briefcase"></i>
                        </div>
                        <div>
                            <h3>الوظائف والرسائل</h3>
                            <p>جهات العمل والمسميات الوظيفية والرسائل</p>
                        </div>
                    </div>
                    <div class="gs-items-grid">
                        <a href="<?= Url::to(['/jobs/jobs/index']) ?>" class="gs-item" data-search="جهات العمل jobs">
                            <div class="gs-item-icon"><i class="fa fa-building-o"></i></div>
                            <div class="gs-item-text">
                                <span class="gs-item-label">جهات العمل</span>
                                <span class="gs-item-desc">المُستثمرين وجهات التوظيف</span>
                            </div>
                            <i class="fa fa-chevron-left gs-item-arrow"></i>
                        </a>
                        <a href="<?= Url::to(['/designation/designation/index']) ?>" class="gs-item" data-search="المسميات الوظيفية designation">
                            <div class="gs-item-icon"><i class="fa fa-id-badge"></i></div>
                            <div class="gs-item-text">
                                <span class="gs-item-label">المسميات الوظيفية</span>
                                <span class="gs-item-desc">الألقاب والمناصب</span>
                            </div>
                            <i class="fa fa-chevron-left gs-item-arrow"></i>
                        </a>
                        <a href="<?= Url::to(['/sms/sms/index']) ?>" class="gs-item" data-search="الرسائل النصية sms">
                            <div class="gs-item-icon"><i class="fa fa-envelope"></i></div>
                            <div class="gs-item-text">
                                <span class="gs-item-label">الرسائل النصية</span>
                                <span class="gs-item-desc">قوالب وإعدادات SMS</span>
                            </div>
                            <i class="fa fa-chevron-left gs-item-arrow"></i>
                        </a>
                    </div>
                </div>

                <!-- No results message -->
                <div class="gs-no-results" id="gs-no-results" style="display:none;">
                    <i class="fa fa-search fa-3x"></i>
                    <h3>لا توجد نتائج</h3>
                    <p>لم يتم العثور على إعدادات تطابق بحثك</p>
                </div>

            </div>

            <!-- ═══════════ Backup Tab ═══════════ -->
            <div class="sys-tab-content <?= $activeTab === 'backup' ? 'active' : '' ?>" id="tab-backup">

                <!-- Database Backup Card -->
                <div class="sys-card bk-card">
                    <div class="sys-card-header">
                        <div class="sys-card-title">
                            <i class="fa fa-database"></i> نسخة احتياطية لقاعدة البيانات
                        </div>
                        <span class="bk-badge bk-badge-fast"><i class="fa fa-bolt"></i> سريع</span>
                    </div>
                    <div class="sys-card-body">
                        <p class="bk-desc">تحميل ملف <code>SQL.gz</code> مضغوط يحتوي على جميع جداول وبيانات قاعدة البيانات الحالية.</p>

                        <div class="bk-info-grid">
                            <div class="bk-info-item">
                                <i class="fa fa-hdd-o"></i>
                                <div>
                                    <strong>الحجم المتوقع</strong>
                                    <span>عادةً أقل من 100 MB</span>
                                </div>
                            </div>
                            <div class="bk-info-item">
                                <i class="fa fa-clock-o"></i>
                                <div>
                                    <strong>الوقت المتوقع</strong>
                                    <span>أقل من دقيقة</span>
                                </div>
                            </div>
                            <div class="bk-info-item">
                                <i class="fa fa-table"></i>
                                <div>
                                    <strong>يشمل</strong>
                                    <span>كل الجداول · البيانات · الإجراءات</span>
                                </div>
                            </div>
                        </div>

                        <div class="bk-actions">
                            <a href="<?= Url::to(['server-backup']) ?>" class="bk-btn bk-btn-primary" id="btn-db-backup" onclick="return startDbBackup(this)">
                                <i class="fa fa-download"></i> تحميل نسخة قاعدة البيانات
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Full Server Backup Card -->
                <div class="sys-card bk-card">
                    <div class="sys-card-header">
                        <div class="sys-card-title">
                            <i class="fa fa-server"></i> نسخة احتياطية كاملة للسيرفر
                        </div>
                        <span class="bk-badge bk-badge-full"><i class="fa fa-cloud-download"></i> شامل</span>
                    </div>
                    <div class="sys-card-body">
                        <p class="bk-desc">تحميل جميع الملفات والصور والمستندات من السيرفر إلى جهازك المحلي عبر سكربت Python مع مزامنة تدريجية (يحمّل الملفات الجديدة/المتغيرة فقط).</p>

                        <div class="bk-info-grid">
                            <div class="bk-info-item">
                                <i class="fa fa-hdd-o"></i>
                                <div>
                                    <strong>الحجم المتوقع</strong>
                                    <span>~20 GB (أول مرة)</span>
                                </div>
                            </div>
                            <div class="bk-info-item">
                                <i class="fa fa-clock-o"></i>
                                <div>
                                    <strong>الوقت المتوقع</strong>
                                    <span>2-6 ساعات (أول مرة) · دقائق (التحديثات)</span>
                                </div>
                            </div>
                            <div class="bk-info-item">
                                <i class="fa fa-folder-open"></i>
                                <div>
                                    <strong>يشمل</strong>
                                    <span>الصور · المستندات · القضايا · الموظفين · الإعدادات</span>
                                </div>
                            </div>
                        </div>

                        <!-- Folders included -->
                        <div class="bk-folders-section">
                            <h4><i class="fa fa-sitemap"></i> المجلدات المشمولة</h4>
                            <div class="bk-folders-grid">
                                <div class="bk-folder-item">
                                    <i class="fa fa-image"></i>
                                    <span>images/imagemanager</span>
                                    <small>صور العملاء والعقود</small>
                                </div>
                                <div class="bk-folder-item">
                                    <i class="fa fa-file-text"></i>
                                    <span>uploads/customers/documents</span>
                                    <small>مستندات العملاء</small>
                                </div>
                                <div class="bk-folder-item">
                                    <i class="fa fa-camera"></i>
                                    <span>uploads/customers/photos</span>
                                    <small>صور العملاء (قديم)</small>
                                </div>
                                <div class="bk-folder-item">
                                    <i class="fa fa-gavel"></i>
                                    <span>uploads/judiciary_*</span>
                                    <small>مرفقات القضايا والقرارات</small>
                                </div>
                                <div class="bk-folder-item">
                                    <i class="fa fa-building"></i>
                                    <span>uploads/investors</span>
                                    <small>مستندات الشركات</small>
                                </div>
                                <div class="bk-folder-item">
                                    <i class="fa fa-user"></i>
                                    <span>images/employeeImage</span>
                                    <small>صور الموظفين</small>
                                </div>
                            </div>
                        </div>

                        <!-- How to run -->
                        <div class="bk-howto-section">
                            <h4><i class="fa fa-terminal"></i> كيفية التشغيل</h4>
                            <div class="bk-code-block">
                                <div class="bk-code-header">
                                    <span>Terminal / PowerShell</span>
                                    <button type="button" class="bk-copy-btn" onclick="copyBackupCmd(this)"><i class="fa fa-copy"></i> نسخ</button>
                                </div>
                                <pre class="bk-code"><code><span class="bk-comment"># نسخة كاملة (قاعدة البيانات + الملفات)</span>
python scripts/backup/backup.py

<span class="bk-comment"># قاعدة البيانات فقط</span>
python scripts/backup/backup.py --db-only

<span class="bk-comment"># ملفات فقط</span>
python scripts/backup/backup.py --files-only

<span class="bk-comment"># موقع محدد فقط</span>
python scripts/backup/backup.py --site jadal
python scripts/backup/backup.py --site namaa

<span class="bk-comment"># معاينة بدون تحميل</span>
python scripts/backup/backup.py --dry-run</code></pre>
                            </div>

                            <div class="bk-note">
                                <i class="fa fa-info-circle"></i>
                                <div>
                                    <strong>ملاحظة:</strong> يتطلب مكتبة <code>paramiko</code>. إذا لم تكن مثبتة:
                                    <code>pip install paramiko</code>
                                </div>
                            </div>

                            <div class="bk-note bk-note-success">
                                <i class="fa fa-refresh"></i>
                                <div>
                                    <strong>مزامنة تدريجية:</strong> السكربت يقارن تاريخ وحجم كل ملف — يحمّل فقط الملفات الجديدة أو المتغيرة.
                                    بعد أول نسخة، التحديثات ستكون سريعة جداً (دقائق فقط).
                                </div>
                            </div>

                            <div class="bk-save-location">
                                <i class="fa fa-folder"></i>
                                <span>مسار الحفظ: <code>%USERPROFILE%\TayseerBackups\</code></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php
$testUrl = Url::to(['test-google-connection']);
$testMapsUrl = Url::to(['test-maps-connection']);
$testSmsUrl = Url::to(['test-sms-connection']);
$testWhatsappUrl = Url::to(['test-whatsapp-connection']);
$googleStatsUrl = Url::to(['/customers/smart-media/google-stats']);
$js = <<<JS
// دليل خريطة Google — طيّ/فتح
window.toggleGmGuide = function() {
    var body = $('#gm-setup-guide');
    var text = $('#gm-guide-toggle-text');
    var chevron = $('#gm-guide-chevron');
    if (body.is(':visible')) {
        body.slideUp(300);
        text.text('عرض الدليل');
        chevron.css('transform', 'rotate(0deg)');
    } else {
        body.slideDown(300);
        text.text('إخفاء الدليل');
        chevron.css('transform', 'rotate(180deg)');
    }
};

// Tab navigation (only for non-disabled)
$('.sys-nav-item:not(.disabled)').on('click', function(e) {
    var tab = $(this).data('tab');
    // إذا العنصر فيه data-tab → تبديل تبويب، وإلا → رابط عادي (مثل إدارة الصور)
    if (!tab) return; // اسمح للرابط يشتغل طبيعي
    e.preventDefault();
    $('.sys-nav-item').removeClass('active');
    $(this).addClass('active');
    $('.sys-tab-content').removeClass('active');
    $('#tab-' + tab).addClass('active');
});

// Clear textarea on focus if masked
$('#gc_private_key').on('focus', function() {
    if ($(this).val() === '••••••••••') {
        $(this).val('');
    }
});
$('#gc_private_key').on('blur', function() {
    if ($(this).val().trim() === '') {
        $(this).val('••••••••••');
    }
});

// ═══════════ General Settings Search ═══════════
window.filterSettings = function(query) {
    query = query.trim().toLowerCase();
    var hasResults = false;
    
    if (!query) {
        // Show all
        $('.gs-category').show();
        $('.gs-item').show();
        $('#gs-no-results').hide();
        return;
    }
    
    $('.gs-category').each(function() {
        var cat = $(this);
        var catVisible = false;
        
        cat.find('.gs-item').each(function() {
            var item = $(this);
            var searchText = (item.attr('data-search') || '').toLowerCase();
            var label = item.find('.gs-item-label').text().toLowerCase();
            var desc = item.find('.gs-item-desc').text().toLowerCase();
            var match = searchText.indexOf(query) !== -1 || label.indexOf(query) !== -1 || desc.indexOf(query) !== -1;
            
            item.toggle(match);
            if (match) catVisible = true;
        });
        
        cat.toggle(catVisible);
        if (catVisible) hasResults = true;
    });
    
    $('#gs-no-results').toggle(!hasResults);
};

// ═══════════ Setup Guide ═══════════

// Toggle guide visibility
window.toggleGuide = function() {
    var body = $('#setup-guide');
    var text = $('#guide-toggle-text');
    var chevron = $('#guide-chevron');
    if (body.is(':visible')) {
        body.slideUp(300);
        text.text('عرض الدليل');
        chevron.css('transform', 'rotate(0deg)');
    } else {
        body.slideDown(300);
        text.text('إخفاء الدليل');
        chevron.css('transform', 'rotate(180deg)');
    }
};

// Navigate between steps
window.goToStep = function(step) {
    $('.gc-step').removeClass('active');
    $('#gc-step-' + step).addClass('active');
    // Update progress dots
    $('.gc-step-dot').removeClass('active completed');
    for (var i = 1; i < step; i++) {
        $('.gc-step-dot[data-step="' + i + '"]').addClass('completed');
    }
    $('.gc-step-dot[data-step="' + step + '"]').addClass('active');
    // Scroll to top of guide
    $('#setup-guide')[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
};

// ═══ Shared: read a JSON file via FileReader ═══
function readJsonFile(file, callback) {
    if (!file) return;
    if (file.type && file.type !== 'application/json' && !file.name.endsWith('.json')) {
        callback(null, 'الملف ليس بصيغة JSON');
        return;
    }
    var reader = new FileReader();
    reader.onload = function(e) {
        try {
            var data = JSON.parse(e.target.result);
            callback(data, null, file.name);
        } catch (err) {
            callback(null, 'صيغة JSON غير صالحة — تأكد أن الملف صحيح');
        }
    };
    reader.onerror = function() { callback(null, 'تعذّر قراءة الملف'); };
    reader.readAsText(file);
}

// Fill Vision API fields from parsed data object
function fillVisionFields(data, resultDiv) {
    var filled = 0;
    if (!resultDiv) resultDiv = $('#gc-parse-result');
    if (data.project_id) { $('#gc_project_id').val(data.project_id).css('border-color', '#28a745'); filled++; }
    if (data.client_email) { $('#gc_client_email').val(data.client_email).css('border-color', '#28a745'); filled++; }
    if (data.private_key) { $('#gc_private_key').val(data.private_key).css('border-color', '#28a745'); filled++; }
    if (filled === 0) {
        resultDiv.html('<div class="gc-parse-error"><i class="fa fa-exclamation-circle"></i> لم يتم العثور على حقول service account في الملف (project_id, client_email, private_key)</div>').show();
        return;
    }
    resultDiv.html(
        '<div class="gc-parse-success"><i class="fa fa-check-circle"></i> تم استخراج ' + filled + ' حقول بنجاح! تحقق من البيانات أدناه ثم اضغط "حفظ الإعدادات"</div>'
    ).show();
    setTimeout(function() { $('#gc_project_id, #gc_client_email, #gc_private_key').css('border-color', ''); }, 3000);
}

// ═══ Vision API: File upload handler (supports multiple dropzones) ═══
function initVisionDropzone(dropId, fileId, contentId, successId, filenameId, resultId) {
    var dropzone = document.getElementById(dropId);
    var fileInput = document.getElementById(fileId);
    if (!dropzone || !fileInput) return;

    dropzone.addEventListener('click', function(e) {
        if (e.target === fileInput) return;
        fileInput.click();
    });
    dropzone.addEventListener('dragover', function(e) { e.preventDefault(); dropzone.classList.add('dragover'); });
    dropzone.addEventListener('dragleave', function() { dropzone.classList.remove('dragover'); });
    dropzone.addEventListener('drop', function(e) {
        e.preventDefault();
        dropzone.classList.remove('dragover');
        if (e.dataTransfer.files.length) handle(e.dataTransfer.files[0]);
    });
    fileInput.addEventListener('change', function() { if (this.files.length) handle(this.files[0]); });

    function handle(file) {
        var resDiv = resultId ? $('#' + resultId) : $('#gc-parse-result');
        readJsonFile(file, function(data, err, name) {
            if (err) {
                resDiv.html('<div class="gc-parse-error"><i class="fa fa-exclamation-circle"></i> ' + err + '</div>').show();
                return;
            }
            var contentEl = document.getElementById(contentId);
            if (contentEl) contentEl.style.display = 'none';
            $('#' + successId).show().find('#' + filenameId).text(name);
            fillVisionFields(data, resDiv);
        });
    }
}
initVisionDropzone('gc-json-dropzone-main', 'gc-json-file-main', 'gc-upload-content-main', 'gc-upload-success-main', 'gc-upload-filename-main', 'gc-parse-result-main');
initVisionDropzone('gc-json-dropzone', 'gc-json-file', 'gc-json-dropzone', 'gc-upload-success', 'gc-upload-filename', null);

// ═══ Vision API: Paste handler (existing) ═══
window.parseJsonCredentials = function() {
    var jsonText = $('#gc-json-paste').val().trim();
    var resultDiv = $('#gc-parse-result');
    if (!jsonText) {
        resultDiv.html('<div class="gc-parse-error"><i class="fa fa-exclamation-circle"></i> الصق محتوى ملف JSON أولاً</div>').show();
        return;
    }
    try {
        fillVisionFields(JSON.parse(jsonText));
    } catch (e) {
        resultDiv.html('<div class="gc-parse-error"><i class="fa fa-exclamation-circle"></i> صيغة JSON غير صالحة — تأكد من نسخ المحتوى كاملاً</div>').show();
    }
};

// ═══ Maps API: File upload handler ═══
(function() {
    var dropzone = document.getElementById('gm-json-dropzone');
    var fileInput = document.getElementById('gm-json-file');
    if (!dropzone || !fileInput) return;

    dropzone.addEventListener('click', function(e) {
        if (e.target === fileInput) return;
        fileInput.click();
    });
    dropzone.addEventListener('dragover', function(e) { e.preventDefault(); dropzone.classList.add('dragover'); });
    dropzone.addEventListener('dragleave', function() { dropzone.classList.remove('dragover'); });
    dropzone.addEventListener('drop', function(e) {
        e.preventDefault();
        dropzone.classList.remove('dragover');
        if (e.dataTransfer.files.length) handleMapsFile(e.dataTransfer.files[0]);
    });
    fileInput.addEventListener('change', function() { if (this.files.length) handleMapsFile(this.files[0]); });

    function handleMapsFile(file) {
        readJsonFile(file, function(data, err, name) {
            var keyField = $('#gm_api_key');
            if (err) {
                keyField.css('border-color', '#dc3545');
                setTimeout(function() { keyField.css('border-color', ''); }, 3000);
                return;
            }
            // Detect service account JSON (wrong file type for Maps)
            if (data.type === 'service_account' || data.private_key || data.client_email) {
                var hint = $('<div class="gc-parse-error" style="margin-top:8px">'
                    + '<i class="fa fa-exclamation-triangle" style="color:#f59e0b"></i> '
                    + '<strong>هذا ملف حساب خدمة (Service Account)</strong> — يُستخدم لـ Vision API فقط.<br>'
                    + 'خريطة Google Maps تحتاج <strong>مفتاح API Key</strong> (نص يبدأ بـ <code>AIza...</code>).<br>'
                    + '<span style="margin-top:6px;display:inline-block">'
                    + 'للحصول عليه: <a href="https://console.cloud.google.com/apis/credentials" target="_blank" style="color:#2563eb;font-weight:700">'
                    + 'Cloud Console → Credentials → Create API Key</a></span>'
                    + '</div>');
                $(dropzone).after(hint);
                setTimeout(function() { hint.fadeOut(function() { hint.remove(); }); }, 12000);
                return;
            }
            var key = data.api_key || data.key || data.maps_api_key || data.google_maps_api_key || data.apiKey || null;
            if (!key && typeof data === 'object') {
                for (var k in data) {
                    if (typeof data[k] === 'string' && data[k].indexOf('AIza') === 0) { key = data[k]; break; }
                }
            }
            if (key) {
                keyField.val(key).css('border-color', '#28a745');
                $('#gm-upload-success').show().find('#gm-upload-filename').text(name);
                dropzone.querySelector('.json-upload-content').style.display = 'none';
                setTimeout(function() { keyField.css('border-color', ''); }, 3000);
            } else {
                keyField.css('border-color', '#dc3545');
                var hint = $('<div class="gc-parse-error" style="margin-top:8px"><i class="fa fa-exclamation-circle"></i> لم يتم العثور على مفتاح API في الملف. المفتاح هو نص يبدأ بـ <code>AIza...</code> — يمكنك لصقه مباشرة في الحقل أعلاه</div>');
                $(dropzone).after(hint);
                setTimeout(function() { keyField.css('border-color', ''); hint.fadeOut(function() { hint.remove(); }); }, 4000);
            }
        });
    }
})();

// Copy JSON value to field
window.copyJsonValue = function(el, fieldId) {
    var field = $('#' + fieldId);
    field.css({ 'border-color': '#4285f4', 'box-shadow': '0 0 0 3px rgba(66,133,244,0.3)' });
    setTimeout(function() {
        field.css({ 'border-color': '', 'box-shadow': '' });
    }, 2000);
};

// ═══ Inner tabs (scoped to parent tab-content) ═══
$('.g-inner-tab').on('click', function() {
    var panel = $(this).data('inner');
    var container = $(this).closest('.sys-tab-content');
    container.find('.g-inner-tab').removeClass('active');
    $(this).addClass('active');
    container.find('.g-inner-panel').removeClass('active');
    $('#g-panel-' + panel).addClass('active');
});

// ═══ Cost tab: source toggle (local / google / info) ═══
$('.cost-src-tab').on('click', function() {
    var group = $(this).data('cost-group');
    var src = $(this).data('cost-src');
    $('.cost-src-tab[data-cost-group="' + group + '"]').removeClass('active');
    $(this).addClass('active');
    $('[data-cost-group="' + group + '"].cost-src-panel').hide();
    $('[data-cost-group="' + group + '"][data-cost-panel="' + src + '"]').show();
    if (src === 'google' && !window['_gc_' + group + '_loaded']) {
        window['_gc_' + group + '_loaded'] = true;
        loadCostStats();
    }
});

var _costStatsCache = null;
function loadCostStats() {
    if (_costStatsCache) { fillCostPanels(_costStatsCache); return; }
    $('#v-gc-status, #m-gc-status').html('<i class="fa fa-spinner fa-spin"></i> جاري الاتصال بـ Google Cloud...');
    $.ajax({
        url: '{$googleStatsUrl}',
        method: 'GET',
        timeout: 25000,
        dataType: 'json',
        success: function(data) {
            _costStatsCache = data;
            fillCostPanels(data);
        },
        error: function(xhr) {
            var msg = '<i class="fa fa-exclamation-triangle" style="color:#e74c3c"></i> فشل الاتصال (' + xhr.status + ')';
            $('#v-gc-status, #m-gc-status').html(msg);
        }
    });
}

function fillCostPanels(data) {
    // --- Vision ---
    if (data && data.google) {
        var g = data.google;
        var u = g.usage || {};
        var total = u.total_requests || 0;
        var free = u.free_tier_used || Math.min(total, 1000);
        var billable = u.billable_requests || 0;
        var cost = u.estimated_cost || 0;
        var remaining = u.free_remaining || Math.max(0, 1000 - total);
        var pct = Math.min(100, (total / 1000) * 100);

        $('#v-gc-total').text(total.toLocaleString());
        $('#v-gc-free').text(free.toLocaleString());
        $('#v-gc-billable').text(billable.toLocaleString());
        $('#v-gc-cost').text('$' + cost.toFixed(4));
        $('#v-gc-bar-label').text(total.toLocaleString() + ' / 1,000');
        $('#v-gc-bar').css('width', pct + '%');
        if (pct > 80) $('#v-gc-bar').addClass('warning');
        if (pct > 95) $('#v-gc-bar').addClass('danger');
        $('#v-gc-remaining-label').text('المتبقي: ' + remaining.toLocaleString() + ' مجاني');
        $('#v-gc-pct').text(Math.round(pct) + '%');

        var statusHtml = '<i class="fa fa-check-circle" style="color:#27ae60"></i> بيانات حقيقية من Google Cloud';
        if (g.billing_enabled) statusHtml += ' — <span style="color:#27ae60">الفوترة مفعّلة</span>';
        $('#v-gc-status').html(statusHtml);

        if (u.breakdown && u.breakdown.length > 0) {
            var tbl = '<table class="cost-breakdown-tbl"><tr><th>الوظيفة</th><th>الطلبات</th><th>الحالة</th></tr>';
            u.breakdown.forEach(function(b) {
                tbl += '<tr><td>' + (b.method || '—') + '</td><td>' + (b.count || 0) + '</td><td>' + (b.status || '—') + '</td></tr>';
            });
            tbl += '</table>';
            $('#v-gc-breakdown').html(tbl);
        }
    } else {
        $('#v-gc-status').html('<i class="fa fa-exclamation-triangle" style="color:#e74c3c"></i> لا توجد بيانات — تأكد من إعداد Google Cloud credentials');
    }

    // --- Maps: verify key and show status ---
    if (data && data.maps) {
        var m = data.maps;
        $('#m-gc-total').text(m.total_requests || '—');
        $('#m-gc-used').text(m.cost || '$0');
        $('#m-gc-remaining').text(m.remaining || '$200');
        var mPct = m.usage_percent || 0;
        $('#m-gc-bar-label').text((m.cost || '$0') + ' / $200');
        $('#m-gc-bar').css('width', mPct + '%');
        if (mPct > 80) $('#m-gc-bar').addClass('warning');
        $('#m-gc-status').html(m.status_html || '<i class="fa fa-check-circle" style="color:#27ae60"></i> متصل');
    } else {
        $('#m-gc-status').html('<i class="fa fa-info-circle" style="color:#4285f4"></i> بيانات Maps API التفصيلية تتوفر عبر <a href="https://console.cloud.google.com/billing" target="_blank" rel="noopener">Google Cloud Billing Console</a> — استخدم زر "اختبار المفتاح" في تبويب Maps للتحقق من صلاحيته');
    }
}

// Test connection — sends form values, auto-saves on success
window.testGoogleConnection = function() {
    var btn = document.getElementById('btn-test-connection');
    var statusDiv = document.getElementById('connection-status');
    if (!btn || !statusDiv) { console.error('Vision test: btn or statusDiv not found'); return; }
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> جاري الاختبار والحفظ...';

    var formData = new URLSearchParams();
    formData.append('gc_project_id', document.getElementById('gc_project_id') ? document.getElementById('gc_project_id').value : '');
    formData.append('gc_client_email', document.getElementById('gc_client_email') ? document.getElementById('gc_client_email').value : '');
    formData.append('gc_private_key', document.getElementById('gc_private_key') ? document.getElementById('gc_private_key').value : '');
    formData.append('gc_monthly_limit', document.getElementById('gc_monthly_limit') ? document.getElementById('gc_monthly_limit').value : '1000');
    formData.append('gc_cost_per_request', document.getElementById('gc_cost_per_request') ? document.getElementById('gc_cost_per_request').value : '0.0015');

    fetch('{$testUrl}', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: formData.toString(),
        credentials: 'same-origin'
    })
    .then(function(r) {
        if (!r.ok) throw new Error('HTTP ' + r.status);
        return r.json();
    })
    .then(function(res) {
        if (res.success) {
            statusDiv.innerHTML =
                '<div class="sys-status-indicator connected">' +
                '<i class="fa fa-check-circle fa-2x"></i>' +
                '<div><strong>متصل بنجاح!</strong>' +
                '<p>' + (res.message || '') + (res.project_id ? ' — المشروع: ' + res.project_id : '') +
                (res.saved ? '<br><i class="fa fa-save"></i> تم حفظ الإعدادات تلقائياً' : '') +
                '</p></div></div>';
            var toggle = document.querySelector('input[name="gc_enabled"][type="checkbox"]');
            if (toggle) toggle.checked = true;
        } else {
            statusDiv.innerHTML =
                '<div class="sys-status-indicator error">' +
                '<i class="fa fa-times-circle fa-2x"></i>' +
                '<div><strong>فشل الاتصال</strong>' +
                '<p>' + (res.error || 'خطأ غير معروف') + '</p></div></div>';
        }
    })
    .catch(function(err) {
        console.error('Vision API test error:', err);
        statusDiv.innerHTML =
            '<div class="sys-status-indicator error">' +
            '<i class="fa fa-times-circle fa-2x"></i>' +
            '<div><strong>خطأ في الاتصال</strong>' +
            '<p>' + err.message + '</p></div></div>';
    })
    .finally(function() {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-plug"></i> اختبار الاتصال';
    });
};

// ═══ Maps API: Test connection ═══
window.testMapsConnection = function() {
    var btn = document.getElementById('btn-test-maps');
    var statusDiv = document.getElementById('gm-connection-status');
    if (!btn || !statusDiv) { console.error('Maps test: btn or statusDiv not found'); return; }
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> جاري الاختبار...';

    fetch('{$testMapsUrl}', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        credentials: 'same-origin'
    })
    .then(function(r) {
        if (!r.ok) throw new Error('HTTP ' + r.status);
        return r.json();
    })
    .then(function(res) {
        if (res.success) {
            statusDiv.innerHTML =
                '<div class="sys-status-indicator connected">' +
                '<i class="fa fa-check-circle fa-2x"></i>' +
                '<div><strong>المفتاح يعمل بنجاح!</strong>' +
                '<p>' + (res.message || '') + '</p></div></div>';
        } else {
            statusDiv.innerHTML =
                '<div class="sys-status-indicator error">' +
                '<i class="fa fa-times-circle fa-2x"></i>' +
                '<div><strong>المفتاح لا يعمل</strong>' +
                '<p>' + (res.error || 'خطأ غير معروف') + '</p></div></div>';
        }
    })
    .catch(function(err) {
        console.error('Maps API test error:', err);
        statusDiv.innerHTML =
            '<div class="sys-status-indicator error">' +
            '<i class="fa fa-times-circle fa-2x"></i>' +
            '<div><strong>خطأ في الاتصال</strong>' +
            '<p>' + err.message + '</p></div></div>';
    })
    .finally(function() {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-plug"></i> اختبار المفتاح';
    });
};

// ═══ Backup: DB download button state ═══
window.startDbBackup = function(el) {
    var b = $(el);
    b.html('<i class="fa fa-spinner fa-spin"></i> جاري إعداد النسخة الاحتياطية...');
    b.css({'pointer-events': 'none', 'opacity': '0.7'});
    setTimeout(function() {
        b.html('<i class="fa fa-download"></i> تحميل نسخة قاعدة البيانات');
        b.css({'pointer-events': '', 'opacity': ''});
    }, 8000);
    return true;
};

// ═══ Backup: Copy command to clipboard ═══
window.copyBackupCmd = function(el) {
    var code = $(el).closest('.bk-code-block').find('code').text();
    var lines = code.split('\\n').filter(function(l) { return l.trim() && l.trim().charAt(0) !== '#'; });
    navigator.clipboard.writeText(lines[0] || code).then(function() {
        var orig = $(el).html();
        $(el).html('<i class="fa fa-check"></i> تم النسخ!');
        setTimeout(function() { $(el).html(orig); }, 2000);
    });
};

// ═══════════════════════════════════════════════════════════
//  SMS API
// ═══════════════════════════════════════════════════════════

window.toggleSmsGuide = function() {
    var body = $('#sms-setup-guide');
    var text = $('#sms-guide-toggle-text');
    var chevron = $('#sms-guide-chevron');
    if (body.is(':visible')) {
        body.slideUp(300);
        text.text('عرض الدليل');
        chevron.css('transform', 'rotate(0deg)');
    } else {
        body.slideDown(300);
        text.text('إخفاء الدليل');
        chevron.css('transform', 'rotate(180deg)');
    }
};

window.goToSmsStep = function(step) {
    $('#sms-setup-guide .gc-step').removeClass('active');
    $('#sms-step-' + step).addClass('active');
    $('#sms-setup-guide .gc-step-dot').removeClass('active completed');
    for (var i = 1; i < step; i++) {
        $('#sms-setup-guide .gc-step-dot[data-step="s' + i + '"]').addClass('completed');
    }
    $('#sms-setup-guide .gc-step-dot[data-step="s' + step + '"]').addClass('active');
    $('#sms-setup-guide')[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
};

window.testSmsConnection = function() {
    var btn = document.getElementById('btn-test-sms');
    var statusDiv = document.getElementById('sms-connection-status');
    if (!btn || !statusDiv) return;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> جاري الاختبار...';

    fetch('{$testSmsUrl}', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
        credentials: 'same-origin'
    })
    .then(function(r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
    .then(function(res) {
        if (res.success) {
            statusDiv.innerHTML =
                '<div class="sys-status-indicator connected"><i class="fa fa-check-circle fa-2x"></i>' +
                '<div><strong>الاتصال ناجح!</strong><p>' + (res.message || '') + '</p></div></div>';
        } else {
            statusDiv.innerHTML =
                '<div class="sys-status-indicator error"><i class="fa fa-times-circle fa-2x"></i>' +
                '<div><strong>فشل الاتصال</strong><p>' + (res.error || 'خطأ غير معروف') + '</p></div></div>';
        }
    })
    .catch(function(err) {
        statusDiv.innerHTML =
            '<div class="sys-status-indicator error"><i class="fa fa-times-circle fa-2x"></i>' +
            '<div><strong>خطأ في الاتصال</strong><p>' + err.message + '</p></div></div>';
    })
    .finally(function() {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-plug"></i> اختبار الاتصال';
    });
};

// Clear masked SMS fields on focus
$('#sms_api_key, #sms_api_secret').on('focus', function() {
    if ($(this).val() === '••••••••••') $(this).val('');
}).on('blur', function() {
    if ($(this).val().trim() === '' && $(this).data('has-value')) $(this).val('••••••••••');
});

// ═══════════════════════════════════════════════════════════
//  WhatsApp Business API
// ═══════════════════════════════════════════════════════════

window.toggleWaGuide = function() {
    var body = $('#wa-setup-guide');
    var text = $('#wa-guide-toggle-text');
    var chevron = $('#wa-guide-chevron');
    if (body.is(':visible')) {
        body.slideUp(300);
        text.text('عرض الدليل');
        chevron.css('transform', 'rotate(0deg)');
    } else {
        body.slideDown(300);
        text.text('إخفاء الدليل');
        chevron.css('transform', 'rotate(180deg)');
    }
};

window.goToWaStep = function(step) {
    $('#wa-setup-guide .gc-step').removeClass('active');
    $('#wa-step-' + step).addClass('active');
    $('#wa-setup-guide .gc-step-dot').removeClass('active completed');
    for (var i = 1; i < step; i++) {
        $('#wa-setup-guide .gc-step-dot[data-step="w' + i + '"]').addClass('completed');
    }
    $('#wa-setup-guide .gc-step-dot[data-step="w' + step + '"]').addClass('active');
    $('#wa-setup-guide')[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
};

window.testWhatsappConnection = function() {
    var btn = document.getElementById('btn-test-whatsapp');
    var statusDiv = document.getElementById('wa-connection-status');
    if (!btn || !statusDiv) return;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> جاري الاختبار...';

    fetch('{$testWhatsappUrl}', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
        credentials: 'same-origin'
    })
    .then(function(r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
    .then(function(res) {
        if (res.success) {
            statusDiv.innerHTML =
                '<div class="sys-status-indicator connected"><i class="fa fa-check-circle fa-2x"></i>' +
                '<div><strong>الاتصال ناجح!</strong><p>' + (res.message || '') + '</p></div></div>';
        } else {
            statusDiv.innerHTML =
                '<div class="sys-status-indicator error"><i class="fa fa-times-circle fa-2x"></i>' +
                '<div><strong>فشل الاتصال</strong><p>' + (res.error || 'خطأ غير معروف') + '</p></div></div>';
        }
    })
    .catch(function(err) {
        statusDiv.innerHTML =
            '<div class="sys-status-indicator error"><i class="fa fa-times-circle fa-2x"></i>' +
            '<div><strong>خطأ في الاتصال</strong><p>' + err.message + '</p></div></div>';
    })
    .finally(function() {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-plug"></i> اختبار الاتصال';
    });
};

// Clear masked WA fields on focus
$('#wa_access_token').on('focus', function() {
    if ($(this).val() === '••••••••••') $(this).val('');
}).on('blur', function() {
    if ($(this).val().trim() === '') $(this).val('••••••••••');
});
$('#wa_app_secret').on('focus', function() {
    if ($(this).val() === '••••••••••') $(this).val('');
}).on('blur', function() {
    if ($(this).val().trim() === '') $(this).val('••••••••••');
});
JS;
$this->registerJs($js);
?>

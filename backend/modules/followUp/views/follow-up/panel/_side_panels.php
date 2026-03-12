<?php

use yii\helpers\Html;

/**
 * @var backend\modules\contracts\models\Contracts $contract
 * @var backend\modules\customers\models\Customers|null $customer
 */

// Feeling options
$feelings = [
    'متجاوب' => 'متجاوب',
    'متعاون' => 'متعاون',
    'مماطل' => 'مماطل',
    'غير متجاوب' => 'غير متجاوب',
    'رافض' => 'رافض',
    'غير متاح' => 'غير متاح',
];

// Connection goals
$goals = [
    1 => 'تحصيل',
    2 => 'مصالحة',
    3 => 'إنهاء عقد',
];
?>

<?php // ═══ CALL PANEL ═══ ?>
<div class="ocp-side-panel" id="panel-call" data-panel="call">
    <div class="ocp-side-panel__header">
        <span class="ocp-side-panel__title"><i class="fa fa-phone" style="color:var(--ocp-event-call)"></i> تسجيل اتصال</span>
        <button class="ocp-side-panel__close" onclick="OCP.closePanel()"><i class="fa fa-times"></i></button>
    </div>
    <div class="ocp-side-panel__body">
        <form id="form-call" onsubmit="return OCP.submitFollowUp(event, 'call')">
            <input type="hidden" name="contract_id" value="<?= $contract->id ?>">
            <input type="hidden" name="action_type" value="call">
            
            <div class="ocp-form-group">
                <label class="ocp-form-label ocp-form-label--required">هدف الاتصال</label>
                <select name="connection_goal" class="ocp-form-input ocp-form-select" required>
                    <option value="">اختر...</option>
                    <?php foreach ($goals as $val => $label): ?>
                    <option value="<?= $val ?>"><?= Html::encode($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="ocp-form-group">
                <label class="ocp-form-label ocp-form-label--required">انطباع العميل</label>
                <select name="feeling" class="ocp-form-input ocp-form-select" required>
                    <option value="">اختر...</option>
                    <?php foreach ($feelings as $val => $label): ?>
                    <option value="<?= $val ?>"><?= Html::encode($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="ocp-form-group">
                <label class="ocp-form-label ocp-form-label--required">موعد التذكير</label>
                <input type="date" name="reminder" class="ocp-form-input" required value="<?= date('Y-m-d', strtotime('+3 days')) ?>">
            </div>

            <div class="ocp-form-group">
                <label class="ocp-form-label">ملاحظات</label>
                <textarea name="notes" class="ocp-form-input ocp-form-textarea" placeholder="أضف ملاحظاتك هنا..."></textarea>
            </div>
        </form>
    </div>
    <div class="ocp-side-panel__footer">
        <button class="ocp-btn ocp-btn--primary" onclick="$('#form-call').submit()">
            <i class="fa fa-check"></i> حفظ الاتصال
        </button>
        <button class="ocp-btn ocp-btn--ghost" onclick="OCP.closePanel()">إلغاء</button>
    </div>
</div>

<?php // ═══ PROMISE PANEL ═══ ?>
<div class="ocp-side-panel" id="panel-promise" data-panel="promise">
    <div class="ocp-side-panel__header">
        <span class="ocp-side-panel__title"><i class="fa fa-handshake-o" style="color:var(--ocp-event-promise)"></i> تسجيل وعد دفع</span>
        <button class="ocp-side-panel__close" onclick="OCP.closePanel()"><i class="fa fa-times"></i></button>
    </div>
    <div class="ocp-side-panel__body">
        <form id="form-promise" onsubmit="return OCP.submitFollowUp(event, 'promise')">
            <input type="hidden" name="contract_id" value="<?= $contract->id ?>">
            <input type="hidden" name="action_type" value="promise">
            <input type="hidden" name="connection_goal" value="1">

            <div class="ocp-form-group">
                <label class="ocp-form-label ocp-form-label--required">تاريخ وعد الدفع</label>
                <input type="date" name="promise_to_pay_at" class="ocp-form-input" required 
                       min="<?= date('Y-m-d') ?>">
            </div>

            <div class="ocp-form-group">
                <label class="ocp-form-label">المبلغ الموعود</label>
                <input type="number" name="promise_amount" class="ocp-form-input" placeholder="اختياري" step="0.01" min="0">
            </div>

            <div class="ocp-form-group">
                <label class="ocp-form-label ocp-form-label--required">انطباع العميل</label>
                <select name="feeling" class="ocp-form-input ocp-form-select" required>
                    <option value="">اختر...</option>
                    <?php foreach ($feelings as $val => $label): ?>
                    <option value="<?= $val ?>"><?= Html::encode($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="ocp-form-group">
                <label class="ocp-form-label ocp-form-label--required">موعد التذكير (متابعة بعد الوعد)</label>
                <input type="date" name="reminder" class="ocp-form-input" required>
                <small style="color:var(--ocp-text-muted);font-size:11px">SLA: يجب المتابعة خلال 24 ساعة من موعد الوعد</small>
            </div>

            <div class="ocp-form-group">
                <label class="ocp-form-label">ملاحظات</label>
                <textarea name="notes" class="ocp-form-input ocp-form-textarea" placeholder="تفاصيل إضافية..."></textarea>
            </div>
        </form>
    </div>
    <div class="ocp-side-panel__footer">
        <button class="ocp-btn ocp-btn--primary" onclick="$('#form-promise').submit()">
            <i class="fa fa-check"></i> حفظ الوعد
        </button>
        <button class="ocp-btn ocp-btn--ghost" onclick="OCP.closePanel()">إلغاء</button>
    </div>
</div>

<?php // ═══ VISIT PANEL ═══ ?>
<div class="ocp-side-panel" id="panel-visit" data-panel="visit">
    <div class="ocp-side-panel__header">
        <span class="ocp-side-panel__title"><i class="fa fa-car" style="color:var(--ocp-event-visit)"></i> تسجيل زيارة</span>
        <button class="ocp-side-panel__close" onclick="OCP.closePanel()"><i class="fa fa-times"></i></button>
    </div>
    <div class="ocp-side-panel__body">
        <form id="form-visit" onsubmit="return OCP.submitFollowUp(event, 'visit')">
            <input type="hidden" name="contract_id" value="<?= $contract->id ?>">
            <input type="hidden" name="action_type" value="visit">
            <input type="hidden" name="connection_goal" value="1">

            <div class="ocp-form-group">
                <label class="ocp-form-label ocp-form-label--required">نتيجة الزيارة</label>
                <select name="feeling" class="ocp-form-input ocp-form-select" required>
                    <option value="">اختر...</option>
                    <option value="تم الدفع">تم الدفع أثناء الزيارة</option>
                    <option value="وعد بالدفع">وعد بالدفع</option>
                    <option value="غير متواجد">العميل غير متواجد</option>
                    <option value="رفض">رفض الدفع</option>
                    <option value="عنوان خاطئ">عنوان غير صحيح</option>
                </select>
            </div>

            <div class="ocp-form-group">
                <label class="ocp-form-label ocp-form-label--required">موعد التذكير</label>
                <input type="date" name="reminder" class="ocp-form-input" required value="<?= date('Y-m-d', strtotime('+2 days')) ?>">
            </div>

            <div class="ocp-form-group">
                <label class="ocp-form-label">ملاحظات</label>
                <textarea name="notes" class="ocp-form-input ocp-form-textarea" placeholder="تفاصيل الزيارة..."></textarea>
            </div>
        </form>
    </div>
    <div class="ocp-side-panel__footer">
        <button class="ocp-btn ocp-btn--primary" onclick="$('#form-visit').submit()">
            <i class="fa fa-check"></i> حفظ الزيارة
        </button>
        <button class="ocp-btn ocp-btn--ghost" onclick="OCP.closePanel()">إلغاء</button>
    </div>
</div>

<?php // ═══ SMS PANEL ═══ ?>
<div class="ocp-side-panel" id="panel-sms" data-panel="sms">
    <div class="ocp-side-panel__header">
        <span class="ocp-side-panel__title"><i class="fa fa-comment" style="color:var(--ocp-event-sms)"></i> إرسال تذكير</span>
        <button class="ocp-side-panel__close" onclick="OCP.closePanel()"><i class="fa fa-times"></i></button>
    </div>
    <div class="ocp-side-panel__body">
        <form id="form-sms" onsubmit="return OCP.submitSms(event)">
            <input type="hidden" name="contract_id" value="<?= $contract->id ?>">
            
            <div class="ocp-form-group">
                <label class="ocp-form-label">رقم الهاتف</label>
                <input type="text" name="phone" class="ocp-form-input" dir="ltr" 
                       value="<?= Html::encode($customer ? \backend\helpers\PhoneHelper::toLocal($customer->primary_phone_number) : '') ?>"
                       placeholder="07XXXXXXXX">
            </div>

            <div class="ocp-form-group">
                <label class="ocp-form-label ocp-form-label--required">نص الرسالة</label>
                <textarea name="message" class="ocp-form-input ocp-form-textarea" required rows="4"
                    placeholder="نص التذكير...">عزيزي العميل، نود تذكيركم بالقسط المستحق. يرجى المراجعة أو الاتصال بنا.</textarea>
            </div>
        </form>
    </div>
    <div class="ocp-side-panel__footer">
        <button class="ocp-btn ocp-btn--primary" onclick="$('#form-sms').submit()">
            <i class="fa fa-paper-plane"></i> إرسال
        </button>
        <button class="ocp-btn ocp-btn--ghost" onclick="OCP.closePanel()">إلغاء</button>
    </div>
</div>

<?php // ═══ LEGAL ESCALATION PANEL (Governance) ═══ ?>
<div class="ocp-side-panel" id="panel-legal" data-panel="legal">
    <div class="ocp-side-panel__header">
        <span class="ocp-side-panel__title"><i class="fa fa-gavel" style="color:var(--ocp-event-legal)"></i> تحويل للقضائي</span>
        <button class="ocp-side-panel__close" onclick="OCP.closePanel()"><i class="fa fa-times"></i></button>
    </div>
    <div class="ocp-side-panel__body">
        <div class="ocp-alert ocp-alert--critical" style="margin-bottom:var(--ocp-space-lg)">
            <div class="ocp-alert__icon"><i class="fa fa-exclamation-triangle"></i></div>
            <div class="ocp-alert__body">
                <div class="ocp-alert__title">إجراء لا رجعة فيه</div>
                <div class="ocp-alert__desc">تحويل العقد للقضائي يعني بدء الإجراءات القانونية. تأكد من استنفاد جميع الخيارات الأخرى.</div>
            </div>
        </div>

        <form id="form-legal" onsubmit="return OCP.submitEscalation(event)">
            <input type="hidden" name="contract_id" value="<?= $contract->id ?>">
            <input type="hidden" name="escalation_target" value="legal">
            
            <div class="ocp-form-group">
                <label class="ocp-form-label ocp-form-label--required">سبب التصعيد</label>
                <textarea name="escalation_reason" class="ocp-form-input ocp-form-textarea" required
                    placeholder="اشرح لماذا يجب تحويل هذا العقد للقضائي..." rows="3"></textarea>
            </div>

            <div class="ocp-form-group">
                <label class="ocp-form-label ocp-form-label--required">نوع التصعيد</label>
                <select name="escalation_type" class="ocp-form-input ocp-form-select" required>
                    <option value="">اختر...</option>
                    <option value="field_collection">تحصيل ميداني</option>
                    <option value="warning">إنذار رسمي</option>
                    <option value="legal">إجراء قضائي</option>
                </select>
            </div>

            <div class="ocp-form-group">
                <label class="ocp-form-label">
                    <input type="checkbox" name="requires_manager_approval" value="1" checked>
                    يتطلب موافقة المدير
                </label>
            </div>

            <div class="ocp-form-group">
                <label class="ocp-form-label">ملاحظات إضافية</label>
                <textarea name="notes" class="ocp-form-input ocp-form-textarea" placeholder="أي تفاصيل إضافية..."></textarea>
            </div>
        </form>
    </div>
    <div class="ocp-side-panel__footer">
        <button class="ocp-btn ocp-btn--danger" onclick="$('#form-legal').submit()">
            <i class="fa fa-gavel"></i> تأكيد التصعيد
        </button>
        <button class="ocp-btn ocp-btn--ghost" onclick="OCP.closePanel()">إلغاء</button>
    </div>
</div>

<?php // ═══ NOTE PANEL ═══ ?>
<div class="ocp-side-panel" id="panel-note" data-panel="note">
    <div class="ocp-side-panel__header">
        <span class="ocp-side-panel__title"><i class="fa fa-sticky-note" style="color:var(--ocp-event-note)"></i> إضافة ملاحظة</span>
        <button class="ocp-side-panel__close" onclick="OCP.closePanel()"><i class="fa fa-times"></i></button>
    </div>
    <div class="ocp-side-panel__body">
        <form id="form-note" onsubmit="return OCP.submitFollowUp(event, 'note')">
            <input type="hidden" name="contract_id" value="<?= $contract->id ?>">
            <input type="hidden" name="action_type" value="note">
            <input type="hidden" name="connection_goal" value="1">
            <input type="hidden" name="feeling" value="ملاحظة">

            <div class="ocp-form-group">
                <label class="ocp-form-label ocp-form-label--required">الملاحظة</label>
                <textarea name="notes" class="ocp-form-input ocp-form-textarea" required rows="5"
                    placeholder="أضف ملاحظتك هنا..."></textarea>
            </div>

            <div class="ocp-form-group">
                <label class="ocp-form-label">موعد التذكير</label>
                <input type="date" name="reminder" class="ocp-form-input" value="<?= date('Y-m-d', strtotime('+7 days')) ?>">
            </div>
        </form>
    </div>
    <div class="ocp-side-panel__footer">
        <button class="ocp-btn ocp-btn--primary" onclick="$('#form-note').submit()">
            <i class="fa fa-check"></i> حفظ الملاحظة
        </button>
        <button class="ocp-btn ocp-btn--ghost" onclick="OCP.closePanel()">إلغاء</button>
    </div>
</div>

<?php // ═══ MANAGER REVIEW PANEL ═══ ?>
<div class="ocp-side-panel" id="panel-review" data-panel="review">
    <div class="ocp-side-panel__header">
        <span class="ocp-side-panel__title"><i class="fa fa-user-circle" style="color:var(--ocp-primary)"></i> طلب مراجعة مدير</span>
        <button class="ocp-side-panel__close" onclick="OCP.closePanel()"><i class="fa fa-times"></i></button>
    </div>
    <div class="ocp-side-panel__body">
        <form id="form-review" onsubmit="return OCP.submitFollowUp(event, 'review')">
            <input type="hidden" name="contract_id" value="<?= $contract->id ?>">
            <input type="hidden" name="action_type" value="review">
            <input type="hidden" name="connection_goal" value="1">
            <input type="hidden" name="feeling" value="طلب مراجعة">

            <div class="ocp-form-group">
                <label class="ocp-form-label ocp-form-label--required">سبب طلب المراجعة</label>
                <textarea name="notes" class="ocp-form-input ocp-form-textarea" required rows="4"
                    placeholder="اشرح لماذا يحتاج هذا العقد مراجعة المدير..."></textarea>
            </div>

            <div class="ocp-form-group">
                <label class="ocp-form-label">موعد التذكير</label>
                <input type="date" name="reminder" class="ocp-form-input" value="<?= date('Y-m-d', strtotime('+1 day')) ?>">
            </div>
        </form>
    </div>
    <div class="ocp-side-panel__footer">
        <button class="ocp-btn ocp-btn--primary" onclick="$('#form-review').submit()">
            <i class="fa fa-paper-plane"></i> إرسال الطلب
        </button>
        <button class="ocp-btn ocp-btn--ghost" onclick="OCP.closePanel()">إلغاء</button>
    </div>
</div>

<?php // ═══ CREATE TASK PANEL ═══ ?>
<div class="ocp-side-panel" id="panel-create-task" data-panel="create-task">
    <div class="ocp-side-panel__header">
        <span class="ocp-side-panel__title"><i class="fa fa-plus-circle" style="color:var(--ocp-primary)"></i> إنشاء مهمة جديدة</span>
        <button class="ocp-side-panel__close" onclick="OCP.closePanel()"><i class="fa fa-times"></i></button>
    </div>
    <div class="ocp-side-panel__body">
        <form id="form-create-task" onsubmit="return OCP.submitTask(event)">
            <input type="hidden" name="contract_id" value="<?= $contract->id ?>">
            
            <div class="ocp-form-group">
                <label class="ocp-form-label ocp-form-label--required">عنوان المهمة</label>
                <input type="text" name="title" class="ocp-form-input" required placeholder="عنوان مختصر للمهمة...">
            </div>

            <div class="ocp-form-group">
                <label class="ocp-form-label">الوصف</label>
                <textarea name="description" class="ocp-form-input ocp-form-textarea" placeholder="تفاصيل إضافية..."></textarea>
            </div>

            <div class="ocp-form-group">
                <label class="ocp-form-label ocp-form-label--required">المرحلة</label>
                <select name="stage" id="task-stage" class="ocp-form-input ocp-form-select" required>
                    <option value="new">جديد/مفتوح</option>
                    <option value="first_call">اتصال أول</option>
                    <option value="promise">وعد دفع</option>
                    <option value="post_promise">متابعة بعد وعد</option>
                    <option value="late">متأخر</option>
                    <option value="escalation">تصعيد</option>
                    <option value="legal">قضائي</option>
                </select>
            </div>

            <div class="ocp-form-group">
                <label class="ocp-form-label">الأولوية</label>
                <select name="priority" class="ocp-form-input ocp-form-select">
                    <option value="low">منخفضة</option>
                    <option value="medium" selected>متوسطة</option>
                    <option value="high">مرتفعة</option>
                    <option value="critical">حرجة</option>
                </select>
            </div>

            <div class="ocp-form-group">
                <label class="ocp-form-label">تاريخ الاستحقاق</label>
                <input type="date" name="due_date" class="ocp-form-input" value="<?= date('Y-m-d', strtotime('+3 days')) ?>">
            </div>

            <div class="ocp-form-group">
                <label class="ocp-form-label">نوع الإجراء</label>
                <select name="action_type" class="ocp-form-input ocp-form-select">
                    <option value="">غير محدد</option>
                    <option value="call">اتصال</option>
                    <option value="promise">وعد دفع</option>
                    <option value="visit">زيارة</option>
                    <option value="sms">رسالة</option>
                    <option value="legal">قضائي</option>
                    <option value="review">مراجعة</option>
                </select>
            </div>
        </form>
    </div>
    <div class="ocp-side-panel__footer">
        <button class="ocp-btn ocp-btn--primary" onclick="$('#form-create-task').submit()">
            <i class="fa fa-check"></i> إنشاء المهمة
        </button>
        <button class="ocp-btn ocp-btn--ghost" onclick="OCP.closePanel()">إلغاء</button>
    </div>
</div>

<?php // ═══ ESCALATION REASON MODAL (for Kanban move to escalation/legal) ═══ ?>
<div class="ocp-side-panel" id="panel-escalation-reason" data-panel="escalation-reason">
    <div class="ocp-side-panel__header">
        <span class="ocp-side-panel__title"><i class="fa fa-exclamation-triangle" style="color:var(--ocp-danger)"></i> سبب التصعيد (إلزامي)</span>
        <button class="ocp-side-panel__close" onclick="OCP.cancelKanbanMove()"><i class="fa fa-times"></i></button>
    </div>
    <div class="ocp-side-panel__body">
        <form id="form-escalation-reason">
            <input type="hidden" name="task_id" id="escalation-task-id" value="">
            <input type="hidden" name="target_stage" id="escalation-target-stage" value="">
            
            <div class="ocp-form-group">
                <label class="ocp-form-label ocp-form-label--required">سبب التصعيد</label>
                <textarea name="reason" class="ocp-form-input ocp-form-textarea" required rows="3"
                    placeholder="يجب توضيح سبب التصعيد..."></textarea>
            </div>

            <div class="ocp-form-group">
                <label class="ocp-form-label ocp-form-label--required">نوع التصعيد</label>
                <select name="type" class="ocp-form-input ocp-form-select" required>
                    <option value="">اختر...</option>
                    <option value="field_collection">تحصيل ميداني</option>
                    <option value="warning">إنذار</option>
                    <option value="legal">قضائي</option>
                </select>
            </div>
        </form>
    </div>
    <div class="ocp-side-panel__footer">
        <button class="ocp-btn ocp-btn--danger" onclick="OCP.confirmKanbanEscalation()">
            <i class="fa fa-check"></i> تأكيد التصعيد
        </button>
        <button class="ocp-btn ocp-btn--ghost" onclick="OCP.cancelKanbanMove()">إلغاء</button>
    </div>
</div>

<?php // ═══ FREEZE PANEL ═══ ?>
<div class="ocp-side-panel" id="panel-freeze" data-panel="freeze">
    <div class="ocp-side-panel__header">
        <span class="ocp-side-panel__title"><i class="fa fa-pause-circle" style="color:#3B82F6"></i> تجميد المتابعة</span>
        <button class="ocp-side-panel__close" onclick="OCP.closePanel()"><i class="fa fa-times"></i></button>
    </div>
    <div class="ocp-side-panel__body">
        <div class="ocp-alert ocp-alert--warning" style="margin-bottom:var(--ocp-space-lg)">
            <div class="ocp-alert__icon"><i class="fa fa-info-circle"></i></div>
            <div class="ocp-alert__body">
                <div class="ocp-alert__title">تجميد المتابعة</div>
                <div class="ocp-alert__desc">سيتم إيقاف المتابعة مؤقتاً لهذا العقد. لن يظهر في تقارير المتابعة حتى يتم إلغاء التجميد.</div>
            </div>
        </div>

        <form id="form-freeze" onsubmit="return OCP.submitFreeze(event)">
            <input type="hidden" name="contract_id" value="<?= $contract->id ?>">
            
            <div class="ocp-form-group">
                <label class="ocp-form-label ocp-form-label--required">سبب التجميد</label>
                <textarea name="reason" class="ocp-form-input ocp-form-textarea" required
                    placeholder="لماذا تريد تجميد المتابعة..."></textarea>
            </div>
        </form>
    </div>
    <div class="ocp-side-panel__footer">
        <button class="ocp-btn ocp-btn--primary" onclick="$('#form-freeze').submit()">
            <i class="fa fa-pause"></i> تجميد
        </button>
        <button class="ocp-btn ocp-btn--ghost" onclick="OCP.closePanel()">إلغاء</button>
    </div>
</div>

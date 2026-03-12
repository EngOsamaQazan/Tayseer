<style>
    ul#w2.nav.nav.tabs {
        border-bottom: 6px solid #ddd;
    }
</style>

<?php

use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
use yii\bootstrap\Tabs;
use yii\helpers\Url;
use common\models\CourseActivitySectionEnabled;

$cid = null;

if (null != Yii::$app->getRequest()->getQueryParam('cid')) {
    $cid = Yii::$app->getRequest()->getQueryParam('cid');
} elseif (null != Yii::$app->getRequest()->getQueryParam('id')) {
    $cid = Yii::$app->getRequest()->getQueryParam('id');
}


$homeActivate = $contentActivate = $userActivate = $discussionActivate = $assessmentActivate = $communicationActive = $registration_template_active = $reviews_active = false;

$content = ['lecture-question','assessment', 'course-content', 'workshop', 'ticket', 'certificate', 'Reported Content', 'Additional Material', 'Recorded Material', 'participant-content-report'];
$userMenu = ['student-registration', 'instructor', 'cash-receipt', 'participant', 'user', 'invited-user', 'waiting-list', 'lead', 'need-approval', 'unconfirmed-payment'];
$communicationMenu = ['membership-subscription', 'activity-subscription', 'activity-reminder', 'default-messages', 'email-messages', 'sms-messages'];
$registrationTemplateMenu = ['activity-setup', 'registration-template'];

$discussionMenu = ['discussions', 'comment'];
$assessmentMenu = ['question', 'case-scenario', 'exam-question', 'exam', 'assignment'];
$controller = Yii::$app->controller->id;
$action = Yii::$app->controller->action->id;


if (in_array($controller, $content)) {
    $contentActivate = true;
} elseif (in_array($controller, $userMenu)) {
    $userActivate = true;
} elseif (in_array($controller, $registrationTemplateMenu)) {
    $registration_template_active = true;
} elseif (in_array($controller, $discussionMenu)) {
    $discussionActivate = true;
} elseif (in_array($controller, $communicationMenu)) {
    $communicationActive = true;
} elseif (in_array($controller, $assessmentMenu)) {
    $assessmentActivate = true;
} elseif ($controller == "course" && $action != 'reviews') {
    $homeActivate = true;
} elseif ($controller == "lecture" || $controller == "session" || $controller == "participant-content-report") {
    $contentActivate = true;
}

if ($action == "reviews") {
    $reviews_active = true;
}

NavBar::begin([
    //'brandLabel' => $name,
    'brandUrl' => Yii::$app->homeUrl,
    'options' => [
        'class' => 'navbar-inverse',
        //'class' => 'navbar-nav',
    ],
]);
//var_dump($communicationActive);exit;
/*   $menuItems = [
  ['label' =>\Yii::t('app','Session') ,'active' => $controllers=='session'?$contentActivate:'', 'icon' => 'dashboard', 'url' => ['/session'],],
  ['label' =>\Yii::t('app','Lecture') ,'active' => $controllers=='lecture'  || $controllers=='lecture-question' ?$contentActivate:'', 'icon' => 'circle-o', 'url' => ['/lecture'],],
  //['label' => 'Question','active' => $controllers=='question'|| $controllers=='case-scenario' ?$contentActivate:'', 'icon' => 'dashboard', 'url' => ['/question'],],
  //['label' => 'case-scenario','active' => $controllers=='case-scenario'?$contentActivate:'', 'icon' => 'dashboard', 'url' => ['/case-scenario'],],
  // ['label' => \Yii::t('app','Exam') ,'active' => $controllers=='exam' || $controllers=='exam-question' ?$contentActivate:'', 'icon' => 'circle-o', 'url' => ['/exam'],],
  //['label' => \Yii::t('app','Manual Exam'), 'icon' => 'circle-o', 'url' => ['/manual-exam'],],
  //['label' => 'Assignment','active' => $controllers=='assignment'?$contentActivate:'', 'icon' => 'dashboard', 'url' => ['/assignment'],],
  ['label' => 'Workshop','active' => $controllers=='workshop'?$contentActivate:'', 'icon' => 'dashboard', 'url' => ['/workshop'],],
  ['label' => 'Ticket','active' => $controllers=='ticket'?$contentActivate:'', 'icon' => 'dashboard', 'url' => ['/ticket'],],
  ['label' => 'Certificates','active' => $controllers=='certificate'?$contentActivate:'', 'icon' => 'dashboard', 'url' => ['/certificate'],],
  ['label' => 'Assessment Content','active' => $controllers=='assessment'?$contentActivate:'', 'icon' => 'dashboard', 'url' => ['/assessment'],],

  ];
 */

$menuItems = [
    ['label' => \Yii::t('app', 'Content'), 'active' => ($controller == 'course-content' || $controller == 'lecture' || $controller == 'session' || $controller == 'assessment' || $controller == 'participant-content-report') ? $contentActivate : '', 'icon' => 'dashboard', 'url' => ['/course-content?cid=' . $cid],],
    ['label' => 'Workshop', 'active' => $controller == 'workshop' ? $contentActivate : '', 'icon' => 'dashboard', 'url' => ['/workshop?cid=' . $cid],],
    ['label' => 'Ticket', 'active' => $controller == 'ticket' ? $contentActivate : '', 'icon' => 'dashboard', 'url' => ['/ticket?cid=' . $cid],],
    ['label' => 'Additional Material', 'active' => $controller == '' ? $contentActivate : '', 'icon' => 'dashboard', 'url' => ['/'],],
    ['label' => 'Recorded Material', 'active' => $controller == '' ? $contentActivate : '', 'icon' => 'dashboard', 'url' => ['/'],],
    ['label' => 'Certificates', 'active' => $controller == 'certificate' ? $contentActivate : '', 'icon' => 'dashboard', 'url' => ['/certificate?cid=' . $cid],],
    ['label' => 'Reported Content', 'active' => $controller == '' ? $contentActivate : '', 'icon' => 'dashboard', 'url' => ['/'],],
//        ['label' => 'Practical Session','active' => $controllers==''?$contentActivate:'', 'icon' => 'dashboard', 'url' => ['/'],],
];
$userItems = [
    ['label' => \Yii::t('app', 'Participant'), 'active' => $controller == 'participant' ? $userActivate : '', 'icon' => 'dashboard', 'url' => ['/participant?cid=' . $cid],],
    ['label' => \Yii::t('app', 'Instructor'), 'active' => $controller == 'instructor' ? $userActivate : '', 'icon' => 'dashboard', 'url' => ['/instructors?cid=' . $cid],],
    ['label' => \Yii::t('app', 'Cash Receipt'), 'active' => $controller == 'cash-receipt' ? $userActivate : '', 'icon' => 'dashboard', 'url' => ['/cash-receipt?cid=' . $cid],],
    ['label' => \Yii::t('app', 'Invited User'), 'active' => $controller == 'invited-user' ? $userActivate : '', 'icon' => 'dashboard', 'url' => ['/invited-user?cid=' . $cid],],
    ['label' => \Yii::t('app', 'Waiting List'), 'active' => $controller == 'waiting-list' ? $userActivate : '', 'icon' => 'dashboard', 'url' => ['/waiting-list?cid=' . $cid],],
    ['label' => \Yii::t('app', 'Lead/Deal'), 'active' => $controller == 'lead' ? $userActivate : '', 'icon' => 'dashboard', 'url' => ['/lead?cid=' . $cid],],
    ['label' => \Yii::t('app', 'Need Approval'), 'active' => $controller == 'need-approval' ? $userActivate : '', 'icon' => 'dashboard', 'url' => ['/need-approval?cid=' . $cid],],
    ['label' => \Yii::t('app', 'Unconfirmed Payment'), 'active' => $controller == 'unconfirmed-payment' ? $userActivate : '', 'icon' => 'dashboard', 'url' => ['/unconfirmed-payment?cid=' . $cid],],
];
$registrationTemplateItem = [
    ['label' => \Yii::t('app', 'Activity Setup'), 'active' => ($controller == 'registration-template' && $action == 'activity-setup') ? $registration_template_active : '', 'icon' => 'dashboard', 'url' => ['/registration-template/activity-setup', 'cid' => $cid]],
    ['label' => \Yii::t('app', 'Badge Setup'), 'active' => ($controller == 'registration-template' && $action == 'badge-setup') ? $registration_template_active : '', 'icon' => 'dashboard', 'url' => ['/registration-template/badge-setup', 'cid' => $cid]],
    ['label' => \Yii::t('app', 'External Registration Page setup'), 'active' => ($controller == 'registration-template' && $action == 'index') ? $registration_template_active : '', 'icon' => 'dashboard', 'url' => ['/registration-template?cid=' . $cid]],
];


$communicationItems = [
    ['label' => \Yii::t('app', 'Emails'), 'active' => $controller == 'email-messages' ? $communicationActive : '', 'icon' => 'dashboard', 'url' => Url::to(['/communication/email-messages', 'cid' => $cid]),],
    ['label' => \Yii::t('app', 'SMS'), 'active' => $controller == 'sms-messages' ? $communicationActive : '', 'icon' => 'dashboard', 'url' => Url::to(['/communication/sms-messages', 'cid' => $cid]),],
    //['label' => \Yii::t('app', 'Membership Subscription'), 'active' => $controllers == 'membership-subscription' ? $communicationActive : '', 'icon' => 'dashboard', 'url' => ['/membership-subscription?cid='.$cid],],
    //['label' => \Yii::t('app', 'Activity Subscription'), 'active' => $controllers == 'activity-subscription' ? $communicationActive : '', 'icon' => 'dashboard', 'url' => ['/activity-subscription'],],
    //['label' => \Yii::t('app', 'Activity Reminder'), 'active' => $controllers == 'activity-reminder' ? $communicationActive : '', 'icon' => 'dashboard', 'url' => ['/activity-reminder'],],
    //['label' => \Yii::t('app', 'Messages in Action Menu'), 'active' => $controllers == 'communication/default-messages' ? $communicationActive : '', 'icon' => 'dashboard', 'url' => ['/communication/default-messages?cid='.$cid],],
];
$discussionsItems = [
    ['label' => \Yii::t('app', 'Lecture discussions'), 'active' => $controller == 'discussions' || $controller == 'comment' ? $discussionActivate : '', 'icon' => 'dashboard', 'url' => ['/discussions'],],
];
$assessmentItems = [
    ['label' => 'Question', 'active' => ($controller == 'question' || $controller == 'case-scenario' || $controller == 'lecture-question') ? $assessmentActivate : '', 'icon' => 'dashboard', 'url' => ['/question?cid=' . $cid],],
    ['label' => \Yii::t('app', 'Exam'), 'active' => $controller == 'exam' || $controller == 'exam-question' ? $assessmentActivate : '', 'icon' => 'circle-o', 'url' => ['/exam?cid=' . $cid],],
    ['label' => 'Assignment', 'active' => ($controller == 'assignment') ? $assessmentActivate : '', 'icon' => 'dashboard', 'url' => ['/assignment?cid=' . $cid],],
];

$arrayItems = [
    [
        'label' => Yii::t('app', 'Home'),
        'url' => ["/course/view?id=$cid"],
        'active' => $homeActivate,
    ],
    [
        'label' => 'Content',
        'url' => ["/course-content?cid=$cid"],
        'content' => Nav::widget([
            'options' => ['class' => 'navbar-nav navbar-inverse'],
            'items' => $menuItems,
        ]),
        'active' => $contentActivate,
    ],
    [
        'label' => Yii::t('app', 'Assessment Center'),
        'url' => ["/question?cid=$cid"],
        'content' => Nav::widget([
            'options' => ['class' => 'navbar-nav navbar-inverse '],
            'items' => $assessmentItems,
        ]),
        'active' => $assessmentActivate,
        //'url' => '',
    ],
    [
        'label' => Yii::t('app', 'User'),
        'url' => ["/participant?cid=$cid"],
        'content' => Nav::widget([
            'options' => ['class' => 'navbar-nav navbar-inverse'],
            'items' => $userItems,
        ]),
        'active' => $userActivate,
    ],
    [
        'label' => Yii::t('app', 'Communication'),
        'url' => Url::to(['/communication/email-messages', 'cid' => $cid]),
        'content' => Nav::widget([
            'options' => ['class' => 'navbar-nav navbar-inverse'],
            'items' => $communicationItems,
        ]),
        'active' => $communicationActive,
    ],
    [
        'label' => Yii::t('app', 'Reports')
        //'url' => '',
    ],
    [
        'label' => Yii::t('app', 'Appearance'), //
        'url' => ['/registration-template/activity-setup', 'cid' => $cid],
        'content' => Nav::widget([
            'options' => ['class' => 'navbar-nav navbar-inverse'],
            'items' => $registrationTemplateItem,
        ]),
        'active' => $registration_template_active,
    ],
    [
        'label' => Yii::t('app', 'Review'),
        'url' => ["course/reviews?cid=$cid"],
        'content' => Nav::widget([
            'options' => ['class' => 'navbar-nav navbar-inverse'],
        ]),
        'active' => $reviews_active,
    ],
];

if (!CourseActivitySectionEnabled::find()->where(['vma_course_id' => $cid, 'vma_activity_section_enabled_id' => CourseActivitySectionEnabled::ASSESSMENT_CENTER, 'is_deleted' => 0])->exists()) {
    unset($arrayItems[2]);
}
echo Tabs::widget([
    'items' => $arrayItems
]);
NavBar::end();
?>
			




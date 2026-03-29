<?php

namespace common\services;

use Yii;
use yii\base\Component;
use backend\modules\notification\models\Notification;
use yii\db\Query;

/**
 * Centralized notification service.
 *
 * Usage:
 *   Yii::$app->notificationService->send($recipientId, Notification::GENERAL, 'Title', '/some/url');
 *   Yii::$app->notificationService->sendToRole(['مدير'], Notification::GENERAL, 'Title', '/url');
 *   Yii::$app->notificationService->sendToCategory(['manager', 'sales_employee'], ...);
 */
class NotificationService extends Component
{
    /**
     * Send a notification to a single recipient.
     */
    public function send(
        int $recipientId,
        int $type,
        string $title,
        string $href = '',
        string $body = '',
        ?int $senderId = null,
        ?string $entityType = null,
        ?int $entityId = null,
        int $priority = Notification::PRIORITY_NORMAL
    ): bool {
        $actualSenderId = $senderId ?? (Yii::$app->user->isGuest ? 0 : Yii::$app->user->id);

        if ((int)$recipientId === (int)$actualSenderId) {
            return true;
        }

        $model = new Notification();
        $model->sender_id = $actualSenderId;
        $model->recipient_id = $recipientId;
        $model->type_of_notification = $type;
        $model->title_html = $title;
        $model->body_html = $body;
        $model->href = $href;
        $model->is_unread = 1;
        $model->is_hidden = 0;
        $model->created_time = time();
        $model->priority = $priority;

        if ($entityType) {
            $model->entity_type = $entityType;
            $model->entity_id = $entityId;
        }

        if (!$model->save()) {
            Yii::warning('Failed to save notification: ' . json_encode($model->errors), 'notification');
            return false;
        }
        return true;
    }

    /**
     * Send notification to all users who hold any of the given RBAC roles.
     */
    public function sendToRole(
        array $roles,
        int $type,
        string $title,
        string $href = '',
        string $body = '',
        ?int $senderId = null,
        ?string $entityType = null,
        ?int $entityId = null
    ): int {
        $actualSenderId = $senderId ?? (Yii::$app->user->isGuest ? 0 : Yii::$app->user->id);

        $recipientIds = (new Query())
            ->select('user_id')
            ->from('{{%auth_assignment}}')
            ->where(['in', 'item_name', $roles])
            ->column();
        $recipientIds = array_unique($recipientIds);

        $sent = 0;
        foreach ($recipientIds as $rid) {
            if ($this->send((int)$rid, $type, $title, $href, $body, $actualSenderId, $entityType, $entityId)) {
                $sent++;
            }
        }
        return $sent;
    }

    /**
     * Send notification to all users mapped to any of the given category slugs.
     */
    public function sendToCategory(
        array $categorySlugs,
        int $type,
        string $title,
        string $href = '',
        string $body = '',
        ?int $senderId = null,
        ?string $entityType = null,
        ?int $entityId = null
    ): int {
        $recipientIds = [];
        foreach ($categorySlugs as $slug) {
            $catId = (new Query())
                ->select('id')
                ->from('{{%user_categories}}')
                ->where(['slug' => $slug, 'is_active' => 1])
                ->scalar();
            if (!$catId) continue;

            $ids = (new Query())
                ->select('user_id')
                ->from('{{%user_category_map}}')
                ->where(['category_id' => $catId])
                ->column();
            $recipientIds = array_merge($recipientIds, $ids);
        }
        $recipientIds = array_unique($recipientIds);

        $actualSenderId = $senderId ?? (Yii::$app->user->isGuest ? 0 : Yii::$app->user->id);
        $sent = 0;
        foreach ($recipientIds as $rid) {
            if ($this->send((int)$rid, $type, $title, $href, $body, $actualSenderId, $entityType, $entityId)) {
                $sent++;
            }
        }
        return $sent;
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(int $notificationId): bool
    {
        $model = Notification::findOne($notificationId);
        if (!$model) return false;

        $model->is_unread = 0;
        $model->read_at = time();
        return $model->save(false);
    }

    /**
     * Mark all notifications as read for a user.
     */
    public function markAllAsRead(int $userId): int
    {
        return Notification::updateAll(
            ['is_unread' => 0, 'read_at' => time()],
            ['recipient_id' => $userId, 'is_unread' => 1]
        );
    }

    /**
     * Hide a notification.
     */
    public function hide(int $notificationId): bool
    {
        $model = Notification::findOne($notificationId);
        if (!$model) return false;

        $model->is_hidden = 1;
        return $model->save(false);
    }

    /**
     * Get unread count for a user.
     */
    public function getUnreadCount(int $userId): int
    {
        return Notification::unreadCountForUser($userId);
    }

    /* ═══ Backward compatibility wrappers ═══ */

    /**
     * @deprecated Use send() instead
     */
    public function add($href, $type_of_notification, $title_html, $body_html, $sender_id, $recipient_id)
    {
        return $this->send((int)$recipient_id, (int)$type_of_notification, $title_html, $href, $body_html, $sender_id ? (int)$sender_id : null);
    }

    /**
     * @deprecated Use sendToRole() instead
     */
    public function sendByRule($rule, $href, $type_of_notification, $title_html, $body_html, $sender_id)
    {
        return $this->sendToRole($rule, (int)$type_of_notification, $title_html, $href, $body_html, $sender_id ? (int)$sender_id : null);
    }

    /**
     * @deprecated Use markAsRead() instead
     */
    public function setReaded($id)
    {
        return $this->markAsRead((int)$id);
    }

    /**
     * @deprecated Use markAllAsRead() instead
     */
    public function setReadedAll($userId = null)
    {
        $uid = $userId ?: Yii::$app->user->id;
        return $this->markAllAsRead((int)$uid);
    }

    /**
     * @deprecated Use hide() instead
     */
    public function setHidden($id)
    {
        return $this->hide((int)$id);
    }
}

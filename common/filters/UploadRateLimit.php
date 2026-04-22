<?php

namespace common\filters;

use Yii;
use yii\base\ActionFilter;
use yii\web\TooManyRequestsHttpException;

/**
 * Phase 7 / M7.1 — Rate-limiter for file-upload endpoints.
 *
 * The unified MediaController::actionUpload (and any controller that
 * mounts this filter) is gated to `$limit` requests per `$window`
 * seconds per authenticated user. Anonymous traffic is bucketed by
 * client IP so a leaked endpoint cannot be abused via a single API
 * token shared across machines.
 *
 * Storage:
 *   • Uses Yii::$app->cache (whatever driver the project is wired to —
 *     APCu in tiny installs, Redis in production). The bucket key is
 *     derived from "user:{id}|ip:{ip}|action:{route}", so multiple
 *     upload-flavoured controllers can each get their own quota.
 *   • Tokens are sliding-window: the cache TTL itself is the window,
 *     and the value is a simple counter incremented per hit. This is
 *     deliberately sloppy — the goal is "stop a runaway browser tab",
 *     not "DDoS protection". Real abuse should also be blocked at the
 *     reverse-proxy layer.
 *
 * Usage:
 *
 *   public function behaviors()
 *   {
 *       return array_merge(parent::behaviors(), [
 *           'rateLimit' => [
 *               'class'   => \common\filters\UploadRateLimit::class,
 *               'only'    => ['upload'],
 *               'limit'   => 30,
 *               'window'  => 60,
 *           ],
 *       ]);
 *   }
 */
class UploadRateLimit extends ActionFilter
{
    /** Maximum number of upload requests allowed inside one window. */
    public int $limit = 30;

    /** Window length in seconds (default: 1 minute). */
    public int $window = 60;

    /** Optional logical bucket name; defaults to the action route. */
    public ?string $bucket = null;

    /**
     * Header name to surface the remaining quota to the client. The
     * unified MediaUploader does not consume it today but the value
     * is useful in browser DevTools and for any future progressive
     * back-off in JS.
     */
    public string $remainingHeader = 'X-Upload-Remaining';

    public function beforeAction($action)
    {
        $cache = Yii::$app->cache ?? null;
        if ($cache === null) {
            // No cache configured — skip silently rather than 500-ing.
            // Production setups always have a cache; dev tooling sometimes does not.
            return parent::beforeAction($action);
        }

        $bucket = $this->bucket ?? ($action->controller->id . '/' . $action->id);
        $key    = $this->buildKey($bucket);

        $current = (int)$cache->get($key);

        if ($current >= $this->limit) {
            // Translate to a 429 with the standard Retry-After header so
            // well-behaved clients (and CDNs) back off automatically.
            Yii::$app->response->headers->set('Retry-After', (string)$this->window);
            Yii::$app->response->headers->set($this->remainingHeader, '0');
            throw new TooManyRequestsHttpException(
                'لقد تجاوزت الحد المسموح للرفع، حاول بعد '
                . $this->window . ' ثانية.'
            );
        }

        // Increment + reset TTL on every hit. We deliberately re-set
        // the TTL each time rather than honouring the original because
        // most cache drivers do not expose the remaining TTL cheaply.
        $cache->set($key, $current + 1, $this->window);

        Yii::$app->response->headers->set(
            $this->remainingHeader,
            (string)max(0, $this->limit - ($current + 1))
        );

        return parent::beforeAction($action);
    }

    private function buildKey(string $bucket): string
    {
        $userPart = 'guest';
        if (Yii::$app->has('user') && !Yii::$app->user->isGuest) {
            $userPart = 'u' . (int)Yii::$app->user->id;
        } else {
            $ip = Yii::$app->request->getUserIP() ?? '0.0.0.0';
            $userPart = 'ip' . $ip;
        }
        return 'upload-rate:' . $bucket . ':' . $userPart;
    }
}

<?php

namespace api\modules\v1\controllers;

use yii\rest\Controller;
use backend\models\Media;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * REST endpoint that returns the unified customer-document set.
 *
 * Phase 4 / M4.3 — Pre-Phase-4 this controller queried
 *   `groupName = 'coustmers'` (yes, the legacy typo) and treated the
 *   `contractId` column as if it were the customer id. Both quirks
 *   came from the early days when only one upload path existed and
 *   "customer" was misspelled in the schema. The unified migration
 *   in M0.1 normalised that to:
 *     • groupName='customers'        (canonical, plus 'coustmers' kept
 *                                     as an alias in GroupNameRegistry
 *                                     so historical rows stay readable)
 *     • entity_type='customer'       (new column, populated by the
 *                                     initial backfill)
 *     • entity_id={customer_id}      (replaces the contractId hack)
 *
 *   The query below intentionally accepts ANY of those three shapes
 *   so consumers see a stable result during the deprecation window.
 *   After M8 only the entity_type/entity_id pair will remain.
 */
class CustomerImagesController extends Controller
{
    public function actionIndex($customer_id)
    {
        $customerId = (int) $customer_id;
        if ($customerId <= 0) {
            throw new NotFoundHttpException('Invalid customer ID.');
        }

        $images = Media::find()
            ->where(['deleted_at' => null])
            ->andWhere([
                'or',
                // Canonical (post-Phase 4 writes via MediaService).
                ['and', ['entity_type' => 'customer'], ['entity_id' => $customerId]],
                // Direct customer_id column (pre-Phase 4 wizard / smart-media writes).
                ['customer_id' => $customerId],
                // Legacy "customer id stored in contractId" REST writes
                // — `coustmers` typo + `customers` canonical alias.
                [
                    'and',
                    ['groupName' => ['coustmers', 'customers']],
                    ['contractId' => (string) $customerId],
                ],
            ])
            ->orderBy(['id' => SORT_DESC])
            ->all();

        if (empty($images)) {
            throw new NotFoundHttpException('No images found for the given customer ID.');
        }

        $response = [];
        foreach ($images as $image) {
            $response[] = [
                'id'  => (int) $image->id,
                'url' => $image->getAbsoluteUrl(),
            ];
        }

        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }
}

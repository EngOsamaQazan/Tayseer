<?php

namespace backend\modules\followUpReport\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\modules\followUpReport\models\FollowUpReport;
use backend\modules\followUpReport\models\FollowUpNoContact;

/**
 * FollowUpReportSearch represents the model behind the search form about `FollowUpReport`.
 */
class FollowUpReportSearch extends FollowUpReport
{
    public $q;
    public $customer_name;
    public $seller_name;
    public $number_row;
    public $users_follow_up;
    public $followed_by;
    public $never_followed;
    public $is_can_not_contact;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'seller_id', 'is_deleted', 'number_row', 'followed_by', 'never_followed', 'is_can_not_contact'], 'integer'],
            [['type', 'Date_of_sale', 'first_installment_date', 'notes', 'status', 'updated_at',
              'selected_image', 'company_id', 'customer_name', 'seller_name',
              'last_follow_up', 'promise_to_pay_at', 'reminder', 'q'], 'safe'],
            [['total_value', 'first_installment_value', 'monthly_installment_value', 'users_follow_up', 'effective_installment'], 'number'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        return Model::scenarios();
    }

    private static $cwIdx = 0;

    private static function arabicNormalize(string $text): string
    {
        $text = str_replace(['أ', 'إ', 'آ'], 'ا', $text);
        $text = str_replace('ة', 'ه', $text);
        $text = str_replace('ى', 'ي', $text);
        return $text;
    }

    private function applyUnifiedSearch($query)
    {
        if (empty($this->q)) return;
        $q = trim($this->q);

        $words = preg_split('/\s+/u', $q, -1, PREG_SPLIT_NO_EMPTY);
        $nameNorm = "REPLACE(REPLACE(REPLACE(REPLACE(c.name, 'ة', 'ه'), 'أ', 'ا'), 'إ', 'ا'), 'ى', 'ي')";
        $nameNormNoSpace = "REPLACE($nameNorm, ' ', '')";

        foreach ($words as $w) {
            $wNorm = self::arabicNormalize($w);
            $p = ':cw' . (self::$cwIdx++);
            $nameExpr = new \yii\db\Expression(
                "($nameNorm LIKE $p OR $nameNormNoSpace LIKE $p)",
                [$p => '%' . $wNorm . '%']
            );
            $or = ['or', $nameExpr,
                ['like', 'c.id_number', $w],
                ['like', 'c.primary_phone_number', $w],
            ];
            if (is_numeric($w)) {
                $or[] = ['=', 'os_follow_up_report.id', (int)$w];
                $or[] = ['=', 'c.id', (int)$w];
            }
            $query->andWhere($or);
        }
    }

    // ═══════════════════════════════════════════════════
    //  البحث الرئيسي — تقرير المتابعة
    // ═══════════════════════════════════════════════════
    public function search($params)
    {
        $query = FollowUpReport::find()
            ->joinWith(['customersWithoutCondition as c'])
            ->joinWith(['seller as s'])
            ->joinWith(['contract co']);

        $pageSize = !empty($params['FollowUpReportSearch']['number_row'])
            ? (int)$params['FollowUpReportSearch']['number_row']
            : 10;

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => $pageSize],
            'sort' => [
                'defaultOrder' => ['never_followed' => SORT_DESC, 'last_follow_up' => SORT_ASC],
                'attributes' => [
                    'id',
                    'Date_of_sale',
                    'total_value',
                    'monthly_installment_value',
                    'first_installment_date',
                    'last_follow_up',
                    'due_amount',
                    'due_installments',
                    'never_followed',
                    'effective_installment',
                    'customer_name' => [
                        'asc' => ['c.name' => SORT_ASC],
                        'desc' => ['c.name' => SORT_DESC],
                    ],
                    'seller_name' => [
                        'asc' => ['s.name' => SORT_ASC],
                        'desc' => ['s.name' => SORT_DESC],
                    ],
                ],
            ],
        ]);

        $this->load($params);
        if (!$this->validate()) {
            return $dataProvider;
        }

        // ── فلتر بدون أرقام تواصل ──
        if ($this->is_can_not_contact !== null && $this->is_can_not_contact !== '') {
            $query->andWhere(['os_follow_up_report.is_can_not_contact' => (int)$this->is_can_not_contact]);
        }

        $this->applyUnifiedSearch($query);

        $query->andFilterWhere([
            'os_follow_up_report.id' => $this->id,
            'seller_id' => $this->seller_id,
        ]);

        if (!empty($this->status)) {
            $query->andFilterWhere(['co.status' => $this->status]);
        }

        if ($this->never_followed !== null && $this->never_followed !== '') {
            $query->andFilterWhere(['never_followed' => $this->never_followed]);
        }

        if (!empty($this->reminder)) {
            $query->andWhere([
                'or',
                ['<=', 'reminder', $this->reminder],
                ['never_followed' => 1],
            ]);
        }

        $query->andFilterWhere(['<=', 'promise_to_pay_at', $this->promise_to_pay_at]);

        $query->andFilterWhere(['like', 'type', $this->type])
            ->andFilterWhere(['like', 'notes', $this->notes])
            ->andFilterWhere(['like', 'company_id', $this->company_id]);

        $query->andFilterWhere(['like', 'c.name', $this->customer_name]);
        $query->andFilterWhere(['=', 'c.is_deleted', false]);

        $query->andFilterWhere(['like', 's.name', $this->seller_name]);

        // ── صلاحيات المتابع ──
        if (!Yii::$app->user->can('مدير') && !Yii::$app->user->can('مدير التحصيل')) {
            $query->andFilterWhere(['co.followed_by' => Yii::$app->user->id]);
        }
        $query->andFilterWhere(['co.followed_by' => $this->followed_by]);

        return $dataProvider;
    }

    // ═══════════════════════════════════════════════════
    //  عداد نتائج البحث (يطبق نفس الفلاتر)
    // ═══════════════════════════════════════════════════
    public function searchCounter($params)
    {
        $query = FollowUpReport::find()
            ->joinWith(['customersWithoutCondition as c'])
            ->joinWith(['seller as s'])
            ->joinWith(['contract co']);

        $this->load($params);
        if (!$this->validate()) {
            return 0;
        }

        if ($this->is_can_not_contact !== null && $this->is_can_not_contact !== '') {
            $query->andWhere(['os_follow_up_report.is_can_not_contact' => (int)$this->is_can_not_contact]);
        }

        $this->applyUnifiedSearch($query);

        $query->andFilterWhere([
            'os_follow_up_report.id' => $this->id,
            'seller_id' => $this->seller_id,
        ]);

        if (!empty($this->status)) {
            $query->andFilterWhere(['co.status' => $this->status]);
        }

        if ($this->never_followed !== null && $this->never_followed !== '') {
            $query->andFilterWhere(['never_followed' => $this->never_followed]);
        }

        if (!empty($this->reminder)) {
            $query->andWhere([
                'or',
                ['<=', 'reminder', $this->reminder],
                ['never_followed' => 1],
            ]);
        }

        $query->andFilterWhere(['<=', 'promise_to_pay_at', $this->promise_to_pay_at]);
        $query->andFilterWhere(['like', 'type', $this->type])
            ->andFilterWhere(['like', 'notes', $this->notes])
            ->andFilterWhere(['like', 'company_id', $this->company_id]);
        $query->andFilterWhere(['like', 'c.name', $this->customer_name]);
        $query->andFilterWhere(['=', 'c.is_deleted', false]);
        $query->andFilterWhere(['like', 's.name', $this->seller_name]);

        if (!Yii::$app->user->can('مدير') && !Yii::$app->user->can('مدير التحصيل')) {
            $query->andFilterWhere(['co.followed_by' => Yii::$app->user->id]);
        }
        $query->andFilterWhere(['co.followed_by' => $this->followed_by]);

        return $query->distinct()->count('id');
    }

    // ═══════════════════════════════════════════════════
    //  بحث — عقود "لا يمكن التواصل"
    // ═══════════════════════════════════════════════════
    public function searchNoContact($params)
    {
        $query = FollowUpNoContact::find()
            ->joinWith(['customersWithoutCondition as c'])
            ->joinWith(['seller as s'])
            ->joinWith(['contract co']);

        $pageSize = !empty($params['FollowUpReportSearch']['number_row'])
            ? (int)$params['FollowUpReportSearch']['number_row']
            : 10;

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => $pageSize],
            'sort' => ['defaultOrder' => ['id' => SORT_DESC]],
        ]);

        $dataProvider->sort->attributes['customer_name'] = [
            'asc' => ['c.name' => SORT_ASC],
            'desc' => ['c.name' => SORT_DESC],
        ];
        $dataProvider->sort->attributes['seller_name'] = [
            'asc' => ['s.name' => SORT_ASC],
            'desc' => ['s.name' => SORT_DESC],
        ];

        $this->load($params);
        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere(['os_follow_up_no_contact.id' => $this->id]);
        $query->andFilterWhere(['seller_id' => $this->seller_id]);
        $query->andFilterWhere(['like', 'c.name', $this->customer_name]);
        $query->andFilterWhere(['=', 'c.is_deleted', false]);
        $query->andFilterWhere(['like', 's.name', $this->seller_name]);

        if (!empty($this->status)) {
            $query->andFilterWhere(['co.status' => $this->status]);
        }
        $query->andFilterWhere(['<>', 'co.status', 'finished']);
        $query->andFilterWhere(['<>', 'co.status', 'canceled']);

        if (!Yii::$app->user->can('مدير') && !Yii::$app->user->can('مدير التحصيل')) {
            $query->andFilterWhere(['co.followed_by' => Yii::$app->user->id]);
        }
        $query->andFilterWhere(['co.followed_by' => $this->followed_by]);

        return $dataProvider;
    }

    /**
     * عدد عقود "لا يمكن التواصل"
     */
    public function searchNoContactCount($params)
    {
        $query = FollowUpNoContact::find()
            ->joinWith(['customersWithoutCondition as c'])
            ->joinWith(['seller as s'])
            ->joinWith(['contract co']);

        $this->load($params);
        if (!$this->validate()) {
            return 0;
        }

        $query->andFilterWhere(['os_follow_up_no_contact.id' => $this->id]);
        $query->andFilterWhere(['seller_id' => $this->seller_id]);
        $query->andFilterWhere(['like', 'c.name', $this->customer_name]);
        $query->andFilterWhere(['=', 'c.is_deleted', false]);
        $query->andFilterWhere(['like', 's.name', $this->seller_name]);

        if (!empty($this->status)) {
            $query->andFilterWhere(['co.status' => $this->status]);
        }
        $query->andFilterWhere(['<>', 'co.status', 'finished']);
        $query->andFilterWhere(['<>', 'co.status', 'canceled']);

        if (!Yii::$app->user->can('مدير') && !Yii::$app->user->can('مدير التحصيل')) {
            $query->andFilterWhere(['co.followed_by' => Yii::$app->user->id]);
        }
        $query->andFilterWhere(['co.followed_by' => $this->followed_by]);

        return $query->count();
    }
}

<?php
namespace scaramangagency\translated\elements\db;

use scaramangagency\translated\elements\Order;

use craft\elements\db\ElementQuery;
use craft\helpers\Db;

class OrderQuery extends ElementQuery
{
    public $orderStatus;
    public $expired;
    public $checkExpired;

    public function orderStatus($value)
    {
        $this->orderStatus = $value;
        return $this;
    }

    public function expired($value)
    {
        $this->expired = $value;
        return $this;
    }

    public function checkExpired($value)
    {
        $this->checkExpired = $value;
        return $this;
    }

    protected function beforePrepare(): bool
    {
        $this->joinElementTable('translated_orders');

        $this->query->select([
            'translated_orders.id',
            'translated_orders.title',
            'translated_orders.userId',
            'translated_orders.reviewedBy',
            'translated_orders.dateCreated',
            'translated_orders.dateApproved',
            'translated_orders.dateRejected',
            'translated_orders.dateFulfilled',
            'translated_orders.orderStatus',
            'translated_orders.quoteDeliveryDate',
            'translated_orders.quoteTotal',
            'translated_orders.sourceLanguage',
            'translated_orders.targetLanguage'
        ]);

        if ($this->orderStatus) {
            $this->subQuery->andWhere(Db::parseParam('translated_orders.orderStatus', $this->orderStatus));

            if ($this->checkExpired) {
                if ($this->expired) {
                    $this->subQuery->andWhere('translated_orders.dateCreated < DATE_SUB(NOW(), INTERVAL 24 HOUR)');
                } else {
                    $this->subQuery->andWhere('translated_orders.dateCreated > DATE_SUB(NOW(), INTERVAL 24 HOUR)');
                }
            }
        }

        return parent::beforePrepare();
    }
}

<?php
namespace scaramangagency\translated\elements\db;

use scaramangagency\translated\elements\Order;

use craft\elements\db\ElementQuery;
use craft\helpers\Db;

class OrderQuery extends ElementQuery
{
    public $orderStatus;

    public function orderStatus($value) {
        $this->orderStatus = $value;
        return $this;
    }


    protected function beforePrepare(): bool
    {
        $this->joinElementTable('translated_orders');

        $this->query->select([
            'translated_orders.*',
        ]);

        if ($this->orderStatus) {
            $this->subQuery->andWhere(Db::parseParam('translated_orders.orderStatus', $this->orderStatus));
        }

        return parent::beforePrepare();
    }
}
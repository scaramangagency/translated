<?php
namespace scaramangagency\translated\elements\db;

use scaramangagency\translated\elements\Order;

use craft\elements\db\ElementQuery;
use craft\helpers\Db;

class OrderQuery extends ElementQuery
{
    public $placedBy;
    public $authorisedBy;
    public $status;
    public $dateApproved;
    public $dateRejected;
    public $dateDelivered;
    //public $dueBy;

    public function placedBy($value) {
        $this->placedBy = $value;
        return $this;
    }

    public function authorisedBy($value) {
        $this->authorisedBy = $value;
        return $this;
    }

    public function dateApproved($value) {
        $this->dateApproved = $value;
        return $this;
    }

    public function dateRejected($value) {
        $this->dateRejected = $value;
        return $this;
    }

    public function dateDelivered($value) {
        $this->dateDelivered = $value;
        return $this;
    }

    protected function beforePrepare(): bool
    {
        $this->joinElementTable('translated_orders');

        $this->query->select([
            'translated_orders.*',
        ]);

        if ($this->status) {
            $this->subQuery->andWhere(Db::parseParam('translated_orders.status', $this->status));
        }

        if ($this->placedBy) {
            $this->subQuery->andWhere(Db::parseParam('translated_orders.placedBy', $this->placedBy));
        }

        if ($this->authorisedBy) {
            $this->subQuery->andWhere(Db::parseParam('translated_orders.authorisedBy', $this->authorisedBy));
        }

        if ($this->dateApproved) {
            $this->subQuery->andWhere(Db::parseParam('translated_orders.dateApproved', $this->dateApproved));
        }

        if ($this->dateRejected) {
            $this->subQuery->andWhere(Db::parseParam('translated_orders.dateRejected', $this->dateRejected));
        }

        if ($this->dateDelivered) {
            $this->subQuery->andWhere(Db::parseParam('translated_orders.dateDelivered', $this->dateDelivered));
        }

        return parent::beforePrepare();
    }
}
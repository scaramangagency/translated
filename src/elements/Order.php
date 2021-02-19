<?php
namespace scaramangagency\translated\elements;

use scaramangagency\translated\Translated;
use scaramangagency\translated\elements\db\OrderQuery;

use Craft;
use craft\base\Element;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\Html;
use craft\elements\User;

class Order extends Element
{
    // Public Properties
    // =========================================================================

    public $placedBy;
    public $authorisedBy;
    public $status;
    public $dateApproved;
    public $dateRejected;
    public $dateDelivered;
    public $dueBy;

    // Static Methods
    // =========================================================================

    public static function displayName(): string {
        return Craft::t('translated', 'translated Order');
    }

    public static function refHandle() {
        return 'order';
    }

    public static function find(): ElementQueryInterface {
        return new OrderQuery(static::class);
    }

    protected static function defineSources(string $context = null): array
    {
        $sources = [
            '*' => [
                'key' => '*',
                'label' => Craft::t('translated', 'All Orders'),
            ],
            'status:1' => [
                'key' => 'status:1',
                'label' => Craft::t('translated', 'Awaiting Authorisation'),
            ],
            'status:2' => [
                'key' => 'status:1',
                'label' => Craft::t('translated', 'Pending Delivery'),
            ],
            'status:3' => [
                'key' => 'status:2',
                'label' => Craft::t('translated', 'Completed'),
            ],
            'status:4' => [
                'key' => 'status:3',
                'label' => Craft::t('translated', 'Rejected'),
            ]
        ];

        return $sources;
    }


    // Public Methods
    // -------------------------------------------------------------------------

    public function datetimeAttributes(): array {
        $attributes = parent::datetimeAttributes();
        $attributes[] = 'dateApproved';
        $attributes[] = 'dateRejected';
        $attributes[] = 'dateDelivered';
        $attributes[] = 'dueBy';

        return $attributes;
    }

    public function getUser() {
        if ($this->placedBy !== null) {
            return Craft::$app->getUsers()->getUserById($this->placedBy);
        }

        return null;
    }

    public function getAuthorisedBy() {
        if ($this->authorisedBy !== null) {
            return Craft::$app->getUsers()->getUserById($this->authorisedBy);
        }

        return null;
    }

    public function placedBy() {
        return $this->getUser()->fullName ?? '';
    }

    public function authorisedBy() {
        return $this->getAuthorisedBy()->fullName ?? '';
    }

    // Element index methods
    // -------------------------------------------------------------------------

    protected static function defineTableAttributes(): array {
        return [
            'id' => ['label' => Craft::t('translated', 'Order')],
            'status' => ['label' => Craft::t('translated', 'Status')],
            'dueBy' => ['label' => Craft::t('translated', 'Due By')],
            'placedBy' => ['label' => Craft::t('translated', 'Placed By')],
            'authorisedBy' => ['label' => Craft::t('translated', 'Authorised By')],
            'dateApproved' => ['label' => Craft::t('translated', 'Date Approved')],
            'dateRejected' => ['label' => Craft::t('translated', 'Date Rejected')],
            'dateDelivered' => ['label' => Craft::t('translated', 'Date Delivered')],
        ];
    }

    protected static function defineDefaultTableAttributes(string $source): array {
        return ['id', 'status', 'dueBy', 'placedBy', 'authorisedBy', 'dateApproved', 'dateRejected', 'dateDelivered'];
    }

    protected static function defineSortOptions(): array {
        return [
            'id' => Craft::t('translated', 'Order'),
            'dateApproved' => Craft::t('translated', 'Date Approved'),
            'dateDelivered' => Craft::t('translated', 'Date Delivered')
        ];
    }

    protected function tableAttributeHtml(string $attribute): string {
        switch ($attribute) {
            case 'placedBy': {
                return $this->placedBy() ?: '';
            }
            case 'authorisedBy': {
                return $this->authorisedBy() ?: '';
            }
            case 'status': {
                // call to service to get actual status
                return 'y';
            }
            case 'dueBy': {
                // call to service to get actual due date?
                return 'tomorrow';
            }
            case 'dateApproved':
            case 'dateRejected': 
            case 'dateDelivered': {
                return ($this->$attribute) ? parent::tableAttributeHtml($attribute) : '-';
            }
            default: {
                return parent::tableAttributeHtml($attribute);
            }
        }
    }
}
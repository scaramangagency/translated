<?php
namespace scaramangagency\translated\elements;

use scaramangagency\translated\Translated;
use scaramangagency\translated\elements\db\OrderQuery;

use Craft;
use craft\base\Element;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\Html;
use craft\helpers\UrlHelper;
use craft\elements\User;

class Order extends Element
{
    const STATUS_PENDING = 1;
	const STATUS_PROCESSING = 2;
	const STATUS_DELIVERED = 3;
	const STATUS_REJECTED = 4;

    // Public Properties
    // =========================================================================

    public $userId;
    public $authorisedBy;
    public $dateApproved;
    public $dateRejected;
    public $dateFulfilled;
    public $estimatedDeliveryDate;
    public $dateOrdered;
    public $orderStatus = self::STATUS_PENDING;

    public $title;
    public $sourceLanguage;
    public $targetLanguage;
    public $translationContent;
    public $translationAsset;
    public $translationSubject;
    public $translationLevel;
    public $translationNotes;
    public $wordCount;

    public $quoteDeliveryDate;
    public $quoteTotal;
    public $quotePID;

    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return 'translated Order';
    }

    public function getCpEditUrl()
    {
        return UrlHelper::cpUrl('translated/orders/view/'.$this->id);
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
            'orderStatus:1' => [
                'key' => 'orderStatus:1',
                'label' => 'Awaiting Authorisation',
                'criteria' => ['status' => self::STATUS_PENDING]
            ],
            'orderStatus:2' => [
                'key' => 'orderStatus:2',
                'label' => 'In Process',
                'criteria' => ['orderStatus' => self::STATUS_PROCESSING]
            ],
            'orderStatus:3' => [
                'key' => 'orderStatus:3',
                'label' => 'Completed',
                'criteria' => ['orderStatus' => self::STATUS_DELIVERED]
            ],
            'orderStatus:4' => [
                'key' => 'orderStatus:4',
                'label' => 'Rejected',
                'criteria' => ['orderStatus' => self::STATUS_REJECTED]
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
        $attributes[] = 'dateFulfilled';
        $attributes[] = 'estimatedDeliveryDate';

        return $attributes;
    }

    public function getUser() {
        if ($this->userId !== null) {
            return Craft::$app->getUsers()->getUserById($this->userId);
        }

        return null;
    }

    public function getAuthorisedBy() {
        if ($this->authorisedBy !== null) {
            return Craft::$app->getUsers()->getUserById($this->authorisedBy);
        }

        return null;
    }

    public function getEstimatedDeliveryDate() {
        if ($this->quoteDeliveryDate !== null) {
            return $this->quoteDeliveryDate;
        }

        return null;
    }


    public function getOwner() {
       return $this->getUser()->fullName ?? '';
    }

    public function getStatus() {
        switch ($this->orderStatus) {
            case 1:
                $status = 'Pending';
                break;
            case 2:
                $status = 'Processing';
                break;
            case 3:
                $status = 'Delivered';
                break;
            case 4:
                $status = 'Rejected';
                break;
        }
        return '<span class="label order-status ' . strtolower($status) .'">'. $status .'</span>';
    }

    public function getAuthoriser() {
        return $this->getAuthorisedBy()->fullName ?? '';
    }

    // Element index methods
    // -------------------------------------------------------------------------

    protected static function defineTableAttributes(): array {
        return [
            'title' => ['label' => 'Title'],
            'orderStatus' => ['label' => 'Status'],
            'estimatedDeliveryDate' => ['label' => 'Estimated delivery date'],
            'ownedBy' => ['label' => 'Placed By'],
            'authorisedBy' => ['label' => 'Authorised By'],
            'rejectedBy' => ['label' => 'Rejected By'],
            'dateApproved' => ['label' => 'Approval date'],
            'dateRejected' => ['label' => 'Rejection date'],
            'dateFulfilled' => ['label' => 'Fulfilment date']
        ];
    }

    protected static function defineDefaultTableAttributes(string $source): array {
        return ['title', 'orderStatus', 'estimatedDeliveryDate', 'ownedBy', 'authorisedBy', 'dateApproved', 'dateRejected', 'dateFulfilled'];
    }

    protected static function defineSortOptions(): array {
        return [
            'title' => 'Title',
            'dateApproved' => 'Approval date',
            'dateFulfilled' => 'Fulfilment date'
        ];
    }

    protected function tableAttributeHtml(string $attribute): string {
        switch ($attribute) {
            case 'title': {
                return $this->title;
            }
            case 'ownedBy': {
                return $this->getOwner() ?: '';
            }
            case 'authorisedBy': {
               return $this->getAuthoriser() ?: '';
            }
            case 'orderStatus': {
                return $this->getStatus();
            }
            case 'estimatedDeliveryDate': {
                return $this->getEstimatedDeliveryDate();
            }
            case 'dateApproved':
            case 'dateRejected': 
            case 'dateFulfilled': {
                return ($this->$attribute) ? parent::tableAttributeHtml($attribute) : '-';
            }
            default: {
                return parent::tableAttributeHtml($attribute);
            }
        }
    }
}
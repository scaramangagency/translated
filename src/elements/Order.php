<?php
namespace scaramangagency\translated\elements;

use scaramangagency\translated\Translated;
use scaramangagency\translated\elements\actions\DeleteAction;
use scaramangagency\translated\elements\db\OrderQuery;
use scaramangagency\translated\records\OrderRecord as OrderRecord;

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
    const STATUS_FAILED = 5;

    // Public Properties
    // =========================================================================
    public $userId;
    public $reviewedBy;
    public $dateApproved;
    public $dateRejected;
    public $dateFulfilled;
    public $estimatedDeliveryDate;
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

    public $translatedContent;

    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return 'order';
    }

    public function getCpEditUrl()
    {
        return UrlHelper::cpUrl('translated/orders/view/' . $this->id);
    }

    public static function refHandle()
    {
        return 'order';
    }

    public static function hasContent(): bool
    {
        return false;
    }

    public static function hasStatuses(): bool
    {
        return false;
    }

    public static function find(): ElementQueryInterface
    {
        return new OrderQuery(static::class);
    }

    protected static function defineSources(string $context = null): array
    {
        $sources = [
            '*' => [
                'defaultSort' => ['translated_orders.dateCreated', 'desc'],
                'key' => '*',
                'label' => 'All Orders'
            ],
            'orderStatus:1' => [
                'defaultSort' => ['translated_orders.dateCreated', 'desc'],
                'key' => 'orderStatus:1',
                'label' => 'Pending',
                'criteria' => [
                    'orderStatus' => self::STATUS_PENDING,
                    'expired' => false,
                    'checkExpired' => true
                ]
            ],
            'orderStatus:2' => [
                'defaultSort' => ['translated_orders.dateCreated', 'desc'],
                'key' => 'orderStatus:2',
                'label' => 'In Process',
                'criteria' => ['orderStatus' => self::STATUS_PROCESSING]
            ],
            'orderStatus:3' => [
                'defaultSort' => ['translated_orders.dateCreated', 'desc'],
                'key' => 'orderStatus:3',
                'label' => 'Completed',
                'criteria' => ['orderStatus' => [self::STATUS_DELIVERED, self::STATUS_FAILED]]
            ],
            'orderStatus:4' => [
                'defaultSort' => ['translated_orders.dateCreated', 'desc'],
                'key' => 'orderStatus:4',
                'label' => 'Rejected',
                'criteria' => ['orderStatus' => self::STATUS_REJECTED]
            ],
            'orderStatus:5' => [
                'defaultSort' => ['translated_orders.dateCreated', 'desc'],
                'key' => 'orderStatus:5',
                'label' => 'Expired',
                'criteria' => [
                    'orderStatus' => self::STATUS_PENDING,
                    'expired' => true,
                    'checkExpired' => true
                ]
            ]
        ];

        return $sources;
    }

    protected static function defineActions(string $source = null): array
    {
        $elementsService = Craft::$app->getElements();

        $actions = parent::defineActions($source);

        $actions[] = $elementsService->createAction([
            'type' => DeleteAction::class,
            'confirmationMessage' => 'Are you sure you want to delete the selected orders?',
            'successMessage' => 'Orders deleted.'
        ]);

        return $actions;
    }

    // Public Methods
    // -------------------------------------------------------------------------

    public function datetimeAttributes(): array
    {
        $attributes = parent::datetimeAttributes();
        $attributes[] = 'dateApproved';
        $attributes[] = 'dateRejected';
        $attributes[] = 'dateFulfilled';
        $attributes[] = 'estimatedDeliveryDate';

        return $attributes;
    }

    public function getUser()
    {
        if ($this->userId !== null) {
            return Craft::$app->getUsers()->getUserById($this->userId);
        }

        return null;
    }

    public function getReviewedBy()
    {
        if ($this->reviewedBy !== null) {
            return Craft::$app->getUsers()->getUserById($this->reviewedBy);
        }

        return null;
    }

    public function getEstimatedDeliveryDate()
    {
        if ($this->quoteDeliveryDate !== null) {
            return $this->quoteDeliveryDate;
        }

        return null;
    }

    public function getOwner()
    {
        return $this->getUser()->fullName ?? '';
    }

    public function getStatus()
    {
        switch ($this->orderStatus) {
            case 1:
                $dd = new \DateTime();
                $dd->modify('-1 day');

                if ($this->dateCreated->format('c') > $dd->format('c')) {
                    $status = 'Pending';
                } else {
                    $status = 'Expired';
                }
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
            default:
                $status = 'Pending';
                break;
        }
        return '<span class="label order-status ' . strtolower($status) . '">' . $status . '</span>';
    }

    public function getReviewer()
    {
        return $this->getReviewedBy()->fullName ?? '';
    }

    // public function getContentTable(): string
    // {
    //     return '{{%translated_orders}}';
    // }

    public function afterSave(bool $isNew)
    {
        if (!$isNew) {
            $record = OrderRecord::findOne($this->id);

            if (!$record) {
                throw new Exception('Invalid order ID: ' . $this->id);
            }

            $dt = new \DateTime();
            $record->dateCreated = $dt;
        } else {
            $record = new OrderRecord();
            $record->id = $this->id;
        }

        $record->sourceLanguage = $this->sourceLanguage;
        $record->targetLanguage = $this->targetLanguage;
        $record->title = $this->title;
        $record->translationLevel = $this->translationLevel;
        $record->wordCount = $this->wordCount;
        $record->translationSubject = $this->translationSubject;
        $record->translationNotes = $this->translationNotes;
        $record->userId = $this->userId;
        $record->orderStatus = $this->orderStatus;

        if ($this->translationAsset) {
            $record->translationAsset = $this->translationAsset;
        } else {
            $record->translationContent = $this->translationContent;
        }

        $record->save(false);

        $this->id = $record->id;

        parent::afterSave($isNew);
    }

    // Element index methods
    // -------------------------------------------------------------------------

    protected static function defineTableAttributes(): array
    {
        return [
            'title' => ['label' => 'Title'],
            'dateCreated' => ['label' => 'Requested on'],
            'orderStatus' => ['label' => 'Status'],
            'quoteTotal' => ['label' => 'Quote total'],
            'estimatedDeliveryDate' => ['label' => 'Delivery date'],
            'ownedBy' => ['label' => 'Requested by'],
            'reviewedBy' => ['label' => 'Reviewed by'],
            'rejectedBy' => ['label' => 'Rejected by'],
            'dateApproved' => ['label' => 'Approved on'],
            'dateRejected' => ['label' => 'Rejected on'],
            'dateFulfilled' => ['label' => 'Fulfilled on']
        ];
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        $attributes = [];
        $attributes[] = 'title';
        $attributes[] = 'dateCreated';
        $attributes[] = 'orderStatus';
        $attributes[] = 'ownedBy';

        if ($source == 'orderStatus:1') {
            $attributes[] = 'quoteTotal';
            $attributes[] = 'estimatedDeliveryDate';
        }

        if ($source == 'orderStatus:2' || $source == 'orderStatus:3') {
            $attributes[] = 'reviewedBy';
            $attributes[] = 'dateApproved';
        }

        if ($source == 'orderStatus:3') {
            $attributes[] = 'dateFulfilled';
        }

        if ($source == 'orderStatus:4') {
            $attributes[] = 'reviewedBy';
            $attributes[] = 'dateRejected';
        }

        return $attributes;
    }

    protected static function defineSearchableAttributes(): array
    {
        return ['title'];
    }

    protected static function defineSortOptions(): array
    {
        return [
            'title' => 'Title',
            'dateCreated' => 'Requested on'
        ];
    }

    protected function tableAttributeHtml(string $attribute): string
    {
        switch ($attribute) {
            case 'title':
                return $this->title;
            case 'ownedBy':
                return $this->getOwner() ?: '';
            case 'reviewedBy':
                return $this->getReviewer() ?: '';
            case 'orderStatus':
                return $this->getStatus();
            case 'estimatedDeliveryDate':
                return $this->getEstimatedDeliveryDate();
            case 'dateApproved':
            case 'dateRejected':
            case 'dateFulfilled':
                return $this->$attribute ? parent::tableAttributeHtml($attribute) : '-';
            default:
                return parent::tableAttributeHtml($attribute);
        }
    }
}

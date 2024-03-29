<?php
namespace scaramangagency\translated\elements;

use scaramangagency\translated\Translated;
use scaramangagency\translated\elements\actions\DeleteAction;
use scaramangagency\translated\elements\db\OrderQuery;
use scaramangagency\translated\records\OrderRecord as OrderRecord;

use Craft;
use craft\base\Element;
use craft\elements\User;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\Html;
use craft\helpers\UrlHelper;

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
    public $auto;
    public $entryId;

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

    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['sourceLanguage', 'targetLanguage', 'wordCount', 'title'], 'required'];
        $rules[] = [
            'translationAsset',
            'required',
            'when' => function () {
                return !$this->translationContent;
            },
            'message' => Craft::t('translated', 'Please either upload an asset or supply text to be translated.')
        ];
        $rules[] = [
            'translationContent',
            'required',
            'when' => function () {
                return !$this->translationAsset;
            },
            'message' => Craft::t('translated', 'Please either upload an asset or supply text to be translated.')
        ];
        return $rules;
    }

    protected static function defineSources(string $context = null): array
    {
        $sources = [
            '*' => [
                'defaultSort' => ['translated_orders.dateCreated', 'desc'],
                'key' => '*',
                'label' => Craft::t('translated', 'All Orders')
            ],
            'orderStatus:1' => [
                'defaultSort' => ['translated_orders.dateCreated', 'desc'],
                'key' => 'orderStatus:1',
                'label' => Craft::t('translated', 'Pending'),
                'criteria' => [
                    'orderStatus' => self::STATUS_PENDING,
                    'expired' => false,
                    'checkExpired' => true
                ]
            ],
            'orderStatus:2' => [
                'defaultSort' => ['translated_orders.dateCreated', 'desc'],
                'key' => 'orderStatus:2',
                'label' => Craft::t('translated', 'Processing'),
                'criteria' => ['orderStatus' => self::STATUS_PROCESSING]
            ],
            'orderStatus:3' => [
                'defaultSort' => ['translated_orders.dateCreated', 'desc'],
                'key' => 'orderStatus:3',
                'label' => Craft::t('translated', 'Delivered'),
                'criteria' => ['orderStatus' => [self::STATUS_DELIVERED, self::STATUS_FAILED]]
            ],
            'orderStatus:4' => [
                'defaultSort' => ['translated_orders.dateCreated', 'desc'],
                'key' => 'orderStatus:4',
                'label' => Craft::t('translated', 'Rejected'),
                'criteria' => ['orderStatus' => self::STATUS_REJECTED]
            ],
            'orderStatus:5' => [
                'defaultSort' => ['translated_orders.dateCreated', 'desc'],
                'key' => 'orderStatus:5',
                'label' => Craft::t('translated', 'Expired'),
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
            'confirmationMessage' => Craft::t(
                'translated',
                'Are you sure you want to permanently delete the selected orders?'
            ),
            'successMessage' => Craft::t('translated', 'Orders deleted.')
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
            return \Craft::$app->getFormatter()->asDatetime($this->quoteDeliveryDate, 'short');
        }

        return '';
    }

    public function getOwner()
    {
        return $this->getUser()->fullName ?? '';
    }

    public function getStatus()
    {
        switch ($this->orderStatus) {
            case 1:
                $t = clone $this->dateCreated;
                $dd = $t->modify('+1 day');
                $now = new \DateTime();

                if ($now->format('c') < $dd->format('c')) {
                    $status = Craft::t('translated', 'Pending');
                } else {
                    $status = Craft::t('translated', 'Expired');
                }
                break;
            case 2:
                $status = Craft::t('translated', 'Processing');
                break;
            case 3:
                $status = Craft::t('translated', 'Delivered');
                break;
            case 4:
                $status = Craft::t('translated', 'Rejected');
                break;
            default:
                $status = Craft::t('translated', 'Pending');
                break;
        }
        return '<span class="label order-status ' . strtolower($status) . '">' . $status . '</span>';
    }

    public function getReviewer()
    {
        return $this->getReviewedBy()->fullName ?? '';
    }

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
        $record->auto = $this->auto;
        $record->entryId = $this->entryId;

        if ($this->translationAsset) {
            $record->translationAsset = $this->translationAsset[0];
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
            'title' => ['label' => Craft::t('translated', 'Title')],
            'dateCreated' => ['label' => Craft::t('translated', 'Requested on')],
            'orderStatus' => ['label' => Craft::t('translated', 'Status')],
            'quoteTotal' => ['label' => Craft::t('translated', 'Quote total')],
            'sourceLanguage' => ['label' => Craft::t('translated', 'Source language')],
            'targetLanguage' => ['label' => Craft::t('translated', 'Target language')],
            'estimatedDeliveryDate' => ['label' => Craft::t('translated', 'Delivery date')],
            'ownedBy' => ['label' => Craft::t('translated', 'Requested by')],
            'reviewedBy' => ['label' => Craft::t('translated', 'Reviewed by')],
            'dateApproved' => ['label' => Craft::t('translated', 'Approved on')],
            'dateRejected' => ['label' => Craft::t('translated', 'Rejected on')],
            'dateFulfilled' => ['label' => Craft::t('translated', 'Fulfilled on')]
        ];
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        $attributes = [];
        $attributes[] = 'title';
        $attributes[] = 'orderStatus';
        $attributes[] = 'dateCreated';
        $attributes[] = 'ownedBy';
        $attributes[] = 'sourceLanguage';
        $attributes[] = 'targetLanguage';

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
            'title' => Craft::t('translated', 'Title'),
            'dateCreated' => Craft::t('translated', 'Requested on')
        ];
    }

    protected function tableAttributeHtml(string $attribute): string
    {
        switch ($attribute) {
            case 'title':
                return $this->title;
            case 'ownedBy':
                return $this->getOwner() ?: '-';
            case 'reviewedBy':
                return $this->getReviewer() ?: '-';
            case 'orderStatus':
                return $this->getStatus();
            case 'estimatedDeliveryDate':
                return $this->getEstimatedDeliveryDate() ?: '-';
            case 'quoteTotal':
                return \Craft::$app->getFormatter()->asCurrency($this->$attribute, 'EUR');
            case 'dateApproved':
            case 'dateRejected':
            case 'dateFulfilled':
                return $this->$attribute ? \Craft::$app->getFormatter()->asDatetime($this->$attribute, 'short') : '-';
            default:
                return parent::tableAttributeHtml($attribute);
        }
    }
}

<?php
/**
 * translated plugin for Craft CMS 3.x
 *
 * Request translations via translated from the comfort of your dashboard
 *
 * @link      https://scaramanga.agency
 * @copyright Copyright (c) 2021 Scaramanga Agency
 */

namespace scaramangagency\translated\models;

use scaramangagency\translated\Translated;

use Craft;
use craft\base\Model;

/**
 * @author    Scaramanga Agency
 * @package   Translated
 * @since     1.0.0
 */
class Order extends Model
{
    // Public Properties
    // =========================================================================

    public $title;
    public $sourceLanguage;
    public $targetLanguage;
    public $translationContent;
    public $translationAsset;
    public $translationSubject;
    public $translationLevel;
    public $translationNotes;
    public $wordCount;
    public $userId;

    public $quoteDeliveryDate;
    public $quoteTotal;
    public $quotePID;

    // Public Methods
    // =========================================================================

    public function rules()
    {
        return [
            [['sourceLanguage', 'targetLanguage'], 'required'],
            ['translationContent', 'string'],
            ['translationAsset', 'integer'],
            ['translationNotes', 'string'],
            ['translationLevel', 'string'],
            ['translationSubject', 'string'],
            ['translatedContent', 'string'],
            ['wordCount', 'integer'],
            ['userId', 'integer'],
            ['title', 'string'],

            ['auto', 'integer'],
            ['entryId', 'integer'],

            ['quoteDeliveryDate', 'date'],
            ['quoteTotal', 'float'],
            ['quotePID', 'integer']
        ];
    }
}

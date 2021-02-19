<?php
/**
 * translated plugin for Craft CMS 3.x
 *
 * Request translations from translated from the comfort of your dashboard
 *
 * @link      https://scaramanga.agency
 * @copyright Copyright (c) 2021 Scaramanga Agency
 */

namespace scaramangagency\translated\records;

use scaramangagency\translated\Translated;

use Craft;
use craft\db\ActiveRecord;

/**
 * @author    Scaramanga Agency
 * @package   Translated
 * @since     1.0.0
 */
class Order extends ActiveRecord
{
    // Public Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%translated_order}}';
    }
}

<?php
namespace scaramangagency\translated\records;

use scaramangagency\translated\Translated;

use Craft;
use craft\db\ActiveRecord;

/**
 * @author    Scaramanga Agency
 * @package   Translated
 * @since     1.0.0
 */
class Order extends ActiveRecord {

    public static function tableName() {
        return '{{%translated_orders}}';
    }
}

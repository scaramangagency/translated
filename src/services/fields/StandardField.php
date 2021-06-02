<?php
/**
 * translated plugin for Craft CMS 3.x
 *
 * Request translations via translated from the comfort of your dashboard
 *
 * @link      https://scaramanga.agency
 * @copyright Copyright (c) 2021 Scaramanga Agency
 */

namespace scaramangagency\translated\services\fields;

use scaramangagency\translated\Translated;

use Craft;
use craft\base\Component;

/**
 * @author    Scaramanga Agency
 * @package   Translated
 * @since     1.0.0
 */
class StandardField extends Component
{
    public function decorateStandardData($element, $field)
    {
        if ($field instanceof \craft\redactor\Field) {
            return [$field->handle => strip_tags($element->getFieldValue($field->handle)->getParsedContent())];
        } else {
            return [$field->handle => $element->getFieldValue($field->handle)];
        }
    }

    public function getStandardDataWordCount($element, $field)
    {
        return str_word_count($element->getFieldValue($field->handle));
    }
}

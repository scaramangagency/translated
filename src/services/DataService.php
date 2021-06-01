<?php
/**
 * translated plugin for Craft CMS 3.x
 *
 * Request translations via translated from the comfort of your dashboard
 *
 * @link      https://scaramanga.agency
 * @copyright Copyright (c) 2021 Scaramanga Agency
 */

namespace scaramangagency\translated\services;

use scaramangagency\translated\Translated;
use scaramangagency\translated\services\fields\MatrixField;
use scaramangagency\translated\services\fields\SupertableField;
use scaramangagency\translated\services\fields\StandardField;

use Craft;
use craft\base\Component;
use putyourlightson\logtofile\LogToFile;

/**
 * @author    Scaramanga Agency
 * @package   Translated
 * @since     1.0.0
 */
class DataService extends Component
{
    public function generateCSVForTranslation($element)
    {
        $data = $this->getDataFromElement($element);
        $data['title'] = $element->title;
        $data['slug'] = $element->slug;
        echo '<pre>';

        $rows = [];
        foreach ($data as $key => $value) {
            $rows[] = $this->massageDataForCSV($value, $key);
        }
        var_dump($rows);
        exit();
        return $data;
    }

    public function getWordCount($element)
    {
        $wordCount = 0;

        $wordCount += str_word_count($element->title);
        $wordCount += str_word_count($element->slug);

        foreach ($element->getFieldLayout()->getFields() as $layoutField) {
            $field = Craft::$app->fields->getFieldById($layoutField->id);

            // if ($field->getIsTranslatable()) {
            if ($field instanceof \craft\fields\Matrix) {
                $wordCount += MatrixField::getMatrixDataWordCount($element, $field);
            }

            if ($field instanceof \verbb\supertable\fields\SuperTableField) {
                $wordCount += SupertableField::getSupertableDataWordCount($element, $field);
            }

            if ($field instanceof \benf\neo\Field) {
                $wordCount += NeoField::getNeoDataWordCount($element, $layoutField);
            }

            if ($field instanceof \craft\fields\PlainText || $field instanceof \craft\redactor\Field) {
                $wordCount += StandardField::getStandardDataWordCount($element, $field);
            }
            // }
        }

        return $wordCount;
    }

    public function getDataFromElement($element)
    {
        $data = [];

        foreach ($element->getFieldLayout()->getFields() as $layoutField) {
            $field = Craft::$app->fields->getFieldById($layoutField->id);

            // if ($field->getIsTranslatable()) {
            if ($field instanceof \craft\fields\Matrix) {
                $data = array_merge($data, MatrixField::decorateMatixData($element, $layoutField));
            }

            if ($field instanceof \verbb\supertable\fields\SuperTableField) {
                $data = array_merge($data, SupertableField::decorateSupertableData($element, $layoutField));
            }

            if ($field instanceof \benf\neo\Field) {
                $data = array_merge($data, NeoField::decorateNeoData($element, $layoutField));
            }

            if ($field instanceof \craft\fields\PlainText || $field instanceof \craft\redactor\Field) {
                $data = array_merge($data, StandardField::decorateStandardData($element, $layoutField));
            }
            // }
        }
        return $data;
    }

    private function massageDataForCSV($value, $key, $currentKey = '')
    {
        $csvRow = [];

        if ($currentKey != '') {
            $key = $currentKey . '||' . $key;
        }

        if (is_object($value)) {
            if ($value instanceof \craft\redactor\FieldData) {
                $csvRow[$key] = $value->getParsedContent();
            }
        }

        if (is_array($value)) {
            foreach ($value as $deeperKey => $deeperValue) {
                $this->massageDataForCSV($deeperValue, $deeperKey, $key);
            }
        }

        if (!is_object($value) && !is_array($value)) {
            $csvRow[$key] = $value;
        }

        return $csvRow;
        // var_dump($csvRow);
    }
}

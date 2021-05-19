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

use Craft;
use craft\base\Component;
use putyourlightson\logtofile\LogToFile;

/**
 * @author    Scaramanga Agency
 * @package   Translated
 * @since     1.0.0
 */
class UtilityService extends Component
{
    // Public Methods
    // =========================================================================

    public function fetchAvailableLanguages($settings)
    {
        $params = [
            'cid' => Craft::parseEnv($settings['translatedUsername']),
            'p' => Craft::parseEnv($settings['translatedPassword']),
            'f' => 'll',
            'of' => 'json'
        ];

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://www.translated.net/hts/?' . http_build_query($params));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $res = curl_exec($ch);
            curl_close($ch);
        } catch (Exception $e) {
            LogToFile::error(
                '[Utility][Languages] Failed to fetch available languages from translated API',
                'translated'
            );
            return false;
        }

        $res = json_decode($res);

        if ($res->code == 0) {
            LogToFile::error(
                '[Utility][Languages] translated API returned an error when fetching languages. Error: ' .
                    $res->message,
                'translated'
            );
            return false;
        }

        $decorateLanguages = [];
        $selectedSource = '';
        $selectedTarget = [];

        for ($i = 0; $i < count((array) $res); $i++) {
            if (property_exists($res, $i)) {
                $decorateLanguages[] = [
                    'label' => $res->{$i}->name,
                    'value' => $res->{$i}->name
                ];

                if (Craft::$app->sites->primarySite->language == $res->{$i}->rfc3066) {
                    $selectedSource = $res->{$i}->name;
                }

                $sites = Craft::$app->sites->allSites;

                foreach ($sites as $site) {
                    if (
                        $site->id != Craft::$app->sites->primarySite->id &&
                        $site->language == $res->{$i}->rfc3066 &&
                        Craft::$app->sites->primarySite->language != $res->{$i}->rfc3066
                    ) {
                        $selectedTarget[] = $res->{$i}->name;
                    }
                }
            }
        }

        return [
            'optionList' => $decorateLanguages,
            'selectedSource' => $selectedSource,
            'selectedTarget' => $selectedTarget
        ];
    }

    public function fetchAvailableSubjects($settings)
    {
        $params = [
            'cid' => Craft::parseEnv($settings['translatedUsername']),
            'p' => Craft::parseEnv($settings['translatedPassword']),
            'f' => 'subjects',
            'of' => 'json'
        ];

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://www.translated.net/hts/?' . http_build_query($params));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $res = curl_exec($ch);
            curl_close($ch);
        } catch (Exception $e) {
            LogToFile::error(
                '[Utility][Subjects] Failed to fetch available subjects from translated API',
                'translated'
            );
            return false;
        }

        $res = json_decode($res);

        if ($res->code == 0) {
            LogToFile::error(
                '[Utility][Subjects] translated API returned an error when fetching subjects. Error: ' . $res->message,
                'translated'
            );
            return false;
        }

        $decorateSubjects = [];

        for ($i = 0; $i < count((array) $res); $i++) {
            if (property_exists($res, $i)) {
                $decorateSubjects[] = [
                    'label' => ucfirst(str_replace('_', ' ', $res->{$i})),
                    'value' => $res->{$i}
                ];
            }
        }

        return $decorateSubjects;
    }

    public function getDataFromElement($element)
    {
        $value = [];
        $value['title'] = $element->title;

        foreach ($element->getFieldLayout()->getFields() as $field) {
            // Generic Plain Text + Redactor Fields
            if ($field instanceof \craft\fields\PlainText || $field instanceof \craft\redactor\Field) {
                $v = $element->getFieldValue($field->handle);
                $value[$field->handle] = str_replace(
                    ["\r", "\n"],
                    '',
                    strip_tags($field->serializeValue($v, $element))
                );
            }

            // Matrix fields
            if ($field instanceof \craft\fields\Matrix) {
                $matrixElement = $element->{$field->handle}->all();
                $i = 0;

                foreach ($matrixElement as $matrixField) {
                    $i++;
                    $matrixBlock = Craft::$app->getMatrix()->getBlockById($matrixField->id);
                    $fieldValues = $matrixBlock->fieldValues;

                    foreach ($fieldValues as $k => $v) {
                        // Redactor
                        if ($v instanceof \craft\redactor\FieldData) {
                            $value[$matrixBlock->id][$k] = str_replace(
                                ["\\r", "\\n"],
                                '',
                                strip_tags($v->getParsedContent())
                            );
                        }

                        // Supertable
                        if ($v instanceof \verbb\supertable\elements\db\SuperTableBlockQuery) {
                            $supertableFields = $v->fieldValues;

                            foreach ($supertableFields as $supertableFieldKey => $supertableFieldValue) {
                                if (!is_object($supertableFieldValue) && $supertableFieldValue != '') {
                                    $value[$matrixBlock->id][$k][$supertableFieldKey] = $supertableFieldValue;
                                }

                                if ($supertableFieldValue instanceof \craft\redactor\FieldData) {
                                    $value[$matrixBlock->id][$k][
                                        $supertableFieldKey
                                    ] = $supertableFieldValue->getParsedContent();
                                }
                            }
                        }

                        // Plain Text
                        if (!is_object($v) && $v != '') {
                            $value[$matrixBlock->id][$k] = $v;
                        }
                    }
                }
            }
        }

        $contentString = '';

        foreach ($value as $k => $v) {
            // This is a plain text field
            if (!is_array($v)) {
                $contentString .= $v . " \r\n\r\n";
            } else {
                foreach ($v as $ck => $cv) {
                    // This is a matrix field (or top level supertable)
                    if (!is_array($cv)) {
                        $contentString .= $cv . " \r\n\r\n";
                    } else {
                        // This is an embedded supertable field
                        foreach ($cv as $gck => $gcv) {
                            $contentString .= $gcv . " \r\n\r\n";
                        }
                    }
                }
            }
        }

        return $contentString;
    }
}

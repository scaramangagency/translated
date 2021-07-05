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
class NeoField extends Component
{
    public function decorateNeoData($element, $field)
    {
        $neo = $element->{$field->handle}->all();

        if (!$neo) {
            return [];
        }

        $data = [
            $field->handle => []
        ];

        foreach ($neo as $block) {
            $blockId = $block->id;

            $data[$field->handle][$blockId] = [
                'fields' => $block->getSerializedFieldValues()
            ];
        }

        return $data;
    }

    public function getNeoDataWordCount($element, $field)
    {
        $neo = $element->{$field->handle}->all();

        if (!$neo) {
            return 0;
        }

        $wordCount = 0;

        foreach ($neo as $block) {
            $wordCount += translated::$plugin->dataService->getWordCount($block);
        }

        return $wordCount;
    }
}

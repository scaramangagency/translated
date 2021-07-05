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
use verbb\supertable\SuperTable;

/**
 * @author    Scaramanga Agency
 * @package   Translated
 * @since     1.0.0
 */
class SupertableField extends Component
{
    public function decorateSupertableData($element, $field)
    {
        $supertableBlocks = $element->{$field->handle}->all();

        if (!$supertableBlocks) {
            return [];
        }

        $data = [
            $field->handle => []
        ];

        $blockTypes = SuperTable::$plugin->getService()->getBlockTypesByFieldId($field->id);
        $blockType = $blockTypes[0];

        foreach ($supertableBlocks as $block) {
            $blockId = $block->id;

            $data[$field->handle][$blockId] = [
                'fields' => translated::$plugin->dataService->getDataFromElement($block)
            ];
        }

        return $data;
    }

    public function getSupertableDataWordCount($element, $field)
    {
        $supertableBlocks = $element->{$field->handle}->all();

        if (!$supertableBlocks) {
            return 0;
        }

        $wordCount = 0;

        foreach ($supertableBlocks as $block) {
            $wordCount += translated::$plugin->dataService->getWordCount($block);
        }

        return $wordCount;
    }
}

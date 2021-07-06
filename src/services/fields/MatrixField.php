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
class MatrixField extends Component
{
    public function decorateMatixData($element, $field)
    {
        $matrixBlocks = $element->{$field->handle}->all();

        if (!$matrixBlocks) {
            return [];
        }

        $data = [
            $field->handle => []
        ];

        foreach ($matrixBlocks as $block) {
            $blockId = $block->id;

            $data[$field->handle][$blockId] = [
                'type' => $block->getType()->handle,
                'fields' => translated::$plugin->dataService->getDataFromElement($block)
            ];
        }

        return $data;
    }

    public function getMatrixDataWordCount($element, $field)
    {
        $matrixBlocks = $element->{$field->handle}->all();

        if (!$matrixBlocks) {
            return 0;
        }

        $wordCount = 0;

        foreach ($matrixBlocks as $block) {
            $wordCount += translated::$plugin->dataService->getWordCount($block);
        }

        return $wordCount;
    }
}

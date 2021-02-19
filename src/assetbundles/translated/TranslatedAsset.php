<?php
/**
 * translated plugin for Craft CMS 3.x
 *
 * Request translations from translated from the comfort of your dashboard
 *
 * @link      https://scaramanga.agency
 * @copyright Copyright (c) 2021 Scaramanga Agency
 */

namespace scaramangagency\translated\assetbundles\translated;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * @author    Scaramanga Agency
 * @package   Translated
 * @since     1.0.0
 */
class TranslatedAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = "@scaramangagency/translated/assetbundles/translated/dist";

        $this->depends = [
            CpAsset::class,
        ];

        $this->js = [
            'js/Translated.js',
        ];

        $this->css = [
            'css/Translated.css',
        ];

        parent::init();
    }
}

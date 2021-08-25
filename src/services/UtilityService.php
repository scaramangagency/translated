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
            return false;
        }

        $res = json_decode($res);

        if ($res->code == 0) {
            return false;
        }

        $decorateLanguages = [];

        $decorateLanguages[] = ['label' => 'Please select...', 'value' => ''];

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
            return false;
        }

        $res = json_decode($res);

        if ($res->code == 0) {
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

    public function fetchAvailableSites()
    {
        $sites = Craft::$app->sites->allSites;

        $decorateSites = [
            [
                'label' => 'Please select...',
                'value' => ''
            ]
        ];

        foreach ($sites as $site) {
            $decorateSites[] = [
                'label' => $site->name,
                'value' => $site->id
            ];
        }

        return $decorateSites;
    }
}

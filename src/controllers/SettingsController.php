<?php

namespace scaramangagency\translated\controllers;

use scaramangagency\translated\Translated;
use scaramangagency\translated\services\TranslatedService;

use Craft;
use craft\web\Controller;

class SettingsController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionIndex()
    {
        $settings = translated::$plugin->getSettings();

        $res = Craft::$app->volumes->getAllVolumes();
        $decorateVolumes = [];

        foreach ($res as $volume) {
            $decorateVolumes[] = [
                'label' => $volume->name,
                'value' => $volume->id
            ];
        }

        return $this->renderTemplate('translated/settings', [
            'settings' => $settings,
            'assetOptions' => $decorateVolumes,
            'config' => Craft::$app->getConfig()->getConfigFromFile('translated')
        ]);
    }

    public function actionSavePluginSettings()
    {
        $this->requirePostRequest();

        $settings = Craft::$app->getRequest()->getBodyParam('settings', []);
        $plugin = Craft::$app->getPlugins()->getPlugin('translated');

        if ($plugin === null) {
            throw new NotFoundHttpException('Plugin not found');
        }

        if (!Craft::$app->getPlugins()->savePluginSettings($plugin, $settings)) {
            Craft::$app->getSession()->setError(Craft::t('translated', "Couldn't save plugin settings"));

            Craft::$app->getUrlManager()->setRouteParams([
                'plugin' => $plugin
            ]);

            return null;
        }

        Craft::$app->getSession()->setNotice(Craft::t('translated', 'Plugin settings saved'));
        return $this->redirectToPostedUrl();
    }
}

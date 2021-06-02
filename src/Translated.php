<?php
namespace scaramangagency\translated;

use scaramangagency\translated\services\Order as OrderService;
use scaramangagency\translated\models\Settings;
use scaramangagency\translated\elements\Order;
use scaramangagency\translated\web\TranslatedAsset;

use Craft;
use craft\base\Plugin;
use craft\services\Plugins;
use craft\services\UserPermissions;
use craft\services\Elements;
use craft\events\PluginEvent;
use craft\web\UrlManager;
use craft\helpers\UrlHelper;
use craft\web\View;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\events\RegisterComponentTypesEvent;

use yii\base\Event;

class Translated extends Plugin
{
    // Static Properties
    // =========================================================================
    public static $plugin;

    // Public Properties
    // =========================================================================
    public $schemaVersion = '1.0.0';
    public $hasCpSettings = true;
    public $hasCpSection = true;

    // Public Methods
    // =========================================================================

    public function init()
    {
        parent::init();
        self::$plugin = $this;

        $this->registerCpUrls();
        $this->registerSiteUrls();
        $this->registerPermissions();
        $this->registerComponents();
        $this->registerElementType();
        $this->addCPHooks();

        Event::on(Plugins::class, Plugins::EVENT_AFTER_INSTALL_PLUGIN, function (PluginEvent $event) {
            if ($event->plugin === $this) {
                $request = Craft::$app->getRequest();
                if ($request->isCpRequest) {
                    Craft::$app
                        ->getResponse()
                        ->redirect(UrlHelper::cpUrl('translated/settings'))
                        ->send();
                }
            }
        });

        Craft::info(Craft::t('translated', '{name} plugin loaded', ['name' => $this->name]), __METHOD__);
    }

    public function getCpNavItem()
    {
        $cpNav = parent::getCpNavItem();
        $subNavs = [];
        $request = Craft::$app->getRequest();

        $user = Craft::$app->getUser()->getIdentity();

        if ($user->can('translated:orders')) {
            $subNavs['orders'] = [
                'label' => Craft::t('translated', 'Orders'),
                'url' => 'translated/orders'
            ];
        }

        if ($user->can('translated:settings') && Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
            $subNavs['settings'] = [
                'label' => Craft::t('translated', 'Settings'),
                'url' => 'translated/settings'
            ];
        }

        $cpNav = array_merge($cpNav, [
            'subnav' => $subNavs
        ]);

        return $cpNav;
    }

    public function afterSaveSettings()
    {
        parent::afterSaveSettings();
        Craft::$app
            ->getResponse()
            ->redirect(UrlHelper::cpUrl('translated/settings'))
            ->send();
    }

    public function getSettingsResponse()
    {
        Craft::$app->controller->redirect(UrlHelper::cpUrl('translated/settings'));
    }

    // Protected Methods
    // =========================================================================
    protected function createSettingsModel()
    {
        return new Settings();
    }

    protected function settingsHtml(): string
    {
        return Craft::$app->view->renderTemplate('translated/settings');
    }

    // Private Methods
    // =========================================================================
    private function registerElementType()
    {
        Event::on(Elements::class, Elements::EVENT_REGISTER_ELEMENT_TYPES, function (
            RegisterComponentTypesEvent $event
        ) {
            $event->types[] = Order::class;
        });
    }

    private function registerComponents()
    {
        $this->setComponents([
            'orderService' => \scaramangagency\translated\services\OrderService::class,
            'utilityService' => \scaramangagency\translated\services\UtilityService::class,
            'dataService' => \scaramangagency\translated\services\DataService::class,
            'profileService' => \scaramangagency\trustpilot\services\ProfileService::class,
            'resourcesService' => \scaramangagency\trustpilot\services\ResourcesService::class
        ]);
    }

    private function registerCpUrls()
    {
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function (RegisterUrlRulesEvent $event) {
            $event->rules = array_merge($event->rules, [
                'translated/settings' => 'translated/settings/index',

                'translated/orders' => 'translated/orders/index',
                'translated/orders/new' => 'translated/orders/new-quote',
                'translated/orders/duplicate/<id>' => 'translated/orders/new-quote',
                'translated/orders/view/<id>' => 'translated/orders/view-order',
                'translated/orders/autogenerate/<siteId>/<id>' => 'translated/orders/autogenerate',
                'translated/orders/manual-download' => 'translated/orders/manual-download'
            ]);
        });
    }

    private function registerSiteUrls()
    {
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_SITE_URL_RULES, function (
            RegisterUrlRulesEvent $event
        ) {
            $event->rules['translated/accept'] = 'translated/orders/webhook';
        });
    }

    private function registerPermissions()
    {
        Event::on(UserPermissions::class, UserPermissions::EVENT_REGISTER_PERMISSIONS, function (
            RegisterUserPermissionsEvent $event
        ) {
            $event->permissions[Craft::t('translated', 'translated')] = [
                'translated:settings' => [
                    'label' => Craft::t('translated', 'Settings')
                ],
                'translated:orders' => [
                    'label' => Craft::t('translated', 'View orders'),
                    'nested' => [
                        'translated:orders:makequotes' => [
                            'label' => Craft::t('translated', 'Request quotes')
                        ],
                        'translated:orders:sendquotes' => [
                            'label' => Craft::t('translated', 'Authorise quotes')
                        ]
                    ]
                ]
            ];
        });
    }

    private function addCPHooks()
    {
        Event::on(View::class, View::EVENT_BEFORE_RENDER_TEMPLATE, function (\craft\events\TemplateEvent $event) {
            $view = Craft::$app->getView();
            $view->registerAssetBundle(TranslatedAsset::class);
        });

        Craft::$app->getView()->hook('cp.entries.edit.details', function (array &$context) {
            $entry = $context['entry'];
            $generateUrl = UrlHelper::cpUrl('translated/orders/autogenerate/' . $entry->siteId . '/' . $entry->id);

            return '<a href="' . $generateUrl . '" class="btn tertiary full">Translate entry</a>';
        });
    }
}

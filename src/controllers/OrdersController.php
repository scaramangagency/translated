<?php
/**
 * translated plugin for Craft CMS 3.x
 *
 * Request translations via translated from the comfort of your dashboard
 *
 * @link      https://scaramanga.agency
 * @copyright Copyright (c) 2021 Scaramanga Agency
 */

namespace scaramangagency\translated\controllers;

use scaramangagency\translated\Translated;
use scaramangagency\translated\services\UtilityService;

use Craft;
use craft\web\Controller;
use craft\elements\Asset;
use craft\helpers\UrlHelper;

/**
 * @author    Scaramanga Agency
 * @package   Translated
 * @since     1.0.0
 */
class OrdersController extends Controller
{
    // Protected Properties
    // =========================================================================

    /**
     * @var    bool|array Allows anonymous access to this controller's actions.
     *         The actions must be in 'kebab-case'
     * @access protected
     */
    protected $allowAnonymous = ['handleDelivery'];

    // Public Methods
    // =========================================================================

    public function actionIndex()
    {
        $settings = Translated::$plugin->getSettings();

        if (!$settings['translatedUsername'] || !$settings['translatedPassword']) {
            return Craft::$app->response->redirect(UrlHelper::cpUrl('translated/settings'))->send();
        }

        $user = Craft::$app->getUser();
        if ($user->checkPermission('translated:orders:makequotes')) {
            $requestQuote = true;
        }

        return $this->renderTemplate('translated/orders', [
            'requestQuote' => $requestQuote ?? false
        ]);
    }

    public function actionNewOrder()
    {
        $settings = Translated::$plugin->getSettings();

        if (!$settings['translatedUsername'] || !$settings['translatedPassword']) {
            return Craft::$app->response->redirect(UrlHelper::cpUrl('translated/settings'))->send();
        }

        $availableLanguages = Translated::$plugin->utilityService->fetchAvailableLanguages($settings);
        $availableSubjects = Translated::$plugin->utilityService->fetchAvailableSubjects($settings);

        return $this->renderTemplate('translated/orders/new', [
            'availableLanguages' => $availableLanguages['optionList'],
            'availableSubjects' => $availableSubjects,
            'elementType' => Asset::class,
            'selectedSource' => $availableLanguages['selectedSource'],
            'selectedTarget' => $availableLanguages['selectedTarget']
        ]);
    }

    public function actionViewOrder(int $id)
    {
        $order = Translated::$plugin->orderService->getOrder($id);

        if (!$order) {
            LogToFile::error('Could not get order record', 'translated');

            Craft::$app->getSession()->setError(Craft::t('app', 'An order does not exist with that ID.'));

            return Craft::$app->response->redirect(UrlHelper::cpUrl('translated/order'))->send();
        }

        $user = Craft::$app->getUser();
        if ($user->checkPermission('translated:orders:sendquotes')) {
            $orderPermissions = true;
        }
        if ($user->checkPermission('translated:orders:makequotes')) {
            $requestQuote = true;
        }

        switch ($order->orderStatus) {
            case 1:
                $dd = new \DateTime();
                $dd->modify('-1 day');

                $dt = new \DateTime(strtotime($order->dateCreated));

                if ($dt->format('c') > $dd->format('c')) {
                    $status = 'Pending';
                } else {
                    $status = 'Expired';
                }
                break;
            case 2:
                $status = 'Processing';
                break;
            case 3:
                $status = 'Delivered';
                break;
            case 4:
                $status = 'Rejected';
                break;
        }

        switch ($order->translationLevel) {
            case 'T':
                $service = 'Professional';
                break;
            case 'R':
                $service = 'Premium';
                break;
            case 'P':
                $service = 'Economy';
                break;
        }

        return $this->renderTemplate('translated/orders/view', [
            'order' => $order,
            'orderPermissions' => $orderPermissions ?? false,
            'requestQuote' => $requestQuote ?? false,
            'statusFlag' => '<span class="label order-status ' . strtolower($status) . '">' . $status . '</span>',
            'serviceLevel' => '<span class="label order-service ' . strtolower($service) . '">' . $service . '</span>'
        ]);
    }

    public function actionRequestQuote()
    {
        $data = Craft::$app->getRequest()->getBodyParam('order', []);
        $getQuote = Translated::$plugin->orderService->getQuote($data);

        return Craft::$app->response->redirect(UrlHelper::cpUrl('translated/orders'))->send();
    }

    public function actionRejectQuote()
    {
        $data = Craft::$app->getRequest()->getBodyParam('order', []);
        $getQuote = Translated::$plugin->orderService->rejectQuote($data);

        return Craft::$app->response->redirect(UrlHelper::cpUrl('translated/orders'))->send();
    }

    public function actionApproveQuote()
    {
        $data = Craft::$app->getRequest()->getBodyParam('order', []);
        $getQuote = Translated::$plugin->orderService->approveQuote($data);

        return Craft::$app->response->redirect(UrlHelper::cpUrl('translated/orders'))->send();
    }

    public function actionHandleDelivery()
    {
        return true;
    }
}

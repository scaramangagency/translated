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
use scaramangagency\translated\services\DataService;
use scaramangagency\translated\services\UtilityService;
use scaramangagency\translated\elements\Order as Order;

use Craft;
use craft\elements\Asset;
use craft\elements\Entry;
use craft\helpers\UrlHelper;
use craft\web\Controller;

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
    protected $allowAnonymous = ['webhook'];
    public $enableCsrfValidation = false;

    // Public Methods
    // =========================================================================

    public function actionIndex()
    {
        $settings = translated::$plugin->getSettings();

        if (!$settings['translatedUsername'] || !$settings['translatedPassword']) {
            return $this->redirect(UrlHelper::cpUrl('translated/settings'));
        }

        $user = Craft::$app->getUser();
        if ($user->checkPermission('translated:orders:makequotes')) {
            $requestQuote = true;
        }

        return $this->renderTemplate('translated/orders', [
            'requestQuote' => $requestQuote ?? false
        ]);
    }

    public function actionNewQuote($id = null, Order $errors = null, array $form = null)
    {
        $settings = translated::$plugin->getSettings();

        if (!$settings['translatedUsername'] || !$settings['translatedPassword']) {
            return $this->redirect(UrlHelper::cpUrl('translated/settings'));
        }

        if ($id) {
            $data = translated::$plugin->orderService->getOrder($id);

            if (!$data) {
                Craft::$app->getSession()->setError(Craft::t('app', 'Failed to get quote to duplicate'));
                return $this->redirect(UrlHelper::cpUrl('translated/orders'));
            }

            if ($data['translationAsset'] != '') {
                $attachedAsset = Asset::find()
                    ->id($data['translationAsset'])
                    ->one();
            }

            $duplicateTitle = $data['title'] . ' copy';
        }

        $availableLanguages = translated::$plugin->utilityService->fetchAvailableLanguages($settings);
        $availableSubjects = translated::$plugin->utilityService->fetchAvailableSubjects($settings);

        if (!$availableLanguages || !$availableSubjects) {
            Craft::$app
                ->getSession()
                ->setError(Craft::t('app', 'Sorry, there appears to be an issue with the translated API'));
            return $this->redirect(UrlHelper::cpUrl('translated/orders'));
        }

        if (!$errors) {
            $errors = new Order();
        }

        if ($form) {
            if ($form['translationAsset'] != '') {
                $attachedAsset = Asset::find()
                    ->id($form['translationAsset'])
                    ->one();
            }
        }

        return $this->renderTemplate('translated/orders/new', [
            'availableLanguages' => $availableLanguages['optionList'],
            'availableSubjects' => $availableSubjects,
            'elementType' => Asset::class,
            'selectedSource' => $availableLanguages['selectedSource'],
            'selectedTarget' => $availableLanguages['selectedTarget'],
            'data' => $data ?? null,
            'form' => $form,
            'err' => $errors,
            'attachedAsset' => $attachedAsset ?? null,
            'duplicateTitle' => $duplicateTitle ?? null
        ]);
    }

    public function actionAutogenerate($id, $siteId, Order $errors = null, array $form = null)
    {
        $settings = translated::$plugin->getSettings();

        if (!$settings['translatedUsername'] || !$settings['translatedPassword']) {
            return $this->redirect(UrlHelper::cpUrl('translated/settings'));
        }

        $element = Craft::$app->getElements()->getElementById($id, null, $siteId);

        if (!$element) {
            Craft::$app->getSession()->setError(Craft::t('app', 'Failed to get get content from this entry'));
            return $this->redirect(Craft::$app->getRequest()->referrer);
        }

        if (!$element->getFieldLayout()) {
            Craft::$app->getSession()->setError(Craft::t('app', 'Failed to get get content from this entry'));
            return $this->redirect(Craft::$app->getRequest()->referrer);
        }

        $csv = translated::$plugin->dataService->generateCSVForTranslation($element);

        if ($csv['uploaded']) {
            $data['translationAsset'] = $csv['path'];
            $attachedAsset = $csv['path'];
        } else {
            $data['failedUpload'] = basename($csv['path']);
        }

        $data['projectName'] = $element->title;
        $data['wordCount'] = translated::$plugin->dataService->getWordCount($element);
        $data['translationNotes'] =
            "Please translate text from RAW column into TRANSLATED column. If possible, please try to retain any HTML markup for paragraphs or headings. \r\n";
        $data['entryId'] = $element->id;
        $data['auto'] = 1;

        $availableLanguages = translated::$plugin->utilityService->fetchAvailableLanguages($settings);
        $availableSubjects = translated::$plugin->utilityService->fetchAvailableSubjects($settings);

        if (!$availableLanguages || !$availableSubjects) {
            Craft::$app
                ->getSession()
                ->setError(Craft::t('app', 'Sorry, there appears to be an issue with the translated API'));
            return $this->redirect(UrlHelper::cpUrl('translated/orders'));
        }

        if (!$errors) {
            $errors = new Order();
        }

        if ($form) {
            if ($form['translationAsset'] != '') {
                $form['translationAsset'] = Asset::find()
                    ->id($form['translationAsset'])
                    ->one();
            }
        }

        return $this->renderTemplate('translated/orders/new', [
            'availableLanguages' => $availableLanguages['optionList'],
            'availableSubjects' => $availableSubjects,
            'elementType' => Asset::class,
            'selectedSource' => $availableLanguages['selectedSource'],
            'selectedTarget' => $availableLanguages['selectedTarget'],
            'data' => $data ?? null,
            'form' => $form,
            'err' => $errors,
            'attachedAsset' => $attachedAsset
        ]);
    }

    public function actionRequestQuote()
    {
        $data = Craft::$app->getRequest()->getBodyParam('order', []);
        $quoteId = translated::$plugin->orderService->handleQuote($data);

        if (!$quoteId['success'] && array_key_exists('errors', $quoteId)) {
            Craft::$app->getSession()->setError(Craft::t('app', 'Could not save quote'));

            Craft::$app->getUrlManager()->setRouteParams([
                'errors' => $quoteId['errors'],
                'form' => $data
            ]);

            return null;
        }

        if (!$quoteId['success']) {
            Craft::$app->getSession()->setError($quoteId['response']);
            return $this->redirect(UrlHelper::cpUrl('translated/orders'));
        }

        return $this->redirect(UrlHelper::cpUrl('translated/orders/view/' . $quoteId['response']));
    }

    public function actionApproveQuote($id)
    {
        $approveQuote = translated::$plugin->orderService->approveQuote($id);

        if (!$approveQuote) {
            Craft::$app->getSession()->setError(Craft::t('app', 'Failed to authorise order'));
            return $this->redirect(UrlHelper::cpUrl('translated/orders'));
        }

        Craft::$app->getSession()->setNotice(Craft::t('app', 'Quote converted to order'));
        return $this->redirect(UrlHelper::cpUrl('translated/orders'));
    }

    public function actionRefreshQuote($id)
    {
        $getQuote = translated::$plugin->orderService->handleQuote(null, $id);

        if (!$getQuote) {
            Craft::$app->getSession()->setError(Craft::t('app', 'Failed to refresh quote'));
            return $this->redirect(UrlHelper::cpUrl('translated/orders'));
        }

        Craft::$app->getSession()->setNotice(Craft::t('app', 'Quote successfully refreshed'));
        return $this->redirect(UrlHelper::cpUrl('translated/orders/view/' . $id));
    }

    public function actionRejectQuote($id)
    {
        $rejectQuote = translated::$plugin->orderService->rejectQuote($id);

        if (!$rejectQuote) {
            Craft::$app->getSession()->setError(Craft::t('app', 'Failed to reject quote'));
            return $this->redirect(UrlHelper::cpUrl('translated/orders'));
        }

        Craft::$app->getSession()->setNotice(Craft::t('app', 'Quote successfully rejected'));
        return $this->redirect(UrlHelper::cpUrl('translated/orders'));
    }

    public function actionViewOrder(int $id)
    {
        $order = translated::$plugin->orderService->getOrder($id);

        if (!$order) {
            Craft::$app->getSession()->setError(Craft::t('app', 'An order does not exist with that ID'));
            return $this->redirect(UrlHelper::cpUrl('translated/orders'));
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

                $dt = new \DateTime($order->dateCreated);

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

        if ($order->orderStatus > 1) {
            $getOrderStatus = translated::$plugin->orderService->getOrderStatus($id);

            if (!$getOrderStatus) {
                $orderStatusFromHTS = null;
            } else {
                $massage = (array) $getOrderStatus;
                $orderStatusFromHTS = $massage[0];
            }
        }

        $user = Craft::$app->getUser();
        if ($user->checkPermission('translated:orders:syncdata')) {
            $syncOrder = true;
        }

        return $this->renderTemplate('translated/orders/view', [
            'order' => $order,
            'orderPermissions' => $orderPermissions ?? false,
            'requestQuote' => $requestQuote ?? false,
            'statusFlag' => '<span class="label order-status ' . strtolower($status) . '">' . $status . '</span>',
            'serviceLevel' => $service,
            'orderStatusFromHTS' => $orderStatusFromHTS ?? null,
            'inSandbox' => translated::$plugin->getSettings()->translatedSandbox,
            'syncOrder' => $syncOrder ?? false
        ]);
    }

    public function actionManualDownload($fp)
    {
        $filepath = Craft::$app->getPath()->getTempAssetUploadsPath() . DIRECTORY_SEPARATOR . $fp;

        if (file_exists($filepath)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/csv');
            header('Content-Disposition: attachment; filename=' . basename($filepath));
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filepath));
            ob_clean();
            flush();

            readfile($filepath);
            exit();
        } else {
            Craft::$app->getSession()->setError(Craft::t('app', 'Failed to download autogenerated file'));
            return $this->redirect(Craft::$app->getRequest()->referrer);
        }
    }

    public function actionGetDeliveryFile($orderId)
    {
        if ($orderId) {
            $data = translated::$plugin->orderService->getOrder($orderId);

            if (!$data) {
                Craft::$app->getSession()->setError(Craft::t('app', 'Failed to get delivery file'));
                return $this->redirect(Craft::$app->getRequest()->referrer);
            }
        }

        $deliveryBlob = base64_decode($data['translatedContent']);
        $assetId = $data['translationAsset'];

        $asset = Asset::find()
            ->id($data['translationAsset'])
            ->one();
        $type = $asset->getMimeType();
        $ext = $asset->extension;

        header('Content-Description: File Transfer');
        header('Content-Type:' . $type . ';charset=utf-8');
        header(
            'Content-Disposition: attachment; filename=delivery_file_' .
                $orderId .
                '_pid#' .
                $data['quotePID'] .
                '.' .
                $ext
        );
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');

        if (ob_get_contents() || ob_get_length()) {
            ob_end_clean();
        }

        flush();

        echo $deliveryBlob;
        exit();
    }

    public function actionSyncResponse($orderId)
    {
        if ($orderId) {
            $data = translated::$plugin->orderService->getOrder($orderId);

            if (!$data) {
                Craft::$app->getSession()->setError(Craft::t('app', 'Failed to get delivery file'));
                return $this->redirect(Craft::$app->getRequest()->referrer);
            }
        }

        $availableSites = translated::$plugin->utilityService->fetchAvailableSites();

        return $this->renderTemplate('translated/orders/sync', [
            'data' => $data,
            'availableSites' => $availableSites
        ]);
    }

    public function actionSyncOrder()
    {
        $settings = translated::$plugin->getSettings();
        $data = Craft::$app->getRequest()->getBodyParam('order', []);
        $syncData = translated::$plugin->dataService->updateEntryFromTranslationCSV($data);

        if (!$syncData) {
            Craft::$app->getSession()->setError(Craft::t('app', 'Failed to get order'));
            return $this->redirect(Craft::$app->getRequest()->referrer);
        }

        $element = Entry::find()
            ->id($data['entryId'])
            ->siteId($data['siteId'])
            ->one();

        if (isset($syncData['title'])) {
            $element->title = $syncData['title'] != '' ? $syncData['title'] : $element->title;
            unset($syncData['title']);
        }

        if (isset($syncData['slug']) && $settings['translateSlugs']) {
            $element->slug = $syncData['slug'] != '' ? $syncData['slug'] : $element->slug;
            unset($syncData['slug']);
        }

        $element->setFieldValues($syncData);

        $success = Craft::$app->elements->saveElement($element, true, false);

        if (!$success) {
            Craft::$app->getSession()->setError(Craft::t('app', 'Failed to sync data to entry'));
            return $this->redirect(Craft::$app->getRequest()->referrer);
        } else {
            Craft::$app->getSession()->setNotice(Craft::t('app', 'Translated data synced to entry'));
            return $this->redirect(UrlHelper::cpUrl('translated/orders/sync/' . $data['id']));
        }
    }

    public function actionWebhook()
    {
        $pid = $_POST['pid'];
        $text = $_POST['text'];

        if (!$pid || !$text) {
            return false;
        }

        return translated::$plugin->orderService->acceptDelivery($pid, $text);
    }
}

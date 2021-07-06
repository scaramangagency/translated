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
use scaramangagency\translated\services\DataService;

use Craft;
use craft\web\Controller;
use craft\elements\Asset;
use craft\elements\Entry;
use craft\helpers\UrlHelper;
use putyourlightson\logtofile\LogToFile;

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

    public function actionAutogenerate($id, $siteId)
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
        } else {
            $data['failedUpload'] = basename($csv['path']);
        }

        $data['projectName'] = $element->title;
        $data['wordCount'] = translated::$plugin->dataService->getWordCount($element);
        $data['translationNotes'] = "Please translate text from RAW column into TRANSLATED column ONLY. \r\n";
        $data['entryId'] = $element->id;
        $data['auto'] = 1;

        $availableLanguages = translated::$plugin->utilityService->fetchAvailableLanguages($settings);
        $availableSubjects = translated::$plugin->utilityService->fetchAvailableSubjects($settings);

        return $this->renderTemplate('translated/orders/new', [
            'availableLanguages' => $availableLanguages['optionList'],
            'availableSubjects' => $availableSubjects,
            'elementType' => Asset::class,
            'selectedSource' => $availableLanguages['selectedSource'],
            'selectedTarget' => $availableLanguages['selectedTarget'],
            'data' => $data ?? null
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
            // TODO: show some sort of nice message saying couldnt get the file
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
        header('Content-Type:' . $type);
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
        ob_clean();
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
        $data = Craft::$app->getRequest()->getBodyParam('order', []);
        $syncData = translated::$plugin->dataService->updateEntryFromTranslationCSV($data);

        if (!$syncData) {
            Craft::$app->getSession()->setError(Craft::t('app', 'Failed to get order'));
            return $this->redirect(Craft::$app->getRequest()->referrer);
        }

        $element = Entry::find()
            ->id($data['entryId'])
            ->one();

        $element->siteId = $data['siteId'];

        if (isset($syncData['title'])) {
            $element->title = $syncData['title'];
            unset($syncData['title']);
        }
        if (isset($syncData['slug'])) {
            $element->slug = $syncData['slug'];
            unset($syncData['slug']);
        }

        $element->setFieldValues($syncData);

        $success = Craft::$app->elements->saveElement($element, true, false);

        if (!$success) {
            Craft::$app->getSession()->setError(Craft::t('app', 'Failed to sync data to entry'));
            return $this->redirect(Craft::$app->getRequest()->referrer);
        } else {
            Craft::$app->getSession()->setNotice(Craft::t('app', 'Translated data synced to entry'));
            return $this->redirect(UrlHelper::cpUrl('translated/orders/view/' . $data['id']));
        }
    }

    public function actionNewQuote($id = null)
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
        }

        $availableLanguages = translated::$plugin->utilityService->fetchAvailableLanguages($settings);
        $availableSubjects = translated::$plugin->utilityService->fetchAvailableSubjects($settings);

        return $this->renderTemplate('translated/orders/new', [
            'availableLanguages' => $availableLanguages['optionList'],
            'availableSubjects' => $availableSubjects,
            'elementType' => Asset::class,
            'selectedSource' => $availableLanguages['selectedSource'],
            'selectedTarget' => $availableLanguages['selectedTarget'],
            'data' => $data ?? null
        ]);
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

        if ($order->orderStatus == 2) {
            $orderStatusFromHTS = translated::$plugin->orderService->getOrderStatus($id);
        }

        return $this->renderTemplate('translated/orders/view', [
            'order' => $order,
            'orderPermissions' => $orderPermissions ?? false,
            'requestQuote' => $requestQuote ?? false,
            'statusFlag' => '<span class="label order-status ' . strtolower($status) . '">' . $status . '</span>',
            'serviceLevel' => '<span class="label order-service ' . strtolower($service) . '">' . $service . '</span>',
            'orderStatusFromHTS' => $orderStatusFromHTS ?? null,
            'inSandbox' => translated::$plugin->getSettings()->translatedSandbox
        ]);
    }

    public function actionRequestQuote()
    {
        $data = Craft::$app->getRequest()->getBodyParam('order', []);
        $quoteId = translated::$plugin->orderService->handleQuote($data);

        if (!$quoteId) {
            Craft::$app->getSession()->setError(Craft::t('app', 'Failed to generate quote'));
            return $this->redirect(UrlHelper::cpUrl('translated/orders'));
        }

        return $this->redirect(UrlHelper::cpUrl('translated/orders/view/' . $quoteId));
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

    public function actionWebhook()
    {
        $pid = $_POST['pid'];
        $text = base64_decode($_POST['text']);

        if (!$pid || !$text) {
            LogToFile::error('[Order][Handle Delivery] Failed to update record for PID:' . $pid, 'translated');
            return false;
        }

        return translated::$plugin->orderService->acceptDelivery($pid, $text);
    }
}

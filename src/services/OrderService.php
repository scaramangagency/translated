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
use scaramangagency\translated\elements\Order as Order;
use scaramangagency\translated\records\OrderRecord as OrderRecord;

use Craft;
use craft\base\Component;
use craft\elements\Asset;

use putyourlightson\logtofile\LogToFile;

/**
 * @author    Scaramanga Agency
 * @package   Translated
 * @since     1.0.0
 */
class OrderService extends Component
{
    // Public Methods
    // =========================================================================
    public function getOrder($id)
    {
        $order = new OrderRecord();
        $order = OrderRecord::findOne(['id' => $id]);

        if (!$order) {
            LogToFile::error('[Order][View] Failed to find order with specified ID', 'translated');
            return false;
        }

        return $order;
    }

    public function getOrderStatus($id)
    {
        $settings = translated::$plugin->getSettings();

        $order = new OrderRecord();
        $order = OrderRecord::findOne(['id' => $id]);

        if (!$order) {
            LogToFile::error('[Order][Status] Failed to find order with specified ID', 'translated');
            return false;
        }

        $params = [
            'cid' => Craft::parseEnv($settings['translatedUsername']),
            'p' => Craft::parseEnv($settings['translatedPassword']),
            'f' => 'status',
            'of' => 'json',
            'pid' => $order['quotePID']
        ];

        try {
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, 'https://www.translated.net/hts/?' . http_build_query($params));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $res = curl_exec($ch);
            curl_close($ch);
        } catch (Exception $e) {
            LogToFile::error('[Order][Status] Failed to get status of this order', 'translated');
            return false;
        }

        $res = json_decode($res);

        if ($res->code == 0) {
            LogToFile::error(
                '[Order][Status] translated API returned an error when getting order status. Error: ' . $res->message,
                'translated'
            );
            return false;
        }

        return $res;
    }

    public function handleQuote($data, $id = null)
    {
        $settings = translated::$plugin->getSettings();

        if ($id) {
            $orderRecord = Craft::$app->getElements()->getElementById($id, Order::class);

            if (!$orderRecord) {
                LogToFile::error('[Order][Quote] Failed to find order row with specified ID', 'translated');
                return false;
            }
        } else {
            $orderRecord = new Order();
        }

        $orderRecord->sourceLanguage = $data['sourceLanguage'];
        $orderRecord->targetLanguage = $data['targetLanguage'];
        $orderRecord->title = $data['title'];
        $orderRecord->translationLevel = $data['translationLevel'];
        $orderRecord->wordCount = $data['wordCount'];
        $orderRecord->translationSubject = $data['translationSubject'];
        $orderRecord->translationNotes = $data['translationNotes'];
        $orderRecord->userId = $data['userId'];
        $orderRecord->auto = $data['auto'];
        $orderRecord->entryId = $data['entryId'] ?? null;

        if ($data['translationAsset']) {
            $orderRecord->translationAsset = $data['translationAsset'];
        } else {
            $orderRecord->translationContent = $data['translationContent'];
        }

        $success = Craft::$app->getElements()->saveElement($orderRecord, true);

        if (!$success) {
            return ['success' => false, 'errors' => $orderRecord];
        }

        $params = [
            'cid' => Craft::parseEnv($settings['translatedUsername']),
            'p' => Craft::parseEnv($settings['translatedPassword']),
            'f' => 'quote',
            'of' => 'json',
            's' => $orderRecord['sourceLanguage'],
            't' => $orderRecord['targetLanguage'],
            'pn' => $orderRecord['title'],
            'jt' => $orderRecord['translationLevel'],
            'w' => $orderRecord['wordCount'],
            'endpoint' => rtrim(Craft::parseEnv(Craft::$app->sites->primarySite->baseUrl), '/') . '/translated/accept',
            'subject' => $orderRecord['translationSubject'],
            'instructions' => $orderRecord['translationNotes']
        ];

        if ($orderRecord['translationAsset']) {
            $translationAsset = Asset::find()
                ->id($orderRecord['translationAsset'])
                ->one();

            $params['text'] = $translationAsset->url;
            $params['df'] = explode('/', $translationAsset->mimeType)[1];
        } else {
            $params['text'] = $orderRecord['translationContent'];
        }

        try {
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, 'https://www.translated.net/hts/?' . http_build_query($params));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $res = curl_exec($ch);
            curl_close($ch);
        } catch (Exception $e) {
            LogToFile::error('[Order][Quote] Failed to generate a quote', 'translated');
            return ['success' => false, 'response' => 'translatedApi'];
        }

        $res = json_decode($res);

        if ($res->code == 0) {
            LogToFile::error(
                '[Order][Quote] translated API returned an error when generating a quote. Error: ' . $res->message,
                'translated'
            );
            return ['success' => false, 'response' => 'translatedApi'];
        }

        $success = $this->_attachQuote($orderRecord->id, $res);

        if (!$success) {
            LogToFile::error(
                '[Order][Quote] Failed to update the order record with the quote information from translated',
                'translated'
            );
            return ['success' => false, 'response' => 'translatedApi'];
        }

        return ['success' => true, 'response' => $orderRecord->id];
    }

    public function approveQuote($id)
    {
        $settings = translated::$plugin->getSettings();

        $orderRecord = new OrderRecord();
        $orderRecord = OrderRecord::findOne(['id' => $id]);

        if (!$orderRecord) {
            LogToFile::error('[Order][Approve] Failed to find order row with specified ID', 'translated');
            return false;
        }

        $params = [
            'cid' => Craft::parseEnv($settings['translatedUsername']),
            'p' => Craft::parseEnv($settings['translatedPassword']),
            'f' => 'confirm',
            'pid' => $orderRecord['quotePID'],
            'of' => 'json',
            'c' => '1'
        ];

        if ($settings['translatedSandbox']) {
            $params['sandbox'] = 1;
        }

        try {
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, 'https://www.translated.net/hts/?' . http_build_query($params));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $res = curl_exec($ch);
            curl_close($ch);
        } catch (Exception $e) {
            LogToFile::error('[Order][Approve] Failed to convert quote to an order', 'translated');
            return false;
        }

        $res = json_decode($res);

        if ($res->code == 0) {
            LogToFile::error(
                '[Order][Approve] translated API returned an error when converting this quote to an order. Error: ' .
                    $res->message,
                'translated'
            );
            return false;
        }

        $dt = new \DateTime();

        $orderRecord->setAttributes(
            [
                'orderStatus' => 2,
                'reviewedBy' => Craft::$app->getUser()->id,
                'dateApproved' => $dt
            ],
            false
        );

        $success = $orderRecord->save();

        if (!$success) {
            LogToFile::error('[Order][Approve] Failed to reject order with specified ID', 'translated');
            return false;
        }

        return true;
    }

    public function duplicateQuote($id)
    {
        $order = new OrderRecord();
        $order = OrderRecord::findOne(['id' => $id]);

        if (!$order) {
            LogToFile::error('[Order][Duplicate] Failed to find order with specified ID', 'translated');
            return false;
        }

        return $order;
    }

    public function rejectQuote($id)
    {
        $order = new OrderRecord();
        $order = OrderRecord::findOne(['id' => $id]);

        if (!$order) {
            LogToFile::error('[Order][Reject] Failed to find order with specified ID', 'translated');
            return false;
        }

        $dt = new \DateTime();

        $order->setAttributes(
            [
                'orderStatus' => 4,
                'reviewedBy' => Craft::$app->getUser()->id,
                'dateRejected' => $dt
            ],
            false
        );

        $success = $order->save();

        if (!$success) {
            LogToFile::error('[Order][Reject] Failed to reject order with specified ID', 'translated');
            return false;
        }

        return true;
    }

    public function delete(array $orders): bool
    {
        if (!$orders) {
            return false;
        }

        foreach ($orders as $order) {
            Craft::$app->elements->deleteElementById($order->id, null, null, true);
        }

        return true;
    }

    public function acceptDelivery($pid, $text)
    {
        // TODO: Add email notification that translation has been received
        $order = new OrderRecord();
        $order = OrderRecord::findOne(['quotePID' => $pid]);

        $dt = new \DateTime();

        $order->setAttributes(
            [
                'dateFulfilled' => $dt->format('c'),
                'translatedContent' => $text,
                'orderStatus' => 3
            ],
            false
        );

        $success = $order->save();

        if (!$success) {
            LogToFile::error(
                '[Order][Accept] Record received, but update has failed. Trying to mark as failed. PID:' . $pid,
                'translated'
            );

            $order->setAttributes(
                [
                    'orderStatus' => 5
                ],
                false
            );

            $success = $order->save();

            if (!$success) {
                LogToFile::error('[Order][Accept] Failed to mark as failed', 'translated');
            }

            return false;
        }

        $settings = translated::$plugin->getSettings();

        if ($settings['translatedCleanup'] && $order->translationAsset) {
            $deleteAsset = Craft::$app->elements->deleteElementById($order->translationAsset->id);

            if (!$deleteAsset) {
                LogToFile::error('[Order][Accept] Failed to delete related asset', 'translated');
            }
        }

        return true;
    }

    // Private Methods
    // =========================================================================
    private function _attachQuote($id, $res)
    {
        $order = new OrderRecord();
        $order = OrderRecord::findOne(['id' => $id]);

        $utc = new \DateTimeZone('UTC');
        $dt = new \DateTime($res->delivery_date, $utc);

        $order->setAttributes(
            [
                'quoteDeliveryDate' => $dt->format('c'),
                'quoteTotal' => $res->total,
                'quotePID' => $res->pid
            ],
            false
        );

        return $order->save();
    }
}

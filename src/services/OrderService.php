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

    public function handleQuote($data, $id = null)
    {
        $settings = Translated::$plugin->getSettings();

        if ($id) {
            $orderRecord = Order::find()
                ->id($id)
                ->isIncomplete(true)
                ->one();

            if (!$orderRecord) {
                LogToFile::error('[Order][Refresh] Failed to find order row with specified ID', 'translated');
                return false;
            }

            $dt = new \DateTime();
            $orderRecord->dateCreated = $dt;
        } else {
            $orderRecord = new Order();

            $orderRecord->sourceLanguage = $data['sourceLanguage'];
            $orderRecord->targetLanguage = $data['targetLanguage'];
            $orderRecord->projectTitle = $data['projectTitle'];
            $orderRecord->translationLevel = $data['translationLevel'];
            $orderRecord->wordCount = $data['wordCount'];
            $orderRecord->translationSubject = $data['translationSubject'];
            $orderRecord->translationNotes = $data['translationNotes'];
            $orderRecord->userId = $data['userId'];
            $orderRecord->orderStatus = 1;

            if ($data['translationAsset']) {
                $orderRecord->translationAsset = $data['translationAsset'];
            } else {
                $orderRecord->translationContent = $data['translationContent'];
            }
        }

        $success = Craft::$app->elements->saveElement($orderRecord, true, true, true);

        if (!$success) {
            LogToFile::error('[Order][Generate] Failed to save the order record', 'translated');
            return false;
        }

        $params = [
            'cid' => Craft::parseEnv($settings['translatedUsername']),
            'p' => Craft::parseEnv($settings['translatedPassword']),
            'f' => 'quote',
            'of' => 'json',
            's' => $orderRecord['sourceLanguage'],
            't' => implode(',', $orderRecord['targetLanguage']),
            'pn' => $orderRecord['projectTitle'],
            'jt' => $orderRecord['translationLevel'],
            'w' => $orderRecord['wordCount'],
            'endpoint' => rtrim(Craft::parseEnv(Craft::$app->sites->primarySite->baseUrl), '/') . '/translated-api',
            'subject' => $orderRecord['translationSubject'],
            'instructions' => $orderRecord['translationNotes']
        ];

        if ($orderRecord['translationAsset']) {
            $translationAsset = Asset::find()
                ->id($orderRecord['translationAsset'])
                ->one();

            $params['text'] = $translationAsset->url;
            $params['df'] = $translationAsset->mimeType;
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
            LogToFile::error('[Order][Generate] Failed to generate a quote', 'translated');
            return false;
        }

        $res = json_decode($res);

        if ($res->code == 0) {
            LogToFile::error(
                '[Order][Quote] translated API returned an error when generating a quote. Error: ' . $res->message,
                'translated'
            );
            return false;
        }

        $utc = new \DateTimeZone('UTC');
        $dt = new \DateTime($res->delivery_date, $utc);

        $orderRecord->quoteDeliveryDate = $dt->format('c');
        $orderRecord->quoteTotal = $res->total;
        $orderRecord->quotePID = $res->pid;

        $success = Craft::$app->elements->saveElement($orderRecord, true, true, true);

        if (!$success) {
            LogToFile::error(
                '[Order][Quote] Failed to update the order record with the quote information from translated',
                'translated'
            );
            return false;
        }

        return $orderRecord->id;
    }

    public function approveQuote($id)
    {
        $settings = Translated::$plugin->getSettings();

        $orderRecord = new OrderRecord();
        $orderRecord = OrderRecord::findOne(['id' => $id]);

        $dt = new \DateTime();
        $orderRecord->setAttribute('dateCreated', $dt);

        if (!$orderRecord) {
            LogToFile::error('[Order][Refresh] Failed to find order row with specified ID', 'translated');
            return false;
        }

        $params = [
            'cid' => Craft::parseEnv($settings['translatedUsername']),
            'p' => Craft::parseEnv($settings['translatedPassword']),
            'pid' => $orderRecord['quotePID'],
            'c' => '1',
            'sandbox' => 1
        ];

        try {
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, 'https://www.translated.net/hts/?' . http_build_query($params));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $res = curl_exec($ch);
            curl_close($ch);
        } catch (Exception $e) {
            LogToFile::error('[Order][Place] Failed to convert quote to an order', 'translated');
            return false;
        }

        $res = json_decode($res);

        if ($res->code == 0) {
            LogToFile::error(
                '[Order][Place] translated API returned an error when converting this quote to an order. Error: ' .
                    $res->message,
                'translated'
            );
            return false;
        }

        return true;
    }

    public function duplicateQuote($id)
    {
        $order = new OrderRecord();
        $order = OrderRecord::findOne(['id' => $id]);

        if (!$order) {
            LogToFile::error('[Order][Reject] Failed to find order with specified ID', 'translated');
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

        $success = Craft::$app->elements->saveElement($order, false);

        if (!$success) {
            LogToFile::error('[Order][Reject] Failed to reject order with specified ID', 'translated');
            return false;
        }

        return true;
    }
}

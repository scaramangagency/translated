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
        $params = [
            'id' => $id
        ];

        $order = new OrderRecord();
        $order = OrderRecord::findOne($params);

        if (!$order) {
            return false;
        }

        return $order;
    }

    public function getQuote($data)
    {
        $settings = Translated::$plugin->getSettings();
        $orderRecord = new OrderRecord();

        $orderRecord->setAttributes(
            [
                'sourceLanguage' => $data['sourceLanguage'],
                'targetLanguage' => $data['targetLanguage'],
                'title' => $data['title'],
                'translationLevel' => $data['translationLevel'],
                'wordCount' => $data['wordCount'],
                'translationSubject' => $data['translationSubject'],
                'translationNotes' => $data['translationNotes'],
                'userId' => $data['userId'],
                'orderStatus' => 1
            ],
            false
        );

        if ($data['translationAsset']) {
            $orderRecord->setAttribute('translationAsset', $data['translationAsset']);
        } else {
            $orderRecord->setAttribute('translationContent', $data['translationContent']);
        }

        if (!$orderRecord->save()) {
            LogToFile::error('Failed to save the order record', 'translated');
            return false;
        }

        $params = [
            'cid' => Craft::parseEnv($settings['translatedUsername']),
            'p' => Craft::parseEnv($settings['translatedPassword']),
            'f' => 'quote',
            'of' => 'json',
            's' => $data['sourceLanguage'],
            't' => implode(',', $data['targetLanguage']),
            'pn' => $data['title'],
            'jt' => $data['translationLevel'],
            'w' => $data['wordCount'],
            'endpoint' => rtrim(Craft::parseEnv(Craft::$app->sites->primarySite->baseUrl), '/') . '/translated-api',
            'subject' => $data['translationSubject'],
            'instructions' => $data['translationNotes']
        ];

        if ($data['translationAsset']) {
            $translationAsset = Asset::find()
                ->id($data['translationAsset'])
                ->one();

            $params['text'] = $translationAsset->url;
            $params['df'] = $translationAsset->mimeType;
        } else {
            $params['text'] = $data['translationContent'];
        }

        try {
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, 'https://www.translated.net/hts/?' . http_build_query($params));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $res = curl_exec($ch);
            curl_close($ch);
        } catch (Exception $e) {
            LogToFile::error('Failed to generate a quote', 'translated');
            return false;
        }

        $res = json_decode($res);

        if ($res->code == 0) {
            LogToFile::error(
                'translated API returned an error when generating a quote. Error: ' . $res->message,
                'translated'
            );
            return false;
        }

        $utc = new \DateTimeZone('UTC');
        $dt = new \DateTime($res->delivery_date, $utc);

        $orderRecord->setAttributes(
            [
                'quoteDeliveryDate' => $dt->format('c'),
                'quoteTotal' => $res->total,
                'quotePID' => $res->pid
            ],
            false
        );

        if (!$orderRecord->save()) {
            LogToFile::error(
                'Failed to update the order record with the quote information from translated',
                'translated'
            );
            return false;
        }

        return true;
    }
}

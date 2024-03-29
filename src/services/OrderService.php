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
use craft\helpers\Template;
use craft\helpers\UrlHelper;
use craft\mail\Message;

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
            return false;
        }

        return $order;
    }

    public function getOrderStatus($id)
    {
        $settings = translated::$plugin->getSettings();

        $order = new OrderRecord();
        $order = OrderRecord::findOne(['id' => $id]);

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
            return false;
        }

        $res = json_decode($res);

        if ($res->code == '0') {
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
                return ['success' => false, 'response' => Craft::t('translated', 'Could not find order record')];
            }

            $orderRecord->dateCreated = new \DateTime();
            $orderRecord->dateUpdated = new \DateTime();

            $user = Craft::$app->getUser();
            $orderRecord->userId = $user->id;

            $success = Craft::$app->getElements()->saveElement($orderRecord, false);
        } else {
            $orderRecord = new Order();

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
            return [
                'success' => false,
                'response' => Craft::t('translated', 'Sorry, there appears to be an issue with the translated API')
            ];
        }

        $res = json_decode($res);

        if ($res->code == 0) {
            return [
                'success' => false,
                'response' => Craft::t('translated', 'Sorry, there appears to be an issue with the translated API')
            ];
        }

        $success = $this->_attachQuote($orderRecord->id, $res);

        if (!$success) {
            return [
                'success' => false,
                'response' => Craft::t('translated', 'Sorry, there appears to be an issue with the translated API')
            ];
        }

        return ['success' => true, 'response' => $orderRecord->id];
    }

    public function approveQuote($id)
    {
        $settings = translated::$plugin->getSettings();

        $orderRecord = new OrderRecord();
        $orderRecord = OrderRecord::findOne(['id' => $id]);

        if (!$orderRecord) {
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
            return false;
        }

        $res = json_decode($res);

        if ($res->code == 0) {
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
            return false;
        }

        return true;
    }

    public function duplicateQuote($id)
    {
        $order = new OrderRecord();
        $order = OrderRecord::findOne(['id' => $id]);

        if (!$order) {
            return false;
        }

        return $order;
    }

    public function rejectQuote($id)
    {
        $order = new OrderRecord();
        $order = OrderRecord::findOne(['id' => $id]);

        if (!$order) {
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
            $order->setAttributes(
                [
                    'orderStatus' => 5
                ],
                false
            );

            $success = $order->save();
            return false;
        }

        $settings = translated::$plugin->getSettings();

        if ($settings['translatedNotifications']) {
            $emails = explode(',', $settings['translatedNotificationEmail']);

            // https://craftcms.stackexchange.com/a/27728
            $message = new Message();
            $systemMessage = Craft::$app->getSystemMessages()->getMessage('test_email', $message->language);

            $view = Craft::$app->getView();
            $templateMode = $view->getTemplateMode();
            $view->setTemplateMode($view::TEMPLATE_MODE_SITE);

            $language = Craft::$app->language;
            if ($message->language !== null) {
                Craft::$app->language = $message->language;
            }

            $settings = Craft::$app->getSystemSettings()->getEmailSettings();
            $variables = ($message->variables ?: []) + [
                'emailKey' => $message->key,
                'fromEmail' => $settings->fromEmail,
                'fromName' => $settings->fromName
            ];

            if (Craft::$app->getEdition() === Craft::Pro && Craft::$app->getMailer()->template) {
                $template = Craft::$app->getMailer()->template;
            } else {
                $view->setTemplateMode($view::TEMPLATE_MODE_CP);
                $template = '_special/email';
            }

            $e = null;

            $htmlBody =
                '<p>Good news. Your translation has been completed and is ready for you to review.</p>' .
                '<p><a style="padding: 8px 12px; display: inline-block; border-radius: 6px; background-color: #E12D39; color: white; font-weight: 500" href="' .
                UrlHelper::cpUrl() .
                '/translated/orders/view/' .
                $order->id .
                '">View your translated file</a></p>';

            try {
                $htmlBody = $view->renderTemplate(
                    $template,
                    array_merge($variables, [
                        'body' => Template::raw($htmlBody)
                    ])
                );
            } catch (TemplateLoaderException $e) {
            }

            Craft::$app->language = $language;
            $view->setTemplateMode($templateMode);

            if ($e !== null) {
                throw $e;
            }

            if (count($emails) > 0) {
                foreach ($emails as $email) {
                    try {
                        $message
                            ->setSubject('Translation has been delivered')
                            ->setHtmlBody($htmlBody)
                            ->setTo($email)
                            ->send();
                    } catch (Exception $e) {
                    }
                }
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

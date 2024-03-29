<?php
/**
 * translated plugin for Craft CMS 3.x
 *
 * Request translations via translated from the comfort of your dashboard
 *
 * @link      https://scaramanga.agency
 * @copyright Copyright (c) 2021 Scaramanga Agency
 */

namespace scaramangagency\translated\models;

use scaramangagency\translated\Translated;

use Craft;
use craft\base\Model;

/**
 * @author    Scaramanga Agency
 * @package   Translated
 * @since     1.0.0
 */
class Settings extends Model
{
    // Public Properties
    // =========================================================================

    public $translatedUsername;
    public $translatedPassword;
    public $translatedSandbox = false;
    public $translateSlugs = true;
    public $translatedNotifications = false;
    public $translatedNotificationEmail;
    public $translatedAutogenerationDirectory;

    // Public Methods
    // =========================================================================

    public function rules()
    {
        return [
            [['translatedUsername', 'translatedPassword'], 'required'],
            ['translatedSandbox', 'boolean'],
            ['translateSlugs', 'boolean'],
            ['translatedNotifications', 'boolean'],
            ['translatedNotificationEmail', 'string'],
            ['translatedAutogenerationDirectory', 'integer']
        ];
    }
}

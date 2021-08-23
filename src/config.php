<?php
/**
 * translated plugin for Craft CMS 3.x
 *
 * Request translations via translated from the comfort of your dashboard
 *
 * @link      https://scaramanga.agency
 * @copyright Copyright (c) 2021 Scaramanga Agency
 */

/**
 * @author    Scaramanga Agency
 * @package   Translated
 * @since     1.0.0
 */

/**
 * Translated config.php
 *
 * This file exists only as a template for the Translated settings.
 * It does nothing on its own.
 *
 * Don't edit this file, instead copy it to 'craft/config' as 'translated.php'
 * and make your changes there to override default settings.
 *
 * Once copied to 'craft/config', this file will be multi-environment aware as
 * well, so you can have different settings groups for each environment, just as
 * you do for 'general.php'
 */

return [
    // Do you want to use the sandbox to test your integration?
    'translatedSandbox' => false,

    // Should Translated send slugs to be translated along with the content?
    'translateSlugs' => true,

    // Should Translated send delivery notifications
    'translatedNotifications' => false,

    // Which emails should Translated send delivery notifications to?
    'translatedNotificationEmail' => ''
];

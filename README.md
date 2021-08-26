# translated plugin for Craft CMS 3.x

Website Internationalisation is fine until you need to suddenly need to speak 10 different languages and fluently enough to translate technical content. This is where the fabulous Translated plugin steps in. Select a page. Click the Translate button. Send it to the Translated translation service. Approve the quote. Receive the translated content back directly in Craft and sync it to the original Craft entry. Et voilà!

There are no nasty surprises. _Translated plugin_ displays a quote from Translated, so you know what the translation will cost in advance\* &mdash; with no sneaky markup from Scaramanga!

With Translated's tiered service levels (Economy, Premium or Professional) it is easy to control costs. Use the Economy level for the less important pages and Professional for key pages where the translation needs to be spot on.

The _Translated plugin_ puts content authors in full control. Translate what you want, when you want. Whether that's an entire website or a single page. With 195 languages covered and 40 areas of expertise, the **Translated plugin** is perfect for maintaining the content for multi-language websites on an ongoing basis.

\*The estimate is based on word count, which the plugin automatically generates from the extracted content. This should be accurate; however, it is possible that the invoiced amount differs lightly from the estimate due to a final word count. This could be slightly lower or higher than the estimated amount.

## Requirements

This plugin requires Craft CMS 3.1.0 or later.

## Before you begin

The Translated plugin requires a Translated account. If you do not already have a Translated account, ensure you have requested one at <https://translated.com/contact-us>. Translated accounts are provided on a request basis.

## Installing the Translated plugin

To install the plugin, follow these instructions.

1.  Open your terminal and go to your Craft project:

        cd /path/to/project

2.  Then tell Composer to load the plugin:

        composer require scaramangagency/translated

3.  In the Control Panel, go to Settings → Plugins and click the “Install” button for Translated.

You can also visit the [Craft Plugin Store](https://plugins.craftcms.com/), search for _Translated_ and click install.

## Configuring Translated

### Assign user permissions

The _Translated_ plugin offers granular user permission, which you must add to existing and new user groups.

Define whether a user and/or user group has permission to:

-   View orders
-   Request quotes
-   Authorise quotes
-   Sync translated data

### Plugin settings

The following options are available under the _Settings_ tab:

-   **Translated username** &mdash; Your Translated username.
-   **Translated password** &mdash; Your Translated password.
-   **Translate slugs** &mdash; Do you want entry slugs to be translated?
-   **Asset volume** &mdash; Assign a volume to store autogenerated and manually uploaded files.
-   **Sandbox mode** &mdash; Enable the Sandbox mode for testing. Any files submitted with Sandbox mode enabled will not be translated and no transaction will occur. You will be returned the initial file you have supplied.

The _Translated_ plugin can be configured to send a notification email every time a new translation is delivered by translated under the the _Notifications_ tab:

-   **Send notifications** &mdash; Send a notification when a translation file is delivered by translated.
-   **Notification email(s)** &mdash; A comma separated list of email addresses to whom the notification email will be sent.

The Translated username and Translated password can be set as environment variables. All other settings can be configured using the plugin config file.

## Using Translated

### Translate from Entry

1. Go to an entry and click the **Translate** button. A CSV file will be automatically generated from the translatable fields assigned to the entry that match the following field types:
    - Plain Text
    - Redactor
2. Fill in the **Generate a new quote** form and hit **Request Quote**. The plugin will best-guess a word count from the generated CSV.
3. Review the order and click the **Approve quote** or **Reject Quote** buttons.

Please note that the _Translated plugin_ also works with the following nestable field types:

-   Matrix
-   Supertable
-   Neo

#### Important notes

-   A Section must be assigned to more than one site before the **Translate** button appears. If the button is not visible on the Entry then check your Section settings.
-   Only fields assigned as translatable (using the Translation Method select menu for each field) will be included in the file sent to Translated. Matrix, Supertable, and Neo fields themselves do not have to be set as translatable, as the plugin will scan them for translatable Plain Text and Redactor fields.
-   The autogenerated file will source the content from the **currently selected site**. So, for example, the autogenerated file will contain the English content if you are on the English site or French content if you are on the French site.

### Translate from a file

It is possible to request manual translations via the plugin, rather than using the autogeneration feature.

1. Go to _Orders > Place New Order_ and fill out the **Generate a new quote** form. You can paste in plain text into the Content field or upload a file. Please note that manual orders will not show the _Sync to Entry_ button on the delivered quote page.
2. Review the order and click the **Approve quote** or **Reject Quote** buttons.

## Translated Sections

### Generate a new quote

The order form contains the following fields:

-   **Project Name** &mdash; This will be automatically generated from the entry title, but you can modify it or create your own.
-   **Translation Level** &mdash; Translated provides three levels of service, with different price points that will be reflected in the estimate:
    -   [£] Economy &mdash; Machine Translation with light human review.
    -   [££] Premium &mdash; Human Translation with quality control.
    -   [£££] Professional &mdash; Human Translation with specialist review and quality control.
-   **Source Language** &mdash; Please identify the language of the text to be translated.
-   **Target Language** &mdash; Please identify the language that you want to be supplied.
-   **Genre** &mdash; Select a category that best describes the subject matter of your text to help allocate this to the most appropriate translator.
-   **Word Count** &mdash; This will be automatically generated from the submitted entry, however, if you are uploading your own document then you will need to provide this information.
-   **Notes** &mdash; Provide additional notes to the translator. This will be automatically populated when requesting a translation from an Entry.
-   **Select file to translate** &mdash; Accepted formats include CSV, Word and Text. Your translation will be returned in the same file format.
-   **Content** &mdash; A free text field to provide content.

### Orders page

A list of all your orders, which can be filtered by status.

### Deleting an order

On the Orders page, select the order by clicking the checkbox and then select **Delete** under the setting (cog icon) menu.

Please note that the _Translated_ plugin will not delete any associated files relating to the order. We recommend taking a note of the file name (located under the _Order information_ tab on the **Orders Entry** page) before deleting the Order and then locate and delete the file in the **Assets** section.

### Order Entry page

Once a quote has been requested, the Orders Entry page will display the following **Order information** about your order:

-   **Original entry** &mdash; A link to the original entry.
-   **Level** &mdash; The service level requested for the translation.
-   **Source Language**
-   **Target Language**
-   **Genre**
-   **Word Count** &mdash; An estimate of the total word count.
-   **Notes** &mdash; Any notes added to the order.
-   **Link** &mdash; A link to the CSV file generated by the plugin and submitted to Translated

The meta panel contains the estimated total cost for the translation, the Entry's status and the estimated delivery date.
**Please note that the estimate price is always quoted in Euros and cannot be changed.**

In addition, the following three action buttons are available:

-   Duplicate Quote (useful for re-submitting the quote for a different language)
-   Reject Quote
-   Approve Quote

You will need to either **Reject** or **Approve** the quote.

If you leave a quote for 24 hours, it automatically expires. Once a quote has expired, there is the option to refresh the quote.

### Orders Entry page (post approval)

If you have configured the plugin to send notifications, you will be notified via email that your order has been fulfilled.

Revisiting the Orders Entry page will reveal an additional tab called **Delivery** where you can download a copy of the translated file and/or sync the content into the entry.

It is important to note that the translated content is not automatically synced to the entry. To complete the process, you must click the **Sync to Entry** button. On the next page, select the Site to which you wish to add the translated content and click **Sync Content**.

You will be redirected back to the **Sync Entry** page and see a success notification message.

You can now visit the entry to check everything has been applied correctly.

Brought to you by [Scaramanga Agency](https://scaramanga.agency)

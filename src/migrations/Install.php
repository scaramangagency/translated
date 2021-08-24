<?php

namespace scaramangagency\translated\migrations;

use scaramangagency\translated\Translated;
use scaramangagency\translated\elements\Order;

use Craft;
use craft\config\DbConfig;
use craft\db\Migration;
use craft\helpers\MigrationHelper;

class Install extends Migration
{
    public function safeUp()
    {
        $this->createTables();
        $this->addForeignKeys();

        return true;
    }

    public function safeDown()
    {
        $this->dropForeignKeys();
        $this->dropTables();
        $this->removeContent();
        $this->dropProjectConfig();

        return true;
    }

    protected function createTables()
    {
        $tablesCreated = false;
        $tableSchema = Craft::$app->db->schema->getTableSchema('{{%translated_orders}}');

        if ($tableSchema === null) {
            $tablesCreated = true;

            $this->createTable('{{%translated_orders}}', [
                'id' => $this->primaryKey(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'userId' => $this->integer(),
                'reviewedBy' => $this->integer(),
                'dateApproved' => $this->dateTime(),
                'dateRejected' => $this->dateTime(),
                'dateFulfilled' => $this->dateTime(),
                'orderStatus' => $this->integer(),

                'sourceLanguage' => $this->string(255),
                'targetLanguage' => $this->string(255),
                'translationContent' => $this->text(),
                'translationAsset' => $this->integer(),
                'translationNotes' => $this->text(),
                'translationSubject' => $this->text(),
                'translationLevel' => $this->string(20),
                'wordCount' => $this->integer(),
                'title' => $this->text(),

                'auto' => $this->integer(),
                'entryId' => $this->integer(),

                'quoteDeliveryDate' => $this->dateTime()->null(),
                'quoteTotal' => $this->float(),
                'quotePID' => $this->integer(),

                'translatedContent' => $this->text(),

                'uid' => $this->uid()
            ]);
        }

        return $tablesCreated;
    }

    protected function addForeignKeys()
    {
        $this->addForeignKey(null, '{{%translated_orders}}', 'userId', '{{%users}}', 'id', 'CASCADE', null);
        $this->addForeignKey(null, '{{%translated_orders}}', 'reviewedBy', '{{%users}}', 'id', 'CASCADE', null);
        $this->addForeignKey(null, '{{%translated_orders}}', 'id', '{{%elements}}', 'id', 'CASCADE', null);
        $this->addForeignKey(null, '{{%translated_orders}}', 'entryId', '{{%entries}}', 'id', 'CASCADE', null);
        $this->addForeignKey(null, '{{%translated_orders}}', 'translationAsset', '{{%assets}}', 'id', 'CASCADE', null);
    }

    protected function dropTables()
    {
        $this->dropTableIfExists('{{%translated_orders}}');
    }

    protected function dropForeignKeys()
    {
        MigrationHelper::dropForeignKeyIfExists(
            '{{%translated_orders}}',
            ['userId', 'id', 'reviewedBy', 'translationAsset'],
            $this
        );
    }

    protected function removeContent()
    {
        $this->delete('{{%elements}}', ['type' => Order::class]);
    }

    protected function dropProjectConfig()
    {
        Craft::$app->projectConfig->remove('translated');
    }
}

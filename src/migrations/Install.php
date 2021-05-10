<?php

namespace scaramangagency\translated\migrations;

use scaramangagency\translated\Translated;

use Craft;
use craft\config\DbConfig;
use craft\db\Migration;
use craft\helpers\MigrationHelper;

class Install extends Migration {

    public function safeUp() {
        $this->createTables();
        $this->addForeignKeys();
    }

    public function safeDown() {
        $this->dropForeignKeys();
        $this->dropTables();
    }

    protected function createTables() {
        $tablesCreated = false;
        $tableSchema = Craft::$app->db->schema->getTableSchema('{{%translated_orders}}');

        if ($tableSchema === null) {
            $tablesCreated = true;

            $this->createTable('{{%translated_orders}}', [
                'id' => $this->primaryKey(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'userId' => $this->integer(),
                'authorisedBy' => $this->integer(),
                'dateOrdered' => $this->dateTime(),
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

                'quoteDeliveryDate' => $this->dateTime(),
                'quoteTotal' => $this->float(),
                'quotePID' => $this->integer(),

                'uid' => $this->uid()
            ]);
        }

        return $tablesCreated;
    }

    protected function addForeignKeys() {
        $this->addForeignKey($this->db->getForeignKeyName('{{%translated_orders}}', 'userId'), '{{%translated_orders}}', 'userId', '{{%users}}', 'id', 'CASCADE', null);
    }

    protected function dropTables() {
        $this->dropTableIfExists('{{%translated_orders}}');
    }

    protected function dropForeignKeys() {
        MigrationHelper::dropForeignKeyIfExists('{{%translated_orders}}', ['userId'], $this);
    }
}

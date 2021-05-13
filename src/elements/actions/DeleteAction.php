<?php

namespace scaramangagency\translated\elements\actions;

use scaramangagency\translated\Translated;

use craft\base\ElementAction;
use craft\elements\db\ElementQueryInterface;

class DeleteAction extends ElementAction
{
    public $confirmationMessage;
    public $successMessage;

    public function getTriggerLabel(): string
    {
        return 'Delete';
    }

    public static function isDestructive(): bool
    {
        return true;
    }

    public function getConfirmationMessage()
    {
        return $this->confirmationMessage;
    }

    public function performAction(ElementQueryInterface $query): bool
    {
        translated::$plugin->orderService->delete($query->all());

        $this->setMessage($this->successMessage);

        return true;
    }
}

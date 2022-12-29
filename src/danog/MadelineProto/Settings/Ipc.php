<?php

namespace danog\MadelineProto\Settings;

use danog\MadelineProto\Magic;
use danog\MadelineProto\SettingsAbstract;

/**
 * IPC server settings.
 */
class Ipc extends SettingsAbstract
{
    public function __construct()
    {
        Magic::start(true);
    }

    public function mergeArray(array $settings): void
    {
    }

    /**
     * Get WARNING: this will cause slow startup if enabled.
     *
     */
    public function getSlow(): bool
    {
        return Magic::$isIpcWorker;
    }
}

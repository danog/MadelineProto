<?php

declare(strict_types=1);

namespace danog\MadelineProto\Settings;

use danog\MadelineProto\Magic;
use danog\MadelineProto\SettingsAbstract;

/**
 * IPC server settings.
 */
final class Ipc extends SettingsAbstract
{
    public function __construct()
    {
        Magic::start(light: true);
    }

    public function mergeArray(array $settings): void
    {
    }

    /**
     * Get WARNING: this will cause slow startup if enabled.
     */
    public function getSlow(): bool
    {
        return Magic::$isIpcWorker;
    }
}

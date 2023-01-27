<?php

declare(strict_types=1);

namespace danog\MadelineProto;

final class SettingsEmpty extends SettingsAbstract
{
    public function mergeArray(array $settings): void
    {
    }
}

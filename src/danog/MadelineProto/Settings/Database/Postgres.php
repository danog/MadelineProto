<?php

namespace danog\MadelineProto\Settings\Database;

/**
 * Postgres backend settings.
 */
class Postgres extends SqlAbstract
{
    public function mergeArray(array $settings): void
    {
        $settings = $settings['db']['postgres'] ?? [];
        if (isset($settings['host'])) {
            $this->setUri("tcp://".($settings['host']).(isset($settings['port']) ? ':'.($settings['port']) : ''));
        }
        parent::mergeArray($settings);
    }
}

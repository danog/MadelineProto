<?php

namespace danog\MadelineProto\Settings\Database;

/**
 * MySQL backend settings.
 */
class Mysql extends SqlAbstract
{
    public function mergeArray(array $settings): void
    {
        $settings = $settings['db']['mysql'] ?? [];
        if (isset($settings['host'])) {
            $this->setUri("tcp://".($settings['host']).(isset($settings['port']) ? ':'.($settings['port']) : ''));
        }
        parent::mergeArray($settings);
    }
}

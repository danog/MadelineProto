<?php

namespace danog\MadelineProto\Db;

use danog\MadelineProto\Logger;
use danog\MadelineProto\MTProto;

trait DbPropertiesTrait
{
    public function initDb(MTProto $MadelineProto, bool $reset = false): \Generator
    {
        if (empty($this->dbProperies)) {
            throw new \LogicException(__CLASS__ . ' must have a $dbProperies');
        }
        $dbSettings = $MadelineProto->settings['db'];
        $prefix = static::getSessionId($MadelineProto);

        foreach ($this->dbProperies as $property => $type) {
            if ($reset) {
                unset($this->{$property});
            } else {
                $this->{$property} = yield DbPropertiesFabric::get($dbSettings, $prefix, $type, $property, $this->{$property});
            }
        }

        if (!$reset && yield $this->usernames->count() === 0) {
            $this->logger('Filling database cache. This can take few minutes.', Logger::WARNING);
            $iterator = $this->chats->getIterator();
            while (yield $iterator->advance()) {
                [$id, $chat] = $iterator->getCurrent();
                if (isset($chat['username'])) {
                    $this->usernames[\strtolower($chat['username'])] = $this->getId($chat);
                }
            }
            $this->logger('Cache filled.', Logger::WARNING);
        }
    }

    private static function getSessionId(MTProto $madelineProto): string
    {
        $result = $madelineProto->getSelf()['id'] ?? null;
        if (!$result) {
            $result = 'tmp_';
            $result .= \str_replace('0', '', \spl_object_hash($madelineProto));
        }

        $className = \explode('\\', __CLASS__);
        $result .= '_' . \end($className);
        return $result;
    }
}

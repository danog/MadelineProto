<?php

namespace danog\MadelineProto\Db;

use danog\MadelineProto\Logger;

abstract class DriverArray implements DbArray
{
    use ArrayCacheTrait;

    public function __destruct()
    {
        $this->stopCacheCleanupLoop();
    }


    public function offsetExists($index): bool
    {
        throw new \RuntimeException('Native isset not support promises. Use isset method');
    }

    abstract protected function initConnection(array $settings): \Generator;

    /**
     * @param self $new
     * @param DbArray|array|null $old
     *
     * @return \Generator
     * @throws \Throwable
     */
    protected static function migrateDataToDb(self $new, $old): \Generator
    {
        if (!empty($old) && !$old instanceof static) {
            Logger::log('Converting database.', Logger::ERROR);

            if ($old instanceof DbArray) {
                $old = yield $old->getArrayCopy();
            } else {
                $old = (array) $old;
            }
            $counter = 0;
            $total = \count($old);
            foreach ($old as $key => $item) {
                $counter++;
                if ($counter % 500 === 0) {
                    yield $new->offsetSet($key, $item);
                    Logger::log("Loading data to table {$new->table}: $counter/$total", Logger::WARNING);
                } else {
                    $new->offsetSet($key, $item);
                }
            }
            Logger::log('Converting database done.', Logger::ERROR);
        }
    }
}

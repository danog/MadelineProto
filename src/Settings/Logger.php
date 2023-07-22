<?php

declare(strict_types=1);

namespace danog\MadelineProto\Settings;

use Closure;
use danog\MadelineProto\Logger as MadelineProtoLogger;
use danog\MadelineProto\Magic;
use danog\MadelineProto\SettingsAbstract;
use danog\MadelineProto\Tools;

use const PHP_SAPI;

/**
 * Logger settings.
 */
final class Logger extends SettingsAbstract
{
    /**
     * Logger type.
     *
     * @var MadelineProtoLogger::LOGGER_* $type Logger type.
     */
    protected int $type;

    /**
     * Extra parameter for logger.
     *
     * @var null|callable|string
     */
    protected $extra;

    /**
     * Logging level.
     *
     * @var MadelineProtoLogger::LEVEL_*
     */
    protected int $level = MadelineProtoLogger::LEVEL_VERBOSE;

    /**
     * Maximum filesize for logger, in case of file logging.
     */
    protected int $maxSize = 1 * 1024 * 1024;

    public function mergeArray(array $settings): void
    {
        if (!isset($settings['logger']['logger_param']) && isset($settings['logger']['param'])) {
            $settings['logger']['logger_param'] = $settings['logger']['param'];
        }
        if (PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg' && isset($settings['logger']['logger_param']) && $settings['logger']['logger_param'] === 'MadelineProto.log') {
            $settings['logger']['logger_param'] = Magic::$script_cwd.'/MadelineProto.log';
        }
        switch ($settings['logger']['logger_level'] ?? null) {
            case 'ULTRA_VERBOSE':
                $settings['logger']['logger_level'] = 5;
                break;
            case 'VERBOSE':
                $settings['logger']['logger_level'] = 4;
                break;
            case 'NOTICE':
                $settings['logger']['logger_level'] = 3;
                break;
            case 'WARNING':
                $settings['logger']['logger_level'] = 2;
                break;
            case 'ERROR':
                $settings['logger']['logger_level'] = 1;
                break;
            case 'FATAL ERROR':
                $settings['logger']['logger_level'] = 0;
                break;
        }
        if (isset($settings['logger']['logger'])) {
            $this->setType($settings['logger']['logger']);
        }
        if (isset($settings['logger']['logger_param'])) {
            $this->setExtra($settings['logger']['logger_param']);
        }
        if (isset($settings['logger']['logger_level'])) {
            $this->setLevel($settings['logger']['logger_level']);
        }
        if (isset($settings['logger']['max_size'])) {
            $this->setMaxSize($settings['logger']['max_size'] ?? 1 * 1024 * 1024);
        }

        $this->init();
    }
    public function __construct()
    {
        $this->type = (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg')
            ? MadelineProtoLogger::ECHO_LOGGER
            : MadelineProtoLogger::FILE_LOGGER;
        Magic::start(light: true);
        $this->extra = Magic::$script_cwd.'/MadelineProto.log';
    }

    public function __sleep()
    {
        return $this->extra instanceof Closure
            ? ['type', 'level', 'maxSize']
            : ['type', 'extra', 'level', 'maxSize'];
    }
    /**
     * Wakeup function.
     */
    public function __wakeup(): void
    {
        $this->type = (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg')
            ? MadelineProtoLogger::ECHO_LOGGER
            : MadelineProtoLogger::FILE_LOGGER;
        if (!$this->extra && $this->type === MadelineProtoLogger::FILE_LOGGER) {
            $this->extra = Magic::$script_cwd.'/MadelineProto.log';
        }

        $this->init();
    }
    /**
     * Initialize global logging.
     */
    private function init(): void
    {
        Magic::start(light: true);
        MadelineProtoLogger::constructorFromSettings($this);
    }
    /**
     * Get $type Logger type.
     *
     * @return MadelineProtoLogger::LOGGER_*
     */
    public function getType(): int
    {
        return \defined('MADELINE_WORKER') ? MadelineProtoLogger::FILE_LOGGER : $this->type;
    }

    /**
     * Set $type Logger type.
     *
     * @param MadelineProtoLogger::LOGGER_* $type $type Logger type.
     */
    public function setType(int $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get extra parameter for logger.
     *
     */
    public function getExtra(): callable|string|null
    {
        return $this->type === MadelineProtoLogger::FILE_LOGGER
            ? Tools::absolute($this->extra)
            : $this->extra;
    }

    /**
     * Set extra parameter for logger.
     *
     * @param null|callable|string $extra Extra parameter for logger.
     */
    public function setExtra(callable|string|null $extra): self
    {
        if ($this->type === MadelineProtoLogger::CALLABLE_LOGGER && !\is_callable($extra)) {
            $this->setType((PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg')
                    ? MadelineProtoLogger::ECHO_LOGGER
                    : MadelineProtoLogger::FILE_LOGGER);
            return $this;
        }
        $this->extra = $extra;

        return $this;
    }

    /**
     * Get logging level.
     *
     * @return MadelineProtoLogger::LEVEL_*
     */
    public function getLevel(): int
    {
        return $this->level;
    }

    /**
     * Set logging level.
     *
     * @param MadelineProtoLogger::LEVEL_* $level Logging level.
     */
    public function setLevel(int $level): self
    {
        $this->level = \max($level, MadelineProtoLogger::NOTICE);

        return $this;
    }

    /**
     * Get maximum filesize for logger, in case of file logging.
     */
    public function getMaxSize(): int
    {
        return $this->maxSize;
    }

    /**
     * Set maximum filesize for logger, in case of file logging.
     *
     * @param int $maxSize Maximum filesize for logger, in case of file logging.
     */
    public function setMaxSize(int $maxSize): self
    {
        $this->maxSize = $maxSize === -1 ? $maxSize : \max($maxSize, 25 * 1024 * 1024);

        return $this;
    }
}

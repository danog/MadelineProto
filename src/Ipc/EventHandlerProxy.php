<?php declare(strict_types=1);

namespace danog\MadelineProto\Ipc;

/**
 * Plugin event handler proxy object, for use through the IPC API.
 */
final class EventHandlerProxy extends IpcCapable
{
    public function __construct(
        protected readonly ?string $__plugin,
        Client $API
    ) {
        parent::__construct($API);
    }
    public function __call(string $name, array $arguments): mixed
    {
        return $this->getClient()->callPluginMethod(
            $this->__plugin,
            $name,
            $arguments
        );
    }
    public function __get(string $name): mixed
    {
        return $this->getClient()->getPluginProperty($this->__plugin, $name);
    }
    public function __set(string $name, mixed $value): void
    {
        $this->getClient()->setPluginProperty($this->__plugin, $name, $value);
    }
    public function __isset(string $name): bool
    {
        return $this->getClient()->issetPluginProperty($this->__plugin, $name);
    }
    public function __unset(string $name): void
    {
        $this->getClient()->unsetPluginProperty($this->__plugin, $name);
    }
}

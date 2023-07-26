<?php declare(strict_types=1);

namespace danog\MadelineProto\Ipc;

use danog\MadelineProto\MTProto;

/**
 * Represents an IPC-capable object.
 *
 * @internal
 */
abstract class IpcCapable
{
    protected readonly string $session;
    private MTProto|Client|null $API;

    /** @internal */
    protected function __construct(MTProto|Client $API)
    {
        $this->API = $API;
        $this->session = $API->getSessionName();
    }

    /** @internal */
    final public function __sleep()
    {
        $vars = \get_object_vars($this);
        unset($vars['API']);
        return \array_keys($vars);
    }

    final protected function getClient(): MTProto|Client
    {
        return $this->API ??= Client::giveInstanceBySession($this->session);
    }

    final public function __debugInfo()
    {
        $vars = \get_object_vars($this);
        unset($vars['API']);
        return $vars;
    }
}

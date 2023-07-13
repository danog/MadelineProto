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
    private readonly string $session;
    protected MTProto|Client|null $API;

    /** @internal */
    protected function __construct(
        MTProto|Client $API,
    ) {
        $this->API = $API;
        if ($API instanceof MTProto) {
            $this->session = $API->wrapper->getSession()->getSessionDirectoryPath();
        } else {
            $this->session = $API->getSession()->getSessionDirectoryPath();
        }
    }

    /** @internal */
    public function __sleep()
    {
        $vars = \get_object_vars($this);
        unset($vars['API']);
        return \array_keys($vars);
    }
    /** @internal */
    public function __wakeup(): void
    {
        $this->API = Client::giveInstanceBySession($this->session);
    }

    public function __debugInfo()
    {
        $vars = \get_object_vars($this);
        unset($vars['API']);
        return $vars;
    }
}

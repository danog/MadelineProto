<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler;

use danog\MadelineProto\Ipc\Client;
use danog\MadelineProto\MTProto;

/**
 * Represents a generic update.
 */
abstract class Update
{
    private readonly string $session;
    protected MTProto|Client|null $API;

    /** @internal */
    protected function __construct(
        MTProto $API,
    ) {
        $this->API = $API;
        $this->session = $API->wrapper->getSession()->getSessionDirectoryPath();
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
}

<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler;

use danog\MadelineProto\API;
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
    public static function fromRawUpdate(
        MTProto $API,
        array $rawUpdate
    ): ?self {
        return match ($rawUpdate['_']) {
            'updateNewMessage' => Message::fromRawUpdate($API, $rawUpdate['message']),
            'updateNewChannelMessage' => $API->getType($rawUpdate) === API::PEER_TYPE_CHANNEL
                ? ChannelMessage::fromRawUpdate($API, $rawUpdate['message'])
                : Message::fromRawUpdate($API, $rawUpdate['message']),
            default => null
        };
    }
    /** @internal */
    protected function __construct(
        MTProto $API,
        public readonly array $rawUpdate
    ) {
        $this->API = $API;
        $this->session = $API->wrapper->getSession()->getSessionDirectoryPath();
    }

    public function __sleep()
    {
        $vars = \get_object_vars($this);
        unset($vars['API']);
        return \array_keys($vars);
    }
    public function __wakeup(): void
    {
        $this->API = Client::giveInstanceBySession($this->session);
    }
}

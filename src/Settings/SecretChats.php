<?php

declare(strict_types=1);

namespace danog\MadelineProto\Settings;

use danog\MadelineProto\SettingsAbstract;

/**
 * Secret chat settings.
 */
final class SecretChats extends SettingsAbstract
{
    /**
     * What secret chats to accept.
     *
     * Boolean or array of IDs
     *
     * @var bool|array<int>
     */
    protected bool|array $accept = true;

    public function mergeArray(array $settings): void
    {
        if (isset($settings['secret_chats']['accept_chats'])) {
            $this->setAccept($settings['secret_chats']['accept_chats']);
        }
    }
    /**
     * Get boolean or array of IDs.
     *
     * @return bool|array<int>
     */
    public function getAccept(): bool|array
    {
        return $this->accept;
    }

    /**
     * Set boolean or array of IDs.
     *
     * @param bool|array<int> $accept Boolean or array of IDs
     */
    public function setAccept(bool|array $accept): self
    {
        $this->accept = $accept;

        return $this;
    }

    /**
     * Can we accept this chat.
     *
     * @internal
     */
    public function canAccept(int $id): bool
    {
        if (!$this->accept) {
            return false;
        }
        if ($this->accept === true) {
            return true;
        }
        return \in_array($id, $this->accept, true);
    }
}

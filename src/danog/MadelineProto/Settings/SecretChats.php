<?php

namespace danog\MadelineProto\Settings;

use danog\MadelineProto\SettingsAbstract;

/**
 * Secret chat settings.
 */
class SecretChats extends SettingsAbstract
{
    /**
     * What secret chats to accept.
     *
     * Boolean or array of IDs
     *
     * @var bool|array<int>
     */
    protected $accept = true;

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
    public function getAccept()
    {
        return $this->accept;
    }

    /**
     * Set boolean or array of IDs.
     *
     * @param bool|array<int> $accept Boolean or array of IDs
     *
     * @return self
     */
    public function setAccept($accept): self
    {
        $this->accept = $accept;

        return $this;
    }

    /**
     * Can we accept this chat.
     *
     * @internal
     *
     * @param integer $id
     * @return boolean
     */
    public function canAccept(int $id): bool
    {
        if (!$this->accept) {
            return false;
        }
        if ($this->accept === true) {
            return true;
        }
        return \in_array($id, $this->accept);
    }
}

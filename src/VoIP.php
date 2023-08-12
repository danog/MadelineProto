<?php

declare(strict_types=1);

/*
Copyright 2016-2018 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with MadelineProto.
If not, see <http://www.gnu.org/licenses/>.
*/

namespace danog\MadelineProto;

use danog\MadelineProto\EventHandler\Update;
use danog\MadelineProto\VoIP\CallState;

final class VoIP extends Update
{
    /** Phone call ID */
    public readonly int $callID;
    /** Whether the call is an outgoing call */
    public readonly bool $outgoing;
    /** ID of the other user in the call */
    public readonly int $otherID;
    /** ID of the creator of the call */
    public readonly int $creatorID;
    /** When was the call created */
    public readonly int $date;

    /**
     * Constructor.
     *
     * @internal
     */
    public function __construct(
        MTProto $API,
        array $call
    ) {
        parent::__construct($API);
        $call['_'] = 'inputPhoneCall';
        $this->date = $call['date'];
        $this->callID = $call['id'];
        if ($call['_'] === 'phoneCallWaiting') {
            $this->outgoing = false;
            $this->otherID = $call['participant_id'];
            $this->creatorID = $call['admin_id'];
        } else {
            $this->outgoing = true;
            $this->otherID = $call['admin_id'];
            $this->creatorID = $call['participant_id'];
        }
    }

    /**
     * Accept call.
     */
    public function accept(): self
    {
        $this->getClient()->acceptCall($this->callID);
        return $this;
    }
    /**
     * Discard call.
     */
    public function discard(array $reason = ['_' => 'phoneCallDiscardReasonDisconnect'], array $rating = []): self
    {
        $this->getClient()->discardCall($this->callID, $reason, $rating);
        return $this;
    }

    /**
     * Get call emojis (will return null if the call is not inited yet).
     *
     * @return ?list{string, string, string, string}
     */
    public function getVisualization(): ?array
    {
        return $this->getClient()->getCallVisualization($this->callID);
    }

    /**
     * Play file.
     */
    public function play(string $file): self
    {
        $this->getClient()->callPlay($this->callID, $file);

        return $this;
    }

    /**
     * Play file.
     */
    public function then(string $file): self
    {
        $this->getClient()->callPlay($this->callID, $file);

        return $this;
    }

    /**
     * Files to play on hold.
     */
    public function playOnHold(array $files): self
    {
        $this->getClient()->callPlayOnHold($this->callID, $files);

        return $this;
    }

    /**
     * Get call state.
     */
    public function getCallState(): CallState
    {
        return $this->getClient()->getCallState($this->callID) ?? CallState::ENDED;
    }
    /**
     * Get call representation.
     */
    public function __toString(): string
    {
        return "call {$this->callID} with {$this->otherID}";
    }
}

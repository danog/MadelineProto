<?php declare(strict_types=1);

/**
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Mahdi <mahdi.talaee1379@gmail.com>
 * @copyright 2016-2023 Mahdi <mahdi.talaee1379@gmail.com>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\EventHandler\Message\Private;

use danog\MadelineProto\EventHandler\Media\Audio;
use danog\MadelineProto\EventHandler\Media\Decrypted as SecretMedias;
use danog\MadelineProto\EventHandler\Media\Document;
use danog\MadelineProto\EventHandler\Media\DocumentPhoto;
use danog\MadelineProto\EventHandler\Media\Gif;
use danog\MadelineProto\EventHandler\Media\MaskSticker;
use danog\MadelineProto\EventHandler\Media\Photo;
use danog\MadelineProto\EventHandler\Media\RoundVideo;
use danog\MadelineProto\EventHandler\Media\Sticker;
use danog\MadelineProto\EventHandler\Media\Video;
use danog\MadelineProto\EventHandler\Media\Voice;
use danog\MadelineProto\EventHandler\Message\Entities\MessageEntity;
use danog\MadelineProto\MTProto;

final class SecretMessage extends AbstractPrivateMessage
{
    /** Content of the message */
    public readonly string $message;
    /** ID of the chat where the message was sent */
    public readonly int $chatId;
    /** When was the message sent */
    public readonly int $date;
    /** Whether the webpage preview is disabled */
    public readonly ?bool $noWebpage;
    /** Whether this message was sent without any notification (silently) */
    public readonly bool $silent;
    /** Time-to-live of the message */
    public readonly ?int $ttlPeriod;
    /**
     * Attached media.
     */
    public readonly Audio|Document|DocumentPhoto|Gif|MaskSticker|Photo|RoundVideo|Sticker|Video|Voice|SecretMedias\Document|SecretMedias\Photo|SecretMedias\Video|null $media;
    /** @var MessageEntity Message [entities](https://core.telegram.org/api/entities) for styled text */
    public readonly array $entities;
    /**  Specifies the ID of the inline bot that generated the message (parameter added in layer 45) */
    public readonly ?string $viaBotName;
    /** Random message ID of the message this message replies to (parameter added in layer 45) */
    public readonly ?int $replyToRandomId;

    /** @internal */
    public function __construct(MTProto $API, array $rawMessage, array $info)
    {
        parent::__construct($API, $rawMessage, $info);
        $decryptedMessage = $rawMessage['decrypted_message'];
        $this->chatId = $rawMessage['chat_id'];
        $this->message = $decryptedMessage['message'] ?? '';
        $this->date = $rawMessage['date'];
        $this->noWebpage = $decryptedMessage['no_webpage'] ?? null;
        $this->silent = $decryptedMessage['silent'];
        $this->ttlPeriod = $decryptedMessage['ttl'] ?? null;
        $this->media = isset($decryptedMessage['media'])
            ? $API->wrapMedia($decryptedMessage['media'], true)
            : null;
        $this->entities = MessageEntity::fromRawEntities($decryptedMessage['entities'] ?? []);
        $this->viaBotName = $decryptedMessage['via_bot_name'] ?? null;
        $this->replyToRandomId = $decryptedMessage['reply_to_random_id'] ?? null;
    }
}

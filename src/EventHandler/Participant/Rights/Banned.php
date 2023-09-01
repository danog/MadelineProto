<?php declare(strict_types=1);

/**
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\EventHandler\Participant\Rights;

use danog\MadelineProto\EventHandler\Participant\Rights;

/**
 * Represents the rights of a normal user in a [supergroup/channel/chat](https://core.telegram.org/api/channel). In this case, the flags are inverted: if set, a flag does not allow a user to do X.
 */
final class Banned extends Rights
{
    /** If set, does not allow a user to view messages in a [supergroup/chat](https://core.telegram.org/api/channel) */
    public readonly bool $viewMessages;

    /** If set, does not allow a user to send messages in a [supergroup/chat](https://core.telegram.org/api/channel) */
    public readonly bool $sendMessages;

    /** If set, does not allow a user to send any media in a [supergroup/chat](https://core.telegram.org/api/channel) */
    public readonly bool $sendMedia;

    /** If set, does not allow a user to send stickers in a [supergroup/chat](https://core.telegram.org/api/channel) */
    public readonly bool $sendStickers;

    /** If set, does not allow a user to send stickers in a [supergroup/chat](https://core.telegram.org/api/channel) */
    public readonly bool $sendGifs;

    /** If set, does not allow a user to send games in a [supergroup/chat](https://core.telegram.org/api/channel) */
    public readonly bool $sendGames;

    /** If set, does not allow a user to use inline bots in a [supergroup/chat](https://core.telegram.org/api/channel) */
    public readonly bool $sendInline;

    /** If set, does not allow a user to embed links in the messages of a [supergroup/chat](https://core.telegram.org/api/channel) */
    public readonly bool $embedLinks;

    /** If set, does not allow a user to send polls in a [supergroup/chat](https://core.telegram.org/api/channel) */
    public readonly bool $sendPolls;

    /** If set, does not allow any user to change the description of a [supergroup/chat](https://core.telegram.org/api/channel) */
    public readonly bool $changeInfo;

    /** If set, does not allow any user to invite users in a [supergroup/chat](https://core.telegram.org/api/channel) */
    public readonly bool $inviteUsers;

    /** If set, does not allow any user to pin messages in a [supergroup/chat](https://core.telegram.org/api/channel) */
    public readonly bool $pinMessages;

    /** If set, does not allow any user to create, delete or modify [forum topics »](https://core.telegram.org/api/forum#forum-topics). */
    public readonly bool $manageTopics;

    /** If set, does not allow a user to send photos in a [supergroup/chat](https://core.telegram.org/api/channel) */
    public readonly bool $sendPhotos;

    /** If set, does not allow a user to send videos in a [supergroup/chat](https://core.telegram.org/api/channel) */
    public readonly bool $sendVideos;

    /** If set, does not allow a user to send round videos in a [supergroup/chat](https://core.telegram.org/api/channel) */
    public readonly bool $sendRoundvideos;

    /** If set, does not allow a user to send audio files in a [supergroup/chat](https://core.telegram.org/api/channel) */
    public readonly bool $sendAudios;

    /** If set, does not allow a user to send documents in a [supergroup/chat](https://core.telegram.org/api/channel) */
    public readonly bool $sendDocs;

    /** If set, does not allow a user to send text messages in a [supergroup/chat](https://core.telegram.org/api/channel) */
    public readonly bool $sendPlain;

    /** Validity of said permissions (it is considered forever any value less then 30 seconds or more then 366 days). */
    public readonly int $untilDate;

    /** @internal */
    public function __construct(
        array $rawRights
    ) {
        $this->viewMessages = $rawRights['view_messages'];
        $this->sendMessages = $rawRights['send_messages'];
        $this->sendMedia = $rawRights['send_media'];
        $this->sendStickers = $rawRights['send_stickers'];
        $this->sendGifs = $rawRights['send_gifs'];
        $this->sendGames = $rawRights['send_games'];
        $this->sendInline = $rawRights['send_inline'];
        $this->embedLinks = $rawRights['embed_links'];
        $this->sendPolls = $rawRights['send_polls'];
        $this->changeInfo = $rawRights['change_info'];
        $this->inviteUsers = $rawRights['invite_users'];
        $this->pinMessages = $rawRights['pin_messages'];
        $this->manageTopics = $rawRights['manage_topics'];
        $this->sendPhotos = $rawRights['send_photos'];
        $this->sendVideos = $rawRights['send_videos'];
        $this->sendRoundvideos = $rawRights['send_roundvideos'];
        $this->sendAudios = $rawRights['send_audios'];
        $this->sendDocs = $rawRights['send_docs'];
        $this->sendPlain = $rawRights['send_plain'];
        $this->untilDate = $rawRights['until_date'];
    }
}

<?php declare(strict_types=1);

/**
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Amir Hossein Jafari <amirhosseinjafari8228@gmail.com>
 * @copyright 2016-2023 Amir Hossein Jafari <amirhosseinjafari8228@gmail.com>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\EventHandler;

use JsonSerializable;

/**
 * Typing events
 */
enum MessageAction : string implements JsonSerializable
{
    /** User is typing. */
    case TYPING = 'sendMessageTypingAction';
    /** Invalidate all previous action updates. E.g. when user deletes entered text or aborts a video upload. */
    case CANCEL = 'sendMessageCancelAction';
    /** User is playing a game. */
    case GAMING = 'sendMessageGamePlayAction';
    /** User is recording a voice message. */
    case RECORD_AUDIO = 'sendMessageRecordAudioAction';
    /** User is recording a round video to share. */
    case RECORD_ROUND = 'sendMessageRecordRoundAction';
    /** User is recording a video. */
    case RECORD_VIDEO = 'sendMessageRecordVideoAction';
    /** User is uploading a video. */
    case UPLOAD_VIDEO = 'sendMessageUploadVideoAction'; //! progress
    /** User is uploading a round video. */
    case UPLOAD_ROUND = 'sendMessageUploadRoundAction'; //! progress
    /** User is uploading a voice message. */
    case UPLOAD_AUDIO = 'sendMessageUploadAudioAction'; //! progress
    /** User is uploading a photo. */
    case UPLOAD_PHOTO = 'sendMessageUploadPhotoAction'; //! progress
    /** User is uploading a file. */
    case UPLOAD_DOCUMENT = 'sendMessageUploadDocumentAction'; //! progress
    /** Chat history is being imported. */
    case HISTORY_IMPORT = 'sendMessageHistoryImportAction'; //! progress
    /** User is selecting a location to share. */
    case GEO_LOCATION = 'sendMessageGeoLocationAction';
    /** User is selecting a contact to share. */
    case CHOOSE_CONTACT = 'sendMessageChooseContactAction';
    /** User is choosing a sticker. */
    case CHOOSE_STICKER = 'sendMessageChooseStickerAction';
    /** User is currently speaking in the group call. */
    case GROUP_CALL_SPEAKING = 'speakingInGroupCallAction';
    /** User is watching an animated emoji reaction triggered by another user, [click here for more info »](https://core.telegram.org/api/animated-emojis#emoji-reactions). */
    case EMOJI_SEEN = 'sendMessageEmojiInteractionSeen';
    // TODO: sendMessageEmojiInteraction

    /** @internal */
    public function jsonSerialize(): string
    {
        return $this->name;
    }
}

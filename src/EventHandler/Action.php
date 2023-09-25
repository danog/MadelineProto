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

use danog\MadelineProto\EventHandler\Action\Cancel;
use danog\MadelineProto\EventHandler\Action\ChooseContact;
use danog\MadelineProto\EventHandler\Action\ChooseSticker;
use danog\MadelineProto\EventHandler\Action\EmojiSeen;
use danog\MadelineProto\EventHandler\Action\EmojiTap;
use danog\MadelineProto\EventHandler\Action\GamePlay;
use danog\MadelineProto\EventHandler\Action\GeoLocation;
use danog\MadelineProto\EventHandler\Action\GroupCallSpeaking;
use danog\MadelineProto\EventHandler\Action\HistoryImport;
use danog\MadelineProto\EventHandler\Action\RecordAudio;
use danog\MadelineProto\EventHandler\Action\RecordRound;
use danog\MadelineProto\EventHandler\Action\RecordVideo;
use danog\MadelineProto\EventHandler\Action\Typing;
use danog\MadelineProto\EventHandler\Action\UploadAudio;
use danog\MadelineProto\EventHandler\Action\UploadDocument;
use danog\MadelineProto\EventHandler\Action\UploadPhoto;
use danog\MadelineProto\EventHandler\Action\UploadRound;
use danog\MadelineProto\EventHandler\Action\UploadVideo;
use JsonSerializable;
use ReflectionClass;
use ReflectionProperty;

/**
 * In-progress actions.
 */
abstract class Action implements JsonSerializable
{
    /** @internal */
    public static function fromRawAction(array $rawAction): Action
    {
        return match ($rawAction['_']) {
            'sendMessageTypingAction' => new Typing,
            'sendMessageCancelAction' => new Cancel,
            'sendMessageGamePlayAction' => new GamePlay,
            'sendMessageGeoLocationAction' => new GeoLocation,
            'sendMessageChooseContactAction' => new ChooseContact,
            'sendMessageChooseStickerAction' => new ChooseSticker,
            'sendMessageRecordAudioAction' => new RecordAudio,
            'sendMessageRecordRoundAction' => new RecordRound,
            'sendMessageRecordVideoAction' => new RecordVideo,
            'speakingInGroupCallAction' => new GroupCallSpeaking,
            'sendMessageUploadVideoAction' => new UploadVideo($rawAction['progress']),
            'sendMessageUploadRoundAction' => new UploadRound($rawAction['progress']),
            'sendMessageUploadAudioAction' => new UploadAudio($rawAction['progress']),
            'sendMessageUploadPhotoAction' => new UploadPhoto($rawAction['progress']),
            'sendMessageUploadDocumentAction' => new UploadDocument($rawAction['progress']),
            'sendMessageHistoryImportAction' => new HistoryImport($rawAction['progress']),
            'sendMessageEmojiInteractionSeen' => new EmojiSeen($rawAction['emoticon']),
            'sendMessageEmojiInteraction' => new EmojiTap(
                $rawAction['emoticon'],
                $rawAction['msg_id'],
                $rawAction['interaction']['a']
            ),
        };
    }

    /** @internal */
    public function toRawAction(): array
    {
        return match (true) {
            $this instanceof Typing =>  [ '_' => 'sendMessageTypingAction' ],
            $this instanceof Cancel =>  [ '_' => 'sendMessageCancelAction' ],
            $this instanceof GamePlay => [ '_' => 'sendMessageGamePlayAction' ],
            $this instanceof GeoLocation => [ '_' => 'sendMessageGeoLocationAction' ],
            $this instanceof ChooseContact => [ '_' => 'sendMessageChooseContactAction' ],
            $this instanceof ChooseSticker => [ '_' => 'sendMessageChooseStickerAction' ],
            $this instanceof RecordAudio => [ '_' => 'sendMessageRecordAudioAction' ],
            $this instanceof RecordRound => [ '_' => 'sendMessageRecordRoundAction' ],
            $this instanceof RecordVideo => [ '_' => 'sendMessageRecordVideoAction' ],
            $this instanceof UploadVideo => [ '_' => 'sendMessageUploadVideoAction'],
            $this instanceof UploadRound => [ '_' => 'sendMessageUploadRoundAction'],
            $this instanceof UploadAudio => [ '_' => 'sendMessageUploadAudioAction'],
            $this instanceof UploadPhoto => [ '_' => 'sendMessageUploadPhotoAction'],
            $this instanceof UploadDocument => [ '_' => 'sendMessageUploadDocumentAction'],
            $this instanceof HistoryImport => [ '_' => 'sendMessageHistoryImportAction'],
            $this instanceof GroupCallSpeaking => [ '_' => 'speakingInGroupCallAction' ],
            $this instanceof EmojiSeen => [ '_' => 'sendMessageEmojiInteractionSeen' ],
            $this instanceof EmojiTap => [ '_' => 'sendMessageEmojiInteraction' ],
        };
    }

    /** @internal */
    public function jsonSerialize(): mixed
    {
        $res = ['_' => static::class];
        $refl = new ReflectionClass($this);
        foreach ($refl->getProperties(ReflectionProperty::IS_PUBLIC) as $prop) {
            $res[$prop->getName()] = $prop->getValue($this);
        }
        return $res;
    }
}

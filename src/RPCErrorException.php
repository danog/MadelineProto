<?php

declare(strict_types=1);

/**
 * RPCErrorException module.
 *
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

namespace danog\MadelineProto;

use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\Request;
use Throwable;

use const PHP_EOL;

use const PHP_SAPI;

/**
 * Indicates an error returned by Telegram's API.
 */
class RPCErrorException extends \Exception
{
    use TL\PrettyException;
    /** @internal */
    public static array $descriptions = ['RPC_MCGET_FAIL' => 'Telegram is having internal issues, please try again later.', 'RPC_CALL_FAIL' => 'Telegram is having internal issues, please try again later.', 'USER_PRIVACY_RESTRICTED' => "The user's privacy settings do not allow you to do this", 'CHANNEL_PRIVATE' => "You haven't joined this channel/supergroup", 'USER_IS_BOT' => "Bots can't send messages to other bots", 'BOT_METHOD_INVALID' => 'This method cannot be run by a bot', 'PHONE_CODE_EXPIRED' => 'The phone code you provided has expired, this may happen if it was sent to any chat on telegram (if the code is sent through a telegram chat (not the official account) to avoid it append or prepend to the code some chars)', 'USERNAME_INVALID' => 'The provided username is not valid', 'ACCESS_TOKEN_INVALID' => 'The provided token is not valid', 'ACTIVE_USER_REQUIRED' => 'The method is only available to already activated users', 'FIRSTNAME_INVALID' => 'The first name is invalid', 'LASTNAME_INVALID' => 'The last name is invalid', 'PHONE_NUMBER_INVALID' => 'The phone number is invalid', 'PHONE_CODE_HASH_EMPTY' => 'phone_code_hash is missing', 'PHONE_CODE_EMPTY' => 'phone_code is missing', 'API_ID_INVALID' => 'The api_id/api_hash combination is invalid', 'PHONE_NUMBER_OCCUPIED' => 'The phone number is already in use', 'PHONE_NUMBER_UNOCCUPIED' => 'The phone number is not yet being used', 'USERS_TOO_FEW' => 'Not enough users (to create a chat, for example)', 'USERS_TOO_MUCH' => 'The maximum number of users has been exceeded (to create a chat, for example)', 'TYPE_CONSTRUCTOR_INVALID' => 'The type constructor is invalid', 'FILE_PART_INVALID' => 'The file part number is invalid', 'FILE_PARTS_INVALID' => 'The number of file parts is invalid', 'MD5_CHECKSUM_INVALID' => 'The MD5 checksums do not match', 'PHOTO_INVALID_DIMENSIONS' => 'The photo dimensions are invalid', 'FIELD_NAME_INVALID' => 'The field with the name FIELD_NAME is invalid', 'FIELD_NAME_EMPTY' => 'The field with the name FIELD_NAME is missing', 'MSG_WAIT_FAILED' => 'A waiting call returned an error', 'USERNAME_NOT_OCCUPIED' => 'The provided username is not occupied', 'PHONE_NUMBER_BANNED' => 'The provided phone number is banned from telegram', 'AUTH_KEY_UNREGISTERED' => 'The authorization key has expired', 'INVITE_HASH_EXPIRED' => 'The invite link has expired', 'USER_DEACTIVATED' => 'The user was deactivated', 'USER_ALREADY_PARTICIPANT' => 'The user is already in the group', 'MESSAGE_ID_INVALID' => 'The provided message id is invalid', 'PEER_ID_INVALID' => 'The provided peer id is invalid', 'CHAT_ID_INVALID' => 'The provided chat id is invalid', 'MESSAGE_DELETE_FORBIDDEN' => "You can't delete one of the messages you tried to delete, most likely because it is a service message.", 'CHAT_ADMIN_REQUIRED' => 'You must be an admin in this chat to do this', -429 => 'Too many requests', 'PEER_FLOOD' => "You are spamreported, you can't do this"];
    /** @internal */
    public static array $errorMethodMap = [];
    private static array $fetchedError = [];

    private const BAD = [
        'PEER_FLOOD' => true,
        'USER_DEACTIVATED_BAN' => true,
        'INPUT_METHOD_INVALID' => true,
        'INPUT_FETCH_ERROR' => true,
        'AUTH_KEY_UNREGISTERED' => true,
        'SESSION_REVOKED' => true,
        'USER_DEACTIVATED' => true,
        'RPC_SEND_FAIL' => true,
        'RPC_CALL_FAIL' => true,
        'RPC_MCGET_FAIL' => true,
        'INTERDC_5_CALL_ERROR' => true,
        'INTERDC_4_CALL_ERROR' => true,
        'INTERDC_3_CALL_ERROR' => true,
        'INTERDC_2_CALL_ERROR' => true,
        'INTERDC_1_CALL_ERROR' => true,
        'INTERDC_5_CALL_RICH_ERROR' => true,
        'INTERDC_4_CALL_RICH_ERROR' => true,
        'INTERDC_3_CALL_RICH_ERROR' => true,
        'INTERDC_2_CALL_RICH_ERROR' => true,
        'INTERDC_1_CALL_RICH_ERROR' => true,
        'AUTH_KEY_DUPLICATED' => true,
        'CONNECTION_NOT_INITED' => true,
        'LOCATION_NOT_AVAILABLE' => true,
        'AUTH_KEY_INVALID' => true,
        'LANG_CODE_EMPTY' => true,
        'memory limit exit' => true,
        'memory limit(?)' => true,
        'INPUT_REQUEST_TOO_LONG' => true,
        'SESSION_PASSWORD_NEEDED' => true,
        'INPUT_FETCH_FAIL' => true,
        'CONNECTION_SYSTEM_EMPTY' => true,
        'FILE_WRITE_FAILED' => true,
        'STORAGE_CHOOSE_VOLUME_FAILED' => true,
        'xxx' => true,
        'AES_DECRYPT_FAILED' => true,
        'Timedout' => true,
        'SEND_REACTION_RESULT1_INVALID' => true,
        'BOT_POLLS_DISABLED' => true,
        'TEMPNAM_FAILED' => true,
        'MSG_WAIT_TIMEOUT' => true,
        'MEMBER_CHAT_ADD_FAILED' => true,
        'CHAT_FROM_CALL_CHANGED' => true,
        'MTPROTO_CLUSTER_INVALID' => true,
        'CONNECTION_DEVICE_MODEL_EMPTY' => true,
        'AUTH_KEY_PERM_EMPTY' => true,
        'UNKNOWN_METHOD' => true,
        'ENCRYPTION_OCCUPY_FAILED' => true,
        'ENCRYPTION_OCCUPY_ADMIN_FAILED' => true,
        'CHAT_OCCUPY_USERNAME_FAILED' => true,
        'REG_ID_GENERATE_FAILED' => true,
        'CONNECTION_LANG_PACK_INVALID' => true,
        'MSGID_DECREASE_RETRY' => true,
        'API_CALL_ERROR' => true,
        'STORAGE_CHECK_FAILED' => true,
        'INPUT_LAYER_INVALID' => true,
        'NEED_MEMBER_INVALID' => true,
        'NEED_CHAT_INVALID' => true,
        'HISTORY_GET_FAILED' => true,
        'CHP_CALL_FAIL' => true,
        'IMAGE_ENGINE_DOWN' => true,
        'MSG_RANGE_UNSYNC' => true,
        'PTS_CHANGE_EMPTY' => true,
        'CONNECTION_SYSTEM_LANG_CODE_EMPTY' => true,
        'WORKER_BUSY_TOO_LONG_RETRY' => true,
        'WP_ID_GENERATE_FAILED' => true,
        'ARR_CAS_FAILED' => true,
        'CHANNEL_ADD_INVALID' => true,
        'CHANNEL_ADMINS_INVALID' => true,
        'CHAT_OCCUPY_LOC_FAILED' => true,
        'GROUPED_ID_OCCUPY_FAILED' => true,
        'GROUPED_ID_OCCUPY_FAULED' => true,
        'LOG_WRAP_FAIL' => true,
        'MEMBER_FETCH_FAILED' => true,
        'MEMBER_OCCUPY_PRIMARY_LOC_FAILED' => true,
        'MEMBER_NO_LOCATION' => true,
        'MEMBER_OCCUPY_USERNAME_FAILED' => true,
        'MT_SEND_QUEUE_TOO_LONG' => true,
        'POSTPONED_TIMEOUT' => true,
        'RPC_CONNECT_FAILED' => true,
        'SHORTNAME_OCCUPY_FAILED' => true,
        'STORE_INVALID_OBJECT_TYPE' => true,
        'STORE_INVALID_SCALAR_TYPE' => true,
        'TMSG_ADD_FAILED' => true,
        'UNKNOWN_ERROR' => true,
        'UPLOAD_NO_VOLUME' => true,
        'USER_NOT_AVAILABLE' => true,
        'VOLUME_LOC_NOT_FOUND' => true,
        'FILE_WRITE_EMPTY' => true,
    ];

    /** @internal */
    public static function isBad(string $error, int $code, string $method): bool
    {
        return isset(self::BAD[$error])
                || str_contains($error, 'Received bad_msg_notification')
                || str_contains($error, 'FLOOD_WAIT_')
                || str_contains($error, '_MIGRATE_')
                || str_contains($error, 'INPUT_METHOD_INVALID')
                || str_contains($error, 'INPUT_CONSTRUCTOR_INVALID')
                || str_contains($error, 'INPUT_FETCH_ERROR_')
                || str_contains($error, 'https://telegram.org/dl')
                || str_starts_with($error, 'Received bad_msg_notification')
                || str_starts_with($error, 'No workers running')
                || str_starts_with($error, 'All workers are busy. Active_queries ')
                || preg_match('/FILE_PART_\d*_MISSING/', $error)
                || !preg_match('/^[a-zA-Z0-9\._]+$/', $method)
                || ($error === 'Timeout' && !\in_array(strtolower($method), ['messages.getbotcallbackanswer', 'messages.getinlinebotresults'], true))
                || ($error === 'BOT_MISSING' && \in_array($method, ['stickers.changeStickerPosition', 'stickers.createStickerSet', 'messages.uploadMedia'], true));
    }

    /**
     * @internal
     */
    private static function report(string $error, int $code, string $method): string
    {
        if (!$method || !$code || !$error) {
            return $error;
        }
        $error = preg_replace('/\\d+$/', 'X', $error);
        $description = self::$descriptions[$error] ?? '';
        if ((!isset(self::$errorMethodMap[$code][$method][$error]) || !isset(self::$descriptions[$error]))
            && !self::isBad($error, $code, $method)
        ) {
            try {
                $res = json_decode(
                    (
                        HttpClientBuilder::buildDefault()
                        ->request(new Request('https://report-rpc-error.madelineproto.xyz/?method='.$method.'&code='.$code.'&error='.$error))
                    )->getBody()->buffer(),
                    true,
                );
                if (isset($res['ok']) && $res['ok'] && isset($res['result']) && \is_string($res['result'])) {
                    $description = $res['result'];
                    self::$descriptions[$error] = $description;
                    self::$errorMethodMap[$code][$method][$error] = $error;
                }
                self::$fetchedError[$error] = true;
            } catch (Throwable) {
            }
        }
        if (!$description) {
            return $error;
        }
        return $description;
    }
    /**
     * Get string representation of exception.
     */
    public function __toString(): string
    {
        Magic::start(light: true);
        $result = sprintf(Lang::$current_lang['rpc_tg_error'], $this->description." ({$this->code})", $this->rpc, $this->file, $this->line.PHP_EOL, Magic::$revision.PHP_EOL.PHP_EOL).PHP_EOL.$this->getTLTrace().PHP_EOL;
        if (PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg') {
            $result = str_replace(PHP_EOL, '<br>'.PHP_EOL, $result);
        }
        return $result;
    }
    /**
     * Get localized error name.
     */
    public function getLocalization(): string
    {
        return $this->description;
    }

    /**
     * @internal
     */
    public static function make(
        string $rpc,
        int $code,
        string $caller,
        ?\Exception $previous = null
    ): self {
        // Start match
        return match ($rpc) {
            'ABOUT_TOO_LONG' => new \danog\MadelineProto\RPCError\AboutTooLongError($caller, $previous),
            'ACCESS_TOKEN_EXPIRED' => new \danog\MadelineProto\RPCError\AccessTokenExpiredError($caller, $previous),
            'ACCESS_TOKEN_INVALID' => new \danog\MadelineProto\RPCError\AccessTokenInvalidError($caller, $previous),
            'ADDRESS_INVALID' => new \danog\MadelineProto\RPCError\AddressInvalidError($caller, $previous),
            'ADMIN_ID_INVALID' => new \danog\MadelineProto\RPCError\AdminIdInvalidError($caller, $previous),
            'ADMIN_RANK_EMOJI_NOT_ALLOWED' => new \danog\MadelineProto\RPCError\AdminRankEmojiNotAllowedError($caller, $previous),
            'ADMIN_RANK_INVALID' => new \danog\MadelineProto\RPCError\AdminRankInvalidError($caller, $previous),
            'ADMIN_RIGHTS_EMPTY' => new \danog\MadelineProto\RPCError\AdminRightsEmptyError($caller, $previous),
            'ADMINS_TOO_MUCH' => new \danog\MadelineProto\RPCError\AdminsTooMuchError($caller, $previous),
            'ALBUM_PHOTOS_TOO_MANY' => new \danog\MadelineProto\RPCError\AlbumPhotosTooManyError($caller, $previous),
            'API_ID_INVALID' => new \danog\MadelineProto\RPCError\ApiIdInvalidError($caller, $previous),
            'API_ID_PUBLISHED_FLOOD' => new \danog\MadelineProto\RPCError\ApiIdPublishedFloodError($caller, $previous),
            'ARTICLE_TITLE_EMPTY' => new \danog\MadelineProto\RPCError\ArticleTitleEmptyError($caller, $previous),
            'AUDIO_CONTENT_URL_EMPTY' => new \danog\MadelineProto\RPCError\AudioContentUrlEmptyError($caller, $previous),
            'AUDIO_TITLE_EMPTY' => new \danog\MadelineProto\RPCError\AudioTitleEmptyError($caller, $previous),
            'AUTH_BYTES_INVALID' => new \danog\MadelineProto\RPCError\AuthBytesInvalidError($caller, $previous),
            'AUTH_TOKEN_ALREADY_ACCEPTED' => new \danog\MadelineProto\RPCError\AuthTokenAlreadyAcceptedError($caller, $previous),
            'AUTH_TOKEN_EXCEPTION' => new \danog\MadelineProto\RPCError\AuthTokenExceptionError($caller, $previous),
            'AUTH_TOKEN_EXPIRED' => new \danog\MadelineProto\RPCError\AuthTokenExpiredError($caller, $previous),
            'AUTH_TOKEN_INVALID' => new \danog\MadelineProto\RPCError\AuthTokenInvalidError($caller, $previous),
            'AUTH_TOKEN_INVALIDX' => new \danog\MadelineProto\RPCError\AuthTokenInvalidxError($caller, $previous),
            'AUTOARCHIVE_NOT_AVAILABLE' => new \danog\MadelineProto\RPCError\AutoarchiveNotAvailableError($caller, $previous),
            'BANK_CARD_NUMBER_INVALID' => new \danog\MadelineProto\RPCError\BankCardNumberInvalidError($caller, $previous),
            'BANNED_RIGHTS_INVALID' => new \danog\MadelineProto\RPCError\BannedRightsInvalidError($caller, $previous),
            'BOOST_NOT_MODIFIED' => new \danog\MadelineProto\RPCError\BoostNotModifiedError($caller, $previous),
            'BOOST_PEER_INVALID' => new \danog\MadelineProto\RPCError\BoostPeerInvalidError($caller, $previous),
            'BOOSTS_EMPTY' => new \danog\MadelineProto\RPCError\BoostsEmptyError($caller, $previous),
            'BOOSTS_REQUIRED' => new \danog\MadelineProto\RPCError\BoostsRequiredError($caller, $previous),
            'BOT_APP_INVALID' => new \danog\MadelineProto\RPCError\BotAppInvalidError($caller, $previous),
            'BOT_APP_SHORTNAME_INVALID' => new \danog\MadelineProto\RPCError\BotAppShortnameInvalidError($caller, $previous),
            'BOT_CHANNELS_NA' => new \danog\MadelineProto\RPCError\BotChannelsNaError($caller, $previous),
            'BOT_COMMAND_DESCRIPTION_INVALID' => new \danog\MadelineProto\RPCError\BotCommandDescriptionInvalidError($caller, $previous),
            'BOT_COMMAND_INVALID' => new \danog\MadelineProto\RPCError\BotCommandInvalidError($caller, $previous),
            'BOT_DOMAIN_INVALID' => new \danog\MadelineProto\RPCError\BotDomainInvalidError($caller, $previous),
            'BOT_GROUPS_BLOCKED' => new \danog\MadelineProto\RPCError\BotGroupsBlockedError($caller, $previous),
            'BOT_INLINE_DISABLED' => new \danog\MadelineProto\RPCError\BotInlineDisabledError($caller, $previous),
            'BOT_INVALID' => new \danog\MadelineProto\RPCError\BotInvalidError($caller, $previous),
            'BOT_MISSING' => new \danog\MadelineProto\RPCError\BotMissingError($caller, $previous),
            'BOT_ONESIDE_NOT_AVAIL' => new \danog\MadelineProto\RPCError\BotOnesideNotAvailError($caller, $previous),
            'BOT_PAYMENTS_DISABLED' => new \danog\MadelineProto\RPCError\BotPaymentsDisabledError($caller, $previous),
            'BOT_RESPONSE_TIMEOUT' => new \danog\MadelineProto\RPCError\BotResponseTimeoutError($caller, $previous),
            'BOT_SCORE_NOT_MODIFIED' => new \danog\MadelineProto\RPCError\BotScoreNotModifiedError($caller, $previous),
            'BOT_WEBVIEW_DISABLED' => new \danog\MadelineProto\RPCError\BotWebviewDisabledError($caller, $previous),
            'BOTS_TOO_MUCH' => new \danog\MadelineProto\RPCError\BotsTooMuchError($caller, $previous),
            'BROADCAST_ID_INVALID' => new \danog\MadelineProto\RPCError\BroadcastIdInvalidError($caller, $previous),
            'BROADCAST_PUBLIC_VOTERS_FORBIDDEN' => new \danog\MadelineProto\RPCError\BroadcastPublicVotersForbiddenError($caller, $previous),
            'BROADCAST_REQUIRED' => new \danog\MadelineProto\RPCError\BroadcastRequiredError($caller, $previous),
            'BUTTON_DATA_INVALID' => new \danog\MadelineProto\RPCError\ButtonDataInvalidError($caller, $previous),
            'BUTTON_TEXT_INVALID' => new \danog\MadelineProto\RPCError\ButtonTextInvalidError($caller, $previous),
            'BUTTON_TYPE_INVALID' => new \danog\MadelineProto\RPCError\ButtonTypeInvalidError($caller, $previous),
            'BUTTON_URL_INVALID' => new \danog\MadelineProto\RPCError\ButtonUrlInvalidError($caller, $previous),
            'BUTTON_USER_INVALID' => new \danog\MadelineProto\RPCError\ButtonUserInvalidError($caller, $previous),
            'BUTTON_USER_PRIVACY_RESTRICTED' => new \danog\MadelineProto\RPCError\ButtonUserPrivacyRestrictedError($caller, $previous),
            'CALL_ALREADY_ACCEPTED' => new \danog\MadelineProto\RPCError\CallAlreadyAcceptedError($caller, $previous),
            'CALL_ALREADY_DECLINED' => new \danog\MadelineProto\RPCError\CallAlreadyDeclinedError($caller, $previous),
            'CALL_OCCUPY_FAILED' => new \danog\MadelineProto\RPCError\CallOccupyFailedError($caller, $previous),
            'CALL_PEER_INVALID' => new \danog\MadelineProto\RPCError\CallPeerInvalidError($caller, $previous),
            'CALL_PROTOCOL_FLAGS_INVALID' => new \danog\MadelineProto\RPCError\CallProtocolFlagsInvalidError($caller, $previous),
            'CDN_METHOD_INVALID' => new \danog\MadelineProto\RPCError\CdnMethodInvalidError($caller, $previous),
            'CHANNEL_FORUM_MISSING' => new \danog\MadelineProto\RPCError\ChannelForumMissingError($caller, $previous),
            'CHANNEL_ID_INVALID' => new \danog\MadelineProto\RPCError\ChannelIdInvalidError($caller, $previous),
            'CHANNEL_INVALID' => new \danog\MadelineProto\RPCError\ChannelInvalidError($caller, $previous),
            'CHANNEL_PARICIPANT_MISSING' => new \danog\MadelineProto\RPCError\ChannelParicipantMissingError($caller, $previous),
            'CHANNEL_PRIVATE' => new \danog\MadelineProto\RPCError\ChannelPrivateError($caller, $previous),
            'CHANNEL_TOO_BIG' => new \danog\MadelineProto\RPCError\ChannelTooBigError($caller, $previous),
            'CHANNEL_TOO_LARGE' => new \danog\MadelineProto\RPCError\ChannelTooLargeError($caller, $previous),
            'CHANNELS_ADMIN_LOCATED_TOO_MUCH' => new \danog\MadelineProto\RPCError\ChannelsAdminLocatedTooMuchError($caller, $previous),
            'CHANNELS_ADMIN_PUBLIC_TOO_MUCH' => new \danog\MadelineProto\RPCError\ChannelsAdminPublicTooMuchError($caller, $previous),
            'CHANNELS_TOO_MUCH' => new \danog\MadelineProto\RPCError\ChannelsTooMuchError($caller, $previous),
            'CHAT_ABOUT_NOT_MODIFIED' => new \danog\MadelineProto\RPCError\ChatAboutNotModifiedError($caller, $previous),
            'CHAT_ABOUT_TOO_LONG' => new \danog\MadelineProto\RPCError\ChatAboutTooLongError($caller, $previous),
            'CHAT_ADMIN_REQUIRED' => new \danog\MadelineProto\RPCError\ChatAdminRequiredError($caller, $previous),
            'CHAT_DISCUSSION_UNALLOWED' => new \danog\MadelineProto\RPCError\ChatDiscussionUnallowedError($caller, $previous),
            'CHAT_FORWARDS_RESTRICTED' => new \danog\MadelineProto\RPCError\ChatForwardsRestrictedError($caller, $previous),
            'CHAT_ID_EMPTY' => new \danog\MadelineProto\RPCError\ChatIdEmptyError($caller, $previous),
            'CHAT_ID_INVALID' => new \danog\MadelineProto\RPCError\ChatIdInvalidError($caller, $previous),
            'CHAT_INVALID' => new \danog\MadelineProto\RPCError\ChatInvalidError($caller, $previous),
            'CHAT_INVITE_PERMANENT' => new \danog\MadelineProto\RPCError\ChatInvitePermanentError($caller, $previous),
            'CHAT_LINK_EXISTS' => new \danog\MadelineProto\RPCError\ChatLinkExistsError($caller, $previous),
            'CHAT_NOT_MODIFIED' => new \danog\MadelineProto\RPCError\ChatNotModifiedError($caller, $previous),
            'CHAT_PUBLIC_REQUIRED' => new \danog\MadelineProto\RPCError\ChatPublicRequiredError($caller, $previous),
            'CHAT_RESTRICTED' => new \danog\MadelineProto\RPCError\ChatRestrictedError($caller, $previous),
            'CHAT_REVOKE_DATE_UNSUPPORTED' => new \danog\MadelineProto\RPCError\ChatRevokeDateUnsupportedError($caller, $previous),
            'CHAT_SEND_INLINE_FORBIDDEN' => new \danog\MadelineProto\RPCError\ChatSendInlineForbiddenError($caller, $previous),
            'CHAT_TITLE_EMPTY' => new \danog\MadelineProto\RPCError\ChatTitleEmptyError($caller, $previous),
            'CHAT_TOO_BIG' => new \danog\MadelineProto\RPCError\ChatTooBigError($caller, $previous),
            'CHATLIST_EXCLUDE_INVALID' => new \danog\MadelineProto\RPCError\ChatlistExcludeInvalidError($caller, $previous),
            'CODE_EMPTY' => new \danog\MadelineProto\RPCError\CodeEmptyError($caller, $previous),
            'CODE_HASH_INVALID' => new \danog\MadelineProto\RPCError\CodeHashInvalidError($caller, $previous),
            'CODE_INVALID' => new \danog\MadelineProto\RPCError\CodeInvalidError($caller, $previous),
            'COLOR_INVALID' => new \danog\MadelineProto\RPCError\ColorInvalidError($caller, $previous),
            'CONNECTION_API_ID_INVALID' => new \danog\MadelineProto\RPCError\ConnectionApiIdInvalidError($caller, $previous),
            'CONNECTION_APP_VERSION_EMPTY' => new \danog\MadelineProto\RPCError\ConnectionAppVersionEmptyError($caller, $previous),
            'CONNECTION_LAYER_INVALID' => new \danog\MadelineProto\RPCError\ConnectionLayerInvalidError($caller, $previous),
            'CONTACT_ADD_MISSING' => new \danog\MadelineProto\RPCError\ContactAddMissingError($caller, $previous),
            'CONTACT_ID_INVALID' => new \danog\MadelineProto\RPCError\ContactIdInvalidError($caller, $previous),
            'CONTACT_MISSING' => new \danog\MadelineProto\RPCError\ContactMissingError($caller, $previous),
            'CONTACT_NAME_EMPTY' => new \danog\MadelineProto\RPCError\ContactNameEmptyError($caller, $previous),
            'CONTACT_REQ_MISSING' => new \danog\MadelineProto\RPCError\ContactReqMissingError($caller, $previous),
            'CREATE_CALL_FAILED' => new \danog\MadelineProto\RPCError\CreateCallFailedError($caller, $previous),
            'CURRENCY_TOTAL_AMOUNT_INVALID' => new \danog\MadelineProto\RPCError\CurrencyTotalAmountInvalidError($caller, $previous),
            'CUSTOM_REACTIONS_TOO_MANY' => new \danog\MadelineProto\RPCError\CustomReactionsTooManyError($caller, $previous),
            'DATA_INVALID' => new \danog\MadelineProto\RPCError\DataInvalidError($caller, $previous),
            'DATA_JSON_INVALID' => new \danog\MadelineProto\RPCError\DataJsonInvalidError($caller, $previous),
            'DATA_TOO_LONG' => new \danog\MadelineProto\RPCError\DataTooLongError($caller, $previous),
            'DATE_EMPTY' => new \danog\MadelineProto\RPCError\DateEmptyError($caller, $previous),
            'DC_ID_INVALID' => new \danog\MadelineProto\RPCError\DcIdInvalidError($caller, $previous),
            'DH_G_A_INVALID' => new \danog\MadelineProto\RPCError\DhGAInvalidError($caller, $previous),
            'DOCUMENT_INVALID' => new \danog\MadelineProto\RPCError\DocumentInvalidError($caller, $previous),
            'EMAIL_HASH_EXPIRED' => new \danog\MadelineProto\RPCError\EmailHashExpiredError($caller, $previous),
            'EMAIL_INVALID' => new \danog\MadelineProto\RPCError\EmailInvalidError($caller, $previous),
            'EMAIL_NOT_SETUP' => new \danog\MadelineProto\RPCError\EmailNotSetupError($caller, $previous),
            'EMAIL_UNCONFIRMED' => new \danog\MadelineProto\RPCError\EmailUnconfirmedError($caller, $previous),
            'EMAIL_VERIFY_EXPIRED' => new \danog\MadelineProto\RPCError\EmailVerifyExpiredError($caller, $previous),
            'EMOJI_INVALID' => new \danog\MadelineProto\RPCError\EmojiInvalidError($caller, $previous),
            'EMOJI_MARKUP_INVALID' => new \danog\MadelineProto\RPCError\EmojiMarkupInvalidError($caller, $previous),
            'EMOJI_NOT_MODIFIED' => new \danog\MadelineProto\RPCError\EmojiNotModifiedError($caller, $previous),
            'EMOTICON_EMPTY' => new \danog\MadelineProto\RPCError\EmoticonEmptyError($caller, $previous),
            'EMOTICON_INVALID' => new \danog\MadelineProto\RPCError\EmoticonInvalidError($caller, $previous),
            'EMOTICON_STICKERPACK_MISSING' => new \danog\MadelineProto\RPCError\EmoticonStickerpackMissingError($caller, $previous),
            'ENCRYPTED_MESSAGE_INVALID' => new \danog\MadelineProto\RPCError\EncryptedMessageInvalidError($caller, $previous),
            'ENCRYPTION_ALREADY_ACCEPTED' => new \danog\MadelineProto\RPCError\EncryptionAlreadyAcceptedError($caller, $previous),
            'ENCRYPTION_ALREADY_DECLINED' => new \danog\MadelineProto\RPCError\EncryptionAlreadyDeclinedError($caller, $previous),
            'ENCRYPTION_DECLINED' => new \danog\MadelineProto\RPCError\EncryptionDeclinedError($caller, $previous),
            'ENCRYPTION_ID_INVALID' => new \danog\MadelineProto\RPCError\EncryptionIdInvalidError($caller, $previous),
            'ENTITIES_TOO_LONG' => new \danog\MadelineProto\RPCError\EntitiesTooLongError($caller, $previous),
            'ENTITY_BOUNDS_INVALID' => new \danog\MadelineProto\RPCError\EntityBoundsInvalidError($caller, $previous),
            'ENTITY_MENTION_USER_INVALID' => new \danog\MadelineProto\RPCError\EntityMentionUserInvalidError($caller, $previous),
            'ERROR_TEXT_EMPTY' => new \danog\MadelineProto\RPCError\ErrorTextEmptyError($caller, $previous),
            'EXPIRE_DATE_INVALID' => new \danog\MadelineProto\RPCError\ExpireDateInvalidError($caller, $previous),
            'EXPORT_CARD_INVALID' => new \danog\MadelineProto\RPCError\ExportCardInvalidError($caller, $previous),
            'EXTERNAL_URL_INVALID' => new \danog\MadelineProto\RPCError\ExternalUrlInvalidError($caller, $previous),
            'FILE_CONTENT_TYPE_INVALID' => new \danog\MadelineProto\RPCError\FileContentTypeInvalidError($caller, $previous),
            'FILE_EMTPY' => new \danog\MadelineProto\RPCError\FileEmtpyError($caller, $previous),
            'FILE_ID_INVALID' => new \danog\MadelineProto\RPCError\FileIdInvalidError($caller, $previous),
            'FILE_PART_EMPTY' => new \danog\MadelineProto\RPCError\FilePartEmptyError($caller, $previous),
            'FILE_PART_INVALID' => new \danog\MadelineProto\RPCError\FilePartInvalidError($caller, $previous),
            'FILE_PART_LENGTH_INVALID' => new \danog\MadelineProto\RPCError\FilePartLengthInvalidError($caller, $previous),
            'FILE_PART_SIZE_CHANGED' => new \danog\MadelineProto\RPCError\FilePartSizeChangedError($caller, $previous),
            'FILE_PART_SIZE_INVALID' => new \danog\MadelineProto\RPCError\FilePartSizeInvalidError($caller, $previous),
            'FILE_PART_TOO_BIG' => new \danog\MadelineProto\RPCError\FilePartTooBigError($caller, $previous),
            'FILE_PARTS_INVALID' => new \danog\MadelineProto\RPCError\FilePartsInvalidError($caller, $previous),
            'FILE_REFERENCE_EMPTY' => new \danog\MadelineProto\RPCError\FileReferenceEmptyError($caller, $previous),
            'FILE_REFERENCE_EXPIRED' => new \danog\MadelineProto\RPCError\FileReferenceExpiredError($caller, $previous),
            'FILE_REFERENCE_INVALID' => new \danog\MadelineProto\RPCError\FileReferenceInvalidError($caller, $previous),
            'FILE_TITLE_EMPTY' => new \danog\MadelineProto\RPCError\FileTitleEmptyError($caller, $previous),
            'FILE_TOKEN_INVALID' => new \danog\MadelineProto\RPCError\FileTokenInvalidError($caller, $previous),
            'FILTER_ID_INVALID' => new \danog\MadelineProto\RPCError\FilterIdInvalidError($caller, $previous),
            'FILTER_INCLUDE_EMPTY' => new \danog\MadelineProto\RPCError\FilterIncludeEmptyError($caller, $previous),
            'FILTER_NOT_SUPPORTED' => new \danog\MadelineProto\RPCError\FilterNotSupportedError($caller, $previous),
            'FILTER_TITLE_EMPTY' => new \danog\MadelineProto\RPCError\FilterTitleEmptyError($caller, $previous),
            'FIRSTNAME_INVALID' => new \danog\MadelineProto\RPCError\FirstnameInvalidError($caller, $previous),
            'FOLDER_ID_EMPTY' => new \danog\MadelineProto\RPCError\FolderIdEmptyError($caller, $previous),
            'FOLDER_ID_INVALID' => new \danog\MadelineProto\RPCError\FolderIdInvalidError($caller, $previous),
            'FORUM_ENABLED' => new \danog\MadelineProto\RPCError\ForumEnabledError($caller, $previous),
            'FRESH_CHANGE_ADMINS_FORBIDDEN' => new \danog\MadelineProto\RPCError\FreshChangeAdminsForbiddenError($caller, $previous),
            'FROM_MESSAGE_BOT_DISABLED' => new \danog\MadelineProto\RPCError\FromMessageBotDisabledError($caller, $previous),
            'FROM_PEER_INVALID' => new \danog\MadelineProto\RPCError\FromPeerInvalidError($caller, $previous),
            'GAME_BOT_INVALID' => new \danog\MadelineProto\RPCError\GameBotInvalidError($caller, $previous),
            'GENERAL_MODIFY_ICON_FORBIDDEN' => new \danog\MadelineProto\RPCError\GeneralModifyIconForbiddenError($caller, $previous),
            'GEO_POINT_INVALID' => new \danog\MadelineProto\RPCError\GeoPointInvalidError($caller, $previous),
            'GIF_CONTENT_TYPE_INVALID' => new \danog\MadelineProto\RPCError\GifContentTypeInvalidError($caller, $previous),
            'GIF_ID_INVALID' => new \danog\MadelineProto\RPCError\GifIdInvalidError($caller, $previous),
            'GIFT_SLUG_EXPIRED' => new \danog\MadelineProto\RPCError\GiftSlugExpiredError($caller, $previous),
            'GIFT_SLUG_INVALID' => new \danog\MadelineProto\RPCError\GiftSlugInvalidError($caller, $previous),
            'GRAPH_EXPIRED_RELOAD' => new \danog\MadelineProto\RPCError\GraphExpiredReloadError($caller, $previous),
            'GRAPH_INVALID_RELOAD' => new \danog\MadelineProto\RPCError\GraphInvalidReloadError($caller, $previous),
            'GRAPH_OUTDATED_RELOAD' => new \danog\MadelineProto\RPCError\GraphOutdatedReloadError($caller, $previous),
            'GROUPCALL_ALREADY_DISCARDED' => new \danog\MadelineProto\RPCError\GroupcallAlreadyDiscardedError($caller, $previous),
            'GROUPCALL_FORBIDDEN' => new \danog\MadelineProto\RPCError\GroupcallForbiddenError($caller, $previous),
            'GROUPCALL_INVALID' => new \danog\MadelineProto\RPCError\GroupcallInvalidError($caller, $previous),
            'GROUPCALL_JOIN_MISSING' => new \danog\MadelineProto\RPCError\GroupcallJoinMissingError($caller, $previous),
            'GROUPCALL_NOT_MODIFIED' => new \danog\MadelineProto\RPCError\GroupcallNotModifiedError($caller, $previous),
            'GROUPCALL_SSRC_DUPLICATE_MUCH' => new \danog\MadelineProto\RPCError\GroupcallSsrcDuplicateMuchError($caller, $previous),
            'GROUPED_MEDIA_INVALID' => new \danog\MadelineProto\RPCError\GroupedMediaInvalidError($caller, $previous),
            'HASH_INVALID' => new \danog\MadelineProto\RPCError\HashInvalidError($caller, $previous),
            'HIDE_REQUESTER_MISSING' => new \danog\MadelineProto\RPCError\HideRequesterMissingError($caller, $previous),
            'IMAGE_PROCESS_FAILED' => new \danog\MadelineProto\RPCError\ImageProcessFailedError($caller, $previous),
            'IMPORT_FILE_INVALID' => new \danog\MadelineProto\RPCError\ImportFileInvalidError($caller, $previous),
            'IMPORT_FORMAT_UNRECOGNIZED' => new \danog\MadelineProto\RPCError\ImportFormatUnrecognizedError($caller, $previous),
            'IMPORT_ID_INVALID' => new \danog\MadelineProto\RPCError\ImportIdInvalidError($caller, $previous),
            'IMPORT_TOKEN_INVALID' => new \danog\MadelineProto\RPCError\ImportTokenInvalidError($caller, $previous),
            'INLINE_RESULT_EXPIRED' => new \danog\MadelineProto\RPCError\InlineResultExpiredError($caller, $previous),
            'INPUT_CHATLIST_INVALID' => new \danog\MadelineProto\RPCError\InputChatlistInvalidError($caller, $previous),
            'INPUT_FILTER_INVALID' => new \danog\MadelineProto\RPCError\InputFilterInvalidError($caller, $previous),
            'INPUT_TEXT_EMPTY' => new \danog\MadelineProto\RPCError\InputTextEmptyError($caller, $previous),
            'INPUT_TEXT_TOO_LONG' => new \danog\MadelineProto\RPCError\InputTextTooLongError($caller, $previous),
            'INPUT_USER_DEACTIVATED' => new \danog\MadelineProto\RPCError\InputUserDeactivatedError($caller, $previous),
            'INVITE_FORBIDDEN_WITH_JOINAS' => new \danog\MadelineProto\RPCError\InviteForbiddenWithJoinasError($caller, $previous),
            'INVITE_HASH_EMPTY' => new \danog\MadelineProto\RPCError\InviteHashEmptyError($caller, $previous),
            'INVITE_HASH_EXPIRED' => new \danog\MadelineProto\RPCError\InviteHashExpiredError($caller, $previous),
            'INVITE_HASH_INVALID' => new \danog\MadelineProto\RPCError\InviteHashInvalidError($caller, $previous),
            'INVITE_REQUEST_SENT' => new \danog\MadelineProto\RPCError\InviteRequestSentError($caller, $previous),
            'INVITE_REVOKED_MISSING' => new \danog\MadelineProto\RPCError\InviteRevokedMissingError($caller, $previous),
            'INVITE_SLUG_EMPTY' => new \danog\MadelineProto\RPCError\InviteSlugEmptyError($caller, $previous),
            'INVITE_SLUG_EXPIRED' => new \danog\MadelineProto\RPCError\InviteSlugExpiredError($caller, $previous),
            'INVITES_TOO_MUCH' => new \danog\MadelineProto\RPCError\InvitesTooMuchError($caller, $previous),
            'INVOICE_PAYLOAD_INVALID' => new \danog\MadelineProto\RPCError\InvoicePayloadInvalidError($caller, $previous),
            'JOIN_AS_PEER_INVALID' => new \danog\MadelineProto\RPCError\JoinAsPeerInvalidError($caller, $previous),
            'LANG_CODE_INVALID' => new \danog\MadelineProto\RPCError\LangCodeInvalidError($caller, $previous),
            'LANG_CODE_NOT_SUPPORTED' => new \danog\MadelineProto\RPCError\LangCodeNotSupportedError($caller, $previous),
            'LANG_PACK_INVALID' => new \danog\MadelineProto\RPCError\LangPackInvalidError($caller, $previous),
            'LASTNAME_INVALID' => new \danog\MadelineProto\RPCError\LastnameInvalidError($caller, $previous),
            'LIMIT_INVALID' => new \danog\MadelineProto\RPCError\LimitInvalidError($caller, $previous),
            'LINK_NOT_MODIFIED' => new \danog\MadelineProto\RPCError\LinkNotModifiedError($caller, $previous),
            'LOCATION_INVALID' => new \danog\MadelineProto\RPCError\LocationInvalidError($caller, $previous),
            'MAX_DATE_INVALID' => new \danog\MadelineProto\RPCError\MaxDateInvalidError($caller, $previous),
            'MAX_ID_INVALID' => new \danog\MadelineProto\RPCError\MaxIdInvalidError($caller, $previous),
            'MAX_QTS_INVALID' => new \danog\MadelineProto\RPCError\MaxQtsInvalidError($caller, $previous),
            'MD5_CHECKSUM_INVALID' => new \danog\MadelineProto\RPCError\Md5ChecksumInvalidError($caller, $previous),
            'MEDIA_CAPTION_TOO_LONG' => new \danog\MadelineProto\RPCError\MediaCaptionTooLongError($caller, $previous),
            'MEDIA_EMPTY' => new \danog\MadelineProto\RPCError\MediaEmptyError($caller, $previous),
            'MEDIA_FILE_INVALID' => new \danog\MadelineProto\RPCError\MediaFileInvalidError($caller, $previous),
            'MEDIA_GROUPED_INVALID' => new \danog\MadelineProto\RPCError\MediaGroupedInvalidError($caller, $previous),
            'MEDIA_INVALID' => new \danog\MadelineProto\RPCError\MediaInvalidError($caller, $previous),
            'MEDIA_NEW_INVALID' => new \danog\MadelineProto\RPCError\MediaNewInvalidError($caller, $previous),
            'MEDIA_PREV_INVALID' => new \danog\MadelineProto\RPCError\MediaPrevInvalidError($caller, $previous),
            'MEDIA_TTL_INVALID' => new \danog\MadelineProto\RPCError\MediaTtlInvalidError($caller, $previous),
            'MEDIA_TYPE_INVALID' => new \danog\MadelineProto\RPCError\MediaTypeInvalidError($caller, $previous),
            'MEDIA_VIDEO_STORY_MISSING' => new \danog\MadelineProto\RPCError\MediaVideoStoryMissingError($caller, $previous),
            'MEGAGROUP_GEO_REQUIRED' => new \danog\MadelineProto\RPCError\MegagroupGeoRequiredError($caller, $previous),
            'MEGAGROUP_ID_INVALID' => new \danog\MadelineProto\RPCError\MegagroupIdInvalidError($caller, $previous),
            'MEGAGROUP_PREHISTORY_HIDDEN' => new \danog\MadelineProto\RPCError\MegagroupPrehistoryHiddenError($caller, $previous),
            'MEGAGROUP_REQUIRED' => new \danog\MadelineProto\RPCError\MegagroupRequiredError($caller, $previous),
            'MESSAGE_EDIT_TIME_EXPIRED' => new \danog\MadelineProto\RPCError\MessageEditTimeExpiredError($caller, $previous),
            'MESSAGE_EMPTY' => new \danog\MadelineProto\RPCError\MessageEmptyError($caller, $previous),
            'MESSAGE_ID_INVALID' => new \danog\MadelineProto\RPCError\MessageIdInvalidError($caller, $previous),
            'MESSAGE_IDS_EMPTY' => new \danog\MadelineProto\RPCError\MessageIdsEmptyError($caller, $previous),
            'MESSAGE_NOT_MODIFIED' => new \danog\MadelineProto\RPCError\MessageNotModifiedError($caller, $previous),
            'MESSAGE_POLL_CLOSED' => new \danog\MadelineProto\RPCError\MessagePollClosedError($caller, $previous),
            'MESSAGE_TOO_LONG' => new \danog\MadelineProto\RPCError\MessageTooLongError($caller, $previous),
            'METHOD_INVALID' => new \danog\MadelineProto\RPCError\MethodInvalidError($caller, $previous),
            'MIN_DATE_INVALID' => new \danog\MadelineProto\RPCError\MinDateInvalidError($caller, $previous),
            'MSG_ID_INVALID' => new \danog\MadelineProto\RPCError\MsgIdInvalidError($caller, $previous),
            'MSG_TOO_OLD' => new \danog\MadelineProto\RPCError\MsgTooOldError($caller, $previous),
            'MSG_WAIT_FAILED' => new \danog\MadelineProto\RPCError\MsgWaitFailedError($caller, $previous),
            'MULTI_MEDIA_TOO_LONG' => new \danog\MadelineProto\RPCError\MultiMediaTooLongError($caller, $previous),
            'NEW_SALT_INVALID' => new \danog\MadelineProto\RPCError\NewSaltInvalidError($caller, $previous),
            'NEW_SETTINGS_EMPTY' => new \danog\MadelineProto\RPCError\NewSettingsEmptyError($caller, $previous),
            'NEW_SETTINGS_INVALID' => new \danog\MadelineProto\RPCError\NewSettingsInvalidError($caller, $previous),
            'NEXT_OFFSET_INVALID' => new \danog\MadelineProto\RPCError\NextOffsetInvalidError($caller, $previous),
            'OFFSET_INVALID' => new \danog\MadelineProto\RPCError\OffsetInvalidError($caller, $previous),
            'OFFSET_PEER_ID_INVALID' => new \danog\MadelineProto\RPCError\OffsetPeerIdInvalidError($caller, $previous),
            'OPTION_INVALID' => new \danog\MadelineProto\RPCError\OptionInvalidError($caller, $previous),
            'OPTIONS_TOO_MUCH' => new \danog\MadelineProto\RPCError\OptionsTooMuchError($caller, $previous),
            'ORDER_INVALID' => new \danog\MadelineProto\RPCError\OrderInvalidError($caller, $previous),
            'PACK_SHORT_NAME_INVALID' => new \danog\MadelineProto\RPCError\PackShortNameInvalidError($caller, $previous),
            'PACK_SHORT_NAME_OCCUPIED' => new \danog\MadelineProto\RPCError\PackShortNameOccupiedError($caller, $previous),
            'PACK_TITLE_INVALID' => new \danog\MadelineProto\RPCError\PackTitleInvalidError($caller, $previous),
            'PARTICIPANT_ID_INVALID' => new \danog\MadelineProto\RPCError\ParticipantIdInvalidError($caller, $previous),
            'PARTICIPANT_JOIN_MISSING' => new \danog\MadelineProto\RPCError\ParticipantJoinMissingError($caller, $previous),
            'PARTICIPANT_VERSION_OUTDATED' => new \danog\MadelineProto\RPCError\ParticipantVersionOutdatedError($caller, $previous),
            'PARTICIPANTS_TOO_FEW' => new \danog\MadelineProto\RPCError\ParticipantsTooFewError($caller, $previous),
            'PASSWORD_EMPTY' => new \danog\MadelineProto\RPCError\PasswordEmptyError($caller, $previous),
            'PASSWORD_HASH_INVALID' => new \danog\MadelineProto\RPCError\PasswordHashInvalidError($caller, $previous),
            'PASSWORD_MISSING' => new \danog\MadelineProto\RPCError\PasswordMissingError($caller, $previous),
            'PASSWORD_RECOVERY_EXPIRED' => new \danog\MadelineProto\RPCError\PasswordRecoveryExpiredError($caller, $previous),
            'PASSWORD_RECOVERY_NA' => new \danog\MadelineProto\RPCError\PasswordRecoveryNaError($caller, $previous),
            'PASSWORD_REQUIRED' => new \danog\MadelineProto\RPCError\PasswordRequiredError($caller, $previous),
            'PAYMENT_PROVIDER_INVALID' => new \danog\MadelineProto\RPCError\PaymentProviderInvalidError($caller, $previous),
            'PEER_HISTORY_EMPTY' => new \danog\MadelineProto\RPCError\PeerHistoryEmptyError($caller, $previous),
            'PEER_ID_INVALID' => new \danog\MadelineProto\RPCError\PeerIdInvalidError($caller, $previous),
            'PEER_ID_NOT_SUPPORTED' => new \danog\MadelineProto\RPCError\PeerIdNotSupportedError($caller, $previous),
            'PEERS_LIST_EMPTY' => new \danog\MadelineProto\RPCError\PeersListEmptyError($caller, $previous),
            'PERSISTENT_TIMESTAMP_EMPTY' => new \danog\MadelineProto\RPCError\PersistentTimestampEmptyError($caller, $previous),
            'PERSISTENT_TIMESTAMP_INVALID' => new \danog\MadelineProto\RPCError\PersistentTimestampInvalidError($caller, $previous),
            'PHONE_CODE_EMPTY' => new \danog\MadelineProto\RPCError\PhoneCodeEmptyError($caller, $previous),
            'PHONE_CODE_EXPIRED' => new \danog\MadelineProto\RPCError\PhoneCodeExpiredError($caller, $previous),
            'PHONE_CODE_HASH_EMPTY' => new \danog\MadelineProto\RPCError\PhoneCodeHashEmptyError($caller, $previous),
            'PHONE_CODE_INVALID' => new \danog\MadelineProto\RPCError\PhoneCodeInvalidError($caller, $previous),
            'PHONE_HASH_EXPIRED' => new \danog\MadelineProto\RPCError\PhoneHashExpiredError($caller, $previous),
            'PHONE_NOT_OCCUPIED' => new \danog\MadelineProto\RPCError\PhoneNotOccupiedError($caller, $previous),
            'PHONE_NUMBER_APP_SIGNUP_FORBIDDEN' => new \danog\MadelineProto\RPCError\PhoneNumberAppSignupForbiddenError($caller, $previous),
            'PHONE_NUMBER_BANNED' => new \danog\MadelineProto\RPCError\PhoneNumberBannedError($caller, $previous),
            'PHONE_NUMBER_FLOOD' => new \danog\MadelineProto\RPCError\PhoneNumberFloodError($caller, $previous),
            'PHONE_NUMBER_INVALID' => new \danog\MadelineProto\RPCError\PhoneNumberInvalidError($caller, $previous),
            'PHONE_NUMBER_OCCUPIED' => new \danog\MadelineProto\RPCError\PhoneNumberOccupiedError($caller, $previous),
            'PHONE_NUMBER_UNOCCUPIED' => new \danog\MadelineProto\RPCError\PhoneNumberUnoccupiedError($caller, $previous),
            'PHONE_PASSWORD_PROTECTED' => new \danog\MadelineProto\RPCError\PhonePasswordProtectedError($caller, $previous),
            'PHOTO_CONTENT_TYPE_INVALID' => new \danog\MadelineProto\RPCError\PhotoContentTypeInvalidError($caller, $previous),
            'PHOTO_CONTENT_URL_EMPTY' => new \danog\MadelineProto\RPCError\PhotoContentUrlEmptyError($caller, $previous),
            'PHOTO_CROP_FILE_MISSING' => new \danog\MadelineProto\RPCError\PhotoCropFileMissingError($caller, $previous),
            'PHOTO_CROP_SIZE_SMALL' => new \danog\MadelineProto\RPCError\PhotoCropSizeSmallError($caller, $previous),
            'PHOTO_EXT_INVALID' => new \danog\MadelineProto\RPCError\PhotoExtInvalidError($caller, $previous),
            'PHOTO_FILE_MISSING' => new \danog\MadelineProto\RPCError\PhotoFileMissingError($caller, $previous),
            'PHOTO_ID_INVALID' => new \danog\MadelineProto\RPCError\PhotoIdInvalidError($caller, $previous),
            'PHOTO_INVALID' => new \danog\MadelineProto\RPCError\PhotoInvalidError($caller, $previous),
            'PHOTO_INVALID_DIMENSIONS' => new \danog\MadelineProto\RPCError\PhotoInvalidDimensionsError($caller, $previous),
            'PHOTO_SAVE_FILE_INVALID' => new \danog\MadelineProto\RPCError\PhotoSaveFileInvalidError($caller, $previous),
            'PHOTO_THUMB_URL_EMPTY' => new \danog\MadelineProto\RPCError\PhotoThumbUrlEmptyError($caller, $previous),
            'PIN_RESTRICTED' => new \danog\MadelineProto\RPCError\PinRestrictedError($caller, $previous),
            'PINNED_DIALOGS_TOO_MUCH' => new \danog\MadelineProto\RPCError\PinnedDialogsTooMuchError($caller, $previous),
            'POLL_ANSWER_INVALID' => new \danog\MadelineProto\RPCError\PollAnswerInvalidError($caller, $previous),
            'POLL_ANSWERS_INVALID' => new \danog\MadelineProto\RPCError\PollAnswersInvalidError($caller, $previous),
            'POLL_OPTION_DUPLICATE' => new \danog\MadelineProto\RPCError\PollOptionDuplicateError($caller, $previous),
            'POLL_OPTION_INVALID' => new \danog\MadelineProto\RPCError\PollOptionInvalidError($caller, $previous),
            'POLL_QUESTION_INVALID' => new \danog\MadelineProto\RPCError\PollQuestionInvalidError($caller, $previous),
            'PREMIUM_ACCOUNT_REQUIRED' => new \danog\MadelineProto\RPCError\PremiumAccountRequiredError($caller, $previous),
            'PRIVACY_KEY_INVALID' => new \danog\MadelineProto\RPCError\PrivacyKeyInvalidError($caller, $previous),
            'PRIVACY_TOO_LONG' => new \danog\MadelineProto\RPCError\PrivacyTooLongError($caller, $previous),
            'PRIVACY_VALUE_INVALID' => new \danog\MadelineProto\RPCError\PrivacyValueInvalidError($caller, $previous),
            'PUBLIC_KEY_REQUIRED' => new \danog\MadelineProto\RPCError\PublicKeyRequiredError($caller, $previous),
            'QUERY_ID_EMPTY' => new \danog\MadelineProto\RPCError\QueryIdEmptyError($caller, $previous),
            'QUERY_ID_INVALID' => new \danog\MadelineProto\RPCError\QueryIdInvalidError($caller, $previous),
            'QUERY_TOO_SHORT' => new \danog\MadelineProto\RPCError\QueryTooShortError($caller, $previous),
            'QUIZ_ANSWER_MISSING' => new \danog\MadelineProto\RPCError\QuizAnswerMissingError($caller, $previous),
            'QUIZ_CORRECT_ANSWER_INVALID' => new \danog\MadelineProto\RPCError\QuizCorrectAnswerInvalidError($caller, $previous),
            'QUIZ_CORRECT_ANSWERS_EMPTY' => new \danog\MadelineProto\RPCError\QuizCorrectAnswersEmptyError($caller, $previous),
            'QUIZ_CORRECT_ANSWERS_TOO_MUCH' => new \danog\MadelineProto\RPCError\QuizCorrectAnswersTooMuchError($caller, $previous),
            'QUIZ_MULTIPLE_INVALID' => new \danog\MadelineProto\RPCError\QuizMultipleInvalidError($caller, $previous),
            'RANDOM_ID_EMPTY' => new \danog\MadelineProto\RPCError\RandomIdEmptyError($caller, $previous),
            'RANDOM_ID_INVALID' => new \danog\MadelineProto\RPCError\RandomIdInvalidError($caller, $previous),
            'RANDOM_LENGTH_INVALID' => new \danog\MadelineProto\RPCError\RandomLengthInvalidError($caller, $previous),
            'RANGES_INVALID' => new \danog\MadelineProto\RPCError\RangesInvalidError($caller, $previous),
            'REACTION_EMPTY' => new \danog\MadelineProto\RPCError\ReactionEmptyError($caller, $previous),
            'REACTION_INVALID' => new \danog\MadelineProto\RPCError\ReactionInvalidError($caller, $previous),
            'REACTIONS_TOO_MANY' => new \danog\MadelineProto\RPCError\ReactionsTooManyError($caller, $previous),
            'REPLY_MARKUP_BUY_EMPTY' => new \danog\MadelineProto\RPCError\ReplyMarkupBuyEmptyError($caller, $previous),
            'REPLY_MARKUP_INVALID' => new \danog\MadelineProto\RPCError\ReplyMarkupInvalidError($caller, $previous),
            'REPLY_MARKUP_TOO_LONG' => new \danog\MadelineProto\RPCError\ReplyMarkupTooLongError($caller, $previous),
            'REPLY_MESSAGE_ID_INVALID' => new \danog\MadelineProto\RPCError\ReplyMessageIdInvalidError($caller, $previous),
            'REPLY_TO_INVALID' => new \danog\MadelineProto\RPCError\ReplyToInvalidError($caller, $previous),
            'REPLY_TO_USER_INVALID' => new \danog\MadelineProto\RPCError\ReplyToUserInvalidError($caller, $previous),
            'RESET_REQUEST_MISSING' => new \danog\MadelineProto\RPCError\ResetRequestMissingError($caller, $previous),
            'RESULT_ID_DUPLICATE' => new \danog\MadelineProto\RPCError\ResultIdDuplicateError($caller, $previous),
            'RESULT_ID_EMPTY' => new \danog\MadelineProto\RPCError\ResultIdEmptyError($caller, $previous),
            'RESULT_ID_INVALID' => new \danog\MadelineProto\RPCError\ResultIdInvalidError($caller, $previous),
            'RESULT_TYPE_INVALID' => new \danog\MadelineProto\RPCError\ResultTypeInvalidError($caller, $previous),
            'RESULTS_TOO_MUCH' => new \danog\MadelineProto\RPCError\ResultsTooMuchError($caller, $previous),
            'REVOTE_NOT_ALLOWED' => new \danog\MadelineProto\RPCError\RevoteNotAllowedError($caller, $previous),
            'RIGHTS_NOT_MODIFIED' => new \danog\MadelineProto\RPCError\RightsNotModifiedError($caller, $previous),
            'RSA_DECRYPT_FAILED' => new \danog\MadelineProto\RPCError\RsaDecryptFailedError($caller, $previous),
            'SCHEDULE_BOT_NOT_ALLOWED' => new \danog\MadelineProto\RPCError\ScheduleBotNotAllowedError($caller, $previous),
            'SCHEDULE_DATE_INVALID' => new \danog\MadelineProto\RPCError\ScheduleDateInvalidError($caller, $previous),
            'SCHEDULE_DATE_TOO_LATE' => new \danog\MadelineProto\RPCError\ScheduleDateTooLateError($caller, $previous),
            'SCHEDULE_STATUS_PRIVATE' => new \danog\MadelineProto\RPCError\ScheduleStatusPrivateError($caller, $previous),
            'SCHEDULE_TOO_MUCH' => new \danog\MadelineProto\RPCError\ScheduleTooMuchError($caller, $previous),
            'SCORE_INVALID' => new \danog\MadelineProto\RPCError\ScoreInvalidError($caller, $previous),
            'SEARCH_QUERY_EMPTY' => new \danog\MadelineProto\RPCError\SearchQueryEmptyError($caller, $previous),
            'SEARCH_WITH_LINK_NOT_SUPPORTED' => new \danog\MadelineProto\RPCError\SearchWithLinkNotSupportedError($caller, $previous),
            'SECONDS_INVALID' => new \danog\MadelineProto\RPCError\SecondsInvalidError($caller, $previous),
            'SEND_AS_PEER_INVALID' => new \danog\MadelineProto\RPCError\SendAsPeerInvalidError($caller, $previous),
            'SEND_MESSAGE_MEDIA_INVALID' => new \danog\MadelineProto\RPCError\SendMessageMediaInvalidError($caller, $previous),
            'SEND_MESSAGE_TYPE_INVALID' => new \danog\MadelineProto\RPCError\SendMessageTypeInvalidError($caller, $previous),
            'SETTINGS_INVALID' => new \danog\MadelineProto\RPCError\SettingsInvalidError($caller, $previous),
            'SHA256_HASH_INVALID' => new \danog\MadelineProto\RPCError\Sha256HashInvalidError($caller, $previous),
            'SHORT_NAME_INVALID' => new \danog\MadelineProto\RPCError\ShortNameInvalidError($caller, $previous),
            'SHORT_NAME_OCCUPIED' => new \danog\MadelineProto\RPCError\ShortNameOccupiedError($caller, $previous),
            'SLOTS_EMPTY' => new \danog\MadelineProto\RPCError\SlotsEmptyError($caller, $previous),
            'SLOWMODE_MULTI_MSGS_DISABLED' => new \danog\MadelineProto\RPCError\SlowmodeMultiMsgsDisabledError($caller, $previous),
            'SLUG_INVALID' => new \danog\MadelineProto\RPCError\SlugInvalidError($caller, $previous),
            'SMS_CODE_CREATE_FAILED' => new \danog\MadelineProto\RPCError\SmsCodeCreateFailedError($caller, $previous),
            'SRP_ID_INVALID' => new \danog\MadelineProto\RPCError\SrpIdInvalidError($caller, $previous),
            'SRP_PASSWORD_CHANGED' => new \danog\MadelineProto\RPCError\SrpPasswordChangedError($caller, $previous),
            'START_PARAM_EMPTY' => new \danog\MadelineProto\RPCError\StartParamEmptyError($caller, $previous),
            'START_PARAM_INVALID' => new \danog\MadelineProto\RPCError\StartParamInvalidError($caller, $previous),
            'START_PARAM_TOO_LONG' => new \danog\MadelineProto\RPCError\StartParamTooLongError($caller, $previous),
            'STICKER_DOCUMENT_INVALID' => new \danog\MadelineProto\RPCError\StickerDocumentInvalidError($caller, $previous),
            'STICKER_EMOJI_INVALID' => new \danog\MadelineProto\RPCError\StickerEmojiInvalidError($caller, $previous),
            'STICKER_FILE_INVALID' => new \danog\MadelineProto\RPCError\StickerFileInvalidError($caller, $previous),
            'STICKER_GIF_DIMENSIONS' => new \danog\MadelineProto\RPCError\StickerGifDimensionsError($caller, $previous),
            'STICKER_ID_INVALID' => new \danog\MadelineProto\RPCError\StickerIdInvalidError($caller, $previous),
            'STICKER_INVALID' => new \danog\MadelineProto\RPCError\StickerInvalidError($caller, $previous),
            'STICKER_MIME_INVALID' => new \danog\MadelineProto\RPCError\StickerMimeInvalidError($caller, $previous),
            'STICKER_PNG_DIMENSIONS' => new \danog\MadelineProto\RPCError\StickerPngDimensionsError($caller, $previous),
            'STICKER_PNG_NOPNG' => new \danog\MadelineProto\RPCError\StickerPngNopngError($caller, $previous),
            'STICKER_TGS_NODOC' => new \danog\MadelineProto\RPCError\StickerTgsNodocError($caller, $previous),
            'STICKER_TGS_NOTGS' => new \danog\MadelineProto\RPCError\StickerTgsNotgsError($caller, $previous),
            'STICKER_THUMB_PNG_NOPNG' => new \danog\MadelineProto\RPCError\StickerThumbPngNopngError($caller, $previous),
            'STICKER_THUMB_TGS_NOTGS' => new \danog\MadelineProto\RPCError\StickerThumbTgsNotgsError($caller, $previous),
            'STICKER_VIDEO_BIG' => new \danog\MadelineProto\RPCError\StickerVideoBigError($caller, $previous),
            'STICKER_VIDEO_NODOC' => new \danog\MadelineProto\RPCError\StickerVideoNodocError($caller, $previous),
            'STICKER_VIDEO_NOWEBM' => new \danog\MadelineProto\RPCError\StickerVideoNowebmError($caller, $previous),
            'STICKERPACK_STICKERS_TOO_MUCH' => new \danog\MadelineProto\RPCError\StickerpackStickersTooMuchError($caller, $previous),
            'STICKERS_EMPTY' => new \danog\MadelineProto\RPCError\StickersEmptyError($caller, $previous),
            'STICKERS_TOO_MUCH' => new \danog\MadelineProto\RPCError\StickersTooMuchError($caller, $previous),
            'STICKERSET_INVALID' => new \danog\MadelineProto\RPCError\StickersetInvalidError($caller, $previous),
            'STORIES_NEVER_CREATED' => new \danog\MadelineProto\RPCError\StoriesNeverCreatedError($caller, $previous),
            'STORIES_TOO_MUCH' => new \danog\MadelineProto\RPCError\StoriesTooMuchError($caller, $previous),
            'STORY_ID_EMPTY' => new \danog\MadelineProto\RPCError\StoryIdEmptyError($caller, $previous),
            'STORY_ID_INVALID' => new \danog\MadelineProto\RPCError\StoryIdInvalidError($caller, $previous),
            'STORY_NOT_MODIFIED' => new \danog\MadelineProto\RPCError\StoryNotModifiedError($caller, $previous),
            'STORY_PERIOD_INVALID' => new \danog\MadelineProto\RPCError\StoryPeriodInvalidError($caller, $previous),
            'SWITCH_PM_TEXT_EMPTY' => new \danog\MadelineProto\RPCError\SwitchPmTextEmptyError($caller, $previous),
            'TAKEOUT_INVALID' => new \danog\MadelineProto\RPCError\TakeoutInvalidError($caller, $previous),
            'TAKEOUT_REQUIRED' => new \danog\MadelineProto\RPCError\TakeoutRequiredError($caller, $previous),
            'TASK_ALREADY_EXISTS' => new \danog\MadelineProto\RPCError\TaskAlreadyExistsError($caller, $previous),
            'TEMP_AUTH_KEY_ALREADY_BOUND' => new \danog\MadelineProto\RPCError\TempAuthKeyAlreadyBoundError($caller, $previous),
            'TEMP_AUTH_KEY_EMPTY' => new \danog\MadelineProto\RPCError\TempAuthKeyEmptyError($caller, $previous),
            'THEME_FILE_INVALID' => new \danog\MadelineProto\RPCError\ThemeFileInvalidError($caller, $previous),
            'THEME_FORMAT_INVALID' => new \danog\MadelineProto\RPCError\ThemeFormatInvalidError($caller, $previous),
            'THEME_INVALID' => new \danog\MadelineProto\RPCError\ThemeInvalidError($caller, $previous),
            'THEME_MIME_INVALID' => new \danog\MadelineProto\RPCError\ThemeMimeInvalidError($caller, $previous),
            'THEME_TITLE_INVALID' => new \danog\MadelineProto\RPCError\ThemeTitleInvalidError($caller, $previous),
            'TITLE_INVALID' => new \danog\MadelineProto\RPCError\TitleInvalidError($caller, $previous),
            'TMP_PASSWORD_DISABLED' => new \danog\MadelineProto\RPCError\TmpPasswordDisabledError($caller, $previous),
            'TO_LANG_INVALID' => new \danog\MadelineProto\RPCError\ToLangInvalidError($caller, $previous),
            'TOKEN_EMPTY' => new \danog\MadelineProto\RPCError\TokenEmptyError($caller, $previous),
            'TOKEN_INVALID' => new \danog\MadelineProto\RPCError\TokenInvalidError($caller, $previous),
            'TOKEN_TYPE_INVALID' => new \danog\MadelineProto\RPCError\TokenTypeInvalidError($caller, $previous),
            'TOPIC_CLOSE_SEPARATELY' => new \danog\MadelineProto\RPCError\TopicCloseSeparatelyError($caller, $previous),
            'TOPIC_CLOSED' => new \danog\MadelineProto\RPCError\TopicClosedError($caller, $previous),
            'TOPIC_DELETED' => new \danog\MadelineProto\RPCError\TopicDeletedError($caller, $previous),
            'TOPIC_HIDE_SEPARATELY' => new \danog\MadelineProto\RPCError\TopicHideSeparatelyError($caller, $previous),
            'TOPIC_ID_INVALID' => new \danog\MadelineProto\RPCError\TopicIdInvalidError($caller, $previous),
            'TOPIC_NOT_MODIFIED' => new \danog\MadelineProto\RPCError\TopicNotModifiedError($caller, $previous),
            'TOPIC_TITLE_EMPTY' => new \danog\MadelineProto\RPCError\TopicTitleEmptyError($caller, $previous),
            'TOPICS_EMPTY' => new \danog\MadelineProto\RPCError\TopicsEmptyError($caller, $previous),
            'TRANSCRIPTION_FAILED' => new \danog\MadelineProto\RPCError\TranscriptionFailedError($caller, $previous),
            'TTL_DAYS_INVALID' => new \danog\MadelineProto\RPCError\TtlDaysInvalidError($caller, $previous),
            'TTL_MEDIA_INVALID' => new \danog\MadelineProto\RPCError\TtlMediaInvalidError($caller, $previous),
            'TTL_PERIOD_INVALID' => new \danog\MadelineProto\RPCError\TtlPeriodInvalidError($caller, $previous),
            'TYPES_EMPTY' => new \danog\MadelineProto\RPCError\TypesEmptyError($caller, $previous),
            'UNTIL_DATE_INVALID' => new \danog\MadelineProto\RPCError\UntilDateInvalidError($caller, $previous),
            'URL_INVALID' => new \danog\MadelineProto\RPCError\UrlInvalidError($caller, $previous),
            'USAGE_LIMIT_INVALID' => new \danog\MadelineProto\RPCError\UsageLimitInvalidError($caller, $previous),
            'USER_ADMIN_INVALID' => new \danog\MadelineProto\RPCError\UserAdminInvalidError($caller, $previous),
            'USER_ALREADY_INVITED' => new \danog\MadelineProto\RPCError\UserAlreadyInvitedError($caller, $previous),
            'USER_ALREADY_PARTICIPANT' => new \danog\MadelineProto\RPCError\UserAlreadyParticipantError($caller, $previous),
            'USER_BANNED_IN_CHANNEL' => new \danog\MadelineProto\RPCError\UserBannedInChannelError($caller, $previous),
            'USER_BLOCKED' => new \danog\MadelineProto\RPCError\UserBlockedError($caller, $previous),
            'USER_BOT' => new \danog\MadelineProto\RPCError\UserBotError($caller, $previous),
            'USER_BOT_INVALID' => new \danog\MadelineProto\RPCError\UserBotInvalidError($caller, $previous),
            'USER_BOT_REQUIRED' => new \danog\MadelineProto\RPCError\UserBotRequiredError($caller, $previous),
            'USER_CHANNELS_TOO_MUCH' => new \danog\MadelineProto\RPCError\UserChannelsTooMuchError($caller, $previous),
            'USER_CREATOR' => new \danog\MadelineProto\RPCError\UserCreatorError($caller, $previous),
            'USER_ID_INVALID' => new \danog\MadelineProto\RPCError\UserIdInvalidError($caller, $previous),
            'USER_INVALID' => new \danog\MadelineProto\RPCError\UserInvalidError($caller, $previous),
            'USER_IS_BLOCKED' => new \danog\MadelineProto\RPCError\UserIsBlockedError($caller, $previous),
            'USER_IS_BOT' => new \danog\MadelineProto\RPCError\UserIsBotError($caller, $previous),
            'USER_KICKED' => new \danog\MadelineProto\RPCError\UserKickedError($caller, $previous),
            'USER_NOT_MUTUAL_CONTACT' => new \danog\MadelineProto\RPCError\UserNotMutualContactError($caller, $previous),
            'USER_NOT_PARTICIPANT' => new \danog\MadelineProto\RPCError\UserNotParticipantError($caller, $previous),
            'USER_PUBLIC_MISSING' => new \danog\MadelineProto\RPCError\UserPublicMissingError($caller, $previous),
            'USER_VOLUME_INVALID' => new \danog\MadelineProto\RPCError\UserVolumeInvalidError($caller, $previous),
            'USERNAME_INVALID' => new \danog\MadelineProto\RPCError\UsernameInvalidError($caller, $previous),
            'USERNAME_NOT_MODIFIED' => new \danog\MadelineProto\RPCError\UsernameNotModifiedError($caller, $previous),
            'USERNAME_NOT_OCCUPIED' => new \danog\MadelineProto\RPCError\UsernameNotOccupiedError($caller, $previous),
            'USERNAME_OCCUPIED' => new \danog\MadelineProto\RPCError\UsernameOccupiedError($caller, $previous),
            'USERNAME_PURCHASE_AVAILABLE' => new \danog\MadelineProto\RPCError\UsernamePurchaseAvailableError($caller, $previous),
            'USERNAMES_ACTIVE_TOO_MUCH' => new \danog\MadelineProto\RPCError\UsernamesActiveTooMuchError($caller, $previous),
            'USERPIC_UPLOAD_REQUIRED' => new \danog\MadelineProto\RPCError\UserpicUploadRequiredError($caller, $previous),
            'USERS_TOO_FEW' => new \danog\MadelineProto\RPCError\UsersTooFewError($caller, $previous),
            'USERS_TOO_MUCH' => new \danog\MadelineProto\RPCError\UsersTooMuchError($caller, $previous),
            'VENUE_ID_INVALID' => new \danog\MadelineProto\RPCError\VenueIdInvalidError($caller, $previous),
            'VIDEO_CONTENT_TYPE_INVALID' => new \danog\MadelineProto\RPCError\VideoContentTypeInvalidError($caller, $previous),
            'VIDEO_FILE_INVALID' => new \danog\MadelineProto\RPCError\VideoFileInvalidError($caller, $previous),
            'VIDEO_TITLE_EMPTY' => new \danog\MadelineProto\RPCError\VideoTitleEmptyError($caller, $previous),
            'VOICE_MESSAGES_FORBIDDEN' => new \danog\MadelineProto\RPCError\VoiceMessagesForbiddenError($caller, $previous),
            'WALLPAPER_FILE_INVALID' => new \danog\MadelineProto\RPCError\WallpaperFileInvalidError($caller, $previous),
            'WALLPAPER_INVALID' => new \danog\MadelineProto\RPCError\WallpaperInvalidError($caller, $previous),
            'WALLPAPER_MIME_INVALID' => new \danog\MadelineProto\RPCError\WallpaperMimeInvalidError($caller, $previous),
            'WALLPAPER_NOT_FOUND' => new \danog\MadelineProto\RPCError\WallpaperNotFoundError($caller, $previous),
            'WC_CONVERT_URL_INVALID' => new \danog\MadelineProto\RPCError\WcConvertUrlInvalidError($caller, $previous),
            'WEBDOCUMENT_INVALID' => new \danog\MadelineProto\RPCError\WebdocumentInvalidError($caller, $previous),
            'WEBDOCUMENT_MIME_INVALID' => new \danog\MadelineProto\RPCError\WebdocumentMimeInvalidError($caller, $previous),
            'WEBDOCUMENT_SIZE_TOO_BIG' => new \danog\MadelineProto\RPCError\WebdocumentSizeTooBigError($caller, $previous),
            'WEBDOCUMENT_URL_INVALID' => new \danog\MadelineProto\RPCError\WebdocumentUrlInvalidError($caller, $previous),
            'WEBPAGE_CURL_FAILED' => new \danog\MadelineProto\RPCError\WebpageCurlFailedError($caller, $previous),
            'WEBPAGE_MEDIA_EMPTY' => new \danog\MadelineProto\RPCError\WebpageMediaEmptyError($caller, $previous),
            'WEBPAGE_NOT_FOUND' => new \danog\MadelineProto\RPCError\WebpageNotFoundError($caller, $previous),
            'WEBPAGE_URL_INVALID' => new \danog\MadelineProto\RPCError\WebpageUrlInvalidError($caller, $previous),
            'WEBPUSH_AUTH_INVALID' => new \danog\MadelineProto\RPCError\WebpushAuthInvalidError($caller, $previous),
            'WEBPUSH_KEY_INVALID' => new \danog\MadelineProto\RPCError\WebpushKeyInvalidError($caller, $previous),
            'WEBPUSH_TOKEN_INVALID' => new \danog\MadelineProto\RPCError\WebpushTokenInvalidError($caller, $previous),
            'YOU_BLOCKED_USER' => new \danog\MadelineProto\RPCError\YouBlockedUserError($caller, $previous),
            'ANONYMOUS_REACTIONS_DISABLED' => new \danog\MadelineProto\RPCError\AnonymousReactionsDisabledError($caller, $previous),
            'BROADCAST_FORBIDDEN' => new \danog\MadelineProto\RPCError\BroadcastForbiddenError($caller, $previous),
            'CHANNEL_PUBLIC_GROUP_NA' => new \danog\MadelineProto\RPCError\ChannelPublicGroupNaError($caller, $previous),
            'CHAT_ADMIN_INVITE_REQUIRED' => new \danog\MadelineProto\RPCError\ChatAdminInviteRequiredError($caller, $previous),
            'CHAT_GUEST_SEND_FORBIDDEN' => new \danog\MadelineProto\RPCError\ChatGuestSendForbiddenError($caller, $previous),
            'CHAT_SEND_AUDIOS_FORBIDDEN' => new \danog\MadelineProto\RPCError\ChatSendAudiosForbiddenError($caller, $previous),
            'CHAT_SEND_DOCS_FORBIDDEN' => new \danog\MadelineProto\RPCError\ChatSendDocsForbiddenError($caller, $previous),
            'CHAT_SEND_GAME_FORBIDDEN' => new \danog\MadelineProto\RPCError\ChatSendGameForbiddenError($caller, $previous),
            'CHAT_SEND_GIFS_FORBIDDEN' => new \danog\MadelineProto\RPCError\ChatSendGifsForbiddenError($caller, $previous),
            'CHAT_SEND_MEDIA_FORBIDDEN' => new \danog\MadelineProto\RPCError\ChatSendMediaForbiddenError($caller, $previous),
            'CHAT_SEND_PHOTOS_FORBIDDEN' => new \danog\MadelineProto\RPCError\ChatSendPhotosForbiddenError($caller, $previous),
            'CHAT_SEND_PLAIN_FORBIDDEN' => new \danog\MadelineProto\RPCError\ChatSendPlainForbiddenError($caller, $previous),
            'CHAT_SEND_POLL_FORBIDDEN' => new \danog\MadelineProto\RPCError\ChatSendPollForbiddenError($caller, $previous),
            'CHAT_SEND_STICKERS_FORBIDDEN' => new \danog\MadelineProto\RPCError\ChatSendStickersForbiddenError($caller, $previous),
            'CHAT_SEND_VIDEOS_FORBIDDEN' => new \danog\MadelineProto\RPCError\ChatSendVideosForbiddenError($caller, $previous),
            'CHAT_SEND_VOICES_FORBIDDEN' => new \danog\MadelineProto\RPCError\ChatSendVoicesForbiddenError($caller, $previous),
            'CHAT_WRITE_FORBIDDEN' => new \danog\MadelineProto\RPCError\ChatWriteForbiddenError($caller, $previous),
            'EDIT_BOT_INVITE_FORBIDDEN' => new \danog\MadelineProto\RPCError\EditBotInviteForbiddenError($caller, $previous),
            'GROUPCALL_ALREADY_STARTED' => new \danog\MadelineProto\RPCError\GroupcallAlreadyStartedError($caller, $previous),
            'INLINE_BOT_REQUIRED' => new \danog\MadelineProto\RPCError\InlineBotRequiredError($caller, $previous),
            'MESSAGE_AUTHOR_REQUIRED' => new \danog\MadelineProto\RPCError\MessageAuthorRequiredError($caller, $previous),
            'MESSAGE_DELETE_FORBIDDEN' => new \danog\MadelineProto\RPCError\MessageDeleteForbiddenError($caller, $previous),
            'POLL_VOTE_REQUIRED' => new \danog\MadelineProto\RPCError\PollVoteRequiredError($caller, $previous),
            'PRIVACY_PREMIUM_REQUIRED' => new \danog\MadelineProto\RPCError\PrivacyPremiumRequiredError($caller, $previous),
            'PUBLIC_CHANNEL_MISSING' => new \danog\MadelineProto\RPCError\PublicChannelMissingError($caller, $previous),
            'RIGHT_FORBIDDEN' => new \danog\MadelineProto\RPCError\RightForbiddenError($caller, $previous),
            'SENSITIVE_CHANGE_FORBIDDEN' => new \danog\MadelineProto\RPCError\SensitiveChangeForbiddenError($caller, $previous),
            'USER_DELETED' => new \danog\MadelineProto\RPCError\UserDeletedError($caller, $previous),
            'USER_PRIVACY_RESTRICTED' => new \danog\MadelineProto\RPCError\UserPrivacyRestrictedError($caller, $previous),
            'USER_RESTRICTED' => new \danog\MadelineProto\RPCError\UserRestrictedError($caller, $previous),
            'CALL_PROTOCOL_COMPAT_LAYER_INVALID' => new \danog\MadelineProto\RPCError\CallProtocolCompatLayerInvalidError($caller, $previous),
            'FILEREF_UPGRADE_NEEDED' => new \danog\MadelineProto\RPCError\FilerefUpgradeNeededError($caller, $previous),
            'FRESH_CHANGE_PHONE_FORBIDDEN' => new \danog\MadelineProto\RPCError\FreshChangePhoneForbiddenError($caller, $previous),
            'FRESH_RESET_AUTHORISATION_FORBIDDEN' => new \danog\MadelineProto\RPCError\FreshResetAuthorisationForbiddenError($caller, $previous),
            'PAYMENT_UNSUPPORTED' => new \danog\MadelineProto\RPCError\PaymentUnsupportedError($caller, $previous),
            'PHONE_PASSWORD_FLOOD' => new \danog\MadelineProto\RPCError\PhonePasswordFloodError($caller, $previous),
            'SEND_CODE_UNAVAILABLE' => new \danog\MadelineProto\RPCError\SendCodeUnavailableError($caller, $previous),
            'STICKERSET_OWNER_ANONYMOUS' => new \danog\MadelineProto\RPCError\StickersetOwnerAnonymousError($caller, $previous),
            'UPDATE_APP_TO_LOGIN' => new \danog\MadelineProto\RPCError\UpdateAppToLoginError($caller, $previous),
            'USERPIC_PRIVACY_REQUIRED' => new \danog\MadelineProto\RPCError\UserpicPrivacyRequiredError($caller, $previous),
            default => new self($rpc, self::report($rpc, $code, $caller), $code, $caller, $previous)
        };

        // End match
    }

    protected function __construct(
        /**
         * @var string RPC error.
         */
        public readonly string $rpc,
        /**
         * @var string Human-readable description of RPC error.
         */
        public readonly string $description,
        int $code,
        private readonly string $caller,
        ?\Exception $previous = null
    ) {
        parent::__construct($rpc, $code, $previous);
        $this->prettifyTL($caller);
        $this->caller = $caller;
        foreach ($this->getTrace() as $level) {
            if (isset($level['function']) && $level['function'] === 'methodCall') {
                $this->line = $level['line'];
                $this->file = $level['file'];
            }
        }
    }
}

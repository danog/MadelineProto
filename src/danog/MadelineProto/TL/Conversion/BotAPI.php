<?php

/**
 * BotAPI module.
 *
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2020 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\TL\Conversion;

use danog\Decoder\FileId;
use danog\MadelineProto\Logger;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\StrTools;
use danog\MadelineProto\Tools;

use const danog\Decoder\TYPES_IDS;

trait BotAPI
{
    private function htmlEntityDecode(string $stuff): string
    {
        return \html_entity_decode(\preg_replace('#< *br */? *>#', "\n", $stuff));
    }
    /**
     * @return ((bool|mixed|string)[][]|string)[][]
     *
     * @psalm-return array<int|int, array{_: string, buttons: array<int|int, array{_: string, text: mixed, same_peer?: bool, query?: mixed, data?: mixed, url?: mixed}>}>
     */
    private function parseButtons($rows): array
    {
        $newrows = [];
        $key = 0;
        $button_key = 0;
        foreach ($rows as $row) {
            $newrows[$key] = ['_' => 'keyboardButtonRow', 'buttons' => []];
            foreach ($row as $button) {
                $newrows[$key]['buttons'][$button_key] = ['_' => 'keyboardButton', 'text' => $button['text']];
                if (isset($button['url'])) {
                    $newrows[$key]['buttons'][$button_key]['_'] = 'keyboardButtonUrl';
                    $newrows[$key]['buttons'][$button_key]['url'] = $button['url'];
                } elseif (isset($button['callback_data'])) {
                    $newrows[$key]['buttons'][$button_key]['_'] = 'keyboardButtonCallback';
                    $newrows[$key]['buttons'][$button_key]['data'] = $button['callback_data'];
                } elseif (isset($button['switch_inline_query'])) {
                    $newrows[$key]['buttons'][$button_key]['_'] = 'keyboardButtonSwitchInline';
                    $newrows[$key]['buttons'][$button_key]['same_peer'] = false;
                    $newrows[$key]['buttons'][$button_key]['query'] = $button['switch_inline_query'];
                } elseif (isset($button['switch_inline_query_current_chat'])) {
                    $newrows[$key]['buttons'][$button_key]['_'] = 'keyboardButtonSwitchInline';
                    $newrows[$key]['buttons'][$button_key]['same_peer'] = true;
                    $newrows[$key]['buttons'][$button_key]['query'] = $button['switch_inline_query_current_chat'];
                } elseif (isset($button['callback_game'])) {
                    $newrows[$key]['buttons'][$button_key]['_'] = 'keyboardButtonGame';
                    $newrows[$key]['buttons'][$button_key]['text'] = $button['callback_game'];
                } elseif (isset($button['request_contact']) && $button['request_contact']) {
                    $newrows[$key]['buttons'][$button_key]['_'] = 'keyboardButtonRequestPhone';
                } elseif (isset($button['request_location']) && $button['request_location']) {
                    $newrows[$key]['buttons'][$button_key]['_'] = 'keyboardButtonRequestGeoLocation';
                }
                $button_key++;
            }
            $key++;
        }
        return $newrows;
    }
    private function parseReplyMarkup($markup)
    {
        if (isset($markup['force_reply']) && $markup['force_reply']) {
            $markup['_'] = 'replyKeyboardForceReply';
            unset($markup['force_reply']);
        }
        if (isset($markup['remove_keyboard']) && $markup['remove_keyboard']) {
            $markup['_'] = 'replyKeyboardHide';
            unset($markup['remove_keyboard']);
        }
        if (isset($markup['keyboard'])) {
            $markup['_'] = 'replyKeyboardMarkup';
            if (isset($markup['resize_keyboard'])) {
                $markup['resize'] = $markup['resize_keyboard'];
                unset($markup['resize_keyboard']);
            }
            if (isset($markup['one_time_keyboard'])) {
                $markup['single_use'] = $markup['one_time_keyboard'];
                unset($markup['one_time_keyboard']);
            }
            $markup['rows'] = $this->parseButtons($markup['keyboard']);
            unset($markup['keyboard']);
        }
        if (isset($markup['inline_keyboard'])) {
            $markup['_'] = 'replyInlineMarkup';
            $markup['rows'] = $this->parseButtons($markup['inline_keyboard']);
            unset($markup['inline_keyboard']);
        }
        return $markup;
    }
    /**
     * Convert MTProto parameters to bot API parameters.
     *
     * @param array $data Data
     *
     * @return \Generator<array>
     */
    public function MTProtoToBotAPI(array $data): \Generator
    {
        $newd = [];
        if (!isset($data['_'])) {
            foreach ($data as $key => $element) {
                $newd[$key] = (yield from $this->MTProtoToBotAPI($element));
            }
            return $newd;
        }
        $res = null;
        switch ($data['_']) {
            case 'updates':
            case 'updatesCombined':
            case 'updateShort':
            case 'updateShortSentMessage':
            case 'updateShortMessage':
            case 'updateShortChatMessage':
                $data = yield from $this->extractMessageUpdate($data);
                // no break
            case 'updateNewChannelMessage':
            case 'updateNewMessage':
                $data = $data['message'];
                // no break
            case 'message':
                $newd['message_id'] = $data['id'];
                $newd['date'] = $data['date'];
                $newd['text'] = $data['message'];
                $newd['post'] = $data['post'];
                $newd['silent'] = $data['silent'];
                if (isset($data['from_id'])) {
                    $newd['from'] = (yield from $this->getPwrChat($data['from_id']));
                }
                $newd['chat'] = (yield from $this->getPwrChat($data['peer_id']));
                if (isset($data['entities'])) {
                    $newd['entities'] = (yield from $this->MTProtoToBotAPI($data['entities']));
                }
                if (isset($data['views'])) {
                    $newd['views'] = $data['views'];
                }
                if (isset($data['edit_date'])) {
                    $newd['edit_date'] = $data['edit_date'];
                }
                if (isset($data['via_bot_id'])) {
                    $newd['via_bot'] = (yield from $this->getPwrChat($data['via_bot_id']));
                }
                if (isset($data['fwd_from']['from_id'])) {
                    $newd['forward_from'] = (yield from $this->getPwrChat($data['fwd_from']['from_id']));
                }
                if (isset($data['fwd_from']['channel_id'])) {
                    try {
                        $newd['forward_from_chat'] = yield from $this->getPwrChat(MTProto::toSupergroup($data['fwd_from']['channel_id']));
                    } catch (\Throwable $e) {
                    }
                }
                if (isset($data['fwd_from']['date'])) {
                    $newd['forward_date'] = $data['fwd_from']['date'];
                }
                if (isset($data['fwd_from']['channel_post'])) {
                    $newd['forward_from_message_id'] = $data['fwd_from']['channel_post'];
                }
                if (isset($data['media'])) {
                    $newd = \array_merge($newd, yield from $this->MTProtoToBotAPI($data['media']));
                }
                return $newd;
            case 'messageEntityMention':
                unset($data['_']);
                $data['type'] = 'mention';
                return $data;
            case 'messageEntityHashtag':
                unset($data['_']);
                $data['type'] = 'hashtag';
                return $data;
            case 'messageEntityBotCommand':
                unset($data['_']);
                $data['type'] = 'bot_command';
                return $data;
            case 'messageEntityUrl':
                unset($data['_']);
                $data['type'] = 'url';
                return $data;
            case 'messageEntityEmail':
                unset($data['_']);
                $data['type'] = 'email';
                return $data;
            case 'messageEntityBold':
                unset($data['_']);
                $data['type'] = 'bold';
                return $data;
            case 'messageEntityStrike':
                unset($data['_']);
                $data['type'] = 'strikethrough';
                return $data;
            case 'messageEntitySpoiler':
                unset($data['_']);
                $data['type'] = 'spoiler';
                return $data;
            case 'messageEntityUnderline':
                unset($data['_']);
                $data['type'] = 'underline';
                return $data;
            case 'messageEntityItalic':
                unset($data['_']);
                $data['type'] = 'italic';
                return $data;
            case 'messageEntityCode':
                unset($data['_']);
                $data['type'] = 'code';
                return $data;
            case 'messageEntityPre':
                unset($data['_']);
                $data['type'] = 'pre';
                return $data;
            case 'messageEntityTextUrl':
                unset($data['_']);
                $data['type'] = 'text_url';
                return $data;
            case 'messageEntityMentionName':
                unset($data['_']);
                $data['type'] = 'text_mention';
                $data['user'] = (yield from $this->getPwrChat($data['user_id']));
                unset($data['user_id']);
                return $data;
            case 'messageMediaPhoto':
                if (isset($data['caption'])) {
                    $res['caption'] = $data['caption'];
                }
                $res['photo'] = [];
                foreach ($data['photo']['sizes'] as $key => $photo) {
                    if (\in_array($photo['_'], ['photoCachedSize', 'photoSize', 'photoSizeProgressive'])) {
                        $res['photo'][$key] = $this->photosizeToBotAPI($photo, $data['photo']);
                    }
                }
                return $res;
            case 'messageMediaEmpty':
                return [];
            case 'messageMediaDocument':
                $type_name = 'document';
                $res = [];
                if (isset($data['document']['thumbs']) && $data['document']['thumbs'] && \in_array(\end($data['document']['thumbs'])['_'], ['photoCachedSize', 'photoSize', 'photoSizeProgressive'])) {
                    $res['thumb'] = $this->photosizeToBotAPI(\end($data['document']['thumbs']), $data['document'], true);
                }
                foreach ($data['document']['attributes'] as $attribute) {
                    switch ($attribute['_']) {
                        case 'documentAttributeFilename':
                            $pathinfo = \pathinfo($attribute['file_name']);
                            $res['ext'] = isset($pathinfo['extension']) ? '.'.$pathinfo['extension'] : '';
                            $res['file_name'] = $pathinfo['filename'];
                            break;
                        case 'documentAttributeAudio':
                            $audio = $attribute;
                            $type_name = $attribute['voice'] ? 'voice' :'audio';
                            $res['duration'] = $attribute['duration'];
                            if (isset($attribute['performer'])) {
                                $res['performer'] = $attribute['performer'];
                            }
                            if (isset($attribute['title'])) {
                                $res['title'] = $attribute['title'];
                            }
                            if (isset($attribute['waveform'])) {
                                $res['title'] = $attribute['waveform'];
                            }
                            break;
                        case 'documentAttributeVideo':
                            $type_name = $attribute['round_message'] ? 'video_note' : 'video';
                            $res['width'] = $attribute['w'];
                            $res['height'] = $attribute['h'];
                            $res['duration'] = $attribute['duration'];
                            break;
                        case 'documentAttributeImageSize':
                            $res['width'] = $attribute['w'];
                            $res['height'] = $attribute['h'];
                            break;
                        case 'documentAttributeAnimated':
                            $type_name = 'animation';
                            $res['animated'] = true;
                            break;
                        case 'documentAttributeHasStickers':
                            $res['has_stickers'] = true;
                            break;
                        case 'documentAttributeSticker':
                            $type_name = 'sticker';
                            $res['mask'] = $attribute['mask'];
                            $res['emoji'] = $attribute['alt'];
                            $res['sticker_set'] = $attribute['stickerset'];
                            if (isset($attribute['mask_coords'])) {
                                $res['mask_coords'] = $attribute['mask_coords'];
                            }
                            break;
                    }
                }
                if (isset($audio) && isset($audio['title']) && !isset($res['file_name'])) {
                    $res['file_name'] = $audio['title'];
                    if (isset($audio['performer'])) {
                        $res['file_name'] .= ' - '.$audio['performer'];
                    }
                }
                if (!isset($res['file_name'])) {
                    $res['file_name'] = $data['document']['access_hash'];
                }
                $res['file_name'] .= '_'.$data['document']['id'];
                if (isset($res['ext'])) {
                    $res['file_name'] .= $res['ext'];
                    unset($res['ext']);
                } else {
                    $res['file_name'] .= Tools::getExtensionFromMime($data['document']['mime_type']);
                }
                $res['file_size'] = $data['document']['size'];
                $res['mime_type'] = $data['document']['mime_type'];

                $fileId = new FileId;
                $fileId->setId($data['document']['id']);
                $fileId->setAccessHash($data['document']['access_hash']);
                $fileId->setFileReference($data['document']['file_reference'] ?? '');
                $fileId->setDcId($data['document']['dc_id']);
                $fileId->setType(TYPES_IDS[$type_name]);

                $res['file_id'] = (string) $fileId;
                $res['file_unique_id'] = $fileId->getUniqueBotAPI();
                return [$type_name => $res, 'caption' => $data['caption'] ?? ''];
            default:
                throw new Exception(\sprintf(\danog\MadelineProto\Lang::$current_lang['botapi_conversion_error'], $data['_']));
        }
    }
    /**
     * Convert bot API parameters to MTProto parameters.
     *
     * @param array $arguments Arguments
     *
     */
    public function botAPIToMTProto(array $arguments): array
    {
        foreach (self::BOTAPI_PARAMS_CONVERSION as $bot => $mtproto) {
            if (isset($arguments[$bot]) && !isset($arguments[$mtproto])) {
                $arguments[$mtproto] = $arguments[$bot];
                //unset($arguments[$bot]);
            }
        }
        if (isset($arguments['reply_markup'])) {
            $arguments['reply_markup'] = $this->parseReplyMarkup($arguments['reply_markup']);
        }
        if (isset($arguments['parse_mode'])) {
            $arguments = $this->parseMode($arguments);
        }
        return $arguments;
    }
    /**
     * Convert markdown and HTML messages.
     *
     * @param array $arguments Arguments
     *
     * @internal
     *
     * @return \Generator<array>
     */
    public function parseMode(array $arguments)
    {
        if (($arguments['message'] ?? '') === '' || !isset($arguments['parse_mode'])) {
            return $arguments;
        }
        if (!(\is_string($arguments['message']) || \is_object($arguments['message']) && \method_exists($arguments['message'], '__toString'))) {
            throw new Exception('Messages can only be strings');
        }
        if (isset($arguments['parse_mode']['_'])) {
            $arguments['parse_mode'] = \str_replace('textParseMode', '', $arguments['parse_mode']['_']);
        }
        if (\stripos($arguments['parse_mode'], 'markdown') !== false) {
            $arguments['message'] = \Parsedown::instance()->line($arguments['message']);
            $arguments['parse_mode'] = 'HTML';
        }
        if (\stripos($arguments['parse_mode'], 'html') !== false) {
            $entities = new DOMEntities($arguments['message']);
            $arguments['message'] = $entities->message;
            $arguments['entities'] = \array_merge($arguments['entities'] ?? [], $entities->entities);
            if ($entities->buttons) {
                $arguments['reply_markup'] = $this->buildRows($entities->buttons);
            }
            unset($arguments['parse_mode']);
        }
        return $arguments;
    }

    /**
     * Split too long message into chunks.
     *
     * @param array $args Arguments
     *
     * @internal
     */
    public function splitToChunks($args): array
    {
        $args = $this->parseMode($args);
        $args['entities'] ??= [];

        var_dump($args);
        // UTF-8 length, not UTF-16
        $max_length = isset($args['media']) ? $this->config['caption_length_max'] : $this->config['message_length_max'];
        $cur_len = 0;
        $cur = '';
        $multiple_args_base = \array_merge($args, ['entities' => [], 'parse_mode' => 'text', 'message' => '']);
        $multiple_args = [];
        foreach (\explode("\n", $args['message']) as $word) {
            foreach (\mb_str_split($word."\n", $max_length, 'UTF-8') as $vv) {
                $len = \mb_strlen($vv, 'UTF-8');
                if ($cur_len + $len <= $max_length) {
                    $cur .= $vv;
                    $cur_len += $len;
                } else {
                    if (\trim($cur) !== '') {
                        $multiple_args[] = [
                            ...$multiple_args_base,
                            'message' => $cur
                        ];
                    }
                    $cur = $vv;
                    $cur_len = $len;
                }
            }
        }
        if (\trim($cur) !== '') {
            $multiple_args[] = [
                ...$multiple_args_base,
                'message' => $cur
            ];
        }
        var_dump($multiple_args);

        $i = 0;
        $offset = 0;
        for ($k = 0; $k < \count($args['entities']); $k++) {
            $entity = $args['entities'][$k];
            do {
                while ($entity['offset'] > $offset + StrTools::mbStrlen($multiple_args[$i]['message'])) {
                    $offset += StrTools::mbStrlen($multiple_args[$i]['message']);
                    $i++;
                }
                $entity['offset'] -= $offset;
                if ($entity['offset'] + $entity['length'] > StrTools::mbStrlen($multiple_args[$i]['message'])) {
                    $newentity = $entity;
                    $newentity['length'] = $entity['length'] - (StrTools::mbStrlen($multiple_args[$i]['message']) - $entity['offset']);
                    $entity['length'] = StrTools::mbStrlen($multiple_args[$i]['message']) - $entity['offset'];
                    $offset += $entity['length'];
                    //StrTools::mbStrlen($multiple_args[$i]['message']);
                    $newentity['offset'] = $offset;
                    $prev_length = StrTools::mbStrlen($multiple_args[$i]['message']);
                    $multiple_args[$i]['message'] = \rtrim($multiple_args[$i]['message']);
                    $diff = $prev_length - StrTools::mbStrlen($multiple_args[$i]['message']);
                    if ($diff) {
                        $entity['length'] -= $diff;
                        foreach ($args['entities'] as $key => &$eentity) {
                            if ($key > $k) {
                                $eentity['offset'] -= $diff;
                            }
                        }
                    }
                    $multiple_args[$i]['entities'][] = $entity;
                    $i++;
                    $entity = $newentity;
                    continue;
                }
                $prev_length = StrTools::mbStrlen($multiple_args[$i]['message']);
                $multiple_args[$i]['message'] = \rtrim($multiple_args[$i]['message']);
                $diff = $prev_length - StrTools::mbStrlen($multiple_args[$i]['message']);
                if ($diff) {
                    $entity['length'] -= $diff;
                    foreach ($args['entities'] as $key => &$eentity) {
                        if ($key > $k) {
                            $eentity['offset'] -= $diff;
                        }
                    }
                }
                $multiple_args[$i]['entities'][] = $entity;
                break;
            } while (true);
        }
        $total = 0;
        foreach ($multiple_args as $args) {
            if (\count($args['entities']) > self::MAX_ENTITY_LENGTH) {
                $total += \count($args['entities']) - self::MAX_ENTITY_LENGTH;
            }
            $c = 0;
            foreach ($args['entities'] as $entity) {
                if (isset($entity['url'])) {
                    $c += \strlen($entity['url']);
                }
            }
            if ($c >= self::MAX_ENTITY_SIZE) {
                $this->logger->logger('Entity size limit possibly exceeded, you may get an error indicating that the entities are too long. Reduce the number of entities and/or size of the URLs used.', Logger::FATAL_ERROR);
            }
        }
        if ($total) {
            $this->logger->logger("Too many entities, {$total} entities will be truncated", Logger::FATAL_ERROR);
        }
        return $multiple_args;
    }
    /**
     * @return ((array|string)[][]|string)[]
     *
     * @psalm-return array{_: string, rows: list<array{_: string, buttons: list<mixed>}>}
     */
    private function buildRows($button_list): array
    {
        $end = false;
        $rows = [];
        $buttons = [];
        $cols = 0;
        foreach ($button_list as $button) {
            if (isset($button['new'])) {
                if (\count($buttons) == 0) {
                    $buttons[] = $button;
                } else {
                    $row = ['_' => 'keyboardButtonRow', 'buttons' => $buttons];
                    $rows[] = $row;
                    $buttons = [$button];
                }
            } else {
                $buttons[] = $button;
                $end = true;
            }
        }
        if ($end) {
            $row = ['_' => 'keyboardButtonRow', 'buttons' => $buttons];
            $rows[] = $row;
        }
        return ['_' => 'replyInlineMarkup', 'rows' => $rows];
    }
}

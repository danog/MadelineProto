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
 * @copyright 2016-2019 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\TL\Conversion;

use danog\MadelineProto\Logger;

trait BotAPI
{
    private function htmlEntityDecode(string $stuff): string
    {
        return \html_entity_decode(\preg_replace('#< *br */? *>#', "\n", $stuff));
    }

    /**
     * Get Telegram UTF-8 length of string.
     *
     * @param string $text Text
     *
     * @return int
     */
    public static function mbStrlen(string $text): int
    {
        $length = 0;
        $textlength = \strlen($text);
        for ($x = 0; $x < $textlength; $x++) {
            $char = \ord($text[$x]);
            if (($char & 0xC0) != 0x80) {
                $length += 1 + ($char >= 0xf0);
            }
        }

        return $length;
    }

    /**
     * Telegram UTF-8 multibyte substring.
     *
     * @param string  $text   Text to substring
     * @param integer $offset Offset
     * @param ?int    $length Length
     *
     * @return string
     */
    public static function mbSubstr(string $text, int $offset, $length = null): string
    {
        $mb_text_length = self::mbStrlen($text);
        if ($offset < 0) {
            $offset = $mb_text_length + $offset;
        }
        if ($length < 0) {
            $length = ($mb_text_length - $offset) + $length;
        } elseif ($length === null) {
            $length = $mb_text_length - $offset;
        }
        $new_text = '';
        $current_offset = 0;
        $current_length = 0;
        $text_length = \strlen($text);
        for ($x = 0; $x < $text_length; $x++) {
            $char = \ord($text[$x]);
            if (($char & 0xC0) != 0x80) {
                $current_offset += 1 + ($char >= 0xf0);
                if ($current_offset > $offset) {
                    $current_length += 1 + ($char >= 0xf0);
                }
            }
            if ($current_offset > $offset) {
                if ($current_length <= $length) {
                    $new_text .= $text[$x];
                }
            }
        }

        return $new_text;
    }

    /**
     * Telegram UTF-8 multibyte split.
     *
     * @param string  $text   Text
     * @param integer $length Length
     *
     * @return array
     */
    public static function mbStrSplit(string $text, int $length): array
    {
        $tlength = \mb_strlen($text, 'UTF-8');
        $result = [];
        for ($x = 0; $x < $tlength; $x += $length) {
            $result[] = \mb_substr($text, $x, $length, 'UTF-8');
        }

        return $result;
    }

    private function parseButtons($rows)
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
     * @param array $data           Data
     * @param array $sent_arguments Sent arguments
     *
     * @return \Generator<array>
     */
    public function MTProtoToBotAPI(array $data, array $sent_arguments = []): \Generator
    {
        $newd = [];
        if (!isset($data['_'])) {
            foreach ($data as $key => $element) {
                $newd[$key] = yield $this->MTProtoToBotAPI($element, $sent_arguments);
            }

            return $newd;
        }
        switch ($data['_']) {
            case 'updateShortSentMessage':
                $newd['message_id'] = $data['id'];
                $newd['date'] = $data['date'];
                $newd['text'] = $sent_arguments['message'];
                if ($data['out']) {
                    $newd['from'] = yield $this->getPwrChat($this->authorization['user']);
                }
                $newd['chat'] = yield $this->getPwrChat($sent_arguments['peer']);
                if (isset($data['entities'])) {
                    $newd['entities'] = yield $this->MTProtoToBotAPI($data['entities'], $sent_arguments);
                }
                if (isset($data['media'])) {
                    $newd = \array_merge($newd, yield $this->MTProtoToBotAPI($data['media'], $sent_arguments));
                }

                return $newd;
            case 'updateNewChannelMessage':
            case 'updateNewMessage':
                return yield $this->MTProtoToBotAPI($data['message']);
            case 'message':
                $newd['message_id'] = $data['id'];
                $newd['date'] = $data['date'];
                $newd['text'] = $data['message'];
                $newd['post'] = $data['post'];
                $newd['silent'] = $data['silent'];
                if (isset($data['from_id'])) {
                    $newd['from'] = yield $this->getPwrChat($data['from_id']);
                }
                $newd['chat'] = yield $this->getPwrChat($data['to_id']);
                if (isset($data['entities'])) {
                    $newd['entities'] = yield $this->MTProtoToBotAPI($data['entities'], $sent_arguments);
                }
                if (isset($data['views'])) {
                    $newd['views'] = $data['views'];
                }
                if (isset($data['edit_date'])) {
                    $newd['edit_date'] = $data['edit_date'];
                }
                if (isset($data['via_bot_id'])) {
                    $newd['via_bot'] = yield $this->getPwrChat($data['via_bot_id']);
                }
                if (isset($data['fwd_from']['from_id'])) {
                    $newd['forward_from'] = yield $this->getPwrChat($data['fwd_from']['from_id']);
                }
                if (isset($data['fwd_from']['channel_id'])) {
                    $newd['forward_from_chat'] = yield $this->getPwrChat($data['fwd_from']['channel_id']);
                }
                if (isset($data['fwd_from']['date'])) {
                    $newd['forward_date'] = $data['fwd_from']['date'];
                }
                if (isset($data['fwd_from']['channel_post'])) {
                    $newd['forward_from_message_id'] = $data['fwd_from']['channel_post'];
                }
                if (isset($data['media'])) {
                    $newd = \array_merge($newd, yield $this->MTProtoToBotAPI($data['media'], $sent_arguments));
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
                $data['user'] = yield $this->getPwrChat($data['user_id']);
                unset($data['user_id']);

                return $data;
            case 'messageMediaPhoto':
                if (isset($data['caption'])) {
                    $res['caption'] = $data['caption'];
                }
                $res['photo'] = [];
                foreach ($data['photo']['sizes'] as $key => $photo) {
                    if (\in_array($photo['_'], ['photoCachedSize', 'photoSize'])) {
                        $res['photo'][$key] = yield $this->photosizeToBotAPI($photo, $data['photo']);
                    }
                }

                return $res;
            case 'messageMediaEmpty':
                return [];
            case 'messageMediaDocument':
                $type_name = 'document';
                $res = [];
                if (isset($data['document']['thumbs']) && $data['document']['thumbs'] && \in_array(\end($data['document']['thumbs'])['_'], ['photoCachedSize', 'photoSize'])) {
                    $res['thumb'] = yield $this->photosizeToBotAPI(\end($data['document']['thumbs']), [], true);
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
                            $type_name = 'audio';
                            if ($attribute['voice']) {
                                $type_name = 'voice';
                            }
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
                            $type_name = 'gif';
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
                    $res['file_name'] .= $this->getExtensionFromMime($data['document']['mime_type']);
                }
                $data['document']['_'] = 'bot_'.$type_name;
                $res['file_size'] = $data['document']['size'];
                $res['mime_type'] = $data['document']['mime_type'];
                $res['file_id'] = \danog\MadelineProto\Tools::base64urlEncode(\danog\MadelineProto\Tools::rleEncode((yield $this->TL->serializeObject(['type' => 'File'], $data['document'], 'File')).\chr(2)));

                return [$type_name => $res, 'caption' => isset($data['caption']) ? $data['caption'] : ''];
            default:
                throw new Exception(\sprintf(\danog\MadelineProto\Lang::$current_lang['botapi_conversion_error'], $data['_']));
        }
    }

    /**
     * Convert bot API parameters to MTProto parameters.
     *
     * @param array $arguments Arguments
     *
     * @return \Generator<array>
     */
    public function botAPIToMTProto(array $arguments): \Generator
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
            $arguments = yield $this->parseMode($arguments);
        }

        return $arguments;
    }

    private function parseNode($node, &$entities, &$new_message, &$offset)
    {
        switch ($node->nodeName) {
            case 'br':
                $new_message .= "\n";
                $offset++;
                break;
            case 's':
            case 'strike':
            case 'del':
                $text = $this->htmlEntityDecode($node->textContent);

                $length = $this->mbStrlen($text);
                $entities[] = ['_' => 'messageEntityStrike', 'offset' => $offset, 'length' => $length];

                $new_message .= $text;
                $offset += $length;
                break;
            case 'u':
                $text = $this->htmlEntityDecode($node->textContent);

                $length = $this->mbStrlen($text);
                $entities[] = ['_' => 'messageEntityUnderline', 'offset' => $offset, 'length' => $length];

                $new_message .= $text;
                $offset += $length;
                break;
            case 'blockquote':
                $text = $this->htmlEntityDecode($node->textContent);

                $length = $this->mbStrlen($text);
                $entities[] = ['_' => 'messageEntityBlockquote', 'offset' => $offset, 'length' => $length];

                $new_message .= $text;
                $offset += $length;
                break;
            case 'b':
            case 'strong':
                $text = $this->htmlEntityDecode($node->textContent);

                $length = $this->mbStrlen($text);
                $entities[] = ['_' => 'messageEntityBold', 'offset' => $offset, 'length' => $length];

                $new_message .= $text;
                $offset += $length;
                break;
            case 'i':
            case 'em':
                $text = $this->htmlEntityDecode($node->textContent);

                $length = $this->mbStrlen($text);
                $entities[] = ['_' => 'messageEntityItalic', 'offset' => $offset, 'length' => $length];

                $new_message .= $text;
                $offset += $length;
                break;
            case 'code':
                $text = $this->htmlEntityDecode($node->textContent);

                $length = $this->mbStrlen($text);
                $entities[] = ['_' => 'messageEntityCode', 'offset' => $offset, 'length' => $length];

                $new_message .= $text;
                $offset += $length;
                break;
            case 'pre':
                $text = $this->htmlEntityDecode($node->textContent);

                $length = $this->mbStrlen($text);

                $language = $node->getAttribute('language');
                if ($language === null) {
                    $language = '';
                }
                $entities[] = ['_' => 'messageEntityPre', 'offset' => $offset, 'length' => $length, 'language' => $language];
                $new_message .= $text;
                $offset += $length;
                break;
            case 'p':
                foreach ($node->childNodes as $node) {
                    yield $this->parseNode($node, $entities, $new_message, $offset);
                }
                break;
            case 'a':
                $text = $this->htmlEntityDecode($node->textContent);
                $length = $this->mbStrlen($text);
                $href = $node->getAttribute('href');
                if (\preg_match('|mention:(.*)|', $href, $matches) || \preg_match('|tg://user\?id=(.*)|', $href, $matches)) {
                    $mention = yield $this->getInfo($matches[1]);
                    if (!isset($mention['InputUser'])) {
                        throw new \danog\MadelineProto\Exception(\danog\MadelineProto\Lang::$current_lang['peer_not_in_db']);
                    }
                    $entities[] = ['_' => 'inputMessageEntityMentionName', 'offset' => $offset, 'length' => $length, 'user_id' => $mention['InputUser']];
                } elseif (\preg_match('|buttonurl:(.*)|', $href)) {
                    if (!isset($entities['buttons'])) {
                        $entities['buttons'] = [];
                    }
                    if (\strpos(\substr($href, -4), '|:new|') !== false) {
                        $entities['buttons'][] = ['_' => 'keyboardButtonUrl', 'text' => $text, 'url' => \str_replace(['buttonurl:', ':new'], '', $href), 'new' => true];
                    } else {
                        $entities['buttons'][] = ['_' => 'keyboardButtonUrl', 'text' => $text, 'url' => \str_replace('buttonurl:', '', $href)];
                    }
                    break;
                } else {
                    $entities[] = ['_' => 'messageEntityTextUrl', 'offset' => $offset, 'length' => $length, 'url' => $href];
                }
                $new_message .= $text;
                $offset += $length;
                break;
            default:
                $text = $this->htmlEntityDecode($node->textContent);
                $length = $this->mbStrlen($text);
                $new_message .= $text;
                $offset += $length;
                break;
        }
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
    public function parseMode(array $arguments): \Generator
    {
        if ($arguments['message'] === '' || !isset($arguments['message']) || !isset($arguments['parse_mode'])) {
            return $arguments;
        }
        if (isset($arguments['parse_mode']['_'])) {
            $arguments['parse_mode'] = \str_replace('textParseMode', '', $arguments['parse_mode']['_']);
        }
        if (\stripos($arguments['parse_mode'], 'markdown') !== false) {
            $arguments['message'] = \Parsedown::instance()->line($arguments['message']);
            $arguments['parse_mode'] = 'HTML';
        }
        if (\stripos($arguments['parse_mode'], 'html') !== false) {
            $new_message = '';

            $arguments['message'] = \trim($this->htmlFixtags($arguments['message']));

            $dom = new \DOMDocument();
            $dom->loadHTML(\mb_convert_encoding($arguments['message'], 'HTML-ENTITIES', 'UTF-8'));
            if (!isset($arguments['entities'])) {
                $arguments['entities'] = [];
            }
            $offset = 0;
            foreach ($dom->getElementsByTagName('body')->item(0)->childNodes as $node) {
                yield $this->parseNode($node, $arguments['entities'], $new_message, $offset);
            }
            if (isset($arguments['entities']['buttons'])) {
                $arguments['reply_markup'] = $this->buildRows($arguments['entities']['buttons']);
                unset($arguments['entities']['buttons']);
            }
            unset($arguments['parse_mode']);
            $arguments['message'] = $new_message;
        }

        return $arguments;
    }

    /**
     * Split too long message into chunks.
     *
     * @param array $args Arguments
     *
     * @internal
     *
     * @return \Generator
     */
    public function splitToChunks($args): \Generator
    {
        $args = yield $this->parseMode($args);
        if (!isset($args['entities'])) {
            $args['entities'] = [];
        }

        $max_length = isset($args['media']) ? $this->config['caption_length_max'] : $this->config['message_length_max'];
        $max_entity_length = 100;
        $max_entity_size = 8110;

        $text_arr = [];
        foreach ($this->multipleExplodeKeepDelimiters(["\n"], $args['message']) as $word) {
            if (\mb_strlen($word, 'UTF-8') > $max_length) {
                foreach ($this->mbStrSplit($word, $max_length) as $vv) {
                    $text_arr[] = $vv;
                }
            } else {
                $text_arr[] = $word;
            }
        }

        $multiple_args_base = \array_merge($args, ['entities' => [], 'parse_mode' => 'text', 'message' => '']);
        $multiple_args = [$multiple_args_base];
        $i = 0;
        foreach ($text_arr as $word) {
            if ($this->mbStrlen($multiple_args[$i]['message'].$word) <= $max_length) {
                $multiple_args[$i]['message'] .= $word;
            } else {
                $i++;
                $multiple_args[$i] = $multiple_args_base;
                $multiple_args[$i]['message'] .= $word;
            }
        }

        $i = 0;
        $offset = 0;
        for ($k = 0; $k < \count($args['entities']); $k++) {
            $entity = $args['entities'][$k];
            do {
                while ($entity['offset'] > $offset + $this->mbStrlen($multiple_args[$i]['message'])) {
                    $offset += $this->mbStrlen($multiple_args[$i]['message']);
                    $i++;
                }
                $entity['offset'] -= $offset;

                if ($entity['offset'] + $entity['length'] > $this->mbStrlen($multiple_args[$i]['message'])) {
                    $newentity = $entity;
                    $newentity['length'] = $entity['length'] - ($this->mbStrlen($multiple_args[$i]['message']) - $entity['offset']);
                    $entity['length'] = $this->mbStrlen($multiple_args[$i]['message']) - $entity['offset'];

                    $offset += $entity['length']; //$this->mbStrlen($multiple_args[$i]['message']);
                    $newentity['offset'] = $offset;

                    $prev_length = $this->mbStrlen($multiple_args[$i]['message']);
                    $multiple_args[$i]['message'] = \rtrim($multiple_args[$i]['message']);
                    $diff = $prev_length - $this->mbStrlen($multiple_args[$i]['message']);

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
                $prev_length = $this->mbStrlen($multiple_args[$i]['message']);
                $multiple_args[$i]['message'] = \rtrim($multiple_args[$i]['message']);
                $diff = $prev_length - $this->mbStrlen($multiple_args[$i]['message']);
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
            if (\count($args['entities']) > $max_entity_length) {
                $total += \count($args['entities']) - $max_entity_length;
            }
            $c = 0;
            foreach ($args['entities'] as $entity) {
                if (isset($entity['url'])) {
                    $c += \strlen($entity['url']);
                }
            }
            if ($c >= $max_entity_size) {
                $this->logger->logger('Entity size limit possibly exceeded, you may get an error indicating that the entities are too long. Reduce the number of entities and/or size of the URLs used.', Logger::FATAL_ERROR);
            }
        }
        if ($total) {
            $this->logger->logger("Too many entities, $total entities will be truncated", Logger::FATAL_ERROR);
        }

        return $multiple_args;
    }

    private function multipleExplodeKeepDelimiters($delimiters, $string)
    {
        $initialArray = \explode(\chr(1), \str_replace($delimiters, \chr(1), $string));
        $finalArray = [];
        $delimOffset = 0;
        foreach ($initialArray as $item) {
            $delimOffset += $this->mbStrlen($item);
            //if ($this->mbStrlen($item) > 0) {
            $finalArray[] = $item.($delimOffset < $this->mbStrlen($string) ? $string[$delimOffset] : '');
            //}
            $delimOffset++;
        }

        return $finalArray;
    }

    private function htmlFixtags($text)
    {
        $diff = 0;
        \preg_match_all('#(.*?)(<(\bu\b|\bs\b|\ba\b|\bb\b|\bstrong\b|\bblockquote\b|\bstrike\b|\bdel\b|\bem\b|i|\bcode\b|\bpre\b)[^>]*>)(.*?)([<]\s*/\s*\3[>])#is', $text, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
        if ($matches) {
            foreach ($matches as $match) {
                if (\trim($match[1][0]) != '') {
                    $mod = \htmlentities($match[1][0]);

                    $temp = \substr($text, 0, $match[1][1] + $diff);
                    $temp .= $mod;
                    $temp .= \substr($text, $match[1][1] + $diff + \strlen($match[1][0]));

                    $diff += \strlen($mod) - \strlen($match[1][0]);

                    $text = $temp;
                }
                $mod = \htmlentities($match[4][0]);

                $temp = \substr($text, 0, $match[4][1] + $diff);
                $temp .= $mod;
                $temp .= \substr($text, $match[4][1] + $diff + \strlen($match[4][0]));

                $diff += \strlen($mod) - \strlen($match[4][0]);
                $text = $temp;
            }
            $diff = 0;
            \preg_match_all('#<a\s*href=("|\')(.+?)("|\')\s*>#is', $text, $matches, PREG_OFFSET_CAPTURE);
            foreach ($matches[2] as $match) {
                $mod = \htmlentities($match[0]);
                $temp = \substr($text, 0, $match[1] + $diff);
                $temp .= $mod;
                $temp .= \substr($text, $match[1] + $diff + \strlen($match[0]));

                $diff += \strlen($mod) - \strlen($match[0]);
                $text = $temp;
            }

            return $text;
        }
        return \htmlentities($text);
    }

    private function buildRows($button_list)
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

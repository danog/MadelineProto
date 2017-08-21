<?php
/*
Copyright 2016-2017 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with MadelineProto.
If not, see <http://www.gnu.org/licenses/>.
*/

namespace danog\MadelineProto\TL\Conversion;

trait BotAPI
{
    public function html_entity_decode($stuff)
    {
        return html_entity_decode(preg_replace('#< *br */? *>#', "\n", $stuff));
    }

    public function parse_buttons($rows)
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
                } elseif (isset($button['request_contact'])) {
                    $newrows[$key]['buttons'][$button_key]['_'] = 'keyboardButtonRequestPhone';
                } elseif (isset($button['request_location'])) {
                    $newrows[$key]['buttons'][$button_key]['_'] = 'keyboardButtonRequestGeoLocation';
                }
                $button_key++;
            }
            $key++;
        }

        return $newrows;
    }

    public function parse_reply_markup($markup)
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
            $markup['rows'] = $this->parse_buttons($markup['keyboard']);
            unset($markup['keyboard']);
        }
        if (isset($markup['inline_keyboard'])) {
            $markup['_'] = 'replyInlineMarkup';
            $markup['rows'] = $this->parse_buttons($markup['inline_keyboard']);
            unset($markup['inline_keyboard']);
        }

        return $markup;
    }

    public function MTProto_to_botAPI($data, $sent_arguments = [])
    {
        $newd = [];
        if (!isset($data['_'])) {
            foreach ($data as $key => $element) {
                $newd[$key] = $this->MTProto_to_botAPI($element, $sent_arguments);
            }

            return $newd;
        }
        switch ($data['_']) {
            case 'updateShortSentMessage':
            $newd['message_id'] = $data['id'];
            $newd['date'] = $data['date'];
            $newd['text'] = $sent_arguments['message'];
            if ($data['out']) {
                $newd['from'] = $this->get_pwr_chat($this->authorization['user']);
            }
            $newd['chat'] = $this->get_pwr_chat($sent_arguments['peer']);
            if (isset($data['entities'])) {
                $newd['entities'] = $this->MTProto_to_botAPI($data['entities'], $sent_arguments);
            }
            if (isset($data['media'])) {
                $newd = array_merge($newd, $this->MTProto_to_botAPI($data['media'], $sent_arguments));
            }

            return $newd;

            case 'updateNewChannelMessage':
            case 'updateNewMessage':
            return $this->MTProto_to_botAPI($data['message']);

            case 'message':
            $newd['message_id'] = $data['id'];
            $newd['date'] = $data['date'];
            $newd['text'] = $data['message'];
            $newd['post'] = $data['post'];
            $newd['silent'] = $data['silent'];
            if (isset($data['from_id'])) {
                $newd['from'] = $this->get_pwr_chat($data['from_id']);
            }
            $newd['chat'] = $this->get_pwr_chat($data['to_id']);
            if (isset($data['entities'])) {
                $newd['entities'] = $this->MTProto_to_botAPI($data['entities'], $sent_arguments);
            }
            if (isset($data['views'])) {
                $newd['views'] = $data['views'];
            }
            if (isset($data['edit_date'])) {
                $newd['edit_date'] = $data['edit_date'];
            }
            if (isset($data['via_bot_id'])) {
                $newd['via_bot'] = $this->get_pwr_chat($data['via_bot_id']);
            }
            if (isset($data['fwd_from']['from_id'])) {
                $newd['froward_from'] = $this->get_pwr_chat($data['fwd_from']['from_id']);
            }
            if (isset($data['fwd_from']['channel_id'])) {
                $newd['forward_from_chat'] = $this->get_pwr_chat($data['fwd_from']['channel_id']);
            }
            if (isset($data['fwd_from']['date'])) {
                $newd['forward_date'] = $data['fwd_from']['date'];
            }
            if (isset($data['fwd_from']['channel_post'])) {
                $newd['forward_from_message_id'] = $data['fwd_from']['channel_post'];
            }

            if (isset($data['media'])) {
                $newd = array_merge($newd, $this->MTProto_to_botAPI($data['media'], $sent_arguments));
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
            $data['user'] = $this->get_pwr_chat($data['user_id']);
            unset($data['user_id']);

            return $data;

            case 'messageMediaPhoto':
            if (isset($data['caption'])) {
                $res['caption'] = $data['caption'];
            }
            $res['photo'] = [];
            foreach ($data['photo']['sizes'] as $key => $photo) {
                $res['photo'][$key] = $this->photosize_to_botapi($photo, $data['photo']);
            }

            return $res;
            case 'messageMediaEmpty':
            return [];

            case 'messageMediaDocument':
            $type_name = 'document';
            $res = [];
            if ($data['document']['thumb']['_'] === 'photoSize') {
                $res['thumb'] = $this->photosize_to_botapi($data['document']['thumb'], [], true);
            }
            foreach ($data['document']['attributes'] as $attribute) {
                switch ($attribute['_']) {
                    case 'documentAttributeFilename':
                    $pathinfo = pathinfo($attribute['file_name']);
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
                $res['file_name'] .= $this->get_extension_from_mime($data['document']['mime_type']);
            }
            $data['document']['_'] = 'bot_'.$type_name;
            $res['file_size'] = $data['document']['size'];
            $res['mime_type'] = $data['document']['mime_type'];
            $res['file_id'] = $this->base64url_encode($this->rle_encode($this->serialize_object(['type' => 'File'], $data['document'], 'File').chr(2)));

            return [$type_name => $res, 'caption' => isset($data['caption']) ? $data['caption'] : ''];
            default:
            throw new Exception("Can't convert ".$data['_'].' to a bot API object');
        }
    }

    public $botapi_params = [
        'disable_web_page_preview' => 'no_webpage',
        'disable_notification'     => 'silent',
        'reply_to_message_id'      => 'reply_to_msg_id',
        'chat_id'                  => 'peer',
        'text'                     => 'message',
    ];

    public function botAPI_to_MTProto($arguments)
    {
        foreach ($this->botapi_params as $bot => $mtproto) {
            if (isset($arguments[$bot]) && !isset($arguments[$mtproto])) {
                $arguments[$mtproto] = $arguments[$bot];
                //unset($arguments[$bot]);
            }
        }
        if (isset($arguments['reply_markup'])) {
            $arguments['reply_markup'] = $this->parse_reply_markup($arguments['reply_markup']);
        }
        if (isset($arguments['parse_mode'])) {
            $arguments = $this->parse_mode($arguments);
        }

        return $arguments;
    }

    public function parse_node($node, &$entities, &$nmessage, $recursive = true)
    {
        switch ($node->nodeName) {
            case 'br':
            $nmessage .= "\n";
            break;
            case 'b':
            case 'strong':
            $text = $this->html_entity_decode($node->textContent);
            $entities[] = ['_' => 'messageEntityBold', 'offset' => mb_strlen($nmessage), 'length' => mb_strlen($text)];
            $nmessage .= $text;
            break;

            case 'i':
            case 'em':
            $text = $this->html_entity_decode($node->textContent);
            $entities[] = ['_' => 'messageEntityItalic', 'offset' => mb_strlen($nmessage), 'length' => mb_strlen($text)];
            $nmessage .= $text;
            break;

            case 'code':
            $text = $this->html_entity_decode($node->textContent);
            $entities[] = ['_' => 'messageEntityCode', 'offset' => mb_strlen($nmessage), 'length' => mb_strlen($text)];
            $nmessage .= $text;
            break;

            case 'pre':
            $text = $this->html_entity_decode($node->textContent);
            $language = $node->getAttribute('language');
            if ($language === null) {
                $language = '';
            }
            $entities[] = ['_' => 'messageEntityPre', 'offset' => mb_strlen($nmessage), 'length' => mb_strlen($text), 'language' => $language];
            $nmessage .= $text;
            break;

            case 'p':
            foreach ($node->childNodes as $node) {
                $this->parse_node($node, $entities, $nmessage);
            }
            break;

            case 'a':
            $text = $this->html_entity_decode($node->textContent);
            $href = $node->getAttribute('href');
            if (preg_match('|mention:|', $href)) {
                $mention = $this->get_info(str_replace('mention:', '', $href));
                if (!isset($mention['InputUser'])) {
                    throw new \danog\MadelineProto\Exception('This peer is not present in the internal peer database');
                }
                $entities[] = ['_' => 'inputMessageEntityMentionName', 'offset' => mb_strlen($nmessage), 'length' => mb_strlen($text), 'user_id' => $mention['InputUser']];
            } elseif (preg_match('|buttonurl:|', $href)) {
                if (!isset($entities['buttons'])) {
                    $entities['buttons'] = [];
                }
                if (preg_match('|:new|', substr($href, -4))) {
                    $entities['buttons'][] = ['_' => 'keyboardButtonUrl', 'text' => $text, 'url' => str_replace('buttonurl:', '', str_replace(':new', '', $href)), 'new' => true];
                } else {
                    $entities['buttons'][] = ['_' => 'keyboardButtonUrl', 'text' => $text, 'url' => str_replace('buttonurl:', '', $href)];
                }
                break;
            } else {
                $entities[] = ['_' => 'messageEntityTextUrl', 'offset' => mb_strlen($nmessage), 'length' => mb_strlen($text), 'url' => $href];
            }
            $nmessage .= $text;
            break;

            default:
            $nmessage .= $this->html_entity_decode($node->nodeValue);
            break;
        }
    }

    public function parse_mode($arguments)
    {
        if (isset($arguments['parse_mode']['_'])) {
            $arguments['parse_mode'] = str_replace('textParseMode', '', $arguments['parse_mode']['_']);
        }
        if (preg_match('/markdown/i', $arguments['parse_mode'])) {
            $arguments['message'] = \Parsedown::instance()->line($arguments['message']);
            $arguments['parse_mode'] = 'HTML';
        }
        if (preg_match('/html/i', $arguments['parse_mode'])) {
            $nmessage = '';

            try {
                $arguments['message'] = $this->html_fixtags($arguments['message']);
                $dom = new \DOMDocument();
                $dom->loadHTML(mb_convert_encoding($arguments['message'], 'HTML-ENTITIES', 'UTF-8'));
                if (!isset($arguments['entities'])) {
                    $arguments['entities'] = [];
                }
                foreach ($dom->getElementsByTagName('body')->item(0)->childNodes as $node) {
                    $this->parse_node($node, $arguments['entities'], $nmessage);
                }
                if (isset($arguments['entities']['buttons'])) {
                    $arguments['reply_markup'] = $this->build_rows($arguments['entities']['buttons']);
                    unset($arguments['entities']['buttons']);
                }
                unset($arguments['parse_mode']);
            } catch (\DOMException $e) {
            } catch (\danog\MadelineProto\Exception $e) {
            }
            $arguments['message'] = $nmessage;
        }

        return $arguments;
    }

    public function split_to_chunks($text)
    {
        $total_length = 4096;
        $text_arr = [];
        foreach ($this->multipleExplodeKeepDelimiters(["\n"], $text) as $word) {
            if (strlen($word) > 4096) {
                foreach (str_split($word, 4096) as $vv) {
                    $text_arr[] = $vv;
                }
            } else {
                $text_arr[] = $word;
            }
        }
        $i = 0;
        $message[0] = '';
        foreach ($text_arr as $word) {
            if (strlen($message[$i].$word.' ') <= $total_length) {
                if ($text_arr[count($text_arr) - 1] == $word) {
                    $message[$i] .= $word;
                } else {
                    $message[$i] .= $word.' ';
                }
            } else {
                $i++;
                if ($text_arr[count($text_arr) - 1] == $word) {
                    $message[$i] = $word;
                } else {
                    $message[$i] = $word.' ';
                }
            }
        }

        return $message;
    }

    public function multipleExplodeKeepDelimiters($delimiters, $string)
    {
        $initialArray = explode(chr(1), str_replace($delimiters, chr(1), $string));
        $finalArray = [];
        foreach ($initialArray as $item) {
            if (strlen($item) > 0) {
                $finalArray[] = $item.$string[strpos($string, $item) + strlen($item)];
            }
        }

        return $finalArray;
    }

    public function html_fixtags($text)
    {
        preg_match_all("#(.*?)(<(a|b|strong|em|i|code|pre)[^>]*>)([^<]*?)(<\/\\3>)(.*)?#is", $text, $matches, PREG_SET_ORDER);
        if ($matches) {
            $last = count($matches) - 1;
            foreach ($matches as $val) {
                if (trim($val[1]) != '') {
                    $text = str_replace($val[1], htmlentities($val[1]), $text);
                }
                $text = str_replace($val[4], htmlentities(trim($val[4])), $text);
                if ($val == $matches[$last]) {
                    $text = str_replace($val[6], $this->html_fixtags($val[6]), $text);
                }
            }
            preg_match_all("#<a href=\x22(.+?)\x22>#is", $text, $matches);
            foreach ($matches[1] as $match) {
                $text = str_replace($match, htmlentities($match), $text);
            }

            return $text;
        } else {
            return htmlentities($text);
        }
    }

    public function build_rows($button_list)
    {
        $end = false;
        $rows = [];
        $buttons = [];
        $cols = 0;
        foreach ($button_list as $button) {
            if (isset($button['new'])) {
                if (count($buttons) == 0) {
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

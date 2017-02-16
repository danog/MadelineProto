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

namespace danog\MadelineProto\TL;

trait TL
{
    public function construct_tl($files)
    {
        \danog\MadelineProto\Logger::log(['Loading TL schemes...'], \danog\MadelineProto\Logger::VERBOSE);
        $this->constructors = new \danog\MadelineProto\TL\TLConstructor();
        $this->methods = new \danog\MadelineProto\TL\TLMethod();
        foreach ($files as $scheme_type => $file) {
            $scheme_type = $scheme_type === 'mtproto';
            \danog\MadelineProto\Logger::log(['Parsing '.basename($file).'...'], \danog\MadelineProto\Logger::VERBOSE);
            $filec = file_get_contents($file);
            $TL_dict = json_decode($filec, true);
            if ($TL_dict === null) {
                $TL_dict = [];
                $type = 'constructors';
                $tl_file = explode("\n", $filec);
                $key = 0;
                foreach ($tl_file as $line) {
                    $line = preg_replace(['|//.*|', '|^\s+$|'], '', $line);
                    if ($line === '') {
                        continue;
                    }
                    if ($line === '---functions---') {
                        $type = 'methods';
                        continue;
                    }
                    if ($line === '---types---') {
                        $type = 'constructors';
                        continue;
                    }
                    if (!preg_match('/^[^\s]+#/', $line)) {
                        continue;
                    }
                    if (preg_match('/^vector#/', $line)) {
                        continue;
                    }
                    $TL_dict[$type][$key][$type === 'constructors' ? 'predicate' : 'method'] = preg_replace('/#.*/', '', $line);
                    $TL_dict[$type][$key]['id'] = \danog\PHP\Struct::unpack('<i', \danog\PHP\Struct::pack('<I', hexdec(preg_replace(['/^[^#]+#/', '/\s.+/'], '', $line))))[0];
                    $TL_dict[$type][$key]['params'] = [];
                    $TL_dict[$type][$key]['type'] = preg_replace(['/.+\s/', '/;/'], '', $line);
                    foreach (explode(' ', preg_replace(['/^[^\s]+\s/', '/=\s[^\s]+/', '/\s$/'], '', $line)) as $param) {
                        if ($param === '') {
                            continue;
                        }
                        if ($param[0] === '{') {
                            continue;
                        }
                        $explode = explode(':', $param);
                        $TL_dict[$type][$key]['params'][] = ['name' => $explode[0], 'type' => $explode[1]];
                    }
                    $key++;
                }
            }
            if (empty($TL_dict) || empty($TL_dict['constructors']) || empty($TL_dict['methods'])) {
                throw new Exception('Invalid source file was provided: '.$file);
            }
            \danog\MadelineProto\Logger::log(['Translating objects...'], \danog\MadelineProto\Logger::ULTRA_VERBOSE);
            foreach ($TL_dict['constructors'] as $elem) {
                $this->constructors->add($elem, $scheme_type);
            }

            \danog\MadelineProto\Logger::log(['Translating methods...'], \danog\MadelineProto\Logger::ULTRA_VERBOSE);
            foreach ($TL_dict['methods'] as $elem) {
                $this->methods->add($elem);
            }
        }
    }

    public function get_method_namespaces()
    {
        return $this->methods->method_namespace;
    }

    public function get_named_method_args($method, $arguments)
    {
        $tl_method = $this->methods->find_by_method($method);
        if ($tl_method === false) {
            throw new Exception('Could not extract method: '.$method);
        }

        if (count(array_filter(array_keys($arguments), 'is_string')) === 0) {
            $argcount = 0;
            $newargs = [];
            foreach ($tl_method['params'] as $current_argument) {
                $newargs[$current_argument['name']] = $arguments[$argcount++];
            }
            $arguments = $newargs;
        }

        return $arguments;
    }

    public function serialize_bool($bool)
    {
        return \danog\PHP\Struct::pack('<i', $this->constructors->find_by_predicate('bool'.($bool ? 'True' : 'False'))['id']);
    }

    public function deserialize_bool($data)
    {
        $id = \danog\PHP\Struct::unpack('<i', $data)[0];
        $tl_elem = $this->constructors->find_by_id($id);
        if ($tl_elem === false) {
            throw new Exception('Could not extract boolean');
        }

        return $tl_elem['predicate'] === 'boolTrue';
    }

    public function serialize_object($type, $object)
    {
        switch ($type['type']) {
            case 'int':
                if (!is_numeric($object)) {
                    throw new Exception('given value ('.$object.") isn't numeric");
                }

                return \danog\PHP\Struct::pack('<i', $object);
            case '#':
                if (!is_numeric($object)) {
                    throw new Exception('given value ('.$object.") isn't numeric");
                }

                return \danog\PHP\Struct::pack('<I', $object);
            case 'long':
                if (!is_numeric($object)) {
                    throw new Exception('given value ('.$object.") isn't numeric");
                }

                return \danog\PHP\Struct::pack('<q', $object);
            case 'int128':
            case 'int256':
            case 'int512':
                return (string) $object;
            case 'double':
                return \danog\PHP\Struct::pack('<d', $object);
            case 'string':
            case 'bytes':
                $l = strlen($object);
                $concat = '';
                if ($l <= 253) {
                    $concat .= \danog\PHP\Struct::pack('<B', $l);
                    $concat .= $object;
                    $concat .= pack('@'.$this->posmod((-$l - 1), 4));
                } else {
                    $concat .= chr(254);
                    $concat .= substr(\danog\PHP\Struct::pack('<i', $l), 0, 3);
                    $concat .= $object;
                    $concat .= pack('@'.$this->posmod(-$l, 4));
                }

                return $concat;
            case 'Bool':
                return $this->serialize_bool((bool) $object);
            case 'true':
                return;
            case '!X':
                return $object;
            case 'Vector t':
                $concat = \danog\PHP\Struct::pack('<i', $this->constructors->find_by_predicate('vector')['id']);
                $concat .= \danog\PHP\Struct::pack('<i', count($object));
                foreach ($object as $current_object) {
                    $concat .= $this->serialize_object(['type' => $type['subtype']], $current_object);
                }

                return $concat;

        }
        $auto = false;

        if ((!is_array($object) || (isset($object['_']) && $this->constructors->find_by_predicate($object['_'])['type'] !== $type['type'])) && in_array($type['type'], ['User', 'InputUser', 'Chat', 'InputChannel', 'Peer', 'InputPeer'])) {
            $object = $this->get_info($object)[$type['type']];
        }
        if (!isset($object['_'])) {
            $constructorData = $this->constructors->find_by_predicate($type['type']);
            if ($constructorData === false) {
                throw new Exception('Predicate was not set!');
            }
            $auto = true;
            $object['_'] = $constructorData['predicate'];
        }
        $predicate = $object['_'];

        $constructorData = $this->constructors->find_by_predicate($predicate);
        if ($constructorData === false) {
            \danog\MadelineProto\Logger::log([$object], \danog\MadelineProto\Logger::FATAL_ERROR);
            throw new Exception('Could not extract type');
        }

        if ($bare = ($type['type'] != '' && $type['type'][0] === '%')) {
            $type['type'] = substr($type['type'], 1);
        }

        if ($predicate === $type['type'] && !$auto) {
            $bare = true;
        }

        $concat = '';
        if (!$bare) {
            $concat .= \danog\PHP\Struct::pack('<i', $constructorData['id']);
        }

        return $concat.$this->serialize_params($constructorData, $object);
    }

    public function serialize_method($method, $arguments)
    {
        $tl = $this->methods->find_by_method($method);
        if ($tl === false) {
            throw new Exception('Could not find method: '.$method);
        }

        return \danog\PHP\Struct::pack('<i', $tl['id']).$this->serialize_params($tl, $arguments);
    }

    public function html_entity_decode($stuff)
    {
        return html_entity_decode(str_replace('<br />', "\n", $stuff));
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
                $newd['from'] = $this->get_pwr_chat($this->datacenter->authorization['user']);
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
            $res['caption'] = $data['caption'];
            $res['photo'] = [];
            foreach ($data['photo']['sizes'] as $key => $photo) {
                $res['photo'][$key] = $this->photosize_to_botapi($photo, $data['photo']);
            }

            return $res;
            case 'messageMediaEmpty':
            return [];

            case 'messageMediaDocument':
            $type = 5;
            $type_name = 'document';
            $res = [];
            if ($data['document']['thumb']['_'] === 'photoSize') {
                $res['thumb'] = $this->photosize_to_botapi($data['document']['thumb'], $data['document'], true);
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
                        $type = 3;
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
                    $type = 4;
                    $type_name = 'video';
                    $res['width'] = $attribute['w'];
                    $res['height'] = $attribute['h'];
                    $res['duration'] = $attribute['duration'];
                    break;

                    case 'documentAttributeImageSize':
                    $res['width'] = $attribute['w'];
                    $res['height'] = $attribute['h'];
                    break;

                    case 'documentAttributeAnimated':
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
            $res['file_size'] = $data['document']['size'];
            $res['mime_type'] = $data['document']['mime_type'];
            $res['file_id'] = $this->base64url_encode($this->rle_encode(\danog\PHP\Struct::pack('<iiqqb', $type, $data['document']['dc_id'], $data['document']['id'], $data['document']['access_hash'], 2)));

            return [$type_name => $res, 'caption' => $data['caption']];
            default:
            throw new Exception("Can't convert ".$data['_'].' to a bot API object');
        }
    }

    public function botAPI_to_MTProto($arguments)
    {
        if (isset($arguments['disable_web_page_preview'])) {
            $arguments['no_webpage'] = $arguments['disable_web_page_preview'];
        }
        if (isset($arguments['disable_notification'])) {
            $arguments['silent'] = $arguments['disable_notification'];
        }
        if (isset($arguments['reply_to_message_id'])) {
            $arguments['reply_to_msg_id'] = $arguments['reply_to_message_id'];
        }
        if (isset($arguments['chat_id'])) {
            $arguments['peer'] = $arguments['chat_id'];
        }
        if (isset($arguments['text'])) {
            $arguments['message'] = $arguments['text'];
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
                            $entities[] = ['_' => 'inputMessageEntityMentionName', 'offset' => mb_strlen($nmessage), 'length' => mb_strlen($text), 'user_id' => $this->get_info(str_replace('mention:', '', $href))['InputUser']];
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
        if (preg_match('/markdown/i', $arguments['parse_mode'])) {
            $arguments['message'] = str_replace("\n", '', \Parsedown::instance()->line($arguments['message']));
            $arguments['parse_mode'] = 'HTML';
        }
        if (preg_match('/html/i', $arguments['parse_mode'])) {
            try {
                $dom = new \DOMDocument();
                $dom->loadHTML(str_replace("\n", '<br>', $arguments['message']));
                $nmessage = '';
                if (!isset($arguments['entities'])) {
                    $arguments['entities'] = [];
                }
                foreach ($dom->getElementsByTagName('body')->item(0)->childNodes as $node) {
                    $this->parse_node($node, $arguments['entities'], $nmessage);
                }
                unset($arguments['parse_mode']);
            } catch (\DOMException $e) {
            } catch (\danog\MadelineProto\Exception $e) {
            }
        }
        $arguments['message'] = $nmessage;

        return $arguments;
    }

    public function serialize_params($tl, $arguments)
    {
        $serialized = '';
        $flags = 0;
        foreach ($tl['params'] as $cur_flag) {
            if ($cur_flag['flag']) {
                switch ($cur_flag['type']) {
                    case 'true':
                    case 'false':
                        $flags = (isset($arguments[$cur_flag['name']]) && $arguments[$cur_flag['name']]) ? ($flags | $cur_flag['pow']) : ($flags & ~$cur_flag['pow']);
                        unset($arguments[$cur_flag['name']]);
                        break;
                    case 'Bool':
                        $arguments[$cur_flag['name']] = (isset($arguments[$cur_flag['name']]) && $arguments[$cur_flag['name']]) && (($flags & $cur_flag['pow']) != 0);
                        if (($flags & $cur_flag['pow']) === 0) {
                            unset($arguments[$cur_flag['name']]);
                        }
                        break;
                    default:
                        $flags = (isset($arguments[$cur_flag['name']]) && $arguments[$cur_flag['name']] !== null) ? ($flags | $cur_flag['pow']) : ($flags & ~$cur_flag['pow']);
                        break;
                }
            }
        }
        $arguments['flags'] = $flags;
        foreach ($tl['params'] as $current_argument) {
            if (!isset($arguments[$current_argument['name']])) {
                if ($current_argument['flag'] && (in_array($current_argument['type'], ['true', 'false']) || ($flags & $current_argument['pow']) === 0)) {
                    //\danog\MadelineProto\Logger::log(['Skipping '.$current_argument['name'].' of type '.$current_argument['type']);
                    continue;
                }
                if ($current_argument['name'] === 'random_id') {
                    switch ($current_argument['type']) {
                        case 'long':
                            $serialized .= $this->random(8);
                            continue 2;
                        case 'int':
                            $serialized .= $this->random(4);
                            continue 2;
                        case 'Vector t':
                            if (isset($arguments['id'])) {
                                $serialized .= \danog\PHP\Struct::pack('<i', $this->constructors->find_by_predicate('vector')['id']);
                                $serialized .= \danog\PHP\Struct::pack('<i', count($arguments['id']));
                                $serialized .= $this->random(8 * count($arguments['id']));
                                continue 2;
                            }
                    }
                }
                throw new Exception('Missing required parameter ('.$current_argument['name'].')');
            }
            //\danog\MadelineProto\Logger::log(['Serializing '.$current_argument['name'].' of type '.$current_argument['type']);
            $serialized .= $this->serialize_object($current_argument, $arguments[$current_argument['name']]);
        }

        return $serialized;
    }

    public function get_length($bytes_io, $type = ['type' => ''])
    {
        $this->deserialize($bytes_io, $type);

        return $bytes_io->pos;
    }

    /**
     * :type bytes_io: io.BytesIO object.
     */
    public function deserialize($bytes_io, $type = ['type' => ''])
    {
        if (is_string($bytes_io)) {
            $bytes_io = new \danog\MadelineProto\Stream($bytes_io);
        } elseif (!is_object($bytes_io)) {
            throw new Exception('An invalid bytes_io handle was provided.');
        }
        //\danog\MadelineProto\Logger::log(['Deserializing '.$type['type'].' at byte '.$bytes_io->pos);
        switch ($type['type']) {
            case 'Bool':
                return $this->deserialize_bool($bytes_io->read(4));
            case 'int':
                return \danog\PHP\Struct::unpack('<i', $bytes_io->read(4))[0];
            case '#':
                return \danog\PHP\Struct::unpack('<I', $bytes_io->read(4))[0];
            case 'long':
                return \danog\PHP\Struct::unpack('<q', $bytes_io->read(8))[0];
            case 'double':
                return \danog\PHP\Struct::unpack('<d', $bytes_io->read(8))[0];
            case 'int128':
                return $bytes_io->read(16);
            case 'int256':
                return $bytes_io->read(32);
            case 'int512':
                return $bytes_io->read(32);
            case 'string':
            case 'bytes':
                $l = \danog\PHP\Struct::unpack('<B', $bytes_io->read(1))[0];
                if ($l > 254) {
                    throw new Exception('Length is too big');
                }
                if ($l === 254) {
                    $long_len = \danog\PHP\Struct::unpack('<I', $bytes_io->read(3).chr(0))[0];
                    $x = $bytes_io->read($long_len);
                    $resto = $this->posmod(-$long_len, 4);
                    if ($resto > 0) {
                        $bytes_io->read($resto);
                    }
                } else {
                    $x = $bytes_io->read($l);
                    $resto = $this->posmod(-($l + 1), 4);
                    if ($resto > 0) {
                        $bytes_io->read($resto);
                    }
                }
                if (!is_string($x)) {
                    throw new Exception("deserialize: generated value isn't a string");
                }

                return $x;
            case 'true':
                return true;
            case 'Vector t':
                $id = \danog\PHP\Struct::unpack('<i', $bytes_io->read(4))[0];
                $constructorData = $this->constructors->find_by_id($id);
                if ($constructorData === false) {
                    throw new Exception('Could not extract type: '.$type['type'].' with id '.$id);
                }
                switch ($constructorData['predicate']) {
                    case 'gzip_packed':
                        return $this->deserialize(gzdecode($this->deserialize($bytes_io, ['type' => 'string'])));
                    case 'Vector t':
                    case 'vector':
                        break;
                    default:
                        throw new Exception('Invalid vector constructor: '.$constructorData['predicate']);
                }
            case 'vector':
                $count = \danog\PHP\Struct::unpack('<i', $bytes_io->read(4))[0];
                $result = [];
                for ($i = 0; $i < $count; $i++) {
                    $result[] = $this->deserialize($bytes_io, ['type' => $type['subtype']]);
                }

                return $result;
        }
        if ($type['type'] != '' && $type['type'][0] === '%') {
            $checkType = substr($type['type'], 1);
            $constructorData = $this->constructors->find_by_type($checkType);
            if ($constructorData === false) {
                throw new Exception('Constructor not found for type: '.$checkType);
            }
        } else {
            $constructorData = $this->constructors->find_by_predicate($type['type']);
            if ($constructorData === false) {
                $id = \danog\PHP\Struct::unpack('<i', $bytes_io->read(4))[0];
                $constructorData = $this->constructors->find_by_id($id);
                if ($constructorData === false) {
                    throw new Exception('Could not extract type: '.$type['type'].' with id '.$id);
                }
            }
        }
        if ($constructorData['predicate'] === 'gzip_packed') {
            return $this->deserialize(gzdecode($this->deserialize($bytes_io, ['type' => 'string'])));
        }
        $x = ['_' => $constructorData['predicate']];
        foreach ($constructorData['params'] as $arg) {
            if ($arg['flag']) {
                switch ($arg['type']) {
                    case 'true':
                    case 'false':
                        $x[$arg['name']] = ($x['flags'] & $arg['pow']) !== 0;
                        continue 2;
                        break;
                    case 'Bool':
                        if (($x['flags'] & $arg['pow']) === 0) {
                            $x[$arg['name']] = false;
                            continue 2;
                        }
                    default:
                        if (($x['flags'] & $arg['pow']) === 0) {
                            //$x[$arg['name']] = $default;
                            continue 2;
                        }
                        break;
                }
            }
            $x[$arg['name']] = $this->deserialize($bytes_io, $arg);
        }
        if (isset($x['flags'])) { // I don't think we need this anymore
            unset($x['flags']);
        }

        return $x;
    }

    public function content_related($method)
    {
        return !in_array(
            $method,
            [
                'rpc_result',
                'rpc_error',
                'rpc_drop_answer',
                'rpc_answer_unknown',
                'rpc_answer_dropped_running',
                'rpc_answer_dropped',
                'get_future_salts',
                'future_salt',
                'future_salts',
                'ping',
                'pong',
                'ping_delay_disconnect',
                'destroy_session',
                'destroy_session_ok',
                'destroy_session_none',
                'new_session_created',
                'msg_container',
                'msg_copy',
                'gzip_packed',
                'http_wait',
                'msgs_ack',
                'bad_msg_notification',
                'bad_server_salt',
                'msgs_state_req',
                'msgs_state_info',
                'msgs_all_info',
                'msg_detailed_info',
                'msg_new_detailed_info',
                'msg_resend_req',
                'msg_resend_ans_req',
            ]
        );
    }
}

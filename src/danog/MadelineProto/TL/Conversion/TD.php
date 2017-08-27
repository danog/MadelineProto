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

trait TD
{
    public function tdcli_to_td(&$params, $key = null)
    {
        if (!is_array($params)) {
            return $params;
        }
        if (!isset($params['ID'])) {
            array_walk($params, [$this, 'tdcli_to_td']);

            return $params;
        }
        foreach ($params as $key => $value) {
            $value = $this->tdcli_to_td($value);
            if (preg_match('/_$/', $key)) {
                $params[preg_replace('/_$/', '', $key)] = $value;
                unset($params[$key]);
            }
        }
        $params['_'] = lcfirst($params['ID']);
        unset($params['ID']);

        return $params;
    }

    public function td_to_mtproto($params)
    {
        $newparams = ['_' => self::REVERSE[$params['_']]];

        foreach (self::TD_PARAMS_CONVERSION[$newparams['_']] as $td => $mtproto) {
            if (is_array($mtproto)) {
                switch (end($mtproto)) {
                    case 'choose_message_content':
                    switch ($params[$td]['_']) {
                        case 'inputMessageText':
                        $params[$td]['_'] = 'messages.sendMessage';
                        if (isset($params['disable_web_page_preview'])) {
                            $newparams['no_webpage'] = $params[$td]['disable_web_page_preview'];
                        }
                        $newparams = array_merge($params[$td], $newparams);
                        break;
                        default: throw new Exception("Can't convert non text messages yet!");
                    }
                    break;
                    default:
                    $newparams[$mtproto[0]] = isset($params[$td]) ? $params[$td] : null;
                    if (is_array($newparams[$mtproto[0]])) {
                        $newparams[$mtproto[0]] = $this->mtproto_to_td($newparams[$mtproto[0]]);
                    }
                }
            }
        }

        return $newparams;
    }

    public function mtproto_to_tdcli($params)
    {
        return $this->td_to_tdcli($this->mtproto_to_td($params));
    }

    public function mtproto_to_td(&$params)
    {
        if (!is_array($params)) {
            return $params;
        }
        if (!isset($params['_'])) {
            array_walk($params, [$this, 'mtproto_to_td']);

            return $params;
        }
        $newparams = ['_' => $params['_']];
        if (in_array($params['_'], self::TD_IGNORE)) {
            return $params;
        }
        foreach (self::TD_PARAMS_CONVERSION[$params['_']] as $td => $mtproto) {
            if (is_string($mtproto)) {
                $newparams[$td] = $mtproto;
            } else {
                switch (end($mtproto)) {
                    case 'choose_chat_id_from_botapi':
                    $newparams[$td] = ($this->get_info($params[$mtproto[0]])['bot_api_id'] == $this->authorization['user']['id']) ? $params['from_id'] : $this->get_info($params[$mtproto[0]])['bot_api_id'];
                    break;
                    case 'choose_incoming_or_sent':
                    $newparams[$td] = ['_' => $params['out'] ? 'messageIsSuccessfullySent' : 'messageIsIncoming'];
                    break;
                    case 'choose_can_edit':
                    $newparams[$td] = !isset($params['fwd_from']) && $params['out'];
                    break;
                    case 'choose_can_delete':
                    $newparams[$td] = $params['out'];
                    break;
                    case 'choose_forward_info':
                    if (isset($params['fwd_from'])) {
                        $newparams[$td] = ['_' => 'messageForwardedFromUser'];
                        if (isset($params['fwd_from']['channel_id'])) {
                            $newparams[$td] = ['_'=> 'messageForwardedPost', 'chat_id' => '-100'.$params['fwd_from']['channel_id']];
                        }
                        $newparams[$td]['date'] = $params['fwd_from']['date'];
                        if (isset($params['fwd_from']['channel_post'])) {
                            $newparams[$td]['channel_post'] = $params['fwd_from']['channel_post'];
                        }
                        if (isset($params['fwd_from']['from_id'])) {
                            $newparams[$td]['sender_user_id'] = $params['fwd_from']['from_id'];
                        }
                    } else {
                        $newparams[$td] = null;
                    }
                    break;
                    case 'choose_ttl':
                    $newparams[$td] = isset($params['ttl']) ? $params['ttl'] : 0;
                    break;
                    case 'choose_ttl_expires_in':
                    $newparams[$td] = $newparams['ttl'] - microtime(true);
                    break;
                    case 'choose_message_content':
                    if ($params['message'] !== '') {
                        $newparams[$td] = ['_' => 'messageText', 'text' => $params['message']];
                        if (isset($params['media']['_']) && $params['media']['_'] === 'messageMediaWebPage') {
                            $newparams[$td]['web_page'] = $this->mtproto_to_td($params['media']['webpage']);
                        }
                        if (isset($params['entities'])) {
                            $newparams[$td]['entities'] = $params['entities'];
                        }
                    } else {
                        throw new Exception("Can't convert non text messages yet!");
                    }
                    break;
                    default:
                    if (isset($mtproto[1])) {
                        $newparams[$td] = isset($params[$mtproto[0]][$mtproto[1]]) ? $params[$mtproto[0]][$mtproto[1]] : null;
                    } else {
                        $newparams[$td] = isset($params[$mtproto[0]]) ? $params[$mtproto[0]] : null;
                    }
                    if (is_array($newparams[$td])) {
                        $newparams[$td] = $this->mtproto_to_td($newparams[$td]);
                    }
                }
            }
        }

        return $newparams;
    }

    public function td_to_tdcli($params)
    {
        if (!is_array($params)) {
            return $params;
        }
        $newparams = [];
        foreach ($params as $key => $value) {
            if ($key === '_') {
                $newparams['ID'] = ucfirst($value);
            } else {
                if (!is_numeric($key) && !preg_match('/_^/', $key)) {
                    $key = $key.'_';
                }
                $newparams[$key] = $this->td_to_tdcli($value);
            }
        }

        return $newparams;
    }
}

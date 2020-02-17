<?php

/**
 * TD module.
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

trait TD
{
    /**
     * Convert tdcli parameters to tdcli.
     *
     * @param array $params Params
     * @param array $key    Key
     *
     * @return array
     */
    public function tdcliToTd(&$params, $key = null): array
    {
        if (!\is_array($params)) {
            return $params;
        }
        if (!isset($params['ID'])) {
            \array_walk($params, [$this, 'tdcliToTd']);
            return $params;
        }
        foreach ($params as $key => $value) {
            $value = $this->tdcliToTd($value);
            if (\preg_match('/_$/', $key)) {
                $params[\preg_replace('/_$/', '', $key)] = $value;
                unset($params[$key]);
            }
        }
        $params['_'] = \lcfirst($params['ID']);
        unset($params['ID']);
        return $params;
    }
    /**
     * Convert TD to MTProto parameters.
     *
     * @param array $params Parameters
     *
     * @return \Generator<array>
     */
    public function tdToMTProto(array $params): \Generator
    {
        $newparams = ['_' => self::REVERSE[$params['_']]];
        foreach (self::TD_PARAMS_CONVERSION[$newparams['_']] as $td => $mtproto) {
            if (\is_array($mtproto)) {
                switch (\end($mtproto)) {
                    case 'choose_message_content':
                        switch ($params[$td]['_']) {
                            case 'inputMessageText':
                                $params[$td]['_'] = 'messages.sendMessage';
                                if (isset($params['disable_web_page_preview'])) {
                                    $newparams['no_webpage'] = $params[$td]['disable_web_page_preview'];
                                }
                                $newparams = \array_merge($params[$td], $newparams);
                                break;
                            default:
                                throw new Exception(\danog\MadelineProto\Lang::$current_lang['non_text_conversion']);
                        }
                        break;
                    default:
                        $newparams[$mtproto[0]] = isset($params[$td]) ? $params[$td] : null;
                        if (\is_array($newparams[$mtproto[0]])) {
                            $newparams[$mtproto[0]] = (yield from $this->MTProtoToTd($newparams[$mtproto[0]]));
                        }
                }
            }
        }
        return $newparams;
    }
    /**
     * MTProto to TDCLI params.
     *
     * @param mixed $params Params
     *
     * @return \Generator
     */
    public function MTProtoToTdcli($params): \Generator
    {
        return $this->tdToTdcli(yield from $this->MTProtoToTd($params));
    }
    /**
     * MTProto to TD params.
     *
     * @param mixed $params Params
     *
     * @return \Generator
     */
    public function MTProtoToTd(&$params): \Generator
    {
        if (!\is_array($params)) {
            return $params;
        }
        if (!isset($params['_'])) {
            \array_walk($params, [$this, 'mtprotoToTd']);
            return $params;
        }
        $newparams = ['_' => $params['_']];
        if (\in_array($params['_'], self::TD_IGNORE)) {
            return $params;
        }
        foreach (self::TD_PARAMS_CONVERSION[$params['_']] as $td => $mtproto) {
            if (\is_string($mtproto)) {
                $newparams[$td] = $mtproto;
            } else {
                switch (\end($mtproto)) {
                    case 'choose_chat_id_from_botapi':
                        $newparams[$td] = (yield from $this->getInfo($params[$mtproto[0]]))['bot_api_id'] == $this->authorization['user']['id'] ? $params['from_id'] : (yield from $this->getInfo($params[$mtproto[0]])['bot_api_id']);
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
                                $newparams[$td] = ['_' => 'messageForwardedPost', 'chat_id' => '-100' . $params['fwd_from']['channel_id']];
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
                        $newparams[$td] = $newparams['ttl'] - \microtime(true);
                        break;
                    case 'choose_message_content':
                        if ($params['message'] !== '') {
                            $newparams[$td] = ['_' => 'messageText', 'text' => $params['message']];
                            if (isset($params['media']['_']) && $params['media']['_'] === 'messageMediaWebPage') {
                                $newparams[$td]['web_page'] = (yield from $this->MTProtoToTd($params['media']['webpage']));
                            }
                            if (isset($params['entities'])) {
                                $newparams[$td]['entities'] = $params['entities'];
                            }
                        } else {
                            throw new Exception(\danog\MadelineProto\Lang::$current_lang['non_text_conversion']);
                        }
                        break;
                    default:
                        if (isset($mtproto[1])) {
                            $newparams[$td] = isset($params[$mtproto[0]][$mtproto[1]]) ? $params[$mtproto[0]][$mtproto[1]] : null;
                        } else {
                            $newparams[$td] = isset($params[$mtproto[0]]) ? $params[$mtproto[0]] : null;
                        }
                        if (\is_array($newparams[$td])) {
                            $newparams[$td] = (yield from $this->MTProtoToTd($newparams[$td]));
                        }
                }
            }
        }
        return $newparams;
    }
    /**
     * Convert TD parameters to tdcli.
     *
     * @param mixed $params Parameters
     *
     * @return mixed
     */
    public function tdToTdcli($params)
    {
        if (!\is_array($params)) {
            return $params;
        }
        $newparams = [];
        foreach ($params as $key => $value) {
            if ($key === '_') {
                $newparams['ID'] = \ucfirst($value);
            } else {
                if (!\is_numeric($key) && !\preg_match('/_^/', $key)) {
                    $key = $key . '_';
                }
                $newparams[$key] = $this->tdToTdcli($value);
            }
        }
        return $newparams;
    }
}

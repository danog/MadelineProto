<?php

declare(strict_types=1);

/**
 * Universal MTProto pagination helper.
 *
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2025 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\MTProtoTools;

/**
 * @internal
 */
final class Pagination
{
    private const ITEM_FIELDS = [
        'messages',
        'participants',
        'photos',
        'stories',
        'dialogs',
        'results',
        'users',
        'chats',
        'documents',
        'reactions',
        'saved_reaction_tags',
        'wallpapers',
        'authorizations',
    ];

    private function __construct()
    {
    }

    public static function apply(string $method, array &$args, ?array $methodInfo = null): ?array
    {
        if (!\array_key_exists('mp_offset', $args)) {
            return null;
        }
        $offset = $args['mp_offset'];
        unset($args['mp_offset']);
        if ($offset === null || $offset === '' || $offset === false) {
            return ['method' => $method, 'args' => $args, 'params' => self::params($methodInfo)];
        }
        if (\is_string($offset)) {
            $offset = self::decode($offset);
        }
        if (!\is_array($offset)) {
            throw new \InvalidArgumentException('mp_offset must be an offset token or an array.');
        }
        foreach (($offset['args'] ?? $offset) as $key => $value) {
            if (!\is_string($key)) {
                continue;
            }
            $args[$key] = $value;
        }
        return ['method' => $method, 'args' => $args, 'params' => self::params($methodInfo)];
    }

    public static function finalize(?array $state, mixed $result): mixed
    {
        if ($state === null || !\is_array($result)) {
            return $result;
        }
        $next = self::next($state['method'], $state['args'], $state['params'], $result);
        $result['mp_offset'] = $next === null ? null : self::encode(['args' => $next]);
        return $result;
    }

    public static function encode(array $offset): string
    {
        return rtrim(strtr(base64_encode(json_encode($offset, JSON_THROW_ON_ERROR)), '+/', '-_'), '=');
    }

    public static function decode(string $offset): array
    {
        $offset = strtr($offset, '-_', '+/');
        $offset .= str_repeat('=', (4 - \strlen($offset) % 4) % 4);
        $decoded = json_decode(base64_decode($offset, true) ?: '', true, flags: JSON_THROW_ON_ERROR);
        if (!\is_array($decoded)) {
            throw new \InvalidArgumentException('Invalid mp_offset token.');
        }
        return $decoded;
    }

    private static function next(string $method, array $args, array $params, array $result): ?array
    {
        if (($result['_'] ?? '') === 'messages.dialogsNotModified'
            || str_ends_with((string) ($result['_'] ?? ''), 'NotModified')
        ) {
            return null;
        }
        if (isset($result['next_offset']) && \is_string($result['next_offset']) && $result['next_offset'] !== '') {
            return ['offset' => $result['next_offset']];
        }

        if ($method === 'messages.getDialogs') {
            return self::nextDialogs($args, $result);
        }

        $items = self::items($result);
        if (!$items) {
            return null;
        }

        if (isset($params['offset'])) {
            return ['offset' => (int) ($args['offset'] ?? 0) + \count($items)];
        }

        $last = end($items);
        if (!\is_array($last)) {
            return null;
        }

        if (isset($params['offset_id']) && isset($last['id'])) {
            return ['offset_id' => $last['id']];
        }
        if (isset($params['max_id']) && isset($last['id'])) {
            return ['max_id' => $last['id']];
        }
        if (isset($params['offset_date']) && isset($last['date'])) {
            return ['offset_date' => $last['date']];
        }

        return null;
    }

    private static function nextDialogs(array $args, array $result): ?array
    {
        $dialogs = $result['dialogs'] ?? [];
        if (!$dialogs) {
            return null;
        }
        $messages = [];
        foreach ($result['messages'] ?? [] as $message) {
            if (\is_array($message) && isset($message['id'])) {
                $messages[$message['id']] = $message;
            }
        }
        $last = end($dialogs);
        if (!\is_array($last) || !isset($last['peer'], $last['top_message'])) {
            return null;
        }
        $top = $messages[$last['top_message']] ?? null;
        if (!\is_array($top) || !isset($top['date'])) {
            return null;
        }
        return [
            'offset_date' => $top['date'],
            'offset_id' => $last['top_message'],
            'offset_peer' => $last['peer'],
            'hash' => $args['hash'] ?? 0,
        ];
    }

    private static function items(array $result): array
    {
        foreach (self::ITEM_FIELDS as $field) {
            if (isset($result[$field]) && \is_array($result[$field]) && array_is_list($result[$field])) {
                return $result[$field];
            }
        }
        return [];
    }

    private static function params(?array $methodInfo): array
    {
        $params = [];
        foreach ($methodInfo['params'] ?? [] as $param) {
            if (isset($param['name'])) {
                $params[$param['name']] = true;
            }
        }
        return $params;
    }
}

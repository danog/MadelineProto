<?php

declare(strict_types=1);

/**
 * MCP resource provider.
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

namespace danog\MadelineProto\Mcp;

use danog\MadelineProto\API;

/**
 * @internal
 */
final class ResourceProvider
{
    /** @return list<array{uri: string, name: string, description: string, mimeType: string}> */
    public function listResources(): array
    {
        return [
            [
                'uri' => 'madeline://dialogs',
                'name' => 'Dialogs',
                'description' => 'Paginated list of dialogs for the current or selected session.',
                'mimeType' => 'application/json',
            ],
        ];
    }

    /** @return list<array{uriTemplate: string, name: string, description: string, mimeType: string}> */
    public function listTemplates(): array
    {
        return [
            [
                'uriTemplate' => 'madeline://messages{?session,peer,limit,mp_offset}',
                'name' => 'Messages',
                'description' => 'Paginated message history for a peer.',
                'mimeType' => 'application/json',
            ],
            [
                'uriTemplate' => 'madeline://peer{?session,id,mp_offset}',
                'name' => 'Peer info',
                'description' => 'Full cached or fetched peer information.',
                'mimeType' => 'application/json',
            ],
            [
                'uriTemplate' => 'madeline://profilePhotos{?session,id,limit,mp_offset}',
                'name' => 'Profile pictures',
                'description' => 'Paginated profile pictures for a user.',
                'mimeType' => 'application/json',
            ],
            [
                'uriTemplate' => 'madeline://dialogs{?session,limit,mp_offset}',
                'name' => 'Dialogs',
                'description' => 'Paginated list of dialogs.',
                'mimeType' => 'application/json',
            ],
            [
                'uriTemplate' => 'madeline://raw/{method}{?session,args,limit,mp_offset}',
                'name' => 'Raw MTProto method',
                'description' => 'Paginated raw MTProto method result; args is URL-safe base64 encoded JSON.',
                'mimeType' => 'application/json',
            ],
        ];
    }

    public function read(API $api, string $uri): array
    {
        $parts = parse_url($uri);
        if (($parts['scheme'] ?? null) !== 'madeline') {
            throw new \InvalidArgumentException("Unsupported resource URI $uri");
        }
        parse_str($parts['query'] ?? '', $query);
        $host = $parts['host'] ?? '';
        $path = trim($parts['path'] ?? '', '/');
        $limit = isset($query['limit']) ? (int) $query['limit'] : 100;
        $mpOffset = $query['mp_offset'] ?? null;

        $result = match ($host) {
            'messages' => $api->messages->getHistory(
                peer: $this->required($query, 'peer'),
                limit: $limit,
                mp_offset: $mpOffset,
            ),
            'peer' => $api->getFullInfo($this->required($query, 'id')),
            'profilePhotos' => $api->photos->getUserPhotos(
                user_id: $this->required($query, 'id'),
                limit: $limit,
                mp_offset: $mpOffset,
            ),
            'dialogs' => $api->messages->getDialogs(
                limit: $limit,
                mp_offset: $mpOffset,
            ),
            'raw' => $this->raw($api, $path, $query, $limit, $mpOffset),
            default => throw new \InvalidArgumentException("Unknown resource $host"),
        };
        if (\is_array($result) && !\array_key_exists('mp_offset', $result)) {
            $result['mp_offset'] = null;
        }

        return [
            'contents' => [
                [
                    'uri' => $uri,
                    'mimeType' => 'application/json',
                    'text' => json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                ],
            ],
        ];
    }

    /** @return list<string> */
    public function updateUris(array $update): array
    {
        $uris = [];
        $type = $update['_'] ?? '';
        if (str_contains($type, 'Message') || str_contains($type, 'Read') || str_contains($type, 'Delete')) {
            $uris[] = 'madeline://messages';
            $uris[] = 'madeline://dialogs';
        }
        if (str_contains($type, 'User') || str_contains($type, 'Channel') || str_contains($type, 'Chat') || str_contains($type, 'Peer')) {
            $uris[] = 'madeline://peer';
        }
        if (str_contains($type, 'Photo') || str_contains($type, 'UserPhoto')) {
            $uris[] = 'madeline://profilePhotos';
        }
        return array_values(array_unique($uris));
    }

    private function raw(API $api, string $method, array $query, int $limit, mixed $mpOffset): mixed
    {
        if ($method === '') {
            throw new \InvalidArgumentException('Raw resource method is required.');
        }
        $args = isset($query['args']) ? $this->decode($query['args']) : [];
        if (!\is_array($args)) {
            throw new \InvalidArgumentException('Raw resource args must decode to a JSON object.');
        }
        $args['limit'] ??= $limit;
        $args['mp_offset'] = $mpOffset;
        [$namespace, $name] = explode('.', $method, 2) + [null, null];
        if ($namespace === null || $name === null || $namespace === '' || $name === '') {
            throw new \InvalidArgumentException('Raw resource method must be namespaced, for example messages.getHistory.');
        }
        return $api->{$namespace}->{$name}(...$args);
    }

    private function decode(string $data): mixed
    {
        $data = strtr($data, '-_', '+/');
        $data .= str_repeat('=', (4 - \strlen($data) % 4) % 4);
        return json_decode(base64_decode($data, true) ?: '', true, flags: JSON_THROW_ON_ERROR);
    }

    private function required(array $query, string $key): string
    {
        if (!isset($query[$key]) || !\is_string($query[$key]) || $query[$key] === '') {
            throw new \InvalidArgumentException("$key is required.");
        }
        return $query[$key];
    }
}

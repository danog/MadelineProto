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

use danog\MadelineProto\EventHandler\Media\GeoPoint;
use danog\MadelineProto\MTProto;

/**
 * An incoming inline query.
 */
final class InlineQuery extends Update
{
    /** Query ID */
    public readonly int $queryId;

    /** Text of query */
    public readonly string $query;

    /** User that sent the query */
    public readonly int $userId;

    /** Offset to navigate through results */
    public readonly string $offset;

    /** Attached geolocation */
    public readonly ?GeoPoint $geo;

    /** Type of the chat from which the inline query was sent. */
    public readonly InlineQueryPeerType $peerType;

    /**
     * @readonly
     *
     * @var list<string> Regex matches, if a filter regex is present
     */
    public ?array $matches = null;
    /**
     * @readonly
     *
     * @var array<array-key, array<array-key, list{string, int}|null|string>|mixed> Regex matches, if a filter multiple match regex is present
     */
    public ?array $matchesAll = null;

    /** @internal */
    public function __construct(MTProto $API, array $rawInlineQuery)
    {
        parent::__construct($API);
        $this->queryId = $rawInlineQuery['query_id'];
        $this->query = $rawInlineQuery['query'];
        $this->userId = $rawInlineQuery['user_id'];
        $this->offset = $rawInlineQuery['offset'];
        $this->peerType = InlineQueryPeerType::from($rawInlineQuery['peer_type']['_']);
        $this->geo = isset($rawInlineQuery['geo'])
            ? new GeoPoint($rawInlineQuery['geo'])
            : null;
    }
}

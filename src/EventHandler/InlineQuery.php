<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler;

use danog\MadelineProto\EventHandler\Media\GeoPoint;
use danog\MadelineProto\MTProto;

/**
 * An incoming inline query
 */
final class InlineQuery extends Update
{
    /** @var int Query ID */
    public readonly int $queryId;
    /** @var string Text of query */
    public readonly string $query;
    /** @var int User that sent the query */
    public readonly int $userId;
    /** @var string Offset to navigate through results */
    public readonly string $offset;
    /** @var GeoPoint Attached geolocation */
    public readonly ?GeoPoint $geo;
    /** @var InlineQueryPeerType Type of the chat from which the inline query was sent. */
    public readonly InlineQueryPeerType $peerType;

    /**
     * @readonly
     *
     * @var list<string> Regex matches, if a filter regex is present
     */
    public ?array $matches = null;

    /** @internal */
    public function __construct(MTProto $API, array $rawInlineQuery)
    {
        parent::__construct($API);
        $this->queryId = $rawInlineQuery['query_id'];
        $this->query = $rawInlineQuery['query'];
        $this->userId = $rawInlineQuery['user_id'];
        $this->offset = $rawInlineQuery['offset'];
        $this->geo = isset($rawInlineQuery['geo']) ? new GeoPoint($rawInlineQuery['geo']) : null;
        $this->peerType = InlineQueryPeerType::fromString($rawInlineQuery['peerType']['_']);
    }
}
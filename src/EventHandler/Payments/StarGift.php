<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Payments;

use danog\MadelineProto\EventHandler\Media\StaticSticker;
use danog\MadelineProto\EventHandler\Media\Sticker;
use danog\MadelineProto\Ipc\IpcCapable;
use danog\MadelineProto\MTProto;

final class StarGift extends IpcCapable implements \JsonSerializable
{
    /** Show the gift is limited or not. */
    public readonly ?bool $limited;
    /** Show the gift is soldOut or not. */
    public readonly ?bool $soldOut;
    /** Gift id. */
    public readonly int $id;
    /** Gift sticker info. */
    public readonly ?Sticker $sticker;
    /** Amount of stars that need for buying this gift. */
    public readonly int $stars;
    /** Amount of gift that left. */
    public readonly ?int $availabilityRemains;
    /** Amount of total gift. */
    public readonly ?int $availabilityTotal;
    public readonly int $convertStars;
    /** Show timestamp for first buy of the gift */
    public readonly ?int $startSell;
    /** Show timestamp for last buy of the gift */
    public readonly ?int $endSell;

    public function __construct(MTProto $API, array $rawStarGift)
    {
        $this->limited = $rawStarGift['limited'] ?? null;
        $this->soldOut = $rawStarGift['sold_out'] ?? null;
        $this->id = $rawStarGift['id'];
        $this->sticker = isset($rawStarGift['sticker']) ?
            new StaticSticker(
                $API,
                $rawStarGift['sticker'],
                $rawStarGift['sticker']['attributes'],
                $rawStarGift['sticker']['attributes'],
                false
            ) : null;
        $this->stars = $rawStarGift['stars'];
        $this->availabilityRemains = $rawStarGift['availability_remains'] ?? null;
        $this->availabilityTotal = $rawStarGift['availability_total'] ?? null;
        $this->convertStars = $rawStarGift['convert_stars'];
        $this->startSell = $rawStarGift['first_sale_date'] ?? null;
        $this->endSell = $rawStarGift['last_sale_date'] ?? null;
        parent::__construct($API);
    }

    /** @internal */
    #[\Override]
    public function jsonSerialize(): mixed
    {
        $v = get_object_vars($this);
        unset($v['API'], $v['session']);
        $v['_'] = static::class;
        return $v;
    }
}

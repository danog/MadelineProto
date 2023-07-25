<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Media;

use danog\MadelineProto\MTProto;

/**
 * Represents a mask sticker.
 */
final class MaskSticker extends AbstractSticker
{
    /**
     * Part of the face, relative to which the mask should be placed.
     */
    public readonly MaskPosition $position;
    /**
     * Shift by X-axis measured in widths of the mask scaled to the face size, from left to right.
     *
     * For example, -1.0 will place the mask just to the left of the default mask position.
     */
    public readonly float $x;
    /**
     * Shift by Y-axis measured in widths of the mask scaled to the face size, from left to right.
     *
     * For example, -1.0 will place the mask just below the default mask position.
     */
    public readonly float $y;
    /**
     * Mask scaling coefficient.
     *
     * For example, 2.0 means a doubled size.
     */
    public readonly float $zoom;

    /** @internal */
    public function __construct(
        MTProto $API,
        array $rawMedia,
        array $stickerAttribute,
        array $photoAttribute,
        bool $protected,
    ) {
        parent::__construct($API, $rawMedia, $stickerAttribute, $photoAttribute['w'], $photoAttribute['h'], $protected);
        $coords = $stickerAttribute['mask_coords'];
        $this->position = match ($coords['n']) {
            0 => MaskPosition::Forehead,
            1 => MaskPosition::Eyes,
            2 => MaskPosition::Mouth,
            3 => MaskPosition::Chin
        };
        $this->x = $coords['x'];
        $this->y = $coords['y'];
        $this->zoom = $coords['zoom'];
    }
}

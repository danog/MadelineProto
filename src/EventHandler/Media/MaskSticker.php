<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Media;

/**
 * Represents a mask sticker.
 */
final class MaskSticker extends AbstractSticker
{
    /**
     * Part of the face, relative to which the mask should be placed.
     */
    public readonly MaskPosition $n;
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
}

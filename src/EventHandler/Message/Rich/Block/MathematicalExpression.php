<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Rich\Block;

use danog\MadelineProto\EventHandler\Message\Rich\Block;

/**
 * A mathematical expression, in LaTeX.
 */
final class MathematicalExpression extends Block
{
    public function __construct(
        /** The expression, in LaTeX */
        public readonly string $expression,
    ) {
    }
}

<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Rich\Text;

use danog\MadelineProto\EventHandler\Message\Rich\RichText;

/**
 * An inline mathematical expression, in LaTeX.
 */
final class MathematicalExpression extends RichText
{
    public function __construct(
        /** The expression, in LaTeX */
        public readonly string $expression,
    ) {
    }
}

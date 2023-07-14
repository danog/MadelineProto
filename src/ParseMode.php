<?php

declare(strict_types=1);

namespace danog\MadelineProto;

/**
 * Indicates a parsing mode for text.
 */
enum ParseMode: string
{
    case HTML = 'HTML';
    case MARKDOWN = 'Markdown';
    case TEXT = 'text';
}

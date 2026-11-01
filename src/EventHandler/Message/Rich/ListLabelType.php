<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Rich;

/**
 * Type of the labels of the items of an ordered list.
 */
enum ListLabelType: string
{
    /** Lowercase letters (a, b, c...) */
    case LOWERCASE_LETTERS = 'a';
    /** Uppercase letters (A, B, C...) */
    case UPPERCASE_LETTERS = 'A';
    /** Lowercase Roman numerals (i, ii, iii...) */
    case LOWERCASE_ROMAN = 'i';
    /** Uppercase Roman numerals (I, II, III...) */
    case UPPERCASE_ROMAN = 'I';
    /** Decimal numbers (1, 2, 3...) */
    case NUMBERS = '1';
}

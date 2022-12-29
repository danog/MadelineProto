<?php

namespace danog\MadelineProto\TL\Conversion;

/**
 * Entities module.
 *
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Mahdi <mahdi.talaee1379@gmail.com>
 * @copyright 2022 Mahdi <mahdi.talaee1379@gmail.com>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

final class Entities
{
    private int $offset = 0;

    /**
     * setOffset
     * setOffset for text.
     *
     *
     */
    private function setOffset(string $start, mixed $end = "</a>"): void
    {
        $this->setOffset[$this->offset][] = $start;
        $this->setOffset2[$this->offset + $this->length][] = $end;
    }

    /**
     * checkEntity
     * checkEntity in text and identify it.
     *
     *
     *
     */
    private function checkEntity(object|array $entity, &$type): array
    {
        if (\is_object($entity)) {
            $entity = (array) $entity;
        }
        if (\is_array($entity)) {
            if (!isset($entity["offset"])) {
                throw new Exception('Can\'t find field "offset"');
            }
            if (!isset($entity["length"])) {
                throw new Exception('Can\'t find field "length"');
            }
            if (!isset($entity["_"])) {
                throw new Exception('Can\'t find field "type"');
            }
            if (\is_array($entity["_"]) || \is_object($entity["_"])) {
                throw new Exception('Field "type" must be of type String');
            }
            if (
                \is_array($entity["offset"]) ||
                \is_object($entity["offset"]) ||
                (string) (int) $entity["offset"] !== (string) $entity["offset"]
            ) {
                throw new Exception('Field "offset" must be of type Integer');
            }
            if (
                \is_array($entity["length"]) ||
                \is_object($entity["length"]) ||
                (string) (int) $entity["length"] !== (string) $entity["length"]
            ) {
                throw new Exception('Field "length" must be of type Integer');
            }
            $this->offset = (int) $entity["offset"];
            $this->length = (int) $entity["length"];
            $type = $this->getEntityCode($entity["_"]);
            return $entity;
        }
        throw new Exception("expected an Object");
    }

    /**
     * entitiesToHtml
     * Covert entities to html tags.
     *
     *
     *
     *
     */
    public function entitiesToHtml(
        string $text,
        array|object $entities = [],
        bool $specialchars = true
    ): string {
        $this->setOffset = [];
        $this->setOffset2 = [];
        $utf16 = $this->encode($text);
        foreach ($entities as $entity) {
            $entity = $this->checkEntity($entity, $type);
            if ($type == CODE) {
                $this->setOffset("<code>", "</code>");
            } elseif ($type == TEXTURL && isset($entity["url"])) {
                $this->setOffset('<a href="' . $entity["url"] . '">');
            } elseif ($type == TEXTMENTION) {
                $this->setOffset(
                    '<a href="tg://user?id=' .
                        ($entity["user"]["id"] ?? ($entity["user_id"] ?? 0)) .
                        '">'
                );
            } elseif ($type == PRE) {
                if (isset($entity["language"])) {
                    $this->setOffset(
                        '<pre><code class="language-' .
                            $entity["language"] .
                            '">',
                        "</code></pre>"
                    );
                } else {
                    $this->setOffset("<pre>", "</pre>");
                }
            } elseif ($type == BOLD) {
                $this->setOffset("<b>", "</b>");
            } elseif ($type == SPOILER) {
                $this->setOffset("<span class=\"tg-spoiler\">", "</span>");
            } elseif ($type == ITALIC) {
                $this->setOffset("<i>", "</i>");
            } elseif ($type == STRIKE) {
                $this->setOffset("<s>", "</s>");
            } elseif ($type == UNDERLINE) {
                $this->setOffset("<u>", "</u>");
            }
        }
        foreach ($this->setOffset2 as $key => $value) {
            if (!isset($this->setOffset[$key])) {
                $this->setOffset[$key] = [];
            }
            $this->setOffset[$key] = \array_merge(
                \array_reverse($this->setOffset2[$key]),
                $this->setOffset[$key]
            );
        }
        $htmlext = "";
        $deltag = [
            "&" . "\0" . "a" . "\0" . "m" . "\0" . "p" . "\0" . ";" . "\0" . "",
            "&" . "\0" . "l" . "\0" . "t" . "\0" . ";" . "\0" . "",
            "&" . "\0" . "g" . "\0" . "t" . "\0" . ";" . "\0" . "",
        ];
        $deltag2 = [
            '/&\\000/',
            '/\\<\\000/',
            '/\\>\\000/',
        ];
        for ($offset = 0; $offset < \strlen($utf16) / 2; $offset++) {
            $t = \substr($utf16, $offset * 2, 2);
            if (isset($this->setOffset[$offset])) {
                foreach ($this->setOffset[$offset] as $tt) {
                    $htmlext .= $this->encode($tt);
                }
                unset($this->setOffset[$offset]);
            }
            $htmlext .= $specialchars ? \preg_replace($deltag2, $deltag, $t) : $t;
        }
        foreach ($this->setOffset as $off) {
            foreach ($off as $tt) {
                $htmlext .= $this->encode($tt);
            }
        }
        return $this->decode($htmlext);
    }

    /**
     * entitiesToMarkdownV1
     * Covert entities to html tags v1 (Telegram version).
     *
     *
     *
     *
     */

    public function entitiesToMarkdownV1(
        string $text,
        object|array $entities = [],
        bool $slashmarkdown = true
    ): string {
        $this->setOffset = [];
        $this->setOffset2 = [];
        $utf16 = $this->encode($text);
        foreach ($entities as $entity) {
            $entity = $this->checkEntity($entity, $type);
            if ($type == CODE) {
                $this->setOffset("`", "`");
            } elseif ($type == TEXTURL && isset($entity["url"])) {
                $this->setOffset("[", "](" . $entity["url"] . ")");
            } elseif ($type == TEXTMENTION) {
                $this->setOffset(
                    "[",
                    "](tg://user?id=" .
                        ($entity["user"]["id"] ?? ($entity["user_id"] ?? 0)) .
                        ")"
                );
            } elseif ($type == PRE) {
                if (isset($entity["language"])) {
                    $this->setOffset("```" . $entity["language"] . "\n", "```");
                } else {
                    $this->setOffset("```", "```");
                }
            } elseif ($type == BOLD) {
                $this->setOffset("*", "*");
            } elseif ($type == ITALIC) {
                $this->setOffset("_", "_");
            }
        }
        foreach ($this->setOffset2 as $key => $value) {
            if (!isset($this->setOffset[$key])) {
                $this->setOffset[$key] = [];
            }
            $this->setOffset[$key] = \array_merge(
                \array_reverse($this->setOffset2[$key]),
                $this->setOffset[$key]
            );
        }
        $htmlext = "";
        $deltag = [
            "\\" . "\0" . "_" . "\0" . "",
            "\\" . "\0" . "*" . "\0" . "",
            "\\" . "\0" . "`" . "\0" . "",
            "\\" . "\0" . "[" . "\0" . "",
        ];
        $deltag2 = [
            '/_\\000/',
            '/\\*\\000/',
            '/`\\000/',
            '/\\[\\000/',
        ];
        for ($offset = 0; $offset < \strlen($utf16) / 2; $offset++) {
            $t = \substr($utf16, $offset * 2, 2);
            if (isset($this->setOffset[$offset])) {
                foreach ($this->setOffset[$offset] as $tt) {
                    $htmlext .= $this->encode($tt);
                }
                unset($this->setOffset[$offset]);
            }
            $htmlext .= $slashmarkdown ? \preg_replace($deltag2, $deltag, $t) : $t;
        }
        foreach ($this->setOffset as $off) {
            foreach ($off as $tt) {
                $htmlext .= $this->encode($tt);
            }
        }
        return $this->decode($htmlext);
    }

    /**
     * entitiesToMarkdown
     * convert given entities to markdown.
     *
     *
     *
     *
     */
    public function entitiesToMarkdown(
        string $text,
        object|array $entities = [],
        bool $slashmarkdown = true
    ): string {
        $this->setOffset = [];
        $this->setOffset2 = [];
        $utf16 = $this->encode($text);
        foreach ($entities as $entity) {
            $entity = $this->checkEntity($entity, $type);
            if ($type == CODE) {
                $this->setOffset("`", "`");
            } elseif ($type == TEXTURL && isset($entity["url"])) {
                $this->setOffset("[", "](" . $entity["url"] . ")");
            } elseif ($type == TEXTMENTION) {
                $this->setOffset(
                    "[",
                    "](tg://user?id=" .
                        ($entity["user"]["id"] ?? ($entity["user_id"] ?? 0)) .
                        ")"
                );
            } elseif ($type == PRE) {
                if (isset($entity["language"])) {
                    $this->setOffset("```" . $entity["language"] . "\n", "```");
                } else {
                    $this->setOffset("```", "```");
                }
            } elseif ($type == SPOILER) {
                $this->setOffset("||", "||");
            } elseif ($type == BOLD) {
                $this->setOffset("**", "**");
            } elseif ($type == ITALIC) {
                $this->setOffset("__", "__");
            } elseif ($type == STRIKE) {
                $this->setOffset("~~", "~~");
            } elseif ($type == UNDERLINE) {
                $this->setOffset("_", "_");
            }
        }
        foreach ($this->setOffset2 as $key => $value) {
            if (!isset($this->setOffset[$key])) {
                $this->setOffset[$key] = [];
            }
            $this->setOffset[$key] = \array_merge(
                \array_reverse($this->setOffset2[$key]),
                $this->setOffset[$key]
            );
        }
        $htmlext = "";
        $deltag = [
            "\\" . "\0" . "_" . "\0" . "",
            "\\" . "\0" . "*" . "\0" . "",
            "\\" . "\0" . "[" . "\0" . "",
            "\\" . "\0" . "]" . "\0" . "",
            "\\" . "\0" . "(" . "\0" . "",
            "\\" . "\0" . ")" . "\0" . "",
            "\\" . "\0" . "~" . "\0" . "",
            "\\" . "\0" . "" . "\0" . "",
            "\\" . "\0" . ">" . "\0" . "",
            "\\" . "\0" . "#" . "\0" . "",
            "\\" . "\0" . "+" . "\0" . "",
            "\\" . "\0" . "-" . "\0" . "",
            "\\" . "\0" . "=" . "\0" . "",
            "\\" . "\0" . "|" . "\0" . "",
            "\\" . "\0" . "{" . "\0" . "",
            "\\" . "\0" . "}" . "\0" . "",
            "\\" . "\0" . "." . "\0" . "",
            "\\" . "\0" . "!" . "\0" . "",
        ];

        $deltag2 = [
            '/_\\000/',
            '/\\*\\000/',
            '/\\[\\000/',
            '/\\]\\000/',
            '/\\(\\000/',
            '/\\)\\000/',
            '/~\\000/',
            '/`\\000/',
            '/\\>\\000/',
            '/\\#\\000/',
            '/\\+\\000/',
            '/\\-\\000/',
            '/\\=\\000/',
            '/\\|\\000/',
            '/\\{\\000/',
            '/\\}\\000/',
            '/\\.\\000/',
            '/\\!\\000/',
        ];
        for ($offset = 0; $offset < \strlen($utf16) / 2; $offset++) {
            $t = \substr($utf16, $offset * 2, 2);
            if (isset($this->setOffset[$offset])) {
                foreach ($this->setOffset[$offset] as $tt) {
                    $htmlext .= $this->encode($tt);
                }
                unset($this->setOffset[$offset]);
            }
            $htmlext .= $slashmarkdown ? \preg_replace($deltag2, $deltag, $t) : $t;
        }
        foreach ($this->setOffset as $off) {
            foreach ($off as $tt) {
                $htmlext .= $this->encode($tt);
            }
        }
        return $this->decode($htmlext);
    }

    /**
     * markdownV1ToHtml
     * convert markdownv1 to html.
     */
    public function markdownV1ToHtml(string $str): string
    {
        $str = \str_replace(["&", "<", ">"], ["&amp;", "&lt;", "&gt;"], $str);
        $len = \mb_strlen($str);
        $backslash = ["_", "*", "`", "["];
        $marks = [];
        $marksi = -1;

        $i = 0;
        $is = function ($string) use (&$i, &$str) {
            return \mb_substr($str, $i, \mb_strlen($string)) == $string;
        };
        $find = function ($str, $find, &$i) use ($backslash) {
            $findlen = \mb_strlen($find);
            $newstr = "";
            for ($i = 0; $i < \mb_strlen($str); $i++) {
                $curchar = \mb_substr($str, $i, 1);

                if (
                    $curchar == "\\" &&
                    \in_array(\mb_substr($str, $i + 1, 1), $backslash)
                ) {
                    $newstr .= \mb_substr($str, $i + 1, 1);
                    $i++;
                } elseif (\mb_substr($str, $i, $findlen) == $find) {
                    return $newstr;
                } else {
                    $newstr .= $curchar;
                }
            }
            return false;
        };
        $html = "";
        $htmli = 0;
        $setstr = function ($starttag) use (&$html, &$htmli): void {
            $html .= $starttag;
            $htmli += \mb_strlen($starttag);
        };
        $i = 0;
        $setmark = function ($mark, &$currentmarki = 0, $fakemark = false) use (
            &$marks,
            &$marksi,
            &$htmli,
            &$i
        ) {
            if ($marksi === -1 || $marks[$marksi][0] !== $mark) {
                $marksi++;
                $marks[$marksi] = [
                    $fakemark == false ? $mark : $fakemark,
                    $htmli,
                    $i,
                ];
                return true;
            }
            $currentmarki = $marks[$marksi][1];
            unset($marks[$marksi]);
            $marksi--;
            return false;
        };
        $currentmarki = 0;
        $setstr2 = function ($endtag, $starttaglen) use (
            &$setstr,
            &$html,
            &$htmli,
            &$currentmarki
        ): void {
            if ($htmli - $currentmarki > $starttaglen) {
                $setstr($endtag);
            } else {
                $htmli -= $starttaglen;
                $html = \mb_substr($html, 0, $htmli);
            }
        };
        for ($i = 0; $i < $len; $i++) {
            $curchar = \mb_substr($str, $i, 1);
            if (
                $curchar == "\\" &&
                \in_array(\mb_substr($str, $i + 1, 1), $backslash)
            ) {
                $setstr(\mb_substr($str, $i + 1, 1));
                $i++;
            } elseif ($curchar == "*") {
                if ($setmark("*", $currentmarki)) {
                    $setstr("<b>");
                } else {
                    $setstr2("</b>", 3);
                }
            } elseif ($curchar == "_") {
                if ($setmark($curchar, $currentmarki)) {
                    $setstr("<i>");
                } else {
                    $setstr2("</i>", 3);
                }
            } elseif ($curchar == "[") {
                $setmark("[", $currentmarki, "]");
            } elseif ($curchar == "]") {
                if (!$setmark("]", $currentmarki, false) && $is("](")) {
                    $txt = \mb_substr(
                        $html,
                        $currentmarki,
                        $htmli - $currentmarki
                    );
                    if ($txt !== "") {
                        $i++;
                        $strfind = $find(\mb_substr($str, $i + 1), ")", $pos);
                        if ($strfind !== false) {
                            $i += $pos + 1;
                            $html =
                                \mb_substr($html, 0, $currentmarki) .
                                '<a href="' .
                                $strfind .
                                '">' .
                                $txt .
                                "</a>";
                            $htmli = \mb_strlen($html);
                        }
                    }
                }
            } elseif ($curchar == "`") {
                if ($is("```")) {
                    $i += 2;
                    $strfind = $find(\mb_substr($str, $i + 1), "```", $pos);
                    if ($strfind !== false) {
                        $i += $pos + 3;
                        if ($strfind !== "") {
                            $lang = "";
                            $ex = \explode("\n", $f, 2);
                            if (isset($ex[1])) {
                                $exx = \explode(" ", $ex[0], 2);
                                if (isset($exx[1])) {
                                    $ex[1] = " " . $exx[1];
                                }
                                $lang = \trim($exx[0]);
                                $strfind = $ex[1];
                            }

                            if ($lang) {
                                $strfind = \trim($f);
                                if ($strfind !== "") {
                                    $setstr(
                                        '<pre><code class="language-' .
                                            $lang .
                                            '">' .
                                            $strfind .
                                            "</code></pre>"
                                    );
                                }
                            } else {
                                $setstr("<pre>" . $strfind . "</pre>");
                            }
                        }
                    } else {
                        throw new Exception(
                            'Can\'t find end of Pre entity at byte offset ' . $i
                        );
                    }
                } else {
                    $strfind = $find(\mb_substr($str, $i + 1), "`", $pos);
                    if ($strfind !== false) {
                        if ($strfind !== "") {
                            $setstr("<code>" . $strfind . "</code>");
                        }
                        $i += $pos + 1;
                    } else {
                        throw new Exception(
                            'Can\'t find end of Code entity at byte offset ' .
                                $i
                        );
                    }
                }
            } elseif (\in_array($curchar, $backslash)) {
                throw new Exception(
                    "Character '$curchar' is reserved and must be escaped with the preceding '\'"
                );
            } else {
                $setstr($curchar);
            }
        }
        foreach ($marks as $mark) {
            $ar = [
                "*" => "Bold",
                "_" => "Italic",
                "]" => "TextUrl",
            ];
            throw new Exception(
                'Can\'t find end of ' .
                    ($ar[$mark[0]] ?? $mark[0]) .
                    " entity at byte offset " .
                    $mark[2]
            );
        }
        return $html;
    }

    /**
     * markdownToHtml
     * convert html tags to markdown format.
     */
    public function markdownToHtml(string $str): string
    {
        $str = \str_replace(["&", "<", ">"], ["&amp;", "&lt;", "&gt;"], $str);
        $len = \mb_strlen($str);
        $backslash = [
            "_",
            "*",
            "[",
            "]",
            "(",
            ")",
            "~",
            "`",
            ">",
            "#",
            "+",
            "-",
            "=",
            "|",
            "{",
            "}",
            ".",
            "!",
        ];
        $marks = [];
        $marksi = -1;

        $i = 0;
        $is = function ($string) use (&$i, &$str) {
            return \mb_substr($str, $i, \mb_strlen($string)) == $string;
        };
        $find = function ($str, $find, &$i) use ($backslash) {
            $findlen = \mb_strlen($find);
            $newstr = "";
            for ($i = 0; $i < \mb_strlen($str); $i++) {
                $curchar = \mb_substr($str, $i, 1);

                if (
                    $curchar == "\\" &&
                    \in_array(\mb_substr($str, $i + 1, 1), $backslash)
                ) {
                    $newstr .= \mb_substr($str, $i + 1, 1);
                    $i++;
                } elseif (\mb_substr($str, $i, $findlen) == $find) {
                    return $newstr;
                } else {
                    $newstr .= $curchar;
                }
            }
            return false;
        };
        $html = "";
        $htmli = 0;
        $setstr = function ($starttag) use (&$html, &$htmli): void {
            $html .= $starttag;
            $htmli += \mb_strlen($starttag);
        };
        $i = 0;
        $setmark = function ($mark, &$currentmarki = 0, $fakemark = false) use (
            &$marks,
            &$marksi,
            &$htmli,
            &$i
        ) {
            if ($marksi === -1 || $marks[$marksi][0] !== $mark) {
                $marksi++;
                $marks[$marksi] = [
                    $fakemark == false ? $mark : $fakemark,
                    $htmli,
                    $i,
                ];
                return true;
            }
            $currentmarki = $marks[$marksi][1];
            unset($marks[$marksi]);
            $marksi--;
            return false;
        };
        $currentmarki = 0;
        $setstr2 = function ($endtag, $starttaglen) use (
            &$setstr,
            &$html,
            &$htmli,
            &$currentmarki
        ): void {
            if ($htmli - $currentmarki > $starttaglen) {
                $setstr($endtag);
            } else {
                $htmli -= $starttaglen;
                $html = \mb_substr($html, 0, $htmli);
            }
        };
        for ($i = 0; $i < $len; $i++) {
            $curchar = \mb_substr($str, $i, 1);
            if (
                $curchar == "\\" &&
                \in_array(\mb_substr($str, $i + 1, 1), $backslash)
            ) {
                $setstr(\mb_substr($str, $i + 1, 1));
                $i++;
            } elseif ($curchar == "*") {
                $tag = "i";
                if (
                    ($marksi === -1 || $marks[$marksi][0] !== $curchar) &&
                    $is("**")
                ) {
                    $curchar = "**";
                    $tag = "b";
                    $i++;
                }
                if ($setmark($curchar, $currentmarki)) {
                    $setstr("<$tag>");
                } else {
                    $setstr2("</$tag>", 3);
                }
            } elseif ($curchar == "_") {
                $tag = "u";
                if (
                    ($marksi === -1 || $marks[$marksi][0] !== $curchar) &&
                    $is("__")
                ) {
                    $curchar = "__";
                    $tag = "i";
                    $i++;
                }
                if ($setmark($curchar, $currentmarki)) {
                    $setstr("<$tag>");
                } else {
                    $setstr2("</$tag>", 3);
                }
            } elseif ($curchar == "~" && $is("~~")) {
                if ($setmark("~~", $currentmarki)) {
                    $setstr("<s>");
                } else {
                    $setstr2("</s>", 3);
                }
                $i++;
            } elseif ($curchar == "|" && $is("||")) {
                if ($setmark("||", $currentmarki)) {
                    $setstr('<span class="tg-spoiler">');
                } else {
                    $setstr2("</span>", 25);
                }
                $i++;
            } elseif ($curchar == "[") {
                $setmark("[", $currentmarki, "]");
            } elseif ($curchar == "]") {
                if (!$setmark("]", $currentmarki, false) && $is("](")) {
                    $txt = \mb_substr(
                        $html,
                        $currentmarki,
                        $htmli - $currentmarki
                    );
                    if ($txt !== "") {
                        $i++;
                        $strfind = $find(\mb_substr($str, $i + 1), ")", $pos);
                        if ($strfind !== false) {
                            $i += $pos + 1;
                            $html =
                                \mb_substr($html, 0, $currentmarki) .
                                '<a href="' .
                                $strfind .
                                '">' .
                                $txt .
                                "</a>";
                            $htmli = \mb_strlen($html);
                        }
                    }
                }
            } elseif ($curchar == "`") {
                if ($is("```")) {
                    $i += 2;
                    $strfind = $find(\mb_substr($str, $i + 1), "```", $pos);
                    if ($strfind !== false) {
                        $i += $pos + 3;
                        if ($strfind !== "") {
                            $lang = "";
                            $ex = \explode("\n", $f, 2);
                            if (isset($ex[1])) {
                                $exx = \explode(" ", $ex[0], 2);
                                if (isset($exx[1])) {
                                    $ex[1] = " " . $exx[1];
                                }
                                $lang = \trim($exx[0]);
                                $strfind = $ex[1];
                            }

                            if ($lang) {
                                $strfind = \trim($f);
                                if ($strfind !== "") {
                                    $setstr(
                                        '<pre><code class="language-' .
                                            $lang .
                                            '">' .
                                            $strfind .
                                            "</code></pre>"
                                    );
                                }
                            } else {
                                $setstr("<pre>" . $strfind . "</pre>");
                            }
                        }
                    } else {
                        throw new Exception(
                            'Can\'t find end of Pre entity at byte offset ' . $i
                        );
                    }
                } else {
                    $strfind = $find(\mb_substr($str, $i + 1), "`", $pos);
                    if ($strfind !== false) {
                        if ($strfind !== "") {
                            $setstr("<code>" . $strfind . "</code>");
                        }
                        $i += $pos + 1;
                    } else {
                        throw new Exception(
                            'Can\'t find end of Code entity at byte offset ' .
                                $i
                        );
                    }
                }
            } elseif (\in_array($curchar, $backslash)) {
                throw new Exception(
                    "Character '$curchar' is reserved and must be escaped with the preceding '\'"
                );
            } else {
                $setstr($curchar);
            }
        }
        foreach ($marks as $mark) {
            $ar = [
                "**" => "Bold",
                "__" => "Italic",
                "*" => "Italic",
                "_" => "Underline",
                "~~" => "Strikethrough",
                "]" => "TextUrl",
                "||" => "Spoiler",
            ];
            throw new Exception(
                'Can\'t find end of ' .
                    ($ar[$mark[0]] ?? $mark[0]) .
                    " entity at byte offset " .
                    $mark[2]
            );
        }
        return $html;
    }
    /**
     * htmlToEntities
     * convert html tags to entities.
     */
    public function htmlToEntities(string $text): DOMEntities
    {
        return new DOMEntities($text);
    }

    /**
     * markdownToEntities
     * convert markdown format to entities.
     */
    public function markdownToEntities(string $text): DOMEntities
    {
        return $this->htmlToEntities(
            $this->markdownToHtml($text)
        );
    }

    /**
     * markdownV1ToEntities
     * convert markdownV1 to entities.
     */
    public function markdownV1ToEntities(string $text): DOMEntities
    {
        return $this->htmlToEntities(
            $this->markdownV1ToHtml($text)
        );
    }

    /**
     * markdownhtmlToEntities
     * convert mixed format(with markdown and html) to entities.
     */
    public function markdownhtmlToEntities(string $text): DOMEntities
    {
        return $this->htmlToEntities(
            $this->markdownToHtml($text, false)
        );
    }

    /**
    * markdownV1htmlToEntities
    * convert mixed format(with markdownv1 and html) to entities.
    */
    public function markdownV1htmlToEntities(string $text): DOMEntities
    {
        return $this->htmlToEntities(
            $this->markdownV1ToHtml($text, false),
        );
    }

    /**
     * htmlToMarkdown
     * convert html tags to markdown format.
     */
    public function htmlToMarkdown(string $str, bool $slashmarkdown = true): string
    {
        $entities = $this->htmlToEntities($str);
        return $this->entitiesToMarkdown($entities->message, $entities->entities, $slashmarkdown);
    }

    /**
     * htmlToMarkdownv1
     * convert html tags to markdownv1 format.
     */
    public function htmlToMarkdownv1(string $str, bool $slashmarkdown = true): string
    {
        $entities = $this->htmlToEntities($str);
        return $this->entitiesToMarkdownV1($entities->message, $entities->entities, $slashmarkdown);
    }

    /**
     * parseText (main function)
     * function return formated text with entities or tags or format it to markdown & markdownv1.
     *
     * @param non-empty-string $text
     * @param "html"|"markdown"|"markdownv1"|"markdownv2"|"markdownhtml"|"markdownv2html"|"markdownv1html" $mode
     *
     */
    public function parseText(string $text, string $mode = "html"): DOMEntities
    {
        return match ($mode) {
            'html' => $this->htmlToEntities($text),
            'markdown', 'markdownv2' => $this->markdownToEntities($text),
            'markdownv1' => $this->markdownV1ToEntities($text),
            'markdownhtml', 'markdownv2html' => $this->markdownhtmlToEntities($text),
            'markdownv1html' => $this->markdownV1htmlToEntities($text),
            default => throw new Exception("unsupported mode")
        };
    }
}

<?php

namespace danog\MadelineProto\TL\Conversion;

use danog\MadelineProto\API;

const BOLD = 0;
const ITALIC = 1;
const UNDERLINE = 2;
const STRIKE = 3;
const SPOILER = 4;
const TEXTURL = 5;
const TEXTMENTION = 6;
const CODE = 7;
const PRE = 8;
const SPANTAG = 11;
const ATAG = 12;

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
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2020 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

trait Entities
{
    private $entities = null;
    private $entitiesid = 0;
    private $offset = 0;
    private $length = 0;
    private $text = "";

    /**
     * br2nl
     * Checks if provided $separator is valid.
     * 
     * @param string $string
     * 
     * @param mixed $separator
     * 
     * @return string|array|null
     */
    private function br2nl(
        string $string,
        mixed $separator = PHP_EOL
    ): string|array|null {
        $separator = in_array($separator, [
            "\n",
            "\r",
            "\r\n",
            "\n\r",
            chr(30),
            chr(155),
            PHP_EOL,
        ])
            ? $separator
            : PHP_EOL; // Checks if provided $separator is valid.
        return preg_replace("/\<br(\s*)?\/?\>/i", $separator, $string);
    }

    /** 
     * getEntityName
     * get entity name by it codes
     * 
     * @param int $code
     * 
     * @return string|bool
     */
    private function getEntityName(int $code): string|bool
    {
        switch ($code) {
            case BOLD:
                return "messageEntityBold";

            case ITALIC:
                return "messageEntityItalic";

            case UNDERLINE:
                return "messageEntityUnderline";

            case STRIKE:
                return "messageEntityStrike";

            case SPOILER:
                return "messageEntitySpoiler";

            case TEXTURL:
                return "messageEntityTextUrl";

            case TEXTMENTION:
                return "messageEntityMentionName";

            case CODE:
                return "messageEntityCode";

            case PRE:
                return "messageEntityPre";
        }
        return false;
    }

    /** 
     * getEntityCode
     * get entity name by it name
     * 
     * @param string $name
     * 
     * @return int|bool
     */
    private function getEntityCode(string $name): int|bool
    {
        switch ($name) {
            case $this->getEntityName(BOLD):
                return BOLD;

            case $this->getEntityName(ITALIC):
                return ITALIC;

            case $this->getEntityName(UNDERLINE):
                return UNDERLINE;

            case $this->getEntityName(STRIKE):
                return STRIKE;

            case $this->getEntityName(SPOILER):
                return SPOILER;

            case $this->getEntityName(TEXTURL):
                return TEXTURL;

            case $this->getEntityName(TEXTMENTION):
                return TEXTMENTION;

            case $this->getEntityName(CODE):
                return CODE;

            case $this->getEntityName(PRE):
                return PRE;
        }
        return false;
    }

    /** 
     * getEntityNameFromTag
     * get entity name by it tag
     * 
     * @param string $tag
     * 
     * @return int|bool
     */
    private function getEntityNameFromTag(string $tag): int|bool
    {
        switch ($tag) {
            case "b":
            case "strong":
            case "bold":
                return BOLD;

            case "i":
            case "em":
            case "italic":
                return ITALIC;

            case "ins":
            case "u":
            case "underline":
                return UNDERLINE;

            case "s":
            case "del":
            case "strike":
            case "strikethrough":
                return STRIKE;

            case "spoiler":
            case "tg-spoiler":
                return SPOILER;

            case "span":
                return SPANTAG;

            case "a":
                return ATAG;

            case "code":
                return CODE;

            case "pre":
                return PRE;
        }
        return false;
    }

    /**
     * setText
     * set text string and offset
     * 
     * @param string $text
     * 
     * @return void
     */
    private function setText(string $text)
    {
        $text = htmlspecialchars_decode($text);
        $l = $this->strlen($text);
        $this->text .= $text;
        $this->offset = $this->offset + $l;
    }

    /**
     * decode
     * decode text from UTF-8 to UTF-16LE to easily parse it tags
     * 
     * @param string $str
     * 
     * @return array|string|false
     */
    private function decode(string $str): array|string|false
    {
        return mb_convert_encoding($str, "UTF-8", "UTF-16LE");
    }

    /**
     * encode
     * 
     * encode parsed text from UTF-16LE to UTF-8 
     * @param string $str
     * 
     * @return array|string|false
     */
    private function encode(string $str): array|string|false
    {
        return mb_convert_encoding($str, "UTF-16LE", "UTF-8");
    }

    /**
     * strlen
     * 
     * @param string $str
     * 
     * @return int|float
     */
    private function strlen(string $str): int|float
    {
        return strlen($this->encode($str)) / 2;
    }

    /** 
     * substr
     * 
     * @param string $string
     * 
     * @param int $offset
     * 
     * @param null|int $length
     * 
     * @return array|string|false
     */
    private function substr(
        string $string,
        int $offset,
        ?int $length = null
    ): array|string|false {
        return $this->decode(substr($string, $offset * 2, $length * 2));
    }

    /**
     * setOffset
     * setOffset for text
     * 
     * @param string $start
     * 
     * @return void
     */
    private function setOffset(string $start, mixed $end = "</a>"): void
    {
        $this->setOffset[$this->offset][] = $start;
        $this->setOffset2[$this->offset + $this->length][] = $end;
    }

    /**
     * setEntitie
     * setEntitie for text and parse special tags to entities
     * 
     * @param int $type
     * 
     * @param array $array
     * 
     * @param string $intag
     * 
     * @param int &$i
     * 
     * @return array|null|bool
     */
    private function setEntitie(
        int $type,
        array $array = [],
        string $intag = "",
        &$i = 0
    ): array|null|bool {
        $result = ["_" => "", "offset" => $this->offset, "length" => 0];
        if ($type == ATAG) {
            if (isset($array["href"])) {
                if (
                    preg_match(
                        '/^(?:tg:\/\/user\?id=|mention:)(.*)$/isu',
                        $array["href"],
                        $matches
                    )
                ) {
                    $userId = $matches[1];

                    if (!is_numeric($userId)) {
                        try {
                            $userId ??= $this->api->getInfo($matches[1])['id'];
                        } catch (\Throwable $e) {
                        }
                    }

                    $result["_"] = $this->getEntityName(TEXTMENTION);
                    $result["user_id"] = $userId;
                } else {
                    $result["_"] = $this->getEntityName(TEXTURL);
                    $result["url"] = $array["href"];
                }
            } else {
                return null;
            }
        } elseif ($type == CODE) {
            $result["_"] = $this->getEntityName(CODE);
            if (
                $intag === "pre" &&
                ((isset($array["class"]) &&
                    preg_match(
                        '/^language\-(.*?)$/',
                        $array["class"],
                        $matches
                    )) ||
                    isset($array["language"]))
            ) {
                $result["_"] = $this->getEntityName(PRE);
                $this->entities[$this->entitiesid - 1]["language"] =
                    $matches[1];
                return null;
            }
            return $result;
        } elseif ($type == SPANTAG) {
            if (isset($array["class"])) {
                $array["class"] = strtolower($array["class"]);
                switch ($array["class"]) {
                    case "bold":
                        $result["_"] = $this->getEntityName(BOLD);
                        break;

                    case "italic":
                        $result["_"] = $this->getEntityName(ITALIC);
                        break;

                    case "underline":
                        $result["_"] = $this->getEntityName(UNDERLINE);
                        break;

                    case "strikethrough":
                    case "strike":
                        $result["_"] = $this->getEntityName(STRIKE);
                        break;

                    case "spoiler":
                    case "tg-spoiler":
                        $result["_"] = $this->getEntityName(SPOILER);
                        break;

                    case "code":
                        $result["_"] = $this->getEntityName(CODE);
                        break;

                    case "pre":
                        $result["_"] = $this->getEntityName(PRE);
                        break;

                    default:
                        return false;
                }
            } else {
                return null;
            }
        } else {
            $result["_"] = $this->getEntityName($type);
        }
        return $result;
    }

    /**
     * checkEntity
     * checkEntity in text and identify it
     * 
     * @param object|array $entity
     * 
     * @param mixed &$type
     * 
     * @return array
     */
    private function checkEntity(object|array $entity, &$type): array
    {
        if (is_object($entity)) {
            $entity = (array) $entity;
        }
        if (is_array($entity)) {
            if (!isset($entity["offset"])) {
                throw new Exception('Can\'t find field "offset"');
            }
            if (!isset($entity["length"])) {
                throw new Exception('Can\'t find field "length"');
            }
            if (!isset($entity["_"])) {
                throw new Exception('Can\'t find field "type"');
            }
            if (is_array($entity["_"]) || is_object($entity["_"])) {
                throw new Exception('Field "type" must be of type String');
            }
            if (
                is_array($entity["offset"]) ||
                is_object($entity["offset"]) ||
                (string) (int) $entity["offset"] !== (string) $entity["offset"]
            ) {
                throw new Exception('Field "offset" must be of type Integer');
            }
            if (
                is_array($entity["length"]) ||
                is_object($entity["length"]) ||
                (string) (int) $entity["length"] !== (string) $entity["length"]
            ) {
                throw new Exception('Field "length" must be of type Integer');
            }
            $this->offset = (int) $entity["offset"];
            $this->length = (int) $entity["length"];
            $type = $this->getEntityCode($entity["_"]);
            return $entity;
        } else {
            throw new Exception("expected an Object");
        }
    }

    /**
     * entitiesToHtml
     * Covert entities to html tags
     * 
     * @param string $text
     *
     * @param array|object $entities
     * 
     * @param bool $specialchars
     * 
     * @return string
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
            $this->setOffset[$key] = array_merge(
                array_reverse($this->setOffset2[$key]),
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
        for ($offset = 0; $offset < strlen($utf16) / 2; $offset++) {
            $t = substr($utf16, $offset * 2, 2);
            if (isset($this->setOffset[$offset])) {
                foreach ($this->setOffset[$offset] as $tt) {
                    $htmlext .= $this->encode($tt);
                }
                unset($this->setOffset[$offset]);
            }
            $htmlext .= $specialchars ? preg_replace($deltag2, $deltag, $t) : $t;
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
     * Covert entities to html tags v1 (Telegram version)
     * 
     * @param string $text
     * 
     * @param object|array $entities 
     * 
     * @param bool $slashmarkdown
     * 
     * @return string
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
            $this->setOffset[$key] = array_merge(
                array_reverse($this->setOffset2[$key]),
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
        for ($offset = 0; $offset < strlen($utf16) / 2; $offset++) {
            $t = substr($utf16, $offset * 2, 2);
            if (isset($this->setOffset[$offset])) {
                foreach ($this->setOffset[$offset] as $tt) {
                    $htmlext .= $this->encode($tt);
                }
                unset($this->setOffset[$offset]);
            }
            $htmlext .= $slashmarkdown ? preg_replace($deltag2, $deltag, $t) : $t;
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
     * convert given entities to markdown
     * 
     * @param string $text
     *
     * @param object|array $entities
     * 
     * @param bool $slashmarkdown
     * 
     * @return string
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
            $this->setOffset[$key] = array_merge(
                array_reverse($this->setOffset2[$key]),
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
        for ($offset = 0; $offset < strlen($utf16) / 2; $offset++) {
            $t = substr($utf16, $offset * 2, 2);
            if (isset($this->setOffset[$offset])) {
                foreach ($this->setOffset[$offset] as $tt) {
                    $htmlext .= $this->encode($tt);
                }
                unset($this->setOffset[$offset]);
            }
            $htmlext .= $slashmarkdown ? preg_replace($deltag2, $deltag, $t) : $t;
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
     * convert markdownv1 to html
     * 
     * @param string $str
     * 
     * @param bool $specialchars
     * 
     * @return string
     */
    public function markdownV1ToHtml(string $str, bool $specialchars = true): string
    {
        if ($specialchars) {
            $str = $this->htmlSpecialChars($str);
        }
        $len = mb_strlen($str);
        $backslash = ["_", "*", "`", "["];
        $marks = [];
        $marksi = -1;

        $i = 0;
        $is = function ($string) use (&$i, &$str) {
            return mb_substr($str, $i, mb_strlen($string)) == $string;
        };
        $find = function ($str, $find, &$i) use ($backslash) {
            $findlen = mb_strlen($find);
            $newstr = "";
            for ($i = 0; $i < mb_strlen($str); $i++) {
                $curchar = mb_substr($str, $i, 1);

                if (
                    $curchar == "\\" &&
                    in_array(mb_substr($str, $i + 1, 1), $backslash)
                ) {
                    $newstr .= mb_substr($str, $i + 1, 1);
                    $i++;
                } elseif (mb_substr($str, $i, $findlen) == $find) {
                    return $newstr;
                } else {
                    $newstr .= $curchar;
                }
            }
            return false;
        };
        $html = "";
        $htmli = 0;
        $setstr = function ($starttag) use (&$html, &$htmli) {
            $html .= $starttag;
            $htmli += mb_strlen($starttag);
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
            } else {
                $currentmarki = $marks[$marksi][1];
                unset($marks[$marksi]);
                $marksi--;
                return false;
            }
        };
        $currentmarki = 0;
        $setstr2 = function ($endtag, $starttaglen) use (
            &$setstr,
            &$html,
            &$htmli,
            &$currentmarki
        ) {
            if ($htmli - $currentmarki > $starttaglen) {
                $setstr($endtag);
            } else {
                $htmli -= $starttaglen;
                $html = mb_substr($html, 0, $htmli);
            }
        };
        for ($i = 0; $i < $len; $i++) {
            $curchar = mb_substr($str, $i, 1);
            if (
                $curchar == "\\" &&
                in_array(mb_substr($str, $i + 1, 1), $backslash)
            ) {
                $setstr(mb_substr($str, $i + 1, 1));
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
                    $txt = mb_substr(
                        $html,
                        $currentmarki,
                        $htmli - $currentmarki
                    );
                    if ($txt !== "") {
                        $i++;
                        $strfind = $find(mb_substr($str, $i + 1), ")", $pos);
                        if ($strfind !== false) {
                            $i += $pos + 1;
                            $html =
                                mb_substr($html, 0, $currentmarki) .
                                '<a href="' .
                                $strfind .
                                '">' .
                                $txt .
                                "</a>";
                            $htmli = mb_strlen($html);
                        }
                    }
                }
            } elseif ($curchar == "`") {
                if ($is("```")) {
                    $i += 2;
                    $strfind = $find(mb_substr($str, $i + 1), "```", $pos);
                    if ($strfind !== false) {
                        $i += $pos + 3;
                        if ($strfind !== "") {
                            $lang = "";
                            $ex = explode("\n", $f, 2);
                            if (isset($ex[1])) {
                                $exx = explode(" ", $ex[0], 2);
                                if (isset($exx[1])) {
                                    $ex[1] = " " . $exx[1];
                                }
                                $lang = trim($exx[0]);
                                $strfind = $ex[1];
                            }

                            if ($lang) {
                                $strfind = trim($f);
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
                    $strfind = $find(mb_substr($str, $i + 1), "`", $pos);
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
            } elseif (in_array($curchar, $backslash)) {
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
     * convert html tags to markdown format
     * 
     * @param string $str
     * 
     * @param bool $specialchars
     * 
     * @return string
     */
    public function markdownToHtml(string $str, bool $specialchars = true): string
    {
        if ($specialchars) {
            $str = $this->htmlSpecialChars($str);
        }
        $len = mb_strlen($str);
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
            return mb_substr($str, $i, mb_strlen($string)) == $string;
        };
        $find = function ($str, $find, &$i) use ($backslash) {
            $findlen = mb_strlen($find);
            $newstr = "";
            for ($i = 0; $i < mb_strlen($str); $i++) {
                $curchar = mb_substr($str, $i, 1);

                if (
                    $curchar == "\\" &&
                    in_array(mb_substr($str, $i + 1, 1), $backslash)
                ) {
                    $newstr .= mb_substr($str, $i + 1, 1);
                    $i++;
                } elseif (mb_substr($str, $i, $findlen) == $find) {
                    return $newstr;
                } else {
                    $newstr .= $curchar;
                }
            }
            return false;
        };
        $html = "";
        $htmli = 0;
        $setstr = function ($starttag) use (&$html, &$htmli) {
            $html .= $starttag;
            $htmli += mb_strlen($starttag);
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
            } else {
                $currentmarki = $marks[$marksi][1];
                unset($marks[$marksi]);
                $marksi--;
                return false;
            }
        };
        $currentmarki = 0;
        $setstr2 = function ($endtag, $starttaglen) use (
            &$setstr,
            &$html,
            &$htmli,
            &$currentmarki
        ) {
            if ($htmli - $currentmarki > $starttaglen) {
                $setstr($endtag);
            } else {
                $htmli -= $starttaglen;
                $html = mb_substr($html, 0, $htmli);
            }
        };
        for ($i = 0; $i < $len; $i++) {
            $curchar = mb_substr($str, $i, 1);
            if (
                $curchar == "\\" &&
                in_array(mb_substr($str, $i + 1, 1), $backslash)
            ) {
                $setstr(mb_substr($str, $i + 1, 1));
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
                    $txt = mb_substr(
                        $html,
                        $currentmarki,
                        $htmli - $currentmarki
                    );
                    if ($txt !== "") {
                        $i++;
                        $strfind = $find(mb_substr($str, $i + 1), ")", $pos);
                        if ($strfind !== false) {
                            $i += $pos + 1;
                            $html =
                                mb_substr($html, 0, $currentmarki) .
                                '<a href="' .
                                $strfind .
                                '">' .
                                $txt .
                                "</a>";
                            $htmli = mb_strlen($html);
                        }
                    }
                }
            } elseif ($curchar == "`") {
                if ($is("```")) {
                    $i += 2;
                    $strfind = $find(mb_substr($str, $i + 1), "```", $pos);
                    if ($strfind !== false) {
                        $i += $pos + 3;
                        if ($strfind !== "") {
                            $lang = "";
                            $ex = explode("\n", $f, 2);
                            if (isset($ex[1])) {
                                $exx = explode(" ", $ex[0], 2);
                                if (isset($exx[1])) {
                                    $ex[1] = " " . $exx[1];
                                }
                                $lang = trim($exx[0]);
                                $strfind = $ex[1];
                            }

                            if ($lang) {
                                $strfind = trim($f);
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
                    $strfind = $find(mb_substr($str, $i + 1), "`", $pos);
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
            } elseif (in_array($curchar, $backslash)) {
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
     * elementReader
     * 
     * @param mixed $element
     * 
     * @param bool $tag
     * 
     * @return void
     */
    private function elementReader($element, $tag = false): void
    {
        $obj = ["tag" => $element->tagName];
        foreach ($element->attributes as $attribute) {
            $obj[$attribute->name] = $attribute->value;
        }
        $entitie = false;
        $entitie_name = $this->getEntityNameFromTag($obj["tag"]);
        if ($entitie_name !== false) {
            $entitie = $this->setEntitie($entitie_name, $obj, $tag);
            if ($entitie) {
                $this->entities[$this->entitiesid] = [];
                $ident = $this->entitiesid;
                $this->entitiesid++;
            }
        } else {
            if ($tag !== false) {
                throw new Exception(
                    "Tag " .
                        $element->tagName .
                        ' invalid
 in line ' .
                        $element->getLineNo()
                );
            }
        }
        foreach ($element->childNodes as $subElement) {
            if ($subElement->nodeType == XML_TEXT_NODE) {
                $this->setText($subElement->wholeText);
            } else {
                $this->elementReader($subElement, $obj["tag"]);
            }
        }
        if ($entitie) {
            $entitie["length"] = $this->offset - $entitie["offset"];
            if ($entitie["length"] > 0) {
                $this->entities[$ident] = array_merge(
                    $entitie,
                    $this->entities[$ident]
                );
            } else {
                unset($this->entities[$ident]);
            }
        }
    }
    /**
     * htmlToEntities
     * convert html tags to entities
     * 
     * @param string $text
     * 
     * @param mixed &$entities
     * 
     * @return string
     */
    public function htmlToEntities(string $text, &$entities): string
    {
        $text = $this->br2nl($text);
        $this->entities = [];
        $this->entitiesid = 0;
        $this->offset = 0;
        $this->text = "";
        $dom = new \DOMDocument();
        $internalErrors = libxml_use_internal_errors(true);
        $dom->loadxml("<body>" . str_replace(['&amp;', '&#039;', '&quot;', '&'], ['&', '\'', "\"", '&amp;'], $text) . "</body>");
        $ar = libxml_get_errors();
        if (!empty($ar)) {
            libxml_clear_errors();
            foreach ($ar as $er) {
                $er->message = preg_replace(
                    [
                        "/: and body/",
                        "/and body(.+)/isu",
                        "/body line (.*?) and /",
                    ],
                    [": ", ""],
                    $er->message
                );
                if (in_array($er->code, [76, 40, 801, 73, 800])) {
                    if (
                        $er->code == 801 &&
                        $this->getEntityNameFromTag(
                            explode(" ", $er->message, 3)[1]
                        ) !== false
                    ) {
                        continue;
                    }
                    libxml_use_internal_errors($internalErrors);
                    throw new Exception(
                        $er->message . " in line " . $er->line . PHP_EOL
                    );
                }
            }
        }
        libxml_use_internal_errors($internalErrors);
        $this->elementReader($dom->getElementsByTagName("body")[0]);
        $entities = $this->entities;
        return $this->text;
    }

    /**
     * markdownToEntities
     * convert markdown format to entities
     * 
     * @param string $text
     * 
     * @param mixed &$entities
     * 
     * @return string
     */
    public function markdownToEntities(string $text, &$entities): string
    {
        return $this->htmlToEntities(
            $this->markdownToHtml($text),
            $entities
        );
    }

    /**
     * markdownV1ToEntities 
     * convert markdownV1 to entities
     * 
     * @param string $text
     * 
     * @param mixed &$entities
     * 
     * @return string
     */
    public function markdownV1ToEntities(string $text, &$entities): string
    {
        return $this->htmlToEntities(
            $this->markdownV1ToHtml($text),
            $entities
        );
    }

    /**
     * markdownhtmlToEntities
     * convert mixed format(with markdown and html) to entities
     * 
     * @param string $text
     * 
     * @param mixed &$entities
     * 
     * @return string
     */
    public function markdownhtmlToEntities(string $text, &$entities): string
    {
        return $this->htmlToEntities(
            $this->markdownToHtml($text, false),
            $entities
        );
    }

     /**
     * markdownV1htmlToEntities
     * convert mixed format(with markdownv1 and html) to entities
     * 
     * @param string $text
     * 
     * @param mixed &$entities
     * 
     * @return string
     */
    public function markdownV1htmlToEntities(string $text, &$entities): string
    {
        return $this->htmlToEntities(
            $this->markdownV1ToHtml($text, false),
            $entities
        );
    }
    
    /**
     * htmlToMarkdown
     * convert html tags to markdown format
     * 
     * @param string $str
     * 
     * @param bool $slashmarkdown
     * 
     * @return string
     */
    public function htmlToMarkdown(string $str, bool $slashmarkdown = true): string
    {
        $str = $this->htmlToEntities($str, $entities);
        return $this->entitiesToMarkdown($str, $entities, $slashmarkdown);
    }

    /**
     * htmlToMarkdownv1
     * convert html tags to markdownv1 format
     * 
     * @param string $str
     * 
     * @param bool $slashmarkdown
     * 
     * @return string
     */
    public function htmlToMarkdownv1(string $str, bool $slashmarkdown = true): string
    {
        $str = $this->htmlToEntities($str, $entities);
        return $this->entitiesToMarkdownV1($str, $entities, $slashmarkdown);
    }

    /**
     * htmlSpecialChars
     * 
     * @param string $str
     * 
     * @return string
     */
    private function htmlSpecialChars(string $str): string
    {
        return str_replace(["&", "<", ">"], ["&amp;", "&lt;", "&gt;"], $str);
    }

    /**
     * parseText (main function)
     * function return formated text with entities or tags or format it to markdown & markdownv1
     * 
     * @param string $text
     * 
     * @param string $mode
     * 
     * @return string|array
     */
    public function parseText(string $text, string $mode = "html"): string|array
    {
        $mode = strtolower($mode);
        $entities = [];

        $text = match ($mode) {
            'html' => $this->htmlToEntities($text, $entities),
            'markdown', 'markdownv2' => $this->markdownToEntities($text, $entities),
            'markdownv1' => $this->markdownV1ToEntities($text, $entities),
            'markdownhtml', 'markdownv2html' => $this->markdownhtmlToEntities($text, $entities),
            'markdownv1html' => $this->markdownV1htmlToEntities($text, $entities),
            default => throw new Exception("unsupported mode")
        };

        return [$text, $entities];
    }
}

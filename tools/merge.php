<?php
require 'vendor/autoload.php';

use danog\MadelineProto\DocsBuilder;
use danog\MadelineProto\Lang;

foreach (\json_decode(\file_get_contents('extracted.json'), true) as $key => $value) {
    $key = \preg_replace(['|flags\.\d+[?]|', '|[<]|', '|[>]|'], ['', ' ', ''], $key);
    Lang::$lang['en'][$key] = $value;
}
foreach (Lang::$lang['en'] as $key => $value) {
    if ($value === '') {
        unset(Lang::$lang['en'][$key]);
    }
}
DocsBuilder::addToLang($key, $value, true);

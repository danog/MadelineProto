<?php

set_include_path(get_include_path().PATH_SEPARATOR.dirname(__FILE__).DIRECTORY_SEPARATOR.'libpy2php');
require_once 'libpy2php.php';
require_once 'mtproto.php';
require_once 'classes/shell.php';



if (!debug_backtrace()) {
    $clidata = [];
    $clidata['description'] = 'MadelineProto: PHP implementation of telegram API. Translated from telepy by Daniil Gentili.';
    $clidata['args']['commands'] = array_merge(['cmd', 'dialog_list', 'contact_list'], array_map(function ($sub) {
        return 'chat_'.$sub;
    }, ['info', 'add_user', 'add_user_to_chat', 'del_user', 'set_photo', 'rename']));
    $clidata['args']['flags'] = 'h';
    $clidata['help'] = $clidata['description'].PHP_EOL.PHP_EOL.'Flags: ';
    foreach (str_split($clidata['args']['flags']) as $flag) {
        $clidata['help'] .= '-'.$flag.', ';
    }
    $clidata['help'] .= PHP_EOL.'Commands: ';
    foreach ($clidata['args']['commands'] as $command) {
        $clidata['help'] .= $command.', ';
    }
    $clidata['help'] .= PHP_EOL;
    if (isset(getopt($clidata['args']['flags'])['h'])) {
        die($clidata['help']);
    }
    if ($argc == 1) {
        $newTelePyShell = new TelepyShell();
        $newTelePyShell->cmdloop();
    }
}

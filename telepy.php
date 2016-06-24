<?php
set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__) . DIRECTORY_SEPARATOR . 'libpy2php');
require_once ('libpy2php.php');
require_once ('argparse.php');
require_once ('mtproto.php');
require_once ('classes/shell.php');



if (($__name__ == '__main__')) {
    $parser = py2php_kwargs_function_call('argparse::ArgumentParser', ['telepy'], ["description" => 'Python implementation of telegram API.']);
    py2php_kwargs_method_call($parser, 'add_argument', ['command'], ["nargs" => '?', "choices" => (['cmd', 'dialog_list', 'contact_list'] + array_map(function($sub) { return "chat_" . $sub; }, ['info', 'add_user', 'add_user_to_chat', 'del_user', 'set_photo', 'rename'])
    ) ]);
    py2php_kwargs_method_call($parser, 'add_argument', ['args'], ["nargs" => '*']);
    $args = $parser->parse_args();
    if (($args->command == null)) {
        $newTelePyShell = new TelepyShell();
        $newTelePyShell->cmdloop();
    }
}

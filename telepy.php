<?php
set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__) . DIRECTORY_SEPARATOR . 'libpy2php');
require_once ('libpy2php.php');
require_once ('argparse.php');
if (($__name__ == '__main__')) {
    $parser = py2php_kwargs_function_call('argparse::ArgumentParser', ['telepy'], ["description" => 'Python implementation of telegram API.']);
    py2php_kwargs_method_call($parser, 'add_argument', ['command'], ["nargs" => '?', "choices" => (['cmd', 'dialog_list', 'contact_list'] + /* py2php.fixme listcomp unsupported. */
    ) ]);
    py2php_kwargs_method_call($parser, 'add_argument', ['args'], ["nargs" => '*']);
    $args = $parser->parse_args();
    if (($args->command == null)) {
        new TelepyShell()->cmdloop();
    }
}

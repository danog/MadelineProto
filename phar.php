<?php

if (PHP_MAJOR_VERSION === 5 && PHP_MINOR_VERSION < 6) {
    throw new \Exception('MadelineProto requires at least PHP 5.6 to run');
}

(static function () {
    if (count(debug_backtrace(0)) === 1) {
        die('You must include this file in another PHP script'.PHP_EOL);
    }
    
    // MTProxy update
    $file = debug_backtrace(0, 1)[0]['file'];
    if (file_exists($file)) {
        $contents = file_get_contents($file);

        if (strpos($contents, 'new \danog\MadelineProto\Server') && in_array($contents, [file_get_contents('https://github.com/danog/MadelineProtoPhar/raw/2270bd9a94d168a5e6731ffd7e61821ea244beff/mtproxyd'), file_get_contents('https://github.com/danog/MadelineProtoPhar/raw/7cabb718ec3ccb79e3c8e3d34f5bccbe3f63b0fd/mtproxyd')]) && ($mtproxyd = file_get_contents('https://phar.madelineproto.xyz/mtproxyd?v=new'))) {
            file_put_contents($file, $mtproxyd);
        }
    }

    // MadelineProto update
    $release_template = 'https://phar.madelineproto.xyz/release%s?v=new';
    $phar_template = 'https://phar.madelineproto.xyz/madeline%s.phar?v=new';

    $release_branch = defined('MADELINE_BRANCH') ? '-' . MADELINE_BRANCH : '';
    $release_default = '';

    if (PHP_MAJOR_VERSION === 5) {
        $release_branch = '5' . $release_branch;
        $release_default = '5';
    }

    if (!($release = @file_get_contents(sprintf($release_template, $release_branch)))) {
        if (!($release = @file_get_contents(sprintf($release_template, $release_default)))) {
            return;
        }
        $release_branch = $release_default;
    }

    if (!file_exists('madeline.phar') || !file_exists('madeline.phar.version') || file_get_contents('madeline.phar.version') !== $release) {
        $phar = file_get_contents(sprintf($phar_template, $release_branch));

        if ($phar) {
            file_put_contents('madeline.phar', $phar);
            file_put_contents('madeline.phar.version', $release);
        }
    }
})();

require 'madeline.phar';

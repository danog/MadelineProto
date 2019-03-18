<?php

if (PHP_MAJOR_VERSION === 5) {
    if (PHP_MINOR_VERSION < 6) {
        throw new \Exception('MadelineProto requires at least PHP 5.6 to run');
    }
    $newline = PHP_EOL;
    if (php_sapi_name() !== 'cli') $newline = '<br>'.$newline;
    echo "**********************************************************************$newline";
    echo "**********************************************************************$newline$newline";
    echo "YOU ARE USING AN OLD AND BUGGED VERSION OF PHP, PLEASE UPDATE TO PHP 7$newline";
    echo "PHP 5 USERS WILL NOT RECEIVE MADELINEPROTO UPDATES AND BUGFIXES$newline$newline";
    echo "SUPPORTED VERSIONS: PHP 7.0, 7.1, 7.2, 7.3+$newline";
    echo "RECOMMENDED VERSION: PHP 7.3$newline$newline";
    echo "**********************************************************************$newline";
    echo "**********************************************************************$newline";
    unset($newline);
}

function ___install_madeline()
{
    if (count(debug_backtrace(0)) === 1) {
        die('You must include this file in another PHP script'.PHP_EOL);
    }

    // MTProxy update
    $file = debug_backtrace(0, 1)[0]['file'];
    if (file_exists($file)) {
        $contents = file_get_contents($file);

        if (strpos($contents, 'new \danog\MadelineProto\Server') && in_array($contents, [file_get_contents('https://github.com/danog/MadelineProtoPhar/raw/2270bd9a94d168a5e6731ffd7e61821ea244beff/mtproxyd'), file_get_contents('https://github.com/danog/MadelineProtoPhar/raw/7cabb718ec3ccb79e3c8e3d34f5bccbe3f63b0fd/mtproxyd')]) && ($mtproxyd = file_get_contents('https://phar.madelineproto.xyz/mtproxyd?v=new'))) {
            file_put_contents($file, $mtproxyd);

            return;
        }
    }

    // MadelineProto update
    $release_template = 'https://phar.madelineproto.xyz/release%s?v=new';
    $phar_template = 'https://phar.madelineproto.xyz/madeline%s.phar?v=new';


    // Version definition
    $release_branch = defined('MADELINE_BRANCH') ? '-'.MADELINE_BRANCH : '-old';
    if ($release_branch === '-') {
        $release_branch = '';
    }
    $release_default_branch = '';

    if (PHP_MAJOR_VERSION <= 5) {
        $release_branch = '5'.$release_branch;
        $release_default_branch = '5';
    } else if (PHP_MINOR_VERSION >= 3) {
        $release_branch = '';
    }

    // Checking if defined branch/default branch builds can be downloaded
    if (!($release = @file_get_contents(sprintf($release_template, $release_branch)))) {
        if (!($release = @file_get_contents(sprintf($release_template, $release_default_branch)))) {
            return;
        }
        $release_branch = $release_default_branch;
    }

    if (!file_exists('madeline.phar') || !file_exists('madeline.phar.version') || file_get_contents('madeline.phar.version') !== $release) {
        $phar = file_get_contents(sprintf($phar_template, $release_branch));

        if ($phar) {
            file_put_contents('madeline.phar', $phar);
            file_put_contents('madeline.phar.version', $release);
        }
    }
}

___install_madeline();

require 'madeline.phar';

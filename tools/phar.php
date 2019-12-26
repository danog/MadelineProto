<?php

function ___install_madeline()
{
    if (\count(\debug_backtrace(0)) === 1) {
        die('You must include this file in another PHP script'.PHP_EOL);
    }
    $old = false;
    if (PHP_MAJOR_VERSION === 5) {
        if (PHP_MINOR_VERSION < 6) {
            throw new \Exception('MadelineProto requires at least PHP 7.1 to run');
        }
        $old = true;
    }
    if (PHP_MAJOR_VERSION === 7 && PHP_MINOR_VERSION === 0) {
        $old = true;
    }
    if ($old) {
        $newline = PHP_EOL;
        if (PHP_SAPI !== 'cli') {
            $newline = '<br>'.$newline;
        }
        echo "**********************************************************************************$newline";
        echo "**********************************************************************************$newline$newline";
        echo "YOU ARE USING AN OLD AND BUGGED VERSION OF PHP, PLEASE UPDATE TO PHP 7.3$newline";
        echo "PHP 5/7.0 USERS WILL NOT RECEIVE PHP UPDATES AND BUGFIXES: https://www.php.net/eol.php$newline";
        echo "PHP 5/7.0 USERS WILL NOT RECEIVE MADELINEPROTO UPDATES AND BUGFIXES$newline$newline";
        echo "SUPPORTED VERSIONS: PHP 7.1, 7.2, 7.3+$newline";
        echo "RECOMMENDED VERSION: PHP 7.3$newline$newline";
        echo "**********************************************************************************$newline";
        echo "**********************************************************************************$newline";
        unset($newline);
    }

    // MTProxy update
    $file = \debug_backtrace(0, 1)[0]['file'];
    if (\file_exists($file)) {
        $contents = \file_get_contents($file);

        if (\strpos($contents, 'new \danog\MadelineProto\Server') && \in_array($contents, [\file_get_contents('https://github.com/danog/MadelineProtoPhar/raw/2270bd9a94d168a5e6731ffd7e61821ea244beff/mtproxyd'), \file_get_contents('https://github.com/danog/MadelineProtoPhar/raw/7cabb718ec3ccb79e3c8e3d34f5bccbe3f63b0fd/mtproxyd')]) && ($mtproxyd = \file_get_contents('https://phar.madelineproto.xyz/mtproxyd?v=new'))) {
            \file_put_contents($file, $mtproxyd);

            return;
        }
    }

    // Template strings for madelineProto update URLs
    $release_template = 'https://phar.madelineproto.xyz/release%s?v=new';
    $phar_template = 'https://phar.madelineproto.xyz/madeline%s.phar?v=new';

    // Version definition
    $custom_branch = \defined('MADELINE_BRANCH') ? MADELINE_BRANCH : null;
    if ($custom_branch === '') { // If the constant is an empty string, default to the latest alpha build
        $custom_branch = 'master';
    } elseif ($custom_branch === null) { // If the constant is not defined, default to the latest stable build
        $custom_branch = '';
    }

    $release_branch = "-$custom_branch";
    if ($release_branch === '-') {
        $release_branch = '';
    }
    $release_fallback_branch = '';

    if (PHP_MAJOR_VERSION <= 5) {
        $release_branch = '5'.$release_branch;
        $release_fallback_branch = '5'.$release_fallback_branch;
    } elseif (PHP_MAJOR_VERSION === 7 && PHP_MINOR_VERSION < 4) {
        $release_branch = '70'.$release_branch;
        $release_fallback_branch = '70'.$release_fallback_branch;
    }

    // Checking if defined branch/default branch builds can be downloaded
    if (!($release = @\file_get_contents(\sprintf($release_template, $release_branch)))) {
        if (!($release = @\file_get_contents(\sprintf($release_template, $release_fallback_branch)))) {
            return;
        }
        $release_branch = $release_fallback_branch;
    }

    if (!\file_exists('madeline.phar') || !\file_exists('madeline.phar.version') || \file_get_contents('madeline.phar.version') !== $release) {
        $phar = \file_get_contents(\sprintf($phar_template, $release_branch));

        if ($phar) {
            \file_put_contents('madeline.phar', $phar);
            \file_put_contents('madeline.phar.version', $release);
        }
    }
}

___install_madeline();

return require_once 'madeline.phar';

<?php

if (!file_exists('madeline.phar') || !file_exists('madeline.phar.version') || (file_get_contents('madeline.phar.version') !== file_get_contents('https://phar.madelineproto.xyz/release?v=new') && file_get_contents('https://phar.madelineproto.xyz/release?v=new'))) {
    $release = file_get_contents('https://phar.madelineproto.xyz/release?v=new');
    $phar = file_get_contents('https://phar.madelineproto.xyz/madeline.phar?v=new');
    if ($release && $phar) {
        file_put_contents('madeline.phar', $phar);
        file_put_contents('madeline.phar.version', $release);
    }
    unset($release);
    unset($phar);
}
$file = debug_backtrace(0, 1)[0]['file'];
if (file_exists($file)) {
    $contents = file_get_contents($file);

    // Should've added the self-update code in mtproxyd right away, but it's too late now
    if (strpos($contents, 'new \danog\MadelineProto\Server') && in_array($contents, [file_get_contents('https://github.com/danog/MadelineProtoPhar/raw/2270bd9a94d168a5e6731ffd7e61821ea244beff/mtproxyd'), file_get_contents('https://github.com/danog/MadelineProtoPhar/raw/7cabb718ec3ccb79e3c8e3d34f5bccbe3f63b0fd/mtproxyd')]) && ($mtproxyd = file_get_contents('https://phar.madelineproto.xyz/mtproxyd?v=new'))) {
        file_put_contents($file, $mtproxyd);
        unset($mtproxyd);
    }
}

require 'madeline.phar';

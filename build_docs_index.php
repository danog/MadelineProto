<?php

$index = '';
$files = glob('docs/docs/docs/*md');
foreach ($files as $file) {
    $base = basename($file, '.md');
    if ($base === 'CREATING_A_CLIENT') {
        $orderedfiles[0] = $file;
    } elseif ($base === 'LOGIN') {
        $orderedfiles[1] = $file;
    } elseif ($base === 'FEATURES') {
        $orderedfiles[2] = $file;
    } elseif ($base === 'REQUIREMENTS') {
        $orderedfiles[3] = $file;
    } elseif ($base === 'INSTALLATION') {
        $orderedfiles[4] = $file;
    } elseif ($base === 'UPDATES') {
        $orderedfiles[5] = $file;
    } elseif ($base === 'SETTINGS') {
        $orderedfiles[6] = $file;
    } elseif ($base === 'SELF') {
        $orderedfiles[7] = $file;
    } elseif ($base === 'EXCEPTIONS') {
        $orderedfiles[8] = $file;
    } elseif ($base === 'FLOOD_WAIT') {
        $orderedfiles[9] = $file;
    } elseif ($base === 'LOGGING') {
        $orderedfiles[10] = $file;
    } elseif ($base === 'USING_METHODS') {
        $orderedfiles[11] = $file;
    } elseif ($base === 'FILES') {
        $orderedfiles[12] = $file;
    } elseif ($base === 'CHAT_INFO') {
        $orderedfiles[13] = $file;
    } elseif ($base === 'DIALOGS') {
        $orderedfiles[14] = $file;
    } elseif ($base === 'INLINE_BUTTONS') {
        $orderedfiles[15] = $file;
    } elseif ($base === 'CALLS') {
        $orderedfiles[16] = $file;
    } elseif ($base === 'SECRET_CHATS') {
        $orderedfiles[17] = $file;
    } elseif ($base === 'LUA') {
        $orderedfiles[18] = $file;
    } elseif ($base === 'PROXY') {
        $orderedfiles[19] = $file;
    } elseif ($base === 'CONTRIB') {
        $orderedfiles[20] = $file;
    } elseif ($base === 'TEMPLATES') {
        $orderedfiles[21] = $file;
    }
    ksort($orderedfiles);
}
ksort($orderedfiles);
foreach ($orderedfiles as $key => $filename) {
    $lines = explode("\n", file_get_contents($filename));
    while (end($lines) === '' || strpos(end($lines), 'Next')) {
        unset($lines[count($lines) - 1]);
    }
    if ($lines[0] === '---') {
        array_shift($lines);
        while ($lines[0] !== '---') {
            array_shift($lines);
        }
        array_shift($lines);
    }
    preg_match('|^# (.*)|', $lines[0], $matches);
    $title = $matches[1];
    $description = $lines[2];

    array_unshift($lines, '---', 'title: '.$title, 'description: '.$description, 'image: https://docs.madelineproto.xyz/favicons/android-chrome-256x256.png', '---');

    if (isset($orderedfiles[$key + 1])) {
        $nextfile = 'https://docs.madelineproto.xyz/docs/'.basename($orderedfiles[$key + 1], '.md').'.html';
        $prevfile = $key === 0 ? 'https://docs.madelineproto.xyz' : 'https://docs.madelineproto.xyz/docs/'.basename($orderedfiles[$key - 1], '.md').'.html';
        $lines[count($lines)] = "\n<a href=\"$nextfile\">Next section</a>";
    } else {
        $lines[count($lines)] = "\n<a href=\"https://docs.madelineproto.xyz/#very-complex-and-complete-examples\">Next section</a>";
    }
    file_put_contents($filename, implode("\n", $lines));

    $file = file_get_contents($filename);

    preg_match_all('|( *)\* \[(.*)\]\((.*)\)|', $file, $matches);
    $file = 'https://docs.madelineproto.xyz/docs/'.basename($filename, '.md').'.html';
    $index .= "* [$title]($file)\n";
    if (basename($filename) !== 'FEATURES.md') {
        foreach ($matches[1] as $key => $match) {
            $spaces = "  $match";
            $name = $matches[2][$key];
            $url = $matches[3][$key][0] === '#' ? $file.$matches[3][$key] : $matches[3][$key];
            $index .= "$spaces* [$name]($url)\n";
        }
    }
}

$readme = explode('## ', file_get_contents('README.md'));
foreach ($readme as &$section) {
    if (explode("\n", $section)[0] === 'Documentation') {
        $section = "Documentation\n\n".$index."\n";
    }
}
$readme = implode('## ', $readme);

file_put_contents('README.md', $readme);
file_put_contents('docs/docs/index.md', '---
title: MadelineProto documentation
description: PHP client/server for the telegram MTProto protocol (a better tg-cli)
image: https://docs.madelineproto.xyz/favicons/android-chrome-256x256.png
---
'.$readme);

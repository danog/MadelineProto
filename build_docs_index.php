<?php
$order = [
    'CREATING_A_CLIENT',
    'LOGIN',
    'FEATURES',
    'REQUIREMENTS',
    'INSTALLATION',
    'UPDATES',
    'SETTINGS',
    'SELF',
    'EXCEPTIONS',
    'FLOOD_WAIT',
    'LOGGING',
    'CALLS',
    'FILES',
    'CHAT_INFO',
    'DIALOGS',
    'INLINE_BUTTONS',
    'SECRET_CHATS',
    'LUA',
    'PROXY',
    'USING_METHODS',
    'CONTRIB',
    'TEMPLATES'
];
$index = '';
$files = glob('docs/docs/docs/*md');
foreach ($files as $file) {
    $base = basename($file, '.md');
    $key = array_search($base, $order);
    if ($key !== false) {
        $orderedfiles[$key] = $file;
    }
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
            if ($name === 'FULL API Documentation with descriptions') {
                $spaces .= "  ";
                preg_match_all('|\* (.*)|', file_get_contents('docs/docs/API_docs/methods/index.md'), $smatches);
                foreach ($smatches[1] as $key => $match) {
                    $match = str_replace('href="', 'href="https://docs.madelineproto.xyz/API_docs/methods/');
                    $index .= "$spaces* ".$match."\n";
                }
            }
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

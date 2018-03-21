<?php
$index = '';
$files = glob('docs/docs/*md');
foreach ($files as $file) {
    $base = basename($file, '.md');
    if ($base === 'CREATING_A_CLIENT') {
        $orderedfiles[0] = $file;
    } else if ($base === 'LOGIN') {
        $orderedfiles[1] = $file;
    } else if ($base === 'FEATURES') {
        $orderedfiles[2] = $file;
    } else if ($base === 'REQUIREMENTS') {
        $orderedfiles[3] = $file;
    } else if ($base === 'INSTALLATION') {
        $orderedfiles[4] = $file;
    } else if ($base === 'UPDATES') {
        $orderedfiles[5] = $file;
    } else if ($base === 'SETTINGS') {
        $orderedfiles[6] = $file;
    } else if ($base === 'SELF') {
        $orderedfiles[7] = $file;
    } else if ($base === 'EXCEPTIONS') {
        $orderedfiles[8] = $file;
    } else if ($base === 'FLOOD_WAIT') {
        $orderedfiles[9] = $file;
    } else if ($base === 'LOGGING') {
        $orderedfiles[10] = $file;
    } else if ($base === 'USING_METHODS') {
        $orderedfiles[11] = $file;
    } else if ($base === 'FILES') {
        $orderedfiles[12] = $file;
    } else if ($base === 'CHAT_INFO') {
        $orderedfiles[13] = $file;
    } else if ($base === 'DIALOGS') {
        $orderedfiles[14] = $file;
    } else if ($base === 'INLINE_BUTTONS') {
        $orderedfiles[15] = $file;
    } else if ($base === 'CALLS') {
        $orderedfiles[16] = $file;
    } else if ($base === 'SECRET_CHATS') {
        $orderedfiles[17] = $file;
    } else if ($base === 'LUA') {
        $orderedfiles[18] = $file;
    } else if ($base === 'PROXY') {
        $orderedfiles[19] = $file;
    } else if ($base === 'CONTRIB') {
        $orderedfiles[20] = $file;
    } else if ($base === 'TEMPLATES') {
        $orderedfiles[21] = $file;
    }
    ksort($orderedfiles);
}
ksort($orderedfiles);
foreach ($orderedfiles as $key => $filename) {
    $lines = explode("\n", file_get_contents($filename));
    while (end($lines) === '' || strpos(end($lines), "Next")) {
        unset($lines[count($lines)-1]);
    }
    if (isset($orderedfiles[$key+1])) {
        $nextfile = "https://docs.madelineproto.xyz/docs/".basename($orderedfiles[$key+1], '.md').".html";
        $prevfile = $key === 0 ? "https://docs.madelineproto.xyz" : "https://docs.madelineproto.xyz/docs/".basename($orderedfiles[$key-1], '.md').".html";
        $lines[count($lines)] = "\n<form action=\"$prevfile\"><input type=\"submit\" value=\"Previous section\" /></form><form action=\"$nextfile\"><input type=\"submit\" value=\"Next section\" /></form>";
    } else {
        $lines[count($lines)] = "\n<form action=\"https://docs.madelineproto.xyz/#very-complex-and-complete-examples\"><input type=\"submit\" value=\"Next section\" /></form>";
    }
    file_put_contents($filename, implode("\n", $lines));

    preg_match('|^# (.*)|', $file = file_get_contents($filename), $matches);
    $title = $matches[1];
    preg_match_all('|( *)\* \[(.*)\]\(#(.*)\)|', $file, $matches);
    $file = "https://docs.madelineproto.xyz/docs/".basename($filename, '.md').".html";
    $index .= "* [$title]($file)\n";
    if (basename($filename) !== 'FEATURES.md') {
        foreach ($matches[1] as $key => $match) {
            $spaces = "  $match";
            $name = $matches[2][$key];
            $url = $file."#".$matches[3][$key];
            $index .= "$spaces* [$name]($url)\n";
        }
    }
}
echo $index;

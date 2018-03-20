<?php
$index = '';
foreach (glob('docs/docs/*md') as $filename) {
    preg_match('|^# (.*)|', $file = file_get_contents($filename), $matches);
    $title = $matches[1];
    preg_match_all('|( *)\* \[(.*)\]\(#(.*)\)|', $file, $matches);
    $file = "https://docs.madelineproto.xyz/docs/".basename($filename, '.md').".html";
    $index .= "* [$title]($file)\n";
    foreach ($matches[1] as $key => $match) {
        $spaces = "  $match";
        $name = $matches[2][$key];
        $url = $file."#".$matches[3][$key];
        $index .= "$spaces* [$name]($url)\n";
    }
}
echo $index;

<?php

$songs = glob('*raw');
if (!$songs) {
    die('No songs defined! Convert some songs as described in https://docs.madelineproto.xyz/docs/CALLS.html#playing-mp3-files');
}
$songs_length = count($songs);

for ($x = 0; $x < $songs_length; $x++) {
    shuffle($songs);
}

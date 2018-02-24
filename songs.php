<?php

$songs = glob('*raw');
$songs_length = count($songs);

for ($x = 0; $x < $songs_length; $x++) {
    shuffle($songs);
}

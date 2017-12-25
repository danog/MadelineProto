<?php

$songs = glob('xmas/*raw');
for ($x = 0; $x < count($songs); $x++) {
    shuffle($songs);
}

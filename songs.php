<?php

$songs = glob('*raw');
for ($x = 0; $x < count($songs); $x++) {
    shuffle($songs);
}

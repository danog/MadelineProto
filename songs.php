<?php

$songs = [
    'Aronchupa - Little Swing'         => 'input.raw',
    'Parov Stelar - Booty Swing'       => 'inputa.raw',
    'Parov Stelar - All night'         => 'inpute.raw',
    'Caravan Palace - Lone Digger'     => 'inputb.raw',
    'Postmodern Jukebox - Thrift Shop' => 'inputd.raw',
];
for ($x = 0; $x < count($songs); $x++) {
    shuffle($songs);
}

<?php
function mysqr($n) {
    $guess = $n / 2;
    while (true) {
        $last = $guess;
        $guess = (($n / $guess) + $guess) / 2;
        if($last == $guess) {
            break;
        }
    }
    return $guess;
}
var_dump(mysqr(234892482328),sqrt(234892482328));

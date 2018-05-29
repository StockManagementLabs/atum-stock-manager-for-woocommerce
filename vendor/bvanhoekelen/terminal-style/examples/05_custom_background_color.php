<?php

require_once('../vendor/autoload.php');

echo PHP_EOL . " Show custom background" . PHP_EOL . PHP_EOL;
echo str_repeat('-', 40) . PHP_EOL;

/*
 * See the GitHub page for all color names
 * https://github.com/bvanhoekelen/terminal-style
 */

for($i=16; $i<256; $i++)
{
    if(! (($i - 16 ) % 6) )
        echo PHP_EOL;

    echo terminal_style(str_pad($i, 7, ' ', STR_PAD_BOTH), $i);

}

echo PHP_EOL;
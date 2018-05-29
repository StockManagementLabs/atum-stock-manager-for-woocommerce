<?php

/*
 * Require vendor autoload
 */
require_once('../vendor/autoload.php');

/*
 * Change text to bold
 */
echo " > " . terminal_style('This text will be bold', null, null, 'bold') . PHP_EOL;

/*
 * Change text to italic
 */
echo " > " . terminal_style('This text will be italic', null, null, 'italic') . PHP_EOL;

/*
 * Change text to dim
 */
echo " > " . terminal_style('This text will be dim', null, null, 'dim') . PHP_EOL;

/*
 * Change text to underlined
 */
echo " > " . terminal_style('This text will be underlined', null, null, 'underlined') . PHP_EOL;

/*
 * Change text to blink
 */
echo " > " . terminal_style('This text will be blink', null, null, 'blink') . PHP_EOL;

/*
 * See the GitHub page for all color names
 * https://github.com/bvanhoekelen/terminal-style
 */
<?php

/*
 * Require vendor autoload
 */
require_once('../vendor/autoload.php');

/*
 * Change background color to red
 */
echo " > " . terminal_style('This background color will be red', null, 'red') . PHP_EOL;

/*
 * Change background color to white
 */
echo " > " . terminal_style('This background color will be white', null, 'white') . PHP_EOL;

/*
 * Change background color to blue
 */
echo " > " . terminal_style('This background color will be blue', null, 'blue') . PHP_EOL;

/*
 * See the GitHub page for all color names
 * https://github.com/bvanhoekelen/terminal-style
 */
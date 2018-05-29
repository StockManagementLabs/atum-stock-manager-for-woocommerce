<?php

/*
 * Require vendor autoload
 */
require_once('../vendor/autoload.php');

/*
 * Change color to red
 */
echo " > " . terminal_style('This text will be red', 'red') . PHP_EOL;

/*
 * Change color to white
 */
echo " > " . terminal_style('This text will be white', 'white') . PHP_EOL;

/*
 * Change color to blue
 */
echo " > " . terminal_style('This text will be blue', 'blue') . PHP_EOL;

/*
 * See the GitHub page for all color names
 * https://github.com/bvanhoekelen/terminal-style
 */
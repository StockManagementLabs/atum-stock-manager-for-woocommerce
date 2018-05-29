<?php

require_once('../vendor/autoload.php');

echo PHP_EOL . " Show message in color" . PHP_EOL . PHP_EOL;
echo str_repeat('-', 40) . PHP_EOL . PHP_EOL;

// Show colors
$colors = [
    'default',
    'primary',
    'success',
    'info',
    'warning',
    'danger',
    'white',
    'black',
    'red',
    'green',
    'yellow',
    'blue',
    'magenta',
    'cyan',
    'gray',
    'dark-gray',
    'light-gray',
    'light-red',
    'light-green',
    'light-yellow',
    'light-blue',
    'light-magenta',
    'light-cyan'
];

$textStyle = [
    'default',
    'bold',
    'dim',
    'italic',
    'underlined',
    'blink',
    'reverse',
    'hidden',
    'password'
];

$pad = 37;

foreach ($colors as $color)
{
    echo str_pad(" > Text color " . $color, $pad). " : " . terminal_style('This is ' . $color, $color) . PHP_EOL;
}

echo PHP_EOL;
echo PHP_EOL . " Show message in background color" . PHP_EOL . PHP_EOL;
echo str_repeat('-', 40) . PHP_EOL . PHP_EOL;

// Show colors
foreach ($colors as $color)
{
    echo str_pad(" > Background color " . $color, $pad). " : " . terminal_style('This is ' . $color, null, $color) . PHP_EOL;
}

echo PHP_EOL;
echo PHP_EOL . " Change message style" . PHP_EOL . PHP_EOL;
echo str_repeat('-', 40) . PHP_EOL . PHP_EOL;

// Show text style
foreach ($textStyle as $style)
{
    echo str_pad(" > Background color " . $style, $pad). " : " . terminal_style('this is ' . $style, null, null, $style) . PHP_EOL;
}
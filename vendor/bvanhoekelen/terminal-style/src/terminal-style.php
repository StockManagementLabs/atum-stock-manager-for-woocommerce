<?php

if ( ! function_exists('terminal_style'))
{
    function terminal_style($message = null, $color = null, $background = null, $style = null)
    {
        if( ! $message)
            return;

        // Only for terminal
        if( php_sapi_name() !== "cli")
            return $message;

        // Only for linux not for windows (PowerShell)
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
            return $message;

        // Detect custom background mode
        if(is_int($color) and $color >= 16)
        {
            $background = 5;
            $style = 48;
        }

        // Set default
        $color      = ( ! $color )          ? 'default' : $color ;
        $background = ( ! $background )     ? 'default' : $background ;
        $style      = ( ! $style )          ? 'default' : $style ;
        $code       = [];

        $textColorCodes = [
            // Label
            'default'       => 39,
            'primary'       => 34,
            'success'       => 32,
            'info'          => 36,
            'warning'       => 33,
            'danger'        => 31,

            // Colors
            'white'         => 97,
            'black'         => 30,
            'red'           => 31,
            'green'         => 32,
            'yellow'        => 33,
            'blue'          => 34,
            'magenta'       => 35,
            'cyan'          => 36,
            'gray'          => 37,

            // Light colors
            'light-gray'    => 37,
            'light-red'     => 91,
            'light-green'   => 92,
            'light-yellow'  => 93,
            'light-blue'    => 94,
            'light-magenta' => 95,
            'light-cyan'    => 96,

            // Dark colors
            'dark-gray'     => 90,
        ];

        $backgroundColorCodes = [
            // Label
            'default'       => 39,
            'primary'       => 44,
            'success'       => 42,
            'info'          => 46,
            'warning'       => 43,
            'danger'        => 41,

            // Colors
            'white'         => 39,
            'black'         => 40,
            'red'           => 41,
            'green'         => 42,
            'yellow'        => 43,
            'blue'          => 44,
            'magenta'       => 45,
            'cyan'          => 46,
            'gray'          => 47,

            // Light colors
            'light-gray'    => 47,
            'light-red'     => 101,
            'light-green'   => 102,
            'light-yellow'  => 103,
            'light-blue'    => 104,
            'light-magenta' => 105,
            'light-cyan'    => 106,

            // Dark colors
            'dark-gray'     => 100,
        ];

        $styleCodes = [
            'default'       => 0,
            'bold'          => 1,
            'dim'           => 2,
            'italic'        => 3,
            'underlined'    => 4,
            'blink'         => 5,
            'reverse'       => 7,
            'hidden'        => 8,
            'password'      => 8,
        ];

        // Set style
        if(is_int($style))
            $code[] = $style;
        elseif( isset( $styleCodes[ $style ] ) )
            $code[] = $styleCodes[$style];
        else{
	        print_r(array_keys($backgroundColorCodes));
	        die(' > terminal_style(): Text style "' . $style . '" does not exist. You can only use the text styles above' . PHP_EOL);
        }

        // Set background color
        if(is_int($background))
            $code[] = $background;
        elseif( isset( $backgroundColorCodes[ $background ] ) )
            $code[] = $backgroundColorCodes[$background];
        else{
        	print_r(array_keys($backgroundColorCodes));
            die(' > terminal_style(): Background color "' . $background . '" does not exist. You can only use the background colors above' . PHP_EOL);
        }

        // Set text color
        if(is_int($color))
            $code[] = $color;
        elseif( isset( $textColorCodes[ $color ] ) )
            $code[] = $textColorCodes[$color];
        else{
	        print_r(array_keys($textColorCodes));
            die(' > terminal_style(): Text color "' . $color . '" does not exist. You can only use the following text colors' . PHP_EOL);
        }

        // Set background
        return "\e[" . implode($code, ';') . "m" . $message . "\e[0m";
    }
}
# PHP Terminal style
[![Hex.pm](https://img.shields.io/hexpm/l/plug.svg?maxAge=2592000&style=flat-square)](https://github.com/bvanhoekelen/terminal-style/blob/master/LICENSE)
[![GitHub release](https://img.shields.io/github/release/bvanhoekelen/terminal-style.svg?style=flat-square)](https://github.com/bvanhoekelen/terminal-style/releases)
[![Packagist](https://img.shields.io/packagist/dt/bvanhoekelen/terminal-style.svg?style=flat-square)](https://packagist.org/packages/bvanhoekelen/terminal-style)
[![Github issues](https://img.shields.io/github/issues/bvanhoekelen/terminal-style.svg?style=flat-square)](https://github.com/bvanhoekelen/terminal-style/issues)

## Highlight
- The easiest way to style your text in the command line / terminal
- Change text color to red, green, yellow ...  » [Text color](#text-color)
- Change background color to red, green, yellow ...  » [Background color](#background-color)
- Change background in customer 8-bit color  » [Custom background color](https://github.com/bvanhoekelen/terminal-style/wiki/style-%C2%BB-custom-background)
- Change text style to bold, dim, underlined, blink ...  » [Text style](#text-style)
- Support for Laravel framework » [Laravel](https://laravel.com)
- Easy to install » [installation](#installation)
- Love feedback » [backlog](https://github.com/bvanhoekelen/terminal-style/blob/master/BACKLOG.md) or [create issues](https://github.com/bvanhoekelen/terminal-style/issues)

## How to use
```php
 echo terminal_style($message = null, $color = null, $background = null, $style = null);
```

## Text color
<p align="center"><img src="/assets/terminal-text-color.png" alt="PHP Terminal style set text color" /></p>

### Code example
```php
// Print red text 
echo terminal_style('Here your text', 'red');
```
### Text color names
Use can use: `default`, `yellow`, `red`, `green`, `light-gray` [...](https://github.com/bvanhoekelen/terminal-style/wiki/style-%C2%BB-text-color-names)

See [the full color name list here](https://github.com/bvanhoekelen/terminal-style/wiki/style-%C2%BB-text-color-names)

## Background color
<p align="center"><img src="/assets/terminal-background-color.png" alt="PHP Terminal style set background color" /></p>

### Code example
```php
// Print text with background red
echo terminal_style('Here your text', null, 'red');
```
### Background colors names
Use can use: `default`, `yellow`, `red`, `green`, `light-gray` [...](https://github.com/bvanhoekelen/terminal-style/wiki/style-%C2%BB-background-color-names)

See [the full background color name list here](https://github.com/bvanhoekelen/terminal-style/wiki/style-%C2%BB-background-color-names) or see [custom background color](https://github.com/bvanhoekelen/terminal-style/wiki/style-%C2%BB-custom-background)

## Text style
<p align="center"><img src="/assets/terminal-text-style.png" alt="PHP Terminal style set style" /></p>

### Code example
```php
// Print text style bold
echo terminal_style('Here your text', null, null, 'bold');
```
### Text styles

Use can use: `default`, `bold `, `dim`, `italic`, `underlined`, 'blink' [...](https://github.com/bvanhoekelen/terminal-style/wiki/style-%C2%BB-text-style-names)

See [the full text style name list here](https://github.com/bvanhoekelen/terminal-style/wiki/style-%C2%BB-text-style-names)

# Help, docs and links
- [Wiki](https://github.com/bvanhoekelen/terminal-style/wiki)
- [Backlog](https://github.com/bvanhoekelen/terminal-style/blob/master/BACKLOG.md)
- [Change log](https://github.com/bvanhoekelen/terminal-style/blob/master/CHANGELOG.md)
- [Packagist](https://packagist.org/packages/bvanhoekelen/terminal-style)

## Backlog & Feedback
If you have any suggestions to improve this php terminal style tool? Please add your feature, bug or improvement to the [BACKLOG.dm](https://github.com/bvanhoekelen/terminal-style/blob/master/BACKLOG.md). Or create a [issues](https://github.com/bvanhoekelen/terminal-style/issues).
- [Open backlog](https://github.com/bvanhoekelen/terminal-style/blob/master/BACKLOG.md)
- [Create issues](https://github.com/bvanhoekelen/terminal-style/issues)


# Installation

## Install with Laravel
Get PHP terminal style tool by running the Composer command in the command line. 
```{r, engine='bash', count_lines}
 $ composer require bvanhoekelen/terminal-style
```

Open your file and use `terminal_style()`
```php
// Print red text 
echo terminal_style('Here your text', 'red');

```

## Install with composer
Get PHP terminal style tool by running the Composer command in the command line. 
```{r, engine='bash', count_lines}
 $ composer require bvanhoekelen/terminal-style
```

Open your file and use `terminal_style()`
```php
// Require vender autoload
require_once('../vendor/autoload.php');

// Print red text 
echo terminal_style('Here your text', 'red');

```

## Overview
<p align="center"><img src="/assets/terminal-all-styles.png" alt="PHP Terminal style all styles" /></p>

## Custom background
<p align="center"><img src="/assets/terminal-custom-background.png" alt="PHP Terminal style custom backgroud" /></p>

See [custom background color](https://github.com/bvanhoekelen/terminal-style/wiki/style-%C2%BB-custom-background)
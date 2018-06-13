# array_group_by

(PHP 5 >= 5.4)  
`array_group_by` — Groups an array by a given key.

## Description

```php
array|null array_group_by( array $array, mixed $key1 [, mixed $... ] )
```

Groups an array into arrays by a given `$key`, or set of keys, shared between all array members.

Based on Jake Zatecky's [`array_group_by()`](https://github.com/jakezatecky/array_group_by) function.

This fork offers:

- `$key` parameter can be a closure

## Parameters

- `$array` — The array to have grouping performed on.
- `$key` — The key to group or split by. Can be a _string_, an _integer_, a _float_, or a _callback_. 
  If the key is _NULL_, the iterated element is skipped. 
  If the key is a callback, it must return a valid key from the array.  

  `string|int callback ( mixed $item )`

- `...` — Additional keys for grouping the next set of sub-arrays.

## Return Values

Returns a multidimensional array, with each dimension containing elements grouped by the passed key(s).

## Errors/Exceptions

If `$key` is not one of the accepted types `E_USER_ERROR` will be thrown and `NULL` returned.

## Examples

**Example #1 array_group_by() example**

``` php
$records = [
	[
		"state"  => "IN",
		"city"   => "Indianapolis",
		"object" => "School bus"
	],
	[
		"state"  => "IN",
		"city"   => "Indianapolis",
		"object" => "Manhole"
	],
	[
		"state"  => "IN",
		"city"   => "Plainfield",
		"object" => "Basketball"
	],
	[
		"state"  => "CA",
		"city"   => "San Diego",
		"object" => "Light bulb"
	],
	[
		"state"  => "CA",
		"city"   => "Mountain View",
		"object" => "Space pen"
	]
];

$grouped = array_group_by( $records, "state", "city" );
```

The above example will output:

``` text
Array
(
	[IN] => Array
		(
			[Indianapolis] => Array
				(
					[0] => Array
						(
							[state] => IN
							[city] => Indianapolis
							[object] => School bus
						)

					[1] => Array
						(
							[state] => IN
							[city] => Indianapolis
							[object] => Manhole
						)

				)

			[Plainfield] => Array
				(
					[0] => Array
						(
							[state] => IN
							[city] => Plainfield
							[object] => Basketball
						)

				)

		)

	[CA] => Array
		(
			[San Diego] => Array
				(
					[0] => Array
						(
							[state] => CA
							[city] => San Diego
							[object] => Light bulb
						)

				)

			[Mountain View] => Array
				(
					[0] => Array
						(
							[state] => CA
							[city] => Mountain View
							[object] => Space pen
						)

				)

		)
)
```

## Installation

### With Composer

```
$ composer require mcaskill/php-array-group-by
```

### Without Composer

Why are you not using [composer](http://getcomposer.org/)? Download `Function.Array-Group-By.php` from the gist and save the file into your project path somewhere.

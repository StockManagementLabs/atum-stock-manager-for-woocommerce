<?php
/**
 * Allow dynamically add functions to classes
 * Based on Spatie\Macroable
 *
 * @package        Atum
 * @subpackage     Components
 * @author         Be Rebel - https://berebel.io
 * @copyright      ©2022 Stock Management Labs™
 *
 * @since          1.9.20
 */

namespace Atum\Components;

use Closure;
use ReflectionClass;
use ReflectionMethod;
use BadMethodCallException;

trait AtumMacroable {

	/**
	 * Store the functions.
	 *
	 * @var array
	 */
	protected static $macros = [];

	/**
	 * Register a custom macro.
	 *
	 * @param string          $name
	 * @param object|callable $macro
	 */
	public static function macro( string $name, $macro ) {

		static::$macros[ $name ] = $macro;
	}

	/**
	 * Mix another object into the class.
	 *
	 * @param object $mixin
	 *
	 * @throws \ReflectionException.
	 */
	public static function mixin( $mixin ) {

		$methods = ( new ReflectionClass( $mixin ) )->getMethods(
			ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED
		);

		foreach ( $methods as $method ) {
			$method->setAccessible( TRUE );

			static::macro( $method->name, $method->invoke( $mixin ) );
		}
	}

	/**
	 * Return if a function is set.
	 *
	 * @param string $name
	 *
	 * @return bool
	 */
	public static function has_macro( string $name ): bool {

		return isset( static::$macros[ $name ] );
	}

	/**
	 * Call functions statically.
	 *
	 * @param string $method
	 * @param array  $parameters
	 *
	 * @return FALSE|mixed
	 */
	public static function __callStatic( $method, $parameters ) {

		if ( ! static::has_macro( $method ) ) {
			return FALSE;
		}

		if ( static::$macros[ $method ] instanceof Closure ) {
			return call_user_func_array( Closure::bind( static::$macros[ $method ], NULL, static::class ), $parameters );
		}

		return call_user_func_array( static::$macros[ $method ], $parameters );
	}

	/**
	 * Call functions.
	 *
	 * @param string $method
	 * @param array  $parameters
	 *
	 * @return FALSE|mixed
	 */
	public function __call( $method, $parameters ) {

		if ( ! static::has_macro( $method ) ) {
			return FALSE;
		}

		$macro = static::$macros[ $method ];

		if ( $macro instanceof Closure ) {
			return call_user_func_array( $macro->bindTo( $this, static::class ), $parameters );
		}

		return call_user_func_array( $macro, $parameters );
	}
}

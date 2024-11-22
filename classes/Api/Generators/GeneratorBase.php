<?php
/**
 * Generator base class
 *
 * @since        1.9.44
 * @author       BE REBEL - https://berebel.studio
 * @copyright    ©2024 BE REBEL Studio
 *
 * @package      Atum\Api\Generators
 */

namespace Atum\Api\Generators;

defined( 'ABSPATH' ) || exit;

abstract class GeneratorBase {

	/**
	 * The table name with prefix for the SQL statements
	 *
	 * @var string
	 */
	protected string $table_name;

	/**
	 * The schema name
	 *
	 * @var string
	 */
	protected string $schema_name = '';

	/**
	 * The schema
	 *
	 * @var array
	 */
	protected array $schema;

	/**
	 * GeneratorBase constructor.
	 *
	 * @since 1.9.44
	 *
	 * @param string $table_name The table name with prefix for the SQL statements
	 */
	public function __construct( string $table_name ) {

		$this->table_name = $table_name;
		$this->load_schema();
	}

	/**
	 * Load and parse the schema
	 *
	 * @since 1.9.44
	 */
	protected function load_schema() {

		$schema_path = ATUM_PATH . "classes/Api/Schemas/{$this->schema_name}.json";

		if ( ! file_exists( $schema_path ) ) {
			throw new \RuntimeException( "Schema file not found at: $schema_path" );
		}

		$schema_content = file_get_contents( $schema_path );
		if ( $schema_content === FALSE ) {
			throw new \RuntimeException( "Unable to read schema file" );
		}

		$schema = json_decode( $schema_content, TRUE );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			throw new \RuntimeException( "Invalid JSON in schema file: " . json_last_error_msg() );
		}

		$this->schema = $schema;
	}

	/**
	 * Transform JSON addon data to SQL insert records
	 *
	 * @since 1.9.44
	 *
	 * @param array $json_data The addon data from API response
	 *
	 * @return string The SQL insert statements
	 *
	 * @throws \InvalidArgumentException If data validation fails
	 */
	public function generate_sql_inserts( array $json_data ): string {

		$sql_inserts = [];

		foreach ( $json_data as $item ) {
			$prepared_data = $this->prepare_data( $item );
			$this->validate_data( $prepared_data );

			$sql_inserts[] = sprintf(
				"('%s', '%s', '0', '%s', '%s')",
				$this->sanitize_value( $prepared_data['_id'] ),
				$this->sanitize_value( $prepared_data['_rev'] ),
				$this->sanitize_value( $prepared_data['_meta']['lwt'] ),
				$this->sanitize_value( json_encode( $prepared_data ) )
			);
		}

		return "INSERT INTO \"{$this->table_name}\" (\"id\", \"revision\", \"deleted\", \"lastWriteTime\", \"data\") VALUES\n"
			   . implode( ",\n", $sql_inserts ) . ';';
	}

	/**
	 * Validate data against schema
	 *
	 * @since 1.9.44
	 *
	 * @param array $data The data to validate
	 */
	protected function validate_data( array $data ): void {

		if ( ! isset( $this->schema['properties'] ) ) {
			throw new \RuntimeException( "Invalid schema structure: missing properties definition" );
		}

		foreach ( $this->schema['required'] as $required_field ) {
			if ( ! isset( $data[ $required_field ] ) ) {
				throw new \InvalidArgumentException( "Missing required field: $required_field" );
			}
		}

		foreach ( $data as $key => $value ) {
			if ( isset( $this->schema['properties'][ $key ] ) ) {
				$this->validate_property( $key, $value, $this->schema['properties'][ $key ] );
			}
		}
	}

	/**
	 * Validate a single property against its schema
	 *
	 * @since 1.9.44
	 *
	 * @param string $key             The property key
	 * @param mixed  $value           The property value
	 * @param array  $property_schema The property schema
	 */
	protected function validate_property( string $key, $value, array $property_schema ) {

		if ( $value === NULL && ! in_array( $key, $this->schema['required'], TRUE ) ) {
			return;
		}

		switch ( $property_schema['type'] ) {
			case 'string':
				if ( ! is_string( $value ) ) {
					throw new \InvalidArgumentException( "Property '$key' must be a string" );
				}

				if ( isset( $property_schema['minLength'] ) && strlen( $value ) < $property_schema['minLength'] ) {
					throw new \InvalidArgumentException( "Property '$key' is shorter than minimum length of {$property_schema['minLength']}" );
				}

				if ( isset( $property_schema['format'] ) && $property_schema['format'] === 'date-time' ) {
					if ( ! $this->is_valid_date_time( $value ) ) {
						throw new \InvalidArgumentException( "Property '$key' must be a valid date-time string" );
					}
				}
				break;

			case 'number':
				if ( ! is_numeric( $value ) ) {
					throw new \InvalidArgumentException( "Property '$key' must be a number" );
				}

				if ( isset( $property_schema['minimum'] ) && $value < $property_schema['minimum'] ) {
					throw new \InvalidArgumentException( "Property '$key' is less than minimum value of {$property_schema['minimum']}" );
				}

				if ( isset( $property_schema['maximum'] ) && $value > $property_schema['maximum'] ) {
					throw new \InvalidArgumentException( "Property '$key' is greater than maximum value of {$property_schema['maximum']}" );
				}

				if ( isset( $property_schema['multipleOf'] ) ) {
					$remainder = fmod( $value, $property_schema['multipleOf'] );
					if ( abs( $remainder ) > 0.00001 ) { // Using small epsilon for float comparison
						throw new \InvalidArgumentException( "Property '$key' must be a multiple of {$property_schema['multipleOf']}" );
					}
				}
				break;

			case 'boolean':
				if ( ! is_bool( $value ) ) {
					throw new \InvalidArgumentException( "Property '$key' must be a boolean" );
				}
				break;

			case 'object':
				if ( ! is_object( $value ) && ! is_array( $value ) ) {
					throw new \InvalidArgumentException( "Property '$key' must be an object" );
				}

				if ( isset( $property_schema['properties'] ) ) {
					foreach ( $property_schema['properties'] as $prop_key => $prop_schema ) {
						if ( isset( $value[ $prop_key ] ) ) {
							$this->validate_property( "$key.$prop_key", $value[ $prop_key ], $prop_schema );
						}
					}
				}
				break;

			case 'array':
				if ( ! is_array( $value ) ) {
					throw new \InvalidArgumentException( "Property '$key' must be an array" );
				}

				if ( isset( $property_schema['items'] ) ) {
					foreach ( $value as $index => $item ) {
						if ( isset( $property_schema['items']['oneOf'] ) ) {
							$this->validate_one_of( $key, $item, $property_schema['items']['oneOf'] );
						}
						else {
							$this->validate_property( "$key[$index]", $item, $property_schema['items'] );
						}
					}
				}
				break;
		}
	}

	/**
	 * Generate a UUID v4
	 *
	 * @since 1.9.44
	 *
	 * @return string
	 */
	protected function generate_uuid(): string {

		return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
			mt_rand( 0, 0xffff ),
			mt_rand( 0, 0x0fff ) | 0x4000,
			mt_rand( 0, 0x3fff ) | 0x8000,
			mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
		);
	}

	/**
	 * Generate timestamp in the required format
	 *
	 * @since 1.9.44
	 *
	 * @return float
	 */
	protected function generate_timestamp(): float {

		return round( microtime( TRUE ) * 1000, 2 );
	}

	/**
	 * Generate a random revision ID
	 *
	 * @since 1.9.44
	 *
	 * @return string
	 */
	protected function generate_revision_id(): string {

		return substr( str_shuffle( 'abcdefghijklmnopqrstuvwxyz' ), 0, 10 );
	}

	/**
	 * Sanitize a value for SQL insertion
	 *
	 * @since 1.9.44
	 *
	 * @param mixed $value The value to sanitize
	 *
	 * @return string Sanitized value
	 */
	protected function sanitize_value( $value ): string {

		if ( is_string( $value ) ) {
			return str_replace( "'", "''", $value );
		}

		if ( is_bool( $value ) ) {
			return $value ? '1' : '0';
		}

		if ( is_null( $value ) ) {
			return 'NULL';
		}

		if ( is_array( $value ) || is_object( $value ) ) {
			return str_replace( "'", "''", json_encode( $value ) );
		}

		return (string) $value;

	}

	/**
	 * Check if a string is a valid date-time
	 *
	 * @since 1.9.44
	 *
	 * @param string $date_string The date string to validate
	 *
	 * @return bool Whether the string is a valid date-time
	 */
	private function is_valid_date_time( string $date_string ): bool {

		$date = \DateTime::createFromFormat( 'Y-m-d\TH:i:s', $date_string );

		return $date && $date->format( 'Y-m-d\TH:i:s' ) === $date_string;
	}

	/**
	 * Validate against oneOf schema
	 *
	 * @since 1.9.44
	 *
	 * @param string $key     Property key
	 * @param mixed  $value   Property value
	 * @param array  $schemas Array of possible schemas
	 *
	 * @throws \InvalidArgumentException If validation fails
	 */
	private function validate_one_of( string $key, $value, array $schemas ): void {

		foreach ( $schemas as $schema ) {
			try {
				$this->validate_property( $key, $value, $schema );

				return; // If we get here, validation passed
			} catch ( \InvalidArgumentException $e ) {
				continue; // Try next schema
			}
		}
		throw new \InvalidArgumentException( "Property '$key' does not match any of the allowed schemas" );
	}

	/**
	 * Prepare data for insertion
	 *
	 * @since 1.9.44
	 *
	 * @param array $data Raw data
	 */
	abstract protected function prepare_data( array $data ): array;
}

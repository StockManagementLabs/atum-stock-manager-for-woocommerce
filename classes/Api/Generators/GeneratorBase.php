<?php
/**
 * Generator base class
 *
 * @since        1.9.44
 * @author       BE REBEL - https://berebel.studio
 * @copyright    ©2025 BE REBEL Studio
 *
 * @package      Atum\Api\Generators
 */

namespace Atum\Api\Generators;

use Atum\Api\Controllers\V3\FullExportController;


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
	 * The revision code
	 *
	 * @var string
	 */
	protected string $revision = '';

	/**
	 * GeneratorBase constructor.
	 *
	 * @since 1.9.44
	 *
	 * @param string $table_name The table name with prefix for the SQL statements.
	 * @param string $revision   The revision code.
	 */
	public function __construct( string $table_name, string $revision ) {

		$this->table_name = $table_name;
		$this->revision   = $revision;

		$this->load_schema();

	}

	/**
	 * Load and parse the schema
	 *
	 * @since 1.9.44
	 *
	 * @throws \Exception If the schema file is not found or invalid
	 */
	protected function load_schema() {

		$schema_path = ATUM_PATH . "classes/Api/Schemas/{$this->schema_name}.json";

		if ( ! file_exists( $schema_path ) ) {
			throw new \Exception( "Schema file not found: $schema_path" );
		}

		$schema_content = file_get_contents( $schema_path );
		if ( $schema_content === FALSE ) {
			throw new \Exception( "Unable to read $this->schema_name schema file" );
		}

		$schema = json_decode( $schema_content, TRUE );
		if ( function_exists( 'json_last_error' ) && json_last_error() !== JSON_ERROR_NONE ) {
			throw new \Exception( "Invalid JSON in $this->schema_name schema file: " . json_last_error_msg() );
		}

		$this->schema = $schema;

	}

	/**
	 * Transform JSON addon data to SQL insert records
	 *
	 * @since 1.9.44
	 *
	 * @param array $results The addon data from API response.
	 * @param int[]|null     The pagination data for the current set of results.
	 *
	 * @return string The SQL insert statements.
	 *
	 * @throws \Exception If data validation fails.
	 */
	public function generate_sql_inserts( array $results, $page = NULL ): string {

		// Create the table if not exists.
		$create_sql = "CREATE TABLE IF NOT EXISTS '$this->table_name' ('id' TEXT NOT NULL PRIMARY KEY, 'revision' TEXT, 'deleted' BOOLEAN NOT NULL CHECK (deleted IN (0, 1)), 'lastWriteTime' INTEGER NOT NULL, 'data' json) WITHOUT ROWID;";

		// Prepare and validate data.
		$sql_inserts = [];

		// // Save debug info if debug mode is enabled.
		if ( FullExportController::DEBUG_MODE ) {
			$debug_file = FullExportController::get_full_export_upload_dir() . "{$this->schema_name}_debug.json";
			$debug_json = file_exists( $debug_file ) ? json_decode( file_get_contents( $debug_file ), TRUE ) : [];
		}

		foreach ( $results as $item ) {

			$prepared_data = $this->prepare_data( $item );
			$this->validate_data( $prepared_data );

			if ( FullExportController::DEBUG_MODE ) {
				$debug_json[] = $prepared_data;
			}

			// TODO: CHECK THE IDS USED, RELATIONS AND UUID GENERATED, ETC.
			$sql_inserts[] = sprintf(
				"('%s', '%s', '0', '%s', '%s')",
				Generator::get_current_counter( $this->schema_name ),
				$this->sanitize_value( $this->revision, TRUE ),
				$this->sanitize_value( $prepared_data['_meta']['lwt'] ),
				$this->sanitize_value( json_encode( $prepared_data ), TRUE )
			);

		}

		if ( FullExportController::DEBUG_MODE ) {
			file_put_contents( $debug_file, json_encode( $debug_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_FORCE_OBJECT ) );
		}

		$insert_sql = "INSERT OR REPLACE INTO '$this->table_name' ('id', 'revision', 'deleted', 'lastWriteTime', 'data') VALUES\n" . implode( ",\n", $sql_inserts ) . ';';

		return $this->add_starting_comment() . $create_sql . "\n" . $insert_sql . "\n" . $this->add_ending_comment();

	}

	/**
	 * Validate data against schema
	 *
	 * @since 1.9.44
	 *
	 * @param array $data The data to validate
	 *
	 * @throws \Exception If validation fails
	 */
	protected function validate_data( array $data ): void {

		if ( ! isset( $this->schema['properties'] ) ) {
			throw new \Exception( "Invalid '$this->schema_name' schema structure: missing properties definition" );
		}

		if ( ! empty( $this->schema['required'] ) && is_array( $this->schema['required'] ) ) {
			foreach ( $this->schema['required'] as $required_field ) {
				if ( ! isset( $data[ $required_field ] ) ) {
					throw new \Exception( "Missing required field: '$required_field' according to schema '$this->schema_name'" );
				}
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
	 *
	 * @throws \Exception If validation fails
	 */
	protected function validate_property( string $key, $value, array $property_schema ) {

		// There is no need to validate empty values if they are not required.
		if (
			( NULL === $value || '' === $value ) &&
			( empty( $this->schema['required'] ) || ! in_array( $key, $this->schema['required'], TRUE ) )
		) {
			return;
		}

		if ( empty( $property_schema['type'] ) ) {
			return;
		}

		// Always allow null values.
		if ( is_null( $value) ) {
			return;
		}

		$error          = '';
		$received_value = "\nReceived value: " . var_export( $value, TRUE );

		switch ( $property_schema['type'] ) {
			case 'string':
				if ( ! is_string( $value ) ) {
					// Just leave the numeric values that are compatible with strings.
					if ( ! is_numeric( $value ) ) {
						$error = "Property '$key' must be a string according to schema '$this->schema_name'";
					}
				}
				elseif ( isset( $property_schema['minLength'] ) && strlen( $value ) < $property_schema['minLength'] ) {
					$error = "Property '$key' is shorter than minimum length of {$property_schema['minLength']} according to schema '$this->schema_name'";
				}
				elseif ( isset( $property_schema['format'] ) && $property_schema['format'] === 'date-time' && ! $this->is_valid_date_time( $value ) ) {
					$error = "Property '$key' must be a valid date-time string according to schema '$this->schema_name'";
				}

				break;

			case 'number':
				if ( ! is_numeric( $value ) ) {
					$error = "Property '$key' must be a number according to schema '$this->schema_name'";
				}
				elseif ( isset( $property_schema['minimum'] ) && $value < $property_schema['minimum'] ) {
					$error = "Property '$key' is less than minimum value of {$property_schema['minimum']} according to schema '$this->schema_name'";
				}
				elseif ( isset( $property_schema['maximum'] ) && $value > $property_schema['maximum'] ) {
					$error = "Property '$key' is greater than maximum value of {$property_schema['maximum']} according to schema '$this->schema_name'";
				}
				// NOTE: Commented for now as the only prop that is using it is the _meta.lwt and we are generating the value ourselves.
				/*elseif ( isset( $property_schema['multipleOf'] ) ) {
					$remainder = fmod( $value, $property_schema['multipleOf'] );

					if ( abs( $remainder ) > 0.00001 ) { // Using small epsilon for float comparison
						$error = "Property '$key' must be a multiple of {$property_schema['multipleOf']} according to schema '$this->schema_name'";
					}
				}*/

				break;

			case 'boolean':
				if ( ! is_bool( $value ) ) {
					$error = "Property '$key' must be a boolean according to schema '$this->schema_name'";
				}
				break;

			case 'object':
				if ( ! is_object( $value ) && ! is_array( $value ) ) {
					$error = "Property '$key' must be an object according to schema '$this->schema_name'";
				}
				elseif ( isset( $property_schema['properties'] ) ) {

					foreach ( $property_schema['properties'] as $prop_key => $prop_schema ) {
						if ( isset( $value[ $prop_key ] ) ) {
							$this->validate_property( "$key.$prop_key", $value[ $prop_key ], $prop_schema );
						}
					}

				}

				break;

			case 'array':
				if ( ! is_array( $value ) ) {
					$error = "Property '$key' must be an array according to schema '$this->schema_name'";
				}
				elseif ( isset( $property_schema['items'] ) ) {

					foreach ( $value as $index => $item ) {

						if ( isset( $property_schema['items']['oneOf'] ) ) {
							$this->validate_one_of( $key, $item, $property_schema['items']['oneOf'] );
						}
						else {
							$this->validate_property( "$key.$index", $item, $property_schema['items'] );
						}

					}

				}
				break;

			// Special case for mixed types where the data types in the db are inconsistent.
			case 'mixed':
				if ( ! is_string( $value ) && ! is_numeric( $value ) && ! is_bool( $value ) ) {
					$error = "Property '$key' must be mixed (string or numeric or boolean) type according to schema '$this->schema_name'";
				}
				break;

			default:
				$error = "Unsupported property '$key' type '{$property_schema['type']}' in schema '$this->schema_name'";
				break;
		}

		if ( $error ) {

			if ( is_string( $received_value ) ) {
				$error .= $received_value;
			}

			throw new \Exception( $error );

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
	 * Sanitize a value for SQL insertion
	 *
	 * @since 1.9.44
	 *
	 * @param mixed $value The value to sanitize
	 *
	 * @return string|null Sanitized value
	 */
	protected function sanitize_value( $value, $allow_null = FALSE ): ?string {

		if ( is_string( $value ) ) {
			return str_replace( "'", "''", $value );
		}

		if ( is_bool( $value ) ) {
			return $value ? '1' : '0';
		}

		if ( is_null( $value ) ) {
			return $allow_null ? NULL : '';
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

		// The dates can be in the format YYYY-MM-DD HH:MM:SS or YYYY-MM-DDTHH:MM:SS
		$date = \DateTime::createFromFormat( 'Y-m-d\TH:i:s', $date_string );

		if ( $date ) {
			return $date->format( 'Y-m-d\TH:i:s' ) === $date_string;
		}

		$date = \DateTime::createFromFormat( 'Y-m-d H:i:s', $date_string );

		return $date && $date->format( 'Y-m-d H:i:s' ) === $date_string;

	}

	/**
	 * Validate against oneOf schema
	 *
	 * @since 1.9.44
	 *
	 * @param string $key            Property key.
	 * @param mixed  $value          Property value.
	 * @param array  $allowed_values Array of possible values.
	 *
	 * @throws \Exception If validation fails
	 */
	private function validate_one_of( string $key, $value, array $allowed_values ): void {

		foreach ( $allowed_values as $allowed_value ) {
			try {
				$this->validate_property( $key, $value, $allowed_value );

				return; // If we get here, validation passed.
			} catch ( \Throwable $e ) {
				continue; // Try next schema.
			}
		}

		throw new \Exception( "Property '$key' does not match any of the allowed values: " . implode( ', ', $allowed_values ) );

	}

	/**
	 * Prepare meta data
	 *
	 * @since 1.9.44
	 *
	 * @param array $meta_data Raw meta data.
	 *
	 * @return array Prepared meta data.
	 */
	protected function prepare_meta_data( array $meta_data ): array {

		return array_map( function ( $meta ) {

			// Normalize the value to a string representation.
			$value = $this->normalize_meta_value( $meta['value'] );

			$prepared_meta = [
				'key'   => $meta['key'],
				'value' => $value,
			];

			if ( isset( $meta['id'] ) ) {
				$prepared_meta['id'] = (int) $meta['id'];
			}

			return  $prepared_meta;

		}, $meta_data );

	}

	/**
	 * Normalize meta value to a consistent string representation
	 *
	 * @since 1.9.44
	 *
	 * @param mixed $value The meta value to normalize.
	 *
	 * @return string Normalized value.
	 */
	protected function normalize_meta_value( $value ): string {

		// If it's already a string, return as-is.
		if ( is_string( $value ) ) {
			return $value;
		}

		// If it's a numeric or boolean value, convert to string.
		if ( is_numeric( $value ) || is_bool( $value ) ) {
			return (string) $value;
		}

		// If it's an array or object, JSON encode it.
		if ( is_array( $value ) || is_object( $value ) ) {
			return json_encode( $value );
		}

		// For null or other types, convert to empty string.
		return '';

	}

	/**
	 * Prepare IDs
	 *
	 * @since 1.9.44
	 *
	 * @param string[]|string|null $ids Array of item IDs or a single ID.
	 *
	 * @return array|null Prepared IDs.
	 */
	protected function prepare_ids( $ids ): ?array {

		if ( is_array( $ids ) ) {

			if ( empty( $ids ) ) {
				return [];
			}

			return array_map( function ( $id ) {

				if ( is_numeric( $id ) ) {
					return [
						'id'  => (string) $id,
						'_id' => NULL,
					];
				}

				return NULL;

			}, $ids );

		}

		return is_numeric( $ids ) ? [
			'id'  => (string) $ids,
			'_id' => NULL,
		] : NULL;

	}

	/**
	 * Prepare data for insertion
	 *
	 * @since 1.9.44
	 *
	 * @param array $data Raw data
	 */
	abstract protected function prepare_data( array $data ): array;

	/**
	 * Get base fields
	 *
	 * @since 1.9.44
	 *
	 * @return array
	 */
	protected function get_base_fields() {

		return [
			'_id'          => $this->schema_name . ':' . $this->generate_uuid(),
			'_rev'         => $this->revision,
			'_deleted'     => FALSE,
			'_meta'        => [
				'lwt' => $this->generate_timestamp(),
			],
			'_attachments' => new \stdClass(),
			'trash'        => FALSE,
			'conflict'     => FALSE,
			'deleted'      => FALSE,
		];

	}

	/**
	 * Add a starting comment to the SQL
	 *
	 * @since 1.9.44
	 *
	 * @param int[]|null $page
	 *
	 * @return string
	 */
	protected function add_starting_comment( $page = NULL ) {

		$comment  = "#\n";
		$comment .= "# Schema: `$this->schema_name`\n";

		if ( ! empty( $page ) ) {
			$comment .= "# Page: " . implode( ' of ', $page ) . "\n";
		}

		$comment .= "#\n";

		return $comment;

	}

	/**
	 * Add an ending comment to the SQL
	 *
	 * @since 1.9.44
	 *
	 * @return string
	 */
	protected function add_ending_comment() {

		$comment  = "#\n";
		$comment .= "# End of schema: `$this->schema_name`\n";
		$comment .= "#\n\n";

		return $comment;

	}

	/**
	 * Check if a value is nullable
	 *
	 * @since 1.9.48
	 *
	 * @param mixed $value
	 *
	 * @return bool
	 */
	protected function is_null_value( $value ) {
		return is_null( $value ) || $value === '';
	}
	
	/**
	 * Convert a string to a boolean and control 'global' values
	 *
	 * @since 1.9.49
	 *
	 * @param mixed $value
	 *
	 * @return bool
	 */
	protected function string_to_bool( $value ) {
		return ( 'global' === $value || $this->is_null_value( $value ) ) ? NULL : wc_string_to_bool( $value );
	}

	/**
	 * Prepare tax class data
	 *
	 * @since 1.9.49
	 *
	 * @param string $tax_class The tax class slug.
	 *
	 * @return array|null Prepared tax class data.
	 */
	protected function prepare_tax_class( $tax_class ) {

		$converted_tax_class = NULL;

		if ( ! empty( $tax_class ) ) {
			$converted_tax_class = [
				'_id'  => 'tax-class:' . $this->generate_uuid(),
				'slug' => $tax_class,
				'name' => ucwords( str_replace( '-', ' ', $tax_class ) ) . ' Rate'
			];
		}

		return $converted_tax_class;

	}

}

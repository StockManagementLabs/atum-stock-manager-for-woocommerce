<?php
/**
 * Tax Class generator for SQLite
 *
 * @since       1.9.44
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2025 BE REBEL Studio
 *
 * @package     Atum\Api\Generators
 */

namespace Atum\Api\Generators;

defined( 'ABSPATH' ) || exit;

class TaxClassGenerator extends GeneratorBase {

	/**
	 * The schema name
	 *
	 * @var string
	 */
	protected string $schema_name = 'tax-class';

	/**
	 * Transform tax class data to schema format
	 *
	 * @since 1.9.44
	 *
	 * @param array $tax_class Raw tax class data.
	 *
	 * @return array Prepared tax class data.
	 */
	protected function prepare_data( array $tax_class ): array {

		return array_merge( $this->get_base_fields(), [
			'slug'     => isset( $tax_class['slug'] ) ? $tax_class['slug'] : NULL,
			'name'     => isset( $tax_class['name'] ) ? $tax_class['name'] : NULL,
			'itemType' => 'tax-class',
		] );

	}

} 
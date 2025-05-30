<?php
/**
 * Supplier generator for SQLite
 *
 * @since       1.9.44
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2025 BE REBEL Studio
 *
 * @package     Atum\Api\Generators
 */

namespace Atum\Api\Generators;

defined( 'ABSPATH' ) || exit;

class SupplierGenerator extends GeneratorBase {

	/**
	 * The schema name
	 *
	 * @var string
	 */
	protected string $schema_name = 'supplier';

	/**
	 * Transform supplier data to schema format
	 *
	 * @since 1.9.44
	 *
	 * @param array $supplier Raw supplier data
	 *
	 * @return array Prepared supplier data
	 */
	protected function prepare_data( array $supplier ): array {

		return array_merge( $this->get_base_fields(), [
			'id'              => (string) $supplier['id'],
			'name'            => $supplier['name'] ?? '',
			'description'     => $supplier['description'] ?? NULL,
			'slug'            => $supplier['slug'] ?? '',
			'status'          => $supplier['status'] ?? 'publish',
			'dateCreated'     => $supplier['date_created'] ?? '',
			'dateCreatedGMT'  => $supplier['date_created_gmt'] ?? '',
			'dateModified'    => $supplier['date_modified'] ?? '',
			'dateModifiedGMT' => $supplier['date_modified_gmt'] ?? '',
			'address'         => $supplier['address'] ?? NULL,
			'assignedTo'      => $supplier['assigned_to'] ?? NULL,
			'city'            => $supplier['city'] ?? NULL,
			'country'         => $supplier['country'] ?? NULL,
			'fax'             => $supplier['fax'] ?? NULL,
			'generalEmail'    => $supplier['general_email'] ?? NULL,
			'orderingEmail'   => $supplier['ordering_email'] ?? NULL,
			'orderingUrl'     => $supplier['ordering_url'] ?? NULL,
			'phone'           => $supplier['phone'] ?? NULL,
			'state'           => $supplier['state'] ?? NULL,
			'taxNumber'       => $supplier['tax_number'] ?? NULL,
			'website'         => $supplier['website'] ?? NULL,
			'zipCode'         => $supplier['zip_code'] ?? NULL,
			'location'        => $supplier['location'] ?? NULL,
			'currency'        => $supplier['currency'] ?? NULL,
			'barcode'         => $supplier['atum_barcode'] ?? NULL,
			'code'            => $supplier['code'] ?? NULL,
			'itemType'        => 'supplier',
			'image'           => ! empty( $supplier['image'] ) ? [
				'id'  => (int) $supplier['image']['id'],
				'src' => $supplier['image']['src'] ?? '',
				'alt' => $supplier['image']['alt'] ?? '',
			] : NULL,
		] );

	}

} 
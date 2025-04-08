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
			'id'                => (string) $supplier['id'],
			'name'              => $supplier['name'] ?? '',
			'description'       => $supplier['description'] ?? null,
			'slug'              => $supplier['slug'] ?? '',
			'status'            => $supplier['status'] ?? 'publish',
			'trash'             => false,
			'dateCreated'       => $supplier['date_created'] ?? '',
			'dateCreatedGMT'    => $supplier['date_created_gmt'] ?? '',
			'dateModified'      => $supplier['date_modified'] ?? '',
			'dateModifiedGMT'   => $supplier['date_modified_gmt'] ?? '',
			'address'           => $supplier['address'] ?? null,
			'assignedTo'        => $supplier['assigned_to'] ?? null,
			'city'              => $supplier['city'] ?? null,
			'country'           => $supplier['country'] ?? null,
			'fax'               => $supplier['fax'] ?? null,
			'generalEmail'      => $supplier['general_email'] ?? null,
			'orderingEmail'     => $supplier['ordering_email'] ?? null,
			'orderingUrl'       => $supplier['ordering_url'] ?? null,
			'phone'             => $supplier['phone'] ?? null,
			'state'             => $supplier['state'] ?? null,
			'taxNumber'         => $supplier['tax_number'] ?? null,
			'website'           => $supplier['website'] ?? null,
			'zipCode'           => $supplier['zip_code'] ?? null,
			'location'          => $supplier['location'] ?? null,
			'currency'          => $supplier['currency'] ?? null,
			'barcode'           => $supplier['atum_barcode'] ?? null,
			'code'              => $supplier['code'] ?? null,
			'conflict'          => false,
			'deleted'           => false,
			'itemType'          => 'supplier',
			'image'             => $supplier['thumbnail_id'] ? [
				'id'  => (int) $supplier['thumbnail_id'],
				'src' => '',
				'alt' => ''
			] : null,
		] );

	}

} 
<?php
/**
 * Coupon generator for SQLite
 *
 * @since       1.9.44
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2024 BE REBEL Studio
 *
 * @package     Atum\Api\Generators
 */

namespace Atum\Api\Generators;

defined( 'ABSPATH' ) || exit;

class CouponGenerator extends GeneratorBase {

	/**
	 * The schema name
	 *
	 * @var string
	 */
	protected string $schema_name = 'coupon';

	/**
	 * Prepare coupon data according to schema
	 *
	 * @since 1.9.44
	 *
	 * @param array $coupon Raw coupon data
	 *
	 * @return array Prepared coupon data
	 */
	protected function prepare_data( array $coupon ): array {

		return [
			'_id'                       => 'coupon:' . $this->generate_uuid(),
			'_rev'                      => '1-' . $this->generate_revision_id(),
			'_deleted'                  => FALSE,
			'_meta'                     => [
				'lwt' => $this->generate_timestamp(),
			],
			'_attachments'              => new \stdClass(),
			'id'                        => (int) $coupon['id'],
			'code'                      => $coupon['code'],
			'amount'                    => (float) $coupon['amount'],
			'status'                    => $coupon['status'],
			'discountType'              => $coupon['discount_type'],
			'description'               => $coupon['description'] ?? '',
			'dateExpires'               => $coupon['date_expires'],
			'dateExpiresGMT'            => $coupon['date_expires_gmt'],
			'usageCount'                => (int) $coupon['usage_count'],
			'individualUse'             => (bool) $coupon['individual_use'],
			'products'                  => $this->prepare_product_references( $coupon['product_ids'] ?? [] ),
			'excludedProducts'          => $this->prepare_product_references( $coupon['excluded_product_ids'] ?? [] ),
			'usageLimit'                => $coupon['usage_limit'] ? (int) $coupon['usage_limit'] : NULL,
			'usageLimitPerUser'         => $coupon['usage_limit_per_user'] ? (int) $coupon['usage_limit_per_user'] : NULL,
			'usageLimitPerItems'        => $coupon['limit_usage_to_x_items'] ? (int) $coupon['limit_usage_to_x_items'] : NULL,
			'freeShipping'              => (bool) $coupon['free_shipping'],
			'productCategories'         => $this->prepare_category_references( $coupon['product_categories'] ?? [] ),
			'excludedProductCategories' => $this->prepare_category_references( $coupon['excluded_product_categories'] ?? [] ),
			'excludeSaleItems'          => (bool) $coupon['exclude_sale_items'],
			'minimumAmount'             => (float) $coupon['minimum_amount'],
			'maximumAmount'             => (float) $coupon['maximum_amount'],
			'emailRestrictions'         => $coupon['email_restrictions'] ?? [],
			'usedBy'                    => $this->prepare_used_by( $coupon['used_by'] ?? [] ),
			'metaData'                  => $this->prepare_meta_data( $coupon['meta_data'] ?? [] ),
			'trash'                     => FALSE,
			'conflict'                  => FALSE,
		];

	}

	/**
	 * Prepare product references
	 *
	 * @since 1.9.44
	 *
	 * @param array $product_ids Array of product IDs
	 *
	 * @return array Prepared product references
	 */
	private function prepare_product_references( array $product_ids ): array {

		return array_map( function ( $id ) {

			return [
				'id'  => (int) $id,
				'_id' => 'product:' . $this->generate_uuid(),
			];

		}, $product_ids );
	}

	/**
	 * Prepare category references
	 *
	 * @since 1.9.44
	 *
	 * @param array $category_ids Array of category IDs
	 *
	 * @return array Prepared category references
	 */
	private function prepare_category_references( array $category_ids ): array {

		return array_map( function ( $id ) {

			return [
				'id'  => (int) $id,
				'_id' => 'category:' . $this->generate_uuid(),
			];

		}, $category_ids );
	}

	/**
	 * Prepare used by data
	 *
	 * @since 1.9.44
	 *
	 * @param array $used_by Array of user IDs or emails
	 *
	 * @return array Prepared used by data
	 */
	private function prepare_used_by( array $used_by ): array {

		return array_map( function ( $user ) {

			if ( is_numeric( $user ) ) {
				return [
					'id'  => (int) $user,
					'_id' => 'user:' . $this->generate_uuid(),
				];
			}

			return $user; // Email string

		}, $used_by );
	}

	/**
	 * Prepare meta data
	 *
	 * @since 1.9.44
	 *
	 * @param array $meta_data Array of meta data
	 *
	 * @return array Prepared meta data
	 */
	private function prepare_meta_data( array $meta_data ): array {

		return array_map( function ( $meta ) {

			return [
				'id'    => (int) $meta['id'],
				'key'   => $meta['key'],
				'value' => (string) $meta['value'],
			];

		}, $meta_data );
	}

} 
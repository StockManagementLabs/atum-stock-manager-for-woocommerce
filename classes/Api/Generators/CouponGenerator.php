<?php
/**
 * Coupon generator for SQLite
 *
 * @since       1.9.44
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2025 BE REBEL Studio
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

		return array_merge( $this->get_base_fields(), [
			'id'                        => (string) $coupon['id'],
			'slug'                      => $coupon['code'],
			'uid'                       => null,
			'itemType'                  => 'coupon',
			'code'                      => $coupon['code'],
			'amount'                    => (float) $coupon['amount'],
			'status'                    => $coupon['status'],
			'dateCreated'               => $coupon['date_created'] ?? null,
			'dateCreatedGMT'            => $coupon['date_created_gmt'] ?? null,
			'dateModified'              => $coupon['date_modified'] ?? null,
			'dateModifiedGMT'           => $coupon['date_modified_gmt'] ?? null,
			'discountType'              => $coupon['discount_type'],
			'description'               => $coupon['description'] ?? null,
			'dateExpires'               => $coupon['date_expires'] ?? null,
			'dateExpiresGMT'            => $coupon['date_expires_gmt'] ?? null,
			'usageCount'                => (int) $coupon['usage_count'],
			'individualUse'             => (bool) $coupon['individual_use'],
			'products'                  => $this->prepare_ids( $coupon['product_ids'] ?? [] ),
			'excludedProducts'          => $this->prepare_ids( $coupon['excluded_product_ids'] ?? [] ),
			'usageLimit'                => $coupon['usage_limit'] ? (int) $coupon['usage_limit'] : null,
			'usageLimitPerUser'         => $coupon['usage_limit_per_user'] ? (int) $coupon['usage_limit_per_user'] : null,
			'usageLimitPerItems'        => $coupon['limit_usage_to_x_items'] ? (int) $coupon['limit_usage_to_x_items'] : null,
			'freeShipping'              => (bool) $coupon['free_shipping'],
			'productCategories'         => $this->prepare_ids( $coupon['product_categories'] ?? [] ),
			'excludedProductCategories' => $this->prepare_ids( $coupon['excluded_product_categories'] ?? [] ),
			'excludeSaleItems'          => (bool) $coupon['exclude_sale_items'],
			'minimumAmount'             => (float) $coupon['minimum_amount'],
			'maximumAmount'             => (float) $coupon['maximum_amount'],
			'emailRestrictions'         => $coupon['email_restrictions'] ?? [],
			'usedBy'                    => $this->prepare_ids( $coupon['used_by'] ?? [] ),
			'metaData'                  => $this->prepare_meta_data( $coupon['meta_data'] ?? [] ),
		] );

	}

} 
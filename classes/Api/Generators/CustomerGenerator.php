<?php
/**
 * Customer generator for SQLite
 *
 * @since       1.9.44
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2025 BE REBEL Studio
 *
 * @package     Atum\Api\Generators
 */

namespace Atum\Api\Generators;

defined( 'ABSPATH' ) || exit;

class CustomerGenerator extends GeneratorBase {

	/**
	 * The schema name
	 *
	 * @var string
	 */
	protected string $schema_name = 'customer';

	/**
	 * Prepare customer data according to schema
	 *
	 * @since 1.9.44
	 *
	 * @param array $customer Raw customer data.
	 *
	 * @return array Prepared customer data.
	 */
	protected function prepare_data( array $customer ): array {

		return array_merge( $this->get_base_fields(), [
			'id'               => (int) $customer['id'],
			'dateCreated'      => $customer['date_created'],
			'dateCreatedGMT'   => $customer['date_created_gmt'],
			'dateModified'     => $customer['date_modified'],
			'dateModifiedGMT'  => $customer['date_modified_gmt'],
			'email'            => $customer['email'],
			'firstName'        => $customer['first_name'],
			'lastName'         => $customer['last_name'],
			'role'             => $customer['role'],
			'username'         => $customer['username'],
			'billing'          => $this->prepare_billing_data( $customer['billing'] ),
			'shipping'         => $this->prepare_shipping_data( $customer['shipping'] ),
			'isPayingCustomer' => (bool) $customer['is_paying_customer'],
			'avatarUrl'        => $customer['avatar_url'],
			'trash'            => FALSE,
			'conflict'         => FALSE,
		] );

	}

	/**
	 * Prepare billing address data
	 *
	 * @since 1.9.44
	 *
	 * @param array $billing Raw billing data
	 *
	 * @return array Prepared billing data
	 */
	private function prepare_billing_data( array $billing ): array {

		return [
			'firstName' => $billing['first_name'],
			'lastName'  => $billing['last_name'],
			'company'   => $billing['company'],
			'address1'  => $billing['address_1'],
			'address2'  => $billing['address_2'],
			'city'      => $billing['city'],
			'state'     => $billing['state'],
			'postcode'  => $billing['postcode'],
			'country'   => $billing['country'],
			'email'     => $billing['email'],
			'phone'     => $billing['phone'],
		];
	}

	/**
	 * Prepare shipping address data
	 *
	 * @since 1.9.44
	 *
	 * @param array $shipping Raw shipping data
	 *
	 * @return array Prepared shipping data
	 */
	private function prepare_shipping_data( array $shipping ): array {

		return [
			'firstName' => $shipping['first_name'],
			'lastName'  => $shipping['last_name'],
			'company'   => $shipping['company'],
			'address1'  => $shipping['address_1'],
			'address2'  => $shipping['address_2'],
			'city'      => $shipping['city'],
			'state'     => $shipping['state'],
			'postcode'  => $shipping['postcode'],
			'country'   => $shipping['country'],
			'phone'     => $shipping['phone'],
		];
	}

} 
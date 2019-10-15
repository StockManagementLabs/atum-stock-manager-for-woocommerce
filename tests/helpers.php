<?php

namespace TestHelpers;

use Atum\Inc\Helpers;
use Atum\Models\Products\AtumProductSimple;
use Atum\PurchaseOrders\PurchaseOrders;
use WC_Cache_Helper;
use WC_Order;
use WC_Order_Item_Product;
use WC_Order_Item_Shipping;
use WC_Product;
use WC_Product_Variable;
use WC_Product_Variation;
use WC_Shipping_Rate;
use WC_Tax;
use WP_Error;
use WC_Product_Attribute;


class TestHelpers {

	public static function count_public_methods( $object ) {
		if( !is_object( $object ) )
			return false;

		$result = [];

		foreach( $object as $att => $value ) {
			$result['methods'][] = $att;
			$result['num'] ++ ;
		}
		return $result;
	}

	public static function has_action( $tag, $function ) {
		global $wp_filter;

		if( !isset ( $wp_filter[ $tag ] ) )
			return false;

		$hook = $wp_filter[ $tag ];

		foreach ( $hook->callbacks as $priority => $call ) {
			foreach ( $call as $idx => $data ) {
				if( $data['function'][0] instanceof $function[0] && $data['function'][1] === $function[1] )
					return $priority;
			}
		}
		return false;
	}

	public static function create_atum_purchase_order( $product = null ) {
		wp_set_current_user( 1 );
		$pos = new PurchaseOrders();
		$pos->register_post_type();

		$post = wp_insert_post( array(
			'post_title'  => 'Purchase Order #xxxx details',
			'post_type'   => PurchaseOrders::POST_TYPE,
			'description' => 'Some description',
			'user_ID'     => 1,
			'post_author' => 1,
			'post_status' => 'atum_ordered',
		) );

		$order = Helpers::get_atum_order_model( $post );
		if( !is_a( $product, WC_Product::class ) )
			$product = self::create_atum_product();

		$product->set_inbound_stock( 25 );

		$order->save();

		$item = $order->add_product( $product->get_id(), 25 );
		$item->save();


		return $order;
	}

	public static function create_atum_product( $product = false ) {
		$product = new AtumProductSimple( ( false === $product ) ? self::create_product() : $product );
		$product->set_props(
			array(
				'name'          => 'Dummy Product',
				'regular_price' => 10,
				'price'         => 10,
				'sku'           => 'DUMMY SKU',
				'manage_stock'  => true,
				'tax_status'    => 'taxable',
				'downloadable'  => false,
				'virtual'       => false,
				'stock_status'  => 'instock',
				'weight'        => '1.1',
				'inbound_stock' => 16,
			)
		);
		$product->set_manage_stock( TRUE );
		$product->save();
		return $product;
	}

	/**
	 * Creates a Order object.
	 *
	 * @param null $product
	 * @param int  $customer_id
	 *
	 * @return WC_Order|WP_Error
	 * @throws WC_Data_Exception
	 */
	public static function create_order( $product = null, $customer_id = 1 ) {
		if ( ! is_a( $product, 'WC_Product' ) ) {
			$product = self::create_product();
		}

		$flat_rate_settings = array(
			'enabled'      => 'yes',
			'title'        => 'Flat rate',
			'availability' => 'all',
			'countries'    => '',
			'tax_status'   => 'taxable',
			'cost'         => '10',
		);

		update_option( 'woocommerce_flat_rate_settings', $flat_rate_settings );
		update_option( 'woocommerce_flat_rate', array() );
		WC_Cache_Helper::get_transient_version( 'shipping', true );
		WC()->shipping()->load_shipping_methods();

		$order_data = array(
			'status'        => 'pending',
			'customer_id'   => $customer_id,
			'customer_note' => '',
			'total'         => '',
		);

		$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
		$order                  = wc_create_order( $order_data );

		// Add order products.
		$item = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'product'  => $product,
				'quantity' => 4,
				'subtotal' => wc_get_price_excluding_tax( $product, array( 'qty' => 4 ) ),
				'total'    => wc_get_price_excluding_tax( $product, array( 'qty' => 4 ) ),
			)
		);
		$item->save();
		$order->add_item( $item );

		// Set billing address.
		$order->set_billing_first_name( 'Jeroen' );
		$order->set_billing_last_name( 'Sormani' );
		$order->set_billing_company( 'WooCompany' );
		$order->set_billing_address_1( 'WooAddress' );
		$order->set_billing_address_2( '' );
		$order->set_billing_city( 'WooCity' );
		$order->set_billing_state( 'NY' );
		$order->set_billing_postcode( '123456' );
		$order->set_billing_country( 'US' );
		$order->set_billing_email( 'admin@example.org' );
		$order->set_billing_phone( '555-32123' );

		// Add shipping costs.
		$shipping_taxes = WC_Tax::calc_shipping_tax( '10', WC_Tax::get_shipping_tax_rates() );
		$rate           = new WC_Shipping_Rate( 'flat_rate_shipping', 'Flat rate shipping', '10', $shipping_taxes, 'flat_rate' );
		$item           = new WC_Order_Item_Shipping();
		$item->set_props(
			array(
				'method_title' => $rate->label,
				'method_id'    => $rate->id,
				'total'        => wc_format_decimal( $rate->cost ),
				'taxes'        => $rate->taxes,
			)
		);
		foreach ( $rate->get_meta_data() as $key => $value ) {
			$item->add_meta_data( $key, $value, true );
		}
		$order->add_item( $item );

		// Set payment gateway.
		$payment_gateways = WC()->payment_gateways->payment_gateways();
		$order->set_payment_method( $payment_gateways['bacs'] );

		// Set totals.
		$order->set_shipping_total( 10 );
		$order->set_discount_total( 0 );
		$order->set_discount_tax( 0 );
		$order->set_cart_tax( 0 );
		$order->set_shipping_tax( 0 );
		$order->set_total( 50 ); // 4 x $10 simple helper product
		$order->save();

		return $order;
	}

	/**
	 * Creates a Product
	 *
	 * @return false|WC_Product|null
	 */
	public static function create_product() {
		$product = new WC_Product();
		$product->set_props(
			array(
				'name'          => 'Dummy Product',
				'regular_price' => 10,
				'price'         => 10,
				'sku'           => 'DUMMY SKU',
				'manage_stock'  => false,
				'tax_status'    => 'taxable',
				'downloadable'  => false,
				'virtual'       => false,
				'stock_status'  => 'instock',
				'weight'        => '1.1',
			)
		);
		$product->save();
		return wc_get_product( $product->get_id() );
	}

	/**
	 * Creates a Variable Product
	 *
	 * @param bool $return_child
	 *
	 * @return false|WC_Product|null
	 */
	public static function create_variation_product( $return_child = false ) {
		$product = new WC_Product_Variable();
		$product->set_props(
			array(
				'name' => 'Dummy Variable Product',
				'sku'  => 'DUMMY VARIABLE SKU',
			)
		);

		$attribute_data = self::create_attribute( 'size', array( 'small', 'large' ) ); // Create all attribute related things.
		$attributes     = array();
		$attribute      = new WC_Product_Attribute();
		$attribute->set_id( $attribute_data['attribute_id'] );
		$attribute->set_name( $attribute_data['attribute_taxonomy'] );
		$attribute->set_options( $attribute_data['term_ids'] );
		$attribute->set_position( 1 );
		$attribute->set_visible( true );
		$attribute->set_variation( true );
		$attributes[] = $attribute;

		$product->set_attributes( $attributes );
		$product->save();

		$variation_1 = new WC_Product_Variation();
		$variation_1->set_props(
			array(
				'parent_id'     => $product->get_id(),
				'sku'           => 'DUMMY SKU VARIABLE SMALL',
				'regular_price' => 10,
			)
		);
		$variation_1->set_attributes( array( 'pa_size' => 'small' ) );
		$variation_1->save();

		$variation_2 = new WC_Product_Variation();
		$variation_2->set_props(
			array(
				'parent_id'     => $product->get_id(),
				'sku'           => 'DUMMY SKU VARIABLE LARGE',
				'regular_price' => 15,
			)
		);
		$variation_2->set_attributes( array( 'pa_size' => 'large' ) );
		$variation_2->save();

		return wc_get_product( $return_child ? $variation_1->get_id() : $product->get_id() );
	}

	/**
	 * Creates attribute
	 *
	 * @param string $raw_name
	 * @param array  $terms
	 *
	 * @return array
	 */
	public static function create_attribute( $raw_name = 'size', $terms = array( 'small' ) ) {
		global $wpdb, $wc_product_attributes;

		// Make sure caches are clean.
		delete_transient( 'wc_attribute_taxonomies' );
		WC_Cache_Helper::incr_cache_prefix( 'woocommerce-attributes' );

		// These are exported as labels, so convert the label to a name if possible first.
		$attribute_labels = wp_list_pluck( wc_get_attribute_taxonomies(), 'attribute_label', 'attribute_name' );
		$attribute_name   = array_search( $raw_name, $attribute_labels, true );

		if ( ! $attribute_name ) {
			$attribute_name = wc_sanitize_taxonomy_name( $raw_name );
		}

		$attribute_id = wc_attribute_taxonomy_id_by_name( $attribute_name );

		if ( ! $attribute_id ) {
			$taxonomy_name = wc_attribute_taxonomy_name( $attribute_name );

			// Degister taxonomy which other tests may have created...
			unregister_taxonomy( $taxonomy_name );

			$attribute_id = wc_create_attribute(
				array(
					'name'         => $raw_name,
					'slug'         => $attribute_name,
					'type'         => 'select',
					'order_by'     => 'menu_order',
					'has_archives' => 0,
				)
			);

			// Register as taxonomy.
			register_taxonomy(
				$taxonomy_name,
				apply_filters( 'woocommerce_taxonomy_objects_' . $taxonomy_name, array( 'product' ) ),
				apply_filters(
					'woocommerce_taxonomy_args_' . $taxonomy_name,
					array(
						'labels'       => array(
							'name' => $raw_name,
						),
						'hierarchical' => false,
						'show_ui'      => false,
						'query_var'    => true,
						'rewrite'      => false,
					)
				)
			);

			// Set product attributes global.
			$wc_product_attributes = array();

			foreach ( wc_get_attribute_taxonomies() as $taxonomy ) {
				$wc_product_attributes[ wc_attribute_taxonomy_name( $taxonomy->attribute_name ) ] = $taxonomy;
			}
		}

		$attribute = wc_get_attribute( $attribute_id );
		$return    = array(
			'attribute_name'     => $attribute->name,
			'attribute_taxonomy' => $attribute->slug,
			'attribute_id'       => $attribute_id,
			'term_ids'           => array(),
		);

		foreach ( $terms as $term ) {
			$result = term_exists( $term, $attribute->slug );

			if ( ! $result ) {
				$result               = wp_insert_term( $term, $attribute->slug );
				$return['term_ids'][] = $result['term_id'];
			} else {
				$return['term_ids'][] = $result['term_id'];
			}
		}

		return $return;
	}
}
<?php
/**
 * Extends the Purchase Order Class and exports it as PDF
 *
 * @package         Atum\PurchaseOrders
 * @subpackage      Exports
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2021 Stock Management Labs™
 *
 * @since           1.3.9
 */

namespace Atum\PurchaseOrders\Exports;

defined( 'ABSPATH' ) || die;

use Atum\Inc\Helpers;
use Atum\PurchaseOrders\Models\PurchaseOrder;
use Atum\PurchaseOrders\PurchaseOrders;
use Atum\Suppliers\Supplier;
use Mpdf\Mpdf;
use Mpdf\MpdfException;
use Mpdf\Output\Destination;


class POExport extends PurchaseOrder {
	
	/**
	 * The company data
	 *
	 * @var array
	 */
	private $company_data = [];

	/**
	 * The shipping data
	 *
	 * @var array
	 */
	private $shipping_data = [];

	/**
	 * Only for PDF debugging during development.
	 *
	 * @var bool
	 */
	private $debug_mode = FALSE;

	
	/**
	 * POExport constructor
	 *
	 * @since 1.3.9
	 *
	 * @param int $id
	 */
	public function __construct( $id = 0 ) {
		
		$post_type = get_post_type( $id );
		
		if ( PurchaseOrders::get_post_type() !== $post_type ) {
			/* translators: the post ID */
			wp_die( sprintf( esc_html__( 'Not a Purchase Order (%d)', ATUM_TEXT_DOMAIN ), (int) $id ) );
		}
		
		// Always read items.
		parent::__construct( $id );
		
		$this->load_extra_data();
		
	}

	/**
	 * Get all extra data not present in a PO by default
	 *
	 * TODO: THIS NEEDS A FULL REFACTORY AND TO CREATE A MODEL FOR THE STORE DETAILS. ALSO NOT IT SHOULDN'T STORE SEPARATED META KEYS.
	 *
	 * @since 1.3.9
	 */
	private function load_extra_data() {
		
		$default_country = get_option( 'woocommerce_default_country' );
		$country_state   = wc_format_country_state_string( Helpers::get_option( 'country', $default_country ) );

		// Company data.
		$this->company_data = array(
			'company'    => Helpers::get_option( 'company_name' ),
			'address_1'  => Helpers::get_option( 'address_1' ),
			'address_2'  => Helpers::get_option( 'address_2' ),
			'city'       => Helpers::get_option( 'city' ),
			'state'      => $country_state['state'],
			'postcode'   => Helpers::get_option( 'zip' ),
			'country'    => $country_state['country'],
			'tax_number' => Helpers::get_option( 'tax_number' ),
		);
		
		if ( 'yes' === Helpers::get_option( 'same_ship_address' ) ) {
			$this->shipping_data = $this->company_data;
		}
		else {

			// Shipping data.
			$country_state = wc_format_country_state_string( Helpers::get_option( 'ship_country', $default_country ) );
			
			$this->shipping_data = array(
				'company'   => Helpers::get_option( 'ship_to' ),
				'address_1' => Helpers::get_option( 'ship_address_1' ),
				'address_2' => Helpers::get_option( 'ship_address_2' ),
				'city'      => Helpers::get_option( 'ship_city' ),
				'state'     => $country_state['state'],
				'postcode'  => Helpers::get_option( 'ship_zip' ),
				'country'   => $country_state['country'],
			);

		}
		
	}

	/**
	 * Return header content if exist
	 *
	 * @since 1.3.9
	 *
	 * @return string
	 */
	public function get_content() {
		
		$total_text_colspan = 3;
		$post_type          = get_post_type_object( get_post_type( $this->get_id() ) );
		$currency           = $this->currency;
		$discount           = $this->get_total_discount();

		if ( $discount ) {
			$desc_percent = 50;
			$total_text_colspan++;
		}
		else {
			$desc_percent = 60;
		}

		$taxes               = $this->get_taxes();
		$n_taxes             = count( $taxes );
		$desc_percent       -= $n_taxes * 10;
		$total_text_colspan += $n_taxes;

		$line_items_fee      = $this->get_items( 'fee' );
		$line_items_shipping = $this->get_items( 'shipping' );
		$po                  = $this;
		
		ob_start();

		Helpers::load_view( 'reports/purchase-order-html', compact( 'po', 'total_text_colspan', 'post_type', 'currency', 'discount', 'desc_percent', 'taxes', 'n_taxes', 'line_items_fee', 'line_items_shipping' ) );

		return ob_get_clean();
		
	}
	
	/**
	 * Return formatted company address
	 *
	 * @return string
	 */
	public function get_company_address() {
		
		return apply_filters( 'atum/purchase_orders/po_export/company_address', WC()->countries->get_formatted_address( $this->company_data ), $this->company_data );

	}
	
	/**
	 * Return formatted supplier address (includes VAT number if saved)
	 *
	 * @return string
	 */
	public function get_supplier_address() {
		
		$address     = '';
		$supplier_id = $this->get_supplier( 'id' );
		
		if ( $supplier_id ) {

			$supplier = new Supplier( $supplier_id );
			
			$address = WC()->countries->get_formatted_address( array(
				'first_name' => $supplier->name,
				'company'    => $supplier->tax_number,
				'address_1'  => $supplier->address,
				'city'       => $supplier->city,
				'state'      => $supplier->state,
				'postcode'   => $supplier->zip_code,
				'country'    => $supplier->country,
			) );
			
		}
		
		return apply_filters( 'atum/purchase_orders/po_export/supplier_address', $address, $supplier_id );
		
	}
	
	/**
	 * Return formatted company address
	 *
	 * @since 1.3.9
	 *
	 * @return string
	 */
	public function get_shipping_address() {
		
		return apply_filters( 'atum/purchase_orders/po_export/shipping_address', WC()->countries->get_formatted_address( $this->shipping_data ), $this->shipping_data, $this->id );
		
	}

	/**
	 * Getter for the company's Tax/VAT number
	 *
	 * @since 1.7.0
	 *
	 * @return string
	 */
	public function get_tax_number() {

		return $this->company_data['tax_number'];
	}

	/**
	 * Return an array with stylesheets needed to include in the pdf
	 *
	 * @since 1.3.9
	 *
	 * @param string $output Whether the output array of stylesheets are returned as a path or as an URL.
	 *
	 * @return array
	 */
	public function get_stylesheets( $output = 'path' ) {
		
		$prefix = 'url' === $output ? ATUM_URL : ATUM_PATH;
		
		return apply_filters( 'atum/purchase_orders/po_export/css', array( $prefix . 'assets/css/atum-po-export.css' ), $output, $this );
	}

	/**
	 * Getter for the debug mode
	 *
	 * @since 1.9.1
	 */
	public function get_debug_mode() {
		return $this->debug_mode;
	}

	/**
	 * Generate the PO PDF
	 *
	 * @since 1.9.1
	 *
	 * @param Destination $destination_mode
	 *
	 * @return string|\WP_Error
	 *
	 * @throws \Mpdf\MpdfException
	 */
	public function generate_pdf( $destination_mode = Destination::INLINE ) {

		try {

			$is_debug_mode = TRUE === $this->debug_mode;
			$uploads       = wp_upload_dir();
			$temp_dir      = $uploads['basedir'] . apply_filters( 'atum/purchase_orders/po_export/temp_pdf_dir', '/atum' );

			if ( ! is_dir( $temp_dir ) ) {

				// Try to create it.
				$success = mkdir( $temp_dir, 0777, TRUE );

				// If can't create it, use default uploads folder.
				if ( ! $success || ! is_writable( $temp_dir ) ) {
					$temp_dir = $uploads['basedir'];
				}

			}

			do_action( 'atum/purchase_orders/po_export/generate_pdf', $this->id );

			$mpdf = new Mpdf( [
				'mode'    => 'utf-8',
				'format'  => 'A4',
				'tempDir' => $temp_dir,
			] );

			// Add support for non-Latin languages.
			$mpdf->useAdobeCJK      = TRUE; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$mpdf->autoScriptToLang = TRUE; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$mpdf->autoLangToFont   = TRUE; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

			$mpdf->SetTitle( __( 'Purchase Order', ATUM_TEXT_DOMAIN ) );

			$mpdf->default_available_fonts = $mpdf->available_unifonts;

			$debug_html = '';
			$css        = $this->get_stylesheets( $is_debug_mode ? 'url' : 'path' );

			foreach ( $css as $file ) {

				if ( $is_debug_mode ) {
					$debug_html .= '<link rel="stylesheet" href="' . $file . '" media="all">'; // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet
				}
				else {
					$stylesheet = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions
					$mpdf->WriteHTML( $stylesheet, 1 );
				}

			}

			if ( $is_debug_mode ) {
				$debug_html .= $this->get_content();

				return $debug_html;
			}

			$mpdf->WriteHTML( $this->get_content() );

			return $mpdf->Output( "po-{$this->id}.pdf", $destination_mode );

		} catch ( MpdfException $e ) {
			return new \WP_Error( 'atum_pdf_generation_error', $e->getMessage() );
		}

	}

}

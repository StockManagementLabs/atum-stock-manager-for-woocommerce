<?php
/**
 * @package         Atum\DataExport
 * @subpackage      Models
 * @author          Salva Machí and Jose Piera - https://sispixels.com
 * @copyright       ©2017 Stock Management Labs™
 *
 * @since           1.3.9
 *
 * Extends the Purchase Order Class and exports it as HTML Model
 */

namespace Atum\DataExport\Models;

defined( 'ABSPATH' ) or die;

use Atum\Inc\Helpers;
use Atum\PurchaseOrders\Models\PurchaseOrder;
use Atum\PurchaseOrders\PurchaseOrders;


class POModel extends PurchaseOrder implements ModelInterface {
	
	/**
	 * Our company name
	 *
	 * @var array
	 */
	private $company_data = [];
	private $shipping_data = [];
	
	/**
	 * POModel constructor.
	 *
	 * @param int $id
	 */
	public function __construct( $id = 0 ) {
		
		$post_type = get_post_type( $id );
		
		if ( $post_type != PurchaseOrders::get_post_type() ) {
			wp_die( sprintf( __( 'Not a Purchase Order (%d)', ATUM_TEXT_DOMAIN ), $id ) );
		}
		
		// Always read items
		parent::__construct( $id );
		
		$this->load_extra_data();
		
	}
	
	/**
 * Get all extra data not present in a PO by default
 */
	private function load_extra_data() {
		
		$default_country = get_option( 'woocommerce_default_country' );
		// Company data
		$country_state = wc_format_country_state_string( Helpers::get_option( 'country', $default_country ) );
		
		$this->company_data = array(
			'company'   => Helpers::get_option( 'company_name' ),
			'address_1' => Helpers::get_option( 'address_1' ),
			'address_2' => Helpers::get_option( 'address_2' ),
			'city'      => Helpers::get_option( 'city' ),
			'state'     => $country_state['state'],
			'postcode'  => Helpers::get_option( 'zip' ),
			'country'   => $country_state['country']
		);
		
		if ( Helpers::get_option( 'same_ship_address' ) == 'yes' ) {
			$this->shipping_data = $this->company_data;
		}
		else {
			// Shipping data
			$country_state = wc_format_country_state_string( Helpers::get_option( 'ship_country', $default_country ) );
			
			$this->shipping_data = array(
				'company'   => Helpers::get_option( 'ship_to' ),
				'address_1' => Helpers::get_option( 'ship_address_1' ),
				'address_2' => Helpers::get_option( 'ship_address_2' ),
				'city'      => Helpers::get_option( 'ship_city' ),
				'state'     => $country_state['state'],
				'postcode'  => Helpers::get_option( 'ship_zip' ),
				'country'   => $country_state['country']
			);
		}
		
	}
	
	/**
	 * {@inheritdoc}
	 *
	 * @return string|void
	 */
	public function get_header() {
		
		return;
	}
	
	/**
	 * {@inheritdoc}
	 *
	 * @return string|void
	 */
	public function get_footer() {
		
		return;
	}
	
	/**
	 * {@inheritdoc}
	 *
	 * @return string
	 */
	public function get_content() {
		
		$total_text_colspan = 3;
		$post_type          = get_post_type_object( get_post_type( $this->get_id() ) );
		$currency           = $this->get_currency();
		$discount           = $this->get_total_discount();
		if ( $discount ) {
			$desc_percent = 50;
			$total_text_colspan ++;
		}
		else {
			$desc_percent = 60;
		}
		$taxes              = $this->get_taxes();
		$n_taxes            = count( $taxes );
		$desc_percent       -= $n_taxes * 10;
		$total_text_colspan += $n_taxes;
		
		$line_items_fee      = $this->get_items( 'fee' );
		$line_items_shipping = $this->get_items( 'shipping' );
		
		
		ob_start();
		?>
		<div class="po-wrapper content-header">
			<div class="float-left">
				<strong><?php echo preg_replace( '/<br/', '</strong><br', $this->get_company_address(), 1 ) ?>
			</div>
			<div class="float-right">
				<h3 class="po-title"><?php _e( 'PURCHASE ORDER', ATUM_TEXT_DOMAIN ) ?></h3>
				<div class="content-header-po-data">
					<div class="row">
						<span class="label">Date:&nbsp;&nbsp;</span>
						<span class="field"><?php echo date_i18n( get_option( 'date_format' ), strtotime( $this->get_date() ) ) ?></span>
					</div>
					<div class="row">
						<span class="label">P.O. #:&nbsp;&nbsp;</span>
						<span class="field"><?php echo $this->get_id() ?></span>
					</div>
				</div>
			</div>
			<div class="spacer" style="clear: both;"></div>
		</div>
		<div class="po-wrapper content-address">
			<div class="float-left">
				<h4><?php _e( 'VENDOR', ATUM_TEXT_DOMAIN ) ?></h4>
				<p class="address">
					<?php echo $this->get_supplier_address() ?>
				</p>
			</div>
			<div class="float-right">
				<h4><?php _e( 'SHIP TO', ATUM_TEXT_DOMAIN ) ?></h4>
				<p class="address">
					<?php echo $this->get_shipping_address() ?>
				</p>
			</div>
			<div class="spacer" style="clear: both;"></div>
		</div>
		<div class="po-wrapper content-lines">
			<table class="">
				<thead>
				<tr class="po-li-head">
					<th class="description" style="width:<?php echo $desc_percent ?>%"><?php _e( 'DESCRIPTION', ATUM_TEXT_DOMAIN ) ?></th>
					<th class="qty"><?php _e( 'QTY', ATUM_TEXT_DOMAIN ) ?></th>
					<th class="price"><?php _e( 'UNIT PRICE', ATUM_TEXT_DOMAIN ) ?></th>
					<?php if ( $discount ): ?>
						<th class="discount"><?php _e( 'DISCOUNT', ATUM_TEXT_DOMAIN ) ?></th>
					<?php endif; ?>
					<?php if ( ! empty( $taxes ) ) :
						
						foreach ( $taxes as $tax_id => $tax_item ) :
							$column_label = ! empty( $tax_item['label'] ) ? $tax_item['label'] : __( 'Tax', ATUM_TEXT_DOMAIN );
							?>
							<th class="tax">
								<?php echo esc_attr( $column_label ); ?>
							</th>
						<?php
						
						endforeach;
					
					endif; ?>
					<th class="total"><?php _e( 'TOTAL', ATUM_TEXT_DOMAIN ) ?></th>
				</tr>
				</thead>
				<tbody class="po-lines">
				<?php foreach ( $this->get_items() as $item ): ?>
					<tr class="po-line">
						<td class="description"><?php echo $item->get_name() ?></td>
						<td class="qty"><?php echo $item->get_quantity() ?></td>
						<td class="price"><?php echo wc_price( $this->get_item_subtotal( $item, FALSE, FALSE ), array( 'currency' => $currency ) ); ?></td>
						<?php if ( $discount ): ?>
							<td class="discount">
								<?php
								if ( $item->get_subtotal() != $item->get_total() ) : ?>
									-<?php echo wc_price( wc_format_decimal( $item->get_subtotal() - $item->get_total(), '' ), array( 'currency' => $currency ) ) ?>
								<?php endif; ?>
							</td>
						<?php endif; ?>
						<?php
						if ( ( $tax_data = $item->get_taxes() ) && wc_tax_enabled() ) :
							
							foreach ( $this->get_taxes() as $tax_item ) :
								$tax_item_id = $tax_item->get_rate_id();
								$tax_item_total = isset( $tax_data['total'][ $tax_item_id ] ) ? $tax_data['total'][ $tax_item_id ] : '';
								?>
								<td class="tax">
									<?php
									if ( '' != $tax_item_total ):
										echo wc_price( wc_round_tax_total( $tax_item_total ), array( 'currency' => $currency ) );
									else:
										echo '&ndash;';
									endif;
									?>
								</td>
							<?php endforeach;
						
						endif; ?>
						<td class="total"><?php echo wc_price( $item->get_total(), array( 'currency' => $currency ) ) ?></td>
					</tr>
				<?php endforeach; ?>
				<?php if ( $line_items_shipping ): ?>
					<?php foreach ( $line_items_shipping as $item_id => $item ): ?>
						<tr class="po-line content-shipping">
							<td class="description"><?php echo esc_html( $item->get_name() ?: __( 'Shipping', ATUM_TEXT_DOMAIN ) ); ?></td>
							<td class="qty">&nbsp;</td>
							<td class="price">&nbsp;</td>
							<?php if ( $discount ): ?>
								<td class="discount">&nbsp;</td>
							<?php endif; ?>
							<?php
							if ( ( $tax_data = $item->get_taxes() ) && wc_tax_enabled() ) :
								
								foreach ( $this->get_taxes() as $tax_item ) :
									$tax_item_id = $tax_item->get_rate_id();
									$tax_item_total = isset( $tax_data['total'][ $tax_item_id ] ) ? $tax_data['total'][ $tax_item_id ] : '';
									?>
									<td class="tax">
										<?php
										if ( '' != $tax_item_total ):
											echo wc_price( wc_round_tax_total( $tax_item_total ), array( 'currency' => $currency ) );
										else:
											echo '&ndash;';
										endif;
										?>
									</td>
								<?php endforeach;
							
							endif; ?>
							<td class="total"><?php echo wc_price( $item->get_total(), array( 'currency' => $currency ) ) ?></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				<?php if ( $line_items_fee ): ?>
					<?php foreach ( $line_items_fee as $item_id => $item ): ?>
						<tr class="po-line content-fees">
							<td class="description kk"><?php echo esc_html( $item->get_name() ?: __( 'Fee', ATUM_TEXT_DOMAIN ) ); ?></td>
							<td class="qty">&nbsp;</td>
							<td class="price">&nbsp;</td>
							<?php if ( $discount ): ?>
								<td class="discount">&nbsp;</td>
							<?php endif; ?>
							<?php
							if ( ( $tax_data = $item->get_taxes() ) && wc_tax_enabled() ) :
								
								foreach ( $this->get_taxes() as $tax_item ) :
									$tax_item_id = $tax_item->get_rate_id();
									$tax_item_total = isset( $tax_data['total'][ $tax_item_id ] ) ? $tax_data['total'][ $tax_item_id ] : '';
									?>
									<td class="tax">
										<?php
										if ( '' != $tax_item_total ):
											echo wc_price( wc_round_tax_total( $tax_item_total ), array( 'currency' => $currency ) );
										else:
											echo '&ndash;';
										endif;
										?>
									</td>
								<?php endforeach;
							endif; ?>
							<td class="total"><?php echo wc_price( $item->get_total(), array( 'currency' => $currency ) ) ?></td>
						</tr>
					<?php endforeach; ?>
				
				<?php endif; ?>
				</tbody>
				<tbody class="content-totals">
				
				<tr class="subtotal">
					<td class="label" colspan="<?php echo $total_text_colspan ?>">
						<?php _e( 'Subtotal', ATUM_TEXT_DOMAIN ) ?>:
					</td>
					<td class="total">
						<?php echo $this->get_formatted_total( '', TRUE ) ?>
					</td>
				</tr>
				<?php if ( $discount ): ?>
					<tr>
						<td class="label" colspan="<?php echo $total_text_colspan ?>">
							<?php _e( 'Discount', ATUM_TEXT_DOMAIN ) ?>:
						</td>
						<td class="total">
							-<?php echo wc_price( $this->get_total_discount(), array( 'currency' => $currency ) ) ?>
						</td>
					</tr>
				<?php endif; ?>
				<?php if ( $line_items_shipping ): ?>
					<tr>
						<td class="label" colspan="<?php echo $total_text_colspan ?>">
							<?php _e( 'Shipping', ATUM_TEXT_DOMAIN ) ?>:
						</td>
						<td class="total">
							<?php echo wc_price( $this->get_shipping_total(), array( 'currency' => $currency ) ) ?>
						</td>
					</tr>
				<?php endif; ?>
				<?php if ( wc_tax_enabled() ) :
					
					$tax_totals = $this->get_tax_totals();
					
					if ( ! empty( $tax_totals ) ):
						
						foreach ( $tax_totals as $code => $tax ) : ?>
							<tr>
								<td class="label" colspan="<?php echo $total_text_colspan ?>">
									<?php echo $tax->label; ?>:
								</td>
								<td class="total">
									<?php echo $tax->formatted_amount; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					
					<?php endif;
				
				endif; ?>
				<tr class="po-total">
					<td colspan="<?php echo $total_text_colspan - 2 ?>"></td>
					<td class="label" colspan="2">
						<?php printf( __( '%s Total', ATUM_TEXT_DOMAIN ), $post_type->labels->singular_name ); ?>:
					</td>
					<td class="total">
						<?php echo $this->get_formatted_total(); ?>
					</td>
				</tr>
				
				
				</tbody>
			</table>
		</div>
		
		<div class="po-wrapper content-description">
			<div class="label">
				<?php _e( 'DESCRIPTION', ATUM_TEXT_DOMAIN ) ?>
			</div>
			<div class="po-content">
				<?php echo apply_filters( 'the_content', $this->get_description() ) ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
		
	}
	
	/**
	 * Return formatted company address
	 *
	 * @return string
	 */
	public function get_company_address() {
		
		return apply_filters( 'atum/data_export/models/po_model/company_address', \WC()->countries->get_formatted_address( $this->company_data ), $this->company_data );
		
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
			
			$address = \WC()->countries->get_formatted_address( array(
				'first_name' => get_the_title( $supplier_id ),
				'company'    => get_post_meta( $supplier_id, '_supplier_details_tax_number', TRUE ),
				'address_1'  => get_post_meta( $supplier_id, '_billing_information_address', TRUE ),
				'city'       => get_post_meta( $supplier_id, '_billing_information_city', TRUE ),
				'state'      => get_post_meta( $supplier_id, '_billing_information_state', TRUE ),
				'postcode'   => get_post_meta( $supplier_id, '_billing_information_zip_code', TRUE ),
				'country'    => get_post_meta( $supplier_id, '_billing_information_country', TRUE )
			) );
			
		}
		
		return apply_filters( 'atum/data_export/models/po_model/supplier_address', $address, $supplier_id );
		
	}
	
	/**
	 * Return formatted company address
	 *
	 * @return string
	 */
	public function get_shipping_address() {
		
		return apply_filters( 'atum/data_export/models/po_model/shipping_address', \WC()->countries->get_formatted_address( $this->shipping_data ), $this->shipping_data );
		
	}
	
	/**
	 * {@inheritdoc}
	 *
	 * @return array
	 */
	public function get_stylesheets( $output = 'path' ) {
		
		$prefix = ( $output == 'url' ) ? ATUM_URL : ATUM_PATH;
		
		return apply_filters( 'atum/data_export/models/po_model/css', array(
			$prefix . 'assets/css/atum-po-model.css',
		), $output, $this );
	}
	
	
}
<?php
/**
 * Adds a metabox to add documents to products that will be attached to WC order confirmation emails
 *
 * @since       1.8.4
 * @author      Be Rebel - https://berebel.io
 * @copyright   ©2021 Stock Management Labs™
 *
 * @package     Atum\MetaBoxes
 */

namespace Atum\MetaBoxes;

defined( 'ABSPATH' ) || die;

use Atum\Inc\Helpers;


class FileAttachment {

	/**
	 * The singleton instance holder
	 *
	 * @var FileAttachment
	 */
	private static $instance;

	/**
	 * Meta key name
	 */
	const ATUM_ATTACHMENTS_META_KEY = '_atum_attachments';


	/**
	 * FileAttachment constructor
	 *
	 * @since 1.8.4
	 */
	private function __construct() {

		// Add meta boxes to WP products.
		add_action( 'add_meta_boxes_product', array( $this, 'add_meta_box' ) );

		// Save the attachments.
		add_action( 'save_post_product', array( $this, 'save_meta_box' ) );

		// Add the attachments to the WC emails (if needed).
		add_filter( 'woocommerce_email_attachments', array( $this, 'attach_files_to_wc_emails' ), 10, 4 );

	}

	/**
	 * Registers the meta box to the product's screen
	 *
	 * @since 1.8.4
	 */
	public function add_meta_box() {

		// Data meta box.
		add_meta_box(
			'atum_files',
			__( 'ATUM attachments', ATUM_TEXT_DOMAIN ),
			array( $this, 'show_files_meta_box' ),
			'product',
			'side',
			'low'
		);

	}

	/**
	 * Displays the ATUM files meta box at products
	 *
	 * @since 1.8.4
	 *
	 * @param \WP_Post $post
	 */
	public function show_files_meta_box( $post ) {

		$product_id          = $post->ID;
		$product_attachments = get_post_meta( $product_id, self::ATUM_ATTACHMENTS_META_KEY, TRUE );
		$product_attachments = $product_attachments ? json_decode( $product_attachments ) : [];
		$email_notifications = self::get_email_notifications();

		Helpers::load_view( 'meta-boxes/product/file-attachment', compact( 'product_attachments', 'email_notifications' ) );

	}

	/**
	 * Save the attachments for this product
	 *
	 * @param int $product_id
	 */
	public function save_meta_box( $product_id ) {

		if ( ! isset( $_POST['atum-attachments'] ) ) {
			return;
		}

		update_post_meta( $product_id, self::ATUM_ATTACHMENTS_META_KEY, sanitize_text_field( $_POST['atum-attachments'] ) );

	}

	/**
	 * Return a list of the email notifications registered in WC settings
	 *
	 * @since 1.8.4
	 *
	 * @param bool $allow_empty
	 *
	 * @return array
	 */
	public static function get_email_notifications( $allow_empty = TRUE ) {

		$email_templates     = WC()->mailer()->get_emails();
		$email_notifications = $allow_empty ? [ '' => __( 'None', ATUM_TEXT_DOMAIN ) ] : [];

		foreach ( $email_templates as $email_template ) {
			$email_notifications[ $email_template->id ] = $email_template->get_title();
		}

		return apply_filters( 'atum/meta_boxes/file_attachment/email_notifications', $email_notifications );

	}

	/**
	 * Attach files to WC emails (if needed)
	 *
	 * @since 1.8.3
	 *
	 * @param array     $attachments
	 * @param string    $email_id
	 * @param object    $object
	 * @param \WC_Email $email
	 *
	 * @return array
	 */
	public function attach_files_to_wc_emails( $attachments, $email_id, $object, $email = NULL ) {

		if ( ! $object instanceof \WC_Order ) {
			return $attachments;
		}

		$order_items = $object->get_items();

		foreach ( $order_items as $order_item ) {

			/**
			 * Variable definition
			 *
			 * @var \WC_Order_Item_Product $order_item
			 */
			$product_id       = $order_item->get_product_id();
			$atum_attachments = get_post_meta( $product_id, self::ATUM_ATTACHMENTS_META_KEY, TRUE );

			if ( ! empty( $atum_attachments ) ) {

				$atum_attachments = json_decode( $atum_attachments );

				foreach ( $atum_attachments as $atum_attachment ) {
					if ( $atum_attachment->email === $email_id ) {
						$attachments[] = get_attached_file( $atum_attachment->id );
						break;
					}
				}

			}

		}

		return $attachments;

	}


	/********************
	 * Instance methods
	 ********************/

	/**
	 * Cannot be cloned
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_attr__( 'Cheatin&#8217; huh?', ATUM_TEXT_DOMAIN ), '1.0.0' );
	}

	/**
	 * Cannot be serialized
	 */
	public function __sleep() {
		_doing_it_wrong( __FUNCTION__, esc_attr__( 'Cheatin&#8217; huh?', ATUM_TEXT_DOMAIN ), '1.0.0' );
	}

	/**
	 * Get Singleton instance
	 *
	 * @return FileAttachment instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}

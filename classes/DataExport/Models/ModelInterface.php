<?php
/**
 * @package         Atum\DataExport
 * @subpackage      Models
 * @author          Salva Machí and Jose Piera - https://sispixels.com
 * @copyright       ©2017 Stock Management Labs™
 *
 * @since           1.3.9
 *
 * Defines the Interface required for Model generation
 */

namespace Atum\DataExport\Models;

defined( 'ABSPATH' ) or die;

interface ModelInterface {
	
	/**
	 * Return header content if exist
	 *
	 * @since 1.3.9
	 *
	 * @return string/void
	 */
	public function get_header();
	
	/**
	 * Return header content if exist
	 *
	 * @since 1.3.9
	 *
	 * @return string
	 */
	public function get_content();
	
	/**
	 * Return footer content if exist
	 *
	 * @since 1.3.9
	 *
	 * @return string/void
	 */
	public function get_footer();
	
	/**
	 * Return an array with stylesheets needed to include in the pdf
	 *
	 * @since 1.3.9
	 *
	 * @param string $output Whether the output array of stylesheets are returned as a path or as a url
	 *
	 * @return array
	 */
	public function get_stylesheets( $output = 'path');
	
	
}
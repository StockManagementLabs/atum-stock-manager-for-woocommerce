<?php
/**
 * News Widget for ATUM Dashboard
 *
 * @package         Atum
 * @subpackage      Dashboard\Widgets
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2022 Stock Management Labs™
 *
 * @since           1.4.0
 */

namespace Atum\Dashboard\Widgets;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumWidget;
use Atum\Inc\Helpers;

/** NEWS WIDGET DISABLED */
class News_DISABLED extends AtumWidget {

	/**
	 * The id of this widget
	 *
	 * @var string
	 */
	protected $id = ATUM_PREFIX . 'news_widget';

	/**
	 * News constructor
	 */
	public function __construct() {

		$this->title       = __( 'Latest News', ATUM_TEXT_DOMAIN );
		$this->description = __( 'Live Feed about the Latest News and Blog Posts', ATUM_TEXT_DOMAIN );
		$this->thumbnail   = ATUM_URL . 'assets/images/dashboard/widget-thumb-news.png';

		parent::__construct();
	}

	/**
	 * Widget initialization
	 *
	 * @since 1.4.0
	 */
	public function init() {

		// TODO: Load the config for this widget??
	}

	/**
	 * Load the widget view
	 *
	 * @since 1.4.0
	 */
	public function render() {

		add_filter( 'wp_feed_cache_transient_lifetime', array( $this, 'limit_feed_cache' ) );
		$sml_feed = fetch_feed( 'https://www.stockmanagementlabs.com/feed/' );
		remove_filter( 'wp_feed_cache_transient_lifetime', array( $this, 'limit_feed_cache' ) );

		$max_items = 0;
		$rss_items = array();

		if ( ! is_wp_error( $sml_feed ) ) {
			// Figure out how many total items there are, but limit it to 10.
			$max_items = $sml_feed->get_item_quantity( 10 );

			// Build an array of all the items, starting with element 0 (first element).
			$rss_items = $sml_feed->get_items( 0, $max_items );
		}

		$config = $this->get_config();

		Helpers::load_view( 'widgets/news', compact( 'max_items', 'rss_items', 'config' ) );

	}

	/**
	 * Change the feed cache limit to 7200 seconds (2 hours)
	 *
	 * @since 1.4.0
	 *
	 * @return int
	 */
	public function limit_feed_cache() {
		return 7200;
	}

	/**
	 * Load widget config view
	 * This is what will display when an admin clicks "Configure" at widget header
	 *
	 * @since 1.4.0
	 *
	 * @return string
	 */
	public function get_config() {
		// TODO: IMPLEMENT WIDGET SETTINGS.
		return ''; // Helpers::load_view_to_string( 'widgets/news-config' );.
	}

}

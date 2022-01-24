<?php
/**
 * Videos Widget for ATUM Dashboard
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
use Madcoda\Youtube\Youtube;


class Videos extends AtumWidget {

	/**
	 * The id of this widget
	 *
	 * @var string
	 */
	protected $id = ATUM_PREFIX . 'videos_widget';

	/**
	 * The video tags that will be used for video filtering
	 *
	 * @var array
	 */
	protected static $video_tags = array();

	/**
	 * Videos constructor
	 */
	public function __construct() {

		$this->title       = __( 'Video Tutorials', ATUM_TEXT_DOMAIN );
		$this->description = __( "Live Feed from ATUM's YouTube Channel", ATUM_TEXT_DOMAIN );
		$this->thumbnail   = ATUM_URL . 'assets/images/dashboard/widget-thumb-videos.png';

		self::$video_tags = (array) apply_filters( 'atum/dashboard/videos_widget/filter_tags', array(
			'atum'                 => __( 'ATUM', ATUM_TEXT_DOMAIN ),
			'atum-product-levels'  => __( 'ATUM Product Levels', ATUM_TEXT_DOMAIN ),
			'atum-multi-inventory' => __( 'ATUM Multi-Inventory', ATUM_TEXT_DOMAIN ),
			'features'             => __( 'Features', ATUM_TEXT_DOMAIN ),
			'add-ons'              => __( 'Add-ons', ATUM_TEXT_DOMAIN ),
			'how-tos'              => __( 'How-tos', ATUM_TEXT_DOMAIN ),
			'updates'              => __( 'Updates', ATUM_TEXT_DOMAIN ),
			'beta'                 => __( 'Beta', ATUM_TEXT_DOMAIN ),
		) );

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

		$view_atts = array_merge( self::get_filtered_videos(), [ 'config' => $this->get_config() ] );

		Helpers::load_view( 'widgets/videos', $view_atts );

	}

	/**
	 * Get the SML channel's videos from Youtube API
	 *
	 * @since 1.4.0
	 *
	 * @param string $sort_by   Optional. The sorting parameter. Possible values: date, rating, relevance, title, viewCount.
	 *
	 * @return array  An array with elements used within the video widget view (videos, first_video, channel, video_tags)
	 */
	public static function get_filtered_videos( $sort_by = 'date' ) {

		$video_data = array();

		try {

			// TODO: IMPLEMENT PAGINATION ONCE THE SML CHANNEL REACHES 50 VIDEOS.
			$youtube = new Youtube( array( 'key' => 'AIzaSyA-F1nv-MzeGiAUjG87jlPIWDQJegiT0Dc' ) );

			$params = array(
				'q'          => '',
				'type'       => 'video',
				'channelId'  => 'UCcTNwTCU4X_UrIj_5TUkweA', // Stock Management Labs' channel ID.
				'part'       => 'id',
				'maxResults' => 50,
				'order'      => in_array( $sort_by, [ 'date', 'rating', 'relevance', 'title', 'viewCount' ] ) ? $sort_by : 'date',
			);

			$video_ids = $youtube->searchAdvanced( $params );

			if ( ! empty( $video_ids ) ) {

				$video_ids        = wp_list_pluck( $video_ids, 'id' );
				$filtered_vid_ids = array();

				foreach ( $video_ids as $video_id ) {
					$filtered_vid_ids[] = $video_id->videoId; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				}

				$video_data['videos']      = $youtube->getVideosInfo( $filtered_vid_ids );
				$video_data['first_video'] = current( $video_data['videos'] );

			}

			$video_data['video_tags'] = self::$video_tags;
			$video_data['channel']    = $youtube->getChannelById( 'UCcTNwTCU4X_UrIj_5TUkweA' );

		} catch ( \Exception $e ) {
			error_log( $e->getMessage() );
		}

		return $video_data;

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
		return ''; // Helpers::load_view_to_string( 'widgets/videos-config' ).
	}

}

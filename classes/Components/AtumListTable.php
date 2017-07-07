<?php
/**
 * @package         Atum
 * @subpackage      Components
 * @author          Salva Machí and Jose Piera - https://sispixels.com
 * @copyright       ©2017 Stock Management Labs™
 *
 * @since           0.0.1
 *
 * Extends WP_List_Table to display the stock management table
 */

namespace Atum\Components;

defined( 'ABSPATH' ) or die;

use Atum\Inc\Helpers;
use Atum\Settings\Settings;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

abstract class AtumListTable extends \WP_List_Table {

	/**
	 * The post type used to build the table (WooCommerce product)
	 * @var string
	 */
	protected $post_type = 'product';
	
	/**
	 * The table columns
	 *
	 * @var array
	 */
	protected $table_columns;
	
	/**
	 * The previously selected items
	 *
	 * @var array
	 */
	protected $selected = array();
	
	/**
	 * Group title columns
	 *
	 * @var array
	 */
	protected $group_columns = array();
	
	/**
	 * Group members
	 *
	 * @var array
	 */
	protected $group_members = array();
	
	/**
	 * Elements per page (in order to obviate option default)
	 *
	 * @var int
	 */
	protected $per_page;
	
	/**
	 * Arrat with the id's of the products in current page
	 *
	 * @var array
	 */
	protected $current_products;
	
	/**
	 * Taxonomies to filter by
	 *
	 * @var array
	 */
	protected $taxonomies = array();
	
	/**
	 * Data for send to client side
	 *
	 * @var array
	 */
	protected $data = array();
	
	/**
	 * Ids for views
	 *
	 * @var array
	 */
	protected $id_views = array();
	
	/**
	 * Counters for views
	 *
	 * @var array
	 */
	protected $count_views = array();
	
	/**
	 * User meta key to control the current user dismissed notices
	 */
	const DISMISSED_NOTICES = 'atum_dismissed_notices';

	/**
	 * Value for empty columns
	 */
	const EMPTY_COL = '&mdash;';
	
	/**
	 * Constructor
	 *
	 * The child class should call this constructor from its own constructor to override the default $args.
	 *
	 * @since 0.0.1
	 *
	 * @param array|string $args          {
	 *      Array or string of arguments.
	 *
	 *      @type array  $table_columns The table columns for the list table
	 *      @type array  $group_members The column grouping members
	 *      @type bool   $show_cb       Optional. Whether to show the row selector checkbox as first table column
	 *      @type int    $per_page      Optional. The number of posts to show per page (-1 for no pagination)
	 *      @type array  $selected      Optional. The posts selected on the list table
	 * }
	 */
	public function __construct( $args = array() ) {
		
		$args = wp_parse_args( $args, array(
			'show_cb'  => FALSE,
			'per_page' => Settings::DEFAULT_POSTS_PER_PAGE,
		) );
		
		if ( ! empty( $args['selected'] ) ) {
			$this->selected = ( is_array( $args['selected'] ) ) ? $args['selected'] : explode( ',', $args['selected'] );
		}

		if ( ! empty($args['group_members']) ) {
			$this->group_members = $args['group_members'];
		}
		
		// Add the checkbox column to the table if enabled
		$this->table_columns = ( $args['show_cb'] == TRUE ) ? array_merge( array( 'cb' => 'cb' ), $args['table_columns'] ) : $args['table_columns'];
		$this->per_page      = $args['per_page'];
		
		$post_type_obj = get_post_type_object( $this->post_type );
		
		if ( ! $post_type_obj ) {
			return FALSE;
		}
		
		// Set \WP_List_Table defaults
		$args = array_merge( array(
			'singular' => strtolower( $post_type_obj->labels->singular_name ),
			'plural'   => strtolower( $post_type_obj->labels->name ),
			'ajax'     => TRUE
		), $args );
		
		parent::__construct( $args );
		
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		
		$user_dismissed_notices = Helpers::get_dismissed_notices();
		
		if (
			Helpers::get_option( 'manage_stock', 'no' ) == 'no' &&
			( !$user_dismissed_notices || ! isset($user_dismissed_notices['manage_stock']) || $user_dismissed_notices['manage_stock'] != 'yes' )
		) {
			
			add_action( 'admin_notices', array( $this, 'add_manage_stock_notice' ) );
		}
		
	}
	
	/**
	 * Column selector checkbox
	 *
	 * @since  0.0.1
	 *
	 * @param object $item
	 *
	 * @return string
	 */
	protected function column_cb( $item ) {
		
		return sprintf(
			'<input type="checkbox"%s name="%s[]" value="%s">',
			checked( in_array( $item->ID, $this->selected ), TRUE, FALSE ),
			$this->_args['singular'],
			$item->ID
		);
	}
	
	/**
	 * REQUIRED! This method dictates the table's columns and titles.
	 * This should return an array where the key is the column slug (and class) and the value
	 * is the column's title text.
	 *
	 * @see   WP_List_Table::single_row_columns()
	 *
	 * @since 0.0.1
	 *
	 * @return array An associative array containing column information: 'slugs'=>'Visible Titles'
	 */
	public function get_columns() {
		
		$result = array();
		
		foreach ( $this->table_columns as $table => $slug ) {
			$group            = $this->search_group_columns( $table );
			$result[ $table ] = ( $group ) ? "<span class='col-$group'>$slug</span>" : $slug;
		}
		
		return apply_filters( 'atum/atum_list_table/columns', $result );
	}
	
	/**
	 * Returns primary column name
	 *
	 * @since 0.0.8
	 *
	 * @return string   Name of the default primary column.
	 */
	protected function get_default_primary_column_name() {
		
		return 'title';
	}

	/**
	 * Create an editable meta cell
	 *
	 * @since 1.2.0
	 *
	 * @param array $args {
	 *      Array of arguments.
	 *
	 *      @type int    $post_id           The current post ID
	 *      @type string $meta_key          The meta key name (without initial underscore) to be saved
	 *      @type mixed  $value             The new value for the meta key cell
	 *      @type string $symbol            Whether to add any symbol to value
	 *      @type string $tooltip           The informational tooltip text
	 *      @type string $input_type        The input type field to use to edit the column value
	 *      @type array  $extra_meta        Any extra fields will be appended to the popover (as JSON array)
	 *      @type string $tooltip_position  Where to place the tooltip
	 * }
	 *
	 * @return string
	 */
	protected function get_editable_column ($args) {

		extract( wp_parse_args( $args, array(
			'post_id'          => NULL,
			'meta_key'         => '',
			'value'            => '',
			'symbol'           => '',
			'tooltip'          => '',
			'input_type'       => 'number',
			'extra_meta'       => array(),
			'tooltip_position' => 'top'
		) ) );

		$extra_meta_data = ( ! empty($extra_meta) ) ? ' data-extra-meta="' . htmlspecialchars( json_encode($extra_meta), ENT_QUOTES, 'UTF-8') . '"' : '';
		$symbol_data = ( ! empty($symbol) ) ? ' data-symbol="' . esc_attr($symbol) . '"' : '';

		$editable_col = '<span class="set-meta tips" data-toggle="tooltip" title="' . $tooltip . '" data-placement="' . $tooltip_position .
		       '" data-item="' . $post_id . '" data-meta="' . $meta_key . '"' . $symbol_data . $extra_meta_data .
		       ' data-input-type="' . $input_type . '">' . $value . '</span>';


		return apply_filters('atum/list_table/editable_column', $editable_col, $args);

	}
	
	/**
	 * All columns are sortable by default except cb and thumbnail
	 *
	 * Optional. If you want one or more columns to be sortable (ASC/DESC toggle),
	 * you will need to register it here. This should return an array where the
	 * key is the column that needs to be sortable, and the value is db column to
	 * sort by. Often, the key and value will be the same, but this is not always
	 * the case (as the value is a column name from the database, not the list table).
	 *
	 * This method merely defines which columns should be sortable and makes them
	 * clickable - it does not handle the actual sorting. You still need to detect
	 * the ORDERBY and ORDER querystring variables within prepare_items() and sort
	 * your data accordingly (usually by modifying your query).
	 *
	 * @return array An associative array containing all the columns that should be sortable: 'slugs' => array('data_values', bool)
	 */
	protected function get_sortable_columns() {
		
		$not_sortable = array( 'thumb', 'cb' );
		
		$sortable_columns = array();
		
		foreach ( $this->table_columns as $key => $column ) {
			// Until next version we don't sort calc values...
			if ( ! in_array( $key, $not_sortable ) && ! ( strpos( $key, 'calc_' ) === 0 ) ) {
				$sortable_columns[ $key ] = array( $key, FALSE );
			}
		}
		
		return apply_filters( 'atum/atum_list_table/sortable_columns', $sortable_columns );
	}
	
	
	/**
	 * Bulk actions are an associative array in the format 'slug' => 'Visible Title'
	 *
	 * @since 0.0.1
	 *
	 * @return array An associative array containing all the bulk actions: 'slugs'=>'Visible Titles'
	 */
	protected function get_bulk_actions() {
		return apply_filters( 'atum/atum_list_table/bulk_actions', array() );
	}
	
	/**
	 * Prepare the table data
	 *
	 * @since  0.0.1
	 */
	public function prepare_items() {
		
		/**
		 * Define our column headers
		 */
		$columns             = $this->get_columns();
		$selected_posts      = $posts_meta_query = $posts = array();
		$sortable            = $this->get_sortable_columns();
		$hidden              = get_hidden_columns( $this->screen );
		$this->group_columns = $this->calc_groups( $this->group_members, $hidden );
		
		/**
		 * REQUIRED. Build an array to be used by the class for column headers.
		 */
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$args = array(
			'post_type'      => array($this->post_type),
			'post_status'    => 'publish',
			'posts_per_page' => $this->per_page,
			'paged'          => $this->get_pagenum()
		);
		
		/*
		 * Tax filter
		 */
		if ( $this->taxonomies ) {
			$args['tax_query'] = (array) apply_filters( 'atum/atum_list_table/taxonomies', $this->taxonomies );
		}
		
		/*
		 * Months filter
		 */
		if ( ! empty( $_REQUEST['m']) ) {

			$month = esc_attr($_REQUEST['m']);
			$args['date_query'] = array(
				array(
					'year' => substr($month, 0, 4),
					'month' => substr($month, -2)
				)
			);

		}
		
		/*
		 * Check if the plugin manage all products stock
		 */
		if ( Helpers::get_option( 'manage_stock', 'no' ) == 'no' ) {
			
			// Only products with the _manage_stock meta set to yes
			$args['meta_query'] = array(
				'relation' => 'AND',
				array(
					'key'   => '_manage_stock',
					'value' => 'yes'
				),
			);
		}
		
		/*
		 * Ordering
		 */
		if ( ! empty( $_REQUEST['orderby'] ) && ! empty( $_REQUEST['order'] ) ) {
			
			$args['order'] = $_REQUEST['order'];
			
			// meta value, for now only have _sku to search. Obviating meta_value_num possibility
			if ( substr( $_REQUEST['orderby'], 0, 1 ) == '_' ) {
				$args['orderby']  = 'meta_value';
				$args['meta_key'] = $_REQUEST['orderby'];
			}
			// Calculated field... Transients with WP_QUERY left join???
			elseif ( strpos( $_REQUEST['orderby'], 'calc_' ) === 0 ) {
				
			}
			// Standard Fields
			else {
				$args['orderby'] = $_REQUEST['orderby'];
			}
		}
		
		/*
		 * Searching
		 */
		if ( ! empty( $_REQUEST['s'] ) ) {
			
			// Search based on post_type id
			if ( is_numeric( $_REQUEST['s'] ) ) {
				
				$post_id = absint( $_REQUEST['s'] );
				switch ( get_post_type( $post_id ) ) {
					
					case $this->post_type :
						$post_ids = array( $post_id );
						break;
					
					default:
						$post_ids = FALSE;
						break;
				}
				
				if ( $post_ids ) {
					$meta_query = array( 'post__in' => $post_ids );
					$args       = array_merge( $args, $meta_query );
				}
				
			}
			// Search by text
			else {
				$args = array_merge( $args, array( 's' => esc_attr( $_REQUEST['s'] ) ) );
			}
			
		}
		elseif ( ! empty( $this->selected ) ) {
			
			// Get first the selected posts that will be upper in the table
			$filter_args = array(
				'post__in' => $this->selected,
				'orderby'  => 'post__in'
			);
			
			$selected_posts_query = new \WP_Query( array_merge( $filter_args, $args ) );
			$selected_posts       = $selected_posts_query->posts;
			$args['post__not_in'] = $this->selected; // Exclude the selected posts from next query
			
		}
		
		// Save args
		$first_args['query_args'] = $args;
		
		// Search the term in the custom fields columns too
		if ( ! empty( $_REQUEST['s'] ) ) {
			
			$meta_key_args = array(
				'relation' => 'OR'
			);
			
			foreach ( $columns as $key => $label ) {
				
				// Only get the meta keys (_ as first char)
				if ( strpos( $key, '_' ) === 0 ) {
					
					$meta_key = array(
						'key'   => $key,
						'value' => $_REQUEST['s']
					);
					
					if ( is_numeric( $_REQUEST['s'] ) ) {
						$meta_key['type'] = 'numeric';
					}
					else {
						$meta_key['compare'] = 'LIKE';
					}
					
					$meta_key_args[] = $meta_key;
					
				}
				
			}
			
			unset( $args['s'] );
			
			if ( isset( $args['meta_query'] ) ) {
				$args['meta_query'][] = $meta_key_args;
			}
			else {
				$args['meta_query'] = $meta_key_args;
			}
			
			$first_args['meta'] = $meta_key_args;
			
		}
		
		// Build "Views Filters" and calculate totals
		if ( is_callable( array( $this, 'set_views_data' ) ) ) {
			$this->set_views_data( $first_args );
		}
		
		$this->data['v_filter'] = '';
		$allow_query = TRUE;
		
		/*
	     * REQUIRED. Register our pagination options & calculations.
		 */
		$found_posts = $this->count_views['count_all'];
		
		if ( ! empty( $_REQUEST['v_filter'] ) ) {
			
			$this->data['v_filter'] = esc_attr( $_REQUEST['v_filter'] );
			$allow_query = FALSE;
			
			foreach ( $this->id_views as $key => $post_ids ) {
				
				if ( $this->data['v_filter'] == $key && ! empty($post_ids) ) {

					// Add the parent products again to the query
					$args['post__in'] = $first_args['query_args']['post__in'] = ( ! empty($this->variable_products) || ! empty($this->grouped_products) ) ? $this->get_parents($post_ids) : $post_ids;
					$allow_query = TRUE;
					$found_posts = $this->count_views["count_$key"];

				}
				
			}
		}
		
		if ( $allow_query ) {
			
			$posts_query = new \WP_Query( $first_args['query_args'] );
			
			if ( isset( $first_args['meta'] ) ) {
				$posts_meta_query = new \WP_Query( $args );
				$posts_meta_query = $posts_meta_query->posts;
			}
			
			$posts = array_merge( $selected_posts, $posts_query->posts, $posts_meta_query );
			$this->current_products = wp_list_pluck($posts, 'ID');
			
			$total_pages = ( $this->per_page == - 1 ) ? 0 : ceil( $found_posts / $this->per_page );
			
		}
		else {
			$found_posts = $total_pages = 0;
		}
		
		/**
		 * REQUIRED!!!
		 * Save the sorted data to the items property, where can be used by the rest of the class
		 */
		$this->items = apply_filters( 'atum/atum_list_table/items', $posts );
		
		$this->set_pagination_args( array(
			'total_items' => $found_posts,
			'per_page'    => $this->per_page,
			'total_pages' => $total_pages,
			'orderby'     => ( ! empty( $_REQUEST['orderby'] ) ) ? $_REQUEST['orderby'] : 'date',
			'order'       => ( ! empty( $_REQUEST['order'] ) ) ? $_REQUEST['order'] : 'desc'
		) );
		
	}
	
	/**
	 * Adds the data needed for ajax filtering, sorting and pagination and displays the table
	 *
	 * @since 0.0.1
	 */
	public function display() {
		
		do_action( 'atum/atum_list_table/before_display', $this );
		
		$singular = $this->_args['singular'];
		$this->display_tablenav( 'top' );
		$this->screen->render_screen_reader_content( 'heading_list' );
		
		?>
		<div class="atum-table-wrapper">
			<table class="wp-list-table atum-list-table <?php echo implode( ' ', $this->get_table_classes() ); ?>">
				
				<thead>
					<?php $this->print_group_columns(); ?>

					<tr class="item-heads">
						<?php $this->print_column_headers(); ?>
					</tr>
				</thead>
				
				<tbody id="the-list"<?php if ( $singular ) echo " data-wp-lists='list:$singular'"; ?>>
					<?php $this->display_rows_or_placeholder(); ?>
				</tbody>
				
				<tfoot>
					<tr>
						<?php $this->print_column_headers( FALSE ); ?>
					</tr>
				</tfoot>
			
			</table>
		</div>
		<?php
		
		$this->display_tablenav( 'bottom' );
		
		// Prepare data
		wp_localize_script( 'atum', 'atumListTable', array_merge( array(
			'page'       => ( isset( $_REQUEST['page'] ) ) ? absint( $_REQUEST['page'] ) : 1,
			'perpage'    => $this->per_page,
			'order'      => $this->_pagination_args['order'],
			'orderby'    => $this->_pagination_args['orderby'],
			'nonce'      => wp_create_nonce( 'atum-list-table-nonce' ),
			'ajaxfilter' => Helpers::get_option( 'enable_ajax_filter', 'yes' ),
			'setValue'   => __('Set the %% value', ATUM_TEXT_DOMAIN),
			'setButton'  => __('Set', ATUM_TEXT_DOMAIN)
		), $this->data ) );
		
		do_action( 'atum/atum_list_table/after_display', $this );
		
	}
	
	/**
	 * Prints the columns that groups the distinct header columns
	 *
	 * @since 0.0.1
	 */
	public function print_group_columns() {
		
		if ( ! empty( $this->group_columns ) ) {
			
			echo '<tr class="group">';
			
			foreach ( $this->group_columns as $group_column ) {
				echo '<th class="' . $group_column['name'] . '" colspan="' . $group_column['colspan'] . '"><span>' . $group_column['title'] . '</span></th>';
			}
			
			echo '</tr>';
			
		}
	}
	
	/**
	 * Generate the table navigation above or below the table
	 * Just the parent function but removing the nonce fields that are not required here
	 *
	 * @since 0.0.1
	 *
	 * @param string $which 'top' or 'bottom' table nav
	 */
	protected function display_tablenav( $which ) {
		
		?>
		<div class="tablenav <?php echo esc_attr( $which ); ?>">
			
			<?php if ( ! empty( $this->get_bulk_actions() ) ): ?>
				<div class="alignleft actions bulkactions">
					<?php $this->bulk_actions( $which ); ?>
				</div>
				<?php
			endif;
			
			$this->extra_tablenav( $which );
			$this->pagination( $which );
			?>
			
			<br class="clear"/>
		</div>
		<?php
	}
	
	/**
	 * Message to be displayed when there are no items
	 *
	 * @since 0.0.1
	 */
	public function no_items() {
		
		$post_type_obj = get_post_type_object( $this->post_type );
		echo $post_type_obj->labels->not_found;
		
		if ( ! empty( $_REQUEST['s'] ) ) {
			printf( __( " with query '%s'", ATUM_TEXT_DOMAIN ), esc_attr( $_REQUEST['s'] ) );
		}
		
	}
	
	/**
	 * Get a list of CSS classes for the WP_List_Table table tag. Deleted 'fixed' from standard function
	 *
	 * @since  0.0.2
	 *
	 * @return array List of CSS classes for the table tag
	 */
	protected function get_table_classes() {
		
		return array( 'widefat', 'striped', $this->_args['plural'] );
	}

	/**
	 * A wrapper to get the right product ID (or variation ID)
	 *
	 * @since 1.2.1
	 *
	 * @param \WC_Product $product
	 *
	 * @return int
	 */
	protected function get_current_product_id($product) {

		if ( $product->get_type() == 'variation' ) {
			/**
			 * @deprecated
			 * The get_variation_id() method was deprecated in WC 3.0.0
			 * In newer versions the get_id() method always be the variation_id if it's a variation
			 */
			return ( version_compare( WC()->version, '3.0.0', '<' ) == -1 ) ? $product->get_variation_id() : $product->get_id();
		}

		return $product->get_id();

	}
	
	/**
	 * Gets the array needed to print html group columns in the table
	 *
	 * @since 0.0.1
	 *
	 * @param   array $group_members Parameter from __contruct method
	 * @param   array $hidden        hidden columns
	 *
	 * @return  array
	 */
	public function calc_groups( $group_members, $hidden ) {
		
		$response = array();
		
		foreach ( $group_members as $name => $group ) {
			
			$counter = 0;
			
			foreach ( $group['members'] as $member ) {
				if ( ! in_array( $member, $hidden ) ) {
					$counter ++;
				}
			}
			
			// Add the group only if there are columns within
			if ($counter) {
				$response[] = array(
					'name'    => $name,
					'title'   => $group['title'],
					'colspan' => $counter
				);
			}
		}
		
		return $response;
		
	}
	
	/**
	 * Return the group of columns that a specific column belongs to or false
	 *
	 * @sinece 0.0.5
	 *
	 * @param $column  string  The column to search to
	 *
	 * @return bool|string
	 */
	public function search_group_columns( $column ) {
		
		foreach ( $this->group_members as $name => $group_member ) {
			if ( in_array( $column, $group_member['members'] ) ) {
				return $name;
			}
		}
		
		return FALSE;
	}
	
	/**
	 * Handle an incoming ajax request
	 * Called by the \Ajax class
	 *
	 * @since 0.0.1
	 */
	public function ajax_response() {
		
		$this->prepare_items();
		extract( $this->_args );
		extract( $this->_pagination_args, EXTR_SKIP );
		
		ob_start();
		
		if ( ! empty( $_REQUEST['no_placeholder'] ) ) {
			$this->display_rows();
		}
		else {
			$this->display_rows_or_placeholder();
		}
		
		$rows = ob_get_clean();
		
		ob_start();
		$this->print_column_headers();
		$headers = ob_get_clean();
		
		ob_start();
		$this->extra_tablenav( 'top' );
		$extra_tablenav_top = ob_get_clean();
		
		ob_start();
		$this->pagination( 'top' );
		$pagination_top = ob_get_clean();
		
		ob_start();
		$this->extra_tablenav( 'bottom' );
		$extra_tablenav_bottom = ob_get_clean();
		
		ob_start();
		$this->pagination( 'bottom' );
		$pagination_bottom = ob_get_clean();
		
		$response                         = array( 'rows' => $rows );
		$response['pagination']['top']    = $pagination_top;
		$response['pagination']['bottom'] = $pagination_bottom;
		$response['extra_t_n']['top']     = $extra_tablenav_top;
		$response['extra_t_n']['bottom']  = $extra_tablenav_bottom;
		$response['column_headers']       = $headers;
		
		ob_start();
		$this->views();
		$response['views'] = ob_get_clean();
		
		
		if ( isset( $total_items ) ) {
			$response['total_items_i18n'] = sprintf( _n( '1 item', '%s items', $total_items ), number_format_i18n( $total_items ) );
		}
		
		if ( isset( $total_pages ) ) {
			$response['total_pages']      = $total_pages;
			$response['total_pages_i18n'] = number_format_i18n( $total_pages );
		}
		
		wp_send_json( $response );
		
	}
	
	/**
	 * Enqueue the required scripts
	 *
	 * @since 0.0.1
	 * @param string $hook
	 */
	public function enqueue_scripts( $hook ) {
			
		wp_register_script( 'mousewheel', ATUM_URL . 'assets/js/vendor/jquery.mousewheel.js', array( 'jquery' ), ATUM_VERSION );
		wp_register_script( 'jscrollpane', ATUM_URL . 'assets/js/vendor/jquery.jscrollpane.min.js', array( 'jquery', 'mousewheel' ), ATUM_VERSION );

		wp_register_style( 'atum-list', ATUM_URL . '/assets/css/atum-list.css', FALSE, ATUM_VERSION );

		if ( isset($this->load_datepicker) && $this->load_datepicker === TRUE ) {
			global $wp_scripts;
			$jquery_version = isset( $wp_scripts->registered['jquery-ui-core']->ver ) ? $wp_scripts->registered['jquery-ui-core']->ver : '1.11.4';
			wp_deregister_style('jquery-ui-style');
			wp_register_style( 'jquery-ui-style', '//code.jquery.com/ui/' . $jquery_version . '/themes/excite-bike/jquery-ui.min.css', array(), $jquery_version );

			wp_enqueue_style('jquery-ui-style');
			wp_enqueue_script('jquery-ui-datepicker');
		}

		$min = (! ATUM_DEBUG) ? '.min' : '';
		wp_register_script( 'atum', ATUM_URL . "/assets/js/atum$min.js", array( 'jquery', 'jscrollpane' ), ATUM_VERSION, TRUE );

		wp_enqueue_style( 'woocommerce_admin_styles' );
		wp_enqueue_style( 'atum-list' );
		wp_enqueue_script( 'jscrollpane' );
		wp_enqueue_script( 'atum' );
		
	}
	
	/**
	 * Add notice warning if Atum manage stock option isn't enabled
	 *
	 * @since 0.1.0
	 */
	public function add_manage_stock_notice() {
		
		?>
		<div class="notice notice-warning atum-notice notice-management-stock is-dismissible" data-nonce="<?php echo wp_create_nonce( ATUM_PREFIX . 'manage-stock-notice' ) ?>">
			<p class="manage-message">
				<?php printf( __( '%1$s plugin can bulk-enable all your items for stock management at the product level. %1$s will save your original settings if you decide to go back later or uninstall the plugin.', ATUM_TEXT_DOMAIN ), strtoupper( ATUM_TEXT_DOMAIN ) ) ?>
				<button type="button" class="add-manage-option button button-primary button-small"><?php _e( "Enable ATUM's Manage Stock option", ATUM_TEXT_DOMAIN ) ?></button>
			</p>
		</div>
		<?php
	}
	
}
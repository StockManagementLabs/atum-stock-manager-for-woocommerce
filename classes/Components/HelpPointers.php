<?php
/**
 * @package        Atum
 * @subpackage     Components
 * @author         Salva Machí and Jose Piera - https://sispixels.com
 * @copyright      ©2017 Stock Management Labs™
 *
 * @since          0.1.6
 *
 * @how_to_use
 * Pointers are defined in an associative array and passed to the class upon instantiation.
 * First we hook into the 'admin_enqueue_scripts' hook with our function:
 *
 *   add_action('admin_enqueue_scripts', 'my_help_pointers');
 *
 *   function my_help_pointers() {
 *      // First we define our pointers
 *      $pointers = array(
 *                       array(
 *                           'id' => 'xyz123',                          // Unique id for this pointer
 *                           'screen' => 'page',                        // This is the page hook we want our pointer to show on
 *                           'target' => '#element-selector',           // The css selector for the pointer to be tied to, best to use ID's
 *                           'title' => 'My ToolTip',
 *                           'content' => 'My tooltips Description',
 *                           'position' => array(
 *                               'edge' => 'top',                       // Top, bottom, left, right
 *                               'align' => 'middle'                    // Top, bottom, left, right, middle
 *                            )
 *                        ),
 *                        // More as needed
 *                        );
 *
 *      // Now we instantiate the class and pass our pointers array to the constructor
 *      $my_pointers = new \Atum\Components\HelpPointers($pointers);
 *    }
 *
 * @author Tim Debo <tim@rawcreativestudios.com>
 * @copyright Copyright (c) 2012, Raw Creative Studios
 * @link https://github.com/rawcreative/wp-help-pointers
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 */

namespace Atum\Components;


class HelpPointers {
	
	/**
	 * The current screen ID
	 * @var string
	 */
	public $screen_id;
	
	/**
	 * An array of valid help pointers for the screen
	 * @var array
	 */
	public $valid;
	
	/**
	 * The help pointers configuration array
	 * @var array
	 */
	public $pointers;
	

	public function __construct( $pntrs = array() ) {

		// Don't run on WP < 3.3
		if ( version_compare( get_bloginfo( 'version' ), '3.3', '<') ) {
			return;
		}

		$screen = get_current_screen();
		$this->screen_id = $screen->id;

		$this->register_pointers($pntrs);

		add_action( 'admin_enqueue_scripts', array( &$this, 'add_pointers' ), 1000 );
		add_action( 'admin_head', array( &$this, 'add_scripts' ) );
	}
	
	/**
	 * Register the help pointers to the screen
	 *
	 * @since 0.1.6
	 *
	 * @param array $pntrs  The help pointers configuration array
	 */
	public function register_pointers( $pntrs ) {

		$pointers = NULL;

		foreach( $pntrs as $ptr ) {
			if( $ptr['screen'] == $this->screen_id ) {
				$pointers[$ptr['id']] = array(
					'screen' => $ptr['screen'],
					'target' => $ptr['target'],
					'options' => array(
						'content' => sprintf( '<h3>%s</h3> <p>%s</p>',$ptr['title'],$ptr['content'] ),
						'position' => $ptr['position']
					)
				);
			}
		}

		if ( ! empty($pointers) ) {
			$this->pointers = $pointers;
		}
		
	}
	
	/**
	 * Get the valid help pointers and add them to the 'valid' property
	 *
	 * @since 0.1.6
	 */
	public function add_pointers() {

		$pointers = $this->pointers;

		if ( empty($pointers) || ! is_array( $pointers ) ) {
			return;
		}

		// Get dismissed pointers
		$dismissed = explode( ',', (string) get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true ) );
		$valid_pointers = array();

		// Check pointers and remove dismissed ones.
		foreach ( $pointers as $pointer_id => $pointer ) {

			// Make sure we have pointers & check if they have been dismissed
			if (
				in_array( $pointer_id, $dismissed ) || empty( $pointer )  ||
				empty( $pointer_id ) || empty( $pointer['target'] ) || empty( $pointer['options'] )
			) {
				continue;
			}

			$pointer['pointer_id'] = $pointer_id;

			// Add the pointer to $valid_pointers array
			$valid_pointers['pointers'][] =  $pointer;
			
		}

		// No valid pointers? Stop here.
		if ( empty( $valid_pointers ) ) {
			return;
		}

		$this->valid = $valid_pointers;
		
	}
	
	/**
	 * Add the script to display the pointers
	 *
	 * @since 0.1.6
	 */
	public function add_scripts() {
		
		$pointers = $this->valid;

		if( empty( $pointers ) ) {
			return;
		}
		
		wp_enqueue_style( 'wp-pointer' );
		wp_enqueue_script( 'wp-pointer' );

		?>
		<script type="text/javascript">
			jQuery(function ($) {

				var ATUMHelpPointer = <?php echo json_encode( $pointers ) ?>;
	
				$.each(ATUMHelpPointer.pointers, function(i) {
					atum_help_pointer_open(i);
				});
	
				function atum_help_pointer_open(i) {

					var pointer = ATUMHelpPointer.pointers[i];
					options = $.extend( pointer.options, {
						close: function() {
							$.post( ajaxurl, {
								pointer: pointer.pointer_id,
								action: 'dismiss-wp-pointer'
							});
						},
						buttons: function( event, t ) {
							var close  = '<?php _e('Close', ATUM_TEXT_DOMAIN) ?>',
								button = $('<a class="close" href="#">' + close + '</a>');
	
							return button.bind( 'click.pointer', function(e) {
								e.preventDefault();
								t.element.pointer('close');
							});
						}
					});
					
					$(pointer.target).pointer( options ).pointer('open');
				}
			});
		</script>
		<?php

	}

}
<?php
/**
 * Help pointers class
 *
 * @package        Atum
 * @subpackage     Components
 * @author         Be Rebel - https://berebel.io
 * @copyright      ©2022 Stock Management Labs™
 *
 * @since          0.1.6
 *
 * @based on WP Help Pointers:
 *
 * @how_to_use
 * Pointers are defined in an associative array and passed to the class upon instantiation
 * First we hook into the 'admin_enqueue_scripts' hook with our function:
 *
 * add_action('admin_enqueue_scripts', 'my_help_pointers');
 *
 * @original_author Tim Debo <tim@rawcreativestudios.com>
 * @original_copyright Copyright (c) 2012, Raw Creative Studios
 * @link https://github.com/rawcreative/wp-help-pointers
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 */

namespace Atum\Components;

defined( 'ABSPATH' ) || die;

/* @deprecated Use the AtumHelpGuide instead */
class AtumHelpPointers {
	
	/**
	 * The current screen ID
	 *
	 * @var string
	 */
	public $screen_id;
	
	/**
	 * An array of valid help pointers for the screen
	 *
	 * @var array
	 */
	public $valid;
	
	/**
	 * The help pointers configuration array
	 *
	 * @var array
	 */
	public $pointers;

	/**
	 * AtumHelpPointers constructor.
	 *
	 * @param array $pntrs
	 */
	public function __construct( $pntrs = array() ) {

		// Doesn't run on WP < 3.3.
		if ( version_compare( get_bloginfo( 'version' ), '3.3', '<' ) ) {
			return;
		}

		$screen          = get_current_screen();
		$this->screen_id = $screen->id;

		$this->register_pointers( $pntrs );

		add_action( 'admin_enqueue_scripts', array( $this, 'add_pointers' ), 1000 );
		add_action( 'admin_head', array( $this, 'add_scripts' ) );

	}
	
	/**
	 * Register the help pointers to the screen
	 *
	 * @since 0.1.6
	 *
	 * @param array $pntrs  The help pointers configuration array.
	 */
	public function register_pointers( $pntrs ) {

		$pointers = NULL;
		
		// Get dismissed pointers.
		$dismissed         = explode( ',', (string) get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', TRUE ) );
		$targets_dismissed = [];
		
		foreach ( $pntrs as $key => $ptr ) {
			
			if ( in_array( $ptr['id'], $dismissed ) ) {
				$targets_dismissed[] = $ptr['target'];
				unset( $pntrs[ $key ] );
			}
		}
		
		foreach ( $pntrs as $ptr ) {
			
			if ( in_array( $ptr['id'], $dismissed ) ) {
				continue;
			}

			if ( $ptr['screen'] === $this->screen_id ) {

				$pointers[ $ptr['id'] ] = array(
					'screen'  => $ptr['screen'],
					'target'  => $ptr['target'],
					'next'    => isset( $ptr['next'] ) && ! in_array( $ptr['next'], $targets_dismissed ) ? $ptr['next'] : '',
					'options' => array(
						'target'        => $ptr['target'],
						'content'       => sprintf( '<h3>%s</h3> <p>%s</p>', $ptr['title'], $ptr['content'] ),
						'position'      => $ptr['position'],
						'arrowPosition' => isset( $ptr['arrow_position'] ) ? $ptr['arrow_position'] : [],
					),
				);

			}

		}

		if ( ! empty( $pointers ) ) {
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

		if ( empty( $pointers ) || ! is_array( $pointers ) ) {
			return;
		}

		// Check pointers and remove dismissed ones.
		foreach ( $pointers as $pointer_id => $pointer ) {

			// Make sure we have pointers & check if they have been dismissed.
			if (
				empty( $pointer ) || empty( $pointer_id ) ||
				empty( $pointer['target'] ) || empty( $pointer['options'] )
			) {
				continue;
			}

			$pointer['pointer_id'] = $pointer_id;

			// Add the pointer to $valid_pointers array.
			$valid_pointers['pointers'][] = $pointer;
			
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

		if ( empty( $pointers ) ) {
			return;
		}
		
		wp_enqueue_style( 'wp-pointer' );
		wp_enqueue_script( 'wp-pointer' );

		// TODO: MOVE TO A FILE?
		// phpcs:disable
		?>
		<script type="text/javascript">
			jQuery(function ($) {

				var ATUMHelpPointers = <?php echo wp_json_encode( $pointers ) ?>;

				if (Object.keys(ATUMHelpPointers).length && typeof ATUMHelpPointers.pointers !== 'undefined' && ATUMHelpPointers.pointers.length) {

					$.each(ATUMHelpPointers.pointers, function (i) {
						doAtumHelpPointer(i);
					});

					function doAtumHelpPointer(i) {

						var atumPointer = ATUMHelpPointers.pointers[i],
						    options     = $.extend(atumPointer.options, {
							    close  : function () {

								    $.ajax({
									    url       : ajaxurl,
									    type      : 'POST',
									    data      : {
										    pointer: atumPointer.pointer_id,
										    action : 'dismiss-wp-pointer'
									    },
									    beforeSend: function () {
										    // Show next pointer
										    if (atumPointer.next) {
											    $(atumPointer.next).pointer('open');
										    }
									    }
								    });

							    },
							    buttons: function (event, t) {
								    var $button = $('<a class="close" href="#"><?php _e( 'Close', ATUM_TEXT_DOMAIN ) ?></a>');

								    return $button.on('click.pointer', function (e) {
									    e.preventDefault();
									    t.element.pointer('close');
								    });
							    }
						    });

						$.widget('wp.pointer', $.wp.pointer, {
							_create: function () {

								var positioning,
								    family;

								this.content = $('<div class="wp-pointer-content"></div>');
								this.arrow = $('<div class="wp-pointer-arrow"><div class="wp-pointer-arrow-inner"></div></div>');

								if (typeof this.options.arrowPosition !== 'undefined') {
									this.arrow.css(this.options.arrowPosition);
								}

								family = this.element.parents().add(this.element);
								positioning = 'absolute';

								if (family.filter(function () {
									return 'fixed' === $(this).css('position');
								}).length) {
									positioning = 'fixed';
								}

								this.pointer = $('<div />')
									.append(this.content)
									.append(this.arrow)
									.attr('id', 'wp-pointer-' + i)
									.addClass(this.options.pointerClass)
									.css({
										'position': positioning,
										'width'   : this.options.pointerWidth + 'px',
										'display' : 'none'
									})
									.data('target', this.options.target)
									.appendTo(this.options.document.body);

							},
							open   : function (event) {
								var self = this,
								    o    = this.options;

								if (this.active || o.disabled || this.element.is(':hidden')) {
									return;
								}

								this.update().done(function () {
									self._open(event);

									setTimeout(function () {
										self.reposition();
									}, 0)
								});
							},
						});

						$(atumPointer.target).pointer(options);
					}

					// Open the first one
					var $firstTarget = $(ATUMHelpPointers.pointers.shift().target);
					$firstTarget.pointer('open');

				}

			});
		</script>
		<?php
		// phpcs:enable
	}

}

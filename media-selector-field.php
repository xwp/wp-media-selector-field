<?php
/**
 * Plugin Name: Media Selector Field
 * Description: Makes a field available for selecting an image from the media library
 * Author: XWP, Dzikri Aziz, Weston Ruter
 * Author URI: https://xwp.co/
 * Version: 0.4
 * License: GPLv2+
 * Text Domain: media-selector-field
 */

/**
 * Copyright (c) 2013 XWP (https://xwp.co/)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

/**
 * A helper class for selecting media/attachment posts to be used
 * in a theme/plugin setting page or metaboxes.
 *
 * Quick howto:
 * - Include this file
 * - Call <code>XTeam_Media_Selector_Field::register();</code>
 *   <em>before</em> the <code>admin_enqueue_scripts</code> action is fired.
 * - In your metabox/setting field: call <code>XTeam_Media_Selector_Field::the_field()</code>
 * - Be sure to pass the required arguments to the above methods.
 *
 * @author Dzikri Aziz <dzikri@xwp.co>
 * @author Weston Ruter <weston@xwp.co>
 */
class XTeam_Media_Selector_Field {

	const VERSION = '0.4';

	protected static $defaults = array();

	protected static $entries = array();

	protected static $_rendered_fields = array();


	public static function register( $entry_id, $options = array() ) {
		// Only applicable in admin area
		if ( ! is_admin() ) {
			return;
		}

		if ( empty( self::$defaults ) ) {
			self::$defaults = array(
				'screen_id'     => 'post',
				'screen_base'   => 'post',
				'multiple'      => false,
				'type'          => '_all',
				'frame_title'   => __( 'Select', 'media-selector-field' ), // Title of the media manager lightbox
				'select_button' => __( 'Select', 'media-selector-field' ), // Button text
				'insert_button' => __( 'Insert', 'media-selector-field' ), // Button text
				'preview_size'  => 'thumbnail',
				'animate'       => 500,
			);

			add_action( 'admin_enqueue_scripts',              array( __CLASS__, 'enqueue_assets' ) );
			add_action( 'customize_controls_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		}

		$new_entry = wp_parse_args( $options, self::$defaults );
		self::$entries[ $entry_id ] = $new_entry;
	}


	public static function enqueue_assets() {
		if ( empty( self::$entries ) ) {
			return;
		}

		$screen = get_current_screen();
		foreach ( self::$entries as $entry_id => $entry_props ) {
			if (
				! in_array( $screen->id, (array) $entry_props['screen_id'] )
				&& ! in_array( $screen->base, (array) $entry_props['screen_base'] )
			) {
				unset( self::$entries[ $entry_id ] );
			}
		}

		if ( empty( self::$entries ) ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_script(
			'xteam-media-selector',
			plugin_dir_url( __FILE__ ) . '/media-selector-field.js',
			array( 'jquery-ui-sortable' ),
			self::VERSION,
			true
		);
		wp_localize_script(
			'xteam-media-selector',
			'xteamMediaSelector',
			self::$entries
		);
		wp_enqueue_style(
			'xteam-media-selector',
			plugin_dir_url( __FILE__ ) . '/media-selector-field.css',
			false,
			self::VERSION
		);

		$customizer_style = array(
			'.media-modal { z-index: 600000 }',
			'.media-modal-backdrop { z-index: 599999 }',
		);
		wp_add_inline_style( 'customize-controls', implode( "\n", $customizer_style ) );
	}


	/**
	 * Render media selector field
	 *
	 * @param string $entry_id      Registered entry ID
	 * @param string $input_name    Input name for your form, defaults to $entry_id
	 * @param array  $current_value Current attachment IDs
	 * @param string $field_id      HTML ID attribute that will be used for the
	 *     wrapper. Make sure to set this if you're rendering the same
	 *     registered entry multiple times or the JS will likely fail.
	 */
	public static function the_field( $entry_id, $input_name = '', $current_value = '', $field_id = '' ) {
		if ( empty( self::$entries[ $entry_id ] ) ) {
			return false;
		}

		$entry = self::$entries[ $entry_id ];

		if ( isset(self::$_rendered_fields[ $entry_id ]) ) {
			self::$_rendered_fields[ $entry_id ]++;
		}
		else {
			self::$_rendered_fields[ $entry_id ] = 1;
		}

		if ( empty( $field_id ) ) {
			$field_id = sprintf( '%s-%d', $entry_id, self::$_rendered_fields[ $entry_id ] );
		}

		if ( ! is_array( $current_value ) ) {
			$current_value = array( $current_value );
		}
		foreach ( $current_value as $idx => $attachment_id ) {
			if ( empty( $attachment_id ) ) {
				unset( $current_value[$idx] );
				continue;
			}

			$attachment = get_post( $attachment_id );
			if ( empty( $attachment ) || ! is_object( $attachment ) ) {
				unset( $current_value[$idx] );
			}
		}

		$wrap_class = 'xteam-media-selector';
		$list_class = 'xteam-media-list attachments';

		if ( empty( $current_value ) ) {
			$list_class .= ' hidden';
			$current_value[] = ''; // Needed to print out the item template.
		}

		if ( empty( $input_name ) ) {
			$input_name = $entry_id;
		}

		if ( $entry['multiple'] ) {
			$input_name .= '[]';
			$list_class .= ' multiple';
		}
		else {
			$wrap_class .= ' single-file';
		}

		$list_attr  = ' id="'. esc_attr( $field_id ) .'"';
		$list_attr .= ' class="'. esc_attr( $list_class ) .'"';
		$list_attr .= ' data-size="'. esc_attr( $entry['preview_size'] ) .'"';
		$list_attr .= ' data-animate="'. esc_attr( $entry['animate'] ) .'"';


		$did_once = false;
		?>
			<div class="<?php echo esc_attr( $wrap_class ) ?>">
			<ul<?php echo $list_attr // xss ok ?>>
				<?php foreach ( $current_value as $attachment_id ) : ?>
					<?php
						$item_class  = 'attachment';
						$thumb_style = '';

						if ( ! empty( $attachment_id ) ) {
							$image = wp_get_attachment_image( $attachment_id, $entry['preview_size'], true );
							$title = get_the_title( $attachment_id );
							$item_class .= ' type-' .substr( get_post_mime_type( $attachment_id ), 0, strpos( $attachment->post_mime_type, '/' ) );

							if (
								'image' === $entry['type']
								&& 'thumbnail' !== $entry['preview_size']
								&& $image_src = wp_get_attachment_image_src( $attachment_id, $entry['preview_size'], false )
							) {
								if ( is_numeric( $entry['preview_size'] ) ) {
									$thumb_style = sprintf(
										' style="width:%dpx;height:%dpx"',
										$entry['preview_size'],
										$entry['preview_size']
									);
								}
								else {
									$thumb_style = sprintf(
										' style="width:%dpx;height:%dpx"',
										$image_src[1],
										$image_src[2]
									);
								}
							}
						}
						else {
							if ( $did_once ) {
								// Skip, we already printed the template.
								continue;
							}

							$image = '<img />';
							$title = '';
						}

						$did_once = true;
				?>
					<li class="<?php echo esc_attr( $item_class ) ?>">
						<div class="attachment-preview"<?php echo $thumb_style // xss ok ?>>
							<div class="thumbnail"<?php echo $thumb_style // xss ok ?>>
								<div class="centered">
									<?php echo $image // xss ok ?>
								</div>
								<div class="filename">
									<div><?php echo esc_html( $title ) ?></div>
								</div>
							</div>
							<a title="<?php esc_attr_e( 'Deselect', 'media-selector-field' ) ?>" href="#" class="check">
								<div class="media-modal-icon"></div>
							</a>
						</div>
						<?php printf(
							'<input type="hidden" name="%s" value="%s" />',
							esc_attr( $input_name ),
							esc_attr( $attachment_id )
						) ?>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php printf(
				'<p><a href="#" class="button-primary xteam-media-select" data-entryid="%s" data-fieldid="%s">%s</a></p>',
				esc_attr( $entry_id ),
				esc_attr( $field_id ),
				esc_html( $entry['select_button'] )
			) ?>
		</div>
		<?php
	}
}

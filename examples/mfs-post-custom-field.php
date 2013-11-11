<?php

/**
 * Plugin Name: Media Selector Field Demo :: Post meta
 * Description: A demo plugin for Media Selector Field
 * Plugin URI: http://x-team.com/wordpress/
 * Author: X-Team, Dzikri Aziz
 * Author URI: http://x-team.com/wordpress/
 * Version: 0.1
 * License: GPLv2+
 * Text Domain: media-selector-field
 * Depends: Media Selector Field
 */


/**
 * Example usage of Media Selector Field for post custom fields
 *
 * @author Dzikri Aziz <kucrut@x-team.com>
 */
class XTeam_Media_Selector_Field_Example_Post {

	/**
	 * Holds our fields
	 *
	 * @access private
	 * @var array
	 */
	private static $_fields;


	/**
	 * Initialize plugin
	 *
	 * @access public
	 * @return void
	 */
	public static function init() {
		if ( ! class_exists( 'XTeam_Media_Selector_Field' ) )
			return;

		self::$_fields = array(
			'xteam_mfs_example_single' => array(
				'title'   => __( 'Single:', 'media-selector-field' ),
				'options' => array(
					'screen_id'     => 'post',
					'screen_base'   => 'post',
					'multiple'      => false,
					'frame_title'   => __( 'Select Attachments', 'media-selector-field' ),
					'select_button' => __( 'Select', 'media-selector-field' ),
					'insert_button' => __( 'Select', 'media-selector-field' ),
					'preview_size'  => 'thumbnail',
					'animate'       => 500,
				),
			),
			'xteam_mfs_example_multiple' => array(
				'title'   => __( 'Multiple:', 'media-selector-field' ),
				'options' => array(
					'screen_id'     => 'post',
					'screen_base'   => 'post',
					'multiple'      => 'add',
					'frame_title'   => __( 'Select Attachments', 'media-selector-field' ),
					'select_button' => __( 'Select', 'media-selector-field' ),
					'insert_button' => __( 'Select images', 'media-selector-field' ),
					'preview_size'  => 'thumbnail',
					'animate'       => 500,
				),
			),
		);

		add_action( 'admin_init', array( __CLASS__, '_register_fields' ) );
		add_action( 'add_meta_boxes_post', array( __CLASS__, '_add_meta_box' ) );
		add_action( 'save_post', array( __CLASS__, '_save_fields' ) );
		add_filter( 'is_protected_meta', array( __CLASS__, '_protect_meta_key' ), 10, 3 );
	}


	/**
	 * Register our fields to MFS
	 *
	 * @access public
	 * @action admin_init
	 * @return void
	 */
	public static function _register_fields() {
		foreach ( self::$_fields as $entry_id => $properties ) {
			XTeam_Media_Selector_Field::register( $entry_id, $properties['options'] );
		}
	}


	/**
	 * Add meta box to the post editing page
	 *
	 * @access public
	 * @action add_meta_boxes_post
	 * @param object $post Post object
	 * @return void
	 */
	public static function _add_meta_box( $post ) {
		add_meta_box(
			strtolower( __CLASS__ ),
			__( 'Media Selector Field', 'media-selector-field' ),
			array( __CLASS__, '_the_meta_box' ),
			$post->post_type
		);
	}


	/**
	 * Meta box
	 *
	 * @param object $post Post object
	 * @param array  $box  Meta box
	 *
	 * @return void
	 */
	public static function _the_meta_box( $post, $box ) {
		?>
		<table class="form-table">
			<tbody>
				<?php foreach ( self::$_fields as $entry_id => $properties ) : ?>
					<tr>
						<th scope="row"><?php echo esc_html( $properties['title'] ) ?></th>
						<td>
							<?php XTeam_Media_Selector_Field::the_field( $entry_id, $entry_id, get_post_meta( $post->ID, $entry_id, true ) ) ?>
							<?php if ( $properties['options']['multiple'] ) : ?>
								<p class="description"><?php esc_html_e( 'Drag & drop to reorder.', 'media-selector-field' ) ?></p>
							<?php endif ?>
						</td>
					</tr>
				<?php endforeach ?>
			</tbody>
		</table>
		<?php
	}


	/**
	 * Save our fields
	 *
	 * @access public
	 * @action save_post
	 * @param int    $post_id Post ID
	 * @param object $post Post object
	 * @return void
	 */
	public static function _save_fields( $post_id, $post ) {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX )
			return;

		if ( ! empty( $_POST['action'] ) && 'inline-edit' === $_POST['action'] )
			return;

		foreach ( array_keys( self::$_fields ) as $entry_id ) {
			if ( ! empty( $_POST[ $entry_id ] ) )
				update_post_meta( $post_id, $entry_id, $_POST[ $entry_id ] );
			else
				delete_post_meta( $post_id, $entry_id );
		}
	}


	/**
	 * Protect meta key so it won't show up in the built-in Custom Fields meta box
	 *
	 * @param bool   $protected
	 * @param string $meta_key
	 * @param string $meta_type
	 *
	 * @return bool
	 */
	public static function _protect_meta_key( $protected, $meta_key, $meta_type ) {
		$keys = array_keys( self::$_fields );
		if ( in_array( $meta_key, $keys ) && 'post' === $meta_type )
			$protected = true;

		return $protected;
	}
}
add_action( 'wp_loaded', array( 'XTeam_Media_Selector_Field_Example_Post', 'init' ) );

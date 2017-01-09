<?php

/**
 * User Activity Core Actions
 *
 * @package User/Activity/Actions/Core
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Core actions
 *
 * @since 0.1.0
 */
class WP_User_Activity_Type_Core extends WP_User_Activity_Type {

	/**
	 * The unique type for this activity
	 *
	 * @since 0.1.0
	 *
	 * @var string
	 */
	public $object_type = 'core';

	/**
	 * Icon of this activity type
	 *
	 * @since 0.1.0
	 *
	 * @var string
	 */
	public $icon = 'wordpress';

	/**
	 * Add hooks
	 *
	 * @since 0.1.0
	 */
	public function __construct() {

		// Set name
		$this->name = esc_html__( 'Core', 'wp-user-actiivity' );

		// Update
		new WP_User_Activity_Action( array(
			'type'    => $this,
			'action'  => 'update',
			'name'    => esc_html__( 'Update', 'wp-user-activity' ),
			'message' => esc_html__( '%1$s updated WordPress %2$s.', 'wp-user-activity' )
		) );

		// Auto-update
		new WP_User_Activity_Action( array(
			'type'    => $this,
			'action'  => 'auto_update',
			'name'    => esc_html__( 'Auto-update', 'wp-user-activity' ),
			'message' => esc_html__( 'WordPress auto-updated %1$s.', 'wp-user-activity' )
		) );

		// Actions
		add_action( '_core_updated_successfully', array( $this, 'core_updated_successfully' ) );

		// Setup callbacks
		parent::__construct();
	}

	/** Callbacks *************************************************************/

	/**
	 * Callback for returning human-readable output.
	 *
	 * @since 0.1.0
	 *
	 * @param  object  $post
	 *
	 * @return string
	 */
	public function update_action_callback( $post ) {
		return sprintf(
			$this->get_activity_action( 'update' ),
			$this->get_activity_author_link( $post ),
			$this->get_how_long_ago( $post )
		);
	}

	/**
	 * Callback for returning human-readable output.
	 *
	 * @since 0.1.0
	 *
	 * @param  object  $post
	 * @param  array   $meta
	 *
	 * @return string
	 */
	public function auto_update_action_callback( $post ) {
		return sprintf(
			$this->get_activity_action( 'auto_update' ),
			$this->get_how_long_ago( $post )
		);
	}

	/** Logging ***************************************************************/

	/**
	 * Updated WordPress core
	 *
	 * @since 0.1.0
	 *
	 * @global  string  $pagenow
	 * @param   string  $wp_version
	 */
	public function core_updated_successfully( $wp_version ) {
		global $pagenow;

		// Auto updated
		if ( 'update-core.php' !== $pagenow ) {
			$object_name = 'WordPress Auto Updated';
			$action      = 'auto_update';
		} else {
			$object_name = 'WordPress Updated';
			$action      = 'update';
		}

		// Insert activity
		wp_insert_user_activity( array(
			'object_type' => $this->object_type,
			'object_name' => $object_name,
			'object_id'   => get_current_blog_id(),
			'action'      => $action
		) );
	}
}
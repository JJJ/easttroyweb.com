<?php

/**
 * Plugin Name: WP User Activity
 * Plugin URI:  https://wordpress.org/plugins/wp-user-activity/
 * Description: The best way to log activity in WordPress
 * Author:      John James Jacoby
 * Author URI:  http://jjj.me
 * Version:     0.1.8
 * Text Domain: wp-user-activity
 * Domain Path: /languages/
 * License:     GPLv2 or later (license.txt)
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Initialize WP User Activity
 *
 * @since 0.1.0
 */
function _wp_user_activity() {

	// Include the files
	$dir = plugin_dir_path( __FILE__ );

	// Include the files
	require_once $dir . '/includes/admin.php';
	require_once $dir . '/includes/classes.php';
	require_once $dir . '/includes/capabilities.php';
	require_once $dir . '/includes/functions.php';
	require_once $dir . '/includes/list-table.php';
	require_once $dir . '/includes/metadata.php';
	require_once $dir . '/includes/metaboxes.php';
	require_once $dir . '/includes/post-types.php';
	require_once $dir . '/includes/taxonomies.php';
	require_once $dir . '/includes/hooks.php';

	// Actions
	require_once $dir . '/actions/class-action-attachments.php';
	require_once $dir . '/actions/class-action-comments.php';
	require_once $dir . '/actions/class-action-core.php';
	require_once $dir . '/actions/class-action-exports.php';
	require_once $dir . '/actions/class-action-menus.php';
	require_once $dir . '/actions/class-action-site-settings.php';
	require_once $dir . '/actions/class-action-plugins.php';
	require_once $dir . '/actions/class-action-posts.php';
	require_once $dir . '/actions/class-action-terms.php';
	require_once $dir . '/actions/class-action-themes.php';
	require_once $dir . '/actions/class-action-users.php';
	require_once $dir . '/actions/class-action-widgets.php';
}
add_action( 'plugins_loaded', '_wp_user_activity' );

/**
 * Return the plugin's URL
 *
 * @since 0.1.2
 *
 * @return string
 */
function wp_user_activity_get_plugin_url() {
	return plugin_dir_url( __FILE__ );
}

/**
 * Return the asset version
 *
 * @since 0.1.2
 *
 * @return int
 */
function wp_user_activity_get_asset_version() {
	return 201510290001;
}

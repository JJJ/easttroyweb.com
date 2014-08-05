<?php

/**
 * Activity component CSS/JS
 *
 * @package BuddyPress
 * @subpackage ActivityScripts
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Enqueue @mentions JS.
 *
 * @since BuddyPress (2.1)
 */
function bp_activity_mentions_script() {
	if ( ! bp_activity_maybe_load_mentions_scripts() ) {
		return;
	}

	$min  = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
	$file = is_rtl() ? "mentions-rtl{$min}.css" : "mentions{$min}.css";

	wp_enqueue_script( 'bp-mentions', buddypress()->plugin_url . "bp-activity/js/mentions{$min}.js", array( 'jquery', 'jquery-atwho' ), bp_get_version(), true );
	wp_enqueue_style( 'bp-mentions-css', buddypress()->plugin_url . "bp-activity/css/{$file}", array(), bp_get_version() );

	// Print a list of the current user's friends to the page for quicker @mentions lookups.
	do_action( 'bp_activity_mentions_prime_results' );
}
add_action( 'bp_enqueue_scripts', 'bp_activity_mentions_script' );

/**
 * Enqueue @mentions JS in wp-admin.
 *
 * @since BuddyPress (2.1)
 */
function bp_activity_mentions_dashboard_script() {
	if ( ! bp_activity_maybe_load_mentions_scripts() ) {
		return;
	}

	// Special handling for New/Edit screens in wp-admin
	if (
		! get_current_screen() ||
		! in_array( get_current_screen()->base, array( 'page', 'post' ) ) || 
		! post_type_supports( get_current_screen()->post_type, 'editor' ) ) {
		return;
	}

	$min  = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
	$file = is_rtl() ? "mentions-rtl{$min}.css" : "mentions{$min}.css";

	wp_enqueue_script( 'bp-mentions', buddypress()->plugin_url . "bp-activity/js/mentions{$min}.js", array( 'jquery', 'jquery-atwho' ), bp_get_version(), true );
	wp_enqueue_style( 'bp-mentions-css', buddypress()->plugin_url . "bp-activity/css/{$file}", array(), bp_get_version() );

	// Print a list of the current user's friends to the page for quicker @mentions lookups.
	do_action( 'bp_activity_mentions_prime_results' );
}
add_action( 'bp_admin_enqueue_scripts', 'bp_activity_mentions_dashboard_script' );
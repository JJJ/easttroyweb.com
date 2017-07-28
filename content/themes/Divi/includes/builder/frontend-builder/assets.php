<?php

// Register assets that need to be fired at head
function et_fb_enqueue_assets_head() {
	if ( et_fb_is_enabled() ) {
		// Setup WP media.
		wp_enqueue_media();
	}
}
add_action( 'wp_enqueue_scripts', 'et_fb_enqueue_assets_head' );

// TODO, make this fire late enough, so that the_content has fired and ET_Builder_Element::get_computed_vars() is ready
// currently its being called in temporary_app_boot() in view.php
// add_action( 'wp_enqueue_scripts', 'et_fb_enqueue_assets' );
function et_fb_enqueue_main_assets() {
	$ver    = ET_BUILDER_VERSION;
	$root   = ET_BUILDER_URI;
	$assets = ET_FB_ASSETS_URI;

	wp_register_style( 'et_pb_admin_date_css', "{$root}/styles/jquery-ui-1.10.4.custom.css", array(), $ver );

	// Enqueue styles.
	wp_enqueue_style( 'et-frontend-builder', "{$assets}/css/style.css", array(
		'et_pb_admin_date_css',
		'wp-mediaelement',
		'wp-color-picker'
	), $ver );
}
add_action( 'wp_enqueue_scripts', 'et_fb_enqueue_main_assets' );

function et_fb_enqueue_google_maps_dependency( $dependencies ) {

	if ( et_pb_enqueue_google_maps_script() ) {
		$dependencies[] = 'google-maps-api';
	}

	return $dependencies;
}
add_filter( 'et_fb_bundle_dependencies', 'et_fb_enqueue_google_maps_dependency' );

function et_fb_load_portability() {
	et_core_register_admin_assets();
	et_core_load_component( 'portability' );

	// Register the Builder individual layouts portability.
	et_core_portability_register( 'et_builder', array(
		'name' =>  esc_html__( 'Divi Builder Layout', 'et_builder' ),
		'type' => 'post',
		'view' => true,
	) );
}

function et_fb_enqueue_assets() {
	global $wp_version;

	et_fb_load_portability();

	$ver    = ET_BUILDER_VERSION;
	$root   = ET_BUILDER_URI;
	$app    = ET_FB_URI;
	$assets = ET_FB_ASSETS_URI;

	// Get WP major version
	$wp_major_version = substr( $wp_version, 0, 3 );

	// Register styles.
	// wp_enqueue_style( 'et-frontend-builder', "{$assets}/css/frontend-builder.css", null, $ver );

	// Register scripts.
	// wp_register_script( 'minicolors', "{$root}/scripts/ext/jquery.minicolors.js" );

	wp_register_script( 'iris', admin_url( 'js/iris.min.js' ), array( 'jquery-ui-draggable', 'jquery-ui-slider', 'jquery-touch-punch' ), false, 1 );
	wp_register_script( 'wp-color-picker', admin_url( 'js/color-picker.min.js' ), array( 'iris' ), false, 1 );
	wp_register_script( 'wp-color-picker-alpha', "{$root}/scripts/ext/wp-color-picker-alpha.min.js", array( 'wp-color-picker' ) );

	$colorpicker_l10n = array(
		'clear'         => esc_html__( 'Clear', 'et_builder' ),
		'defaultString' => esc_html__( 'Default', 'et_builder' ),
		'pick'          => esc_html__( 'Select Color', 'et_builder' ),
		'current'       => esc_html__( 'Current Color', 'et_builder' ),
	);

	wp_localize_script( 'wp-color-picker', 'wpColorPickerL10n', $colorpicker_l10n );

	wp_register_script( 'react-tiny-mce', "{$assets}/vendors/tinymce.min.js" );

	if ( version_compare( $wp_major_version, '4.5', '<' ) ) {
		wp_register_script( 'et_pb_admin_date_js', "{$root}/scripts/ext/jquery-ui-1.10.4.custom.min.js", array( 'jquery' ), $ver, true );
	} else {
		wp_register_script( 'et_pb_admin_date_js', "{$root}/scripts/ext/jquery-ui-1.11.4.custom.min.js", array( 'jquery' ), $ver, true );
	}

	wp_register_script( 'et_pb_admin_date_addon_js', "{$root}/scripts/ext/jquery-ui-timepicker-addon.js", array( 'et_pb_admin_date_js' ), $ver, true );

	wp_register_script( 'wp-shortcode', includes_url() . '/js/shortcode.js', array(), $wp_version );

	$fb_bundle_dependencies = apply_filters( 'et_fb_bundle_dependencies', array(
		'jquery',
		'jquery-ui-core',
		'jquery-ui-draggable',
		'jquery-ui-resizable',
		'underscore',
		// 'minicolors',
		'jquery-ui-sortable',
		'jquery-effects-core',
		'iris',
		'wp-color-picker',
		'wp-color-picker-alpha',
		'react-tiny-mce',
		'easypiechart',
		'et_pb_admin_date_addon_js',
		'salvattore',
		'hashchange',
		'wp-shortcode',
	) );

	// Enqueue scripts.
	wp_enqueue_script( 'et-frontend-builder', "{$app}/bundle.js", $fb_bundle_dependencies, $ver, true );

	// Enqueue failure notice script.
	wp_enqueue_script( 'et-frontend-builder-failure', "{$assets}/scripts/failure_notice.js", array(), $ver, true );
	wp_localize_script( 'et-frontend-builder-failure', 'et_fb_options', array(
		'ajaxurl'                    => admin_url( 'admin-ajax.php' ),
		'et_admin_load_nonce'        => wp_create_nonce( 'et_admin_load_nonce' ),
		'memory_limit_increased'     => esc_html__( 'Your memory limit has been increased', 'et_builder' ),
		'memory_limit_not_increased' => esc_html__( "Your memory limit can't be changed automatically", 'et_builder' ),
	) );

	do_action( 'et_fb_enqueue_assets' );
}

<?php
/**
 * @package Make Plus
 */

global $ttfmake_section_data, $ttfmake_is_js_template;

// New settings instance
$settings = MakePlus()->get_component( 'panels' )->panels_settings();

// Load the section class and render
$section_template = dirname( __FILE__ ) . '/section.php';
require_once( $section_template );

$section = new TTFMP_Panels_Builder_Section( $ttfmake_section_data, $ttfmake_is_js_template, $settings );
$section->render();

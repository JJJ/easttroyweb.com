<?php

/**
 * Main bbPress Admin Class
 *
 * @package bbPress
 * @subpackage Administration
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'BBP_Admin' ) ) :
/**
 * Loads bbPress plugin admin area
 *
 * @package bbPress
 * @subpackage Administration
 * @since bbPress (r2464)
 */
class BBP_Admin {

	/** Directory *************************************************************/

	/**
	 * @var string Path to the bbPress admin directory
	 */
	public $admin_dir = '';

	/** URLs ******************************************************************/

	/**
	 * @var string URL to the bbPress admin directory
	 */
	public $admin_url = '';

	/**
	 * @var string URL to the bbPress images directory
	 */
	public $images_url = '';

	/**
	 * @var string URL to the bbPress admin styles directory
	 */
	public $styles_url = '';

	/** Capability ************************************************************/

	/**
	 * @var bool Minimum capability to access Tools and Settings
	 */
	public $minimum_capability = 'keep_gate';

	/** Separator *************************************************************/

	/**
	 * @var bool Whether or not to add an extra top level menu separator
	 */
	public $show_separator = false;

	/** Functions *************************************************************/

	/**
	 * The main bbPress admin loader
	 *
	 * @since bbPress (r2515)
	 *
	 * @uses BBP_Admin::setup_globals() Setup the globals needed
	 * @uses BBP_Admin::includes() Include the required files
	 * @uses BBP_Admin::setup_actions() Setup the hooks and actions
	 */
	public function __construct() {
		$this->setup_globals();
		$this->includes();
		$this->setup_actions();
	}

	/**
	 * Admin globals
	 *
	 * @since bbPress (r2646)
	 * @access private
	 */
	private function setup_globals() {
		$bbp = bbpress();
		$this->admin_dir  = trailingslashit( $bbp->includes_dir . 'admin'  ); // Admin path
		$this->admin_url  = trailingslashit( $bbp->includes_url . 'admin'  ); // Admin url
		$this->images_url = trailingslashit( $this->admin_url   . 'images' ); // Admin images URL
		$this->styles_url = trailingslashit( $this->admin_url   . 'styles' ); // Admin styles URL
	}

	/**
	 * Include required files
	 *
	 * @since bbPress (r2646)
	 * @access private
	 */
	private function includes() {
		require( $this->admin_dir . 'tools.php'     );
		require( $this->admin_dir . 'converter.php' );
		require( $this->admin_dir . 'settings.php'  );
		require( $this->admin_dir . 'functions.php' );
		require( $this->admin_dir . 'metaboxes.php' );
		require( $this->admin_dir . 'forums.php'    );
		require( $this->admin_dir . 'topics.php'    );
		require( $this->admin_dir . 'replies.php'   );
		require( $this->admin_dir . 'users.php'     );
	}

	/**
	 * Setup the admin hooks, actions and filters
	 *
	 * @since bbPress (r2646)
	 * @access private
	 *
	 * @uses add_action() To add various actions
	 * @uses add_filter() To add various filters
	 */
	private function setup_actions() {

		// Bail to prevent interfering with the deactivation process
		if ( bbp_is_deactivation() )
			return;

		/** General Actions ***************************************************/

		add_action( 'bbp_admin_menu',              array( $this, 'admin_menus'                )     ); // Add menu item to settings menu
		add_action( 'bbp_admin_head',              array( $this, 'admin_head'                 )     ); // Add some general styling to the admin area
		add_action( 'bbp_admin_notices',           array( $this, 'activation_notice'          )     ); // Add notice if not using a bbPress theme
		add_action( 'bbp_register_admin_style',    array( $this, 'register_admin_style'       )     ); // Add green admin style
		add_action( 'bbp_register_admin_settings', array( $this, 'register_admin_settings'    )     ); // Add settings
		add_action( 'bbp_activation',              array( $this, 'new_install'                )     ); // Add menu item to settings menu
		add_action( 'admin_enqueue_scripts',       array( $this, 'enqueue_scripts'            )     ); // Add enqueued JS and CSS
		add_action( 'wp_dashboard_setup',          array( $this, 'dashboard_widget_right_now' )     ); // Forums 'Right now' Dashboard widget
		add_action( 'admin_bar_menu',              array( $this, 'admin_bar_about_link'       ), 15 ); // Add a link to bbPress about page to the admin bar

		/** Ajax **************************************************************/

		add_action( 'wp_ajax_bbp_suggest_topic',        array( $this, 'suggest_topic' ) );
		add_action( 'wp_ajax_nopriv_bbp_suggest_topic', array( $this, 'suggest_topic' ) );

		add_action( 'wp_ajax_bbp_suggest_user',         array( $this, 'suggest_user'  ) );
		add_action( 'wp_ajax_nopriv_bbp_suggest_user',  array( $this, 'suggest_user'  ) );

		/** Filters ***********************************************************/

		// Modify bbPress's admin links
		add_filter( 'plugin_action_links', array( $this, 'modify_plugin_action_links' ), 10, 2 );

		// Map settings capabilities
		add_filter( 'bbp_map_meta_caps',   array( $this, 'map_settings_meta_caps' ), 10, 4 );

		// Hide the theme compat package selection
		add_filter( 'bbp_admin_get_settings_sections', array( $this, 'hide_theme_compat_packages' ) );

		// Allow keymasters to save forums settings
		add_filter( 'option_page_capability_bbpress',  array( $this, 'option_page_capability_bbpress' ) );

		/** Network Admin *****************************************************/

		// Add menu item to settings menu
		add_action( 'network_admin_menu',  array( $this, 'network_admin_menus' ) );

		/** Dependencies ******************************************************/

		// Allow plugins to modify these actions
		do_action_ref_array( 'bbp_admin_loaded', array( &$this ) );
	}

	/**
	 * Add the admin menus
	 *
	 * @since bbPress (r2646)
	 *
	 * @uses add_management_page() To add the Recount page in Tools section
	 * @uses add_options_page() To add the Forums settings page in Settings
	 *                           section
	 */
	public function admin_menus() {

		$hooks = array();

		// These are later removed in admin_head
		if ( current_user_can( 'bbp_tools_page' ) ) {
			if ( current_user_can( 'bbp_tools_repair_page' ) ) {
				$hooks[] = add_management_page(
					__( 'Repair Forums', 'bbpress' ),
					__( 'Forum Repair',  'bbpress' ),
					$this->minimum_capability,
					'bbp-repair',
					'bbp_admin_repair'
				);
			}

			if ( current_user_can( 'bbp_tools_import_page' ) ) {
				$hooks[] = add_management_page(
					__( 'Import Forums', 'bbpress' ),
					__( 'Forum Import',  'bbpress' ),
					$this->minimum_capability,
					'bbp-converter',
					'bbp_converter_settings'
				);
			}

			if ( current_user_can( 'bbp_tools_reset_page' ) ) {
				$hooks[] = add_management_page(
					__( 'Reset Forums', 'bbpress' ),
					__( 'Forum Reset',  'bbpress' ),
					$this->minimum_capability,
					'bbp-reset',
					'bbp_admin_reset'
				);
			}

			// Fudge the highlighted subnav item when on a bbPress admin page
			foreach ( $hooks as $hook ) {
				add_action( "admin_head-$hook", 'bbp_tools_modify_menu_highlight' );
			}

			// Forums Tools Root
			add_management_page(
				__( 'Forums', 'bbpress' ),
				__( 'Forums', 'bbpress' ),
				$this->minimum_capability,
				'bbp-repair',
				'bbp_admin_repair'
			);
		}

		// Are settings enabled?
		if ( ! bbp_settings_integration() && current_user_can( 'bbp_settings_page' ) ) {
			add_options_page(
				__( 'Forums',  'bbpress' ),
				__( 'Forums',  'bbpress' ),
				$this->minimum_capability,
				'bbpress',
				'bbp_admin_settings'
			);
		}

		// These are later removed in admin_head
		if ( current_user_can( 'bbp_about_page' ) ) {

			// About
			add_dashboard_page(
				__( 'Welcome to bbPress',  'bbpress' ),
				__( 'Welcome to bbPress',  'bbpress' ),
				$this->minimum_capability,
				'bbp-about',
				array( $this, 'about_screen' )
			);

			// Credits
			add_dashboard_page(
				__( 'Welcome to bbPress',  'bbpress' ),
				__( 'Welcome to bbPress',  'bbpress' ),
				$this->minimum_capability,
				'bbp-credits',
				array( $this, 'credits_screen' )
			);
		}

		// Bail if plugin is not network activated
		if ( ! is_plugin_active_for_network( bbpress()->basename ) )
			return;

		add_submenu_page(
			'index.php',
			__( 'Update Forums', 'bbpress' ),
			__( 'Update Forums', 'bbpress' ),
			'manage_network',
			'bbp-update',
			array( $this, 'update_screen' )
		);
	}

	/**
	 * Add the network admin menus
	 *
	 * @since bbPress (r3689)
	 * @uses add_submenu_page() To add the Update Forums page in Updates
	 */
	public function network_admin_menus() {

		// Bail if plugin is not network activated
		if ( ! is_plugin_active_for_network( bbpress()->basename ) )
			return;

		add_submenu_page(
			'upgrade.php',
			__( 'Update Forums', 'bbpress' ),
			__( 'Update Forums', 'bbpress' ),
			'manage_network',
			'bbpress-update',
			array( $this, 'network_update_screen' )
		);
	}

	/**
	 * If this is a new installation, create some initial forum content.
	 *
	 * @since bbPress (r3767)
	 * @return type
	 */
	public static function new_install() {
		if ( !bbp_is_install() )
			return;

		bbp_create_initial_content();
	}

	/**
	 * Register the settings
	 *
	 * @since bbPress (r2737)
	 *
	 * @uses add_settings_section() To add our own settings section
	 * @uses add_settings_field() To add various settings fields
	 * @uses register_setting() To register various settings
	 * @todo Put fields into multidimensional array
	 */
	public static function register_admin_settings() {

		// Bail if no sections available
		$sections = bbp_admin_get_settings_sections();
		if ( empty( $sections ) )
			return false;

		// Are we using settings integration?
		$settings_integration = bbp_settings_integration();

		// Loop through sections
		foreach ( (array) $sections as $section_id => $section ) {

			// Only proceed if current user can see this section
			if ( ! current_user_can( $section_id ) )
				continue;

			// Only add section and fields if section has fields
			$fields = bbp_admin_get_settings_fields_for_section( $section_id );
			if ( empty( $fields ) )
				continue;

			// Toggle the section if core integration is on
			if ( ( true === $settings_integration ) && !empty( $section['page'] ) ) {
				$page = $section['page'];
			} else {
				$page = 'bbpress';
			}

			// Add the section
			add_settings_section( $section_id, $section['title'], $section['callback'], $page );

			// Loop through fields for this section
			foreach ( (array) $fields as $field_id => $field ) {

				// Add the field
				if ( ! empty( $field['callback'] ) && !empty( $field['title'] ) ) {
					add_settings_field( $field_id, $field['title'], $field['callback'], $page, $section_id, $field['args'] );
				}

				// Register the setting
				register_setting( $page, $field_id, $field['sanitize_callback'] );
			}
		}
	}

	/**
	 * Maps settings capabilities
	 *
	 * @since bbPress (r4242)
	 *
	 * @param array $caps Capabilities for meta capability
	 * @param string $cap Capability name
	 * @param int $user_id User id
	 * @param mixed $args Arguments
	 * @uses get_post() To get the post
	 * @uses apply_filters() Calls 'bbp_map_meta_caps' with caps, cap, user id and
	 *                        args
	 * @return array Actual capabilities for meta capability
	 */
	public static function map_settings_meta_caps( $caps = array(), $cap = '', $user_id = 0, $args = array() ) {

		// What capability is being checked?
		switch ( $cap ) {

			// BuddyPress
			case 'bbp_settings_buddypress' :
				if ( ( is_plugin_active( 'buddypress/bp-loader.php' ) && defined( 'BP_VERSION' ) && bp_is_root_blog() ) && is_super_admin() ) {
					$caps = array( bbpress()->admin->minimum_capability );
				} else {
					$caps = array( 'do_not_allow' );
				}

				break;

			// Akismet
			case 'bbp_settings_akismet' :
				if ( ( is_plugin_active( 'akismet/akismet.php' ) && defined( 'AKISMET_VERSION' ) ) && is_super_admin() ) {
					$caps = array( bbpress()->admin->minimum_capability );
				} else {
					$caps = array( 'do_not_allow' );
				}

				break;

			// bbPress
			case 'bbp_about_page'            : // About and Credits
			case 'bbp_tools_page'            : // Tools Page
			case 'bbp_tools_repair_page'     : // Tools - Repair Page
			case 'bbp_tools_import_page'     : // Tools - Import Page
			case 'bbp_tools_reset_page'      : // Tools - Reset Page
			case 'bbp_settings_page'         : // Settings Page
			case 'bbp_settings_users'        : // Settings - Users
			case 'bbp_settings_features'     : // Settings - Features
			case 'bbp_settings_theme_compat' : // Settings - Theme compat
			case 'bbp_settings_root_slugs'   : // Settings - Root slugs
			case 'bbp_settings_single_slugs' : // Settings - Single slugs
			case 'bbp_settings_user_slugs'   : // Settings - User slugs
			case 'bbp_settings_per_page'     : // Settings - Per page
			case 'bbp_settings_per_rss_page' : // Settings - Per RSS page
				$caps = array( bbpress()->admin->minimum_capability );
				break;
		}

		return apply_filters( 'bbp_map_settings_meta_caps', $caps, $cap, $user_id, $args );
	}

	/**
	 * Register the importers
	 *
	 * @since bbPress (r2737)
	 *
	 * @uses apply_filters() Calls 'bbp_importer_path' filter to allow plugins
	 *                        to customize the importer script locations.
	 */
	public function register_importers() {

		// Leave if we're not in the import section
		if ( !defined( 'WP_LOAD_IMPORTERS' ) )
			return;

		// Load Importer API
		require_once( ABSPATH . 'wp-admin/includes/import.php' );

		// Load our importers
		$importers = apply_filters( 'bbp_importers', array( 'bbpress' ) );

		// Loop through included importers
		foreach ( $importers as $importer ) {

			// Allow custom importer directory
			$import_dir  = apply_filters( 'bbp_importer_path', $this->admin_dir . 'importers', $importer );

			// Compile the importer path
			$import_file = trailingslashit( $import_dir ) . $importer . '.php';

			// If the file exists, include it
			if ( file_exists( $import_file ) ) {
				require( $import_file );
			}
		}
	}

	/**
	 * Admin area activation notice
	 *
	 * Shows a nag message in admin area about the theme not supporting bbPress
	 *
	 * @since bbPress (r2743)
	 *
	 * @uses current_user_can() To check notice should be displayed.
	 */
	public function activation_notice() {
		// @todo - something fun
	}

	/**
	 * Add Settings link to plugins area
	 *
	 * @since bbPress (r2737)
	 *
	 * @param array $links Links array in which we would prepend our link
	 * @param string $file Current plugin basename
	 * @return array Processed links
	 */
	public static function modify_plugin_action_links( $links, $file ) {

		// Return normal links if not bbPress
		if ( plugin_basename( bbpress()->file ) !== $file ) {
			return $links;
		}

		// New links to merge into existing links
		$new_links = array();

		// Settings page link
		if ( current_user_can( 'bbp_settings_page' ) ) {
			$new_links['settings'] = '<a href="' . add_query_arg( array( 'page' => 'bbpress'   ), admin_url( 'options-general.php' ) ) . '">' . esc_html__( 'Settings', 'bbpress' ) . '</a>';
		}

		// About page link
		if ( current_user_can( 'bbp_about_page' ) ) {
			$new_links['about']    = '<a href="' . add_query_arg( array( 'page' => 'bbp-about' ), admin_url( 'index.php'           ) ) . '">' . esc_html__( 'About',    'bbpress' ) . '</a>';
		}

		// Add a few links to the existing links array
		return array_merge( $links, $new_links );
	}

	/**
	 * Add the 'Right now in Forums' dashboard widget
	 *
	 * @since bbPress (r2770)
	 *
	 * @uses wp_add_dashboard_widget() To add the dashboard widget
	 */
	public static function dashboard_widget_right_now() {
		wp_add_dashboard_widget( 'bbp-dashboard-right-now', __( 'Right Now in Forums', 'bbpress' ), 'bbp_dashboard_widget_right_now' );
	}

	/**
	 * Add a link to bbPress about page to the admin bar
	 *
	 * @since bbPress (r5136)
	 *
	 * @param WP_Admin_Bar $wp_admin_bar
	 */
	public function admin_bar_about_link( $wp_admin_bar ) {

		if ( is_user_logged_in() ) {

			$wp_admin_bar->add_menu( array(
				'parent' => 'wp-logo',
				'id'     => 'bbp-about',
				'title'  => esc_html__( 'About bbPress', 'bbpress' ),
				'href'   => add_query_arg( array( 'page' => 'bbp-about' ), admin_url( 'index.php' ) )
			) );
		}
	}

	/**
	 * Enqueue any admin scripts we might need
	 * @since bbPress (r4260)
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 'suggest' );
	}

	/**
	 * Add some general styling to the admin area
	 *
	 * @since bbPress (r2464)
	 *
	 * @uses bbp_get_forum_post_type() To get the forum post type
	 * @uses bbp_get_topic_post_type() To get the topic post type
	 * @uses bbp_get_reply_post_type() To get the reply post type
	 * @uses sanitize_html_class() To sanitize the classes
	 */
	public function admin_head() {

		// Remove the individual recount and converter menus.
		// They are grouped together by h2 tabs
		remove_submenu_page( 'tools.php', 'bbp-repair'    );
		remove_submenu_page( 'tools.php', 'bbp-converter' );
		remove_submenu_page( 'tools.php', 'bbp-reset'     );
		remove_submenu_page( 'index.php', 'bbp-about'     );
		remove_submenu_page( 'index.php', 'bbp-credits'   );

		// The /wp-admin/images/ folder
		$wp_admin_url     = admin_url( 'images/' );

		// Icons for top level admin menus
		$version          = bbp_get_version();
		$menu_icon_url    = $this->images_url . 'menu.png?ver='       . $version;
		$icon32_url       = $this->images_url . 'icons32.png?ver='    . $version;
		$menu_icon_url_2x = $this->images_url . 'menu-2x.png?ver='    . $version;
		$icon32_url_2x    = $this->images_url . 'icons32-2x.png?ver=' . $version;
		$badge_url        = $this->images_url . 'badge.png?ver='      . $version;
		$badge_url_2x     = $this->images_url . 'badge-2x.png?ver='   . $version;

		// The image size changed in WordPress 3.5
		if ( function_exists( 'wp_enqueue_media' ) ) {
			$icon32_size = '756px 45px';
		} else {
			$icon32_size = '708px 45px';
		}

		// Top level menu classes
		$forum_class = sanitize_html_class( bbp_get_forum_post_type() );
		$topic_class = sanitize_html_class( bbp_get_topic_post_type() );
		$reply_class = sanitize_html_class( bbp_get_reply_post_type() );

		// Post type checker (only topics and replies)
		if ( 'post' === get_current_screen()->base ) :
			switch( get_current_screen()->post_type ) :
				case bbp_get_reply_post_type() :
				case bbp_get_topic_post_type() : ?>
					<script type="text/javascript">
						jQuery(document).ready(function() {
							var bbp_author_id = jQuery( '#bbp_author_id' );
							bbp_author_id.suggest( ajaxurl + '?action=bbp_suggest_user', {
								onSelect: function() {
									var value = this.value;
									bbp_author_id.val( value.substr( 0, value.indexOf( ' ' ) ) );
								}
							} );
						<?php if ( bbp_get_topic_post_type() === get_current_screen()->post_type ) : ?>
							jQuery( '#misc-publishing-actions' ).find( '.misc-pub-section' ).first().remove();
							jQuery( '#save-action' ).remove();
						<?php elseif ( bbp_get_reply_post_type() === get_current_screen()->post_type ) : ?>
							var bbp_topic_id = jQuery( '#bbp_topic_id' );
							bbp_topic_id.suggest( ajaxurl + '?action=bbp_suggest_topic', {
								onSelect: function() {
									var value = this.value;
									bbp_topic_id.val( value.substr( 0, value.indexOf( ' ' ) ) );
								}
							} );
						<?php endif; ?>
						});

					</script><?php

					break;
			endswitch;
		endif; ?>

		<style type="text/css" media="screen">
		/*<![CDATA[*/

			/* Kludge for too-wide forums dropdown */
			#poststuff #bbp_forum_attributes select#parent_id,
			#poststuff #bbp_topic_attributes select#parent_id,
			#poststuff #bbp_reply_attributes select#bbp_forum_id {
				max-width: 170px;
			}

			/* Version Badge */

			.bbp-badge {
				padding-top: 142px;
				height: 50px;
				width: 173px;
				color: #fafafa;
				font-weight: bold;
				font-size: 14px;
				text-align: center;
				margin: 0 -5px;
				background: url('<?php echo $badge_url; ?>') no-repeat;
			}

			.about-wrap .bbp-badge {
				position: absolute;
				top: 0;
				right: 0;
			}
				body.rtl .about-wrap .bbp-badge {
					right: auto;
					left: 0;
				}

			#bbp-dashboard-right-now p.sub,
			#bbp-dashboard-right-now .table,
			#bbp-dashboard-right-now .versions {
				margin: -12px;
			}

			#bbp-dashboard-right-now .inside {
				font-size: 12px;
				padding-top: 20px;
				margin-bottom: 0;
			}

			#bbp-dashboard-right-now p.sub {
				padding: 5px 0 15px;
				color: #8f8f8f;
				font-size: 14px;
				position: absolute;
				top: -17px;
				left: 15px;
			}
				body.rtl #bbp-dashboard-right-now p.sub {
					right: 15px;
					left: 0;
				}

			#bbp-dashboard-right-now .table {
				margin: 0;
				padding: 0;
				position: relative;
			}

			#bbp-dashboard-right-now .table_content {
				float: left;
				border-top: #ececec 1px solid;
				width: 45%;
			}
				body.rtl #bbp-dashboard-right-now .table_content {
					float: right;
				}

			#bbp-dashboard-right-now .table_discussion {
				float: right;
				border-top: #ececec 1px solid;
				width: 45%;
			}
				body.rtl #bbp-dashboard-right-now .table_discussion {
					float: left;
				}

			#bbp-dashboard-right-now table td {
				padding: 3px 0;
				white-space: nowrap;
			}

			#bbp-dashboard-right-now table tr.first td {
				border-top: none;
			}

			#bbp-dashboard-right-now td.b {
				padding-right: 6px;
				text-align: right;
				font-family: Georgia, "Times New Roman", "Bitstream Charter", Times, serif;
				font-size: 14px;
				width: 1%;
			}
				body.rtl #bbp-dashboard-right-now td.b {
					padding-left: 6px;
					padding-right: 0;
				}

			#bbp-dashboard-right-now td.b a {
				font-size: 18px;
			}

			#bbp-dashboard-right-now td.b a:hover {
				color: #d54e21;
			}

			#bbp-dashboard-right-now .t {
				font-size: 12px;
				padding-right: 12px;
				padding-top: 6px;
				color: #777;
			}
				body.rtl #bbp-dashboard-right-now .t {
					padding-left: 12px;
					padding-right: 0;
				}

			#bbp-dashboard-right-now .t a {
				white-space: nowrap;
			}

			#bbp-dashboard-right-now .spam {
				color: red;
			}

			#bbp-dashboard-right-now .waiting {
				color: #e66f00;
			}

			#bbp-dashboard-right-now .approved {
				color: green;
			}

			#bbp-dashboard-right-now .versions {
				padding: 6px 10px 12px;
				clear: both;
			}

			#bbp-dashboard-right-now .versions .b {
				font-weight: bold;
			}

			#bbp-dashboard-right-now a.button {
				float: right;
				clear: right;
				position: relative;
				top: -5px;
			}
				body.rtl #bbp-dashboard-right-now a.button {
					float: left;
					clear: left;
				}

			/* Icon 32 */
			#icon-edit.icon32-posts-<?php echo $forum_class; ?>,
			#icon-edit.icon32-posts-<?php echo $topic_class; ?>,
			#icon-edit.icon32-posts-<?php echo $reply_class; ?> {
				background: url('<?php echo $icon32_url; ?>');
				background-repeat: no-repeat;
			}

			/* Icon Positions */
			#icon-edit.icon32-posts-<?php echo $forum_class; ?> {
				background-position: -4px 0px;
			}

			#icon-edit.icon32-posts-<?php echo $topic_class; ?> {
				background-position: -4px -90px;
			}

			#icon-edit.icon32-posts-<?php echo $reply_class; ?> {
				background-position: -4px -180px;
			}

			/* Icon 32 2x */
			@media only screen and (-webkit-min-device-pixel-ratio: 1.5) {
				#icon-edit.icon32-posts-<?php echo $forum_class; ?>,
				#icon-edit.icon32-posts-<?php echo $topic_class; ?>,
				#icon-edit.icon32-posts-<?php echo $reply_class; ?> {
					background-image: url('<?php echo $icon32_url_2x; ?>');
					background-size: 45px 255px;
				}
			}

			/* Menu */
			#menu-posts-<?php echo $forum_class; ?> .wp-menu-image,
			#menu-posts-<?php echo $topic_class; ?> .wp-menu-image,
			#menu-posts-<?php echo $reply_class; ?> .wp-menu-image,

			#menu-posts-<?php echo $forum_class; ?>:hover .wp-menu-image,
			#menu-posts-<?php echo $topic_class; ?>:hover .wp-menu-image,
			#menu-posts-<?php echo $reply_class; ?>:hover .wp-menu-image,

			#menu-posts-<?php echo $forum_class; ?>.wp-has-current-submenu .wp-menu-image,
			#menu-posts-<?php echo $topic_class; ?>.wp-has-current-submenu .wp-menu-image,
			#menu-posts-<?php echo $reply_class; ?>.wp-has-current-submenu .wp-menu-image {
				background: url('<?php echo $menu_icon_url; ?>');
				background-repeat: no-repeat;
			}

			/* Menu Positions */
			#menu-posts-<?php echo $forum_class; ?> .wp-menu-image {
				background-position: 0px -32px;
			}
			#menu-posts-<?php echo $forum_class; ?>:hover .wp-menu-image,
			#menu-posts-<?php echo $forum_class; ?>.wp-has-current-submenu .wp-menu-image {
				background-position: 0px 0px;
			}
			#menu-posts-<?php echo $topic_class; ?> .wp-menu-image {
				background-position: -70px -32px;
			}
			#menu-posts-<?php echo $topic_class; ?>:hover .wp-menu-image,
			#menu-posts-<?php echo $topic_class; ?>.wp-has-current-submenu .wp-menu-image {
				background-position: -70px 0px;
			}
			#menu-posts-<?php echo $reply_class; ?> .wp-menu-image {
				background-position: -35px -32px;
			}
			#menu-posts-<?php echo $reply_class; ?>:hover .wp-menu-image,
			#menu-posts-<?php echo $reply_class; ?>.wp-has-current-submenu .wp-menu-image {
				background-position:  -35px 0px;
			}

			/* Menu 2x */
			@media only screen and (-webkit-min-device-pixel-ratio: 1.5) {
				#menu-posts-<?php echo $forum_class; ?> .wp-menu-image,
				#menu-posts-<?php echo $topic_class; ?> .wp-menu-image,
				#menu-posts-<?php echo $reply_class; ?> .wp-menu-image,

				#menu-posts-<?php echo $forum_class; ?>:hover .wp-menu-image,
				#menu-posts-<?php echo $topic_class; ?>:hover .wp-menu-image,
				#menu-posts-<?php echo $reply_class; ?>:hover .wp-menu-image,

				#menu-posts-<?php echo $forum_class; ?>.wp-has-current-submenu .wp-menu-image,
				#menu-posts-<?php echo $topic_class; ?>.wp-has-current-submenu .wp-menu-image,
				#menu-posts-<?php echo $reply_class; ?>.wp-has-current-submenu .wp-menu-image {
					background-image: url('<?php echo $menu_icon_url_2x; ?>');
					background-size: 100px 64px;
				}

				.bbp-badge {
					background-image: url('<?php echo $badge_url_2x; ?>');
					background-size: 173px 194px;
				}
			}

			<?php if ( 'bbpress' === get_user_option( 'admin_color' ) ) : ?>

				/* Green Scheme Images */

				.post-com-count {
					background-image: url('<?php echo $wp_admin_url; ?>bubble_bg.gif');
				}

				.button,
				.submit input,
				.button-secondary {
					background-image: url('<?php echo $wp_admin_url; ?>white-grad.png');
				}

				.button:active,
				.submit input:active,
				.button-secondary:active {
					background-image: url('<?php echo $wp_admin_url; ?>white-grad-active.png');
				}

				.curtime #timestamp {
					background-image: url('<?php echo $wp_admin_url; ?>date-button.gif');
				}

				.tagchecklist span a,
				#bulk-titles div a {
					background-image: url('<?php echo $wp_admin_url; ?>xit.gif');
				}

				.tagchecklist span a:hover,
				#bulk-titles div a:hover {
					background-image: url('<?php echo $wp_admin_url; ?>xit.gif');
				}
				#screen-meta-links a.show-settings {
					background-image: url('<?php echo $wp_admin_url; ?>arrows.png');
				}

				#screen-meta-links a.show-settings.screen-meta-active {
					background-image: url('<?php echo $wp_admin_url; ?>arrows.png');
				}

				#adminmenushadow,
				#adminmenuback {
					background-image: url('<?php echo $wp_admin_url; ?>menu-shadow.png');
				}

				#adminmenu li.wp-has-current-submenu.wp-menu-open .wp-menu-toggle,
				#adminmenu li.wp-has-current-submenu:hover .wp-menu-toggle {
					background-image: url('<?php echo $wp_admin_url; ?>arrows-dark.png');
				}

				#adminmenu .wp-has-submenu:hover .wp-menu-toggle,
				#adminmenu .wp-menu-open .wp-menu-toggle {
					background-image: url('<?php echo $wp_admin_url; ?>arrows.png');
				}

				#collapse-button div {
					background-image: url('<?php echo $wp_admin_url; ?>arrows.png');
				}

				/* menu and screen icons */
				.icon16.icon-dashboard,
				#adminmenu .menu-icon-dashboard div.wp-menu-image {
					background-image: url('<?php echo $wp_admin_url; ?>menu.png?ver=20100531');
				}

				#adminmenu .menu-icon-dashboard:hover div.wp-menu-image,
				#adminmenu .menu-icon-dashboard.wp-has-current-submenu div.wp-menu-image,
				#adminmenu .menu-icon-dashboard.current div.wp-menu-image {
					background-image: url('<?php echo $wp_admin_url; ?>menu.png?ver=20100531');
				}

				.icon16.icon-post,
				#adminmenu .menu-icon-post div.wp-menu-image {
					background-image: url('<?php echo $wp_admin_url; ?>menu.png?ver=20100531');
				}

				#adminmenu .menu-icon-post:hover div.wp-menu-image,
				#adminmenu .menu-icon-post.wp-has-current-submenu div.wp-menu-image {
					background-image: url('<?php echo $wp_admin_url; ?>menu.png?ver=20100531');
				}

				.icon16.icon-media,
				#adminmenu .menu-icon-media div.wp-menu-image {
					background-image: url('<?php echo $wp_admin_url; ?>menu.png?ver=20100531');
				}

				#adminmenu .menu-icon-media:hover div.wp-menu-image,
				#adminmenu .menu-icon-media.wp-has-current-submenu div.wp-menu-image {
					background-image: url('<?php echo $wp_admin_url; ?>menu.png?ver=20100531');
				}

				.icon16.icon-links,
				#adminmenu .menu-icon-links div.wp-menu-image {
					background-image: url('<?php echo $wp_admin_url; ?>menu.png?ver=20100531');
				}

				#adminmenu .menu-icon-links:hover div.wp-menu-image,
				#adminmenu .menu-icon-links.wp-has-current-submenu div.wp-menu-image {
					background-image: url('<?php echo $wp_admin_url; ?>menu.png?ver=20100531');
				}

				.icon16.icon-page,
				#adminmenu .menu-icon-page div.wp-menu-image {
					background-image: url('<?php echo $wp_admin_url; ?>menu.png?ver=20100531');
				}

				#adminmenu .menu-icon-page:hover div.wp-menu-image,
				#adminmenu .menu-icon-page.wp-has-current-submenu div.wp-menu-image {
					background-image: url('<?php echo $wp_admin_url; ?>menu.png?ver=20100531');
				}

				.icon16.icon-comments,
				#adminmenu .menu-icon-comments div.wp-menu-image {
					background-image: url('<?php echo $wp_admin_url; ?>menu.png?ver=20100531');
				}

				#adminmenu .menu-icon-comments:hover div.wp-menu-image,
				#adminmenu .menu-icon-comments.wp-has-current-submenu div.wp-menu-image,
				#adminmenu .menu-icon-comments.current div.wp-menu-image {
					background-image: url('<?php echo $wp_admin_url; ?>menu.png?ver=20100531');
				}

				.icon16.icon-appearance,
				#adminmenu .menu-icon-appearance div.wp-menu-image {
					background-image: url('<?php echo $wp_admin_url; ?>menu.png?ver=20100531');
				}

				#adminmenu .menu-icon-appearance:hover div.wp-menu-image,
				#adminmenu .menu-icon-appearance.wp-has-current-submenu div.wp-menu-image {
					background-image: url('<?php echo $wp_admin_url; ?>menu.png?ver=20100531');
				}

				.icon16.icon-plugins,
				#adminmenu .menu-icon-plugins div.wp-menu-image {
					background-image: url('<?php echo $wp_admin_url; ?>menu.png?ver=20100531');
				}

				#adminmenu .menu-icon-plugins:hover div.wp-menu-image,
				#adminmenu .menu-icon-plugins.wp-has-current-submenu div.wp-menu-image {
					background-image: url('<?php echo $wp_admin_url; ?>menu.png?ver=20100531');
				}

				.icon16.icon-users,
				#adminmenu .menu-icon-users div.wp-menu-image {
					background-image: url('<?php echo $wp_admin_url; ?>menu.png?ver=20100531');
				}

				#adminmenu .menu-icon-users:hover div.wp-menu-image,
				#adminmenu .menu-icon-users.wp-has-current-submenu div.wp-menu-image,
				#adminmenu .menu-icon-users.current div.wp-menu-image {
					background-image: url('<?php echo $wp_admin_url; ?>menu.png?ver=20100531');
				}

				.icon16.icon-tools,
				#adminmenu .menu-icon-tools div.wp-menu-image {
					background-image: url('<?php echo $wp_admin_url; ?>menu.png?ver=20100531');
				}

				#adminmenu .menu-icon-tools:hover div.wp-menu-image,
				#adminmenu .menu-icon-tools.wp-has-current-submenu div.wp-menu-image,
				#adminmenu .menu-icon-tools.current div.wp-menu-image {
					background-image: url('<?php echo $wp_admin_url; ?>menu.png?ver=20100531');
				}

				.icon16.icon-settings,
				#adminmenu .menu-icon-settings div.wp-menu-image {
					background-image: url('<?php echo $wp_admin_url; ?>menu.png?ver=20100531');
				}

				#adminmenu .menu-icon-settings:hover div.wp-menu-image,
				#adminmenu .menu-icon-settings.wp-has-current-submenu div.wp-menu-image {
					background-image: url('<?php echo $wp_admin_url; ?>menu.png?ver=20100531');
				}

				.icon16.icon-site,
				#adminmenu .menu-icon-site div.wp-menu-image {
					background-image: url('<?php echo $wp_admin_url; ?>menu.png?ver=20100531');
				}

				#adminmenu .menu-icon-site:hover div.wp-menu-image,
				#adminmenu .menu-icon-site.wp-has-current-submenu div.wp-menu-image {
					background-image: url('<?php echo $wp_admin_url; ?>menu.png?ver=20100531');
				}
				/* end menu and screen icons */

				/* Screen Icons */
				.icon32.icon-post,
				#icon-edit,
				#icon-post {
					background-image: url('<?php echo $wp_admin_url; ?>icons32.png?ver=20100531');
				}

				.icon32.icon-dashboard,
				#icon-index {
					background-image: url('<?php echo $wp_admin_url; ?>icons32.png?ver=20100531');
				}

				.icon32.icon-media,
				#icon-upload {
					background-image: url('<?php echo $wp_admin_url; ?>icons32.png?ver=20100531');
				}

				.icon32.icon-links,
				#icon-link-manager,
				#icon-link,
				#icon-link-category {
					background-image: url('<?php echo $wp_admin_url; ?>icons32.png?ver=20100531');
				}

				.icon32.icon-page,
				#icon-edit-pages,
				#icon-page {
					background-image: url('<?php echo $wp_admin_url; ?>icons32.png?ver=20100531');
				}

				.icon32.icon-comments,
				#icon-edit-comments {
					background-image: url('<?php echo $wp_admin_url; ?>icons32.png?ver=20100531');
				}

				.icon32.icon-appearance,
				#icon-themes {
					background-image: url('<?php echo $wp_admin_url; ?>icons32.png?ver=20100531');
				}

				.icon32.icon-plugins,
				#icon-plugins {
					background-image: url('<?php echo $wp_admin_url; ?>icons32.png?ver=20100531');
				}

				.icon32.icon-users,
				#icon-users,
				#icon-profile,
				#icon-user-edit {
					background-image: url('<?php echo $wp_admin_url; ?>icons32.png?ver=20100531');
				}

				.icon32.icon-tools,
				#icon-tools,
				#icon-admin {
					background-image: url('<?php echo $wp_admin_url; ?>icons32.png?ver=20100531');
				}

				.icon32.icon-settings,
				#icon-options-general {
					background-image: url('<?php echo $wp_admin_url; ?>icons32.png?ver=20100531');
				}

				.icon32.icon-site,
				#icon-ms-admin {
					background-image: url('<?php echo $wp_admin_url; ?>icons32.png?ver=20100531');
				}
				/* end screen icons */

				.meta-box-sortables .postbox:hover .handlediv {
					background-image: url('<?php echo $wp_admin_url; ?>arrows.png');
				}

				.tablenav .tablenav-pages a {
					background-image: url('<?php echo $wp_admin_url; ?>menu-bits.gif?ver=20100610');
				}

				.view-switch #view-switch-list {
					background-image: url('<?php echo $wp_admin_url; ?>list.png');
				}

				.view-switch .current #view-switch-list {
					background-image: url('<?php echo $wp_admin_url; ?>list.png');
				}

				.view-switch #view-switch-excerpt {
					background-image: url('<?php echo $wp_admin_url; ?>list.png');
				}

				.view-switch .current #view-switch-excerpt {
					background-image: url('<?php echo $wp_admin_url; ?>list.png');
				}

				#header-logo {
					background-image: url('<?php echo $wp_admin_url; ?>wp-logo.png?ver=20110504');
				}

				.sidebar-name-arrow {
					background-image: url('<?php echo $wp_admin_url; ?>arrows.png');
				}

				.sidebar-name:hover .sidebar-name-arrow {
					background-image: url('<?php echo $wp_admin_url; ?>arrows-dark.png');
				}

				.item-edit {
					background-image: url('<?php echo $wp_admin_url; ?>arrows.png');
				}

				.item-edit:hover {
					background-image: url('<?php echo $wp_admin_url; ?>arrows-dark.png');
				}

				.wp-badge {
					background-image: url('<?php echo $wp_admin_url; ?>wp-badge.png');
					background-image: url('<?php echo $wp_admin_url; ?>wp-badge.png'), -ms-linear-gradient(top, #378aac, #165d84); /* IE10 */
					background-image: url('<?php echo $wp_admin_url; ?>wp-badge.png'), -moz-linear-gradient(top, #378aac, #165d84); /* Firefox */
					background-image: url('<?php echo $wp_admin_url; ?>wp-badge.png'), -o-linear-gradient(top, #378aac, #165d84); /* Opera */
					background-image: url('<?php echo $wp_admin_url; ?>wp-badge.png'), -webkit-gradient(linear, left top, left bottom, from(#378aac), to(#165d84)); /* old Webkit */
					background-image: url('<?php echo $wp_admin_url; ?>wp-badge.png'), -webkit-linear-gradient(top, #378aac, #165d84); /* new Webkit */
					background-image: url('<?php echo $wp_admin_url; ?>wp-badge.png'), linear-gradient(top, #378aac, #165d84); /* proposed W3C Markup */
				}

				.rtl .post-com-count {
					background-image: url('<?php echo $wp_admin_url; ?>bubble_bg-rtl.gif');
				}

				/* Menu */
				.rtl #adminmenushadow,
				.rtl #adminmenuback {
					background-image: url('<?php echo $wp_admin_url; ?>menu-shadow-rtl.png');
				}

				.rtl #adminmenu li.wp-has-current-submenu.wp-menu-open .wp-menu-toggle,
				.rtl #adminmenu li.wp-has-current-submenu:hover .wp-menu-toggle {
					background-image: url('<?php echo $wp_admin_url; ?>arrows-dark.png');
				}

				.rtl #adminmenu .wp-has-submenu:hover .wp-menu-toggle,
				.rtl #adminmenu .wp-menu-open .wp-menu-toggle {
					background-image: url('<?php echo $wp_admin_url; ?>arrows.png');
				}

				.rtl .meta-box-sortables .postbox:hover .handlediv {
					background-image: url('<?php echo $wp_admin_url; ?>arrows.png');
				}

				.rtl .tablenav .tablenav-pages a {
					background-image: url('<?php echo $wp_admin_url; ?>menu-bits-rtl.gif?ver=20100610');
				}

				.rtl .sidebar-name-arrow {
					background-image: url('<?php echo $wp_admin_url; ?>arrows.png');
				}

				.rtl .sidebar-name:hover .sidebar-name-arrow {
					background-image: url('<?php echo $wp_admin_url; ?>arrows-dark.png');
				}

				@media only screen and (-webkit-min-device-pixel-ratio: 1.5) {
					.icon32.icon-post,
					#icon-edit,
					#icon-post,
					.icon32.icon-dashboard,
					#icon-index,
					.icon32.icon-media,
					#icon-upload,
					.icon32.icon-links,
					#icon-link-manager,
					#icon-link,
					#icon-link-category,
					.icon32.icon-page,
					#icon-edit-pages,
					#icon-page,
					.icon32.icon-comments,
					#icon-edit-comments,
					.icon32.icon-appearance,
					#icon-themes,
					.icon32.icon-plugins,
					#icon-plugins,
					.icon32.icon-users,
					#icon-users,
					#icon-profile,
					#icon-user-edit,
					.icon32.icon-tools,
					#icon-tools,
					#icon-admin,
					.icon32.icon-settings,
					#icon-options-general,
					.icon32.icon-site,
					#icon-ms-admin {
						background-image: url('<?php echo $wp_admin_url; ?>icons32-2x.png?ver=20120412') !important;
						background-size: <?php echo $icon32_size; ?>
					}

					.icon16.icon-dashboard,
					.menu-icon-dashboard div.wp-menu-image,
					.icon16.icon-post,
					.menu-icon-post div.wp-menu-image,
					.icon16.icon-media,
					.menu-icon-media div.wp-menu-image,
					.icon16.icon-links,
					.menu-icon-links div.wp-menu-image,
					.icon16.icon-page,
					.menu-icon-page div.wp-menu-image,
					.icon16.icon-comments,
					.menu-icon-comments div.wp-menu-image,
					.icon16.icon-appearance,
					.menu-icon-appearance div.wp-menu-image,
					.icon16.icon-plugins,
					.menu-icon-plugins div.wp-menu-image,
					.icon16.icon-users,
					.menu-icon-users div.wp-menu-image,
					.icon16.icon-tools,
					.menu-icon-tools div.wp-menu-image,
					.icon16.icon-settings,
					.menu-icon-settings div.wp-menu-image,
					.icon16.icon-site,
					.menu-icon-site div.wp-menu-image {
						background-image: url('<?php echo $wp_admin_url; ?>menu-2x.png?ver=20120412') !important;
						background-size: 390px 64px;
					}
				}
			<?php endif; ?>

		/*]]>*/
		</style>

		<?php
	}

	/**
	 * Registers the bbPress admin color scheme
	 *
	 * Because wp-content can exist outside of the WordPress root there is no
	 * way to be certain what the relative path of the admin images is.
	 * We are including the two most common configurations here, just in case.
	 *
	 * @since bbPress (r2521)
	 *
	 * @uses wp_admin_css_color() To register the color scheme
	 */
	public function register_admin_style () {

		// Updated admin color scheme CSS
		if ( function_exists( 'wp_enqueue_media' ) ) {
			$green_scheme = $this->styles_url . 'green.css';

		} else {
			$green_scheme = $this->styles_url . 'green-34.css';
		}

		// Register the green scheme
		wp_admin_css_color( 'bbpress', esc_html_x( 'Green', 'admin color scheme', 'bbpress' ), $green_scheme, array( '#222222', '#006600', '#deece1', '#6eb469' ) );
	}

	/**
	 * Hide theme compat package selection if only 1 package is registered
	 *
	 * @since bbPress (r4315)
	 *
	 * @param array $sections Forums settings sections
	 * @return array
	 */
	public function hide_theme_compat_packages( $sections = array() ) {
		if ( count( bbpress()->theme_compat->packages ) <= 1 )
			unset( $sections['bbp_settings_theme_compat'] );

		return $sections;
	}

	/**
	 * Allow keymaster role to save Forums settings
	 *
	 * @since bbPress (r4678)
	 *
	 * @param string $capability
	 * @return string Return 'keep_gate' capability
	 */
	public function option_page_capability_bbpress( $capability = 'manage_options' ) {
		$capability = 'keep_gate';
		return $capability;
	}

	/** Ajax ******************************************************************/

	/**
	 * Ajax action for facilitating the forum auto-suggest
	 *
	 * @since bbPress (r4261)
	 *
	 * @uses get_posts()
	 * @uses bbp_get_topic_post_type()
	 * @uses bbp_get_topic_id()
	 * @uses bbp_get_topic_title()
	 */
	public function suggest_topic() {

		// Try to get some topics
		$topics = get_posts( array(
			's'         => like_escape( $_REQUEST['q'] ),
			'post_type' => bbp_get_topic_post_type()
		) );

		// If we found some topics, loop through and display them
		if ( ! empty( $topics ) ) {
			foreach ( (array) $topics as $post ) {
				printf( esc_html__( '%s - %s', 'bbpress' ), bbp_get_topic_id( $post->ID ), bbp_get_topic_title( $post->ID ) . "\n" );
			}
		}
		die();
	}

	/**
	 * Ajax action for facilitating the topic and reply author auto-suggest
	 *
	 * @since bbPress (r5014)
	 */
	public function suggest_user() {

		// Try to get some users
		$users_query = new WP_User_Query( array(
			'search'         => '*' . like_escape( $_REQUEST['q'] ) . '*',
			'fields'         => array( 'ID', 'user_nicename' ),
			'search_columns' => array( 'ID', 'user_nicename', 'user_email' ),
			'orderby'        => 'ID'
		) );

		// If we found some users, loop through and display them
		if ( ! empty( $users_query->results ) ) {
			foreach ( (array) $users_query->results as $user ) {
				printf( esc_html__( '%s - %s', 'bbpress' ), bbp_get_user_id( $user->ID ), bbp_get_user_nicename( $user->ID, array( 'force' => $user->user_nicename ) ) . "\n" );
			}
		}
		die();
	}

	/** About *****************************************************************/

	/**
	 * Output the about screen
	 *
	 * @since bbPress (r4159)
	 */
	public function about_screen() {

		list( $display_version ) = explode( '-', bbp_get_version() ); ?>

		<div class="wrap about-wrap">
			<h1><?php printf( esc_html__( 'Welcome to bbPress %s', 'bbpress' ), $display_version ); ?></h1>
			<div class="about-text"><?php printf( esc_html__( 'Thank you for updating! bbPress %s is bundled up and ready to weather the storm of users in your community!', 'bbpress' ), $display_version ); ?></div>
			<div class="bbp-badge"><?php printf( esc_html__( 'Version %s', 'bbpress' ), $display_version ); ?></div>

			<h2 class="nav-tab-wrapper">
				<a class="nav-tab nav-tab-active" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'bbp-about' ), 'index.php' ) ) ); ?>">
					<?php esc_html_e( 'What&#8217;s New', 'bbpress' ); ?>
				</a><a class="nav-tab" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'bbp-credits' ), 'index.php' ) ) ); ?>">
					<?php esc_html_e( 'Credits', 'bbpress' ); ?>
				</a>
			</h2>

			<div class="changelog">
				<h3><?php esc_html_e( 'Forum Subscriptions', 'bbpress' ); ?></h3>

				<div class="feature-section col two-col">
					<div class="last-feature">
						<h4><?php esc_html_e( 'Subscribe to Forums', 'bbpress' ); ?></h4>
						<p><?php esc_html_e( 'Now your users can subscribe to new topics in specific forums.', 'bbpress' ); ?></p>
					</div>

					<div>
						<h4><?php esc_html_e( 'Manage Subscriptions', 'bbpress' ); ?></h4>
						<p><?php esc_html_e( 'Your users can manage all of their subscriptions in one convenient location.', 'bbpress' ); ?></p>
					</div>
				</div>
			</div>

			<div class="changelog">
				<h3><?php esc_html_e( 'Converters', 'bbpress' ); ?></h3>

				<div class="feature-section col one-col">
					<div class="last-feature">
						<p><?php esc_html_e( 'We&#8217;re all abuzz about the hive of new importers, AEF, Drupal, FluxBB, Kunena Forums for Joomla, MyBB, Phorum, PHPFox, PHPWind, PunBB, SMF, Xenforo and XMB. Existing importers are now sweeter than honey with improved importing stickies, topic tags, forum categories and the sting is now gone if you need to remove imported users.', 'bbpress' ); ?></p>
					</div>
				</div>

				<div class="feature-section col three-col">
					<div>
						<h4><?php esc_html_e( 'Theme Compatibility', 'bbpress' ); ?></h4>
						<p><?php esc_html_e( 'Better handling of styles and scripts in the template stack.', 'bbpress' ); ?></p>
					</div>

					<div>
						<h4><?php esc_html_e( 'Polyglot support', 'bbpress' ); ?></h4>
						<p><?php esc_html_e( 'bbPress fully supports automatic translation updates.', 'bbpress' ); ?></p>
					</div>

					<div class="last-feature">
						<h4><?php esc_html_e( 'User capabilities', 'bbpress' ); ?></h4>
						<p><?php esc_html_e( 'Roles and capabilities have been swept through, cleaned up, and simplified.', 'bbpress' ); ?></p>
					</div>
				</div>
			</div>

			<div class="return-to-dashboard">
				<a href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'bbpress' ), 'options-general.php' ) ) ); ?>"><?php esc_html_e( 'Go to Forum Settings', 'bbpress' ); ?></a>
			</div>

		</div>

		<?php
	}

	/**
	 * Output the credits screen
	 *
	 * Hardcoding this in here is pretty janky. It's fine for 2.2, but we'll
	 * want to leverage api.wordpress.org eventually.
	 *
	 * @since bbPress (r4159)
	 */
	public function credits_screen() {

		list( $display_version ) = explode( '-', bbp_get_version() ); ?>

		<div class="wrap about-wrap">
			<h1><?php printf( esc_html__( 'Welcome to bbPress %s', 'bbpress' ), $display_version ); ?></h1>
			<div class="about-text"><?php printf( esc_html__( 'Thank you for updating! bbPress %s is waxed, polished, and ready for you to take it for a lap or two around the block!', 'bbpress' ), $display_version ); ?></div>
			<div class="bbp-badge"><?php printf( esc_html__( 'Version %s', 'bbpress' ), $display_version ); ?></div>

			<h2 class="nav-tab-wrapper">
				<a href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'bbp-about' ), 'index.php' ) ) ); ?>" class="nav-tab">
					<?php esc_html_e( 'What&#8217;s New', 'bbpress' ); ?>
				</a><a href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'bbp-credits' ), 'index.php' ) ) ); ?>" class="nav-tab nav-tab-active">
					<?php esc_html_e( 'Credits', 'bbpress' ); ?>
				</a>
			</h2>

			<p class="about-description"><?php esc_html_e( 'bbPress is created by a worldwide swarm of busy, busy bees.', 'bbpress' ); ?></p>

			<h4 class="wp-people-group"><?php esc_html_e( 'Project Leaders', 'bbpress' ); ?></h4>
			<ul class="wp-people-group " id="wp-people-group-project-leaders">
				<li class="wp-person" id="wp-person-matt">
					<a href="http://profiles.wordpress.org/matt"><img src="http://0.gravatar.com/avatar/767fc9c115a1b989744c755db47feb60?s=60" class="gravatar" alt="Matt Mullenweg" /></a>
					<a class="web" href="http://profiles.wordpress.org/matt">Matt Mullenweg</a>
					<span class="title"><?php esc_html_e( 'Founding Developer', 'bbpress' ); ?></span>
				</li>
				<li class="wp-person" id="wp-person-johnjamesjacoby">
					<a href="http://profiles.wordpress.org/johnjamesjacoby"><img src="http://0.gravatar.com/avatar/81ec16063d89b162d55efe72165c105f?s=60" class="gravatar" alt="John James Jacoby" /></a>
					<a class="web" href="http://profiles.wordpress.org/johnjamesjacoby">John James Jacoby</a>
					<span class="title"><?php esc_html_e( 'Lead Developer', 'bbpress' ); ?></span>
				</li>
				<li class="wp-person" id="wp-person-jmdodd">
					<a href="http://profiles.wordpress.org/jmdodd"><img src="http://0.gravatar.com/avatar/6a7c997edea340616bcc6d0fe03f65dd?s=60" class="gravatar" alt="Jennifer M. Dodd" /></a>
					<a class="web" href="http://profiles.wordpress.org/jmdodd">Jennifer M. Dodd</a>
					<span class="title"><?php esc_html_e( 'Feature Developer', 'bbpress' ); ?></span>
				</li>
				<li class="wp-person" id="wp-person-netweb">
					<a href="http://profiles.wordpress.org/netweb"><img src="http://0.gravatar.com/avatar/97e1620b501da675315ba7cfb740e80f?s=60" class="gravatar" alt="Stephen Edgar" /></a>
					<a class="web" href="http://profiles.wordpress.org/netweb">Stephen Edgar</a>
					<span class="title"><?php esc_html_e( 'Converter Specialist', 'bbpress' ); ?></span>
				</li>
			</ul>

			<h4 class="wp-people-group"><?php esc_html_e( 'Contributing Developers', 'bbpress' ); ?></h4>
			<ul class="wp-people-group " id="wp-people-group-contributing-developers">
				<li class="wp-person" id="wp-person-jaredatch">
					<a href="http://profiles.wordpress.org/jaredatch"><img src="http://0.gravatar.com/avatar/e341eca9e1a85dcae7127044301b4363?s=60" class="gravatar" alt="Jared Atchison" /></a>
					<a class="web" href="http://profiles.wordpress.org/jaredatch">Jared Atchison</a>
					<span class="title"><?php esc_html_e( 'Bug Testing', 'bbpress' ); ?></span>
				</li>
				<li class="wp-person" id="wp-person-gautamgupta">
					<a href="http://profiles.wordpress.org/gautamgupta"><img src="http://0.gravatar.com/avatar/b0810422cbe6e4eead4def5ae7a90b34?s=60" class="gravatar" alt="Gautam Gupta" /></a>
					<a class="web" href="http://profiles.wordpress.org/gautamgupta">Gautam Gupta</a>
					<span class="title"><?php esc_html_e( 'Feature Developer', 'bbpress' ); ?></span>
				</li>
			</ul>

			<h4 class="wp-people-group"><?php esc_html_e( 'Core Contributors to bbPress 2.5', 'bbpress' ); ?></h4>
			<p class="wp-credits-list">
				<a href="http://profiles.wordpress.org/alex-ye">alex-ye</a>,
				<a href="http://profiles.wordpress.org/alexvorn2">alexvorn2</a>,
				<a href="http://profiles.wordpress.org/aliso">aliso</a>,
				<a href="http://profiles.wordpress.org/boonebgorges">boonebgorges</a>,
				<a href="http://profiles.wordpress.org/daveshine">daveshine</a>,
				<a href="http://profiles.wordpress.org/DJPaul">DJPaul</a>,
				<a href="http://profiles.wordpress.org/ethitter">ethitter</a>,
				<a href="http://profiles.wordpress.org/fanquake">fanquake</a>,
				<a href="http://profiles.wordpress.org/GargajCNS">GargajCNS</a>,
				<a href="http://profiles.wordpress.org/GautamGupta">GautamGupta</a>,
				<a href="http://profiles.wordpress.org/imath">imath</a>,
				<a href="http://profiles.wordpress.org/jkudish">jkudish</a>,
				<a href="http://profiles.wordpress.org/kobenland">kobenland</a>,
				<a href="http://profiles.wordpress.org/lakrisgubben">lakrisgubben</a>,
				<a href="http://profiles.wordpress.org/loki_racer">loki_racer</a>,
				<a href="http://profiles.wordpress.org/mamaduka">mamaduka</a>,
				<a href="http://profiles.wordpress.org/Maty">Maty</a>,
				<a href="http://profiles.wordpress.org/mercime">mercime</a>,
				<a href="http://profiles.wordpress.org/mordauk">mordauk</a>,
				<a href="http://profiles.wordpress.org/mrcl">mrcl</a>,
				<a href="http://profiles.wordpress.org/MZAWeb">MZAWeb</a>,
				<a href="http://profiles.wordpress.org/r-a-y">r-a-y</a>,
				<a href="http://profiles.wordpress.org/strangerstudios">strangerstudios</a>,
				<a href="http://profiles.wordpress.org/thebrandonallen">thebrandonallen</a>,
				<a href="http://profiles.wordpress.org/tlovett1">tlovett1</a>,
				<a href="http://profiles.wordpress.org/wpdennis">wpdennis</a>,
			</p>

			<div class="return-to-dashboard">
				<a href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'bbpress' ), 'options-general.php' ) ) ); ?>"><?php esc_html_e( 'Go to Forum Settings', 'bbpress' ); ?></a>
			</div>

		</div>

		<?php
	}

	/** Updaters **************************************************************/

	/**
	 * Update all bbPress forums across all sites
	 *
	 * @since bbPress (r3689)
	 *
	 * @global WPDB $wpdb
	 * @uses get_blog_option()
	 * @uses wp_remote_get()
	 */
	public static function update_screen() {

		// Get action
		$action = isset( $_GET['action'] ) ? $_GET['action'] : ''; ?>

		<div class="wrap">
			<div id="icon-edit" class="icon32 icon32-posts-topic"><br /></div>
			<h2><?php esc_html_e( 'Update Forum', 'bbpress' ); ?></h2>

		<?php

		// Taking action
		switch ( $action ) {
			case 'bbp-update' :

				// Run the full updater
				bbp_version_updater(); ?>

				<p><?php esc_html_e( 'All done!', 'bbpress' ); ?></p>
				<a class="button" href="index.php?page=bbp-update"><?php esc_html_e( 'Go Back', 'bbpress' ); ?></a>

				<?php

				break;

			case 'show' :
			default : ?>

				<p><?php esc_html_e( 'You can update your forum through this page. Hit the link below to update.', 'bbpress' ); ?></p>
				<p><a class="button" href="index.php?page=bbp-update&amp;action=bbp-update"><?php esc_html_e( 'Update Forum', 'bbpress' ); ?></a></p>

			<?php break;

		} ?>

		</div><?php
	}

	/**
	 * Update all bbPress forums across all sites
	 *
	 * @since bbPress (r3689)
	 *
	 * @global WPDB $wpdb
	 * @uses get_blog_option()
	 * @uses wp_remote_get()
	 */
	public static function network_update_screen() {
		global $wpdb;

		// Get action
		$action = isset( $_GET['action'] ) ? $_GET['action'] : ''; ?>

		<div class="wrap">
			<div id="icon-edit" class="icon32 icon32-posts-topic"><br /></div>
			<h2><?php esc_html_e( 'Update Forums', 'bbpress' ); ?></h2>

		<?php

		// Taking action
		switch ( $action ) {
			case 'bbpress-update' :

				// Site counter
				$n = isset( $_GET['n'] ) ? intval( $_GET['n'] ) : 0;

				// Get blogs 5 at a time
				$blogs = $wpdb->get_results( "SELECT * FROM {$wpdb->blogs} WHERE site_id = '{$wpdb->siteid}' AND spam = '0' AND deleted = '0' AND archived = '0' ORDER BY registered DESC LIMIT {$n}, 5", ARRAY_A );

				// No blogs so all done!
				if ( empty( $blogs ) ) : ?>

					<p><?php esc_html_e( 'All done!', 'bbpress' ); ?></p>
					<a class="button" href="update-core.php?page=bbpress-update"><?php esc_html_e( 'Go Back', 'bbpress' ); ?></a>

				<?php

				// Still have sites to loop through
				else : ?>

					<ul>

						<?php foreach ( (array) $blogs as $details ) :

							$siteurl = get_blog_option( $details['blog_id'], 'siteurl' ); ?>

							<li><?php echo $siteurl; ?></li>

							<?php

							// Get the response of the bbPress update on this site
							$response = wp_remote_get(
								trailingslashit( $siteurl ) . 'wp-admin/index.php?page=bbp-update&action=bbp-update',
								array( 'timeout' => 30, 'httpversion' => '1.1' )
							);

							// Site errored out, no response?
							if ( is_wp_error( $response ) )
								wp_die( sprintf( __( 'Warning! Problem updating %1$s. Your server may not be able to connect to sites running on it. Error message: <em>%2$s</em>', 'bbpress' ), $siteurl, $response->get_error_message() ) );

							// Switch to the new blog
							switch_to_blog( $details[ 'blog_id' ] );

							$basename = bbpress()->basename;

							// Run the updater on this site
							if ( is_plugin_active_for_network( $basename ) || is_plugin_active( $basename ) ) {
								bbp_version_updater();
							}

							// restore original blog
							restore_current_blog();

							// Do some actions to allow plugins to do things too
							do_action( 'after_bbpress_upgrade', $response             );
							do_action( 'bbp_upgrade_site',      $details[ 'blog_id' ] );

						endforeach; ?>

					</ul>

					<p>
						<?php esc_html_e( 'If your browser doesn&#8217;t start loading the next page automatically, click this link:', 'bbpress' ); ?>
						<a class="button" href="update-core.php?page=bbpress-update&amp;action=bbpress-update&amp;n=<?php echo ( $n + 5 ); ?>"><?php esc_html_e( 'Next Forums', 'bbpress' ); ?></a>
					</p>
					<script type='text/javascript'>
						<!--
						function nextpage() {
							location.href = 'update-core.php?page=bbpress-update&action=bbpress-update&n=<?php echo ( $n + 5 ) ?>';
						}
						setTimeout( 'nextpage()', 250 );
						//-->
					</script><?php

				endif;

				break;

			case 'show' :
			default : ?>

				<p><?php esc_html_e( 'You can update all the forums on your network through this page. It works by calling the update script of each site automatically. Hit the link below to update.', 'bbpress' ); ?></p>
				<p><a class="button" href="update-core.php?page=bbpress-update&amp;action=bbpress-update"><?php esc_html_e( 'Update Forums', 'bbpress' ); ?></a></p>

			<?php break;

		} ?>

		</div><?php
	}
}
endif; // class_exists check

/**
 * Setup bbPress Admin
 *
 * @since bbPress (r2596)
 *
 * @uses BBP_Admin
 */
function bbp_admin() {
	bbpress()->admin = new BBP_Admin();

	bbpress()->admin->converter = new BBP_Converter();
}

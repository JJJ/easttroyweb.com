<?php

class UnboxInit {
	/**
	 * The one instance of UnboxInit.
	 *
	 * @since 1.0.
	 *
	 * @var   UnboxInit
	 */
	private static $instance;

	/**
	 * Current unbox version
	 *
	 * @since 1.0.
	 *
	 * @var   float    The current version number.
	 */
	private static $version = 1.0;

	/**
	 * Key for storing the unbox status.
	 *
	 * @since 1.0.
	 *
	 * @var   string    The key used in the db.
	 */
	private static $key;

	/**
	 * Instantiate or return the one UnboxInit instance.
	 *
	 * @since  1.0.
	 *
	 * @return UnboxInit
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initiate actions.
	 *
	 * @since  1.0.
	 *
	 * @return UnboxInit
	 */
	public function __construct() {
		global $pagenow;

		// Create the key needed for checking the theme
		self::$key = 'unbox-' . md5( wp_get_theme()->template );

		// Only load if on the right page
		if ( 'themes.php' === $pagenow && isset( $_GET['activated'] ) && true === (bool) $_GET['activated'] && ! $this->is_unboxed() ) {
			/**
			 * Load the .mo file. This is a reworking of "load_plugin_textdomain". Given that this is a plugin within
			 * a theme, the file paths do not work correctly with "load_plugin_textdomain" or "load_theme_textdomain".
			 * This functionality implements identical functionality with the correct paths.
			 */
			$domain = 'unbox';
			$locale = apply_filters( 'plugin_locale', get_locale(), $domain );
			$path   = WP_LANG_DIR . '/themes/';
			$mofile = $domain . '-' . $locale . '.mo';

			// Attempt to load from within the languages directory
			if ( true !== load_textdomain( $domain, $path . $mofile ) ) {
				// As a fallback, load from the plugin
				$path = trailingslashit( get_template_directory() ) . 'includes/unbox/languages/';
				load_textdomain( $domain, $path . $mofile );
			}

			// Add the CSS and JS to display the unboxing
			add_action( 'admin_enqueue_scripts', array( $this, 'load_assets' ), 20 );

			// Add the HTML to display the unboxing
			add_action( 'admin_footer', array( $this, 'load_template' ), 20 );

			// Because everything is unboxed, denote that the unboxing happened
			$this->denote_unboxed();
		}
	}

	/**
	 * Enqueue the stylesheet and JS for unbox.
	 *
	 * @since  1.0.
	 *
	 * @return void
	 */
	public function load_assets() {
		$stylesheet_uri = $this->_get_file_location( 'css/unbox-style.css', 'uri' );

		// Add the styles
		if ( ! empty( $stylesheet_uri ) ) {
			wp_enqueue_style(
				'unbox-stylesheet',
				$stylesheet_uri,
				array(),
				$this->get_version(),
				'all'
			);
		}

		// Add the script
		wp_enqueue_script(
			'unbox-script',
			get_template_directory_uri() . '/includes/unbox/js/unbox.js',
			array( 'jquery' ),
			$this->get_version(),
			true
		);
	}

	/**
	 * Load the HTML for the unbox message.
	 *
	 * @since  1.0.
	 *
	 * @return void
	 */
	public function load_template() {
		$template_path = $this->_get_file_location( 'template-unbox.php', 'path' );

		if ( ! empty( $template_path ) ) {
			require( $template_path );
		}
	}

	/**
	 * Return the location of the most sensible stylesheet.
	 *
	 * @since  1.0.
	 *
	 * @param  string    $name    Name of the file to get.
	 * @param  bool      $what    Either 'path' or 'uri'.
	 * @return string             The URI of the stylesheet.
	 */
	private function _get_file_location( $name, $what ) {
		if ( empty( $name ) ) {
			return '';
		}

		/**
		 * Check the following locations for the file:
		 * - /child-theme/{file-name}
		 * - /child-theme/includes/unbox/{file-name}
		 * - /parent-theme/{file-name}
		 * - /parent-theme/includes/unbox/{file-name}
		 */
		if ( file_exists( get_stylesheet_directory() . '/' . $name ) ) {
			$file_uri  = get_stylesheet_directory_uri() . '/' . $name;
			$file_path = get_stylesheet_directory() . '/' . $name;
		} elseif ( file_exists( get_stylesheet_directory() . '/includes/unbox/' . $name ) ) {
			$file_uri  = get_stylesheet_directory_uri() . '/includes/unbox/' . $name;
			$file_path = get_stylesheet_directory() . '/includes/unbox/' . $name;
		} elseif ( file_exists( get_template_directory() . '/' . $name ) ) {
			$file_uri  = get_template_directory_uri() . '/' . $name;
			$file_path = get_template_directory() . '/' . $name;
		} else {
			$file_uri  = get_template_directory_uri() . '/includes/unbox/' . $name;
			$file_path = get_template_directory() . '/includes/unbox/' . $name;
		}

		// Return the requested string
		if ( 'path' === $what ) {
			return $file_path;
		} else {
			return $file_uri;
		}
	}

	/**
	 * Get the link for the documentation for the theme.
	 *
	 * @since  1.0.
	 *
	 * @return string    Link for documentation.
	 */
	public function get_documention_link() {
		if ( defined( 'IS_WPCOM' ) && IS_WPCOM ) {
			$url = 'http://theme.wordpress.com/themes/' . strtolower( $this->get_theme_name() ) . '/support/';
		} else {
			$url = 'http://thethemefoundry.com/tutorials/' . strtolower( $this->get_theme_name() );
		}

		return $url;
	}

	/**
	 * Get the link for the support forums for the theme.
	 *
	 * @since  1.0.
	 *
	 * @return string    Link for support forum.
	 */
	public function get_support_link() {
		if ( defined( 'IS_WPCOM' ) && IS_WPCOM ) {
			$url = 'http://premium-themes.forums.wordpress.com/forum/' . strtolower( $this->get_theme_name() );
		} else {
			$url = 'http://thethemefoundry.com/questions/forum/' . strtolower( $this->get_theme_name() );
		}

		return $url;
	}

	/**
	 * Get the name of the current theme.
	 *
	 * @since  1.0.
	 *
	 * @return string    The name of the current theme.
	 */
	public function get_theme_name() {
		$theme = wp_get_theme();
		return $theme->get( 'Name' );
	}

	/**
	 * Set a value to the database to denote that unboxing occurred.
	 *
	 * @since  1.0.
	 *
	 * @return void
	 */
	public function denote_unboxed() {
		update_option( self::$key, 1 );
	}

	/**
	 * Whether or not the theme has been unboxed or not.
	 *
	 * @since  1.0.
	 *
	 * @return bool    Whether or not the theme is unboxed.
	 */
	public function is_unboxed() {
		$message_has_been_displayed = ( 1 === (int) get_option( self::$key, 0 ) );
		$debug_mode                 = ( defined( 'UNBOX_DEBUG' ) && true === UNBOX_DEBUG );

		if ( $debug_mode ) {
			return false;
		} else {
			return $message_has_been_displayed;
		}
	}

	/**
	 * Get the version of the plugin.
	 *
	 * @since  1.0.
	 *
	 * @return float    The current version number.
	 */
	public function get_version() {
		return self::$version;
	}
}

/**
 * Instantiate or return the one UnboxInit instance.
 *
 * @since  1.0.
 *
 * @return UnboxInit
 */
function unbox_init() {
	return UnboxInit::instance();
}

add_action( 'admin_init', 'unbox_init' );
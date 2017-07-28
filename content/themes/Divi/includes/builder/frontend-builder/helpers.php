<?php


function et_fb_shortcode_tags() {
	global $shortcode_tags;

	$shortcode_tag_names = array();
	foreach ( $shortcode_tags as $shortcode_tag_name => $shortcode_tag_cb ) {
		$shortcode_tag_names[] = $shortcode_tag_name;
	}
	return implode( '|', $shortcode_tag_names );
}

function et_fb_prepare_library_cats() {
	$raw_categories_array = apply_filters( 'et_pb_new_layout_cats_array', get_terms( 'layout_category', array( 'hide_empty' => false ) ) );
	$clean_categories_array = array();

	if ( is_array( $raw_categories_array ) && ! empty( $raw_categories_array ) ) {
		foreach( $raw_categories_array as $category ) {
			$clean_categories_array[] = array(
				'name' => html_entity_decode( $category->name ),
				'id' => $category->term_id,
				'slug' => $category->slug,
			);
		}
	}

	return $clean_categories_array;
}

function et_fb_get_layout_type( $post_id ) {
	$post_terms  = wp_get_post_terms( $post_id, 'layout_type' );
	$layout_type = $post_terms[0]->slug;

	return $layout_type;
}

function et_fb_comments_template() {
	return dirname(__FILE__) . '/comments_template.php';
}

function et_fb_modify_comments_request( $params ) {
	// modify the request parameters the way it doesn't change the result just to make request with unique parameters
	$params->query_vars['type__not_in'] = 'et_pb_comments_random_type_9999';
}

// comments template cannot be generated via AJAX so prepare it beforehand
function et_fb_get_comments_markup() {
	// Modify the comments request to make sure it's unique.
	// Otherwise WP generates SQL error and doesn't allow multiple comments sections on single page
	add_action( 'pre_get_comments', 'et_fb_modify_comments_request', 1 );

	// include custom comments_template to display the comment section with Divi style
	add_filter( 'comments_template', 'et_fb_comments_template' );

	ob_start();
	comments_template( '', true );
	$comments_content = ob_get_contents();
	ob_end_clean();

	// remove all the actions and filters to not break the default comments section from theme
	remove_filter( 'comments_template', 'et_fb_comments_template' );
	remove_action( 'pre_get_comments', 'et_fb_modify_comments_request', 1 );

	return $comments_content;
}

function et_fb_backend_helpers() {
	global $post, $paged;

	$layout_type = '';

	$post_type    = isset( $post->post_type ) ? $post->post_type : false;
	$post_id      = isset( $post->ID ) ? $post->ID : false;
	$post_status  = isset( $post->post_status ) ? $post->post_status : false;

	if ( 'et_pb_layout' === $post_type ) {
		$layout_type = et_fb_get_layout_type( $post_id );
	}

	$google_fonts = array_merge( array( 'Default' => array() ), et_builder_get_google_fonts() );
	$current_user = wp_get_current_user();
	$current_url  = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

	$export_url = add_query_arg( array(
		'et_core_portability' => true,
		'context'             => 'et_builder',
		'name'                => 'temp_name',
		'nonce'               => wp_create_nonce( 'et_core_portability_nonce' ),
	), admin_url() );

	$fb_modules_array = apply_filters( 'et_fb_modules_array', ET_Builder_Element::get_modules_array( $post_type, true, true ) );

	$helpers = array(
		'debug'                        => true,
		'postId'                       => $post_id,
		'postStatus'                   => $post_status,
		'postType'                     => $post_type,
		'layoutType'                   => $layout_type,
		'publishCapability'            => ( is_page() && ! current_user_can( 'publish_pages' ) ) || ( ! is_page() && ! current_user_can( 'publish_posts' ) ) ? 'no_publish' : 'publish',
		'shortcodeObject'              => array(),
		'ajaxUrl'                      => admin_url( 'admin-ajax.php' ),
		'tinymceSkinUrl'               => ET_FB_ASSETS_URI . '/vendors/tinymce-skin',
		'tinymceCSSFiles'              => esc_url( includes_url( 'js/tinymce' ) . '/skins/wordpress/wp-content.css' ),
		'images_uri'                   => ET_BUILDER_URI .'/images',
		'generalFields'                => array(),
		'advancedFields'               => array(),
		'customCssFields'              => array(),
		'moduleParentShortcodes'       => ET_Builder_Element::get_parent_shortcodes( $post_type ),
		'moduleChildShortcodes'        => ET_Builder_Element::get_child_shortcodes( $post_type ),
		'moduleChildSlugs'             => ET_Builder_Element::get_child_slugs( $post_type ),
		'moduleRawContentShortcodes'   => ET_Builder_Element::get_raw_content_shortcodes( $post_type ),
		'modules'                      => $fb_modules_array,
		'modulesCount'                 => count( $fb_modules_array ),
		'modulesWithChildren'          => ET_Builder_Element::get_shortcodes_with_children( $post_type ),
		'structureModules'             => ET_Builder_Element::get_structure_modules( $post_type ),
		'et_builder_css_media_queries' => ET_Builder_Element::get_media_quries( 'for_js' ),
		'commentsModuleMarkup'         => et_fb_get_comments_markup(),
		'shortcode_tags'               => et_fb_shortcode_tags(),
		'getFontIconSymbols'           => et_pb_get_font_icon_symbols(),
		'failureNotification'          => et_builder_get_failure_notification_modal(),
		'exitNotification'             => et_builder_get_exit_notification_modal(),
		'getTaxonomies'                => apply_filters( 'et_fb_taxonomies', array(
			'category'                 => get_categories(),
			'project_category'         => get_categories( array( 'taxonomy' => 'project_category' ) ),
			'product_category'         => class_exists( 'WooCommerce' ) ? get_terms( 'product_cat' ) : '',
		) ),
		'googleAPIKey'                 => et_pb_is_allowed( 'theme_options' ) ? get_option( 'et_google_api_settings' ) : '',
		'googleFontsList'              => array_keys( $google_fonts ),
		'googleFonts'                  => $google_fonts,
		'gutterWidth'                  => et_get_option( 'gutter_width', 3 ),
		'fontIcons'                    => et_pb_get_font_icon_symbols(),
		'fontIconsDown'                => et_pb_get_font_down_icon_symbols(),
		'widgetAreas'                  => et_builder_get_widget_areas_list(),
		'site_url'                     => get_site_url(),
		'etBuilderAccentColor'         => et_builder_accent_color(),
		'gmt_offset_string'            => et_pb_get_gmt_offset_string(),
		'et_builder_fonts_data'        => et_builder_get_fonts(),
		'currentUserDisplayName'       => $current_user->display_name,
		'locale'                       => get_locale(),
		'roleSettings'                 => et_pb_get_role_settings(),
		'currentRole'                  => et_pb_get_current_user_role(),
		'exportUrl'                    => $export_url,
		'urls'                         => array(
			'loginFormUrl'             => esc_url( site_url( 'wp-login.php', 'login_post' ) ),
			'forgotPasswordUrl'        => esc_url( wp_lostpassword_url() ),
			'logoutUrl'                => esc_url( wp_logout_url() ),
			'logoutUrlRedirect'        => esc_url( wp_logout_url( $current_url ) ),
			'themeOptionsUrl'          => esc_url( et_pb_get_options_page_link() ),
			'builderPreviewStyle'      => ET_BUILDER_URI . '/styles/preview.css',
		),
		'nonces'                       => array(
			'moduleContactFormSubmit'  => wp_create_nonce( 'et-pb-contact-form-submit' ),
			'et_admin_load'            => wp_create_nonce( 'et_admin_load_nonce' ),
			'computedProperty'         => wp_create_nonce( 'et_pb_process_computed_property_nonce' ),
			'renderShortcode'          => wp_create_nonce( 'et_pb_render_shortcode_nonce' ),
			'backendHelper'            => wp_create_nonce( 'et_fb_backend_helper_nonce' ),
			'renderSave'               => wp_create_nonce( 'et_fb_save_nonce' ),
			'prepareShortcode'         => wp_create_nonce( 'et_fb_prepare_shortcode_nonce' ),
			'processImportedData'      => wp_create_nonce( 'et_fb_process_imported_data_nonce' ),
			'retrieveLibraryModules'   => wp_create_nonce( 'et_fb_retrieve_library_modules_nonce' ),
			'saveLibraryModules'       => wp_create_nonce( 'et_fb_save_library_modules_nonce' ),
			'preview'                  => wp_create_nonce( 'et_pb_preview_nonce' ),
		),
		'conditionalTags'              => et_fb_conditional_tag_params(),
		'currentPage'                  => et_fb_current_page_params(),
		'appPreferences'               => et_fb_app_preferences(),
		'classNames'                   => array(
			'hide_on_mobile_class'     => 'et-hide-mobile',
		),
		'columnLayouts'                => et_builder_get_columns(),
		'pageSettingsFields'           => et_pb_get_builder_settings_configurations(),
		'pageSettingsValues'           => et_pb_get_builder_settings_values(),
		'splitTestSubjects'            => false !== ( $all_subjects_raw = get_post_meta( $post_id, '_et_pb_ab_subjects' , true ) ) ? explode( ',', $all_subjects_raw ) : array(),
		'defaults'                     => array(
			'contactFormInputs'        => et_fb_process_shortcode( sprintf(
				'[et_pb_contact_field field_title="%1$s" field_type="input" field_id="Name" required_mark="on" fullwidth_field="off" /][et_pb_contact_field field_title="%2$s" field_type="email" field_id="Email" required_mark="on" fullwidth_field="off" /][et_pb_contact_field field_title="%3$s" field_type="text" field_id="Message" required_mark="on" fullwidth_field="on" /]',
				esc_attr__( 'Name', 'et_builder' ),
				esc_attr__( 'Email Address', 'et_builder' ),
				esc_attr__( 'Message', 'et_builder' )
			) ),
		),
		'all_modules_default_attrs'        => ET_Builder_Element::get_all_modules_default_fields( $post_type ),
		'saveModuleLibraryCategories'      => et_fb_prepare_library_cats(),
		'columnSettingFields'              => array(
			'advanced'                     => array(
				'bg_img_%s'                => array(
					'label'                => esc_html__( 'Column %s Background Image', 'et_builder' ),
					'type'                 => 'upload',
					'option_category'      => 'basic_option',
					'upload_button_text'   => esc_attr__( 'Upload an image', 'et_builder' ),
					'choose_text'          => esc_attr__( 'Choose a Background Image', 'et_builder' ),
					'update_text'          => esc_attr__( 'Set As Background', 'et_builder' ),
					'description'          => esc_html__( 'If defined, this image will be used as the background for this module. To remove a background image, simply delete the URL from the settings field.', 'et_builder' ),
					'tab_slug'             => 'advanced',
				),
				'parallax_%s'              => array(
					'label'                => esc_html__( 'Column %s Parallax Effect', 'et_builder' ),
					'type'                 => 'yes_no_button',
					'option_category'      => 'configuration',
					'options'              => array(
						'on'               => esc_html__( 'Yes', 'et_builder' ),
						'off'              => esc_html__( 'No', 'et_builder' ),
					),
					'affects'              => array(
						'parallax_method_%s',
					),
					'description'          => esc_html__( 'Here you can choose whether or not use parallax effect for the featured image', 'et_builder' ),
					'tab_slug'             => 'advanced',
				),
				'parallax_method_%s'       => array(
					'label'                => esc_html__( 'Column %s Parallax Method', 'et_builder' ),
					'type'                 => 'select',
					'option_category'      => 'configuration',
					'options'              => array(
						'off'              => esc_html__( 'CSS', 'et_builder' ),
						'on'               => esc_html__( 'True Parallax', 'et_builder' ),
					),
					'depends_show_if'      => 'on',
					'depends_to'           => array(
						'parallax_%s',
					),
					'description'          => esc_html__( 'Here you can choose which parallax method to use for the featured image', 'et_builder' ),
					'tab_slug'             => 'advanced',
				),
				'background_color_%s'      => array(
					'label'                => esc_html__( 'Column %s Background Color', 'et_builder' ),
					'type'                 => 'color-alpha',
					'custom_color'         => true,
					'tab_slug'             => 'advanced',
				),
				'padding_%s'               => array(
					'label'                => esc_html__( 'Column %s Custom Padding', 'et_builder' ),
					'type'                 => 'custom_padding',
					'mobile_options'       => true,
					'option_category'      => 'layout',
					'description'          => esc_html__( 'Adjust padding to specific values, or leave blank to use the default padding.', 'et_builder' ),
					'tab_slug'             => 'advanced',
				),
			),
			'css'                     => array(
				'module_id_%s'        => array(
					'label'           => esc_html__( 'Column %s CSS ID', 'et_builder' ),
					'type'            => 'text',
					'option_category' => 'configuration',
					'tab_slug'        => 'custom_css',
					'option_class'    => 'et_pb_custom_css_regular',
				),
				'module_class_%s'     => array(
					'label'           => esc_html__( 'Column %s CSS Class', 'et_builder' ),
					'type'            => 'text',
					'option_category' => 'configuration',
					'tab_slug'        => 'custom_css',
					'option_class'    => 'et_pb_custom_css_regular',
				),
				'custom_css_before_%s'=> array(
					'label'           => esc_html__( 'Column %s before', 'et_builder' ),
					'no_space_before_selector' => true,
					'selector'        => ':before',
				),
				'custom_css_main_%s'  => array(
					'label'           => esc_html__( 'Column %s Main Element', 'et_builder' ),
				),
				'custom_css_after_%s' => array(
					'label'           => esc_html__( 'Column %s After', 'et_builder' ),
					'no_space_before_selector' => true,
					'selector'        => ':after',
				),

			),
		),
	);

	// Internationalization.
	$helpers['i18n'] = array(
		'modules'      => array(
			'audio'    => array(
				'meta' => _x( 'by <strong>%1$s</strong>', 'Audio Module meta information', 'et_builder' ),
			),
			'contactForm' => array(
				'thankYou' => esc_html__( 'Thanks for contacting us', 'et_builder' ),
				'submit'   => esc_attr__( 'Submit', 'et_builder' ),
			),
			'countdownTimer' => array(
				'dayFull'     => esc_html__( 'Day(s)', 'et_builder' ),
				'dayShort'    => esc_html__( 'Day', 'et_builder' ),
				'hourFull'    => esc_html__( 'Hour(s)', 'et_builder' ),
				'hourShort'   => esc_html__( 'Hrs', 'et_builder' ),
				'minuteFull'  => esc_html__( 'Minute(s)', 'et_builder' ),
				'minuteShort' => esc_html__( 'Min', 'et_builder' ),
				'secondFull'  => esc_html__( 'Second(s)', 'et_builder' ),
				'secondShort' => esc_html__( 'Sec', 'et_builder' ),
			),
			'signup' => array(
				'emailAddress' => esc_attr__( 'Email Address', 'et_builder' ),
				'firstName'    => esc_attr__( 'First Name', 'et_builder' ),
				'lastName'     => esc_attr__( 'Last Name', 'et_builder' ),
				'name'         => esc_attr__( 'Name', 'et_builder' ),
				'email'        => esc_attr__( 'Email', 'et_builder' ),
			),
			'filterablePortfolio' => array(
				'all' => esc_html__( 'All', 'et_builder' ),
			),
			'login' => array(
				'loginAs'         => sprintf( esc_html__( 'Login as %s', 'et_builder' ), $current_user->display_name ),
				'login'           => esc_html__( 'Login', 'et_builder' ),
				'logout'          => esc_html__( 'Log out', 'et_builder' ),
				'forgotPassword'  => esc_html__( 'Forgot your password?', 'et_builder' ),
				'username'        => esc_html__( 'Username', 'et_builder' ),
				'password'        => esc_html__( 'Password', 'et_builder' ),
			),
			'search' => array(
				'submitButtonText' => esc_html__( 'Search', 'et_builder' ),
				'searchfor' => esc_html__( 'Search for:', 'et_builder' ),
			),
			'fullwidthPostSlider' => array(
				'by' => esc_html( 'by ', 'et_builder' ),
			),
		),
		'saveButtonText'               => esc_attr__( 'Save', 'et_builder' ),
		'saveDraftButtonText'          => esc_attr__( 'Save Draft', 'et_builder' ),
		'publishButtonText'            => ( is_page() && ! current_user_can( 'publish_pages' ) ) || ( ! is_page() && ! current_user_can( 'publish_posts' ) ) ? esc_attr__( 'Submit', 'et_builder' ) : esc_attr__( 'Publish', 'et_builder' ),
		'controls'                     => array(
			'tinymce'                  => array(
				'visual'               => esc_html__( 'Visual', 'et_builder' ),
				'text'                 => esc_html__( 'Text', 'et_builder' ),
			),
			'moduleItem'               => array(
				'addNew'               => esc_html__( 'Add New Item', 'et_builder' ),
			),
			'upload'                   => array(
				'buttonText'           => esc_html__( 'Upload', 'et_builder' ),
			),
			'insertMedia'              => array(
				'buttonText'           => esc_html__( 'Add Media', 'et_builder' ),
				'modalTitleText'       => esc_html__( 'Insert Media', 'et_builder' ),
			),
			'inputMargin'              => array(
				'top'                  => esc_html__( 'Top', 'et_builder' ),
				'right'                => esc_html__( 'Right', 'et_builder' ),
				'bottom'               => esc_html__( 'Bottom', 'et_builder' ),
				'left'                 => esc_html__( 'Left', 'et_builder' ),
			),
			'colorpicker'              => array(
				'clear'                => esc_html__( 'Clear', 'et_builder' ),
			),
			'uploadGallery'            => array(
				'uploadButtonText'     => esc_html__( 'Update Gallery', 'et_builder'),
			),
			'centerMap'                => array(
				'updateMapButtonText'  => esc_html__( 'Find', 'et_builder'),
				'geoCodeError'         => esc_html__( 'Geocode was not successful for the following reason', 'et_builder' ),
				'geoCodeError_2'       => esc_html__( 'Geocoder failed due to', 'et_builder' ),
				'noResults'            => esc_html__( 'No results found', 'et_builder' ),
				'mapPinAddressInvalid' => esc_html__( 'Invalid Pin and address data. Please try again.', 'et_builder' ),
			),
			'tabs'                     => array(
				'general'              => esc_html__( 'General', 'et_builder' ),
				'design'               => esc_html__( 'Design', 'et_builder' ),
				'css'                  => esc_html__( 'CSS', 'et_builder' ),
			),
			'additionalButton'         => array(
				'changeApiKey'         => esc_html__( 'Change API Key', 'et_builder' ),
				'generateImageUrlFromVideo' => esc_html__( 'Generate From Video', 'et_builder' ),
			),
		),
		'rightClickMenuItems' => array(
			'undo'            => esc_html__( 'Undo', 'et_builder' ),
			'redo'            => esc_html__( 'Redo', 'et_builder' ),
			'lock'            => esc_html__( 'Lock', 'et_builder' ),
			'unlock'          => esc_html__( 'Unlock', 'et_builder' ),
			'copy'            => esc_html__( 'Copy', 'et_builder' ),
			'paste'           => esc_html__( 'Paste', 'et_builder' ),
			'copyStyle'       => esc_html__( 'Copy Style', 'et_builder' ),
			'pasteStyle'      => esc_html__( 'Paste Style', 'et_builder' ),
			'disable'         => esc_html__( 'Disable', 'et_builder' ),
			'enable'          => esc_html__( 'Enable', 'et_builder' ),
			'save'            => esc_html__( 'Save to Library', 'et_builder' ),
			'moduleType'      => array(
				'module'      => esc_html__( 'Module', 'et_builder' ),
				'row'         => esc_html__( 'Row', 'et_builder' ),
				'section'     => esc_html__( 'Section', 'et_builder' ),
			),
			'disableGlobal'   => esc_html__( 'Disable Global', 'et_builder' ),
		),
		'tooltips'            => array(
			'insertModule'     => esc_html__( 'Insert Module', 'et_builder' ),
			'insertColumn'     => esc_html__( 'Insert Columns', 'et_builder' ),
			'insertSection'    => esc_html__( 'Insert Section', 'et_builder' ),
			'insertRow'        => esc_html__( 'Insert Row', 'et_builder' ),
			'newModule'        => esc_html__( 'New Module', 'et_builder' ),
			'newRow'           => esc_html__( 'New Row', 'et_builder' ),
			'newSection'       => esc_html__( 'New Section', 'et_builder' ),
			'addFromLibrary'   => esc_html__( 'Add From Library', 'et_builder' ),
			'addToLibrary'     => esc_html__( 'Add to Library', 'et_builder' ),
			'loading'          => esc_html__( 'loading...', 'et_builder' ),
			'regular'          => esc_html__( 'Regular', 'et_builder' ),
			'fullwidth'        => esc_html__( 'Fullwidth', 'et_builder' ),
			'specialty'        => esc_html__( 'Specialty', 'et_builder' ),
			'changeRow'        => esc_html__( 'Choose Layout', 'et_builder' ),
			'clearLayout'      => esc_html__( 'Clear Layout', 'et_builder' ),
			'clearLayoutText'  => esc_html__( 'All of your current page content will be lost. Do you wish to proceed?', 'et_builder' ),
			'yes'              => esc_html__( 'Yes', 'et_builder' ),
			'loadLayout'       => esc_html__( 'Load From Library', 'et_builder' ),
			'predefinedLayout' => esc_html__( 'Predefined Layouts', 'et_builder' ),
			'replaceLayout'    => esc_html__( 'Replace existing content.', 'et_builder' ),
			'search'           => esc_html__( 'Search', 'et_builder' ) . '...',
			'portability'      => esc_html__( 'Portability', 'et_builder' ),
			'export'           => esc_html__( 'Export', 'et_builder' ),
			'import'           => esc_html__( 'Import', 'et_builder' ),
			'exportText'       => esc_html__( 'Exporting your Divi Builder Layout will create a JSON file that can be imported into a different website.', 'et_builder' ),
			'exportName'       => esc_html__( 'Export File Name', 'et_builder' ),
			'exportButton'     => esc_html__( 'Export Divi Builder Layout', 'et_builder' ),
			'importText'       => esc_html__( 'Importing a previously-exported Divi Builder Layout file will overwrite all content currently on this page.', 'et_builder' ),
			'importField'      => esc_html__( 'Select File To Import', 'et_builder' ),
			'importBackUp'     => esc_html__( 'Download backup before importing', 'et_builder' ),
			'importButton'     => esc_html__( 'Import Divi Builder Layout', 'et_builder' ),
			'noFile'           => esc_html__( 'No File Selected', 'et_builder' ),
			'chooseFile'       => esc_html__( 'Choose File', 'et_builder' ),
		),
		'saveModuleLibraryAttrs'        => array(
			'general'               => esc_html__( 'Include General Settings', 'et_builder' ),
			'advanced'              => esc_html__( 'Include Advanced Design Settings', 'et_builder' ),
			'css'                   => esc_html__( 'Include Custom CSS', 'et_builder' ),
			'selectCategoriesText'  => esc_html__( 'Select category(ies) for new template or type a new name ( optional )', 'et_builder' ),
			'templateName'          => esc_html__( 'Template Name', 'et_builder' ),
			'selectiveSync'         => esc_html__( 'Selective Sync', 'et_builder' ),
			'selectiveError'        => esc_html__( 'Please select at least 1 tab to save', 'et_builder' ),
			'globalTitle'           => esc_html__( 'Save as Global', 'et_builder' ),
			'globalText'            => esc_html__( 'Make this a global item', 'et_builder' ),
			'createCatText'         => esc_html__( 'Create New Category', 'et_builder' ),
			'addToCatText'          => esc_html__( 'Add To Categories', 'et_builder' ),
			'descriptionText'       => esc_html__( 'Here you can add the current item to your Divi Library for later use.', 'et_builder' ),
			'descriptionTextLayout' => esc_html__( 'Save your current page to the Divi Library for later use.', 'et_builder' ),
			'saveText'              => esc_html__( 'Save to Library', 'et_builder' ),
			'allCategoriesText'     => esc_html__( 'All Categories', 'et_builder' ),
		),
		'modals' => array(
			'tabItemTitles' => array(
				'general' => esc_html__( 'General', 'et_builder' ),
				'design'  => esc_html__( 'Design', 'et_builder' ),
				'css'     => esc_html__( 'CSS', 'et_builder' ),
			),
			'pageSettings' => array(
				'title' => esc_html__( 'Page Settings', 'et_builder' ),
			),
		),
		'history' => array(
			'modal' => array(
				'title' => esc_html__( 'Editing History', 'et_builder' ),
				'tabs' => array(
					'states' => esc_html__( 'History States', 'et_builder' ),
				),
			),
			'meta' => et_pb_history_localization(),
		),
		'help' => array(
			'modal' => array(
				'title' => esc_html__( 'Divi Builder Helper', 'et_builder' ),
				'tabs' => array(
					'shortcut' => esc_html__( 'Shortcuts', 'et_builder' ),
				),
			),
			'shortcuts' => array(
				'page_title' => esc_html__( 'Page Shortcuts', 'et_builder' ),
				'page' => array(
					'undo' => array(
						'kbd'  => array( 'super', 'z' ),
						'desc' => esc_html__( 'Undo', 'et_builder' ),
					),
					'redo' => array(
						'kbd'  => array( 'super', 'y' ),
						'desc' => esc_html__( 'Redo', 'et_builder' ),
					),
					'save' => array(
						'kbd'  => array( 'super', 's' ),
						'desc' => esc_html__( 'Save Page', 'et_builder' ),
					),
					'save_as_draft' => array(
						'kbd'  => array( 'super', 'shift' , 's'),
						'desc' => esc_html__( 'Save Page As Draft', 'et_builder' ),
					),
					'exit' => array(
						'kbd'  => array( 'super', 'e' ),
						'desc' => esc_html__( 'Exit Visual Builder', 'et_builder' ),
					),
					'exit_to_backend_builder' => array(
						'kbd'  => array( 'super', 'shift', 'e' ),
						'desc' => esc_html__( 'Exit To Backend Builder', 'et_builder' ),
					),
					'toggle_settings_bar' => array(
						'kbd'  => array( 't' ),
						'desc' => esc_html__( 'Toggle Settings Bar', 'et_builder' ),
					),
					'open_page_settings' => array(
						'kbd'  => array( 'o' ),
						'desc' => esc_html__( 'Open Page Settings', 'et_builder' ),
					),
					'open_history' => array(
						'kbd'  => array( 'h' ),
						'desc' => esc_html__( 'Open History Window', 'et_builder' ),
					),
					'open_portability' => array(
						'kbd'  => array( 'p' ),
						'desc' => esc_html__( 'Open Portability Window', 'et_builder' ),
					),
					'zoom_in' => array(
						'kbd'  => array( 'super', '+' ),
						'desc' => esc_html__( 'Responsive Zoom In', 'et_builder' ),
					),
					'zoom_out' => array(
						'kbd'  => array( 'super', '-' ),
						'desc' => esc_html__( 'Responsive Zoom Out', 'et_builder' ),
					),
					'help' => array(
						'kbd'  => array( '?' ),
						'desc' => esc_html__( 'List All Shortcuts', 'et_builder' ),
					),
				),
				'inline_title' => esc_html__( 'Inline Editor Shortcuts', 'et_builder' ),
				'inline' => array(
					'escape' => array(
						'kbd'  => array( 'esc' ),
						'desc' => esc_html__( 'Exit Inline Editor', 'et_builder' ),
					),
				),
				'module_title' => esc_html__( 'Module Shortcuts', 'et_builder' ),
				'module' => array(
					'module_copy' => array(
						'kbd'  => array( 'super', 'c' ),
						'desc' => esc_html__( 'Copy Module', 'et_builder' ),
					),
					'module_cut' => array(
						'kbd'  => array( 'super', 'x' ),
						'desc' => esc_html__( 'Cut Module', 'et_builder' ),
					),
					'module_paste' => array(
						'kbd'  => array( 'super', 'v' ),
						'desc' => esc_html__( 'Paste Module', 'et_builder' ),
					),
					'module_copy_styles' => array(
						'kbd'  => array( 'super', 'alt', 'c' ),
						'desc' => esc_html__( 'Copy Module Styles', 'et_builder' ),
					),
					'module_paste_styles' => array(
						'kbd'  => array( 'super', 'alt', 'v' ),
						'desc' => esc_html__( 'Paste Module Styles', 'et_builder' ),
					),
					'module_lock' => array(
						'kbd'  => array( 'l' ),
						'desc' => esc_html__( 'Lock Module', 'et_builder' ),
					),
					'module_disable' => array(
						'kbd'  => array( 'd' ),
						'desc' => esc_html__( 'Disable Module', 'et_builder' ),
					),
					'drag_auto_copy' => array(
						'kbd'  => array( 'alt', 'module move' ),
						'desc' => esc_html__( 'Move and copy module into dropped location', 'et_builder' ),
					),
					'column_change_structure' => array(
						'kbd'  => array( 'c', array( '1', '2', '3', '4' ) ),
						'desc' => esc_html__( 'Change Column Structure', 'et_builder' ),
					),
					'row_make_fullwidth' => array(
						'kbd'  => array( 'r', 'f' ),
						'desc' => esc_html__( 'Make Row Fullwidth', 'et_builder' ),
					),
					'row_edit_gutter' => array(
						'kbd'  => array( 'g', array( '1', '2', '3', '4' ) ),
						'desc' => esc_html__( 'Change Gutter Width', 'et_builder' ),
					),
					'add_new_row' => array(
						'kbd'  => array( 'r', array( '1', '2', '3', '4' ) ),
						'desc' => esc_html__( 'Add New Row', 'et_builder' ),
					),
					'add_new_section' => array(
						'kbd'  => array( 's', array( '1', '2', '3' ) ),
						'desc' => esc_html__( 'Add New Section', 'et_builder' ),
					),
					'resize_padding_auto_opposite' => array(
						'kbd'  => array( 'shift', 'Drag Padding' ),
						'desc' => esc_html__( 'Restrict padding to 10px increments', 'et_builder' ),
					),
					'resize_padding_limited' => array(
						'kbd'  => array( 'alt', 'Drag Padding' ),
						'desc' => esc_html__( 'Padding limited to opposing value', 'et_builder' ),
					),
					'resize_padding_10' => array(
						'kbd'  => array( 'shift', 'alt', 'Drag Padding' ),
						'desc' => esc_html__( 'Mirror padding on both sides', 'et_builder' ),
					),
					'increase_padding_row' => array(
						'kbd'  => array( 'r', array( 'left', 'right', 'up', 'down' ) ),
						'desc' => esc_html__( 'Increase Row Padding', 'et_builder' ),
					),
					'decrease_padding_row' => array(
						'kbd'  => array( 'r', 'alt', array( 'left', 'right', 'up', 'down' ) ),
						'desc' => esc_html__( 'Decrease Row Padding', 'et_builder' ),
					),
					'increase_padding_section' => array(
						'kbd'  => array( 's', array( 'left', 'right', 'up', 'down' ) ),
						'desc' => esc_html__( 'Increase Section Padding', 'et_builder' ),
					),
					'decrease_padding_section' => array(
						'kbd'  => array( 's', 'alt', array( 'left', 'right', 'up', 'down' ) ),
						'desc' => esc_html__( 'Decrease Section Padding', 'et_builder' ),
					),
					'increase_padding_row_10' => array(
						'kbd'  => array( 'r', 'shift', array( 'left', 'right', 'up', 'down' ) ),
						'desc' => esc_html__( 'Increase Row Padding By 10px', 'et_builder' ),
					),
					'decrease_padding_row_10' => array(
						'kbd'  => array( 'r', 'alt', 'shift', array( 'left', 'right', 'up', 'down' ) ),
						'desc' => esc_html__( 'Decrease Row Padding By 10px', 'et_builder' ),
					),
					'increase_padding_section_10' => array(
						'kbd'  => array( 's', 'shift', array( 'left', 'right', 'up', 'down' ) ),
						'desc' => esc_html__( 'Increase Section Padding By 10px', 'et_builder' ),
					),
					'decrease_padding_section_10' => array(
						'kbd'  => array( 's', 'alt', 'shift', array( 'left', 'right', 'up', 'down' ) ),
						'desc' => esc_html__( 'Decrease Section Padding By 10px', 'et_builder' ),
					),
				),
				'modal_title' => esc_html__( 'Modal Shortcuts', 'et_builder' ),
				'modal' => array(
					'escape' => array(
						'kbd'  => array( 'esc' ),
						'desc' => esc_html__( 'Close Modal', 'et_builder' ),
					),
					'save_changes' => array(
						'kbd'  => array( 'enter' ),
						'desc' => esc_html__( 'Save Changes', 'et_builder' ),
					),
					'undo' => array(
						'kbd'  => array( 'super', 'z' ),
						'desc' => esc_html__( 'Undo', 'et_builder' ),
					),
					'redo' => array(
						'kbd'  => array( 'super', 'shift', 'z' ),
						'desc' => esc_html__( 'Redo', 'et_builder' ),
					),
					'switch_tabs' => array(
						'kbd'  => array( 'shift', 'tab' ),
						'desc' => esc_html__( 'Switch Tabs', 'et_builder' ),
					),
					'toggle_expand' => array(
						'kbd'  => array( 'super', 'enter' ),
						'desc' => esc_html__( 'Expand Modal Fullscreen', 'et_builder' ),
					),
					'toggle_snap' => array(
						'kbd'  => array( 'super', array( 'left', 'right' ) ),
						'desc' => esc_html__( 'Snap Modal Left / Right', 'et_builder' ),
					),
				),
			),
		),
		'sortable' => array(
			'has_no_ab_permission'                     => esc_html__( 'You do not have permission to edit the module, row or section in this split test.', 'et_builder' ),
			'cannot_move_goal_into_subject'            => esc_html__( 'A split testing goal cannot be moved inside of a split testing subject. To perform this action you must first end your split test.', 'et_builder' ),
			'cannot_move_subject_into_goal'            => esc_html__( 'A split testing subject cannot be moved inside of a split testing goal. To perform this action you must first end your split test.', 'et_builder' ),
			'cannot_move_row_goal_out_from_subject'    => esc_html__( 'Once set, a goal that has been placed inside a split testing subject cannot be moved outside the split testing subject. You can end your split test and start a new one if you would like to make this change.', 'et_builder' ),
			'section_only_row_dragged_away'            => esc_html__( 'The section should have at least one row.', 'et_builder' ),
			'global_module_alert'                      => esc_html__( 'You cannot add global modules into global sections or rows', 'et_builder' ),
			'cannot_move_module_goal_out_from_subject' => esc_html__( 'Once set, a goal that has been placed inside a split testing subject cannot be moved outside the split testing subject. You can end your split test and start a new one if you would like to make this change.', 'et_builder' ),
			'stop_dropping_3_col_row'                  => esc_html__( '3 column row can\'t be used in this column.', 'et_builder' ),
		),
		'tooltip' => array(
			'pageSettingsBar' => array(
				'responsive' => array(
					'zoom'    => esc_html__( 'Zoom Out', 'et_builder' ),
					'desktop' => esc_html__( 'Desktop View', 'et_builder' ),
					'tablet'  => esc_html__( 'Tablet View', 'et_builder' ),
					'phone'   => esc_html__( 'Phone View', 'et_builder' ),
				),
				'main' => array(
					'loadLibrary'       => esc_html__( 'Load From Library', 'et_builder' ),
					'saveToLibrary'     => esc_html__( 'Save To Library', 'et_builder' ),
					'clearLayout'       => esc_html__( 'Clear Layout', 'et_builder' ),
					'pageSettingsModal' => esc_html__( 'Page Settings', 'et_builder' ),
					'history'           => esc_html__( 'Editing History', 'et_builder' ),
					'portability'       => esc_html__( 'Portability', 'et_builder' ),
					'open'              => esc_html__( 'Expand Settings', 'et_builder' ),
					'close'             => esc_html__( 'Collapse Settings', 'et_builder' ),
				),
				'save' => array(
					'saveDraft' => esc_html__( 'Save as Draft', 'et_builder' ),
					'save'      => esc_html__( 'Save', 'et_builder' ),
					'publish'   => esc_html__( 'Publish', 'et_builder' ),
				)
			),
			'modal' => array(
				'expandModal'   => esc_html__( 'Expand Modal', 'et_builder' ),
				'contractModal' => esc_html__( 'Contract Modal', 'et_builder' ),
				'resize'        => esc_html__( 'Resize Modal', 'et_builder' ),
				'snapModal'     => esc_html__( 'Snap to Left', 'et_builder' ),
				'separateModal' => esc_html__( 'Separate Modal', 'et_builder' ),
				'redo'          => esc_html__( 'Redo', 'et_builder' ),
				'undo'          => esc_html__( 'Undo', 'et_builder' ),
				'cancel'        => esc_html__( 'Discard All Changes', 'et_builder' ),
				'save'          => esc_html__( 'Save Changes', 'et_builder' ),
			),
			'inlineEditor' => array(
				'back'             => esc_html__( 'Go Back', 'et_builder' ),
				'increaseFontSize' => esc_html__( 'Decrease Font Size', 'et_builder' ),
				'decreaseFontSize' => esc_html__( 'Increase Font Size', 'et_builder' ),
				'bold'             => esc_html__( 'Bold Text', 'et_builder' ),
				'italic'           => esc_html__( 'Italic Text', 'et_builder' ),
				'underline'        => esc_html__( 'Underline Text', 'et_builder' ),
				'link'             => esc_html__( 'Insert Link', 'et_builder' ),
				'quote'            => esc_html__( 'Insert Quote', 'et_builder' ),
				'alignment'        => esc_html__( 'Text Alignment', 'et_builder' ),
				'centerText'       => esc_html__( 'Center Text', 'et_builder' ),
				'rightText'        => esc_html__( 'Right Text', 'et_builder' ),
				'leftText'         => esc_html__( 'Left Text', 'et_builder' ),
				'justifyText'      => esc_html__( 'Justify Text', 'et_builder' ),
				'list'             => esc_html__( 'List Settings', 'et_builder' ),
				'indent'           => esc_html__( 'Indent List', 'et_builder' ),
				'undent'           => esc_html__( 'Undent List', 'et_builder' ),
				'orderedList'      => esc_html__( 'Insert Ordered List', 'et_builder' ),
				'unOrderedList'    => esc_html__( 'Insert Unordered List', 'et_builder' ),
				'text'             => esc_html__( 'Text Settings', 'et_builder' ),
				'textColor'        => esc_html__( 'Text Color', 'et_builder' ),
				'heading' => array(
					'one'   => esc_html__( 'Insert Heading One', 'et_builder' ),
					'two'   => esc_html__( 'Insert Heading Two', 'et_builder' ),
					'three' => esc_html__( 'Insert Heading Three', 'et_builder' ),
					'four'  => esc_html__( 'Insert Heading Four', 'et_builder' ),
				),
			),
			'section' => array(
				'tab' => array(
					'move'         => esc_html__( 'Move Section', 'et_builder' ),
					'settings'     => esc_html__( 'Section Settings', 'et_builder' ),
					'duplicate'    => esc_html__( 'Duplicate Section', 'et_builder' ),
					'addToLibrary' => esc_html__( 'Save Section To Library', 'et_builder' ),
					'delete'       => esc_html__( 'Delete Section', 'et_builder' ),
				),
				'addButton' => esc_html__( 'Add New Section', 'et_builder' ),
			),
			'row' => array(
				'tab' => array(
					'move'         => esc_html__( 'Move Row', 'et_builder' ),
					'settings'     => esc_html__( 'Row Settings', 'et_builder' ),
					'duplicate'    => esc_html__( 'Duplicate Row', 'et_builder' ),
					'addToLibrary' => esc_html__( 'Save Row To Library', 'et_builder' ),
					'delete'       => esc_html__( 'Delete Row', 'et_builder' ),
					'update'       => esc_html__( 'Change Column Structure', 'et_builder' ),
				),
				'addButton' => esc_html__( 'Add New Row', 'et_builder' ),
			),
			'module' => array(
				'tab' => array(
					'move'         => esc_html__( 'Move Module', 'et_builder' ),
					'settings'     => esc_html__( 'Module Settings', 'et_builder' ),
					'duplicate'    => esc_html__( 'Duplicate Module', 'et_builder' ),
					'addToLibrary' => esc_html__( 'Save Module To Library', 'et_builder' ),
					'delete'       => esc_html__( 'Delete Module', 'et_builder' ),
				),
				'addButton' => esc_html__( 'Add New Module', 'et_builder' ),
			),
		),
		'unsavedConfirmation' => esc_html__( 'Unsaved changes will be lost if you leave the Divi Builder at this time.', 'et_builder' ),
		'libraryLoadError'    => esc_html__( 'Error loading Library items from server. Please refresh the page and try again.', 'et_builder' ),
	);

	// Pass helpers via localization.
	wp_localize_script( 'et-frontend-builder', 'ETBuilderBackend', $helpers );
}

if ( ! function_exists( 'et_fb_fix_plugin_conflicts' ) ) :
function et_fb_fix_plugin_conflicts() {
	// Disable Autoptimize plugin
	remove_action( 'init', 'autoptimize_start_buffering', -1 );
	remove_action( 'template_redirect', 'autoptimize_start_buffering', 2 );
}
endif;

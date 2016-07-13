<?php
$this->text(	'main_top_padding',
				__('Page Content Top Padding', 'Avada'),
				__('In pixels ex: 20px. Leave empty for default value.', 'Avada')
			);

$this->text(	'main_bottom_padding',
				__('Page Content Bottom Padding', 'Avada'),
				__('In pixels ex: 20px. Leave empty for default value.', 'Avada')
			);

$this->text(	'hundredp_padding',
				__('100% Width Left/Right Padding', 'Avada'),
				__('This option controls the left/right padding for page content when using 100% site width or 100% width page template.  Enter value in px. ex: 20px.', 'Avada')
			);

$screen = get_current_screen();			

if ( $screen->post_type == 'page' ) {
	$this->select(	'show_first_featured_image',
					__('Disable First Featured Image', 'Avada'),
					array('no' => __('No', 'Avada'), 'yes' => __('Yes', 'Avada')),
					__('Disable the 1st featured image on page.', 'Avada')
				  );
}

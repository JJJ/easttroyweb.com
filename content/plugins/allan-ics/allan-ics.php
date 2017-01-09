<?php

/**
 * Plugin Name: Mods for Allan ICS
 * Author:      John James Jacoby
 * Author URI:  http://jjj.me/
 * License:     GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Version:     0.1.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Customize footer credits
 */
function allan_footer_creds_text() {
	ob_start();

	?>

	<div class="creds">
		<p>
			Copyright &copy; <?php echo date( 'Y' ); ?>
			Allan Integrated Control Systems. All rights reserved.
		</p>
	</div>

	<?php

	// Output footer
	echo ob_get_clean();
}
add_filter( 'genesis_footer_creds_text', 'allan_footer_creds_text' );
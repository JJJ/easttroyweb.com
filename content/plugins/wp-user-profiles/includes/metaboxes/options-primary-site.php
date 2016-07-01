<?php

/**
 * User Profile Primary Site
 *
 * @package Plugins/Users/Profiles/Metaboxes/Primary
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Render the primary-site metabox for user profile screen
 *
 * @since 0.1.0
 *
 * @param WP_User $user The WP_User object to be edited.
 */
function wp_user_profiles_primary_site_metabox( $user = null ) {

	// Get sites
	$sites   = (array) get_blogs_of_user( $user->ID );
	$primary = (int) get_user_meta( $user->ID, 'primary_blog', true );

	// If there is only 1 site, maybe do some clean-up
	if ( count( $sites ) === 1 ) {
		$site = reset( $sites );

		// Reset the primary site if it's out of sync
		if ( $primary !== $site->userblog_id ) {
			update_user_meta( $user->ID, 'primary_blog', $site->userblog_id );
		}
	} ?>

	<table class="form-table">
		<tr>
			<th scope="row">
				<label for="primary_blog">
					<?php _e( 'Primary Site', 'wp-user-profiles' ); ?>
				</label>
			</th>
			<td>
				<select name="primary_blog" id="primary_blog"><?php

					foreach ( $sites as $site ) :

						?><option value="<?php echo esc_attr( $site->userblog_id ); ?>" <?php selected( $primary, $site->userblog_id ); ?>><?php echo esc_url( get_home_url( $site->userblog_id ) ) ?></option><?php

					endforeach;

				?></select>
			</td>
		</tr>
	</table>

	<?php
}

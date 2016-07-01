<?php

/**
 * User Profile Session Metabox
 *
 * @package Plugins/Users/Profiles/Metaboxes/Sessions
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Render the password metabox for user profile screen
 *
 * @since 0.1.0
 *
 * @param WP_User $user The WP_User object to be edited.
 */
function wp_user_profiles_session_metabox( $user = null ) {

	// Get session
	$sessions = WP_Session_Tokens::get_instance( $user->ID ); ?>

	<table class="form-table">

		<?php if ( IS_PROFILE_PAGE && count( $sessions->get_all() ) === 1 ) : ?>

			<tr class="user-sessions-wrap hide-if-no-js">
				<th><?php esc_html_e( 'Sessions', 'wp-user-profiles' ); ?></th>
				<td aria-live="assertive">
					<div class="destroy-sessions"><button type="button" disabled class="button button-secondary"><?php esc_html_e( 'Log Out Everywhere Else', 'wp-user-profiles' ); ?></button></div>
					<p class="description">
						<?php esc_html_e( 'You are only logged in at this location.' ); ?>
					</p>
				</td>
			</tr>

		<?php elseif ( IS_PROFILE_PAGE && count( $sessions->get_all() ) > 1 ) : ?>

			<tr class="user-sessions-wrap hide-if-no-js">
				<th><?php esc_html_e( 'Sessions', 'wp-user-profiles' ); ?></th>
				<td aria-live="assertive">
					<div class="destroy-sessions"><button type="button" class="button button-secondary" id="destroy-sessions"><?php esc_html_e( 'Log Out Everywhere Else', 'wp-user-profiles' ); ?></button></div>
					<p class="description">
						<?php esc_html_e( 'Did you lose your phone or leave your account logged in at a public computer? You can log out everywhere else, and stay logged in here.', 'wp-user-profiles' ); ?>
					</p>
				</td>
			</tr>

		<?php elseif ( ! IS_PROFILE_PAGE && $sessions->get_all() ) : ?>

			<tr class="user-sessions-wrap hide-if-no-js">
				<th><?php esc_html_e( 'Sessions', 'wp-user-profiles' ); ?></th>
				<td>
					<p><button type="button" class="button button-secondary" id="destroy-sessions"><?php esc_html_e( 'Log Out Everywhere', 'wp-user-profiles' ); ?></button></p>
					<p class="description">
						<?php
						/* translators: 1: User's display name. */
						printf( esc_html__( 'Log %s out of all locations.', 'wp-user-profiles' ), $user->display_name );
						?>
					</p>
				</td>
			</tr>

		<?php elseif ( ! IS_PROFILE_PAGE && ! $sessions->get_all() ) : ?>

			<tr class="user-sessions-wrap hide-if-no-js">
				<th><?php esc_html_e( 'Inactive', 'wp-user-profiles' ); ?></th>
				<td>
					<p>
						<?php
						/* translators: 1: User's display name. */
						printf( esc_html__( '%s has not logged in yet.', 'wp-user-profiles' ), $user->display_name );
						?>
					</p>
				</td>
			</tr>

		<?php endif; ?>

	</table>

<?php
}

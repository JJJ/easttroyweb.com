<?php
/**
 * The template part for displaying a message that posts cannot be found.
 *
 * Learn more: http://codex.wordpress.org/Template_Hierarchy
 *
 * @package Camera
 */
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'post' ); ?>>
	<div class="container content-container">
		<header class="entry-header">
			<h1 class="entry-title"><?php _e( 'Nothing Found', 'camera' ); ?></h1>
			<div class="entry-subtitle">
				<?php if ( is_home() && current_user_can( 'publish_posts' ) ) : ?>

				<p><?php printf( __( 'Ready to publish your first post? <a href="%1$s">Get started here</a>.', 'camera' ), esc_url( admin_url( 'post-new.php' ) ) ); ?></p>

			<?php elseif ( is_search() ) : ?>

				<p><?php _e( 'Sorry, but nothing matched your search terms. Please try again with some different keywords.', 'camera' ); ?></p>
				<?php get_search_form(); ?>

			<?php else : ?>

				<p><?php _e( 'It seems we can&rsquo;t find what you&rsquo;re looking for. Perhaps searching can help.', 'camera' ); ?></p>
				<?php get_search_form(); ?>

			<?php endif; ?>
			</div>
		</header><!-- .entry-header -->

		<div class="entry-content">

		</div><!-- .entry-content -->
	</div>
</article><!-- #post-## -->

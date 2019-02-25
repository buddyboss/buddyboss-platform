<?php
/**
 * @package WordPress
 * @subpackage BuddyBoss Media
 *
 */

/*
 * The template file to display the content of 'all media page'.
 * Making changes to this file is not advised.
 * To override this template file:
 *  - create a folder 'buddyboss-media' inside your active theme (or child theme)
 *  - copy this file and place in the folder mentioned above
 *  - and make changes to the new file (the one you just copied into your theme).
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;
?>

<?php bp_nouveau_before_activity_directory_content(); ?>

<div id="buddypress" class="buddypress-wrap bp-dir-hori-nav">

	<?php if ( is_user_logged_in() ) : ?>

		<?php bp_get_template_part( 'activity/post-form' ); ?>

	<?php endif; ?>

	<?php bp_nouveau_template_notices(); ?>

		<nav class="iactivity-type-navs main-navs bp-navs dir-navs" role="navigation" aria-label="<?php esc_attr_e( 'Directory menu', 'buddypress' ); ?>">
			<ul class="component-navigation activity-nav">
				<?php $global_media_permalink = trailingslashit( _get_page_link( buddyboss_media()->option('all-media-page') ) ); ?>
				<li class="dynamic selected" id="photos-all">
					<a href="<?php echo esc_url( $global_media_permalink );?>"><?php _e( 'All Photos', 'buddyboss-media' );?></a>
				</li>
				<li class="dynamic" id="albums-personal">
					<a href="<?php echo esc_url( $global_media_permalink );?>albums/"><?php _e( 'All Albums', 'buddyboss-media' );?></a>
				</li>
			</ul>
		</nav>

		<?php if ( is_user_logged_in() ) : ?>
			<?php bp_get_template_part( 'activity/post-form' ) ?>
		<?php endif; ?>

	<div class="screen-content">

		<?php bp_nouveau_activity_hook( 'before_directory', 'list' ); ?>

		<div id="activity-stream" class="activity" data-bp-list="activity">

			<div id="bp-ajax-loader"><?php bp_nouveau_user_feedback( 'directory-activity-loading' ); ?></div>

		</div><!-- .activity -->

		<?php bp_nouveau_after_activity_directory_content(); ?>

	</div><!-- // .screen-content -->
</div>
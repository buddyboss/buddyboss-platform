<?php
/**
 * The template for BuddyBoss Activity templates
 *
 * This template can be overridden by copying it to yourtheme/buddypress/activity/index.php.
 *
 * @since   BuddyPress 2.3.0
 * @version 1.0.0
 */

$is_ajax_request = bb_get_ajax_request_page_load();
?>

	<?php bp_nouveau_before_activity_directory_content(); ?>

	<?php if ( is_user_logged_in() ) : ?>

		<?php bp_get_template_part( 'activity/post-form' ); ?>

	<?php endif; ?>

	<?php bp_nouveau_template_notices(); ?>

	<?php if ( ! bp_nouveau_is_object_nav_in_sidebar() ) : ?>

		<?php bp_get_template_part( 'common/nav/directory-nav' ); ?>

	<?php endif; ?>

	<div class="screen-content">

		<?php bp_get_template_part( 'common/search-and-filters-bar' ); ?>

		<?php bp_nouveau_activity_hook( 'before_directory', 'list' ); ?>

		<div id="activity-stream" class="activity" data-bp-list="activity" data-ajax="<?php echo ( 1 === $is_ajax_request ) ? 'false' : 'true'; ?>">
			<?php
			if ( 1 === $is_ajax_request ) {
				bp_get_template_part( 'activity/activity-loop' );
			} else {
				echo '<div id="bp-ajax-loader">';
				bp_nouveau_user_feedback( 'directory-activity-loading' );
				echo '</div>';
			}
			?>
		</div><!-- .activity -->

		<?php bp_nouveau_after_activity_directory_content(); ?>

	</div><!-- // .screen-content -->


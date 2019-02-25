<?php
/**
 * BuddyPress Media - group album photos page template
 *
 * @package WordPress
 * @subpackage BuddyBoss Media
 */
?>
<?php
/**
* Fires before the display of the group activity post form.
*
* @since 1.2.0
*/
do_action( 'bp_before_group_activity_post_form' ); ?>

<?php if ( is_user_logged_in() && bp_group_is_member() ) : ?>

	<?php bp_get_template_part( 'activity/post-form' ); ?>

	<input type="hidden" id="whats-new-post-object" name="whats-new-post-object" value="groups" />
	<input type="hidden" id="whats-new-post-in" name="whats-new-post-in" value="<?php bp_group_id(); ?>" />
<?php endif; ?>

<?php

/**
 * Fires after the display of the group activity post form.
 *
 * @since 1.2.0
 */
do_action( 'bp_after_group_activity_post_form' ); ?>
<?php

/**
 * Fires before the display of the group activities list.
 *
 * @since 1.2.0
 */
do_action( 'bp_before_group_activity_content' ); ?>

<div class="activity single-group">
	<?php if( buddyboss_media_check_custom_activity_template_load() ):?>
		<?php bp_get_template_part( 'activity/buddyboss-media-activity-loop' ) ?>
	<?php else : ?>
		<?php bp_get_template_part( 'activity/activity-loop' ) ?>
	<?php endif; ?>
</div><!-- .activity -->
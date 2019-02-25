<?php
/**
 * BuddyPress Media - Users Photos
 *
 * @package WordPress
 * @subpackage BuddyBoss Media
 */
?>
<?php if ( bp_is_my_profile() ) : ?>
	<?php bp_get_template_part( 'activity/post-form' ) ?>
<?php endif; ?>

<div class="activity">
	<?php if( buddyboss_media_check_custom_activity_template_load() ):?>
		<?php bp_get_template_part( 'activity/buddyboss-media-activity-loop' ) ?>
	<?php else : ?>
		<?php bp_get_template_part( 'activity/activity-loop' ) ?>
	<?php endif; ?>
</div><!-- .activity -->
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
<div id="buddypress">
	<div class="item-list-tabs no-ajax" id="subnav" role="navigation">
		<ul>
			<?php $global_media_permalink = trailingslashit( _get_page_link( buddyboss_media()->option('all-media-page') ) ); ?>
			<li class="selected" id="photos-all"><a href="<?php echo esc_url( $global_media_permalink );?>"><?php _e( 'All Photos', 'buddyboss-media' );?></a></li>
			<li id="albums-personal"><a href="<?php echo esc_url( $global_media_permalink );?>albums/"><?php _e( 'All Albums', 'buddyboss-media' );?></a></li>
		</ul>
	</div>
	
	<?php if ( is_user_logged_in() ) : ?>
		<?php bp_get_template_part( 'activity/post-form' ) ?>
	<?php endif; ?>
	
	<div class="activity">
		<?php if( buddyboss_media_check_custom_activity_template_load() ):?>
			<?php bp_get_template_part( 'activity/buddyboss-media-activity-loop' ) ?>
		<?php else : ?>
			<?php bp_get_template_part( 'activity/activity-loop' ) ?>
		<?php endif; ?>
	</div><!-- .activity -->
</div>
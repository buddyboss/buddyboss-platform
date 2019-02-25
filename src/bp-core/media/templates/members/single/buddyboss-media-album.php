<?php
/**
 * BuddyPress Media - Users Photos
 *
 * @package WordPress
 * @subpackage BuddyBoss Media
 */
?>

<?php if ( buddyboss_media_has_albums( ) ) : ?>

	<?php while ( buddyboss_media_albums() ) : buddyboss_media_the_album(); ?>
		<div id="buddypress">

			<h2 class="entry-title"><?php buddyboss_media_album_title();?>
				<?php
				if ( bp_is_my_profile() || current_user_can( 'manage_options' ) ) {
					global $bp;
					$edit_album_url = $bp->displayed_user->domain . buddyboss_media_component_slug() . '/albums/';
					$edit_album_url = esc_url(add_query_arg( 'album', buddyboss_media_album_get_id(), $edit_album_url ));
					?>
					<a href="<?php echo esc_url( $edit_album_url );?>" class="button album-edit bp-title-button">
						<?php _e( 'Edit Album', 'buddyboss-media' );?>
					</a>
					<?php
				}
				?>
			</h2>

			<p class="album-description"><?php buddyboss_media_album_description(); ?></p>

			<?php if ( bp_is_my_profile() ) : ?>
				<?php bp_get_template_part( 'activity/post-form' ) ?>
			<?php endif; ?>

			<div class="activity">

				<?php if( buddyboss_media_check_custom_activity_template_load() ):?>
					<?php bp_get_template_part( 'activity/buddyboss-media-activity-loop' ) ?>
				<?php else: ?>
					<?php bp_get_template_part( 'activity/activity-loop' ) ?>
				<?php endif; ?>

			</div><!-- .activity -->

		</div>
	<?php endwhile; ?>

<?php else: ?>
	<div id="message" class="info">
		<p><?php _e( 'There were no albums found.', 'buddyboss-media' ); ?></p>
	</div>
<?php endif; ?>
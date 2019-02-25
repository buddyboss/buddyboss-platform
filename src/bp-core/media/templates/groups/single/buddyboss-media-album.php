<?php
/**
 * BuddyPress Media - group single album template
 *
 * @package WordPress
 * @subpackage BuddyBoss Media
 */
?>

<?php if ( buddyboss_media_has_albums() ): ?>

	<?php  while ( buddyboss_media_albums() ) : buddyboss_media_the_album(); ?>
		<div id="buddypress">
				
			<h2 class="entry-title"><?php buddyboss_media_album_title();?> 
				<?php 
                //user with delete access to this album can edit it too
                if( bbm_groups_user_can_delete_albums( buddyboss_media_album_get_id() ) ):
					global $bp;
                    
                    $edit_album_url = buddyboss_media_album_get_permalink();
					$edit_album_url = esc_url(add_query_arg( 'album', buddyboss_media_album_get_id(), $edit_album_url ));
					?>
					<a href="<?php echo esc_url( $edit_album_url );?>" class="button album-edit bp-title-button">
						<?php _e( 'Edit Album', 'buddyboss-media' );?>
					</a>
                <?php endif; ?>
			</h2>
			
			<p class="album-description"><?php buddyboss_media_album_description(); ?></p>

			<?php if( bbm_groups_user_can_delete_albums( buddyboss_media_album_get_id() ) ): ?>
				<input type="hidden" id="whats-new-post-object" name="whats-new-post-object" value="groups" />
				<input type="hidden" id="whats-new-post-in" name="whats-new-post-in" value="<?php bp_group_id(); ?>" />
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
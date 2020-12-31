<?php
/**
 * BuddyBoss - Video Entry
 *
 * @package BuddyBoss\Core
 *
 * @since BuddyBoss 1.0.0
 */

global $video_template;

$attachment_id = bp_get_video_attachment_id();
$download_url  = bp_video_download_link( $attachment_id, bp_get_video_id() );
$group_id      = bp_get_video_group_id();
$move_id       = '';
$move_type     = '';
$video_privacy = bp_video_user_can_manage_video( bp_get_video_id(), bp_loggedin_user_id() );
$can_manage    = true === (bool) $video_privacy['can_manage'];

if ( $group_id > 0 ) {
	$move_id   = $group_id;
	$move_type = 'group';
} else {
	$move_id   = bp_get_video_user_id();
	$move_type = 'profile';
}


?>
<li class="lg-grid-1-5 md-grid-1-3 sm-grid-1-3" data-id="<?php bp_video_id(); ?>" data-date-created="<?php bp_video_date_created(); ?>">

	<div class="bb-video-thumb bb-item-thumb">
		<div class="video-action-wrap item-action-wrap">
			<a href="#" class="video-action_more item-action_more" data-balloon-pos="up" data-balloon="More actions">
				<i class="bb-icon-menu-dots-v"></i>
			</a>
			<div class="video-action_list item-action_list">
				<ul>
					<li class="edit_thumbnail_video">
						<a href="#" data-action="video" data-video-attachment-id="<?php bp_video_attachment_id(); ?>" data-video-id="<?php bp_video_id(); ?>" class="ac-video-thumbnail-edit"><?php esc_html_e( 'Add Thumbnail', 'buddyboss' ); ?></a>
					</li>
					<li class="move_video">
						<a href="#" data-action="video" data-video-id="<?php bp_video_id(); ?>" data-parent-activity-id="<?php bp_video_parent_activity_id(); ?>" data-item-activity-id="<?php bp_video_activity_id(); ?>" data-type="<?php echo esc_attr( $move_type ); ?>" id="<?php echo esc_attr( $move_id ); ?>" class="ac-video-move"><?php esc_html_e( 'Move', 'buddyboss' ); ?></a>
					</li>
					<li class="delete_file">
						<a class="video-file-delete" data-video-id="<?php bp_video_id(); ?>" data-parent-activity-id="<?php bp_video_parent_activity_id(); ?>" data-item-activity-id="<?php bp_video_activity_id(); ?>" data-item-from="video" data-item-id="<?php bp_video_id(); ?>" data-type="video" href="#"><?php esc_html_e( 'Delete', 'buddyboss' ); ?></a>
					</li>

				</ul>
			</div>
		</div>
		<p class="bb-video-duration"><?php bp_video_length(); ?></p>
		<a class="bb-open-video-theatre bb-video-cover-wrap bb-item-cover-wrap" data-id="<?php bp_video_id(); ?>" data-attachment-full="<?php bp_video_attachment_image(); ?>" data-activity-id="<?php bp_video_activity_id(); ?>" data-privacy="<?php bp_video_privacy(); ?>" data-parent-activity-id="<?php bp_video_parent_activity_id(); ?>" data-album-id="<?php bp_video_album_id(); ?>" data-group-id="<?php bp_video_group_id(); ?>" data-attachment-id="<?php bp_video_attachment_id(); ?>" href="#">
			<img src="<?php echo esc_url( buddypress()->plugin_url ); ?>bp-templates/bp-nouveau/images/placeholder.png" data-src="<?php bp_video_attachment_image_thumbnail(); ?>" alt="<?php bp_video_title(); ?>" class="lazy"/>
		</a>
		<?php
		$video_privacy = bp_video_user_can_manage_video( bp_get_video_id(), bp_loggedin_user_id() );
		$can_manage    = true === (bool) $video_privacy['can_manage'];
		if ( ( ( bp_is_my_profile() || bp_current_user_can( 'bp_moderate' ) ) || ( bp_is_group() && ( ( bp_is_group_video() && $can_manage ) || ( bp_is_group_albums() && $can_manage ) ) ) ) && ! bp_is_video_directory() ) :
			?>
			<div class="bb-video-check-wrap bb-action-check-wrap">
				<input id="bb-video-<?php bp_video_id(); ?>" class="bb-custom-check" type="checkbox" value="<?php bp_video_id(); ?>" name="bb-video-select" />
				<label class="bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_html_e( 'Select', 'buddyboss' ); ?>" for="bb-video-<?php bp_video_id(); ?>"><span class="bb-icon bb-icon-check"></span></label>
			</div>
		<?php endif; ?>
	</div>

</li>

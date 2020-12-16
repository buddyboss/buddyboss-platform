<?php
/**
 * BuddyBoss - Activity Video
 *
 * @since BuddyBoss 1.0.0
 */
?>

<?php
global $video_template;

$width  =  isset( $video_template->video->attachment_data->meta['width'] ) ? $video_template->video->attachment_data->meta['width'] : 0;
$height =  isset( $video_template->video->attachment_data->meta['height'] ) ? $video_template->video->attachment_data->meta['height'] : 0;

?>

<div class="bb-activity-video-elem <?php echo $video_template->current_video > 4 ? 'hide' : ''; echo $video_template->video_count == 1 || $video_template->video_count > 1 && $video_template->current_video == 0 ? 'act-grid-1-1 ' : ''; echo $video_template->video_count > 1 && $video_template->current_video > 0 ? 'act-grid-1-2 ' : ''; echo $width > $height ? 'bb-horizontal-layout' : ''; echo $height > $width || $width == $height ? 'bb-vertical-layout' : ''; ?>">
	<div class="video-action-wrap item-action-wrap">
		<a href="#" class="video-action_more item-action_more" data-balloon-pos="up" data-balloon="More actions">
			<i class="bb-icon-menu-dots-v"></i>
		</a>
		<div class="video-action_list item-action_list">
			<ul>
				<li class="edit_video">
					<a href="#" data-action="video" data-media-id="567" data-parent-activity-id="" data-item-activity-id="194811" data-type="profile" id="2" class="ac-video-edit">Edit</a>
				</li>    
				<li class="move_video">
					<a href="#" data-action="video" data-media-id="567" data-parent-activity-id="" data-item-activity-id="194811" data-type="profile" id="2" class="ac-video-move">Move</a>
				</li>
				<li class="delete_file">
					<a class="video-file-delete" data-media-id="567" data-parent-activity-id="" data-item-activity-id="194811" data-item-from="media" data-item-id="567" data-type="video" href="#">Delete</a>
				</li>
			</ul>
		</div>
	</div>
	<a href="#"
	   class="bb-open-video-theatre entry-video"
	   data-id="<?php bp_video_id(); ?>"
	   data-attachment-id="<?php bp_video_attachment_id(); ?>"
	   data-attachment-full="<?php bp_video_attachment_image(); ?>"
	   data-activity-id="<?php bp_video_activity_id(); ?>"
	   data-privacy="<?php bp_video_privacy(); ?>"
	   data-parent-activity-id="<?php bp_video_parent_activity_id(); ?>"
	   data-album-id="<?php bp_video_album_id(); ?>"
	   data-group-id="<?php bp_video_group_id(); ?>"
	>
		<img src="<?php echo buddypress()->plugin_url; ?>bp-templates/bp-nouveau/images/placeholder.png" data-src="<?php bp_video_attachment_image_activity_thumbnail(); ?>" class="no-round video lazy" alt="<?php bp_video_title(); ?>" />

		<?php if ( $video_template->video_count > 5 && $video_template->current_video == 4 ) {
			?>
			<span class="bb-videos-length"><span><strong>+<?php echo $video_template->video_count - 5; ?></strong> <span><?php _e( 'More Videos', 'buddyboss' ); ?></span></span></span>
			<?php
		} ?>
	</a>
</div>

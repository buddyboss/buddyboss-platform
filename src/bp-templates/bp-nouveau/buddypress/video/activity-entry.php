<?php
/**
 * BuddyBoss - Activity Video
 *
 * @package BuddyBoss\Core
 *
 * @since BuddyBoss 1.0.0
 */

global $video_template;

$width  = isset( $video_template->video->attachment_data->thumb_meta['width'] ) ? $video_template->video->attachment_data->thumb_meta['width'] : 0;
$height = isset( $video_template->video->attachment_data->thumb_meta['height'] ) ? $video_template->video->attachment_data->thumb_meta['height'] : 0;

?>

<div class="bb-activity-video-elem
<?php
echo $video_template->current_video > 4 ? esc_attr( 'hide' ) : '';
echo 1 === $video_template->video_count || $video_template->video_count > 1 && 0 === $video_template->current_video ? esc_attr( 'act-grid-1-1 ' ) : '';
echo $video_template->video_count > 1 && $video_template->current_video > 0 ? esc_attr( 'act-grid-1-2 ' ) : '';
echo $width > $height ? esc_attr( 'bb-horizontal-layout' ) : '';
echo $height > $width || $width === $height ? esc_attr( 'bb-vertical-layout' ) : '';
?>
">
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
				<li class="delete_video">
					<a class="video-file-delete" data-media-id="567" data-parent-activity-id="" data-item-activity-id="194811" data-item-from="media" data-item-id="567" data-type="video" href="#">Delete</a>
				</li>
			</ul>
		</div>
	</div>
	<video id="video-<?php bp_video_id(); ?>" class="video-js" controls poster="<?php bp_video_attachment_image(); ?>" data-setup='{"fluid": true,"playbackRates": [0.5, 1, 1.5, 2] }'>
		<source src="<?php bp_video_link(); ?>" type="<?php bp_video_type(); ?>"></source>
	</video>
</div>

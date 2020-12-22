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
echo esc_attr( bp_video_id() ) . ' ';
echo $video_template->current_video > 2 ? esc_attr( 'hide ' ) : '';
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
	<?php if ($video_template->video_count == 1) { ?>
		<video id="video-<?php bp_video_id(); ?>" class="video-js" controls poster="<?php bp_video_attachment_image(); ?>" data-setup='{"fluid": true,"playbackRates": [0.5, 1, 1.5, 2] }'>
			<source src="<?php bp_video_link(); ?>" type="<?php bp_video_type(); ?>"></source>
		</video>
		<p class="bb-video-duration">1:45</p>
		<span class="bb-video-play">
			<svg width="52px" height="52px" viewBox="0 0 52 52" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><g id="online-communities-video-" transform="translate(-807.000000, -1039.000000)"><g id="Group-63-Copy-3" transform="translate(544.000000, 802.000000)"><g id="Icon-Play-52" transform="translate(263.000000, 237.000000)"><path d="M0,26 C0,11.6409091 11.6409091,0 26,0 C40.3590909,0 52,11.6409091 52,26 C52,40.3590909 40.3590909,52 26,52 C11.6409091,52 0,40.3590909 0,26 L0,26 Z" id="Combined-Shape" fill="#FFFFFF" opacity="1"></path><path d="M21.3852814,18.5511567 C21.3852814,17.443438 22.12779,17.0404603 23.0538192,17.6578131 L33.8985618,24.8876415 C34.8200698,25.5019801 34.8245909,26.4950057 33.8985618,27.1123585 L23.0538192,34.3421869 C22.1323112,34.9565256 21.3852814,34.5678829 21.3852814,33.4488433 L21.3852814,18.5511567 Z" id="Stroke-3728" fill="#393E41"></path></g></g></g></g></svg>
		</span>
	<?php } else { ?>
		<a class="bb-open-video-theatre bb-video-cover-wrap bb-item-cover-wrap" data-id="<?php bp_video_id(); ?>" data-attachment-full="<?php bp_video_attachment_image(); ?>" data-activity-id="<?php bp_video_activity_id(); ?>" data-privacy="<?php bp_video_privacy(); ?>" data-parent-activity-id="<?php bp_video_parent_activity_id(); ?>" data-album-id="<?php bp_video_album_id(); ?>" data-group-id="<?php bp_video_group_id(); ?>" data-attachment-id="<?php bp_video_attachment_id(); ?>" href="#">
			<img src="<?php echo esc_url(buddypress()->plugin_url); ?>bp-templates/bp-nouveau/images/placeholder.png" data-src="<?php bp_video_attachment_image_thumbnail(); ?>" alt="<?php bp_video_title(); ?>" class="lazy" />
			<?php
			if ($video_template->video_count > 2 && 2 === $video_template->current_video) {
				$count = $video_template->video_count - 3;
			?>
				<span class="bb-videos-length"><span><strong>+<?php echo esc_html($count); ?></strong> <span><?php esc_html_e('More Videos', 'buddyboss'); ?></span></span></span>
			<?php
			}
			?>
		</a>
	<?php } ?>
</div>

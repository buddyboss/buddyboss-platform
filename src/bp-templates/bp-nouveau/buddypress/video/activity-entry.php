<?php
/**
 * BuddyBoss - Activity Video
 *
 * This template is used to render activity video.
 *
 * This template can be overridden by copying it to yourtheme/buddypress/activity/video/activity-entry.php.
 *
 * @package BuddyBoss\Core
 *
 * @since   BuddyBoss 1.7.0
 * @version 1.7.0
 */

global $video_template;

$width  = isset( $video_template->video->attachment_data->thumb_meta['width'] ) ? $video_template->video->attachment_data->thumb_meta['width'] : 0;
$height = isset( $video_template->video->attachment_data->thumb_meta['height'] ) ? $video_template->video->attachment_data->thumb_meta['height'] : 0;

$group_id = bp_get_video_group_id();
if ( $group_id > 0 ) {
	$move_id   = $group_id;
	$move_type = 'group';
} else {
	$move_id   = bp_get_video_user_id();
	$move_type = 'profile';
}

$more_video              = $video_template->video_count > 3 ? true : false;
$media_privacy           = bb_media_user_can_access( bp_get_video_id(), 'video' );
$can_edit                = true === (bool) $media_privacy['can_edit'];
$can_move                = true === (bool) $media_privacy['can_move'];
$db_privacy              = bp_get_video_privacy();
$is_comment_vid          = bp_video_is_activity_comment_video( bp_get_video_id() );
$attachment_urls         = bb_video_get_attachments_symlinks( bp_get_video_attachment_id(), bp_get_video_id() );
$parent_root_activity_id = 0;

if ( $is_comment_vid ) {
	$hierarchy = bb_get_activity_hierarchy( bp_get_activity_id() );
	if ( ! empty( $hierarchy ) ) {
		$main_parent_id = end( $hierarchy );
		if ( ! empty( $main_parent_id ) ) {
			$parent_activity = new BP_Activity_Activity( $main_parent_id['id'] );
			if ( ! empty( $parent_activity->id ) && ! empty( $parent_activity->privacy ) ) {
				$parent_root_activity_id = $parent_activity->id;
			}
		}
	}
}

$has_no_thumbnail = '';
$attachment_full  = bp_get_video_popup_thumb();
$poster_full      = bp_get_video_activity_thumb();

if ( false !== strpos( $attachment_full, 'video-placeholder.jpg' ) || false !== strpos( $poster_full, 'video-placeholder.jpg' ) ) {
	$has_no_thumbnail = ' has-no-thumbnail';
}
?>

<div class="bb-activity-video-elem
<?php
echo esc_attr( bp_get_video_id() ) . ' ';
echo $video_template->current_video > 2 ? esc_attr( 'hide ' ) : '';
echo 1 === $video_template->video_count || $video_template->video_count > 1 && 0 === $video_template->current_video ? esc_attr( 'act-grid-1-1 ' ) : '';
echo $video_template->video_count > 1 && $video_template->current_video > 0 ? esc_attr( 'act-grid-1-2 ' ) : '';
echo $width > $height ? esc_attr( 'bb-horizontal-layout' ) : '';
echo $height > $width || $width === $height ? esc_attr( 'bb-vertical-layout' ) : '';
echo ( $more_video && 2 === $video_template->current_video ) ? esc_attr( ' no_more_option ' ) : '';
echo esc_attr( $has_no_thumbnail );
?>
" data-id="<?php echo esc_attr( bp_get_video_id() ); ?>">
	<div class="video-action-wrap item-action-wrap">

		<?php if ( $can_edit ) { ?>
			<?php
			$item_id = 0;
			if ( bp_loggedin_user_id() === bp_get_video_user_id() || bp_current_user_can( 'bp_moderate' ) || $can_edit ) {
				?>
				<a href="#" class="video-action_more item-action_more" data-balloon-pos="up" data-balloon="<?php esc_html_e( 'More actions', 'buddyboss' ); ?>">
					<i class="bb-icon-rl bb-icon-ellipsis-v"></i>
				</a>
				<div class="video-action_list item-action_list">
					<ul>
						<?php if ( ! in_array( $db_privacy, array( 'forums', 'comment', 'message' ), true ) ) { ?>
						<li class="edit_thumbnail_video video-action-class">
							<a href="#" data-action="video" data-video-attachments="<?php echo esc_html( wp_json_encode( $attachment_urls ) ); ?>" data-video-attachment-id="<?php bp_video_attachment_id(); ?>" data-video-id="<?php bp_video_id(); ?>" class="ac-video-thumbnail-edit"><?php esc_html_e( 'Change Thumbnail', 'buddyboss' ); ?></a>
						</li>
						<?php } ?>
						<?php
						if ( ! in_array( $db_privacy, array( 'forums', 'message' ), true ) ) {
							if ( $can_move ) {
								if ( $is_comment_vid ) {
									?>
									<li class="move_video move-disabled video-action-class" data-balloon-pos="down" data-balloon="<?php esc_html_e( 'Video inherits activity privacy in comment. You are not allowed to move.', 'buddyboss' ); ?>">
										<a href="#"><?php esc_html_e( 'Move', 'buddyboss' ); ?></a>
									</li>
									<?php
								} else {
									?>
									<li class="move_video video-action-class">
										<a href="#" data-action="video" data-video-id="<?php bp_video_id(); ?>" data-parent-activity-id="<?php bp_video_parent_activity_id(); ?>" data-item-activity-id="<?php bp_video_activity_id(); ?>" data-type="<?php echo esc_attr( $move_type ); ?>" id="<?php echo esc_attr( $move_id ); ?>" class="ac-video-move"><?php esc_html_e( 'Move', 'buddyboss' ); ?></a>
									</li>
									<?php
								}
							}
						}
						?>
						<li class="delete_file video-action-class">
							<a class="video-file-delete" data-video-id="<?php bp_video_id(); ?>" data-root-parent-activity-id="<?php echo esc_attr( $parent_root_activity_id ); ?>" data-parent-activity-id="<?php bp_video_parent_activity_id(); ?>" data-item-activity-id="<?php bp_video_activity_id(); ?>" data-item-from="activity" data-item-id="<?php bp_video_id(); ?>" data-type="video" href="#"><?php esc_html_e( 'Delete', 'buddyboss' ); ?></a>
						</li>
					</ul>
				</div>

			<?php } ?>
		<?php } ?>

	</div>

	<?php if ( 1 === $video_template->video_count ) { ?>
		<video playsinline id="video-<?php bp_video_id(); ?>" class="video-js single-activity-video" data-id="<?php bp_video_id(); ?>" data-attachment-full="<?php echo esc_url( $attachment_full ); ?>" data-activity-id="<?php bp_video_activity_id(); ?>" data-privacy="<?php bp_video_privacy(); ?>" data-parent-activity-id="<?php bp_video_parent_activity_id(); ?>" data-album-id="<?php bp_video_album_id(); ?>" data-group-id="<?php bp_video_group_id(); ?>" data-attachment-id="<?php bp_video_attachment_id(); ?>" controls poster="<?php echo esc_url( $poster_full ); ?>" data-setup='{"aspectRatio": "16:9", "fluid": true,"playbackRates": [0.5, 1, 1.5, 2], "fullscreenToggle" : false }'>
			<source src="<?php bp_video_link(); ?>" type="<?php bp_video_type(); ?>"></source>
		</video>
		<p class="bb-video-loader"></p>
		<?php if ( ! empty( bp_get_video_length() ) ) { ?>
		<p class="bb-video-duration"><?php bp_video_length(); ?></p>
		<?php } ?>
		<a class="bb-open-video-theatre bb-video-cover-wrap bb-item-cover-wrap hide" data-id="<?php bp_video_id(); ?>" data-attachment-full="<?php echo esc_url( $attachment_full ); ?>" data-activity-id="<?php bp_video_activity_id(); ?>" data-privacy="<?php bp_video_privacy(); ?>" data-parent-activity-id="<?php bp_video_parent_activity_id(); ?>" data-album-id="<?php bp_video_album_id(); ?>" data-group-id="<?php bp_video_group_id(); ?>" data-attachment-id="<?php bp_video_attachment_id(); ?>" href="#">
			<img src="<?php echo esc_url( buddypress()->plugin_url ); ?>bp-templates/bp-nouveau/images/video-placeholder.jpg" data-src="<?php echo esc_url( $poster_full ); ?>" alt="<?php bp_video_title(); ?>" class="lazy" />
			<?php
			if ( $video_template->video_count > 3 && 2 === $video_template->current_video ) {
				$count = $video_template->video_count - 3;
				?>
				<span class="bb-videos-length"><span><strong>+<?php echo esc_html( $count ); ?></strong> <span><?php esc_html_e( 'More Video', 'buddyboss' ); ?></span></span></span>
				<?php
			}
			?>
			<?php if ( ! empty( bp_get_video_length() ) ) { ?>
			<p class="bb-video-duration"><?php bp_video_length(); ?></p>
			<?php } ?>
		</a>
	<?php } else { ?>
		<a class="bb-open-video-theatre bb-video-cover-wrap bb-item-cover-wrap" data-id="<?php bp_video_id(); ?>" data-attachment-full="<?php echo esc_url( $attachment_full ); ?>" data-activity-id="<?php bp_video_activity_id(); ?>" data-privacy="<?php bp_video_privacy(); ?>" data-parent-activity-id="<?php bp_video_parent_activity_id(); ?>" data-album-id="<?php bp_video_album_id(); ?>" data-group-id="<?php bp_video_group_id(); ?>" data-attachment-id="<?php bp_video_attachment_id(); ?>" href="#">
			<img src="<?php echo esc_url( buddypress()->plugin_url ); ?>bp-templates/bp-nouveau/images/video-placeholder.jpg" data-src="<?php echo esc_url( $poster_full ); ?>" alt="<?php bp_video_title(); ?>" class="lazy" />
			<?php
			if ( $video_template->video_count > 3 && 2 === $video_template->current_video ) {
				$count = $video_template->video_count - 3;
				if ( 1 === $count ) {
					?>
                    <span class="bb-videos-length"><span><strong>+<?php echo esc_html( $count ); ?></strong> <span><?php esc_html_e( 'More Video', 'buddyboss' ); ?></span></span></span>
					<?php
				} else {
					?>
                    <span class="bb-videos-length"><span><strong>+<?php echo esc_html( $count ); ?></strong> <span><?php esc_html_e( 'More Videos', 'buddyboss' ); ?></span></span></span>
					<?php
				}
			}
			?>
			<?php if ( ! empty( bp_get_video_length() ) ) { ?>
			<p class="bb-video-duration"><?php bp_video_length(); ?></p>
			<?php } ?>
		</a>
	<?php } ?>
</div>

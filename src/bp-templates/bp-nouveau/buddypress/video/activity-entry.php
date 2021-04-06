<?php
/**
 * BuddyBoss - Activity Video
 *
 * @package BuddyBoss\Core
 *
 * @since BuddyBoss 1.5.7
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
$more_video = $video_template->video_count > 3 ? true : false;

$media_privacy   = bp_video_user_can_manage_video( bp_get_video_id(), bp_loggedin_user_id() );
$can_manage      = true === (bool) $media_privacy['can_manage'];
$can_move        = true === (bool) $media_privacy['can_move'];
$db_privacy      = bp_get_video_privacy();
$is_comment_vid  = bp_video_is_activity_comment_video( bp_get_video_id() );
$attachment_urls = bp_video_get_attachments( bp_get_video_attachment_id() ); 
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
?>
" data-id="<?php echo esc_attr( bp_get_video_id() ); ?>">
	<div class="video-action-wrap item-action-wrap">

		<?php if ( $can_manage ) { ?>
			<?php
			$item_id = 0;
			if ( bp_loggedin_user_id() === bp_get_video_user_id() || bp_current_user_can( 'bp_moderate' ) ) {
			?>
				<a href="#" class="video-action_more item-action_more" data-balloon-pos="up" data-balloon="<?php esc_html_e( 'More actions', 'buddyboss' ); ?>">
					<i class="bb-icon-menu-dots-v"></i>
				</a>
				<div class="video-action_list item-action_list">
					<ul>
						<li class="edit_thumbnail_video video-action-class">
							<a href="#" data-action="video" data-video-attachments="<?php echo esc_html(json_encode( $attachment_urls )); ?>" data-video-attachment-id="<?php bp_video_attachment_id(); ?>" data-video-id="<?php bp_video_id(); ?>" class="ac-video-thumbnail-edit"><?php esc_html_e( 'Add Thumbnail', 'buddyboss' ); ?></a>
						</li>
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
							<a class="video-file-delete" data-video-id="<?php bp_video_id(); ?>" data-parent-activity-id="<?php bp_video_parent_activity_id(); ?>" data-item-activity-id="<?php bp_video_activity_id(); ?>" data-item-from="activity" data-item-id="<?php bp_video_id(); ?>" data-type="video" href="#"><?php esc_html_e( 'Delete', 'buddyboss' ); ?></a>
						</li>
					</ul>
				</div>

			<?php } ?>
		<?php } ?>

	</div>

	<?php if ( 1 === $video_template->video_count ) { ?>
		<video id="video-<?php bp_video_id(); ?>" class="video-js single-activity-video" data-id="<?php bp_video_id(); ?>" data-attachment-full="<?php bp_video_attachment_image(); ?>" data-activity-id="<?php bp_video_activity_id(); ?>" data-privacy="<?php bp_video_privacy(); ?>" data-parent-activity-id="<?php bp_video_parent_activity_id(); ?>" data-album-id="<?php bp_video_album_id(); ?>" data-group-id="<?php bp_video_group_id(); ?>" data-attachment-id="<?php bp_video_attachment_id(); ?>" controls poster="<?php bp_video_attachment_image(); ?>" data-setup='{"fluid": true,"playbackRates": [0.5, 1, 1.5, 2], "fullscreenToggle" : false }'>
			<source src="<?php bp_video_link(); ?>"></source>
		</video>
		<?php if ( ! empty( bp_get_video_length() ) ) { ?>
		<p class="bb-video-duration"><?php bp_video_length(); ?></p>
		<?php } ?>
		<a class="bb-open-video-theatre bb-video-cover-wrap bb-item-cover-wrap hide" data-id="<?php bp_video_id(); ?>" data-attachment-full="<?php bp_video_attachment_image(); ?>" data-activity-id="<?php bp_video_activity_id(); ?>" data-privacy="<?php bp_video_privacy(); ?>" data-parent-activity-id="<?php bp_video_parent_activity_id(); ?>" data-album-id="<?php bp_video_album_id(); ?>" data-group-id="<?php bp_video_group_id(); ?>" data-attachment-id="<?php bp_video_attachment_id(); ?>" href="#">
			<img src="<?php echo esc_url( buddypress()->plugin_url ); ?>bp-templates/bp-nouveau/images/video-placeholder.jpg" data-src="<?php bp_video_attachment_image_thumbnail(); ?>" alt="<?php bp_video_title(); ?>" class="lazy" />
			<?php
			if ( $video_template->video_count > 3 && 2 === $video_template->current_video ) {
				$count = $video_template->video_count - 3;
				?>
				<span class="bb-videos-length"><span><strong>+<?php echo esc_html( $count ); ?></strong> <span><?php esc_html_e( 'More Videos', 'buddyboss' ); ?></span></span></span>
				<?php
			}
			?>
			<?php if ( ! empty( bp_get_video_length() ) ) { ?>
			<p class="bb-video-duration"><?php bp_video_length(); ?></p>
			<?php } ?>
		</a>
	<?php } else { ?>
		<a class="bb-open-video-theatre bb-video-cover-wrap bb-item-cover-wrap" data-id="<?php bp_video_id(); ?>" data-attachment-full="<?php bp_video_attachment_image(); ?>" data-activity-id="<?php bp_video_activity_id(); ?>" data-privacy="<?php bp_video_privacy(); ?>" data-parent-activity-id="<?php bp_video_parent_activity_id(); ?>" data-album-id="<?php bp_video_album_id(); ?>" data-group-id="<?php bp_video_group_id(); ?>" data-attachment-id="<?php bp_video_attachment_id(); ?>" href="#">
			<img src="<?php echo esc_url( buddypress()->plugin_url ); ?>bp-templates/bp-nouveau/images/video-placeholder.jpg" data-src="<?php bp_video_attachment_image_thumbnail(); ?>" alt="<?php bp_video_title(); ?>" class="lazy" />
			<?php
			if ( $video_template->video_count > 3 && 2 === $video_template->current_video ) {
				$count = $video_template->video_count - 3;
				?>
				<span class="bb-videos-length"><span><strong>+<?php echo esc_html( $count ); ?></strong> <span><?php esc_html_e( 'More Videos', 'buddyboss' ); ?></span></span></span>
				<?php
			}
			?>
			<?php if ( ! empty( bp_get_video_length() ) ) { ?>
			<p class="bb-video-duration"><?php bp_video_length(); ?></p>
			<?php } ?>
		</a>
	<?php } ?>
</div>

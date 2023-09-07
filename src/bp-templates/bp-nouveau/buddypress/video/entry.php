<?php
/**
 * BuddyBoss - Video Entry
 *
 * This template can be overridden by copying it to yourtheme/buddypress/video/entry.php.
 *
 * @package BuddyBoss\Core
 *
 * @since   BuddyBoss 1.7.0
 * @version 1.7.0
 */

global $video_template;

$attachment_id = bp_get_video_attachment_id();
$download_url  = bp_video_download_link( $attachment_id, bp_get_video_id() );
$group_id      = bp_get_video_group_id();
$move_id       = '';
$move_type     = '';
$video_privacy = bb_media_user_can_access( bp_get_video_id(), 'video' );
$can_edit      = true === (bool) $video_privacy['can_edit'];
$can_move      = true === (bool) $video_privacy['can_move'];
$can_delete    = true === (bool) $video_privacy['can_delete'];

if ( $group_id > 0 ) {
	$move_id   = $group_id;
	$move_type = 'group';
} else {
	$move_id   = bp_get_video_user_id();
	$move_type = 'profile';
}

$is_comment_vid = bp_video_is_activity_comment_video( $video_template->video );

$attachment_urls = bb_video_get_attachments_symlinks( bp_get_video_attachment_id(), bp_get_video_id() );

$has_no_thumbnail = '';
$attachment_full  = bp_get_video_popup_thumb();
$poster_full      = bp_get_video_directory_page_thumb();

if ( false !== strpos( $attachment_full, 'video-placeholder.jpg' ) || false !== strpos( $poster_full, 'video-placeholder.jpg' ) ) {
	$has_no_thumbnail = 'has-no-thumbnail';
}
?>
<li class="lg-grid-1-5 md-grid-1-3 sm-grid-1-3" data-id="<?php bp_video_id(); ?>" data-date-created="<?php bp_video_date_created(); ?>">
	<div class="bb-video-thumb bb-item-thumb <?php echo esc_attr( $has_no_thumbnail ); ?>">
		<div class="video-action-wrap item-action-wrap">
			<?php
			$report_btn = bp_video_get_report_link( array( 'id' => bp_get_video_id() ) );
			if ( $can_edit || $can_move || $can_delete || $report_btn ) {
				?>
				<a href="#" class="video-action_more item-action_more" data-balloon-pos="up" data-balloon="<?php esc_html_e( 'More actions', 'buddyboss' ); ?>">
					<i class="bb-icon-rl bb-icon-ellipsis-v"></i>
				</a>
				<div class="video-action_list item-action_list">
					<ul>
						<?php
						if ( $can_edit && ( bb_user_can_create_video() || $group_id > 0 ) ) {
							?>
							<li class="edit_thumbnail_video">
								<a href="#" data-action="video" data-video-attachments="<?php echo esc_html( json_encode( $attachment_urls ) ); ?>" data-video-attachment-id="<?php bp_video_attachment_id(); ?>" data-video-id="<?php bp_video_id(); ?>" class="ac-video-thumbnail-edit"><?php esc_html_e( 'Change Thumbnail', 'buddyboss' ); ?></a>
							</li>
							<?php
						}
						if ( $is_comment_vid ) {
							?>
							<li class="move_video move-disabled" data-balloon-pos="down" data-balloon="<?php esc_html_e( 'Video inherits activity privacy in comment. You are not allowed to move.', 'buddyboss' ); ?>">
								<a href="#"><?php esc_html_e( 'Move', 'buddyboss' ); ?></a>
							</li>
							<?php
						} elseif ( $can_move ) {
							?>
							<li class="move_video">
								<a href="#" data-action="video" data-video-id="<?php bp_video_id(); ?>" data-parent-activity-id="<?php bp_video_parent_activity_id(); ?>" data-item-activity-id="<?php bp_video_activity_id(); ?>" data-type="<?php echo esc_attr( $move_type ); ?>" id="<?php echo esc_attr( $move_id ); ?>" class="ac-video-move"><?php esc_html_e( 'Move', 'buddyboss' ); ?></a>
							</li>
							<?php
						}

						if ( $report_btn ) {
							?>
							<li class="report_file">
								<?php echo $report_btn; // phpcs:ignore ?>
							</li>
							<?php
						}

						if ( $can_delete ) {
							?>
							<li class="delete_file">
								<a class="video-file-delete" data-video-id="<?php bp_video_id(); ?>" data-parent-activity-id="<?php bp_video_parent_activity_id(); ?>" data-item-activity-id="<?php bp_video_activity_id(); ?>" data-item-from="video" data-item-id="<?php bp_video_id(); ?>" data-type="video" href="#"><?php esc_html_e( 'Delete', 'buddyboss' ); ?></a>
							</li>
							<?php
						}
						?>
					</ul>
				</div>
			<?php } ?>
		</div>

		<?php if ( ! empty( bp_get_video_length() ) ) { ?>
		<p class="bb-video-duration"><?php bp_video_length(); ?></p>
		<?php } ?>

		<a class="bb-open-video-theatre bb-video-cover-wrap bb-item-cover-wrap" data-id="<?php bp_video_id(); ?>" data-attachment-full="<?php echo esc_url( $attachment_full ); ?>" data-activity-id="<?php bp_video_activity_id(); ?>" data-privacy="<?php bp_video_privacy(); ?>" data-parent-activity-id="<?php bp_video_parent_activity_id(); ?>" data-album-id="<?php bp_video_album_id(); ?>" data-group-id="<?php bp_video_group_id(); ?>" data-attachment-id="<?php bp_video_attachment_id(); ?>" href="#">
			<img src="<?php echo esc_url( buddypress()->plugin_url ); ?>bp-templates/bp-nouveau/images/video-placeholder.jpg" data-src="<?php echo esc_url( $poster_full ); ?>" alt="<?php bp_video_title(); ?>" class="lazy"/>
		</a>

		<?php
		$video_privacy = bb_media_user_can_access( bp_get_video_id(), 'video' );
		$can_delete    = true === (bool) $video_privacy['can_delete'];
		if ( ( ( bp_is_my_profile() || bp_current_user_can( 'bp_moderate' ) ) || ( bp_is_group() && ( ( bp_is_group_video() && $can_delete ) || ( bp_is_group_albums() && $can_delete ) ) ) ) && ! bp_is_video_directory() ) :
			?>
			<div class="bb-video-check-wrap bb-action-check-wrap">
				<input id="bb-video-<?php bp_video_id(); ?>" class="bb-custom-check" type="checkbox" value="<?php bp_video_id(); ?>" name="bb-video-select" />
				<label class="bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Select', 'buddyboss' ); ?>" for="bb-video-<?php bp_video_id(); ?>"><span class="bb-icon-rl bb-icon-check"></span></label>
			</div>
		<?php endif; ?>
	</div>
</li>

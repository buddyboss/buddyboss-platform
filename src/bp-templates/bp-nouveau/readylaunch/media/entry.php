<?php
/**
 * ReadyLaunch - Media Entry template.
 *
 * This template handles displaying individual media entries in the media listing.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

global $media_template;

$move_id                  = '';
$move_type                = '';
$bp_media_id              = bp_get_media_id();
$bp_is_my_profile         = bp_is_my_profile();
$bp_user_is_moderate      = bp_current_user_can( 'bp_moderate' );
$bp_is_group              = bp_is_group();
$bp_is_group_media        = bp_is_group_media();
$bp_is_group_albums       = bp_is_group_albums();
$bp_is_media_directory    = bp_is_media_directory();
$attachment_id            = bp_get_media_attachment_id();
$group_id                 = bp_get_media_group_id();
$media_activity_id        = bp_get_media_activity_id();
$media_parent_activity_id = bp_get_media_parent_activity_id();
$bp_media_privacy         = bp_get_media_privacy();
$bp_media_album_id        = bp_get_media_album_id();


if ( 'video' === $media_template->media->type ) {
	$download_url  = bp_video_download_link( $attachment_id, $bp_media_id );
	$media_privacy = bb_media_user_can_access( $bp_media_id, 'video' );
	$can_edit      = true === (bool) $media_privacy['can_edit'];
	$can_move      = true === (bool) $media_privacy['can_move'];
	$can_delete    = true === (bool) $media_privacy['can_delete'];

	if ( $group_id > 0 ) {
		$move_id   = $group_id;
		$move_type = 'group';
	} else {
		$move_id   = bp_get_media_user_id();
		$move_type = 'profile';
	}

	$is_comment_vid = bp_video_is_activity_comment_video( $media_template->media );

	$length_formatted = wp_get_attachment_metadata( $attachment_id );
	$poster_id        = bb_get_video_thumb_id( $attachment_id );
	$poster_full      = bb_get_video_default_placeholder_image();
	$poster_default   = $poster_full;
	$poster_thumb     = $poster_full;

	if ( $poster_id ) {
		$poster_full  = bb_video_get_thumb_url( $bp_media_id, $poster_id, 'bb-video-profile-album-add-thumbnail-directory-poster-image' );
		$poster_thumb = bb_video_get_thumb_url( $bp_media_id, $poster_id, 'bb-video-activity-image' );
	}

	$attachment_urls = bb_video_get_attachments_symlinks( $attachment_id, $bp_media_id );

	?>
	<li class="lg-grid-1-5 md-grid-1-3 sm-grid-1-3 bb-video-li" data-id="<?php echo esc_attr( $bp_media_id ); ?>" data-date-created="<?php bp_media_date_created(); ?>">
		<div class="bb-video-thumb bb-item-thumb">
			<div class="video-action-wrap item-action-wrap bb_more_options action">
				<?php
				$report_btn = bp_video_get_report_link( array( 'id' => $bp_media_id ) );
				if ( $can_edit || $report_btn ) {
					?>
					<a href="#" class="video-action_more item-action_more bb_more_options_action" data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'More actions', 'buddyboss' ); ?>">
						<i class="bb-icons-rl-dots-three"></i>
					</a>
					<div class="video-action_list item-action_list bb_more_dropdown bb_more_options_list">
						<?php bp_get_template_part( 'common/more-options-view' ); ?>
						<ul>
							<?php
							if ( $can_edit && ( bb_user_can_create_video() || $group_id > 0 ) ) {
								?>
								<li class="edit_thumbnail_video">
									<a
										href="#"
										data-action="video"
										data-video-attachments="<?php echo esc_html( wp_json_encode( $attachment_urls ) ); ?>"
										data-video-attachment-id="<?php echo esc_attr( $attachment_id ); ?>"
										data-video-id="<?php echo esc_attr( $bp_media_id ); ?>"
										class="bb-rl-ac-video-thumbnail-edit"
									>
										<?php esc_html_e( 'Change Thumbnail', 'buddyboss' ); ?>
									</a>
								</li>
								<?php
							}
							if ( $is_comment_vid ) {
								?>
								<li class="move_video move-disabled" data-balloon-pos="down" data-balloon="<?php esc_attr_e( 'Video inherits activity privacy in comment. You are not allowed to move.', 'buddyboss' ); ?>">
									<a href="#"><?php esc_html_e( 'Move', 'buddyboss' ); ?></a>
								</li>
								<?php
							} elseif ( $can_move ) {
								?>
								<li class="move_video">
									<a
										href="#"
										data-action="video"
										data-video-id="<?php echo esc_attr( $bp_media_id ); ?>"
										data-parent-activity-id="<?php echo esc_attr( $media_parent_activity_id ); ?>"
										data-item-activity-id="<?php echo esc_attr( $media_activity_id ); ?>"
										data-type="<?php echo esc_attr( $move_type ); ?>"
										id="<?php echo esc_attr( $move_id ); ?>"
										class="bb-rl-ac-video-move"
									>
										<?php esc_html_e( 'Move', 'buddyboss' ); ?>
									</a>
								</li>
								<?php
							}
							if ( $can_delete ) {
								?>
								<li class="delete_file">
									<a
										class="bb-rl-video-file-delete"
										data-video-id="<?php echo esc_attr( $bp_media_id ); ?>"
										data-parent-activity-id="<?php echo esc_attr( $media_parent_activity_id ); ?>"
										data-item-activity-id="<?php echo esc_attr( $media_activity_id ); ?>"
										data-item-from="video"
										data-item-id="<?php echo esc_attr( $bp_media_id ); ?>"
										data-type="video"
										href="#"
									>
										<?php esc_html_e( 'Delete', 'buddyboss' ); ?>
									</a>
								</li>
								<?php
							}
							if ( $report_btn ) {
								?>
								<li class="report_file">
									<?php echo wp_kses_post( $report_btn ); ?>
								</li>
								<?php
							}
							?>
						</ul>
					</div>
					<div class="bb_more_dropdown_overlay"></div>
					<?php
				}
				?>
			</div>
			<p class="bb-video-loader"></p>
			<?php if ( ! empty( esc_html( $length_formatted['length_formatted'] ) ) ) { ?>
				<p class="bb-video-duration"><?php echo esc_html( $length_formatted['length_formatted'] ); ?></p>
			<?php } ?>
			<a
				class="bb-open-video-theatre bb-video-cover-wrap bb-item-cover-wrap"
				data-id="<?php echo esc_attr( $bp_media_id ); ?>"
				data-attachment-full="<?php echo esc_url( $poster_full ); ?>"
				data-activity-id="<?php echo esc_attr( $media_activity_id ); ?>"
				data-privacy="<?php echo esc_attr( $bp_media_privacy ); ?>"
				data-parent-activity-id="<?php echo esc_attr( $media_parent_activity_id ); ?>"
				data-album-id="<?php echo esc_attr( $bp_media_album_id ); ?>"
				data-group-id="<?php echo esc_attr( $group_id ); ?>"
				data-attachment-id="<?php echo esc_attr( $attachment_id ); ?>"
				href="#"
			>
				<img
					src="<?php echo esc_url( $poster_default ); ?>"
					data-src="<?php echo esc_url( $poster_thumb ); ?>"
					alt="<?php bp_media_title(); ?>"
					class="lazy"/>
			</a>
			<?php
			$video_privacy = bb_media_user_can_access( $bp_media_id, 'video' );
			$can_edit      = true === (bool) $video_privacy['can_edit'];

			if (
				(
					( $bp_is_my_profile || $bp_user_is_moderate ) ||
					(
						$bp_is_group &&
						(
							( $bp_is_group_media && $can_delete ) ||
							( $bp_is_group_albums && $can_delete )
						)
					)
				) &&
				! $bp_is_media_directory
			) :
				?>
				<div class="bb-media-check-wrap bb-action-check-wrap">
					<input
						id="bb-media-<?php echo esc_attr( $bp_media_id ); ?>"
						class="bb-custom-check"
						type="checkbox"
						value="<?php echo esc_attr( $bp_media_id ); ?>"
						name="bb-media-select" />
					<label
						class="bp-tooltip"
						data-bp-tooltip-pos="up"
						data-bp-tooltip="<?php esc_attr_e( 'Select', 'buddyboss' ); ?>"
						for="bb-media-<?php echo esc_attr( $bp_media_id ); ?>"
					>
						<span class="bb-icons-rl-check-circle"></span>
					</label>
				</div>
			<?php endif; ?>
		</div>

	</li>
	<?php
} else {
	$download_url  = bp_media_download_link( $attachment_id, $bp_media_id );
	$media_privacy = bb_media_user_can_access( $bp_media_id, 'photo' );
	$can_edit      = true === (bool) $media_privacy['can_edit'];
	$can_move      = true === (bool) $media_privacy['can_move'];
	$can_delete    = true === (bool) $media_privacy['can_delete'];

	if ( $group_id > 0 ) {
		$move_id   = $group_id;
		$move_type = 'group';
	} else {
		$move_id   = bp_get_media_user_id();
		$move_type = 'profile';
	}

	$is_comment_pic = bp_media_is_activity_comment_photo( $media_template->media );

	?>
	<li class="lg-grid-1-5 md-grid-1-3 sm-grid-1-3 bb-photo-li" data-id="<?php echo esc_attr( $bp_media_id ); ?>" data-date-created="<?php bp_media_date_created(); ?>">

	<div class="bb-photo-thumb bb-item-thumb">
		<div class="media-action-wrap bb_more_options action">
			<?php
			$report_btn = bp_media_get_report_link( array( 'id' => $bp_media_id ) );
			if ( $can_move || $report_btn || $can_delete ) {
				?>
				<a href="#" class="media-action_more bb_more_options_action" data-balloon-pos="up" data-balloon="<?php esc_html_e( 'More actions', 'buddyboss' ); ?>">
					<i class="bb-icons-rl-dots-three"></i>
				</a>
				<div class="media-action_list bb_more_dropdown bb_more_options_list">
					<?php bp_get_template_part( 'common/more-options-view' ); ?>
					<ul>
						<?php
						if ( $is_comment_pic ) {
							?>
							<li class="move_file move-disabled" data-balloon-pos="down" data-balloon="<?php esc_html_e( 'Photo inherits activity privacy in comment. You are not allowed to move.', 'buddyboss' ); ?>">
								<a href="#"><?php esc_html_e( 'Move', 'buddyboss' ); ?></a>
							</li>
							<?php
						} elseif ( $can_move ) {
							?>
							<li class="move_file">
								<a
									href="#"
									data-action="media"
									data-media-id="<?php echo esc_attr( $bp_media_id ); ?>"
									data-parent-activity-id="<?php echo esc_attr( $media_parent_activity_id ); ?>"
									data-item-activity-id="<?php echo esc_attr( $media_activity_id ); ?>"
									data-type="<?php echo esc_attr( $move_type ); ?>"
									id="<?php echo esc_attr( $move_id ); ?>"
									class="ac-media-move"
								>
									<?php esc_html_e( 'Move', 'buddyboss' ); ?>
								</a>
							</li>
							<?php
						}

						if ( $report_btn ) {
							?>
							<li class="report_file">
								<?php echo $report_btn; ?>
							</li>
							<?php
						}

						if ( $can_delete ) {
							?>
							<li class="delete_file">
								<a
									class="bb-rl-media-file-delete"
									data-media-id="<?php echo esc_attr( $bp_media_id ); ?>"
									data-parent-activity-id="<?php echo esc_attr( $media_parent_activity_id ); ?>"
									data-item-activity-id="<?php echo esc_attr( $media_activity_id ); ?>"
									data-item-from="media"
									data-item-id="<?php echo esc_attr( $bp_media_id ); ?>"
									data-type="media"
									href="#"
								>
									<?php esc_html_e( 'Delete', 'buddyboss' ); ?>
								</a>
							</li>
							<?php
						}
						?>
					</ul>
				</div>
				<div class="bb_more_dropdown_overlay"></div>
			<?php } ?>
		</div> <!--.media-action-wrap-->
		<a
			class="bb-rl-open-media-theatre bb-rl-entry-img bb-open-media-theatre bb-photo-cover-wrap bb-item-cover-wrap"
			data-id="<?php echo esc_attr( $bp_media_id ); ?>"
			data-attachment-full="<?php bb_media_photos_theatre_popup_image(); ?>"
			data-activity-id="<?php echo esc_attr( $media_activity_id ); ?>"
			data-privacy="<?php echo esc_attr( $bp_media_privacy ); ?>"
			data-parent-activity-id="<?php echo esc_attr( $media_parent_activity_id ); ?>"
			data-album-id="<?php echo esc_attr( $bp_media_album_id ); ?>"
			data-group-id="<?php echo esc_attr( $group_id ); ?>"
			data-attachment-id="<?php echo esc_attr( $attachment_id ); ?>"
			data-can-edit="<?php echo esc_attr( bp_media_user_can_edit( $bp_media_id ) ); ?>"
			aria-label="<?php esc_attr_e( 'Open image in media theatre', 'buddyboss' ); ?>"
			href="#">
			<img src="<?php echo esc_url( buddypress()->plugin_url ); ?>bp-templates/bp-nouveau/images/placeholder.png" data-src="<?php bb_media_photos_directory_image_thumbnail(); ?>" alt="<?php bp_media_title(); ?>" class="lazy"/>
		</a>
		<?php
		if (
			(
				( $bp_is_my_profile || $bp_user_is_moderate ) ||
				(
					$bp_is_group &&
					(
						( $bp_is_group_media && $can_delete ) ||
						( $bp_is_group_albums && $can_delete )
					)
				)
			) &&
			! $bp_is_media_directory
		) :
			?>
			<div class="bb-media-check-wrap bb-action-check-wrap">
				<input
					id="bb-media-<?php echo esc_attr( $bp_media_id ); ?>"
					class="bb-custom-check"
					type="checkbox"
					value="<?php echo esc_attr( $bp_media_id ); ?>"
					name="bb-media-select" />
				<label
					class="bp-tooltip"
					data-bp-tooltip-pos="up"
					data-bp-tooltip="<?php esc_html_e( 'Select', 'buddyboss' ); ?>"
					for="bb-media-<?php echo esc_attr( $bp_media_id ); ?>">
					<span class="bb-icons-rl-check-circle"></span>
				</label>
			</div>
		<?php endif; ?>
	</div>

</li>
	<?php
}

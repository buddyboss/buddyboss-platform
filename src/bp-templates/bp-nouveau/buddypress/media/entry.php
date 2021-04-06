<?php
/**
 * BuddyBoss - Media Entry
 *
 * @since BuddyBoss 1.0.0
 */

global $media_template;

$move_id   = '';
$move_type = '';


if ( 'video' === $media_template->media->type ) {
	$attachment_id = bp_get_media_attachment_id();
	$download_url  = bp_video_download_link( $attachment_id, bp_get_media_id() );
	$group_id      = bp_get_media_group_id();

	$media_privacy = bp_video_user_can_manage_video( bp_get_media_id(), bp_loggedin_user_id() );
	$can_manage    = true === (bool) $media_privacy['can_manage'];
	$can_move      = true === (bool) $media_privacy['can_move'];

	if ( $group_id > 0 ) {
		$move_id   = $group_id;
		$move_type = 'group';
	} else {
		$move_id   = bp_get_media_user_id();
		$move_type = 'profile';
	}

	$is_comment_vid = bp_video_is_activity_comment_video( $media_template->media );

	$length_formatted = wp_get_attachment_metadata( $attachment_id );
	$poster_id        = get_post_meta( $attachment_id, 'bp_video_preview_thumbnail_id', true );
	$poster_full      = buddypress()->plugin_url . 'bp-templates/bp-nouveau/images/placeholder.png';
	$poster_default   = buddypress()->plugin_url . 'bp-templates/bp-nouveau/images/placeholder.png';
	$poster_thumb     = buddypress()->plugin_url . 'bp-templates/bp-nouveau/images/placeholder.png';

	if ( $poster_id ) {
		$poster_full  = wp_get_attachment_image_url( $poster_id, 'full' );
		$poster_thumb = wp_get_attachment_image_url( $poster_id, 'bp-video-thumbnail' );
	}

	$attachment_urls = bp_video_get_attachments( bp_get_video_attachment_id() );

	?>
	<li class="lg-grid-1-5 md-grid-1-3 sm-grid-1-3 bb-video-li" data-id="<?php bp_media_id(); ?>" data-date-created="<?php bp_media_date_created(); ?>">
		<div class="bb-video-thumb bb-item-thumb">
			<div class="video-action-wrap item-action-wrap">
				<?php
				$report_btn = bp_video_get_report_link( array( 'id' => bp_get_media_id() ) );
				if ( $can_manage || $report_btn ) {
					?>
					<a href="#" class="video-action_more item-action_more" data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'More actions', 'buddyboss' ); ?>">
						<i class="bb-icon-menu-dots-v"></i>
					</a>
					<div class="video-action_list item-action_list">
						<ul>
							<?php
							if ( $can_manage ) {
								?>
								<li class="edit_thumbnail_video">
									<a href="#" data-action="video" data-video-attachments="<?php echo esc_html(json_encode( $attachment_urls )); ?>" data-video-attachment-id="<?php bp_media_attachment_id(); ?>" data-video-id="<?php bp_media_id(); ?>" class="ac-video-thumbnail-edit"><?php esc_html_e( 'Add Thumbnail', 'buddyboss' ); ?></a>
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
									<a href="#" data-action="video" data-video-id="<?php bp_media_id(); ?>" data-parent-activity-id="<?php bp_media_parent_activity_id(); ?>" data-item-activity-id="<?php bp_media_activity_id(); ?>" data-type="<?php echo esc_attr( $move_type ); ?>" id="<?php echo esc_attr( $move_id ); ?>" class="ac-video-move"><?php esc_html_e( 'Move', 'buddyboss' ); ?></a>
								</li>
								<?php
							}
							if ( $can_manage ) {
								?>
								<li class="delete_file">
									<a class="video-file-delete" data-video-id="<?php bp_media_id(); ?>" data-parent-activity-id="<?php bp_media_parent_activity_id(); ?>" data-item-activity-id="<?php bp_media_activity_id(); ?>" data-item-from="video" data-item-id="<?php bp_media_id(); ?>" data-type="video" href="#"><?php esc_html_e( 'Delete', 'buddyboss' ); ?></a>
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
					<?php
				}
				?>
			</div>
			<?php if ( ! empty( esc_html( $length_formatted['length_formatted'] ) ) ) { ?>
			<p class="bb-video-duration"><?php echo esc_html( $length_formatted['length_formatted'] ); ?></p>
			<?php } ?>
			<a class="bb-open-video-theatre bb-video-cover-wrap bb-item-cover-wrap" data-id="<?php bp_media_id(); ?>" data-attachment-full="<?php echo esc_url( $poster_full ); ?>" data-activity-id="<?php bp_media_activity_id(); ?>" data-privacy="<?php bp_media_privacy(); ?>" data-parent-activity-id="<?php bp_media_parent_activity_id(); ?>" data-album-id="<?php bp_media_album_id(); ?>" data-group-id="<?php bp_media_group_id(); ?>" data-attachment-id="<?php bp_media_attachment_id(); ?>" href="#">
				<img src="<?php echo esc_url( $poster_default ); ?>" data-src="<?php echo esc_url( $poster_thumb ); ?>" alt="<?php bp_media_title(); ?>" class="lazy"/>
			</a>
			<?php
			$video_privacy = bp_video_user_can_manage_video( bp_get_media_id(), bp_loggedin_user_id() );
			$can_manage    = true === (bool) $video_privacy['can_manage'];
			if ( ( ( bp_is_my_profile() || bp_current_user_can( 'bp_moderate' ) ) || ( bp_is_group() && ( ( bp_is_group_media() && $can_manage ) || ( bp_is_group_albums() && $can_manage ) ) ) ) && ! bp_is_media_directory() ) :
				?>
				<div class="bb-media-check-wrap bb-action-check-wrap">
					<input id="bb-media-<?php bp_media_id(); ?>" class="bb-custom-check" type="checkbox" value="<?php bp_media_id(); ?>" name="bb-media-select" />
					<label class="bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Select', 'buddyboss' ); ?>" for="bb-media-<?php bp_media_id(); ?>"><span class="bb-icon bb-icon-check"></span></label>
				</div>
			<?php endif; ?>
		</div>

	</li>
	<?php
} else {
	$attachment_id = bp_get_media_attachment_id();
	$download_url  = bp_media_download_link( $attachment_id, bp_get_media_id() );
	$group_id      = bp_get_media_group_id();

	$media_privacy = bp_media_user_can_manage_media( bp_get_media_id(), bp_loggedin_user_id() );
	$can_manage    = ( true === (bool) $media_privacy['can_manage'] ) ? true : false;
	$can_move      = ( true === (bool) $media_privacy['can_add'] ) ? true : false;

	if ( $group_id > 0 ) {
		$move_id   = $group_id;
		$move_type = 'group';
	} else {
		$move_id   = bp_get_media_user_id();
		$move_type = 'profile';
	}

	$is_comment_pic = bp_media_is_activity_comment_photo( $media_template->media );

	?>
	<li class="lg-grid-1-5 md-grid-1-3 sm-grid-1-3 bb-photo-li" data-id="<?php bp_media_id(); ?>" data-date-created="<?php bp_media_date_created(); ?>">

	<div class="bb-photo-thumb bb-item-thumb">
		<div class="media-action-wrap">
			<?php
			$report_btn = bp_media_get_report_link( array( 'id' => bp_get_media_id() ) );
			if ( $can_manage || $report_btn ) {
				?>
				<a href="#" class="media-action_more" data-balloon-pos="up" data-balloon="<?php esc_html_e( 'More actions', 'buddyboss' ); ?>">
					<i class="bb-icon-menu-dots-v"></i>
				</a>
				<div class="media-action_list">
					<ul>
						<?php
						if ( $can_manage ) {
							if ( $is_comment_pic ) {
								?>
								<li class="move_file move-disabled" data-balloon-pos="down" data-balloon="<?php esc_html_e( 'Photo inherits activity privacy in comment. You are not allowed to move.', 'buddyboss' ); ?>">
									<a href="#"><?php esc_html_e( 'Move', 'buddyboss' ); ?></a>
								</li>
								<?php
							} else {
								if ( $can_move ) {
									?>
									<li class="move_file">
										<a href="#" data-action="media" data-media-id="<?php bp_media_id(); ?>" data-parent-activity-id="<?php bp_media_parent_activity_id(); ?>" data-item-activity-id="<?php bp_media_activity_id(); ?>" data-type="<?php echo esc_attr( $move_type ); ?>" id="<?php echo esc_attr( $move_id ); ?>" class="ac-media-move"><?php esc_html_e( 'Move', 'buddyboss' ); ?></a>
									</li>
									<?php
								}
							}
						}
						?>

						<?php
						if ( $report_btn ) {
							?>
							<li class="report_file">
								<?php echo $report_btn; ?>
							</li>
							<?php
						}
						?>

						<?php if ( $can_manage ) { ?>
							<li class="delete_file">
								<a class="media-file-delete" data-media-id="<?php bp_media_id(); ?>" data-parent-activity-id="<?php bp_media_parent_activity_id(); ?>" data-item-activity-id="<?php bp_media_activity_id(); ?>" data-item-from="media" data-item-id="<?php bp_media_id(); ?>" data-type="media" href="#"><?php esc_html_e( 'Delete', 'buddyboss' ); ?></a>
							</li>
						<?php } ?>
					</ul>
				</div>
			<?php } ?>
		</div> <!--.media-action-wrap-->
		<a class="bb-open-media-theatre bb-photo-cover-wrap bb-item-cover-wrap"
		   data-id="<?php bp_media_id(); ?>"
		   data-attachment-full="<?php bp_media_attachment_image(); ?>"
		   data-activity-id="<?php bp_media_activity_id(); ?>"
		   data-privacy="<?php bp_media_privacy(); ?>"
		   data-parent-activity-id="<?php bp_media_parent_activity_id(); ?>"
		   data-album-id="<?php bp_media_album_id(); ?>"
		   data-group-id="<?php bp_media_group_id(); ?>"
		   data-attachment-id="<?php bp_media_attachment_id(); ?>"
            data-can-edit="<?php echo esc_attr( bp_media_user_can_edit( bp_get_media_id() ) ); ?>"
		   href="#">
			<img src="<?php echo esc_url( buddypress()->plugin_url ); ?>bp-templates/bp-nouveau/images/placeholder.png" data-src="<?php bp_media_attachment_image_thumbnail(); ?>" alt="<?php bp_media_title(); ?>" class="lazy"/>
		</a>
		<?php
		if ( ( ( bp_is_my_profile() || bp_current_user_can( 'bp_moderate' ) ) || ( bp_is_group() && ( ( bp_is_group_media() && $can_manage ) || ( bp_is_group_albums() && $can_manage ) ) ) ) && ! bp_is_media_directory() ) :
			?>
			<div class="bb-media-check-wrap bb-action-check-wrap">
				<input id="bb-media-<?php bp_media_id(); ?>" class="bb-custom-check" type="checkbox" value="<?php bp_media_id(); ?>" name="bb-media-select" />
				<label class="bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_html_e( 'Select', 'buddyboss' ); ?>" for="bb-media-<?php bp_media_id(); ?>"><span class="bb-icon bb-icon-check"></span></label>
			</div>
		<?php endif; ?>
	</div>

</li>
	<?php
}

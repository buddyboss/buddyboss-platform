<?php
/**
 * BuddyBoss - Media Entry
 *
 * @since BuddyBoss 1.0.0
 */

global $media_template;

$attachment_id = bp_get_media_attachment_id();
$download_url  = bp_media_download_link( $attachment_id, bp_get_media_id() );
$group_id      = bp_get_media_group_id();
$move_id       = '';
$move_type     = '';
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
<li class="lg-grid-1-5 md-grid-1-3 sm-grid-1-3" data-id="<?php bp_media_id(); ?>" data-date-created="<?php bp_media_date_created(); ?>">

	<div class="bb-photo-thumb">
		<div class="media-action-wrap">
			<?php
			$report_btn = bp_media_get_report_link( array( 'id' => bp_get_media_id() ) );
			if ( $can_manage || $report_btn ) { ?>
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
		<a class="bb-open-media-theatre bb-photo-cover-wrap"
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
			<div class="bb-media-check-wrap">
				<input id="bb-media-<?php bp_media_id(); ?>" class="bb-custom-check" type="checkbox" value="<?php bp_media_id(); ?>" name="bb-media-select" />
				<label class="bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_html_e( 'Select', 'buddyboss' ); ?>" for="bb-media-<?php bp_media_id(); ?>"><span class="bb-icon bb-icon-check"></span></label>
			</div>
		<?php endif; ?>
	</div>

</li>

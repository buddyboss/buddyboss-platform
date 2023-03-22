<?php
/**
 * The template for activity media
 *
 * This template can be overridden by copying it to yourtheme/buddypress/media/activity-entry.php.
 *
 * @since   BuddyBoss 1.0.0
 * @version 1.0.0
 */

global $media_template;

$width         = isset( $media_template->media->attachment_data->meta['width'] ) ? $media_template->media->attachment_data->meta['width'] : 0;
$height        = isset( $media_template->media->attachment_data->meta['height'] ) ? $media_template->media->attachment_data->meta['height'] : 0;
$attachment_id = bp_get_media_attachment_id();
$download_url  = bp_media_download_link( $attachment_id, bp_get_media_id() );
$group_id      = bp_get_media_group_id();
$move_id       = '';
$move_type     = '';
$media_privacy = bb_media_user_can_access( bp_get_media_id(), 'photo' );
$can_move      = true === (bool) $media_privacy['can_move'];
$can_delete    = true === (bool) $media_privacy['can_delete'];
$db_privacy    = bp_get_media_privacy();

if ( $group_id > 0 ) {
	$move_id   = $group_id;
	$move_type = 'group';
} else {
	$move_id   = bp_get_media_user_id();
	$move_type = 'profile';
}
$is_comment_pic = bp_media_is_activity_comment_photo( $media_template->media );
$more_media     = $media_template->media_count > 5 ? true : false;
?>

<div class="bb-activity-media-elem media-activity 
	<?php
	echo esc_attr( bp_get_media_id() ) . ' ';
	echo $media_template->current_media > 4 ? esc_attr( 'hide' ) : '';
	echo 1 === $media_template->media_count || $media_template->media_count > 1 && 0 === $media_template->current_media ? esc_attr( 'act-grid-1-1 ' ) : '';
	echo $media_template->media_count > 1 && $media_template->current_media > 0 ? 'act-grid-1-2 ' : '';
	echo $width > $height ? 'bb-horizontal-layout' : '';
	echo $height > $width || $width === $height ? esc_attr( 'bb-vertical-layout' ) : '';
	echo ( $more_media && 4 === $media_template->current_media ) ? esc_attr( ' no_more_option ' ) : '';
	?>
	" data-id="<?php echo esc_attr( bp_get_media_id() ); ?>">
	<div class="media-action-wrap">
		<?php
		if ( $can_move || $can_delete ) {
			$item_id = 0;
			if ( bp_loggedin_user_id() === bp_get_media_user_id() || bp_current_user_can( 'bp_moderate' ) ) {
				?>
					<a href="#" class="media-action_more" data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'More actions', 'buddyboss' ); ?>">
						<i class="bb-icon-rl bb-icon-ellipsis-v"></i>
					</a>
					<div class="media-action_list">
						<ul class="conflict-activity-ul-li-comment">
							<?php
							if ( ! in_array( $db_privacy, array( 'forums', 'message' ), true ) ) {
								if ( $is_comment_pic ) {
									?>
										<li class="move_file media-action-class move-disabled" data-balloon-pos="down" data-balloon="<?php esc_html_e( 'Photo inherits activity privacy in comment. You are not allowed to move.', 'buddyboss' ); ?>">
											<a href="#"><?php esc_attr_e( 'Move', 'buddyboss' ); ?></a>
										</li>
										<?php
								} elseif ( $can_move ) {
									?>
									<li class="move_file media-action-class">
										<a href="#" data-media-id="<?php bp_media_id(); ?>" data-action="activity" data-parent-activity-id="<?php bp_media_parent_activity_id(); ?>" data-item-activity-id="<?php bp_media_activity_id(); ?>" data-type="<?php echo esc_attr( $move_type ); ?>" id="<?php echo esc_attr( $move_id ); ?>" class="ac-media-move"><?php esc_attr_e( 'Move', 'buddyboss' ); ?></a>
									</li>
									<?php
								}
							}
							$item_id = 0;
							if ( bp_is_active( 'activity' ) ) {
								if ( bp_get_activity_comment_id() ) {
									$item_id = bp_get_activity_comment_id();
								} else {
									$item_id = bp_get_activity_id();
								}
							}
							if ( $can_delete ) {
								?>
								<li class="delete_file media-action-class">
									<a class="media-file-delete" data-item-activity-id="<?php echo esc_attr( $item_id ); ?>" data-parent-activity-id="<?php bp_media_parent_activity_id(); ?>" data-item-from="activity" data-item-id="<?php bp_media_id(); ?>" data-type="media" href="#"><?php esc_html_e( 'Delete', 'buddyboss' ); ?></a>
								</li>
								<?php
							}
							?>
						</ul>
					</div>
				<?php } ?>
		<?php } ?>
	</div> <!--.media-action-wrap-->
	<a href="#"
		class="bb-open-media-theatre entry-img"
		data-id="<?php bp_media_id(); ?>"
		data-attachment-id="<?php bp_media_attachment_id(); ?>"
		data-attachment-full="<?php bb_media_photos_theatre_popup_image(); ?>"
		data-activity-id="<?php bp_media_activity_id(); ?>"
		data-privacy="<?php bp_media_privacy(); ?>"
		data-parent-activity-id="<?php bp_media_parent_activity_id(); ?>"
		data-album-id="<?php bp_media_album_id(); ?>"
		data-group-id="<?php bp_media_group_id(); ?>"
		data-can-edit="<?php echo esc_attr( bp_media_user_can_edit( bp_get_media_id() ) ); ?>"
	>
		<?php $size = 1 === $media_template->media_count ? 'bb-media-activity-image' : 'bb-media-photos-album-directory-image-medium'; ?>
		<img src="<?php echo esc_url( buddypress()->plugin_url ); ?>bp-templates/bp-nouveau/images/placeholder.png" data-src="<?php echo 1 === $media_template->media_count ? esc_url( bp_get_media_attachment_image_activity_thumbnail() ) : esc_url( bb_get_media_photos_directory_image_thumbnail() ); ?>" class="no-round photo lazy" alt="<?php bp_media_title(); ?>" />

		<?php
		if ( $media_template->media_count > 5 && 4 === $media_template->current_media ) {
			$count = $media_template->media_count - 5;
			?>
			<span class="bb-photos-length"><span><strong>+<?php echo esc_html( $count ); ?></strong> <span><?php esc_html_e( 'More Photos', 'buddyboss' ); ?></span></span></span>
			<?php
		}
		?>
	</a>
</div>

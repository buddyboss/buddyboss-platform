<?php
/**
 * ReadyLaunch - The template for activity media.
 *
 * @since   BuddyBoss 2.9.00
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

global $media_template;

$bp_get_media_id          = bp_get_media_id();
$media_activity_id        = bp_get_media_activity_id();
$media_parent_activity_id = bp_get_media_parent_activity_id();
$width                    = $media_template->media->attachment_data->meta['width'] ?? 0;
$height                   = $media_template->media->attachment_data->meta['height'] ?? 0;
$attachment_id            = bp_get_media_attachment_id();
$download_url             = bp_media_download_link( $attachment_id, $bp_get_media_id );
$group_id                 = bp_get_media_group_id();
$move_id                  = '';
$move_type                = '';
$media_privacy            = bb_media_user_can_access( $bp_get_media_id, 'photo' );
$can_move                 = true === (bool) $media_privacy['can_move'];
$can_delete               = true === (bool) $media_privacy['can_delete'];
$db_privacy               = bp_get_media_privacy();
$media_user_id            = bp_get_media_user_id();
$media_count              = $media_template->media_count;

if ( $group_id > 0 ) {
	$move_id   = $group_id;
	$move_type = 'group';
} else {
	$move_id   = $media_user_id;
	$move_type = 'profile';
}
$is_comment_pic = bp_media_is_activity_comment_photo( $media_template->media );
$max_length     = $is_comment_pic ? bb_media_get_activity_comment_max_thumb_length() : bb_media_get_activity_max_thumb_length();
$more_media     = $media_count > $max_length;
?>

<div class="bb-rl-activity-media-elem bb-rl-media-activity
	<?php
	echo esc_attr( $bp_get_media_id ) . ' ';
	echo ( $media_template->current_media > ( $max_length - 1 ) ) ? esc_attr( 'hide ' ) : '';
	echo 1 === $media_count ? esc_attr( 'act-grid-1-1 ' ) : '';
	echo ( 1 === $media_count || $media_count > 1 ) && 0 === $media_template->current_media ? esc_attr( 'act-grid-1-1 ' ) : '';
	echo $media_count > 1 && $media_template->current_media > 0 ? 'act-grid-1-2 ' : '';
	echo ( $more_media && ( $max_length - 1 ) === $media_template->current_media ) ? esc_attr( ' no_more_option ' ) : '';
	?>
	" data-id="<?php echo esc_attr( $bp_get_media_id ); ?>">
	<div class="bb-rl-more_dropdown-wrap">
		<?php
		if ( $can_move || $can_delete ) {
			if ( bp_loggedin_user_id() === $media_user_id || bp_current_user_can( 'bp_moderate' ) ) {
				?>
				<a href="#" class="bb_rl_more_dropdown__action" data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'More actions', 'buddyboss' ); ?>">
					<i class="bb-icons-rl-dots-three"></i>
				</a>
				<div class="bb-rl-media-action_list bb_rl_more_dropdown">
					<ul class="bb-rl-conflict-activity-ul-li-comment">
						<?php
						if ( ! in_array( $db_privacy, array( 'forums', 'message' ), true ) ) {
							if ( $is_comment_pic ) {
								?>
								<li class="bb_rl_move_file bb-rl-media-action-class bb-rl-move-disabled" data-balloon-pos="down" data-balloon="<?php esc_html_e( 'Photo inherits activity privacy in comment. You are not allowed to move.', 'buddyboss' ); ?>">
									<a href="#"><?php esc_attr_e( 'Move', 'buddyboss' ); ?></a>
								</li>
								<?php
							} elseif ( $can_move ) {
								?>
								<li class="bb_rl_move_file bb-rl-media-action-class">
									<a href="#" data-media-id="<?php echo esc_attr( $bp_get_media_id ); ?>" data-action="activity" data-parent-activity-id="<?php echo esc_attr( $media_parent_activity_id ); ?>" data-item-activity-id="<?php echo esc_attr( $media_activity_id ); ?>" data-type="<?php echo esc_attr( $move_type ); ?>" id="<?php echo esc_attr( $move_id ); ?>" class="ac-media-move"><?php esc_attr_e( 'Move', 'buddyboss' ); ?></a>
								</li>
								<?php
							}
						}
						if ( $can_delete ) {
							$item_id = ( bp_is_active( 'activity' ) ) ? ( bp_get_activity_comment_id() ?? bp_get_activity_id() ) : 0;
							?>
							<li class="bb_rl_delete_file bb-rl-media-action-class">
								<a class="bb-rl-media-file-delete" data-item-activity-id="<?php echo esc_attr( $item_id ); ?>" data-parent-activity-id="<?php echo esc_attr( $media_parent_activity_id ); ?>" data-item-from="activity" data-item-id="<?php echo esc_attr( $bp_get_media_id ); ?>" data-type="media" href="#"><?php esc_html_e( 'Delete', 'buddyboss' ); ?></a>
							</li>
							<?php
						}
						?>
					</ul>
				</div>
				<div class="bb_rl_more_dropdown_overlay"></div>
			<?php } ?>
		<?php } ?>
	</div> <!--.bb-rl-more_dropdown-wrap-->
	<a href="#"
		class="bb-rl-open-media-theatre bb-rl-entry-img"
		data-id="<?php echo esc_attr( $bp_get_media_id ); ?>"
		data-attachment-id="<?php echo esc_attr( $attachment_id ); ?>"
		data-attachment-full="<?php bb_media_photos_theatre_popup_image(); ?>"
		data-activity-id="<?php echo esc_attr( $media_activity_id ); ?>"
		data-privacy="<?php echo esc_attr( $db_privacy ); ?>"
		data-parent-activity-id="<?php echo esc_attr( $media_parent_activity_id ); ?>"
		data-album-id="<?php bp_media_album_id(); ?>"
		data-group-id="<?php echo esc_attr( $group_id ); ?>"
		data-can-edit="<?php echo esc_attr( bp_media_user_can_edit( $bp_get_media_id ) ); ?>"
	>
		<?php $size = 1 === $media_count ? 'bb-media-activity-image' : 'bb-media-photos-album-directory-image-medium'; ?>
		<img src="<?php echo esc_url( buddypress()->plugin_url ); ?>bp-templates/bp-nouveau/images/placeholder.png" data-src="<?php echo 1 === $media_count ? esc_url( bp_get_media_attachment_image_activity_thumbnail() ) : esc_url( bb_get_media_photos_directory_image_thumbnail() ); ?>" class="no-round photo lazy" alt="<?php bp_media_title(); ?>" />

		<?php
		if ( $media_count > $max_length && ( $max_length - 1 ) === $media_template->current_media ) {
			$count = $media_count - $max_length;
			?>
			<span class="bb-rl-photos-length"><span><strong>+<?php echo esc_html( $count ); ?></strong> <span><?php esc_html_e( 'more photos', 'buddyboss' ); ?></span></span></span>
			<?php
		}
		?>
	</a>
</div>

<?php
/**
 * BuddyBoss - Activity Media
 *
 * @since BuddyBoss 1.0.0
 */
?>

<?php
global $media_template;

$width  	   =  isset( $media_template->media->attachment_data->meta['width'] ) ? $media_template->media->attachment_data->meta['width'] : 0;
$height 	   = isset( $media_template->media->attachment_data->meta['height'] ) ? $media_template->media->attachment_data->meta['height'] : 0;
$attachment_id = bp_get_media_attachment_id();
$download_url  = bp_media_download_link( $attachment_id, bp_get_media_id() );
$group_id      = bp_get_media_group_id();
$move_id       = '';
$move_type     = '';

if ( $group_id > 0 ) {
	$move_id   = $group_id;
	$move_type = 'group';
} else {
	$move_id   = bp_get_media_user_id();
	$move_type = 'profile';
}
?>

<div class="bb-activity-media-elem media-activity <?php echo $media_template->current_media > 4 ? 'hide' : ''; echo $media_template->media_count == 1 || $media_template->media_count > 1 && $media_template->current_media == 0 ? 'act-grid-1-1 ' : ''; echo $media_template->media_count > 1 && $media_template->current_media > 0 ? 'act-grid-1-2 ' : ''; echo $width > $height ? 'bb-horizontal-layout' : ''; echo $height > $width || $width == $height ? 'bb-vertical-layout' : ''; ?>" data-id="<?php echo esc_attr( bp_get_media_id() ); ?>">
	<div class="media-action-wrap">
		<a href="#" class="media-action_more" data-balloon-pos="up" data-balloon="<?php _e( 'More actions', 'buddyboss' ); ?>">
			<i class="bb-icon-menu-dots-v"></i>
		</a>
		<div class="media-action_list">
			<ul class="conflict-activity-ul-li-comment">
				<li class="move_file">
					<a href="#" data-media-id="<?php bp_media_id(); ?>" data-action="activity" data-parent-activity-id="<?php bp_media_parent_activity_id(); ?>" data-item-activity-id="<?php bp_media_activity_id(); ?>" data-type="<?php echo $move_type; ?>" id="<?php echo $move_id; ?>" class="ac-media-move"><?php _e( 'Move', 'buddyboss' ); ?></a>
				</li>
				<li class="delete_file">
					<a class="media-file-delete" data-item-activity-id="<?php bp_media_activity_id(); ?>" data-parent-activity-id="<?php bp_media_parent_activity_id(); ?>" data-item-from="activity" data-item-id="<?php bp_media_id(); ?>" data-type="media" href="#"><?php _e( 'Delete', 'buddyboss' ); ?></a>
				</li>
			</ul>
		</div>
	</div> <!--.media-action-wrap-->
	<a href="#"
	   class="bb-open-media-theatre entry-img"
	   data-id="<?php bp_media_id(); ?>"
	   data-attachment-id="<?php bp_media_attachment_id(); ?>"
	   data-attachment-full="<?php bp_media_attachment_image(); ?>"
	   data-activity-id="<?php bp_media_activity_id(); ?>"
	   data-privacy="<?php bp_media_privacy(); ?>"
	   data-parent-activity-id="<?php bp_media_parent_activity_id(); ?>"
	   data-album-id="<?php bp_media_album_id(); ?>"
	   data-group-id="<?php bp_media_group_id(); ?>"
	>
		<img src="<?php echo buddypress()->plugin_url; ?>bp-templates/bp-nouveau/images/placeholder.png" data-src="<?php bp_media_attachment_image_activity_thumbnail(); ?>" class="no-round photo lazy" alt="<?php bp_media_title(); ?>" />

		<?php if ( $media_template->media_count > 5 && $media_template->current_media == 4 ) {
			?>
			<span class="bb-photos-length"><span><strong>+<?php echo $media_template->media_count - 5; ?></strong> <span><?php _e( 'More Photos', 'buddyboss' ); ?></span></span></span>
			<?php
		} ?>
	</a>
</div>

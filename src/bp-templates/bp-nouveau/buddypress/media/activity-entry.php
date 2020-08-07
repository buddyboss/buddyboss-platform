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
?>

<div class="bb-activity-media-elem media-activity <?php echo $media_template->current_media > 4 ? 'hide' : ''; echo $media_template->media_count == 1 || $media_template->media_count > 1 && $media_template->current_media == 0 ? 'act-grid-1-1 ' : ''; echo $media_template->media_count > 1 && $media_template->current_media > 0 ? 'act-grid-1-2 ' : ''; echo $width > $height ? 'bb-horizontal-layout' : ''; echo $height > $width || $width == $height ? 'bb-vertical-layout' : ''; ?>" data-id="<?php echo esc_attr( bp_get_media_id() ); ?>">
	<div class="media-action-wrap">
		<a href="<?php echo $download_url; ?>" class="media-action_download" data-id="<?php bp_document_id(); ?>" data-activity-id="194912" data-balloon-pos="up" data-balloon="Download">
			<i class="bb-icon-download"></i>
		</a>

		<a href="#" class="media-action_more" data-balloon-pos="up" data-balloon="More actions">
			<i class="bb-icon-menu-dots-v"></i>
		</a>
		<div class="media-action_list">
			<ul class="conflict-activity-ul-li-comment">
				<li class="copy_download_file_url">
					<a href="<?php echo $download_url; ?>">Copy Download Link</a>
				</li>
				<li class="move_file">
					<a href="#" data-action="media" data-type="profile" id="2" class="ac-media-move">Move</a>
				</li>
				<li class="delete_file">
					<a class="media-file-delete" data-item-activity-id="194912" data-item-from="activity" data-item-preview-attachment-id="5106" data-item-attachment-id="5106" data-item-id="<?php bp_document_id(); ?>" data-type="media" href="#">Delete</a>
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

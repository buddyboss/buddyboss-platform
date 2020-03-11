<?php
/**
 * BuddyBoss - Media Entry
 *
 * @since BuddyBoss 1.0.0
 */

$attachment_id = bp_get_document_attachment_id();
$extension = '';
if ( $attachment_id ) {
	$extension = bp_document_extension( $attachment_id );
	$svg_icon  = bp_document_svg_icon( $extension );
	$link = wp_get_attachment_url( $attachment_id );
	$move_class = 'ac-document-move';
	$listing_class = 'ac-document-list';
	$type = 'document';
} else {
	$svg_icon  = bp_document_svg_icon('folder' );
	$link = bp_get_folder_link();
	$move_class = 'ac-folder-move';
	$listing_class = 'ac-folder-list';
	$type = 'folder';
}

?>
<div class="media-folder_items <?php echo $listing_class; ?>" data-id="<?php bp_document_id(); ?>">

	<div class="media-folder_icon">
		<a href="<?php echo esc_url( $link ); ?>">
			<i class="<?php echo $svg_icon; ?>"></i>
		</a>
	</div>

	<div class="media-folder_details">
		<a class="media-folder_name" href="<?php echo esc_url( $link ); ?>">
			<span><?php bp_document_title(); ?></span><?php echo $extension ? '.' . $extension : ''; ?>
			<span class="media-document-id" data-item-id="<?php echo base64_encode( bp_get_document_id() ); ?>" style="display: none;"></span>
			<span class="media-document-attachment-id" data-item-id="<?php echo base64_encode( bp_get_document_attachment_id() ); ?>" style="display: none;"></span>
			<span class="media-document-type" data-item-id="<?php echo esc_attr( $type ); ?>" style="display: none;"></span>
		</a>
		<div class="media-folder_name_edit_wrap">
			<input type="text" value="" class="media-folder_name_edit" />
			<?php if( wp_is_mobile() ){ ?>
				<a href="#" class="name_edit_cancel button small"><?php _e( 'Cancel', 'buddyboss' ); ?></a>
				<a href="#" class="name_edit_save button small pull-right"><?php _e( 'Save', 'buddyboss' ); ?></a>
			<?php } ?>

		</div>

	</div>

	<div class="media-folder_modified">
		<div  class="media-folder_details__bottom">
			<span class="media-folder_date"><?php bp_document_date(); ?></span>
			<?php if ( ! bp_is_user() ) { ?>
				<span class="media-folder_author">by <?php bp_document_author(); ?></span>
			<?php } ?>
		</div>
	</div>

	<div class="media-folder_visibility">
		<div  class="media-folder_details__bottom">
			<span><?php bp_document_privacy(); ?></span>
		</div>
	</div>

	<?php if ( bp_is_my_profile() || ( bp_is_group() && groups_can_user_manage_albums( bp_loggedin_user_id(), bp_get_current_group_id() ) ) ) : ?>
		<div class="media-folder_actions">
			<a href="#" class="media-folder_action__anchor">
				<i class="bb-icon-menu-dots-v"></i>
			</a>
			<div class="media-folder_action__list">
				<ul>
					<?php if( $attachment_id ){ ?>
						<li class="download_file"><a href="<?php echo esc_url( $link ); ?>"><?php _e( 'Download', 'buddyboss' ); ?></a></li>
					<?php } ?>
					<li class="rename_file"><a href="#" data-type="<?php echo esc_attr( $type ); ?>" class="ac-document-rename"><?php _e( 'Rename', 'buddyboss' ); ?></a></li>
					<li class="move_file"><a href="#" class="<?php echo $move_class; ?>"><?php _e( 'Move', 'buddyboss' ); ?></a></li>
					<li class="delete_file"><a class="document-file-delete" data-item-preview-attachment-id="<?php echo esc_attr( bp_get_document_preview_attachment_id() ); ?>" data-item-attachment-id="<?php echo esc_attr( bp_get_document_attachment_id() ); ?>" data-item-id="<?php echo esc_attr( bp_get_document_id() ); ?>" data-type="<?php echo esc_attr( $type ); ?>" href="#"><?php _e( 'Delete', 'buddyboss' ); ?></a></li>
				</ul>
			</div>
		</div>
	<?php endif; ?>

</div>

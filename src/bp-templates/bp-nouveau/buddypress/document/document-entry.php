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
} else {
	$svg_icon  = bp_document_svg_icon('folder' );
	$link = bp_get_folder_link();
	$move_class = 'ac-folder-move';
	$listing_class = 'ac-folder-list';
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
		</a>
		<input type="text" value="" class="media-folder_name_edit" />
	</div>

	<div class="media-folder_modified">
		<div  class="media-folder_details__bottom">
			<span class="media-folder_date"><?php bp_document_date_created(); ?></span>
			<?php if ( ! bp_is_user() ) { ?>
				<span class="media-folder_author">by <?php bp_document_author(); ?></span>
			<?php } ?>
		</div>
	</div>

	<div class="media-folder_visibility">
		<div  class="media-folder_details__bottom">
			<span>All Members</span>
		</div>
	</div>

	<?php if ( bp_is_my_profile() || ( bp_is_group() && groups_can_user_manage_albums( bp_loggedin_user_id(), bp_get_current_group_id() ) ) ) : ?>
		<div class="media-folder_actions">
			<a href="#" class="media-folder_action__anchor">
				<i class="bb-icon-menu-dots-v"></i>
			</a>
			<div class="media-folder_action__list">
				<ul>
					<li class="rename_file"><a href="#" class="ac-document-rename"><?php _e( 'Rename', 'buddyboss' ); ?></a></li>
					<li class="move_file"><a href="#" class="<?php echo $move_class; ?>"><?php _e( 'Move', 'buddyboss' ); ?></a></li>
					<li class="delete_file"><a href="#"><?php _e( 'Delete', 'buddyboss' ); ?></a></li>
				</ul>
			</div>
		</div>
	<?php endif; ?>

</div>

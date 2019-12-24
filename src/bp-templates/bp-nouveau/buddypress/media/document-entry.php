<?php
/**
 * BuddyBoss - Media Entry
 *
 * @since BuddyBoss 1.0.0
 */

$attachment_id = bp_get_media_attachment_id();
if ( isset( $attachment_id) ) {
	$extension = bp_media_get_document_extension( $attachment_id );
	$svg_icon  = bp_media_get_document_svg_icon( $extension );
	$link = wp_get_attachment_url( $attachment_id );
} else {
	$svg_icon  = bp_media_get_document_svg_icon('folder' );
	$link = bp_get_document_folder_link();
}

?>
<div class="media-folder_items">
	<div class="media-folder_icon">
		<a href="<?php echo esc_url( $link ); ?>"><img src="<?php echo esc_url( $svg_icon ); ?>" /></a>
	</div>
	<div class="media-folder_details">
		<a class="media-folder_name" href="<?php echo esc_url( $link ); ?>"><?php bp_media_name(); ?></a>
			<div  class="media-folder_details__bottom">
				<span class="media-folder_date"><?php bp_media_date_created(); ?></span>
				<?php if ( ! bp_is_user() ) { ?>
					<span class="media-folder_author"><?php bp_media_author(); ?></td></span>
				<?php } ?>
			</div>
	</div>
	<div class="media-folder_actions">
		<a href="#" class="media-folder_action__anchor">
			<i class="bb-icon-menu-dots-v"></i>
		</a>
		<div class="media-folder_action__list">
			<ul>
				<li><a href="#">Rename</a></li>
				<li><a href="#">Delete</a></li>
			</ul>
		</div>
	</div>
</div>

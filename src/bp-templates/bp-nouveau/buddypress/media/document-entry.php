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
<tr>
	<td class="svg-document-icon"><a href="<?php echo esc_url( $link ); ?>"><img src="<?php echo esc_url( $svg_icon ); ?>" /></a></td>
	<td><a href="<?php echo esc_url( $link ); ?>"><?php bp_media_name(); ?></a></td>
	<td><?php bp_media_date_created(); ?></td>
	<?php if ( ! bp_is_user() ) { ?>
	<td><?php bp_media_author(); ?></td>
	<?php } ?>
	<td>:</td>
</tr>

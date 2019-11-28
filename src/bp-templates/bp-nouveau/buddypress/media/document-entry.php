<?php
/**
 * BuddyBoss - Media Entry
 *
 * @since BuddyBoss 1.0.0
 */

$attachment_id = bp_get_media_attachment_id();
$extension     = bp_media_get_document_extension( $attachment_id );
$svg_icon      = bp_media_get_document_svg_icon( $extension );

?>
<tr>
	<td class="svg-document-icon"><img src="<?php echo esc_url( $svg_icon ); ?>" /></td>
	<td><?php bp_media_name(); ?></td>
	<td><?php bp_media_date_created(); ?></td>
	<td><?php bp_media_author(); ?></td>
	<td>:</td>
</tr>

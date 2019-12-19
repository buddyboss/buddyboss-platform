<?php
/**
 * BuddyBoss - Activity Media
 *
 * @since BuddyBoss 1.0.0
 */

global $media_template;

$attachment_id     = bp_get_media_attachment_id();
$extension         = bp_media_get_document_extension( $attachment_id );
$svg_icon          = bp_media_get_document_svg_icon( $extension );
$svg_icon_download = bp_media_get_document_svg_icon( 'download' );
$url               = wp_get_attachment_url( $attachment_id );
$filename          = basename( get_attached_file( $attachment_id ) );
$size              = size_format(filesize( get_attached_file( $attachment_id ) ) );

?>

<div class="bb-activity-media-elem document-activity">

	<div class="document-description-wrap">
		<a href="<?php echo esc_url( $url ); ?>" target="_blank" class="entry-img" data-id="<?php bp_media_id(); ?>" data-activity-id="<?php bp_media_activity_id(); ?>">
			<img style="width: 40px;" width="40" height="40" src="<?php echo esc_url( $svg_icon ); ?>" class="" alt="<?php bp_media_title(); ?>" />
		</a>
		<div class="document-detail-wrap">
			<span class="document-title"><?php echo $filename; ?></span>
			<span class="document-description"><?php echo $size; ?></span>
		</div>
	</div>
	<a href="<?php echo esc_url( $url ); ?>" target="_blank" class="entry-img" data-id="<?php bp_media_id(); ?>" data-activity-id="<?php bp_media_activity_id(); ?>">
		<img style="width: 40px;" width="40" height="40" src="<?php echo esc_url( $svg_icon_download ); ?>" class="" alt="<?php bp_media_title(); ?>" />
	</a>
</div>

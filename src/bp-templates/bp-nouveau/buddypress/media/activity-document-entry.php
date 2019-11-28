<?php
/**
 * BuddyBoss - Activity Media
 *
 * @since BuddyBoss 1.0.0
 */

global $media_template;

$attachment_id = bp_get_media_attachment_id();
$extension     = bp_media_get_document_extension( $attachment_id );
$svg_icon      = bp_media_get_document_svg_icon( $extension );
$url           = wp_get_attachment_url( $attachment_id );

?>

<div class="bb-activity-media-elem">
	<a href="<?php echo esc_url( $url ); ?>"
	   target="_blank"
	   class="entry-img"
	   data-id="<?php bp_media_id(); ?>"
	   data-activity-id="<?php bp_media_activity_id(); ?>">

		<img style="width: 40px;" width="40" height="40" src="<?php echo esc_url( $svg_icon ); ?>" class="" alt="<?php bp_media_title(); ?>" />
	</a>
</div>

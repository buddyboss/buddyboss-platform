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

<div class="bb-activity-media-elem document-activity <?php echo wp_is_mobile() ? 'is-mobile' : ''; ?> ">

	<div class="document-description-wrap">
		<a href="<?php echo esc_url( $url ); ?>" target="_blank" class="entry-img" data-id="<?php bp_media_id(); ?>" data-activity-id="<?php bp_media_activity_id(); ?>">
			<img style="width: 40px;" width="40" height="40" src="<?php echo esc_url( $svg_icon ); ?>" class="" alt="<?php bp_media_title(); ?>" />
		</a>
		<a href="<?php echo esc_url( $url ); ?>" target="_blank" class="document-detail-wrap">
			<span class="document-title"><?php echo $filename; ?></span>
			<span class="document-description"><?php echo $size; ?></span>
			<span class="document-helper-text"><?php esc_html_e( '- Click to Download', 'buddyboss' ); ?></span>
		</a>
	</div>
	<div class="document-action-wrap">
		<a href="<?php echo esc_url( $url ); ?>" target="_blank" class="document-action_download" data-id="<?php bp_media_id(); ?>" data-activity-id="<?php bp_media_activity_id(); ?>" data-balloon-pos="down" data-balloon="Download">
			<i class="bb-icon-download"></i>
		</a>
		<a href="#" target="_blank" class="document-action_more" data-balloon-pos="down" data-balloon="More actions">
			<i class="bb-icon-menu-dots-h"></i>
		</a>
		<div class="document-action_list">
			<ul>
				<li><a href="#">Move</a></li>
				<li><a href="#">Delete</a></li>
			</ul>
		</div>
		
	</div>
	
</div>

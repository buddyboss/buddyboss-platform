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


<?php if ( 'css' !== $extension && 'txt' !== $extension ) { ?>
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

	<?php if ( 'mp3' === $extension || 'wav' === $extension || 'ogg' === $extension ) { ?>
		<div class="document-audio-wrap">
			<audio controls>
				<source src="<?php echo esc_url( $url ); ?>" type="audio/mpeg">
				Your browser does not support the audio element.
			</audio>
		</div>
	<?php } ?>
</div> <!-- .bb-activity-media-elem -->
<?php }else{ ?>
<p class="document-filename"><?php echo $filename; ?></strong></p>
<div class="bb-activity-media-elem document-activity <?php echo wp_is_mobile() ? 'is-mobile' : ''; ?> ">
	<div class="document-text-wrap">
		
		<div class="document-text" data-extension="<?php echo $extension; ?>">
			<div class="document-text-file-data-hidden" style="display: none;"><?php
					readfile($url, 'r');
				 ?>
			</div>
		</div>
		<div class="document-action-wrap">
			<a href="#" class="document-action_collapse" data-balloon-pos="down" data-balloon="Collapse"><i class="bb-icon-arrow-up document-icon-collapse"></i></a>
			<a href="<?php echo esc_url( $url ); ?>" target="_blank" class="document-action_download" data-id="<?php bp_media_id(); ?>" data-activity-id="<?php bp_media_activity_id(); ?>" data-balloon-pos="down" data-balloon="Download">
				<i class="bb-icon-download document-icon-download"></i>
			</a>

			<a href="#" target="_blank" class="document-action_more" data-balloon-pos="down" data-balloon="More actions">
				<i class="bb-icon-menu-dots-h document-icon-download-more"></i>
			</a>
			<div class="document-action_list">
				<ul>
					<li><a href="#">Move</a></li>
					<li><a href="#">Delete</a></li>
				</ul>
			</div>
		</div>

		<div class="document-expand">
			<a href="#" class="document-expand-anchor"><i class="bb-icon-plus document-icon-plus"></i> Click to expand</a>
		</div>

	</div> <!-- .document-text-wrap -->
</div> <!-- .bb-activity-media-elem -->
<?php } ?>

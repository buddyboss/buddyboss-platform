<?php
/**
 * BuddyBoss - Activity Media
 *
 * @since BuddyBoss 1.0.0
 */
?>

<?php
global $media_template;

$width  =  isset( $media_template->media->attachment_data->meta['width'] ) ? $media_template->media->attachment_data->meta['width'] : 0;
$height =  isset( $media_template->media->attachment_data->meta['height'] ) ? $media_template->media->attachment_data->meta['height'] : 0;

?>

<div class="bb-activity-media-elem
<?php
echo $media_template->current_media > 4 ? 'hide' : '';
echo 1 === $media_template->media_count || $media_template->media_count > 1 && 0 === $media_template->current_media ? 'act-grid-1-1 ' : '';
echo $media_template->media_count > 1 && $media_template->current_media > 0 ? 'act-grid-1-2 ' : '';
echo $width > $height ? 'bb-horizontal-layout' : '';
echo $height > $width || $width === $height ? 'bb-vertical-layout' : '';
?>
">
	<a href="#"
	class="bb-open-media-theatre entry-img"
	data-id="<?php bp_media_id(); ?>"
	data-attachment-full="<?php bp_media_attachment_image(); ?>"
	data-activity-id="<?php bp_media_activity_id(); ?>">

		<img src="<?php echo buddypress()->plugin_url; ?>bp-templates/bp-nouveau/images/placeholder.png" data-src="<?php bp_media_attachment_image_activity_thumbnail(); ?>" class="no-round photo lazy" alt="<?php bp_media_title(); ?>" />

		<?php
		if ( $media_template->media_count > 5 && 4 === $media_template->current_media ) {
			?>
			<span class="bb-photos-length"><span><strong>+<?php echo $media_template->media_count - 5; ?></strong> <span><?php _e( 'More Photos', 'buddyboss' ); ?></span></span></span>
			<?php
		}
		?>
	</a>
</div>

<?php
/**
 * The template for activity document entry
 *
 * This template can be overridden by copying it to yourtheme/buddypress/document/activity-entry.php.
 *
 * @since   BuddyBoss 1.4.0
 * @package BuddyBoss\Core
 * @version 1.4.0
 */

$attachment_id     = bp_get_document_attachment_id();
$extension         = bp_get_document_extension();
$svg_icon          = bp_document_svg_icon( $extension, $attachment_id );
$svg_icon_download = bp_document_svg_icon( 'download' );
$url               = wp_get_attachment_url( $attachment_id );
$filename          = basename( get_attached_file( $attachment_id ) );
$size              = is_file( get_attached_file( $attachment_id ) ) ? bp_document_size_format( filesize( get_attached_file( $attachment_id ) ) ) : 0;
$download_url      = bp_document_download_link( $attachment_id, bp_get_document_id() );
$document_privacy  = bb_media_user_can_access( bp_get_document_id(), 'document' );
$can_download_btn  = true === (bool) $document_privacy['can_download'];
$can_edit_btn      = true === (bool) $document_privacy['can_edit'];
$can_view          = true === (bool) $document_privacy['can_view'];
$can_add           = true === (bool) $document_privacy['can_add'];
$can_move          = true === (bool) $document_privacy['can_move'];
$can_delete        = true === (bool) $document_privacy['can_delete'];
$db_privacy        = bp_get_db_document_privacy();
$extension_lists   = bp_document_extensions_list();
$attachment_url    = '';
$mirror_text       = '';

if ( $attachment_id ) {
	$text_attachment_url = wp_get_attachment_url( $attachment_id );
	$mirror_text         = bp_document_mirror_text( $attachment_id );
}

$class_theatre = apply_filters( 'bp_document_activity_theater_class', 'bb-open-document-theatre' );
$class_popup   = apply_filters( 'bp_document_activity_theater_description_class', 'document-detail-wrap-description-popup' );
$click_text    = apply_filters( 'bp_document_activity_click_to_view_text', __( ' view', 'buddyboss' ) );
$video_url     = bb_document_video_get_symlink( bp_get_document_id() );
?>

<div class="bb-activity-media-elem document-activity <?php bp_document_id(); ?> <?php echo wp_is_mobile() ? 'is-mobile' : ''; ?>" data-id="<?php bp_document_id(); ?>" data-parent-id="<?php bp_document_parent_id(); ?>" >
	<?php bp_get_template_part( 'document/activity-document-preview' ); ?> <!-- .bb-code-extension-files-preview. -->
	<div class="document-description-wrap">
		<a
				href="<?php echo esc_url( $download_url ); ?>"
				class="entry-img <?php echo esc_attr( $class_theatre ); ?>"
				data-id="<?php bp_document_id(); ?>"
				data-attachment-full=""
				data-attachment-id="<?php echo esc_attr( $attachment_id ); ?>"
				data-privacy="<?php bp_db_document_privacy(); ?>"
				data-extension="<?php echo $extension ? esc_attr( $extension ) : ''; ?>"
				data-parent-activity-id="<?php bp_document_parent_activity_id(); ?>"
				data-activity-id="<?php bp_document_activity_id(); ?>"
				data-author="<?php bp_document_user_id(); ?>"
				data-preview="<?php bp_document_attachment_url(); ?>"
				data-full-preview="<?php bp_document_attachment_url(); ?>"
				data-text-preview="<?php bp_document_attachment_url(); ?>"
				data-video-preview="<?php echo $video_url ? esc_url( $video_url ) : ''; ?>"
				data-mp3-preview="<?php bp_document_attachment_url(); ?>"
				data-album-id="<?php bp_document_folder_id(); ?>"
				data-group-id="<?php bp_document_group_id(); ?>"
				data-document-title="<?php echo esc_html( $filename ); ?>"
				data-mirror-text="<?php echo esc_html( $mirror_text ); ?>"
				data-can-edit="<?php echo esc_attr( bp_document_user_can_edit( bp_get_document_id() ) ); ?>"
				data-icon-class="<?php echo esc_attr( $svg_icon ); ?>">
			<i class="<?php echo esc_attr( $svg_icon ); ?>" ></i>
		</a>
		<a
				href="<?php echo esc_url( $download_url ); ?>"
				class="document-detail-wrap <?php echo esc_attr( $class_popup ); ?>"
				data-id="<?php bp_document_id(); ?>"
				data-attachment-id="<?php echo esc_attr( $attachment_id ); ?>"
				data-attachment-full=""
				data-privacy="<?php bp_db_document_privacy(); ?>"
				data-extension="<?php echo $extension ? esc_attr( $extension ) : ''; ?>"
				data-parent-activity-id="<?php bp_document_parent_activity_id(); ?>"
				data-activity-id="<?php bp_document_activity_id(); ?>"
				data-author="<?php bp_document_user_id(); ?>"
				data-preview="<?php bp_document_attachment_url(); ?>"
				data-full-preview="<?php bp_document_attachment_url(); ?>"
				data-text-preview="<?php bp_document_attachment_url(); ?>"
				data-mp3-preview="<?php bp_document_attachment_url(); ?>"
				data-video-preview="<?php echo $video_url ? esc_url( $video_url ) : ''; ?>"
				data-album-id="<?php bp_document_folder_id(); ?>"
				data-group-id="<?php bp_document_group_id(); ?>"
				data-document-title="<?php echo esc_html( $filename ); ?>"
				data-mirror-text="<?php echo esc_html( $mirror_text ); ?>"
				data-can-edit="<?php echo esc_attr( bp_document_user_can_edit( bp_get_document_id() ) ); ?>"
				data-icon-class="<?php echo esc_attr( $svg_icon ); ?>">
			<span class="document-title"><?php echo esc_html( $filename ); ?></span>
			<span class="document-description"><?php echo esc_html( $size ); ?></span>
			<span class="document-extension-description"><?php echo esc_html( bp_document_get_extension_description( bp_get_document_extension() ) ); ?></span>
			<span class="document-helper-text"> <span> - </span><span class="document-helper-text-click"><?php esc_html_e( 'Click to', 'buddyboss' ); ?></span><span class="document-helper-text-inner"><?php echo esc_html( $click_text ); ?></span></span>
		</a>
	</div>

	<?php bp_get_template_part( 'document/activity-document-actions' ); ?> <!-- .bb-activity-document-actions. -->

	<?php
		// Code extension files preview.
		bp_get_template_part( 'document/code-preview' );
	?>

</div> <!-- .bb-activity-media-elem -->

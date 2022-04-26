<?php
/**
 * Template for displaying the search results of the document
 *
 * This template can be overridden by copying it to yourtheme/buddypress/search/loop/document.php.
 *
 * @package BuddyBoss\Core
 * @since   BuddyBoss 1.0.0
 * @version 1.0.0
 */

$listing_class       = '';
$attachment_id       = bp_get_document_attachment_id();
$extension           = '';
$attachment_url      = '';
$text_attachment_url = '';
$document_id         = bp_get_document_id();
$filename            = basename( get_attached_file( $attachment_id ) );
$svg_icon            = '';
$document_type       = '';
$download_link       = '';
$document_title      = '';
$mirror_text         = '';
if ( $attachment_id ) {
	$extension           = bp_document_extension( $attachment_id );
	$svg_icon            = bp_document_svg_icon( $extension, $attachment_id );
	$download_link       = bp_document_download_link( $attachment_id, $document_id );
	$text_attachment_url = wp_get_attachment_url( $attachment_id );
	$listing_class       = 'ac-document-list';
	$document_type       = 'document';
	$document_title      = bp_get_document_title();
	$attachment_url      = bp_document_get_preview_url( bp_get_document_id(), bp_get_document_attachment_id(), 'bb-document-pdf-preview-activity-image' );
	$mirror_text         = bp_document_mirror_text( $attachment_id );

}
$document_id = bp_get_document_id();


$class = ''; // used.
if ( $attachment_id && bp_get_document_activity_id() ) {
	$class = ''; // used.
}
$link = bp_get_document_link( bp_get_document_id() );

?>

<li data-bp-item-id="<?php bp_document_id(); ?>" data-bp-item-component="document" class="search-document-list">
	<div class="list-wrap">
		<div class="item">
			<div class="media-folder_items <?php echo esc_attr( $listing_class ); ?>" data-activity-id="<?php bp_document_activity_id(); ?>" data-id="<?php bp_document_id(); ?>" data-parent-id="<?php bp_document_parent_id(); ?>" id="div-listing-<?php bp_document_id(); ?>">
				<div class="media-folder_icon">
					<a href="<?php echo esc_url( $link ); ?>"> <i class="<?php echo esc_attr( $svg_icon ); ?>"></i> </a>
				</div>
				<div class="media-folder_details">
					<a class="media-folder_name <?php echo esc_attr( $class ); ?>" href="<?php echo esc_url( $link ); ?>" data-id="<?php bp_document_id(); ?>" data-attachment-full="" data-privacy="<?php bp_db_document_privacy(); ?>" data-extension="<?php echo $extension ? esc_attr( $extension ) : ''; ?>" data-parent-activity-id="<?php bp_document_parent_activity_id(); ?>" data-activity-id="<?php bp_document_activity_id(); ?>" data-full-preview="<?php echo $attachment_url ? esc_url( $attachment_url ) : ''; ?>" data-preview="<?php echo $attachment_url ? esc_url( $attachment_url ) : ''; ?>" data-text-preview="<?php echo $text_attachment_url ? esc_url( $text_attachment_url ) : ''; ?>" data-album-id="<?php bp_document_folder_id(); ?>" data-group-id="<?php bp_document_group_id(); ?>" data-document-title="<?php echo esc_html( $filename ); ?>" data-mirror-text="<?php echo esc_html( $mirror_text ); ?>" data-icon-class="<?php echo esc_attr( $svg_icon ); ?>">
						<span><?php echo esc_html( $document_title ); ?></span><?php echo $extension ? '.' . esc_html( $extension ) : ''; ?>
						<i class="media-document-id" data-item-id="<?php echo esc_attr( bp_get_document_id() ); ?>" style="display: none;"></i>
						<i class="media-document-attachment-id" data-item-id="<?php echo esc_attr( bp_get_document_attachment_id() ); ?>" style="display: none;"></i>
						<i class="media-document-type" data-item-id="<?php echo esc_attr( $document_type ); ?>" style="display: none;"></i>
					</a>
				</div>
				<div class="media-folder_modified">
					<div class="media-folder_details__bottom">
						<?php
						if ( ! bp_is_user() ) {
							?>
							<span class="media-folder_author"><?php esc_html_e( 'By ', 'buddyboss' ); ?><a href="<?php echo esc_url( trailingslashit( bp_core_get_user_domain( bp_get_document_user_id() ) . bp_get_document_slug() ) ); ?>"><?php bp_document_author(); ?></a></span>
							<?php
						}
						?>
						<span class="middot">&middot;</span>
						<span class="media-folder_date"><?php bp_document_date(); ?></span>
					</div>
				</div>

				<div class="media-folder_visibility">
					<div class="media-folder_details__bottom">
						<?php
						if ( bp_is_active( 'groups' ) ) {
							$group_id = bp_get_document_group_id();
							if ( $group_id > 0 ) {
								?>
								<span class="middot">&middot;</span>
								<span class="bp-tooltip" data-bp-tooltip-pos="down" data-bp-tooltip="<?php esc_attr_e( 'Based on group privacy', 'buddyboss' ); ?>">
									<?php bp_document_privacy(); ?>
								</span>
								<?php
							} else {
								?>
								<span class="middot">&middot;</span>
								<span id="privacy-<?php echo esc_attr( bp_get_document_id() ); ?>">
									<?php bp_document_privacy(); ?>
								</span>
								<?php
							}
						} else {
							?>
							<span class="middot">&middot;</span>
							<span>
								<?php bp_document_privacy(); ?>
							</span>
							<?php
						}
						?>
					</div>
				</div>
			</div>
		</div>
	</div>
</li>

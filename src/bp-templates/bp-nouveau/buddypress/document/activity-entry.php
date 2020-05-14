<?php
/**
 * BuddyBoss - Activity Document
 *
 * @since BuddyBoss 1.0.0
 * @package BuddyBoss\Core
 */

global $document_template;

$attachment_id     = bp_get_document_attachment_id();
$extension         = bp_document_extension( $attachment_id );
$svg_icon          = bp_document_svg_icon( $extension );
$svg_icon_download = bp_document_svg_icon( 'download' );
$url               = wp_get_attachment_url( $attachment_id );
$filename          = basename( get_attached_file( $attachment_id ) );
$size              = is_file( get_attached_file( $attachment_id ) ) ? size_format( filesize( get_attached_file( $attachment_id ) ) ) : 0;
$download_url      = bp_document_download_link( $attachment_id, bp_get_document_id() );
$document_privacy  = bp_document_user_can_manage_document( bp_get_document_id(), bp_loggedin_user_id() );
$can_download_btn  = ( true === (bool) $document_privacy['can_download'] ) ? true : false;
$can_manage_btn    = ( true === (bool) $document_privacy['can_manage'] ) ? true : false;
$can_view          = ( true === (bool) $document_privacy['can_view'] ) ? true : false;
$db_privacy        = bp_get_db_document_privacy();
$extension_lists   = bp_document_extensions_list();

$group_id = bp_get_document_group_id();
if ( $group_id > 0 ) {
	$move_id   = $group_id;
	$move_type = 'group';
} else {
	$move_id   = bp_get_document_user_id();
	$move_type = 'profile';
}

$extension_description = '';

if ( ! empty( $extension_lists ) ) {
	$extension_lists = array_column( $extension_lists, 'description', 'extension' );
	$extension_name  = '.' . $extension;
	if ( ! empty( $extension_lists ) && ! empty( $extension ) && array_key_exists( $extension_name, $extension_lists ) ) {
		$extension_description = '<span class="document-extension-description">' . esc_html( $extension_lists[ $extension_name ] ) . '</span>';
	}
}
?>

<div class="bb-activity-media-elem document-activity <?php bp_document_id(); ?> <?php echo wp_is_mobile() ? 'is-mobile' : ''; ?>" data-id="<?php bp_document_id(); ?>" data-parent-id="<?php bp_document_parent_id(); ?>" >
	<div class="document-description-wrap">
		<a href="<?php echo esc_url( $download_url ); ?>" class="entry-img" data-id="<?php bp_document_id(); ?>" data-activity-id="<?php bp_document_activity_id(); ?>">
			<i class="<?php echo esc_attr( $svg_icon ); ?>" ></i>
		</a>
		<a href="<?php echo esc_url( $download_url ); ?>" class="document-detail-wrap">
			<span class="document-title"><?php echo esc_html( $filename ); ?></span>
			<span class="document-description"><?php echo esc_html( $size ); ?></span>
			<?php echo $extension_description; ?>
			<span class="document-helper-text">&ndash; <?php esc_html_e( 'Click to Download', 'buddyboss' ); ?></span>
		</a>
	</div>
	<div class="document-action-wrap">
		<a href="<?php echo esc_url( $download_url ); ?>" class="document-action_download" data-id="<?php bp_document_id(); ?>" data-activity-id="<?php bp_document_activity_id(); ?>" data-balloon-pos="up" data-balloon="<?php esc_html_e( 'Download', 'buddyboss' ); ?>">
			<i class="bb-icon-download"></i>
		</a>
		
			<a href="#" target="_blank" class="document-action_more" data-balloon-pos="up" data-balloon="<?php esc_html_e( 'More actions', 'buddyboss' ); ?>">
				<i class="bb-icon-menu-dots-v"></i>
			</a>
			<div class="document-action_list">
				<ul>
					<li class="copy_download_file_url">
						<a href="<?php echo esc_url( $download_url ); ?>"><?php esc_html_e( 'Copy Download Link', 'buddyboss' ); ?></a>
					</li>
				<?php
				if ( bp_loggedin_user_id() === bp_get_document_user_id() ) {
					
					if ( ! in_array( $db_privacy, array( 'forums', 'message' ), true ) ) {
						?>
					<li class="move_file"><a href="#" data-type="<?php echo esc_attr( $move_type ); ?>" id="<?php echo esc_attr( $move_id ); ?>" class="ac-document-move"><?php esc_html_e( 'Move', 'buddyboss' ); ?></a></li>
						<?php
					}
					?>
					<li class="delete_file"><a class="document-file-delete" data-item-activity-id="<?php bp_activity_id(); ?>" data-item-from="activity" data-item-preview-attachment-id="<?php echo esc_attr( bp_get_document_preview_attachment_id() ); ?>" data-item-attachment-id="<?php echo esc_attr( bp_get_document_attachment_id() ); ?>" data-item-id="<?php echo esc_attr( bp_get_document_id() ); ?>" data-type="<?php echo esc_attr( 'document' ); ?>" href="#"><?php esc_html_e( 'Delete', 'buddyboss' ); ?></a></li>
					<?php
				}
				?>
				</ul>
			</div>
	</div>
	<?php
	if ( 'mp3' === $extension || 'wav' === $extension || 'ogg' === $extension ) {
		?>
		<div class="document-audio-wrap">
			<audio controls>
				<source src="<?php echo esc_url( $url ); ?>" type="audio/mpeg">
				<?php esc_html_e( 'Your browser does not support the audio element.', 'buddyboss' ); ?>
			</audio>
		</div>
		<?php
	}
	if ( 'pdf' === $extension || 'pptx' === $extension || 'pps' === $extension || 'xls' === $extension || 'xlsx' === $extension || 'pps' === $extension || 'ppt' === $extension || 'pptx' === $extension || 'doc' === $extension || 'docx' === $extension || 'dot' === $extension || 'rtf' === $extension || 'wps' === $extension || 'wpt' === $extension || 'dotx' === $extension || 'potx' === $extension || 'xlsm' === $extension ) {
		$attachment_url = wp_get_attachment_url( bp_get_document_preview_attachment_id() );
		if ( $attachment_url ) {
			?>
			<div class="document-preview-wrap">
				<img src="<?php echo esc_url( $attachment_url ); ?>" alt="" />
			</div><!-- .document-preview-wrap -->
			<?php
		}
	}
	$sizes = is_file( get_attached_file( $attachment_id ) ) ? get_attached_file( $attachment_id ) : 0;
	if ( $sizes && filesize( $sizes ) / 1e+6 < 2 ) {
		if ( 'css' === $extension || 'txt' === $extension || 'html' === $extension || 'htm' === $extension || 'js' === $extension || 'csv' === $extension ) {
			$data      = bp_document_get_preview_text_from_attachment( $attachment_id );
			$file_data = $data['text'];
			$more_text = $data['more_text']
			?>
			<div class="document-text-wrap">
				<div class="document-text" data-extension="<?php echo esc_attr( $extension ); ?>">
					<textarea class="document-text-file-data-hidden" style="display: none;"><?php echo wp_kses_post( $file_data ); ?></textarea>
				</div>
				<div class="document-expand">
					<a href="#" class="document-expand-anchor"><i class="bb-icon-plus document-icon-plus"></i> <?php esc_html_e( 'Click to expand', 'buddyboss' ); ?></a>
				</div>
			</div> <!-- .document-text-wrap -->
			<?php
			if ( true === $more_text ) {

				printf(
					/* translators: %s: download string */
					'<div class="more_text_view">%s</div>',
					sprintf(
						/* translators: %s: download url */
						wp_kses_post( 'This file was truncated for preview. Please <a href="%s">download</a> to view the full file.', 'buddyboss' ),
						esc_url( $download_url )
					)
				);
			}
		}
	}
	?>
</div> <!-- .bb-activity-media-elem -->

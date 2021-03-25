<?php
/**
 * BuddyBoss - Activity Document
 *
 * @since BuddyBoss 1.4.0
 * @package BuddyBoss\Core
 */

global $document_template;

$attachment_id     = bp_get_document_attachment_id();
$extension         = bp_document_extension( $attachment_id );
$svg_icon          = bp_document_svg_icon( $extension, $attachment_id );
$svg_icon_download = bp_document_svg_icon( 'download' );
$url               = wp_get_attachment_url( $attachment_id );
$filename          = basename( get_attached_file( $attachment_id ) );
$size              = is_file( get_attached_file( $attachment_id ) ) ? bp_document_size_format( filesize( get_attached_file( $attachment_id ) ) ) : 0;
$download_url      = bp_document_download_link( $attachment_id, bp_get_document_id() );
$document_privacy  = bp_document_user_can_manage_document( bp_get_document_id(), bp_loggedin_user_id() );
$can_download_btn  = ( true === (bool) $document_privacy['can_download'] ) ? true : false;
$can_manage_btn    = ( true === (bool) $document_privacy['can_manage'] ) ? true : false;
$can_view          = ( true === (bool) $document_privacy['can_view'] ) ? true : false;
$can_add           = ( true === (bool) $document_privacy['can_add'] ) ? true : false;
$db_privacy        = bp_get_db_document_privacy();
$extension_lists   = bp_document_extensions_list();
$attachment_url    = '';
$mirror_text       = '';
$is_comment_doc    = bp_document_is_activity_comment_document( $document_template->document );

if ( $attachment_id ) {
	$text_attachment_url = wp_get_attachment_url( $attachment_id );
	$mirror_text         = bp_document_mirror_text( $attachment_id );
}

$group_id = bp_get_document_group_id();
if ( $group_id > 0 ) {
	$move_id   = $group_id;
	$move_type = 'group';
} else {
	$move_id   = bp_get_document_user_id();
	$move_type = 'profile';
}

$extension_description = '';
$attachment_url        = bp_document_get_preview_image_url( bp_get_document_id(), $extension, bp_get_document_preview_attachment_id() );

if ( ! empty( $extension_lists ) ) {
	$extension_lists = array_column( $extension_lists, 'description', 'extension' );
	$extension_name  = '.' . $extension;
	if ( ! empty( $extension_lists ) && ! empty( $extension ) && array_key_exists( $extension_name, $extension_lists ) ) {
		$extension_description = '<span class="document-extension-description">' . esc_html( $extension_lists[ $extension_name ] ) . '</span>';
	}
}

$class_theatre             = apply_filters( 'bp_document_activity_theater_class', 'bb-open-document-theatre' );
$class_popup               = apply_filters( 'bp_document_activity_theater_description_class', 'document-detail-wrap-description-popup' );
$click_text                = apply_filters( 'bp_document_activity_click_to_view_text', __( ' view', 'buddyboss' ) );
$bp_document_music_preview = apply_filters( 'bp_document_music_preview', true );
$bp_document_text_preview  = apply_filters( 'bp_document_text_preview', true );
$bp_document_image_preview = apply_filters( 'bp_document_image_preview', true );

$audio_url = '';
if ( in_array( $extension, bp_get_document_preview_music_extensions(), true ) && $bp_document_music_preview ) {
	$audio_url = bp_document_get_preview_audio_url( bp_get_document_id(), $extension, $attachment_id );
}
?>

<div class="bb-activity-media-elem document-activity <?php bp_document_id(); ?> <?php echo wp_is_mobile() ? 'is-mobile' : ''; ?>" data-id="<?php bp_document_id(); ?>" data-parent-id="<?php bp_document_parent_id(); ?>" >
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
				data-preview="<?php echo $attachment_url ? esc_url( $attachment_url ) : ''; ?>"
				data-text-preview="<?php echo $text_attachment_url ? esc_url( $text_attachment_url ) : ''; ?>"
				data-mp3-preview="<?php echo $audio_url ? esc_url( $audio_url ) : ''; ?>"
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
				data-preview="<?php echo $attachment_url ? esc_url( $attachment_url ) : ''; ?>"
				data-text-preview="<?php echo $text_attachment_url ? esc_url( $text_attachment_url ) : ''; ?>"
				data-mp3-preview="<?php echo $audio_url ? esc_url( $audio_url ) : ''; ?>"
				data-album-id="<?php bp_document_folder_id(); ?>"
				data-group-id="<?php bp_document_group_id(); ?>"
				data-document-title="<?php echo esc_html( $filename ); ?>"
				data-mirror-text="<?php echo esc_html( $mirror_text ); ?>"
                data-can-edit="<?php echo esc_attr( bp_document_user_can_edit( bp_get_document_id() ) ); ?>"
				data-icon-class="<?php echo esc_attr( $svg_icon ); ?>">
			<span class="document-title"><?php echo esc_html( $filename ); ?></span>
			<span class="document-description"><?php echo esc_html( $size ); ?></span>
			<?php echo $extension_description; ?>
			<span class="document-helper-text"> <span> — </span><span class="document-helper-text-click"><?php echo __( 'Click to', 'buddyboss' ); ?></span><span class="document-helper-text-inner"><?php echo _e( $click_text, 'buddyboss' ); ?></span></span>
		</a>
	</div>
	<div class="document-action-wrap">
		<a href="#" class="document-action_collapse" data-balloon-pos="down" data-balloon="<?php esc_html_e( 'Collapse', 'buddyboss' ); ?>"><i class="bb-icon-arrow-up document-icon-collapse"></i></a>
		<a href="<?php echo esc_url( $download_url ); ?>" class="document-action_download" data-id="<?php bp_document_id(); ?>" data-activity-id="<?php bp_document_activity_id(); ?>" data-balloon-pos="up" data-balloon="<?php esc_html_e( 'Download', 'buddyboss' ); ?>">
			<i class="bb-icon-download"></i>
		</a>

			<a href="#" target="_blank" class="document-action_more" data-balloon-pos="up" data-balloon="<?php esc_html_e( 'More actions', 'buddyboss' ); ?>">
				<i class="bb-icon-menu-dots-v"></i>
			</a>
			<div class="document-action_list">
				<ul class="conflict-activity-ul-li-comment">
					<?php
					if ( $can_download_btn ) {
						?>
						<li class="copy_download_file_url document-action-class">
							<a href="<?php echo esc_url( $download_url ); ?>"><?php esc_html_e( 'Copy Download Link', 'buddyboss' ); ?></a>
						</li>
						<?php
					}
					if ( bp_loggedin_user_id() === bp_get_document_user_id() || bp_current_user_can( 'bp_moderate' ) ) {
						if ( ! in_array( $db_privacy, array( 'forums', 'message' ), true ) ) {
							if ( $is_comment_doc ) {
								?>
								<li class="move_file document-action-class move-disabled" data-balloon-pos="down" data-balloon="<?php esc_html_e( 'Documents added in comment cannot be moved', 'buddyboss' ); ?>">
									<a href="#"><?php esc_html_e( 'Move', 'buddyboss' ); ?></a>
								</li>
								<?php
							} else {
								if ( $can_add ) {
									?>
									<li class="move_file document-action-class">
										<a href="#" data-action="document" data-type="<?php echo esc_attr( $move_type ); ?>" id="<?php echo esc_attr( $move_id ); ?>" class="ac-document-move"><?php esc_html_e( 'Move', 'buddyboss' ); ?></a>
									</li>
									<?php
								}
							}
						}
						$item_id = 0;
						if ( bp_is_active( 'activity' ) && bp_get_activity_comment_id() ) {
							$item_id = bp_get_activity_comment_id();
						} else {
							if ( bp_is_active( 'activity' ) ) {
								$item_id = bp_get_activity_id();
							}
						}
						?>
						<li class="delete_file document-action-class"><a class="document-file-delete" data-item-activity-id="<?php echo esc_attr( $item_id ); ?>" data-item-from="activity" data-item-preview-attachment-id="<?php echo esc_attr( bp_get_document_preview_attachment_id() ); ?>" data-item-attachment-id="<?php echo esc_attr( bp_get_document_attachment_id() ); ?>" data-item-id="<?php echo esc_attr( bp_get_document_id() ); ?>" data-type="<?php echo esc_attr( 'document' ); ?>" href="#"><?php esc_html_e( 'Delete', 'buddyboss' ); ?></a></li>
						<?php
					}
					?>
				</ul>
			</div>
	</div>
	<?php
	if ( in_array( $extension, bp_get_document_preview_music_extensions(), true ) && $bp_document_music_preview ) {
		?>
		<div class="document-audio-wrap">
			<audio controls controlsList="nodownload">
				<source src="<?php echo esc_url( $audio_url ); ?>" type="audio/mpeg">
				<?php esc_html_e( 'Your browser does not support the audio element.', 'buddyboss' ); ?>
			</audio>
		</div>
		<?php
	}

	$attachment_url = bp_document_get_preview_image_url( bp_get_document_id(), $extension, bp_get_document_preview_attachment_id() );
	if ( $attachment_url && $bp_document_image_preview ) {
		?>
		<div class="document-preview-wrap">
			<img src="<?php echo esc_url( $attachment_url ); ?>" alt="" />
		</div><!-- .document-preview-wrap -->
		<?php
	}
	$sizes = is_file( get_attached_file( $attachment_id ) ) ? get_attached_file( $attachment_id ) : 0;
	if ( $sizes && filesize( $sizes ) / 1e+6 < 2 && $bp_document_text_preview ) {
		if ( in_array( $extension, bp_get_document_preview_code_extensions(), true ) ) {
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

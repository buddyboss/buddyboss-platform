<?php
/**
 * The template for document entry
 *
 * This template can be overridden by copying it to yourtheme/buddypress/document/document-entry.php.
 *
 * @since   BuddyBoss 1.4.0
 * @package BuddyBoss\Core
 * @version 1.4.0
 */

global $document_template;

$attachment_id       = bp_get_document_attachment_id();
$extension           = '';
$can_download        = false;
$can_view            = false;
$attachment_url      = '';
$text_attachment_url = '';
$move_id             = '';
$move_type           = '';
$folder_link         = '';
$document_id         = bp_get_document_id();
$filename            = basename( get_attached_file( $attachment_id ) );
$mirror_text         = '';
$audio_url           = '';
$video_url           = '';
$can_add             = false;
$can_move            = false;
$can_edit            = false;
$data_action         = '';
$is_comment_doc      = false;

if ( $attachment_id ) {
	$extension           = bp_document_extension( $attachment_id );
	$svg_icon            = bp_document_svg_icon( $extension, $attachment_id );
	$download_link       = bp_document_download_link( $attachment_id, $document_id );
	$text_attachment_url = wp_get_attachment_url( $attachment_id );
	$move_class          = 'ac-document-move';
	$listing_class       = 'ac-document-list';
	$document_type       = 'document';
	$document_privacy    = bb_media_user_can_access( bp_get_document_id(), 'document' );
	$can_download        = true === (bool) $document_privacy['can_download'];
	$can_edit            = true === (bool) $document_privacy['can_edit'];
	$can_view            = true === (bool) $document_privacy['can_view'];
	$can_add             = true === (bool) $document_privacy['can_add'];
	$can_delete          = true === (bool) $document_privacy['can_delete'];
	$can_move            = true === (bool) $document_privacy['can_move'];
	$group_id            = bp_get_document_group_id();
	$document_title      = bp_get_document_title();
	$data_action         = 'document';
	$mirror_text         = bp_document_mirror_text( $attachment_id );
	$is_comment_doc      = bp_document_is_activity_comment_document( $document_template->document );

	if ( $group_id > 0 ) {
		$move_id   = $group_id;
		$move_type = 'group';
	} else {
		$move_id   = bp_get_document_user_id();
		$move_type = 'profile';
	}

	$attachment_url = bp_document_get_preview_url( bp_get_document_id(), bp_get_document_attachment_id(), 'bb-document-image-preview-activity-image' );
	$video_url      = bb_document_video_get_symlink( bp_get_document_id() );

} else {
	$svg_icon       = bp_document_svg_icon( 'folder' );
	$download_link  = bp_document_folder_download_link( bp_get_document_folder_id() );
	$folder_link    = bp_get_folder_link();
	$move_class     = 'ac-folder-move';
	$listing_class  = 'ac-folder-list';
	$document_type  = 'folder';
	$folder_privacy = bb_media_user_can_access( bp_get_document_folder_id(), 'folder' );
	$can_edit       = true === (bool) $folder_privacy['can_edit'];
	$can_view       = true === (bool) $folder_privacy['can_view'];
	$can_download   = true === (bool) $folder_privacy['can_download'];
	$can_add        = true === (bool) $folder_privacy['can_add'];
	$can_delete     = true === (bool) $folder_privacy['can_delete'];
	$can_move       = true === (bool) $folder_privacy['can_move'];
	$group_id       = bp_get_document_folder_group_id();
	$document_title = bp_get_folder_title();
	$data_action    = 'folder';
	if ( $group_id > 0 ) {
		$move_id   = $group_id;
		$move_type = 'group';
	} else {
		$move_id   = bp_get_document_user_id();
		$move_type = 'profile';
	}
}

$document_id          = bp_get_document_id();
$document_folder_link = ( $attachment_id ) ? $download_link : $folder_link;
$class                = '';

if ( $attachment_id ) {
	$class = 'bb-open-document-theatre';
}

?>
<div class="media-folder_items <?php echo esc_attr( $listing_class ); ?>" data-author="<?php bp_document_user_id(); ?>"
data-group-id="<?php bp_document_group_id(); ?>" data-activity-id="<?php bp_document_activity_id(); ?>"
data-id="<?php bp_document_id(); ?>" data-parent-id="<?php bp_document_parent_id(); ?>"
id="div-listing-<?php bp_document_id(); ?>">
	<div class="media-folder_icon">
		<a href="<?php echo esc_url( $document_folder_link ); ?>"> <i class="<?php echo esc_attr( $svg_icon ); ?>"></i> </a>
	</div>
	<div class="media-folder_details">
		<a class="media-folder_name <?php echo esc_attr( $class ); ?>" href="<?php echo esc_url( $document_folder_link ); ?>"
		data-id="<?php bp_document_id(); ?>" data-attachment-full=""
		data-attachment-id="<?php echo esc_attr( $attachment_id ); ?>"
		data-privacy="<?php bp_db_document_privacy(); ?>"
		data-extension="<?php echo $extension ? esc_attr( $extension ) : ''; ?>"
		data-parent-activity-id="<?php bp_document_parent_activity_id(); ?>"
		data-activity-id="<?php bp_document_activity_id(); ?>" data-author="<?php bp_document_user_id(); ?>"
		data-preview="<?php bp_document_attachment_url(); ?>"
		data-full-preview="<?php bp_document_attachment_url(); ?>"
		data-text-preview="<?php bp_document_attachment_url(); ?>"
		data-mp3-preview="<?php bp_document_attachment_url(); ?>"
		data-video-preview="<?php echo $video_url ? esc_url( $video_url ) : ''; ?>"
		data-album-id="<?php bp_document_folder_id(); ?>" data-group-id="<?php bp_document_group_id(); ?>"
		data-document-title="<?php echo esc_html( $filename ); ?>"
		data-mirror-text="<?php echo esc_html( $mirror_text ); ?>"
		data-can-edit="<?php echo esc_attr( bp_document_user_can_edit( bp_get_document_id() ) ); ?>"
		data-icon-class="<?php echo esc_attr( $svg_icon ); ?>">
			<span><?php echo esc_html( $document_title ); ?></span><?php echo $extension ? '.' . esc_html( $extension ) : ''; ?>
			<i class="media-document-id" data-item-id="<?php echo esc_attr( bp_get_document_id() ); ?>"
			style="display: none;"></i>
			<i class="media-document-attachment-id"
			data-item-id="<?php echo esc_attr( bp_get_document_attachment_id() ); ?>" style="display: none;"></i>
			<i class="media-document-type" data-item-id="<?php echo esc_attr( $document_type ); ?>"
			style="display: none;"></i>
		</a>
		<div class="media-folder_name_edit_wrap">
			<input type="text" value="" class="media-folder_name_edit"/>
			<?php
			if ( $attachment_id ) {
				?>
				<small class="error-box"><?php _e( 'Following special characters are not supported:<br/> ? [ ] / \\\\ = < > : ; , \' " & $ # * ( ) | ~ ` ! { } % + {space}', 'buddyboss' ); ?></small>
			<?php } else { ?>
				<small class="error-box"><?php _e( 'Following special characters are not supported:<br/> \ / ? % * : | " < >', 'buddyboss' ); ?></small>
				<?php
			}
			if ( wp_is_mobile() ) {
				?>
				<a href="#" class="name_edit_cancel button small"><?php esc_html_e( 'Cancel', 'buddyboss' ); ?></a>
				<a href="#"
				class="name_edit_save button small pull-right"><?php esc_html_e( 'Save', 'buddyboss' ); ?></a>
				<?php
			}
			?>
		</div>
	</div>
	<div class="media-folder_modified">
		<div class="media-folder_details__bottom">
			<span class="media-folder_date"><?php bp_document_date(); ?></span>
			<?php
			if ( ! bp_is_user() ) {
				$user_domain = bp_core_get_user_domain( bp_get_document_user_id() );
				if ( ! empty( $user_domain ) && false !== strpos( 'user-edit.php', $user_domain ) ) {
					$user_domain .= bp_get_document_slug();
				}
				?>
				<span class="media-folder_author"><?php esc_html_e( 'by ', 'buddyboss' ); ?>
					<?php
					if ( ! empty( $user_domain ) ) {
						?>
						<a href="<?php echo esc_url( trailingslashit( $user_domain ) ); ?>">
							<?php bp_document_author(); ?>
						</a>
						<?php
					} else {
						bp_document_author();
					}
					?>
				</span>
				<?php
			}
			?>
		</div>
	</div>
	<div class="media-folder_group">
		<?php
		if ( bp_is_document_directory() && bp_is_active( 'groups' ) && isset( $_POST ) && isset( $_POST['scope'] ) && 'personal' !== $_POST['scope'] ) {
			?>
			<div class="media-folder_details__bottom">
				<?php
				$group_id = bp_get_document_group_id();
				if ( $group_id > 0 ) {
					// Get the group from the database.
					$group = groups_get_group( $group_id );

					$group_name   = isset( $group->name ) ? bp_get_group_name( $group ) : '';
					$group_link   = sprintf( '<a href="%s" class="bp-group-home-link %s-home-link">%s</a>', esc_url( trailingslashit( bp_get_group_permalink( $group ) . bp_get_document_slug() ) ), esc_attr( bp_get_group_slug( $group ) ), esc_html( bp_get_group_name( $group ) ) );
					$group_status = bp_get_group_status( $group );
					?>
					<span class="media-folder_group"><?php echo wp_kses_post( $group_link ); ?></span>
					<span class="media-folder_status"><?php echo esc_html__( ucfirst( $group_status ), 'buddyboss' ); // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText ?></span>
					<?php
				} else {
					?>
					<span class="media-folder_group"><?php esc_html_e( '-', 'buddyboss' ); ?></span>
					<?php
				}
				?>
			</div>
			<?php
		}
		?>
	</div>
	<div class="media-folder_visibility">
		<div class="media-folder_details__bottom">
			<?php
			if ( bp_is_active( 'groups' ) ) {
				$group_id = bp_get_document_group_id();
				if ( $group_id > 0 ) {
					?>
					<span class="bp-tooltip" data-bp-tooltip-pos="down" data-bp-tooltip="<?php esc_attr_e( 'Based on group privacy', 'buddyboss' ); ?>"><?php bp_document_privacy(); ?></span>
					<?php
				} else {
					?>
					<span id="privacy-<?php echo esc_attr( bp_get_document_id() ); ?>"><?php bp_document_privacy(); ?></span>
					<?php
				}
			} else {
				?>
				<span><?php bp_document_privacy(); ?></span>
				<?php
			}
			?>
			<select data-item-type="<?php echo esc_attr( $document_type ); ?>"
					data-item-id="<?php echo esc_attr( $document_id ); ?>" id="bb-folder-privacy" class="hide">
				<?php
				foreach ( bp_document_get_visibility_levels() as $key => $privacy ) :
					if ( 'grouponly' === $key ) {
						continue;
					}
					?>
					<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $privacy ); ?></option>
					<?php
				endforeach;
				?>
			</select>
		</div>
	</div>
	<?php
	$show = false;
	if ( $attachment_id && $can_download ) {
		$show = true;
	} elseif ( $can_download ) {
		$show = true;
	} elseif ( $can_edit ) {
		$show = true;
	}
	?>
	<div class="media-folder_actions">
		<?php
		if ( $show ) {
			?>
			<a href="#" class="media-folder_action__anchor"> <i class="bb-icon-f bb-icon-ellipsis-v"></i> </a>
			<div class="media-folder_action__list">
				<ul>
					<?php
					if ( $can_download ) {
						?>
						<li class="download_file">
							<a href="<?php echo esc_url( $download_link ); ?>"><?php esc_html_e( 'Download', 'buddyboss' ); ?></a>
						</li>
						<li class="copy_download_file_url">
							<a href="<?php echo esc_url( $download_link ); ?>"><?php esc_html_e( 'Copy Download Link', 'buddyboss' ); ?></a>
						</li>
						<?php
					}
					if ( $can_edit ) {
						if ( ( 'document' === $document_type || 'folder' === $document_type ) && 0 === $group_id && 0 === bp_get_document_parent_id() ) {
							$url            = '#';
							$child_activity = '';
							$text           = esc_html__( 'Edit Privacy', 'buddyboss' );
							$class          = 'ac-document-privacy';
							$li_class       = '';
							if ( 'document' === $document_type ) {
								$child_activity = get_post_meta( $attachment_id, 'bp_document_activity_id', true );
								if ( $child_activity ) {
									$parent_activity_id        = get_post_meta( $attachment_id, 'bp_document_parent_activity_id', true );
									$parent_activity_permalink = '';
									if ( bp_is_active( 'activity' ) ) {
										$parent_activity_permalink = bp_activity_get_permalink( $parent_activity_id );
									}
									if ( $parent_activity_permalink ) {
										$text     = esc_html__( 'Edit Post Privacy', 'buddyboss' );
										$url      = $parent_activity_permalink;
										$class    = '';
										$li_class = 'redirect-activity-privacy-change';
									}
								}
							}

							?>
							<li class="privacy_file <?php echo esc_attr( $li_class ); ?>"
								id="<?php echo esc_attr( bp_get_document_id() ); ?>">
								<a href="<?php echo esc_url( $url ); ?>"
								data-id="<?php echo esc_attr( bp_get_document_id() ); ?>"
								data-privacy="<?php echo esc_attr( bp_get_db_document_privacy() ); ?>"
								class="<?php echo esc_attr( $class ); ?>"><?php echo esc_html( $text ); ?></a>
							</li>
							<?php
						}
						?>
						<li class="rename_file">
							<a href="#" data-type="<?php echo esc_attr( $document_type ); ?>"
							   class="ac-document-rename"><?php esc_html_e( 'Rename', 'buddyboss' ); ?></a>
						</li>
						<?php
						if ( $can_move ) {
							if ( $is_comment_doc ) {
								?>
								<li class="move_file disabled-move" data-balloon-pos="down"
									data-balloon="<?php esc_html_e( 'Document inherits activity privacy in comment. You are not allowed to move.', 'buddyboss' ); ?>">
									<a href="#"><?php esc_html_e( 'Move', 'buddyboss' ); ?></a>
								</li>
								<?php
							} else {
								?>
								<li class="move_file">
									<a href="#" data-action="<?php echo esc_attr( $data_action ); ?>"
									data-parent-id="<?php echo esc_attr( bp_get_document_parent_id() ); ?>"
									data-id="<?php echo esc_attr( $document_id ); ?>"
									data-type="<?php echo esc_attr( $move_type ); ?>"
									id="<?php echo esc_attr( $move_id ); ?>"
									class="<?php echo esc_attr( $move_class ); ?>"><?php esc_html_e( 'Move', 'buddyboss' ); ?></a>
								</li>
								<?php
							}
						}
					}

					$report_btn = bp_document_get_report_link( array( 'id' => bp_get_document_id() ) );
					if ( $report_btn && 'document' === $document_type ) {
						?>
						<li class="report_file">
							<?php echo $report_btn; ?>
						</li>
						<?php
					}

					if ( $can_delete ) {
						?>
						<li class="delete_file">
							<a class="document-file-delete" data-item-from="listing"
							data-item-preview-attachment-id="<?php echo esc_attr( bp_get_document_attachment_id() ); ?>"
							data-item-attachment-id="<?php echo esc_attr( bp_get_document_attachment_id() ); ?>"
							data-item-id="<?php echo esc_attr( bp_get_document_id() ); ?>"
							data-type="<?php echo esc_attr( $document_type ); ?>"
							href="#"><?php esc_html_e( 'Delete', 'buddyboss' ); ?></a>
						</li>
						<?php
					}
					?>
				</ul>
			</div>
			<?php
		}
		?>
	</div>
</div>

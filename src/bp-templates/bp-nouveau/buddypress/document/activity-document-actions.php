<?php
/**
 * The template for activity document actions
 *
 * This template can be overridden by copying it to yourtheme/buddypress/document/activity-document-actions.php.
 *
 * @since   BuddyBoss 1.7.0
 * @package BuddyBoss\Core
 * @version 1.7.0
 */

global $document_template;
$download_url     = bp_document_download_link( bp_get_document_attachment_id(), bp_get_document_id() );
$document_privacy = bb_media_user_can_access( bp_get_document_id(), 'document' );
$can_download_btn = true === (bool) $document_privacy['can_download'];
$can_view         = true === (bool) $document_privacy['can_view'];
$can_move         = true === (bool) $document_privacy['can_move'];
$can_add          = true === (bool) $document_privacy['can_add'];
$can_delete       = true === (bool) $document_privacy['can_delete'];
$db_privacy       = bp_get_db_document_privacy();
$is_comment_doc   = bp_document_is_activity_comment_document( $document_template->document );

$group_id = bp_get_document_group_id();
if ( $group_id > 0 ) {
	$move_id   = $group_id;
	$move_type = 'group';
} else {
	$move_id   = bp_get_document_user_id();
	$move_type = 'profile';
}
?>
<div class="document-action-wrap">
	<a href="#" class="document-action_collapse" data-balloon-pos="up" data-tooltip-collapse="<?php esc_attr_e( 'Collapse', 'buddyboss' ); ?>" data-balloon="<?php esc_attr_e( 'Expand', 'buddyboss' ); ?>"><i class="bb-icon-merge bb-icon-l document-icon-collapse"></i></a>
	<a href="<?php echo esc_url( $download_url ); ?>" class="document-action_download" data-id="<?php bp_document_id(); ?>" data-activity-id="<?php bp_document_activity_id(); ?>" data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'Download', 'buddyboss' ); ?>">
		<i class="bb-icon-l bb-icon-download"></i>
	</a>

	<a href="#" target="_blank" class="document-action_more" data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'More actions', 'buddyboss' ); ?>">
		<i class="bb-icon-f bb-icon-ellipsis-h"></i>
	</a>
	<div class="document-action_list">
		<ul class="conflict-activity-ul-li-comment">
			<?php
			if ( $can_download_btn ) {
				?>
				<li class="copy_download_file_url document-action-class">
					<a href="<?php echo esc_url( $download_url ); ?>"><?php esc_attr_e( 'Copy Download Link', 'buddyboss' ); ?></a>
				</li>
				<?php
			}
			if ( $can_move || bp_loggedin_user_id() === bp_get_document_user_id() || bp_current_user_can( 'bp_moderate' ) ) {
				if ( ! in_array( $db_privacy, array( 'forums', 'message' ), true ) ) {
					if ( $is_comment_doc ) {
						?>
						<li class="move_file document-action-class move-disabled" data-balloon-pos="down" data-balloon="<?php esc_attr_e( 'Document inherits activity privacy in comment. You are not allowed to move.', 'buddyboss' ); ?>">
							<a href="#"><?php esc_attr_e( 'Move', 'buddyboss' ); ?></a>
						</li>
						<?php
					} else {
						if ( $can_move ) {
							?>
							<li class="move_file document-action-class">
								<a href="#" data-action="document" data-type="<?php echo esc_attr( $move_type ); ?>" id="<?php echo esc_attr( $move_id ); ?>" class="ac-document-move"><?php esc_attr_e( 'Move', 'buddyboss' ); ?></a>
							</li>
							<?php
						}
					}
				}
				$item_id = 0;
				if ( bp_is_active( 'activity' ) ) {
					if ( bp_get_activity_comment_id() ) {
						$item_id = bp_get_activity_comment_id();
					} else {
						$item_id = bp_get_activity_id();
					}
				}
				if ( $can_delete ) {
					?>
					<li class="delete_file document-action-class">
						<a class="document-file-delete" data-item-activity-id="<?php echo esc_attr( $item_id ); ?>" data-item-from="activity" data-item-preview-attachment-id="<?php echo esc_attr( bp_get_document_preview_attachment_id() ); ?>" data-item-attachment-id="<?php echo esc_attr( bp_get_document_attachment_id() ); ?>" data-item-id="<?php echo esc_attr( bp_get_document_id() ); ?>" data-type="<?php echo esc_attr( 'document' ); ?>" href="#"><?php esc_attr_e( 'Delete', 'buddyboss' ); ?></a>
					</li>
					<?php
				}
			}
			?>
		</ul>
	</div>
</div>

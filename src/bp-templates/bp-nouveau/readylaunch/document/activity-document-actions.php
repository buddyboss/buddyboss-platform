<?php
/**
 * ReadyLaunch - Activity document actions template.
 *
 * This template handles document action buttons in activity views
 * including download, move, and delete functionality.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

global $document_template;
$document_id            = bp_get_document_id();
$document_attachment_id = bp_get_document_attachment_id();
$download_url           = bp_document_download_link( $document_attachment_id, $document_id );
$document_privacy       = bb_media_user_can_access( $document_id, 'document' );
$can_download_btn       = true === (bool) $document_privacy['can_download'];
$can_view               = true === (bool) $document_privacy['can_view'];
$can_move               = true === (bool) $document_privacy['can_move'];
$can_add                = true === (bool) $document_privacy['can_add'];
$can_delete             = true === (bool) $document_privacy['can_delete'];
$db_privacy             = bp_get_db_document_privacy();
$is_comment_doc         = bp_document_is_activity_comment_document( $document_template->document );
$document_user_id       = bp_get_document_user_id();
$group_id               = bp_get_document_group_id();
$move_id                = ( 0 < $group_id ) ? $group_id : $document_user_id;
$move_type              = ( 0 < $group_id ) ? 'group' : 'profile';
?>
<div class="bb-rl-document-action-wrap">
	<a href="<?php echo esc_url( $download_url ); ?>" class="bb-rl-document-action_download" data-id="<?php echo esc_attr( $document_id ); ?>" data-activity-id="<?php bp_document_activity_id(); ?>" data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'Download', 'buddyboss' ); ?>">
		<i class="bb-icons-rl-arrow-circle-down"></i>
	</a>
	<a href="#" target="_blank" class="bb-rl-document-action_more" data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'More actions', 'buddyboss' ); ?>">
		<i class="bb-icons-rl-dots-three"></i>
	</a>
	<div class="bb_rl_more_dropdown">
		<ul class="bb-rl-conflict-activity-ul-li-comment">
			<?php
			if ( $can_download_btn ) {
				?>
				<li class="bb_rl_copy_download_file_url bb-rl-document-action-class">
					<a href="<?php echo esc_url( $download_url ); ?>"><?php esc_attr_e( 'Copy Download Link', 'buddyboss' ); ?></a>
				</li>
				<?php
			}
			if ( $can_move || bp_loggedin_user_id() === $document_user_id || bp_current_user_can( 'bp_moderate' ) ) {
				if ( ! in_array( $db_privacy, array( 'forums', 'message' ), true ) ) {
					if ( $is_comment_doc ) {
						?>
						<li class="bb_rl_move_file bb-rl-document-action-class bb-rl-move-disabled" data-balloon-pos="down" data-balloon="<?php esc_attr_e( 'Document inherits activity privacy in comment. You are not allowed to move.', 'buddyboss' ); ?>">
							<a href="#"><?php esc_attr_e( 'Move', 'buddyboss' ); ?></a>
						</li>
						<?php
					} elseif ( $can_move ) {
						?>
						<li class="bb_rl_move_file bb-rl-document-action-class">
							<a href="#" data-action="document" data-type="<?php echo esc_attr( $move_type ); ?>" id="<?php echo esc_attr( $move_id ); ?>" class="ac-document-move"><?php esc_attr_e( 'Move', 'buddyboss' ); ?></a>
						</li>
						<?php
					}
				}
				if ( $can_delete ) {
					$item_id = ( bp_is_active( 'activity' ) ) ? ( bp_get_activity_comment_id() ?? bp_get_activity_id() ) : 0;
					?>
					<li class="bb_rl_delete_file bb-rl-document-action-class">
						<a class="bb-rl-document-file-delete" data-item-activity-id="<?php echo esc_attr( $item_id ); ?>" data-item-from="activity" data-item-preview-attachment-id="<?php echo esc_attr( $document_attachment_id ); ?>" data-item-attachment-id="<?php echo esc_attr( $document_attachment_id ); ?>" data-item-id="<?php echo esc_attr( $document_id ); ?>" data-type="<?php echo esc_attr( 'document' ); ?>" href="#"><?php esc_attr_e( 'Delete', 'buddyboss' ); ?></a>
					</li>
					<?php
				}
			}
			?>
		</ul>
	</div>
	<div class="bb_rl_more_dropdown_overlay"></div>
</div>

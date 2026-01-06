<?php
/**
 * ReadyLaunch - No document template.
 *
 * This template displays a message when no documents are found
 * and provides action buttons for adding documents or folders.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$folder_id = 0;
if ( function_exists( 'bp_is_group_single' ) && bp_is_group_single() && bp_is_group_folders() ) {
	$folder_id = (int) bp_action_variable( 1 );
} else {
	$folder_id = (int) bp_action_variable( 0 );
}

$is_inside_folder   = $folder_id > 0;
$bp_is_group        = bp_is_group();
$active_extensions  = bp_document_get_allowed_extension();
$can_create_folder  = false;

// Check if user can create folders.
if ( is_user_logged_in() && ! empty( $active_extensions ) ) {
	if ( bp_is_active( 'groups' ) && $bp_is_group ) {
		$can_create_folder = groups_can_user_manage_document( bp_loggedin_user_id(), bp_get_current_group_id() );
	} elseif ( bp_is_my_profile() && bb_user_can_create_document() ) {
		$can_create_folder = true;
	}
}

?>
<div class="bb-rl-media-none">
	<div class="bb-rl-media-none-figure"><i class="bb-icons-rl-file-doc"></i></div>
	<aside class="bp-feedback bp-messages info">
		<span class="bp-icon" aria-hidden="true"></span>
		<p><?php esc_html_e( 'No documents found', 'buddyboss' ); ?></p>
	</aside>
	<p class="bb-rl-media-none-description"><?php esc_html_e( 'It looks like there aren\'t any documents in this directory.', 'buddyboss' ); ?></p>
	<div class="bb-rl-media-none-actions">
		<?php
		if ( $is_inside_folder ) {
			if ( $can_create_folder ) {
				?>
				<div class="bb-media-actions-wrap album-actions-wrap">
					<div class="bb-media-actions">
						<a href="#" id="bb-create-folder-child" class="bb-create-folder-stacked button bb-rl-button bb-rl-button--secondaryFill bb-rl-button--small">
							<i class="bb-icons-rl-folder-plus"></i><?php esc_html_e( 'Create Folder', 'buddyboss' ); ?>
						</a>
					</div>
				</div>
				<?php
				bp_get_template_part( 'document/create-child-folder' );
			}
		} else {
			bp_get_template_part( 'document/add-folder' );
		}

		bp_get_template_part( 'document/add-document' );
		?>
	</div>
</div>

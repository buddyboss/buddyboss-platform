<?php
/**
 * The template for create folder
 *
 * This template can be overridden by copying it to yourtheme/buddypress/document/add-folder.php.
 *
 * @since   BuddyBoss 1.4.0
 * @package BuddyBoss\Core
 * @version 1.4.0
 */

$active_extensions = bp_document_get_allowed_extension();


if ( is_user_logged_in() && ! empty( $active_extensions ) && ( ( bp_is_my_profile() && bb_user_can_create_document() ) || ( bp_is_active( 'groups' ) && bp_is_group() && groups_can_user_manage_document( bp_loggedin_user_id(), bp_get_current_group_id() ) ) ) ) : ?>

	<div class="bb-media-actions-wrap album-actions-wrap">
		<div class="bb-media-actions">
			<a href="#" id="bb-create-folder" class="bb-create-folder button small outline"><i class="bb-icon-l bb-icon-folder-alt"></i><?php esc_html_e( 'Create Folder', 'buddyboss' ); ?></a>
		</div>
	</div>

	<?php
	bp_get_template_part( 'document/create-folder' );
endif;

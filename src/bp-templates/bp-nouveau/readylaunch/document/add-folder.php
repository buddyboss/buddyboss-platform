<?php
/**
 * The template for create folder
 *
 * @since   BuddyBoss 2.9.00
 * @package BuddyBoss\Core
 * @version 1.0.0
 */

$active_extensions = bp_document_get_allowed_extension();


if ( is_user_logged_in() && ! empty( $active_extensions ) && ( ( bp_is_my_profile() && bb_user_can_create_document() ) || ( bp_is_active( 'groups' ) && bp_is_group() && groups_can_user_manage_document( bp_loggedin_user_id(), bp_get_current_group_id() ) ) ) ) : ?>

	<div class="bb-media-actions-wrap album-actions-wrap">
		<div class="bb-media-actions">
			<a href="#" id="bb-create-folder" class="bb-create-folder button bb-rl-button bb-rl-button--secondaryFill bb-rl-button--small"><i class="bb-icons-rl-folder-plus"></i><?php esc_html_e( 'Create Folder', 'buddyboss' ); ?></a>
		</div>
	</div>

	<?php
	bp_get_template_part( 'document/create-folder' );
endif;

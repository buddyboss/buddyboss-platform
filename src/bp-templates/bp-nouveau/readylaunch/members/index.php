<?php
/**
 * The ReadyLaunch template for members directory.
 *
 * This template handles the members directory page layout for the ReadyLaunch theme.
 * It includes search filters, profile search, invite functionality, and members listing with skeleton loading.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$is_send_ajax_request = bb_is_send_ajax_request();

/**
 * Fires at the begining of the templates BP injected content.
 *
 * @since BuddyBoss 2.9.00
 */
do_action( 'bp_before_directory_members_page' );
?>

<div class="bb-rl-members-directory-wrapper">
	<div class="bb-rl-secondary-header flex items-center">
		<div class="bb-rl-entry-heading flex">
			<h2><?php esc_html_e( 'Members', 'buddyboss' ); ?> <span class="bb-rl-heading-count"><?php echo ! $is_send_ajax_request ? esc_html( bp_core_get_all_member_count() ) : ''; ?></span></h2>
			<?php
			if ( ! bp_disable_advanced_profile_search() ) {
				?>
				<div class="bb-rl-advance-profile-search">
					<a href="javascript::" class="bb-rl-advance-profile-search-toggle"><?php esc_html_e( 'Profile Search', 'buddyboss' ); ?></a>
				<?php bp_profile_search_show_form(); ?>
				</div>
				<?php
			}
			?>
		</div>
		<div class="bb-rl-sub-ctrls flex items-center">
			<?php
			bp_get_template_part( 'common/search-and-filters-bar' );
			if ( bp_allow_user_to_send_invites() ) {
				?>
				<div class="bb-rl-action-button">
					<a href="" id="bb-rl-invite-button" class="bb-rl-button bb-rl-button--brandFill bb-rl-button--small flex items-center"><i class="bb-icons-rl-plus"></i><?php esc_html_e( 'Invite', 'buddyboss' ); ?></a>
				</div>
				<?php
			}
			?>
		</div>
	</div>

	<div class="bb-rl-container-inner">

		<?php
		/**
		 * Fires before the display of the members.
		 *
		 * @since BuddyBoss 2.9.00
		 */
		do_action( 'bp_before_directory_members' );
		?>

		<div class="bb-rl-members-directory-container flex">

			<?php
			/**
			 * Fires before the display of the members list tabs.
			 *
			 * @since BuddyBoss 2.9.00
			 */
			do_action( 'bp_before_directory_members_tabs' );

			/**
			 * Fires before the display of the members content.
			 *
			 * @since BuddyBoss 2.9.00
			 */
			do_action( 'bp_before_directory_members_content' );
			?>

			<div class="screen-content bb-rl-members-directory-content">

				<div id="bb-rl-members-dir-list" class="members dir-list bb-rl-members" data-bp-list="members" data-ajax="<?php echo esc_attr( $is_send_ajax_request ? 'true' : 'false' ); ?>">
					<?php
					if ( $is_send_ajax_request ) {
						echo '<div id="bp-ajax-loader">';
						?>
						<div class="bb-rl-skeleton-grid <?php bp_nouveau_loop_classes(); ?>">
							<?php for ( $i = 0; $i < 8; $i++ ) : ?>
								<div class="bb-rl-skeleton-grid-block">
									<div class="bb-rl-skeleton-avatar bb-rl-skeleton-loader"></div>
									<div class="bb-rl-skeleton-data">
										<span class="bb-rl-skeleton-data-bit bb-rl-skeleton-loader"></span>
										<span class="bb-rl-skeleton-data-bit bb-rl-skeleton-loader"></span>
										<span class="bb-rl-skeleton-data-bit bb-rl-skeleton-loader"></span>
									</div>
									<div class="bb-rl-skeleton-footer">
										<span class="bb-rl-skeleton-data-bit bb-rl-skeleton-loader"></span>
										<span class="bb-rl-skeleton-data-bit bb-rl-skeleton-loader"></span>
										<span class="bb-rl-skeleton-data-bit bb-rl-skeleton-loader"></span>
									</div>
								</div>
							<?php endfor; ?>
						</div>
						<?php
						echo '</div>';
					} else {
						bp_get_template_part( 'members/members-loop' );
					}
					?>
				</div><!-- #members-dir-list -->

				<?php
				/**
				 * Fires and displays the members content.
				 *
				 * @since BuddyBoss 2.9.00
				 */
				do_action( 'bp_directory_members_content' );
				?>
			</div><!-- // .screen-content -->

			<?php

			bp_get_template_part( 'sidebar/right-sidebar' );

			/**
			 * Fires after the display of the members content.
			 *
			 * @since BuddyBoss 2.9.00
			 */
			do_action( 'bp_after_directory_members_content' );
			?>

		</div>

		<?php
		/**
		 * Fires after the display of the members.
		 *
		 * @since BuddyBoss 2.9.00
		 */
		do_action( 'bp_after_directory_members' );
		?>
	</div>

</div>

<!-- Invite popup -->
<?php
if ( bp_allow_user_to_send_invites() ) {
	$send_invite_member_type_allow     = bp_check_member_send_invites_tab_member_type_allowed();
	$is_disabled_invite_member_subject = bp_disable_invite_member_email_subject();
	$is_disabled_invite_member_content = bp_disable_invite_member_email_content();
	?>
	<div id="bb-rl-invite-modal" class="bb-rl-invite-modal bb-rl-modal"  style="display: none;">
		<transition name="modal">
			<div class="bb-rl-modal-mask">
				<div class="bb-rl-modal-wrapper">
					<div class="bb-rl-modal-container">
						<header class="bb-rl-modal-header">
							<h4><span class="target_name"><?php echo esc_html__( 'Send invite to add member', 'buddyboss' ); ?></span></h4>
							<a class="bb-rl-close-invite bb-rl-modal-close-button" href="javascript:void(0);">
								<span class="bb-icons-rl-x"></span>
							</a>
						</header>
						<div class="bb-rl-invite-content bb-rl-modal-content">
							<form action="" method="post" class="bb-rl-invite-form" id="bb-rl-invite-form" novalidate>
								<div class="bb-rl-form-field-wrapper">
									<label for="bb-rl-invite-name"><?php esc_html_e( 'Name', 'buddyboss' ); ?></label>
									<input type="text" name="bb-rl-invite-name" id="bb-rl-invite-name" value="" class="bb-rl-input-field" placeholder="<?php esc_html_e( 'Type name', 'buddyboss' ); ?>">
								</div>
								<div class="bb-rl-form-field-wrapper">
									<label for="bb-rl-invite-email"><?php esc_html_e( 'Email address', 'buddyboss' ); ?></label>
									<input type="email" name="bb-rl-invite-email" id="bb-rl-invite-email" value="" class="bb-rl-input-field" placeholder="<?php esc_html_e( 'Enter an email address', 'buddyboss' ); ?>">
								</div>
								<?php
								if ( true === $send_invite_member_type_allow ) {
									$current_user              = bp_loggedin_user_id();
									$member_type               = bp_get_member_type( $current_user );
									$member_type_post_id       = bp_member_type_post_by_type( $member_type );
									$get_selected_member_types = get_post_meta( $member_type_post_id, '_bp_member_type_allowed_member_type_invite', true );
									if ( isset( $get_selected_member_types ) && ! empty( $get_selected_member_types ) ) {
										$member_types = $get_selected_member_types;
									} else {
										$member_types = bp_get_active_member_types();
									}
									?>
										<div class="bb-rl-form-field-wrapper">
											<label for="bb-rl-invite-type"><?php esc_html_e( 'Profile Type', 'buddyboss' ); ?></label>
											<select id="bb-rl-invite-type" name="bb-rl-invite-type" class="bb-rl-input-field bb-rl-input-field--select">
											<?php
											foreach ( $member_types as $type ) {
												$name = bp_get_member_type_key( $type );
												if ( $type_obj = bp_get_member_type_object( $name ) ) {
													$member_type = $type_obj->labels['singular_name'];
												}
												?>
													<option value="<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $member_type ); ?></option>
													<?php
											}
											?>
											</select>
										</div>
										<?php
								}
								if ( true === $is_disabled_invite_member_subject ) {
									?>
									<div class="bb-rl-form-field-wrapper">
										<label for="bb-rl-invite-custom-subject"><?php esc_html_e( 'Subject', 'buddyboss' ); ?></label>
										<textarea name="bp_member_invites_custom_subject" id="bb-rl-invite-custom-subject" class="bb-rl-textarea-field" rows="5" cols="10"><?php echo esc_textarea( bp_get_member_invitation_subject() ); ?></textarea>
									</div>
									<?php
								}

								if ( true === $is_disabled_invite_member_content ) {
									?>
									<div class="bb-rl-form-field-wrapper">
										<label for="bb-rl-invite-custom-content"><?php esc_html_e( 'Invitation message', 'buddyboss' ); ?></label>
										<?php
											add_filter( 'mce_buttons', 'bp_nouveau_btn_invites_mce_buttons', 10, 1 );
											add_filter( 'tiny_mce_before_init', 'bp_nouveau_send_invite_content_css' );
											wp_editor(
												bp_get_member_invites_wildcard_replace( bp_get_member_invitation_message() ),
												'bb-rl-invite-custom-content',
												array(
													'textarea_name' => 'bp_member_invites_custom_content',
													'teeny' => false,
													'media_buttons' => false,
													'dfw' => false,
													'tinymce' => true,
													'quicktags' => false,
													'tabindex' => '3',
													'textarea_rows' => 5,
												)
											);
											// Remove the temporary filter on editor buttons.
											remove_filter( 'mce_buttons', 'bp_nouveau_btn_invites_mce_buttons', 10, 1 );
											remove_filter( 'tiny_mce_before_init', 'bp_nouveau_send_invite_content_css' );
										?>
									</div>
									<?php
								}
								wp_nonce_field( 'bb_rl_invite_form_action', 'bb_rl_invite_form_nonce' );
								?>
								<input type="hidden" name="action" value="bb_rl_invite_form" />
							</form>
						</div>
						<footer class="bb-rl-modal-footer flex items-center">
							<a class="bb-rl-close-invite bb-rl-modal-close-button bb-rl-button bb-rl-button--secondaryFill bb-rl-button--small" href="javascript:void(0);"><?php echo esc_html__( 'Cancel', 'buddyboss' ); ?></a>
							<input type="submit" name="bb-rl-submit-invite" id="bb-rl-submit-invite" form="bb-rl-invite-form" value="<?php esc_html_e( 'Send Invite', 'buddyboss' ); ?>" class="bb-rl-button-submit-invite bb-rl-button bb-rl-button--brandFill bb-rl-button--small" disabled="disabled">
						</footer>
					</div>
				</div>
			</div>
		</transition>
	</div> <!-- .bb-invite-connection -->
	<?php
}
/**
 * Fires at the bottom of the member directory template file.
 *
 * @since BuddyBoss 2.9.00
 */
do_action( 'bp_after_directory_members_page' );
?>

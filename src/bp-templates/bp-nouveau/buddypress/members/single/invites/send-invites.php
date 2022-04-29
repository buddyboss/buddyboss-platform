<?php
/**
 * The template for send invites
 *
 * This template can be overridden by copying it to yourtheme/buddypress/members/single/invites/send-invites.php.
 *
 * @since   BuddyBoss 1.0.0
 * @version 1.0.0
 */

bp_nouveau_member_hook( 'before', 'invites_send_template' ); ?>

<h2 class="screen-heading general-settings-screen">
	<?php _e( 'Send Invites', 'buddyboss' ); ?>
</h2>

<p class="info invite-info">
	<?php _e( 'Invite non-members to create an account. They will receive an email with a link to register.', 'buddyboss' ); ?>
</p>

<form action="<?php echo esc_url( bp_displayed_user_domain() . bp_get_invites_slug() ); ?>" method="post" class="standard-form" id="send-invite-form">

	<table class="invite-settings bp-tables-user" id="<?php echo esc_attr( 'member-invites-table' ); ?>">
		<thead>
		<tr>
			<th class="title"><?php esc_html_e( 'Recipient Name', 'buddyboss' ); ?></th>
			<th class="title"><?php esc_html_e( 'Recipient Email', 'buddyboss' ); ?></th>
			<?php
			if ( true === bp_check_member_send_invites_tab_member_type_allowed() ) {
				?>
				<th class="title"><?php esc_html_e( 'Profile Type', 'buddyboss' ); ?></th>
				<?php
			}
			?>
			<th class="title actions"></th>
		</tr>
		</thead>

		<tbody>

		<?php
		$raw = apply_filters( 'bp_invites_member_default_invitation_raw', 1 );
		for ( $i = 0; $i < $raw; $i++ ) {
			?>

			<tr>
				<td class="field-name">
					<input type="text" name="invitee[<?php echo $i; ?>][]" id="invitee_<?php echo $i; ?>_title" value="<?php echo esc_attr( '' ); ?>" class="invites-input" <?php bp_form_field_attributes( 'invitee' ); ?>/>
				</td>
				<td class="field-email">
					<input type="email" name="email[<?php echo $i; ?>][]" id="email_<?php echo $i; ?>_email" value="<?php echo esc_attr( '' ); ?>" class="invites-input" <?php bp_form_field_attributes( 'email' ); ?>/>
				</td>
				<?php
				if ( true === bp_check_member_send_invites_tab_member_type_allowed() ) {
					$current_user = bp_loggedin_user_id();
					$member_type = bp_get_member_type( $current_user );
					$member_type_post_id = bp_member_type_post_by_type( $member_type );
					$get_selected_member_types = get_post_meta( $member_type_post_id, '_bp_member_type_allowed_member_type_invite', true );
					if ( isset( $get_selected_member_types ) && !empty( $get_selected_member_types ) ) {
						$member_types = $get_selected_member_types;
					} else {
						$member_types = bp_get_active_member_types();
					}
					?>
					<td class="field-member-type">
						<select name="member-type[<?php echo $i; ?>][]" id="member_type<?php echo $i; ?>_member_type" class="invites-input">
							<option value=""><?php _e( '-- Select Type --', 'buddyboss'); ?></option>
							<?php
							foreach ( $member_types as $type ) {
								$name    = bp_get_member_type_key( $type );
								if ( $type_obj = bp_get_member_type_object( $name ) ) {
									$member_type = $type_obj->labels['singular_name'];
								}
								?>
								<option value="<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $member_type ); ?></option>
								<?php
							}
							?>
						</select>
					</td>
					<?php
				}
				?>
				<td class="field-actions">
					<span class="field-actions-remove"><i class="bb-icon-l bb-icon-times"></i></span>
				</td>
			</tr>

		<?php }; ?>
			<tr>
				<td class="field-name" colspan="<?php if ( true === bp_check_member_send_invites_tab_member_type_allowed() ) { echo 3; } else { echo 2; }?>">
				</td>
				<td class="field-actions-last" colspan="">
					<span class="field-actions-add"><i class="bb-icon-l bb-icon-plus"></i></span>
				</td>
			</tr>

		</tbody>
	</table>

	<?php

	if ( true === bp_disable_invite_member_email_subject() ) {
		?>
		<label for="bp-member-invites-custom-subject"><?php _e( 'Customize the text of the invitation subject.', 'buddyboss' ) ?></label>
		<textarea name="bp_member_invites_custom_subject" id="bp-member-invites-custom-subject" rows="5" cols="10" ><?php echo esc_textarea( bp_get_member_invitation_subject() ) ?></textarea>
		<input type="hidden" value="<?php _e('Are you sure you want to send the invite without a subject?', 'buddyboss') ?>" name="error-message-empty-subject-field" id="error-message-empty-subject-field">
		<?php
	}

	if ( true === bp_disable_invite_member_email_content() ) {

		?>
		<label for="bp-member-invites-custom-content"><?php _e( 'Customize the text of the invitation email. A link to register will be sent with the email.', 'buddyboss' ) ?></label>
		<?php
		add_filter( 'mce_buttons', 'bp_nouveau_btn_invites_mce_buttons', 10, 1 );
		add_filter('tiny_mce_before_init','bp_nouveau_send_invite_content_css');
		wp_editor(
			bp_get_member_invites_wildcard_replace( bp_get_member_invitation_message() ),
			'bp-member-invites-custom-content',
			array(
				'textarea_name' => 'bp_member_invites_custom_content',
				'teeny'         => false,
				'media_buttons' => false,
				'dfw'           => false,
				'tinymce'       => true,
				'quicktags'     => false,
				'tabindex'      => '3',
				'textarea_rows' => 5,
			)
		);
		// Remove the temporary filter on editor buttons
		remove_filter( 'mce_buttons', 'bp_nouveau_btn_invites_mce_buttons', 10, 1 );
		remove_filter('tiny_mce_before_init','bp_nouveau_send_invite_content_css');
		?>
		<input type="hidden" value="<?php _e('Are you sure you want to send the invite without adding a message?', 'buddyboss') ?>" name="error-message-empty-body-field" id="error-message-empty-body-field">
		<?php
	}

	if ( true === bp_disable_invite_member_email_subject() && true === bp_disable_invite_member_email_content() ) {
		?>
		<input type="hidden" value="<?php _e('Are you sure you want to send the invite without adding a subject and message?', 'buddyboss') ?>" name="error-message-empty-subject-body-field" id="error-message-empty-subject-body-field">
		<?php
	}
	?>
	<input type="hidden" value="<?php _e('Enter a valid email address', 'buddyboss') ?>" name="error-message-invalid-email-address-field" id="error-message-invalid-email-address-field">
	<input type="hidden" value="<?php _e('Enter name', 'buddyboss') ?>" name="error-message-empty-name-field" id="error-message-empty-name-field">
	<input type="hidden" value="<?php _e('Please fill out all required fields to invite a new member.', 'buddyboss') ?>" name="error-message-required-field" id="error-message-required-field">
	<?php bp_nouveau_submit_button( 'member-invites-submit' ); ?>

</form>
<?php
bp_nouveau_member_hook( 'after', 'invites_send_template' );

<?php
bp_nouveau_member_hook( 'before', 'invites_send_template' ); ?>

<h2 class="screen-heading general-settings-screen">
	<?php _e( 'Invite by Email', 'buddyboss' ); ?>
</h2>

<p class="info invite-info">
	<?php _e( 'Invite non-members to join the network. They will receive an email with a link to register.', 'buddyboss' ); ?>
</p>

<form action="<?php echo esc_url( bp_displayed_user_domain() . bp_get_invites_slug() ); ?>" method="post" class="standard-form" id="send-invite-form">

	<table class="invite-settings bp-tables-user" id="<?php echo esc_attr( 'member-invites-table' ); ?>">
		<thead>
		<tr>
			<th class="title"><?php esc_html_e( 'Name', 'buddyboss' ); ?></th>
			<th class="title"><?php esc_html_e( 'Email', 'buddyboss' ); ?></th>
			<?php
			if ( true === bp_check_member_send_invites_tab_member_type_allowed() ) {
				?>
				<th class="title"><?php esc_html_e( 'Type', 'buddyboss' ); ?></th>
				<?php
			}
			?>
		</tr>
		</thead>

		<tbody>

		<?php
		$raw = apply_filters( 'bp_invites_member_default_invitation_raw', 5 );
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
							<option value=""><?php echo __( '-- Select Type --', 'buddyboss'); ?></option>
							<?php
							foreach ( $member_types as $type ) {
								$name    = bp_get_member_type_key( $type );
								if ( $type_obj = bp_get_member_type_object( $name ) ) {
									$member_type = $type_obj->labels['singular_name'];
									$member_type = __( $member_type, 'buddyboss');
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
			</tr>

		<?php }; ?>

		</tbody>
	</table>

	<?php

	if ( true === bp_disable_invite_member_email_subject() ) {
		?>
		<label for="bp-member-invites-custom-subject"><?php _e( 'Customize the text of the invitation subject.', 'buddyboss' ) ?></label>
		<textarea name="bp_member_invites_custom_subject" id="bp-member-invites-custom-subject" rows="15" cols="10" ><?php echo esc_textarea( bp_get_member_invitation_subject() ) ?></textarea>
		<?php
	}

	if ( true === bp_disable_invite_member_email_content() ) {
		?>
		<label for="bp-member-invites-custom-content"><?php _e( 'Customize the text of the invitation email.', 'buddyboss' ) ?></label>
		<textarea name="bp_member_invites_custom_content" id="bp-member-invites-custom-content" rows="15" cols="10" ><?php echo esc_textarea( bp_get_member_invitation_message() ) ?></textarea>
		<?php
	}
	?>
	<input type="hidden" value="<?php _e('Please fill all the required field to invite member.', 'buddyboss') ?>" name="error-message-required-field" id="error-message-required-field">
	<?php bp_nouveau_submit_button( 'member-invites-submit' ); ?>

</form>
<?php
bp_nouveau_member_hook( 'after', 'invites_send_template' );

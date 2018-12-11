<?php
bp_nouveau_member_hook( 'before', 'invites_send_template' ); ?>

<h2 class="screen-heading general-settings-screen">
	<?php _e( 'Invite by Email', 'buddyboss' ); ?>
</h2>

<p class="info invite-info">
	<?php _e( 'Invite non-members to join the network, They will receive an email with a link to register.', 'buddyboss' ); ?>
</p>

<form action="<?php echo esc_url( bp_displayed_user_domain() . bp_get_invites_slug() ); ?>" method="post" class="standard-form" id="send-invite-form">

	<table class="invite-settings bp-tables-user" id="<?php echo esc_attr( 'member-invites-table' ); ?>">
		<thead>
		<tr>
			<th class="title"><?php esc_html_e( 'Name', 'buddyboss' ); ?></th>
			<th class="title"><?php esc_html_e( 'Email', 'buddyboss' ); ?></th>
		</tr>
		</thead>

		<tbody>

		<?php
		for ( $i = 0; $i < 5; $i++ ) {
			?>

			<tr>
				<td class="field-name">
					<input type="text" name="invitee[<?php echo $i; ?>][]" id="invitee" value="<?php echo esc_attr( '' ); ?>" class="invites-input" <?php bp_form_field_attributes( 'invitee' ); ?>/>
				</td>
				<td class="field-email">
					<input type="email" name="email[<?php echo $i; ?>][]" id="email" value="<?php echo esc_attr( '' ); ?>" class="invites-input" <?php bp_form_field_attributes( 'email' ); ?>/>
				</td>
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

	<?php bp_nouveau_submit_button( 'member-invites-submit' ); ?>

</form>
<?php
bp_nouveau_member_hook( 'after', 'invites_send_template' );

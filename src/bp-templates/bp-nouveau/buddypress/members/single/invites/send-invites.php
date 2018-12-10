<?php
bp_nouveau_member_hook( 'before', 'invites_send_template' ); ?>

<h2 class="screen-heading general-settings-screen">
	<?php _e( 'Invite by Email', 'buddyboss' ); ?>
</h2>

<p class="info invite-info">
	<?php _e( 'Invite non-members to join the network, They will receive an email with a link to register.', 'buddyboss' ); ?>
</p>

<form action="<?php echo esc_url( bp_displayed_user_domain() . bp_get_invites_slug() ); ?>" method="post" class="standard-form" id="send-invite-form">

	<?php if ( bp_xprofile_get_settings_fields() ) : ?>

		<?php
		while ( bp_profile_groups() ) :
			bp_the_profile_group();
			?>

			<?php if ( bp_profile_fields() ) : ?>

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

		<?php endif; ?>

		<?php endwhile; ?>

	<?php endif; ?>



	<?php bp_nouveau_submit_button( 'member-invites-submit' ); ?>

</form>
<?php
bp_nouveau_member_hook( 'after', 'invites_send_template' );

<div class="wrap">

    <div class="bp-admin-card section-bp_compatibility-integration">
        <h1><?php _e( 'BuddyPress Settings', 'buddypress' ); ?> </h1>

		<?php
		// We're saving our own options, until the WP Settings API is updated to work with Multisite.
		$form_action = add_query_arg( array(
			'page' => 'bp-integrations',
			'tab'  => 'bp-compatibility'
		), bp_get_admin_url( 'admin.php' ) );
		?>

        <form action="<?php echo esc_url( $form_action ) ?>" method="post">

			<?php
			//add_settings_section callback is displayed here. For every new section we need to call settings_fields.
			settings_fields( "buddypress" );

			// all the add_settings_field callbacks is displayed here
			do_settings_sections( "buddypress" );
			?>
            <p class="submit">
                <input type="submit" name="submit" class="button-primary"
                       value="<?php esc_attr_e( 'Save Settings', 'buddypress' ); ?>"/>
            </p>

        </form>
    </div>

</div>
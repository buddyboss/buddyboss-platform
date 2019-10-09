<div class="wrap">

	<div class="bp-admin-card section-bp_compatibility-integration">
		<h2><?php _e( 'BuddyPress <span>&mdash; Third party plugin settings</span>', 'buddyboss' ); ?></h2>

		<?php
		// We're saving our own options, until the WP Settings API is updated to work with Multisite.
		$form_action = add_query_arg(
			array(
				'page' => 'bp-integrations',
				'tab'  => 'bp-compatibility',
			),
			bp_get_admin_url( 'admin.php' )
		);
		?>

		<form action="<?php echo esc_url( $form_action ); ?>" method="post">

			<?php
			// add_settings_section callback is displayed here. For every new section we need to call settings_fields.
			settings_fields( 'buddypress' );


			ob_start();

			// all the add_settings_field callbacks is displayed here
			bp_core_compatibility_do_settings_sections( 'buddypress' );

			$output = ob_get_contents();
			ob_clean();

			if ( ! empty( $output ) ) {
				echo $output;

				submit_button( __( 'Save Settings', 'buddyboss' ) );
			} else {
				printf( '<p>%s</p>', __( 'In BuddyPress, developers frequently added their plugin options into Settings > BuddyPress > Options. If you enable any third party BuddyPress plugins that used this method, those options will appear on this page.', 'buddyboss' ) );
			}
			?>
		</form>
	</div>

</div>

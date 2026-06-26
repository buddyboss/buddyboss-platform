<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly. ?>
<div class="wrap">

	<div class="bp-admin-card section-bp_compatibility-integration">
		<h2>
			<?php
			$meta_icon = bb_admin_icons( 'bp_compatibility-integration' );
			if ( ! empty( $meta_icon ) ) {
				echo '<i class="' . esc_attr( $meta_icon ) . '"></i>';
			}
			echo sprintf(
			/* translators: 1. Text. 2. Text. */
				'%1$s&nbsp;<span>&mdash; %2$s</span>',
				esc_html__( 'BuddyPress', 'buddyboss-platform' ),
				esc_html__( 'Third party plugin settings', 'buddyboss-platform' )
			);
			?>
		</h2>

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
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $output is buffered settings-section HTML (form controls) from bp_core_compatibility_do_settings_sections(); wp_kses_post would strip inputs.
				echo $output;

				submit_button( __( 'Save Settings', 'buddyboss-platform' ) );
			} else {
				printf( '<p>%s</p>', esc_html__( 'In BuddyPress, developers frequently added their plugin options into Settings > BuddyPress > Options. If you enable any third party BuddyPress plugins that used this method, those options will appear on this page.', 'buddyboss-platform' ) );
			}
			?>
		</form>
	</div>

</div>

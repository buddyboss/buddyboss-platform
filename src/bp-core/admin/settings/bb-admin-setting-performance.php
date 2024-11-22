<?php
/**
 * Add admin performance settings page in Dashboard->BuddyBoss->Settings
 *
 * @package BuddyBoss\Core
 *
 * @since BuddyBoss 2.5.80
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Main performance settings class.
 *
 * @since BuddyBoss 2.5.80
 */
class BB_Admin_Setting_Performance extends BP_Admin_Setting_tab {

	/**
	 * Initial method for this class.
	 *
	 * @since BuddyBoss 2.5.80
	 *
	 * @return void
	 */
	public function initialize() {
		$this->tab_label = esc_html__( 'Advanced', 'buddyboss' );
		$this->tab_name  = 'bp-advanced';
		$this->tab_order = 90;
	}

	public function settings_save() {

		// Get old values for cpt and check if it disabled then keep it and later will save it.
		// phpcs:ignore
		$bb_activity_load_type = isset( $_POST['bb_activity_load_type'] ) ? sanitize_text_field( wp_unslash( $_POST['bb_activity_load_type'] ) ) : '';
		bp_update_option( 'bb_activity_load_type', $bb_activity_load_type );

		// Get old telemetry setting value.
		$old_telemetry_reporting = bp_get_option( 'bb_advanced_telemetry_reporting' );

		parent::settings_save();

		// After settings saved.
		$this->bb_admin_send_immediate_telemetry_on_complete( $old_telemetry_reporting );
	}

	/**
	 * Register setting fields
	 *
	 * @since BuddyBoss 2.5.80
	 *
	 * @return void
	 */
	public function register_fields() {
		$this->add_section( 'bb_performance_general', __( 'General', 'buddyboss' ), '', 'bb_admin_performance_general_setting_tutorial' );
		$this->add_field( 'bb_ajax_request_page_load', __( 'Page requests', 'buddyboss' ), 'bb_admin_performance_setting_general_callback', 'intval' );

		if ( bp_is_active( 'activity' ) ) {
			$this->add_section( 'bb_performance_activity', __( 'Activity', 'buddyboss' ), '', 'bb_admin_performance_activity_setting_tutorial' );
			$this->add_field( 'bb_load_activity_per_request', __( 'Activity loading', 'buddyboss' ), 'bb_admin_performance_setting_activity_callback', 'intval' );
		}

		$this->add_section(
			'bb_advanced_telemetry',
			__( 'Telemetry', 'buddyboss' ),
			'',
			array(
				$this,
				'bb_admin_advanced_telemetry_setting_tutorial',
			)
		);
		$this->add_field(
			'bb_advanced_telemetry_reporting',
			__( 'Telemetry', 'buddyboss' ),
			array(
				$this,
				'bb_admin_advanced_setting_telemetry_callback',
			),
			'string'
		);

		/**
		 * Fires to register Performance tab settings fields and section.
		 *
		 * @since BuddyBoss 2.5.80
		 *
		 * @param Object $this BB_Admin_Setting_Performance.
		 */
		do_action( 'bb_admin_setting_performance_register_fields', $this );
	}

	/**
	 * Displays a tutorial link for telemetry settings.
	 *
	 * @since BuddyBoss 2.7.40
	 */
	public function bb_admin_advanced_telemetry_setting_tutorial() {
		?>
		<p>
			<a class="button bb-button_filled bb-telemetry-tutorial-link" 
				href="<?php echo esc_url( 'https://www.buddyboss.com/usage-tracking/?utm_source=product&utm_medium=platform&utm_campaign=telemetry' ); ?>" 
				target="_blank">
				<?php esc_html_e( 'About Telemetry', 'buddyboss' ); ?>
			</a>
		</p>
		<?php
	}

	/**
	 * Outputs the telemetry setting fields.
	 *
	 * @since BuddyBoss 2.7.40
	 */
	public function bb_admin_advanced_setting_telemetry_callback() {
		$bb_advanced_telemetry_reporting = bp_get_option( 'bb_advanced_telemetry_reporting', 'complete' );
		$telemetry_modes                 = array(
			'complete'  => array(
				'label'    => esc_html__( 'Complete reporting', 'buddyboss' ),
				'name'     => 'bb_advanced_telemetry_reporting',
				'value'    => 'complete',
				'id'       => 'complete_reporting',
				'checked'  => 'complete' === $bb_advanced_telemetry_reporting,
				'notice'   => sprintf(
					/* translators: %1$s and %2$s wrap the phrase "disable telemetry" in a clickable link. */
					esc_html__( 'Telemetry helps us gather usage statistics and information about your configuration and the features and functionality you use. We aggregate this information to help us improve our product and associate it with your customer record to help us serve you better. We do not gather or send any of your users\' personally identifiable information. To stop contributing towards improving the product, you can %1$sdisable telemetry%2$s.', 'buddyboss' ),
					'<a class="bb-disable-telemetry-link" href="#">',
					'</a>'
				),
				'disabled' => false,
			),
			'anonymous' => array(
				'label'    => esc_html__( 'Anonymous reporting', 'buddyboss' ),
				'name'     => 'bb_advanced_telemetry_reporting',
				'value'    => 'anonymous',
				'id'       => 'anonymous_reporting',
				'checked'  => 'anonymous' === $bb_advanced_telemetry_reporting,
				'notice'   => sprintf(
					/* translators: %1$s and %2$s wrap the phrase "disable telemetry" in a clickable link. */
					esc_html__( 'Telemetry helps us gather usage statistics and information about your configuration and the features and functionality you use. We aggregate this information to help us improve our product. By choosing anonymous reporting, your data will not be associated with your customer record, and the way we serve you will be less relevant to you. We do not gather or send any of your users\' personally identifiable information. If you stop contributing towards improving the product, you can %1$sdisable telemetry%2$s.', 'buddyboss' ),
					'<a class="bb-disable-telemetry-link" href="#">',
					'</a>'
				),
				'disabled' => false,
			),
		);

		if ( ! empty( $telemetry_modes ) && is_array( $telemetry_modes ) ) {
			$notice_text = '';
			foreach ( $telemetry_modes as $telemetry_mode ) {
				?>
				<label for="<?php echo esc_attr( $telemetry_mode['id'] ); ?>" class="<?php echo esc_attr( ! empty( $telemetry_mode['disabled'] ) ? 'disabled' : '' ); ?>">
					<input name="<?php echo esc_attr( $telemetry_mode['name'] ); ?>"
						id="<?php echo esc_attr( $telemetry_mode['id'] ); ?>"
						type="radio"
						value="<?php echo esc_attr( $telemetry_mode['value'] ); ?>"
						data-current-val="<?php echo esc_attr( $telemetry_mode['value'] ); ?>"
						data-notice="<?php /* phpcs:ignore */ echo ! empty( $telemetry_mode['notice'] ) ? htmlspecialchars( $telemetry_mode['notice'], ENT_QUOTES, 'UTF-8' ) : ''; ?>"
						<?php
						checked( $telemetry_mode['checked'] );
						?>
					/>
					<?php echo esc_html( $telemetry_mode['label'] ); ?>
				</label>
				<?php
				if ( ! empty( $telemetry_mode['checked'] ) || ( 'disable' === $bb_advanced_telemetry_reporting && 'complete' === $telemetry_mode['value'] ) ) {
					$notice_text = $telemetry_mode['notice'];
				}
			}

			if ( ! empty( $notice_text ) ) {
				?>
				<p class="description bb-telemetry-mode-description">
					<?php echo wp_kses_post( $notice_text ); ?>
				</p>
				<?php
			}
		}
		?>
		<div class='bb-setting-telemetry-no-reporting <?php echo ( 'disable' !== $bb_advanced_telemetry_reporting ) ? esc_attr( 'bp-hide' ) : ''; ?>'>
			<br>
			<label for="no_reporting">
				<input name="bb_advanced_telemetry_reporting" id="disable_reporting" type="radio" value="disable" <?php checked( $bb_advanced_telemetry_reporting, 'disable' ); ?>/>
				<?php esc_html_e( 'Disable telemetry', 'buddyboss' ); ?>
			</label>
			<p class="description">
				<?php
				esc_html_e(
					'Disabling telemetry will stop gathering and reporting usage statistics 
				about your configuration and the features and functionality you use. By disabling telemetry, 
				you will not be contributing towards the improvement of the product and the way we serve 
				you will be less relevant to you.',
					'buddyboss'
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Checks old values and compares if it changed to complete reporting then send telemetry immediately.
	 *
	 * @param string $old_telemetry_reporting Old telemetry setting value.
	 *
	 * @since BuddyBoss 2.7.40
	 */
	public function bb_admin_send_immediate_telemetry_on_complete( $old_telemetry_reporting ) {
		check_admin_referer( $this->tab_name . '-options' );

		// Check if it changed to complete reporting then send telemetry immediately.
		$new_telemetry_reporting = isset( $_POST['bb_advanced_telemetry_reporting'] ) ? sanitize_text_field( wp_unslash( $_POST['bb_advanced_telemetry_reporting'] ) ) : '';

		// Check if telemetry reporting has changed.
		if ( $old_telemetry_reporting === $new_telemetry_reporting ) {
			return;
		}

		// Dismiss telemetry notice if reporting status has changed.
		update_option( 'bb_telemetry_notice_dismissed', 1 );

		if ( 'complete' === $new_telemetry_reporting && class_exists( 'BB_Telemetry' ) ) {

			// Clear single scheduled cron.
			if ( wp_next_scheduled( 'bb_telemetry_report_single_cron_event' ) ) {
				wp_clear_scheduled_hook( 'bb_telemetry_report_single_cron_event' );
			}

			$bb_telemetry = BB_Telemetry::instance();
			$bb_telemetry->bb_send_telemetry_report_to_analytics();
		}
	}
}

// Class instance.
return new BB_Admin_Setting_Performance();

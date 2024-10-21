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
		$bb_activity_load_type = isset( $_POST['bb_activity_load_type'] ) ? sanitize_text_field( wp_unslash( $_POST['bb_activity_load_type'] ) ) : '';
		bp_update_option( 'bb_activity_load_type', $bb_activity_load_type );

		parent::settings_save();
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
			__( 'Telemetry', 'buddyboss' ), '',
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
	 * @since BuddyBoss [BBVERSION]
	 */
	public function bb_admin_advanced_telemetry_setting_tutorial() {
		?>
		<p>
			<a class="button" href="
		<?php
			echo esc_url(
				bp_get_admin_url(
					add_query_arg(
						array(
							'page'    => 'bp-help',
							'article' => 127427, // @todo: update when release.
						),
						'admin.php'
					)
				)
			);
			?>
		"><?php esc_html_e( 'View Tutorial', 'buddyboss' ); ?></a>
		</p>
		<?php
	}

	/**
	 * Outputs the telemetry setting fields.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function bb_admin_advanced_setting_telemetry_callback() {
		$bb_advanced_telemetry_reporting = bp_get_option( 'bb_advanced_telemetry_reporting', 'complete' );
		?>
		<fieldset>
			<legend class="screen-reader-text">
				<span><?php echo __( 'Complete Reporting', 'buddyboss' ); ?></span>
			</legend>
			<label for="full_reporting_feedback">
				<input name="bb_advanced_telemetry_reporting" id="complete_reporting" type="radio" value="complete" <?php checked( $bb_advanced_telemetry_reporting, 'complete' ); ?>/>
				<?php esc_html_e( 'Complete reporting', 'buddyboss' ); ?>
			</label>
			<p class="description">
				<?php esc_html_e( 'Telemetry helps us gather usage statistics and information about your 
				configuration and the features and functionality you use. We aggregate this information to help us 
				improve our product and associate it with your customer record to help us serve you better. 
				We do not gather or send any of your users\' personally identifiable information. 
				To stop contributing towards improving the product you can disable telemetry.', 'buddyboss' ); ?>
			</p>
			<br>
			<label for="anonymous_reporting">
				<input name="bb_advanced_telemetry_reporting" id="anonymous_reporting" type="radio" value="anonymous" <?php checked( $bb_advanced_telemetry_reporting, 'anonymous' ); ?>/>
				<?php esc_html_e( 'Anonymous reporting', 'buddyboss' ); ?>
			</label>
			<p class="description">
				<?php esc_html_e( 'Telemetry helps us gather usage statistics and information about your 
				configuration and the features and functionality you use. We aggregate this information to help us 
				improve our product. By choosing anonymous reporting, your data will not be associated with your 
				customer record, and the way we serve you will be less relevant to you. We do not gather or 
				send any of your users\' personally identifiable information. If you stop contributing towards 
				improving the product, you can disable telemetry.', 'buddyboss' ); ?>
			</p>
			<br>
			<label for="no_reporting">
				<input name="bb_advanced_telemetry_reporting" id="disable_reporting" type="radio" value="disable" <?php checked( $bb_advanced_telemetry_reporting, 'disable' ); ?>/>
				<?php esc_html_e( 'Disable telemetry', 'buddyboss' ); ?>
			</label>
			<p class="description">
				<?php esc_html_e( 'Disabling telemetry will stop gathering and reporting usage statistics 
				about your configuration and the features and functionality you use. By disabling telemetry, 
				you will not be contributing towards the improvement of the product and the way we serve 
				you will be less relevant to you.', 'buddyboss' ); ?>
			</p>
		</fieldset>
		<?php
	}
}

// Class instance.
return new BB_Admin_Setting_Performance();

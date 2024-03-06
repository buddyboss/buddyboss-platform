<?php
/**
 * Recaptcha integration admin tab.
 *
 * @since   BuddyBoss 2.5.60
 * @package BuddyBoss\Recaptcha
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Setup Recaptcha integration admin tab class.
 *
 * @since BuddyBoss 2.5.60
 */
class BB_Recaptcha_Admin_Tab extends BP_Admin_Integration_tab {

	/**
	 * Current section.
	 *
	 * @var $current_section
	 */
	protected $current_section;

	/**
	 * Initialize.
	 *
	 * @since BuddyBoss 2.5.60
	 */
	public function initialize() {
		$this->tab_order       = 53;
		$this->current_section = 'bb_recaptcha-integration';
		$this->intro_template  = $this->root_path . '/templates/admin/integration-tab-intro.php';

		add_filter( 'bb_admin_icons', array( $this, 'admin_setting_icons' ), 10, 2 );
	}

	/**
	 * Recaptcha Integration is active?
	 *
	 * @since BuddyBoss 2.5.60
	 *
	 * @return bool
	 */
	public function is_active() {
		return (bool) apply_filters( 'bb_recaptcha_integration_is_active', true );
	}

	/**
	 * Recaptcha integration tab scripts.
	 *
	 * @since BuddyBoss 2.5.60
	 */
	public function register_admin_script() {

		$active_tab = bp_core_get_admin_active_tab();

		if ( 'bb-recaptcha' === $active_tab ) {
			$min     = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
			$rtl_css = is_rtl() ? '-rtl' : '';
			wp_enqueue_style( 'bb-recaptcha-admin', bb_recaptcha_integration_url( '/assets/css/bb-recaptcha-admin' . $rtl_css . $min . '.css' ), false, buddypress()->version );

			wp_enqueue_script( 'bb-recaptcha-admin', bb_recaptcha_integration_url( '/assets/js/bb-recaptcha-admin' . $min . '.js' ), array( 'jquery' ), buddypress()->version );
			wp_localize_script(
				'bb-recaptcha-admin',
				'bbRecaptchaAdmin',
				array(
					'ajax_url'            => admin_url( 'admin-ajax.php' ),
					'nonce'               => wp_create_nonce( 'bb-recaptcha-verification' ),
					'bb_recaptcha_ok'     => __( 'OK', 'buddyboss' ),
					'bb_recaptcha_cancel' => __( 'Cancel', 'buddyboss' ),
				)
			);
		}

		parent::register_admin_script();
	}

	/**
	 * Method to save the fields.
	 *
	 * @since 1.0.0
	 */
	public function settings_save() {

		$bb_recaptcha = isset( $_POST['bb_recaptcha'] ) ? map_deep( wp_unslash( $_POST['bb_recaptcha'] ), 'sanitize_text_field' ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		// Connection status.
		$verified = bb_recaptcha_connection_status();

		if ( ! empty( $bb_recaptcha ) ) {
			if ( ! empty( $bb_recaptcha['exclude_ip'] ) ) {
				$bb_recaptcha['exclude_ip'] = sanitize_textarea_field( wp_unslash( $_POST['bb_recaptcha']['exclude_ip'] ) );
			}
			if ( ! empty( $bb_recaptcha['score_threshold'] ) ) {
				if ( $bb_recaptcha['score_threshold'] < 0 ) {
					$bb_recaptcha['score_threshold'] = 0;
				}
				if ( $bb_recaptcha['score_threshold'] > 1 ) {
					$bb_recaptcha['score_threshold'] = 1;
				}
			} else {
				if ( 0 != $bb_recaptcha['score_threshold'] ) {
					$bb_recaptcha['score_threshold'] = 0.5;
				}
			}
			if ( isset( $bb_recaptcha['allow_bypass'] ) ) {
				$bb_recaptcha['bypass_text'] = trim( $bb_recaptcha['bypass_text'] );
				if ( ! empty( $bb_recaptcha['bypass_text'] ) ) {
					if ( strlen( $bb_recaptcha['bypass_text'] ) < 6 ) {
						$bb_recaptcha['bypass_text'] = '';
					}
					if ( strlen( $bb_recaptcha['bypass_text'] ) > 10 ) {
						$bb_recaptcha['bypass_text'] = '';
					}
				}
				if ( empty( $bb_recaptcha['bypass_text'] ) ) {
					$bb_recaptcha['allow_bypass'] = false;
				}
			}
			if ( empty( $bb_recaptcha['site_key'] ) || empty( $bb_recaptcha['secret_key'] ) ) {
				$verified = 'not-connected';
			}
			$bb_recaptcha['connection_status'] = $verified;
			bp_update_option( 'bb_recaptcha', $bb_recaptcha );
		}
	}

	/**
	 * Register setting fields for recaptcha integration.
	 *
	 * @since BuddyBoss 2.5.60
	 */
	public function register_fields() {

		$sections = $this->get_settings_sections();

		foreach ( $sections as $section_id => $section ) {

			// Only add section and fields if section has fields.
			$fields = $this->get_settings_fields_for_section( $section_id );

			if ( empty( $fields ) ) {
				continue;
			}

			$section_title     = ! empty( $section['title'] ) ? $section['title'] : '';
			$section_callback  = ! empty( $section['callback'] ) ? $section['callback'] : false;
			$tutorial_callback = ! empty( $section['tutorial_callback'] ) ? $section['tutorial_callback'] : false;
			$notice            = ! empty( $section['notice'] ) ? $section['notice'] : false;

			// Add the section.
			$this->add_section( $section_id, $section_title, $section_callback, $tutorial_callback, $notice );

			// Loop through fields for this section.
			foreach ( $fields as $field_id => $field ) {

				$field['args'] = isset( $field['args'] ) ? $field['args'] : array();

				if ( ! empty( $field['callback'] ) && ! empty( $field['title'] ) ) {
					$sanitize_callback = isset( $field['sanitize_callback'] ) ? $field['sanitize_callback'] : array();
					$this->add_field( $field_id, $field['title'], $field['callback'], $sanitize_callback, $field['args'] );
				}
			}
		}
	}

	/**
	 * Get setting sections for recaptcha integration.
	 *
	 * @since BuddyBoss 2.5.60
	 *
	 * @return array $settings Settings sections for recaptcha integration.
	 */
	public function get_settings_sections() {

		$status      = 'not-connected';
		$status_text = __( 'Not Connected', 'buddyboss' );
		$verified    = bb_recaptcha_connection_status();
		if ( ! empty( $verified ) && 'connected' === $verified ) {
			$status      = 'connected';
			$status_text = __( 'Connected', 'buddyboss' );
		}
		$html = '<div class="bb-recaptcha-status">' .
			'<span class="status-line ' . esc_attr( $status ) . '">' . esc_html( $status_text ) . '</span>' .
		'</div>';

		$settings = array(
			'bb_recaptcha_versions' => array(
				'page'              => 'recaptcha',
				'title'             => __( 'reCAPTCHA', 'buddyboss' ) . $html,
				'tutorial_callback' => array( $this, 'setting_callback_recaptcha_tutorial' ),
				'notice'            => sprintf(
				/* translators: recaptcha link */
					__( 'Check reCAPTCHA %s for usage statistics and monitor its performance. Adjust settings if necessary to maintain security.', 'buddyboss' ),
					'<a href="https://www.google.com/recaptcha/admin" target="_blank">' . esc_html__( 'Admin Console', 'buddyboss' ) . '</a>'
				),
			),
			'bb_recaptcha_settings' => array(
				'page'              => 'recaptcha',
				'title'             => __( 'reCAPTCHA Settings', 'buddyboss' ),
				'tutorial_callback' => array( $this, 'setting_callback_recaptcha_tutorial' ),
			),
		);

		$enabled_for = bb_recaptcha_recaptcha_versions();
		if ( 'recaptcha_v2' === $enabled_for ) {
			$settings['bb_recaptcha_design'] = array(
				'page'              => 'recaptcha',
				'title'             => __( 'reCAPTCHA Design', 'buddyboss' ),
				'tutorial_callback' => array( $this, 'setting_callback_recaptcha_tutorial' ),
			);
		}

		return $settings;
	}

	/**
	 * Get setting fields for section in recaptcha integration.
	 *
	 * @since BuddyBoss 2.5.60
	 *
	 * @param string $section_id Section ID.
	 *
	 * @return array|false $fields setting fields for section in recaptcha integration false otherwise.
	 */
	public function get_settings_fields_for_section( $section_id = '' ) {

		// Bail if section is empty.
		if ( empty( $section_id ) ) {
			return false;
		}

		$fields = $this->get_settings_fields();

		return isset( $fields[ $section_id ] ) ? $fields[ $section_id ] : false;
	}

	/**
	 * Register setting fields for recaptcha integration.
	 *
	 * @since BuddyBoss 2.5.60
	 *
	 * @return array $fields setting fields for pusher integration.
	 */
	public function get_settings_fields() {
		$verified    = bb_recaptcha_connection_status();
		$enabled_for = bb_recaptcha_recaptcha_versions();
		$actions     = bb_recaptcha_actions();

		$fields = array();

		$fields['bb_recaptcha_versions'] = array(
			'information'             => array(
				'title'             => esc_html__( 'Information', 'buddyboss' ),
				'callback'          => array( $this, 'setting_callback_recaptcha_information' ),
				'sanitize_callback' => 'string',
				'args'              => array( 'class' => 'hidden-header' ),
			),
			'versions'                => array(
				'title'             => esc_html__( 'Versions', 'buddyboss' ),
				'callback'          => array( $this, 'setting_callback_recaptcha_versions' ),
				'sanitize_callback' => 'string',
				'args'              => array(),
			),
			'v2_option'               => array(
				'title'             => esc_html__( 'reCAPTCHA v2 option', 'buddyboss' ),
				'callback'          => array( $this, 'setting_callback_recaptcha_v2_option' ),
				'sanitize_callback' => 'string',
				'args'              => array( 'class' => 'hidden-header field-button recaptcha_v2 ' . ( 'recaptcha_v2' === $enabled_for ? '' : 'bp-hide' ) ),
			),
			'bb-recaptcha-site-key'   => array(
				'title'             => __( 'Site Key', 'buddyboss' ),
				'callback'          => array( $this, 'settings_callback_recaptcha_site_key' ),
				'sanitize_callback' => 'string',
				'args'              => array(),
			),
			'bb-recaptcha-secret-key' => array(
				'title'             => __( 'Secret Key', 'buddyboss' ),
				'callback'          => array( $this, 'settings_callback_recaptcha_secret_key' ),
				'sanitize_callback' => 'string',
				'args'              => array(),
			),
			'bb-recaptcha-verify'     => array(
				'title'             => esc_html__( 'Verify', 'buddyboss' ),
				'callback'          => array( $this, 'setting_callback_recaptcha_verify' ),
				'sanitize_callback' => 'string',
				'args'              => array( 'class' => 'hidden-header field-button verify-row ' . ( 'connected' === $verified ? 'bp-hide' : '' ) ),
			),
		);

		$fields['bb_recaptcha_settings'] = array(
			'bb-recaptcha-score-threshold' => array(
				'title'             => esc_html__( 'Score Threshold', 'buddyboss' ),
				'callback'          => array( $this, 'setting_callback_score_threshold' ),
				'sanitize_callback' => 'absint',
				'args'              => array( 'class' => 'recaptcha_v3 ' . ( 'recaptcha_v2' === $enabled_for ? 'bp-hide' : '' ) ),
			),
			'bb-recaptcha-enabled-for'     => array(
				'title'    => esc_html__( 'Enabled For', 'buddyboss' ),
				'callback' => array( $this, 'setting_callback_enabled_for' ),
				'args'     => array(),
			),
			'bb-recaptcha-allow-bypass'    => array(
				'title'             => ' ',
				'callback'          => array( $this, 'setting_callback_allow_bypass' ),
				'sanitize_callback' => 'absint',
				'args'              => array( 'class' => 'bb_login_require ' . ( true === $actions['bb_login']['enabled'] ? '' : 'bp-hide' ) ),
			),
			'bb-recaptcha-language-code'   => array(
				'title'             => esc_html__( 'Language Code', 'buddyboss' ),
				'callback'          => array( $this, 'setting_callback_language_code' ),
				'sanitize_callback' => 'string',
				'args'              => array(),
			),
			'bb-recaptcha-conflict-mode'   => array(
				'title'    => esc_html__( 'No-Conflict Mode', 'buddyboss' ),
				'callback' => array( $this, 'setting_callback_conflict_mode' ),

				'args'     => array(),
			),
			'bb-recaptcha-exclude-ip'      => array(
				'title'             => esc_html__( 'Exclude IP', 'buddyboss' ),
				'callback'          => array( $this, 'setting_callback_exclude_ip' ),
				'sanitize_callback' => 'sanitize_textarea_field',
				'args'              => array(),
			),
		);

		$v2_option = bb_recaptcha_recaptcha_v2_option();

		$class_v2_checkbox  = 'recaptcha_v2_checkbox';
		$class_v2_invisible = 'recaptcha_v2_invisible';
		if ( 'v2_checkbox' === $v2_option ) {
			$class_v2_invisible .= ' bp-hide';
		} else {
			$class_v2_checkbox .= ' bp-hide';
		}

		if ( 'recaptcha_v2' === $enabled_for ) {
			$fields['bb_recaptcha_design'] = array(
				'bb-recaptcha-theme'          => array(
					'title'             => esc_html__( 'Theme', 'buddyboss' ),
					'callback'          => array( $this, 'setting_callback_theme' ),
					'sanitize_callback' => 'absint',
					'args'              => array( 'class' => $class_v2_checkbox ),
				),
				'bb-recaptcha-size'           => array(
					'title'    => esc_html__( 'Size', 'buddyboss' ),
					'callback' => array( $this, 'setting_callback_size' ),
					'args'     => array( 'class' => $class_v2_checkbox ),
				),
				'bb-recaptcha-badge-position' => array(
					'title'    => esc_html__( 'Badge Position', 'buddyboss' ),
					'callback' => array( $this, 'setting_callback_badge_position' ),
					'args'     => array( 'class' => $class_v2_invisible ),
				),
			);
		}

		return $fields;
	}

	/**
	 * Link to Recaptcha Settings tutorial.
	 *
	 * @since BuddyBoss 2.5.60
	 */
	public function setting_callback_recaptcha_tutorial() {
		?>
		<p>
			<a class="button" href="
			<?php
				echo esc_url(
					bp_get_admin_url(
						add_query_arg(
							array(
								'page'    => 'bp-help',
								'article' => '127314',
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
	 * Callback fields for recaptcha information.
	 *
	 * @since BuddyBoss 2.5.60
	 *
	 * @return void
	 */
	public function setting_callback_recaptcha_information() {
		?>
		<div class="show-full-width">
			<?php
			$verified = bb_recaptcha_connection_status();
			if ( ! empty( $verified ) && isset( $_GET['bb_verified'] ) ) {
				if ( 'connected' === $verified ) {
					?>
					<div class="bb-recaptcha-success show-full-width bb-success-section">
						<span><?php echo esc_html__( 'reCAPTCHA connected successfully.', 'buddyboss' ); ?></span>
					</div>
					<?php
				} else {
					?>
					<div class="bb-recaptcha-errors show-full-width bb-error-section">
						<span><?php echo esc_html__( 'Error verifying reCAPTCHA, Please try again.', 'buddyboss' ); ?></span>
					</div>
					<?php
				}
			}
			?>
			<span class="bb-recaptcha-prompt">
				<?php
				printf(
				/* translators: recaptcha link */
					esc_html__( 'Go to %s and log in with your Google account. Upon registration, you\'ll get a site key and secret key. Add these keys below to implement reCAPTCHA and protect your site from fraud, spam, and abuse.', 'buddyboss' ),
					'<a href="https://www.google.com/recaptcha/admin#list" target="_blank">' . esc_html__( 'reCAPTCHA website', 'buddyboss' ) . '</a>',
				)
				?>
			</span>
		</div>
		<?php
	}

	/**
	 * Callback function for versions in Recaptcha integration.
	 *
	 * @since BuddyBoss 2.5.60
	 */
	public function setting_callback_recaptcha_versions() {
		$enabled_for = bb_recaptcha_recaptcha_versions();
		?>
		<div class="recaptcha-version-fields">
			<input type="radio" name="bb_recaptcha[recaptcha_version]" id="recaptcha_v3" value="recaptcha_v3" <?php checked( $enabled_for, 'recaptcha_v3' ); ?>>
			<label for="recaptcha_v3"><?php esc_html_e( 'reCAPTCHA v3 (Recommended)', 'buddyboss' ); ?></label>
			<input type="radio" name="bb_recaptcha[recaptcha_version]" id="recaptcha_v2" value="recaptcha_v2" <?php checked( $enabled_for, 'recaptcha_v2' ); ?>>
			<label for="recaptcha_v2"><?php esc_html_e( 'reCAPTCHA v2', 'buddyboss' ); ?></label>
		</div>
		<?php
	}

	/**
	 * Callback function for recaptcha v2 options in Recaptcha integration.
	 *
	 * @since BuddyBoss 2.5.60
	 */
	public function setting_callback_recaptcha_v2_option() {
		$v2_option = bb_recaptcha_recaptcha_v2_option();
		?>
		<div class="recaptcha-version-fields recaptcha-v2-fields">
			<input type="radio" name="bb_recaptcha[v2_option]" id="v2_checkbox" value="v2_checkbox" <?php checked( $v2_option, 'v2_checkbox' ); ?>>
			<label for="v2_checkbox"><?php esc_html_e( 'Checkbox', 'buddyboss' ); ?></label>
			<input type="radio" name="bb_recaptcha[v2_option]" id="v2_invisible_badge" value="v2_invisible_badge" <?php checked( $v2_option, 'v2_invisible_badge' ); ?>>
			<label for="v2_invisible_badge"><?php esc_html_e( 'Invisible Badge', 'buddyboss' ); ?></label>
			<p class="description v2_checkbox_description <?php echo 'v2_checkbox' === $v2_option ? '' : 'bp-hide'; ?>"><?php esc_html_e( 'Validate request with the "I\'m not a robot" checkbox', 'buddyboss' ); ?></p>
			<p class="description v2_invisible_badge_description <?php echo 'v2_invisible_badge' === $v2_option ? '' : 'bp-hide'; ?>"><?php esc_html_e( 'Shows invisible reCaptcha badge. It is invoked directly when the user clicks on an existing button on your site.', 'buddyboss' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Callback function for site key in Recaptcha integration.
	 *
	 * @since BuddyBoss 2.5.60
	 */
	public function settings_callback_recaptcha_site_key() {
		$site_key = bb_recaptcha_site_key();
		?>
		<div class="password-toggle">
			<input name="bb_recaptcha[site_key]" id="bb-recaptcha-site-key" type="password"
					value="<?php echo esc_html( $site_key ); ?>"
					aria-label="<?php esc_html_e( 'Site Key', 'buddyboss' ); ?>"
					data-old-value="<?php echo esc_html( $site_key ); ?>"
			/>
			<button type="button" class="button button-secondary bb-hide-pw hide-if-no-js" data-toggle="0">
				<span class="bb-icon bb-icon-eye-small" aria-hidden="true"></span>
			</button>
		</div>
		<?php
	}

	/**
	 * Callback function for secret key in Recaptcha integration.
	 *
	 * @since BuddyBoss 2.5.60
	 */
	public function settings_callback_recaptcha_secret_key() {
		$secret_key = bb_recaptcha_secret_key();
		?>
		<div class="password-toggle">
			<input name="bb_recaptcha[secret_key]" id="bb-recaptcha-secret-key" type="password"
					value="<?php echo esc_html( $secret_key ); ?>"
					aria-label="<?php esc_html_e( 'Secret Key', 'buddyboss' ); ?>"
					data-old-value="<?php echo esc_html( $secret_key ); ?>"
			/>
			<button type="button" class="button button-secondary bb-hide-pw hide-if-no-js" data-toggle="0">
				<span class="bb-icon bb-icon-eye-small" aria-hidden="true"></span>
			</button>
		</div>
		<?php
	}

	/**
	 * Callback function for verify button in Recaptcha integration.
	 *
	 * @since BuddyBoss 2.5.60
	 */
	public function setting_callback_recaptcha_verify() {
		$verified       = bb_recaptcha_connection_status();
		$site_key       = bb_recaptcha_site_key();
		$secret_key     = bb_recaptcha_secret_key();
		$verify_disable = '';
		if (
			'connected' === $verified ||
			( empty( $site_key ) && empty( $secret_key ) )
		) {
			$verify_disable = 'disabled="disabled"';
		}
		$enabled_for = bb_recaptcha_recaptcha_versions();
		$v2_option   = bb_recaptcha_recaptcha_v2_option();

		$v3_class = 'bp-hide';
		if (
			'recaptcha_v3' === $enabled_for ||
			(
				'recaptcha_v2' === $enabled_for &&
				'v2_invisible_badge' === $v2_option
			)
		) {
			$v3_class = '';
		}

		$v2_class = 'bp-hide';
		if ( 'recaptcha_v2' === $enabled_for && 'v2_checkbox' === $v2_option ) {
			$v2_class = '';
		}
		?>
		<div class="show-verify">
			<button type="button" class="button recaptcha-verification" <?php echo esc_attr( $verify_disable ); ?>>
				<?php esc_html_e( 'Verify', 'buddyboss' ); ?>
			</button>
		</div>
		<div id="bp-hello-backdrop" style="display: none;"></div>
		<div id="bp-hello-container" class="bp-hello-recaptcha" role="dialog" aria-labelledby="bp-hello-title" style="display: none;">
			<div class="bp-hello-header">
				<div class="bp-hello-title">
					<h2 id="bp-hello-title" tabindex="-1">
						<?php esc_html_e( 'Verify reCAPTCHA', 'buddyboss' ); ?>
					</h2>
				</div>
				<div class="bp-hello-close">
					<button type="button" class="close-modal button">
						<i class="bb-icon-f bb-icon-times"></i>
					</button>
				</div>
			</div>
			<div class="bp-hello-content">
				<div id="bp-hello-content-recaptcha_v3" class="bp-hello-recaptcha-content-container <?php echo esc_attr( $v3_class ); ?>">
					<div class="verifying_token loading">
						<img src="<?php echo bb_recaptcha_integration_url( 'assets/images/recaptcha.png' ); ?>" alt="" class="recaptcha-verify-icon" />
						<p>
							<?php esc_html_e( 'Verifying reCAPTCHA token', 'buddyboss' ); ?>
						</p>
					</div>
					<div class="verified_token" style="display:none;">
						<p>
							<?php esc_html_e( 'reCAPTCHA token is ready, click submit to verify', 'buddyboss' ); ?>
						</p>
					</div>
				</div>
				<div id="bp-hello-content-recaptcha_v2" class="bp-hello-recaptcha-content-container <?php echo esc_attr( $v2_class ); ?>">
					<div class="verifying_token" id="verifying_token"></div>
				</div>
				<div class="bb-popup-buttons">
					<button type="submit" id="recaptcha_submit" class="button button-primary" disabled="disabled">
						<?php esc_html_e( 'Submit', 'buddyboss' ); ?>
					</button>
					<button id="recaptcha_cancel" class="button">
						<?php esc_html_e( 'Cancel', 'buddyboss' ); ?>
					</button>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Callback fields for recaptcha score threshold.
	 *
	 * @since BuddyBoss 2.5.60
	 *
	 * @return void
	 */
	public function setting_callback_score_threshold() {
		?>
		<input name="bb_recaptcha[score_threshold]" id="bb-recaptcha-score-threshold" type="number" min="0" max="1" step="0.1" value="<?php echo esc_attr( bb_recaptcha_setting( 'score_threshold', 0.5 ) ); ?>" required/>
		<p class="description">
			<?php
			esc_html_e( 'reCAPTCHA v3 provides a score for every request seamlessly, without causing user friction. Input a risk score options 0, 0.5 and 1 in the field above to evaluate the probability of being identified as a bot.', 'buddyboss' );
			?>
		</p>
		<?php
	}

	/**
	 * Callback fields for recaptcha enable or not.
	 *
	 * @since BuddyBoss 2.5.60
	 *
	 * @return void
	 */
	public function setting_callback_enabled_for() {
		$actions = bb_recaptcha_actions();

		foreach ( $actions as $action => $setting ) {

			$disabled = ! empty( $setting['disabled'] ) ? ' disabled="disabled"' : '';
			$checked  = bp_enable_site_registration() && ! empty( $setting['enabled'] ) ? ' checked="checked"' : '';

			echo '<input id="recaptcha_' . esc_attr( $action ) . '" name="bb_recaptcha[enabled_for][' . esc_attr( $action ) . ']" type="checkbox" value="1" ' . $disabled . $checked . '/>' .
				'<label for="recaptcha_' . esc_attr( $action ) . '">' . esc_html( $setting['label'] ) . '</label><br /><br />';
		}

		echo '<p class="description">' .
			sprintf(
					/* translators: registration setting link. */
				esc_html__( 'Select the pages to include in the reCAPTCHA submission. Make sure to %s if both registration and account activation are disabled.', 'buddyboss' ),
				'<a href="' . esc_url( bp_get_admin_url( 'admin.php?page=bp-settings&tab=bp-general#bp_registration' ) ) . '">' . esc_html__( 'Enable Registration', 'buddyboss' ) . '</a>'
			) .
			'</p>';
	}

	/**
	 * Callback fields for allow bypass recaptcha.
	 *
	 * @since BuddyBoss 2.5.60
	 *
	 * @return void
	 */
	public function setting_callback_allow_bypass() {
		$checked      = bb_recaptcha_allow_bypass_enable();
		$allow_bypass = false;
		if ( bb_recaptcha_is_enabled( 'bb_login' ) && $checked ) {
			$allow_bypass = true;
		}
		$bypass_text = bb_recaptcha_setting( 'bypass_text' );
		?>
		<input id="bb_recaptcha_allow_bypass" name="bb_recaptcha[allow_bypass]" type="checkbox" value="1" <?php checked( $checked ); ?> />
		<label for="bb_recaptcha_allow_bypass"><?php esc_html_e( 'Allow bypass, enter a 6 to 10-character string to customize your URL', 'buddyboss' ); ?></label>
		<input type="text" name="bb_recaptcha[bypass_text]" value="<?php echo esc_html( $bypass_text ); ?>" placeholder="<?php esc_attr_e( 'stringxs', 'buddyboss' ); ?>" <?php echo $allow_bypass ? '' : 'disabled="disabled"'; ?> minlength="6" maxlength="10" required>
		<p class="description"><?php esc_html_e( 'The bypass URL enables you to bypass reCAPTCHA in case of issues. We recommend keeping the link below securely stored for accessing your site.', 'buddyboss' ); ?></p>
		<div class="copy-toggle <?php echo $allow_bypass ? '' : 'bp-hide'; ?>">
			<?php
			if ( empty( $bypass_text ) ) {
				$bypass_text = 'xxUNIQUE_STRINGXS';
			}
			$domain_name = wp_login_url() . '?bypass_captcha=';
			?>
			<a href="<?php echo esc_attr( $domain_name . $bypass_text ); ?>" class="copy-toggle-text" data-domain="<?php echo esc_attr( $domain_name ); ?>"><?php echo esc_attr( $domain_name . $bypass_text ); ?></a>
			<span role="button" class="bb-recaptcha-copy-button hide-if-no-js" data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'Copy', 'buddyboss' ); ?>" data-copied-text="<?php esc_attr_e( 'Copied', 'buddyboss' ); ?>">
				<i class="bb-icon-l bb-icon-copy"></i>
			</span>
		</div>
		<?php
	}

	/**
	 * Callback fields for recaptcha language code.
	 *
	 * @since BuddyBoss 2.5.60
	 *
	 * @return void
	 */
	public function setting_callback_language_code() {
		$languages = bb_recaptcha_languages();
		$language  = bb_recaptcha_setting( 'language_code', 'en' );
		?>
		<select name="bb_recaptcha[language_code]" id="bb-recaptcha-language-code">
			<?php
			foreach ( $languages as $code => $label ) {
				echo '<option value="' . esc_attr( $code ) . '" ' . selected( $language, $code, false ) . '>' . esc_html( $label ) . '</option>';
			}
			?>
		</select>
		<p class="description">
			<?php
			printf(
			/* translators: Language code link. */
				esc_html__( 'Choose a language for reCAPTCHA V3 when shown. Find %s in the reCAPTCHA documentation.', 'buddyboss' ),
				'<a href="https://developers.google.com/recaptcha/docs/language" target="_blank">' . esc_html__( 'Language codes', 'buddyboss' ) . '</a>'
			)
			?>
		</p>
		<?php
	}

	/**
	 * Callback fields for recaptcha conflict mode.
	 *
	 * @since BuddyBoss 2.5.60
	 *
	 * @return void
	 */
	public function setting_callback_conflict_mode() {
		$checked = bb_recaptcha_conflict_mode();
		?>
		<input id="bb-recaptcha-conflict-mode" name="bb_recaptcha[conflict_mode]" type="checkbox" value="1" <?php checked( $checked ); ?> />
		<label for="bb-recaptcha-conflict-mode"><?php esc_html_e( 'Allow no-conflict mode to prevent compatibility conflicts', 'buddyboss' ); ?></label>
		<p class="description"><?php esc_html_e( 'When checked, other instances of reCAPTCHA are forcefully removed to prevent conflicts. Only enable this option if your site is experiencing compatibility issues or if instructed to do so by support.', 'buddyboss' ); ?></p>
		<?php
	}

	/**
	 * Callback fields for recaptcha exclude ip.
	 *
	 * @since BuddyBoss 2.5.60
	 *
	 * @return void
	 */
	public function setting_callback_exclude_ip() {
		?>
		<label for="bb-recaptcha-exclude-ip"><?php esc_html_e( 'Enter the IP addresses that you want to skip from captcha submission. Enter one IP per line.', 'buddyboss' ); ?></label>
		<textarea rows="3" cols="50" name="bb_recaptcha[exclude_ip]" id="bb-recaptcha-exclude-ip"><?php echo esc_textarea( bb_recaptcha_setting( 'exclude_ip' ) ); ?></textarea>
		<?php
	}

	/**
	 * Added icon for the recaptcha admin settings.
	 *
	 * @since BuddyBoss 2.5.60
	 *
	 * @param string $meta_icon Icon class.
	 * @param string $id        Section ID.
	 *
	 * @return string
	 */
	public function admin_setting_icons( $meta_icon, $id = '' ) {
		if (
			! empty( $id ) &&
			in_array( $id, array( 'bb_recaptcha_versions', 'bb_recaptcha_settings', 'bb_recaptcha_design', true ) )
		) {
			$meta_icon = 'bb-icon-i bb-icon-brand-google';
		}

		return $meta_icon;
	}

	/**
	 * Callback fields for recaptcha theme for v2 version.
	 *
	 * @since BuddyBoss 2.5.60
	 *
	 * @return void
	 */
	public function setting_callback_theme() {
		$v2_option  = bb_recaptcha_recaptcha_v2_option();
		$connection = bb_recaptcha_connection_status();
		?>
		<div class="bb-grid-style-outer">
			<?php
			new BB_Admin_Setting_Fields(
				array(
					'type'        => 'radio',
					'id'          => 'bb-recaptcha-theme-style-',
					'label'       => esc_html__( 'Theme', 'buddyboss' ),
					'disabled'    => 'v2_checkbox' !== $v2_option || 'connected' !== $connection,
					'opt_wrapper' => true,
					'name'        => 'bb_recaptcha[theme]',
					'value'       => bb_recaptcha_setting( 'theme', 'light' ),
					'options'     => array(
						'light' => array(
							'label' => esc_html__( 'Light', 'buddyboss' ),
							'class' => 'option opt-light',
						),
						'dark'  => array(
							'label' => esc_html__( 'Dark', 'buddyboss' ),
							'class' => 'option opt-dark',
						),
					),
				)
			);
			?>
		</div>
		<p class="description"><?php echo esc_html__( 'Select the style of your reCAPTCHA theme.', 'buddyboss' ); ?></p>
		<?php
	}

	/**
	 * Callback fields for recaptcha size for v2 version.
	 *
	 * @since BuddyBoss 2.5.60
	 *
	 * @return void
	 */
	public function setting_callback_size() {
		$v2_option  = bb_recaptcha_recaptcha_v2_option();
		$connection = bb_recaptcha_connection_status();
		$v2_theme   = bb_recaptcha_v2_theme();
		$size_class = 'opt-size-light';
		if ( 'dark' === $v2_theme ) {
			$size_class = 'opt-size-dark';
		}
		?>
		<div class="bb-grid-style-outer">
			<?php
			new BB_Admin_Setting_Fields(
				array(
					'type'        => 'radio',
					'id'          => 'bb-recaptcha-size-',
					'label'       => esc_html__( 'Size', 'buddyboss' ),
					'disabled'    => 'v2_checkbox' !== $v2_option || 'connected' !== $connection,
					'opt_wrapper' => true,
					'name'        => 'bb_recaptcha[size]',
					'value'       => bb_recaptcha_setting( 'size', 'normal' ),
					'options'     => array(
						'normal'  => array(
							'label' => esc_html__( 'Normal', 'buddyboss' ),
							'class' => 'option opt-normal ' . esc_attr( $size_class ),
						),
						'compact' => array(
							'label' => esc_html__( 'Compact', 'buddyboss' ),
							'class' => 'option opt-compact ' . esc_attr( $size_class ),
						),
					),
				)
			);
			?>
		</div>
		<p class="description"><?php echo esc_html__( 'Select the size of your reCAPTCHA.', 'buddyboss' ); ?></p>
		<?php
	}

	/**
	 * Callback fields for recaptcha badge for v2 version.
	 *
	 * @since BuddyBoss 2.5.60
	 *
	 * @return void
	 */
	public function setting_callback_badge_position() {
		$v2_option  = bb_recaptcha_recaptcha_v2_option();
		$connection = bb_recaptcha_connection_status();
		?>
		<div class="bb-grid-style-outer">
			<?php
			new BB_Admin_Setting_Fields(
				array(
					'type'        => 'radio',
					'id'          => 'bb-recaptcha-badge-position-',
					'label'       => esc_html__( 'Badge Position', 'buddyboss' ),
					'disabled'    => 'v2_checkbox' === $v2_option || 'connected' !== $connection,
					'opt_wrapper' => true,
					'name'        => 'bb_recaptcha[badge_position]',
					'value'       => bb_recaptcha_setting( 'badge_position', 'bottomright' ),
					'options'     => array(
						'bottomright' => array(
							'label' => esc_html__( 'Bottom Right', 'buddyboss' ),
							'class' => 'option opt-bottom-right',
						),
						'bottomleft'  => array(
							'label' => esc_html__( 'Bottom Left', 'buddyboss' ),
							'class' => 'option opt-bottom-left',
						),
						'inline'      => array(
							'label' => esc_html__( 'Inline', 'buddyboss' ),
							'class' => 'option opt-inline',
						),
					),
				)
			);
			?>
		</div>
		<p class="description"><?php echo esc_html__( 'Select the position of your invisible reCAPTCHA badge.', 'buddyboss' ); ?></p>
		<?php
	}
}

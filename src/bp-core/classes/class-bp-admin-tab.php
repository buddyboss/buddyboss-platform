<?php
/**
 * Main BuddyBoss Admin Integration Tab Class.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BP_Admin_Tab' ) ) :

	#[\AllowDynamicProperties]
	abstract class BP_Admin_Tab {

		/**
		 * Global variable name that store the tab instances
		 *
		 * @since BuddyBoss 1.0.0
		 * @var string
		 */
		public $global_tabs_var = '';

		/**
		 * Admin menu page name
		 *
		 * @since BuddyBoss 1.0.0
		 * @var string
		 */
		public $menu_page = '';

		/**
		 * Tab label name
		 *
		 * @since BuddyBoss 1.0.0
		 * @var string
		 */
		public $tab_label = '';

		/**
		 * Tab url slug
		 *
		 * @since BuddyBoss 1.0.0
		 * @var string
		 */
		public $tab_name = '';

		/**
		 * Tab order
		 *
		 * @since BuddyBoss 1.0.0
		 * @var integer
		 */
		public $tab_order = 50;

		public function __construct() {
			$this->initialize();
			$this->register_tab();
			$this->register_hook();

			if ( $this->is_active() ) {
				$this->register_fields();
				do_action( 'bp_admin_tab_fields_registered', $this->tab_name, $this );
				add_action( 'bp_admin_init', array( $this, 'maybe_save_admin_settings' ), 100 );
			}
		}

		/**
		 * Cutom class initialization
		 *
		 * @since BuddyBoss 1.0.0
		 */
		public function initialize() {
			// nothing.
		}

		/**
		 * Determine whether this tab is active
		 *
		 * @since BuddyBoss 1.0.0
		 */
		public function is_active() {
			return true;
		}

		/**
		 * Register the tab to global variable
		 *
		 * @since BuddyBoss 1.0.0
		 */
		public function register_tab() {
			$GLOBALS[$this->global_tabs_var][ $this->tab_name ] = $this;
		}

		public function register_hook() {
			add_action( 'admin_head', array( $this, 'register_admin_script' ) );
		}

		public function register_admin_script() {
			wp_enqueue_script(
				'bp-admin',
				buddypress()->plugin_url . 'bp-core/admin/js/settings-page.js',
				array( 'jquery', 'jquery-ui-sortable' ),
				buddypress()->version,
				true
			);

			$screen         = get_current_screen();
			$screen_id      = $screen ? $screen->id : '';
			$email_template = '';

			if ( 'edit-bp-email' === $screen_id ) {

				$emails              = bp_email_get_schema();
				$descriptions        = bp_email_get_type_schema( 'description' );
				$total_missing_count = 0;
				$missing_email_label = array();

				ob_start();

				// Add these emails to the database.
				foreach ( $emails as $id => $email ) {
					if (
						term_exists( $id, bp_get_email_tax_type() ) &&
						get_terms(
							array(
								'taxonomy' => bp_get_email_tax_type(),
								'slug'     => $id,
								'fields'   => 'count',
							)
						) > 0
					) {
						continue;
					}

					// Some emails are multisite-only.
					if ( ! is_multisite() && isset( $email['args'] ) && ! empty( $email['args']['multisite'] ) ) {
						continue;
					}

					if ( array_key_exists( $id, $descriptions ) ) {
						$total_missing_count ++;
						$missing_email_label[] = $descriptions[ $id ];
					}
				}

				if ( $total_missing_count > 0 ) {
					?>
					<a href="javascript:void(0);" class="page-title-action btn-open-missing-email">
						<span class="count"><?php echo esc_html( $total_missing_count ); ?> </span>
						<?php
						if ( $total_missing_count > 1 ) {
							esc_html_e( 'Emails Missing', 'buddyboss' );
						} else {
							esc_html_e( 'Email Missing', 'buddyboss' );
						}
						?>
					</a>
					<div id="bp-hello-container" class="bp-hello-email" role="dialog" aria-labelledby="bp-hello-title" style="display: none;">
						<div class="bp-hello-header">
							<div class="bp-hello-title">
								<h2 id="bp-hello-title" tabindex="-1">
									<span class="count">
										<?php echo esc_html( $total_missing_count ); ?> </span>
									<?php
									if ( $total_missing_count > 1 ) {
										esc_html_e( 'Emails Missing', 'buddyboss' );
									} else {
										esc_html_e( 'Email Missing', 'buddyboss' );
									}
									?>
							</div>
							<div class="bp-hello-close">
								<button type="button" class="close-modal button bp-tooltip" data-bp-tooltip-pos="down" data-bp-tooltip="<?php esc_attr_e( 'Close pop-up', 'buddyboss' ); ?>">
									<?php esc_html_e( 'Close', 'buddyboss' ); ?>
								</button>
							</div>
						</div>

						<div class="bp-hello-content">
							<?php
							if ( ! empty( $missing_email_label ) ) {
								echo '<div class="missing-email-list"><ul>';

								foreach ( $missing_email_label as $label ) {
									echo '<li>' . wp_kses_post( $label ) . '</li>';
								}

								echo '</ul></div>';
							}
							?>
							<div class="bb-popup-buttons">
								<a href="
								<?php
								echo esc_url(
									bp_get_admin_url(
										add_query_arg(
											array(
												'page'     => 'bp-repair-community',
												'tab'      => 'bp-repair-community',
												'tool'     => 'bp-reinstall-emails',
												'scrollto' => 'bpreinstallemails',
											),
											'admin.php'
										)
									)
								);
								?>
								" class="button">
									<?php esc_html_e( 'Reset All Emails', 'buddyboss' ); ?>
								</a>
								<a href="
								<?php
								echo esc_url(
									bp_get_admin_url(
										add_query_arg(
											array(
												'page'     => 'bp-repair-community',
												'tab'      => 'bp-repair-community',
												'tool'     => 'bp-missing-emails',
												'scrollto' => 'bpmissingemails',
											),
											'admin.php'
										)
									)
								);
								?>
								" class="button button-primary">
									<?php esc_html_e( 'Install Missing Emails', 'buddyboss' ); ?>
								</a>
							</div>
						</div>
					</div>
					<?php
				}

				// Get the output buffer contents.
				$email_template = trim( ob_get_clean() );
			}

			$cover_dimensions = bb_attachments_get_default_custom_cover_image_dimensions();

			$localize_arg = array(
				'ajax_url'                     => admin_url( 'admin-ajax.php' ),
				'select_document'              => esc_js( __( 'Please upload a file to check the MIME Type.', 'buddyboss' ) ),
				'tools'                        => array(
					'default_data'  => array(
						'submit_button_message' => esc_js( __( 'Are you sure you want to import data? This action is going to alter your database. If this is a live website you may want to create a backup of your database first.', 'buddyboss' ) ),
						'clear_button_message'  => esc_js( __( 'Are you sure you want to delete all Default Data content? Content that was created by you and others, and not by this default data installer, will not be deleted.', 'buddyboss' ) ),
					),
					'repair_forums' => array(
						'validate_site_id_message' => esc_html__( 'Select site to repair the forums', 'buddyboss' ),
					),
				),
				'moderation'                   => array(
					'suspend_confirm_message'   => esc_js( __( 'Please confirm you want to suspend this member. Members who are suspended will be logged out and not allowed to login again. Their content will be hidden from all members in your network. Please allow a few minutes for this process to complete.', 'buddyboss' ) ),
					'unsuspend_confirm_message' => esc_js( __( 'Please confirm you want to unsuspend this member. Members who are unsuspended will be allowed to login again, and their content will no longer be hidden from other members in your network. Please allow a few minutes for this process to complete.', 'buddyboss' ) ),
				),
				'cover_size_alert'             => array(
					'profile' => esc_html__( 'Changing the Cover Image Size will reposition all of your members cover images. Are you sure you wish to save these changes?', 'buddyboss' ),
					'group'   => esc_html__( 'Changing the Cover Image Size will reposition all of your groups cover images. Are you sure you wish to save these changes?', 'buddyboss' ),
				),
				'avatar_settings'              => array(
					'wordpress_show_avatar'    => bp_get_option( 'show_avatars' ),
					'wordpress_avatar_default' => bp_get_option( 'avatar_default', 'mystery' ),
					'wordpress_avatar_types'   => array(
						'mystery',
						'blank',
						'gravatar_default',
						'identicon',
						'wavatar',
						'monsterid',
						'retro',
					),
				),
				'profile_group_cover'          => array(
					'select_file'       => esc_js( esc_html__( 'No file was uploaded.', 'buddyboss' ) ),
					'file_upload_error' => esc_js( esc_html__( 'There was a problem uploading the cover photo.', 'buddyboss' ) ),
					'feedback_messages' => array(
						0 => sprintf(
						/* translators: 1. Cover image width. 2. Cover image height. */
							esc_html__( 'Cover photo was uploaded successfully. For best results, upload an image that is %1$spx by %2$spx or larger.', 'buddyboss' ),
							(int) $cover_dimensions['width'],
							(int) $cover_dimensions['height']
						),
						1 => esc_html__( 'Cover photo was uploaded successfully.', 'buddyboss' ),
						2 => esc_html__( 'There was a problem deleting cover photo. Please try again.', 'buddyboss' ),
						3 => esc_html__( 'Cover photo was deleted successfully.', 'buddyboss' ),
					),
					'upload'            => array(
						'nonce'           => wp_create_nonce( 'bp-uploader' ),
						'action'          => 'bp_cover_image_upload',
						'object'          => ( 'bp-xprofile' === bp_core_get_admin_active_tab() ) ? 'user' : 'group',
						'item_id'         => 0,
						'item_type'       => 'default',
						'has_cover_image' => false,
					),
					'remove'            => array(
						'nonce'  => wp_create_nonce( 'bp_delete_cover_image' ),
						'action' => 'bp_cover_image_delete',
						'json'   => true,
					),
				),
				'member_directories'           => array(
					'profile_actions'    => function_exists( 'bb_get_member_directory_profile_actions' ) ? bb_get_member_directory_profile_actions() : array(),
					'profile_action_btn' => function_exists( 'bb_get_member_directory_primary_action' ) ? bb_get_member_directory_primary_action() : '',
				),
				'email_template'               => array(
					'html' => $email_template,
				),
				'bb_registration_restrictions' => array(
					'feedback_messages' => array(
						'empty'     => esc_html__( 'The rule content cannot be empty.', 'buddyboss' ),
						'duplicate' => esc_html__( 'The rule content cannot be duplicate.', 'buddyboss' ),
					),
				),
			);

			// Localize only post_type is member type and group type.
			if (
				0 === strpos( get_current_screen()->id, 'bp-group-type' ) ||
				0 === strpos( get_current_screen()->id, 'bp-member-type' )
			) {
				$localize_arg['post_type'] = get_current_screen()->id;
				if ( function_exists( 'buddyboss_theme_get_option' ) ) {
					$localize_arg['background_color'] = buddyboss_theme_get_option( 'label_background_color' );
					$localize_arg['color']            = buddyboss_theme_get_option( 'label_text_color' );
				}
			}

			wp_localize_script(
				'bp-admin',
				'BP_ADMIN',
				$localize_arg
			);

			$active_tab = bp_core_get_admin_active_tab();

			if ( 'bp-xprofile' === $active_tab || 'bp-groups' === $active_tab ) {

				wp_enqueue_style( 'thickbox' );
				wp_enqueue_script( 'media-upload' );

				// Get Avatar Uploader.
				bp_attachments_enqueue_scripts( 'BP_Attachment_Avatar' );
			}
		}

		/**
		 * Register setting fields belong to this group
		 *
		 * @since BuddyBoss 1.0.0
		 */
		public function register_fields() {
			// nothing.
		}

		/**
		 * Save the fields if it's form post request
		 *
		 * @since BuddyBoss 1.0.0
		 */
		public function maybe_save_admin_settings() {
			if ( ! $this->is_saving() ) {
				return false;
			}

			check_admin_referer( $this->tab_name . '-options' );

			$this->settings_save();
			do_action( 'bp_admin_tab_setting_save', $this->tab_name, $this );

			$this->settings_saved();
			do_action( 'bp_admin_tab_setting_saved', $this->tab_name, $this );
		}

		/**
		 * Determine whether current request is saving on the current tab
		 *
		 * @since BuddyBoss 1.0.0
		 */
		public function is_saving() {
			if ( ! isset( $_GET['page'] ) || ! isset( $_POST['submit'] ) ) {
				return false;
			}

			if ( $this->menu_page != $_GET['page'] ) {
				return false;
			}

			if ( $this->tab_name != $this->get_active_tab() ) {
				return false;
			}

			return true;
		}

		/**
		 * Method to save the fields
		 *
		 * By default it'll loop throught the setting group's fields, but allow
		 * extended classes to have their own logic if needed
		 *
		 * @since BuddyBoss 1.0.0
		 */
		public function settings_save() {
			global $wp_settings_fields;
			$fields = isset( $wp_settings_fields[ $this->tab_name ] ) ? (array) $wp_settings_fields[ $this->tab_name ] : array();

			foreach ( $fields as $section => $settings ) {
				foreach ( $settings as $setting_name => $setting ) {
					if (
						in_array(
							$setting_name,
							array(
								'bp-enable-private-network-public-content',
								'bb-enable-private-rss-feeds-public-content',
								'bb-enable-private-rest-apis-public-content',
							),
							true
						)
					) {
						$value = isset( $_POST[ $setting_name ] ) ? sanitize_textarea_field( wp_unslash( $_POST[ $setting_name ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
					} elseif (
						in_array(
							$setting_name,
							array(
								'bb-domain-restrictions',
								'bb-email-restrictions',
							),
							true
						)
					) {

						unset( $_POST[ $setting_name ]['placeholder_priority_index'] );
						$value = $_POST[ $setting_name ];

						if ( 'bb-domain-restrictions' === $setting_name ) {

							//Re-index as per priority.
							$value = array_values( $value );
						}

					} else {
						$value = isset( $_POST[ $setting_name ] ) ? ( is_array( $_POST[ $setting_name ] ) ? map_deep( wp_unslash( $_POST[ $setting_name ] ), 'sanitize_text_field' ) : sanitize_text_field( wp_unslash( $_POST[ $setting_name ] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
					}

					bp_update_option( $setting_name, $value );
				}
			}
		}

		/**
		 * Method trigger after data are saved
		 *
		 * @since BuddyBoss 1.0.0
		 */
		abstract public function settings_saved();

		/**
		 * Method that should return the current active tab
		 *
		 * @since BuddyBoss 1.0.0
		 */
		abstract public function get_active_tab();

		/**
		 * Return if the tab should be visible. Default to if there's any setting fields
		 *
		 * @since BuddyBoss 1.0.0
		 */
		public function is_tab_visible() {
			return $this->has_fields();
		}

		/**
		 * Return if this tab has setting fields
		 *
		 * @since BuddyBoss 1.0.0
		 */
		public function has_fields() {
			global $wp_settings_fields;

			return ! empty( $wp_settings_fields[ $this->tab_name ] );
		}

		/**
		 * Output the form html on the setting page (not including tab and page title)
		 *
		 * @since BuddyBoss 1.4.0
		 */
		public function form_html() {
			settings_fields( $this->tab_name );
			$this->bp_custom_do_settings_sections( $this->tab_name );

			if ( isset( $_GET ) && isset( $_GET['tab'] ) && 'bp-document' === $_GET['tab'] && 'bp-settings' === $_GET['page'] ) {
				?>
			<p class="submit">
				<input type="submit" name="submit" class="button-primary" value="<?php esc_attr_e( 'Save Settings', 'buddyboss' ); ?>" />
				<a class="button" href="
				<?php
				echo esc_url(
					bp_get_admin_url(
						add_query_arg(
							array(
								'page'    => 'bp-help',
								'article' => 87474,
							),
							'admin.php'
						)
					)
				);
				?>
				"><?php esc_html_e( 'View Tutorial', 'buddyboss' ); ?></a>
			</p>
				<?php
			} else {
				printf(
					'<p class="submit">
				<input type="submit" name="submit" class="button-primary" value="%s" />
			</p>',
					esc_attr__( 'Save Settings', 'buddyboss' )
				);
			}
		}

		/**
		 * Add a wp setting section into current tab. Chainable
		 *
		 * @since BuddyBoss 1.0.0
		 */
		public function add_section( $id, $title, $callback = '__return_null', $tutorial_callback = '', $notice = '' ) {
			global $wp_settings_sections;
			add_settings_section( $id, $title, $callback, $this->tab_name );
			$this->active_section = $id;
			if ( ! empty( $tutorial_callback ) ) {
				$wp_settings_sections[ $this->tab_name ][ $id ]['tutorial_callback'] = $tutorial_callback;
			}
			if ( ! empty( $notice ) ) {
				$wp_settings_sections[ $this->tab_name ][ $id ]['notice'] = $notice;
			}
			if ( function_exists( 'bb_admin_icons' ) ) {
				$meta_icon = bb_admin_icons( $id );
				if ( ! empty( $meta_icon ) ) {
					$wp_settings_sections[ $this->tab_name ][ $id ]['icon'] = $meta_icon;
				}
			}
			return $this;
		}

		/**
		 * Add a wp setting field to a wp setting section. Chainable
		 *
		 * @since BuddyBoss 1.0.0
		 */
		public function add_field( $name, $label, $callback, $field_args = array(), $callback_args = array(), $id = null ) {
			if ( ! $id ) {
				$id = $this->active_section;
			}

			add_settings_field( $name, $label, $callback, $this->tab_name, $id, $callback_args );
			register_setting( $this->tab_name, $name, $field_args );

			return $this;
		}

		/**
		 * Alias to add input text box field
		 *
		 * @since BuddyBoss 1.0.0
		 */
		public function add_input_field( $name, $label, $callback_args = array(), $field_args = 'sanitize_text_field', $id = null ) {
			$callback = array( $this, 'render_input_field_html' );

			$callback_args = bp_parse_args(
				$callback_args,
				array(
					'input_type'        => 'text',
					'input_name'        => $this->get_input_name( $name ),
					'input_id'          => $this->get_input_id( $name ),
					'input_description' => '',
					'input_value'       => $this->get_input_value( $name ),
					'input_placeholder' => '',
				)
			);

			return $this->add_field( $name, $label, $callback, $field_args, $callback_args, $id );
		}

		/**
		 * Alias to add input check box field
		 *
		 * @since BuddyBoss 1.0.0
		 */
		public function add_checkbox_field( $name, $label, $callback_args = array(), $field_args = 'intval', $id = null ) {
			$callback = array( $this, 'render_checkbox_field_html' );

			$callback_args = bp_parse_args(
				$callback_args,
				array(
					'input_name'        => $this->get_input_name( $name ),
					'input_id'          => $this->get_input_id( $name ),
					'input_text'        => '',
					'input_description' => '',
					'input_value'       => $this->get_input_value( $name, null ),
					'input_default'     => 0,
					'input_run_js'      => false,
				)
			);

			return $this->add_field( $name, $label, $callback, $field_args, $callback_args, $id );
		}

		/**
		 * Alias to add input select field
		 *
		 * @since BuddyBoss 1.0.0
		 */
		public function add_select_field( $name, $label, $callback_args = array(), $field_args = 'sanitize_text_field', $id = null ) {
			$callback = array( $this, 'render_select_field_html' );

			$callback_args = bp_parse_args(
				$callback_args,
				array(
					'input_name'        => $this->get_input_name( $name ),
					'input_id'          => $this->get_input_id( $name ),
					'input_options'     => array(),
					'input_description' => '',
					'input_value'       => $this->get_input_value( $name, null ),
					'input_default'     => 0,
				)
			);

			return $this->add_field( $name, $label, $callback, $field_args, $callback_args, $id );
		}

		/**
		 * Output the input field html based on the arguments
		 *
		 * @since BuddyBoss 1.0.0
		 */
		public function render_input_field_html( $args ) {
			printf(
				'<input name="%s" type="%s" id="%s" value="%s" placeholder="%s" class="regular-text" /> %s',
				$args['input_name'],
				$args['input_type'],
				$args['input_id'],
				$args['input_value'],
				$args['input_placeholder'],
				$args['input_description'] ? $this->render_input_description( $args['input_description'] ) : ''
			);
		}

		/**
		 * Output the checkbox field html based on the arguments
		 *
		 * @since BuddyBoss 1.0.0
		 */
		public function render_checkbox_field_html( $args ) {
			$input_value = is_null( $args['input_value'] ) ? $args['input_default'] : $args['input_value'];

			printf(
				'
				<input id="%1$s" name="%2$s" type="hidden" value="0" />
				<input id="%1$s" name="%2$s" type="checkbox" value="1" %3$s %4$s autocomplete="off"/>
				<label for="%1$s">%5$s</label>
				%6$s
			',
				$args['input_id'],
				$args['input_name'],
				checked( (bool) $input_value, true, false ),
				$args['input_run_js'] ? "data-run-js-condition=\"{$args['input_run_js']}\"" : '',
				$args['input_text'],
				$args['input_description'] ? $this->render_input_description( $args['input_description'] ) : ''
			);
		}

		/**
		 * Output the select field html based on the arguments
		 *
		 * @since BuddyBoss 1.0.0
		 */
		public function render_select_field_html( $args ) {
			$input_value = is_null( $args['input_value'] ) ? $args['input_default'] : $args['input_value'];
			$input_name  = $args['input_name'];

			printf(
				'<select name="%s" id="%s" autocomplete="off" %s>',
				$args['input_name'],
				$args['input_id'],
				isset( $args['input_run_js'] ) && $args['input_run_js'] ? "data-run-js-condition=\"{$args['input_run_js']}\"" : ''
			);

			foreach ( $args['input_options'] ?: array() as $key => $value ) {
				$selected = $input_value == $key ? 'selected' : '';
				printf( '<option value="%s" %s>%s</option>', $key, $selected, $value );
			}

			echo '</select>';

			if ( $args['input_description'] ) {
				echo $this->render_input_description( $args['input_description'] );
			}
		}

		protected function render_input_description( $text ) {
			return "<p class=\"description\">{$text}</p>";
		}

		protected function get_input_name( $name ) {
			return $name;
		}

		protected function get_input_id( $id ) {
			return sanitize_title( $id );
		}

		protected function get_input_value( $key, $default = '' ) {
			return bp_get_option( $key, $default );
		}

		/**
		 * Prints out all settings sections added to a particular settings page
		 *
		 * Part of the Settings API. Use this in a settings page callback function
		 * to output all the sections and fields that were added to that $page with
		 * add_settings_section() and add_settings_field()
		 *
		 * @global $wp_settings_sections Storage array of all settings sections added to admin pages
		 * @global $wp_settings_fields Storage array of settings fields and info about their pages/sections
		 * @since BuddyBoss 1.0.0
		 *
		 * @param string $page The slug name of the page whose settings sections you want to output
		 */
		public function bp_custom_do_settings_sections( $page ) {
			global $wp_settings_sections, $wp_settings_fields;

			if ( ! isset( $wp_settings_sections[ $page ] ) ) {
				return;
			}

			foreach ( (array) $wp_settings_sections[ $page ] as $section ) {
				echo "<div id='{$section['id']}' class='bp-admin-card section-{$section['id']}'>";
				$has_tutorial_btn = ( isset( $section['tutorial_callback'] ) && ! empty( $section['tutorial_callback'] ) ) ? 'has_tutorial_btn' : '';
				$has_icon         = ( isset( $section['icon'] ) && ! empty( $section['icon'] ) ) ? '<i class="' . esc_attr( $section['icon'] ) . '"></i>' : '';
				if ( $section['title'] ) {
					echo '<h2 class=' . esc_attr( $has_tutorial_btn ) . '>' . $has_icon .
						wp_kses_post( $section['title'] );

						if ( isset( $section['tutorial_callback'] ) && ! empty( $section['tutorial_callback'] ) ) {
						?>
							<div class="bbapp-tutorial-btn">
								<?php call_user_func( $section['tutorial_callback'], $section ); ?>
							</div>
							<?php
						}

					echo "</h2>\n";
				}

				if ( $section['callback'] ) {
					call_user_func( $section['callback'], $section );
				}

				if ( ! isset( $wp_settings_fields ) || ! isset( $wp_settings_fields[ $page ] ) || ! isset( $wp_settings_fields[ $page ][ $section['id'] ] ) ) {
					continue;
				}

				echo '<table class="form-table">';
					$this->bp_custom_do_settings_fields( $page, $section['id'] );
				echo '</table>';

				if ( isset( $section['notice'] ) && ! empty( $section['notice'] ) ) {
					?>
					<div class="display-notice bb-bottom-notice">
						<?php echo wp_kses_post( $section['notice'] ); ?>
					</div>
					<?php
				}
				echo '</table></div>';
			}
		}

		/**
		 * Print out the settings fields for a particular settings section
		 *
		 * Part of the Settings API. Use this in a settings page to output
		 * a specific section. Should normally be called by do_settings_sections()
		 * rather than directly.
		 *
		 * @global $wp_settings_fields Storage array of settings fields and their pages/sections
		 *
		 * @since BuddyBoss 1.0.0
		 *
		 * @param string $page Slug title of the admin page who's settings fields you want to show.
		 * @param string $section Slug title of the settings section who's fields you want to show.
		 */
		public function bp_custom_do_settings_fields( $page, $section ) {
			global $wp_settings_fields;

			if ( ! isset( $wp_settings_fields[ $page ][ $section ] ) ) {
				return;
			}

			foreach ( (array) $wp_settings_fields[ $page ][ $section ] as $field ) {
				$class = '';

				if ( ! empty( $field['args']['class'] ) ) {
					$class = ' class="' . esc_attr( $field['args']['class'] ) . '"';
				}

				echo "<tr{$class}>";

				if ( ! empty( $field['args']['label_for'] ) ) {
					echo '<th scope="row"><label for="' . esc_attr( $field['args']['label_for'] ) . '">' . $field['title'] . '</label></th>';
				} else {
					echo '<th scope="row">' . $field['title'] . '</th>';
				}

				echo '<td>';
				call_user_func( $field['callback'], $field['args'] );
				echo '</td>';
				echo '</tr>';
			}
		}
	}

endif;

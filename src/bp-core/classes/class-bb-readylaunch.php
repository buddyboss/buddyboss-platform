<?php
/**
 * Readylaunch class.
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Core
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BB_Readylaunch' ) ) {

	/**
	 * BuddyBoss Readylaunch object.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	#[\AllowDynamicProperties]
	class BB_Readylaunch {

		/**
		 * The single instance of the class.
		 *
		 * @since  BuddyBoss [BBVERSION]
		 *
		 * @access private
		 * @var self
		 */
		private static $instance = null;

		/**
		 * ReadyLaunch Settings.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		public $settings = array();

		/**
		 * Get the instance of this class.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return Controller|BB_Readylaunch|null
		 */
		public static function instance() {

			if ( null === self::$instance ) {
				$class_name     = __CLASS__;
				self::$instance = new $class_name();
			}

			return self::$instance;
		}

		/**
		 * Constructor method.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		public function __construct() {
			$this->settings = bb_get_enabled_readylaunch();

			// Register the ReadyLaunch menu.
			$this->bb_register_readylaunch_menus();

			// Register the ReadyLaunch widgets.
			$this->bb_register_readylaunch_widgets();

			add_action( 'bp_admin_init', array( $this, 'bb_core_admin_readylaunch_page_fields' ) );
			add_action( 'bp_admin_init', array( $this, 'bb_core_admin_maybe_save_readylaunch_settings' ), 100 );

			$enabled = $this->bb_is_readylaunch_enabled();
			if ( $enabled ) {
				add_filter( 'template_include',
					array(
						$this,
						'override_page_templates',
					),
					999999
				); // High priority so we have the last say here.

				// Remove BuddyPress template locations.
				remove_filter( 'bp_get_template_stack', 'bp_add_template_stack_locations' );

				// Add Readylaunch template locations.
				add_filter( 'bp_get_template_stack', array( $this, 'add_template_stack' ), PHP_INT_MAX );

				add_action( 'wp_enqueue_scripts', array( $this, 'bb_enqueue_scripts' ) );

				// Dequeue theme/plugins styles.
				add_action( 'wp_enqueue_scripts', array( $this, 'bb_dequeue_styles' ), 99999 );
				// Dequeue bbpress activity js.
				add_filter( 'bbp_is_single_topic', array( $this, 'bb_dequeue_bbpress_activity_js' ), 99999 );

				add_action( 'wp_ajax_bb_fetch_header_messages', array( $this, 'bb_fetch_header_messages' ) );
				add_action( 'wp_ajax_bb_fetch_header_notifications', array( $this, 'bb_fetch_header_notifications' ) );

				add_filter( 'heartbeat_received', array( $this, 'bb_heartbeat_unread_notifications' ), 12, 2 );
				add_filter( 'heartbeat_nopriv_received', array( $this, 'bb_heartbeat_unread_notifications' ), 12, 2 );

				add_action( 'wp_ajax_bb_mark_notification_read', array( $this, 'bb_mark_notification_read' ) );
			}
		}

		/**
		 * Check if ReadyLaunch is enabled for the current directory.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return bool True if ReadyLaunch is enabled, false otherwise.
		 */
		private function bb_is_readylaunch_enabled() {

			if (
				(
					bp_is_members_directory() &&
					! empty( $this->settings['members'] )
				) ||
				(
					bp_is_video_directory() &&
					! empty( $this->settings['video'] ) &&
					bp_is_current_component( 'video' )
				) ||
				(
					bp_is_media_directory() &&
					! empty( $this->settings['media'] ) &&
					bp_is_current_component( 'media' )
				) ||
				(
					bp_is_document_directory() &&
					! empty( $this->settings['document'] )
				) ||
				(
					bp_is_groups_directory() &&
					! empty( $this->settings['groups'] )
				) ||
				(
					bp_is_activity_directory() &&
					! empty( $this->settings['activity'] )
				)
			) {
				return true;
			}

			return false;
		}

		/**
		 * Register the ReadyLaunch menus.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		public function bb_register_readylaunch_menus() {
			// Define the menus and their respective theme locations.
			$menus = array(
				'bb-readylaunch'             => __( 'ReadyLaunch', 'buddyboss' ),
				'bb-top-readylaunchpanel'    => __( 'Top ReadyLaunchPanel', 'buddyboss' ),
				'bb-bottom-readylaunchpanel' => __( 'Bottom ReadyLaunchPanel', 'buddyboss' ),
			);

			foreach ( $menus as $theme_location => $menu_name ) {
				// Check if the menu already exists.
				$menu_exists = wp_get_nav_menu_object( $menu_name );

				// If the menu doesn't exist, create it.
				$menu_id = ! $menu_exists ? wp_create_nav_menu( $menu_name ) : $menu_exists->term_id;

				// Register the theme location if it has not been registered already.
				if ( ! has_nav_menu( $theme_location ) ) {
					register_nav_menu( $theme_location, $menu_name );
				}

				// If the menu exists and the theme location is ready, assign the menu to the location.
				$nav_menu_locations = get_theme_mod( 'nav_menu_locations', array() );
				if ( ! empty( $menu_id ) && ! isset( $nav_menu_locations[ $theme_location ] ) ) {
					set_theme_mod(
						'nav_menu_locations',
						array_merge(
							$nav_menu_locations,
							array( $theme_location => $menu_id )
						)
					);
				}
			}
		}

		/**
		 * Register the ReadyLaunch widgets.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		public function bb_register_readylaunch_widgets() {
			$sidebar_id = 'bb-readylaunch-sidebar';
			register_sidebar(
				array(
					'name'          => __( 'BB ReadyLaunch™ Sidebar', 'buddyboss' ),
					'id'            => $sidebar_id,
					'description'   => __( 'Add widgets here to appear in the right sidebar on ReadyLaunch pages. This sidebar is used to display additional content or tools specific to ReadyLaunch.', 'buddyboss' ),
					'before_widget' => '<div id="%1$s" class="widget %2$s">',
					'after_widget'  => '</div>',
					'before_title'  => '<h2 class="widget-title">',
					'after_title'   => '</h2>',
				)
			);
		}

		/**
		 * Adds settings fields for the ReadyLaunch admin page.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		public function bb_core_admin_readylaunch_page_fields() {
			global $wp_settings_sections;

			// Add the ReadyLaunch settings section.
			add_settings_section(
				'bb_readylaunch',
				__( 'ReadyLaunch', 'buddyboss' ),
				array( $this, 'bb_admin_readylaunch_pages_description' ),
				'bb-readylaunch'
			);

			add_settings_field(
				'bb_readylaunch',
				__( 'Global Design Settings', 'buddyboss' ),
				array( $this, 'bb_readylaunch_global_design_settings' ),
				'bb-readylaunch',
				'bb_readylaunch'
			);

			// Get the directory pages.
			$directory_pages = bp_core_admin_get_directory_pages();

			// Add an icon to the settings section if the function exists.
			if ( function_exists( 'bb_admin_icons' ) ) {
				$wp_settings_sections['bb-readylaunch']['bb_readylaunch']['icon'] = bb_admin_icons( 'bb_readylaunch' );
			}

			// Get the enabled ReadyLaunch pages and BuddyPress directory page IDs.
			$enabled_pages = $this->settings;
			$bp_pages      = bp_core_get_directory_page_ids( 'all' );
			$description   = '';

			// Loop through each directory page and add a settings field if applicable.
			foreach ( $directory_pages as $name => $label ) {
				if (
					! empty( $bp_pages[ $name ] ) ||
					(
						'new_forums_page' === $name &&
						! empty( bp_get_forum_page_id() )
					)
				) {
					add_settings_field( $name, $label, array(
						$this,
						'bb_enable_setting_callback_page_directory',
					), 'bb-readylaunch', 'bb_readylaunch', compact( 'enabled_pages', 'name', 'label', 'description' ) );
					register_setting( 'bb-readylaunch', $name, array(
						'default' => array(),
					) );
				}
			}
		}

		/**
		 * ReadyLaunch pages description callback.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		public function bb_admin_readylaunch_pages_description() {
		}

		/**
		 * ReadyLaunch global design settings callback.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		public function bb_readylaunch_global_design_settings() {
			$active_left_sidebar_section = bb_load_readylaunch()->bb_is_active_any_left_sidebar_section( false );
			if ( $active_left_sidebar_section ) {
				?>
				<tr class="bb-rl-admin-settings">
					<th scope="row">
						<?php esc_html_e( 'Left Sidebar', 'buddyboss' ); ?>
					</th>
					<td>
						<?php
						if ( ! empty( $active_left_sidebar_section['groups'] ) ) {
							?>
							<input type="checkbox" name="bb-readylaunch[groups_sidebar]" id="bb-readylaunch-groups-sidebar" value="1" <?php checked( $this->bb_is_sidebar_enabled_for_groups() ); ?> />
							<label for="enabled-meeting-webinars"><?php esc_html_e( 'Groups', 'buddyboss' ); ?></label>
							<br /><br />
							<?php
						}
						if ( ! empty( $active_left_sidebar_section['courses'] ) ) {
							?>
							<input type="checkbox" name="bb-readylaunch[courses_sidebar]" id="bb-readylaunch-courses-sidebar" value="1" <?php checked( $this->bb_is_sidebar_enabled_for_courses() ); ?> />
							<label for="enabled-meeting-webinars"><?php esc_html_e( 'Courses', 'buddyboss' ); ?></label>
							<br /><br />
							<?php
						}
						?>
					</td>
				</tr>
				<?php
			}
		}

		/**
		 * Pages drop downs callback.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array $args
		 */
		public function bb_enable_setting_callback_page_directory( $args ) {
			extract( $args );

			// Switch to the root blog if not already on it.
			if ( ! bp_is_root_blog() ) {
				switch_to_blog( bp_get_root_blog_id() );
			}

			$checked = ! empty( $this->settings ) && isset( $this->settings[ $name ] );

			// For the button.
			if ( 'button' === $name ) {
				printf(
					'<p><a href="%s" class="button">%s</a></p>',
					esc_url( $label['link'] ),
					esc_html( $label['label'] )
				);
			} else {
				printf(
					'<input type="checkbox" value="1" name="bb-readylaunch[%s]" %s />',
					esc_attr( $name ),
					checked( $checked, true, false )
				);
			}

			// Restore the current blog if switched.
			if ( ! bp_is_root_blog() ) {
				restore_current_blog();
			}
		}

		/**
		 * Save ReadyLaunch settings if applicable.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return bool False if settings are not saved, true otherwise.
		 */
		public function bb_core_admin_maybe_save_readylaunch_settings() {
			// Check if the page and submit parameters are set.
			if ( ! isset( $_GET['page'] ) || ! isset( $_POST['submit'] ) ) {
				return false;
			}

			// Check if the current page is the ReadyLaunch settings page.
			if ( 'bb-readylaunch' !== $_GET['page'] ) {
				return false;
			}

			// Verify the nonce for security.
			if ( ! check_admin_referer( 'bb-readylaunch-options' ) ) {
				return false;
			}

			// Save the ReadyLaunch settings if provided.
			if ( isset( $_POST['bb-readylaunch'] ) ) {
				bp_update_option( 'bb_readylaunch', $_POST['bb-readylaunch'] );
			}

			// Redirect to the ReadyLaunch settings page with a success message.
			bp_core_redirect(
				bp_get_admin_url(
					add_query_arg(
						array(
							'page'  => 'bb-readylaunch',
							'added' => 'true',
						),
						'admin.php'
					)
				)
			);
		}

		/**
		 * Override the page templates.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return string ReadyLaunch layout template.
		 */
		public function override_page_templates() {

			return bp_locate_template( 'layout.php' );
		}

		/**
		 * Add custom template stack for ReadyLaunch.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array $stack The current template stack.
		 *
		 * @return array The modified template stack with ReadyLaunch custom location.
		 */
		public function add_template_stack( $stack ) {
			$stylesheet_dir = get_stylesheet_directory();
			$template_dir   = get_template_directory();

			$stack = array_flip( $stack );

			unset( $stack[ $stylesheet_dir ], $stack[ $template_dir ] );

			$stack = array_flip( $stack );

			$custom_location = 'readylaunch';

			foreach ( $stack as $key => $value ) {
				$stack[ $key ] = untrailingslashit( trailingslashit( $value ) . $custom_location );
			}

			return $stack;
		}

		/**
		 * Enqueue ReadyLaunch scripts.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		public function bb_enqueue_scripts() {
			$min = bp_core_get_minified_asset_suffix();

			wp_enqueue_script( 'bb-readylaunch-front', buddypress()->plugin_url . "bp-templates/bp-nouveau/readylaunch/assets/js/bb-readylaunch-front{$min}.js", array( 'jquery' ), bp_get_version(), true );

			wp_enqueue_style( 'bb-readylaunch-style-main', buddypress()->plugin_url . "bp-templates/bp-nouveau/readylaunch/assets/css/main{$min}.css", array(), bp_get_version() );

			wp_enqueue_style( 'bb-readylaunch-icons', buddypress()->plugin_url . "bp-templates/bp-nouveau/readylaunch/assets/icons/css/bb-icons-rl{$min}.css", array(), bp_get_version() );

			wp_localize_script(
				'bb-readylaunch-front',
				'bbReadyLaunchFront',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'nonce'    => wp_create_nonce( 'bb-readylaunch' ),
				)
			);
		}

		/**
		 * Dequeue all styles and scripts except the ones with the allowed suffix.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		public function bb_dequeue_styles() {
			global $wp_styles, $wp_scripts;

			$allow_suffix = array(
				'bb-readylaunch',
				'query-monitor',
			);

			// Dequeue and deregister scripts.
			foreach ( $wp_scripts->queue as $handle ) {
				$src = $wp_scripts->registered[ $handle ]->src ?? '';

				if (
					false === strpos( $src, '/wp-includes/' ) &&
					! $this->bb_has_allowed_suffix( $handle, $allow_suffix )
				) {
					wp_dequeue_script( $handle );
					wp_deregister_script( $handle );
				}
			}

			// Dequeue and deregister styles.
			foreach ( $wp_styles->queue as $handle ) {
				$src = $wp_styles->registered[ $handle ]->src ?? '';

				if (
					false === strpos( $src, '/wp-includes/' ) &&
					! $this->bb_has_allowed_suffix( $handle, $allow_suffix )
				) {
					wp_dequeue_style( $handle );
					wp_deregister_style( $handle );
				}
			}
		}

		/**
		 * Function to check if the handle has allowed suffix.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string $handle       The script handle.
		 * @param array  $allow_suffix The allowed suffix.
		 *
		 * @return bool True if the handle has an allowed suffix, false otherwise.
		 */
		private function bb_has_allowed_suffix( $handle, $allow_suffix ) {
			foreach ( $allow_suffix as $suffix ) {
				if ( false !== strpos( $handle, $suffix ) ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Dequeue bbPress activity js.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return bool False to prevent bbPress activity js from being enqueued.
		 */
		public function bb_dequeue_bbpress_activity_js() {
			return false;
		}

		/**
		 * Fetch header messages.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		public function bb_fetch_header_messages() {

			check_ajax_referer( 'bb-readylaunch', 'nonce' );

			$type = ! empty( $_POST['tab'] ) ? sanitize_text_field( wp_unslash( $_POST['tab'] ) ) : 'all';

			ob_start();
			bp_get_template_part( 'header/unread-messages', null, array( 'type' => $type ) );
			$messages = ob_get_clean();
			wp_send_json_success( $messages );
		}

		/**
		 * Fetch header notifications.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		public function bb_fetch_header_notifications() {

			check_ajax_referer( 'bb-readylaunch', 'nonce' );

			$page = ! empty( $_POST['page'] ) ? intval( sanitize_text_field( wp_unslash( $_POST['page'] ) ) ) : 1;

			ob_start();
			bp_get_template_part( 'header/unread-notifications', null, array( 'page' => $page ) );
			$notifications = ob_get_clean();
			wp_send_json_success( $notifications );
		}

		/**
		 * Check if the sidebar is enabled for groups.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return bool True if the sidebar is enabled for groups, false otherwise.
		 */
		public function bb_is_sidebar_enabled_for_groups() {

			return ! empty( $this->settings['groups_sidebar'] );
		}

		/**
		 * Check if the sidebar is enabled for courses.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return bool True if the sidebar is enabled for courses, false otherwise.
		 */
		public function bb_is_sidebar_enabled_for_courses() {

			return ! empty( $this->settings['courses_sidebar'] );
		}

		/**
		 * Check if any left sidebar section (groups or courses) is active.
		 *
		 * This function checks if the groups or courses sections are active in the left sidebar.
		 * It applies the 'bb_readylaunch_left_sidebar_courses' filter to get the arguments and
		 * parses them to ensure they have the required structure.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param bool $data if true, then check for return data otherwise it will check plugin active or not.
		 *
		 * @return array|bool The active courses array if any section is active, false otherwise.
		 */
		public function bb_is_active_any_left_sidebar_section( bool $data ) {
			$args = apply_filters(
				'bb_readylaunch_left_sidebar_middle_content',
				array(
					'has_sidebar_data'               => $data,
					'is_sidebar_enabled_for_groups'  => $this->bb_is_sidebar_enabled_for_groups(),
					'is_sidebar_enabled_for_courses' => $this->bb_is_sidebar_enabled_for_courses(),
				)
			);

			bp_parse_args(
				$args,
				array(
					'integration' => '',
				)
			);

			if (
				! $data &&
				(
					empty( $args['groups']['integration'] ) &&
					(
						empty( $args['courses']['integration'] ) ||
						! in_array(
							$args['courses']['integration'],
							array(
								'sfwd-courses',
								'tutorlms',
								'lifterlms',
								'meprlms',
							),
							true
						)
					)
				)
			) {
				return false;
			}

			return $args;
		}

		/**
		 * Render the middle content for left sidebar HTML.
		 *
		 * This function generates the HTML for the middle section of the left sidebar,
		 * displaying a list of items or an error message if no items are available.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array $args Array of arguments for the left sidebar middle content.
		 */
		public function bb_render_left_sidebar_middle_html( $args = array() ) {
			bp_parse_args(
				$args,
				array(
					'heading'    => '',
					'items'      => array(),
					'error_text' => '',
				)
			);

			$title      = ! empty( $args['heading'] ) ? $args['heading'] : __( 'Courses', 'buddyboss' );
			$items      = ! empty( $args['items'] ) ? $args['items'] : array();
			$error_text = ! empty( $args['error_text'] ) ? $args['error_text'] : __( 'There are no courses to display.', 'buddyboss' );
			?>
			<div class="bb-rl-list">
				<h2><?php echo esc_html( $title ); ?></h2>
				<?php
				if ( ! empty( $items ) ) {
					?>
					<ul class="item-list" aria-live="polite" aria-relevant="all" aria-atomic="true">
						<?php
						foreach ( $items as $item ) {
							?>
							<li>
								<?php
								if ( ! empty( $item['thumbnail'] ) ) { ?>
									<div class="item-avatar">
										<a href="<?php echo esc_url( $item['permalink'] ); ?>">
											<?php echo $item['thumbnail']; ?>
										</a>
									</div>
									<?php
								} ?>
								<div class="item">
									<div class="item-title">
										<a href="<?php echo esc_url( $item['permalink'] ); ?>">
											<?php echo esc_html( $item['title'] ); ?>
										</a>
									</div>
								</div>
							</li>
							<?php
						}
						?>
					</ul>
					<?php
				} else {
					?>
					<div class="widget-error">
						<?php echo esc_html( $error_text ); ?>
					</div>
					<?php
				}
				?>
			</div>
			<?php
		}

		/**
		 * Adds unread notifications to the heartbeat response.
		 *
		 * This function checks if the user is logged in and if the notification component is active.
		 * If both conditions are met, it retrieves the unread notifications template part and the total
		 * count of unread notifications, then adds them to the response array.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array $response The existing heartbeat response array.
		 *
		 * @return array The modified heartbeat response array with unread notifications' data.
		 */
		public function bb_heartbeat_unread_notifications( $response = array(), $data = array() ) {
			if (
				bp_loggedin_user_id() &&
				bp_is_active( 'notifications' ) &&
				! empty( $data['bb_fetch_header_notifications'] )
			) {

				// Handle the mark_as_read_notifications.
				if ( ! empty( $data['mark_as_read_notifications'] ) ) {
					$ids = array_map( 'intval', explode( ',', $data['mark_as_read_notifications'] ) );
					foreach ( $ids as $id ) {
						BP_Notifications_Notification::update(
							array( 'is_new' => 0 ),
							array(
								'id'      => $id,
								'user_id' => bp_loggedin_user_id(),
							)
						);
					}

					// Indicate that the notifications were processed.
					$response['mark_as_read_processed'] = true;
				}

				// Handle the delete_notifications.
				if ( ! empty( $data['mark_as_delete_notifications'] ) ) {
					$ids = array_map( 'intval', explode( ',', $data['mark_as_delete_notifications'] ) );
					foreach ( $ids as $id ) {
						BP_Notifications_Notification::delete(
							array(
								'id'      => $id,
								'user_id' => bp_loggedin_user_id(),
							)
						);
					}

					// Indicate that the notifications were processed.
					$response['mark_as_delete_processed'] = true;
				}
				ob_start();
				bp_get_template_part( 'header/unread-notifications' );
				$response['unread_notifications'] = ob_get_clean();
				$response['total_notifications']  = bp_notifications_get_unread_notification_count( bp_loggedin_user_id() );
			}

			return $response;
		}

		/**
		 * Marks BuddyBoss notifications as read.
		 *
		 * This function handles AJAX requests to mark notifications as read for the logged-in user.
		 * It checks if the notification component is active and verifies the AJAX nonce.
		 *
		 * Depending on the provided notification ID, it either marks a specific notification or all notifications as read.
		 * Finally, it returns the updated unread notifications count and content via a JSON response.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return void
		 */
		public function bb_mark_notification_read() {

			if ( ! bp_is_active( 'notifications' ) ) {
				return;
			}

			check_ajax_referer( 'bb-readylaunch', 'nonce' );

			$user_id = bp_loggedin_user_id();

			$id = isset( $_POST['read_notification_ids'] ) ? sanitize_text_field( $_POST['read_notification_ids'] ) : '';
			if ( 'all' !== $id ) {
				if ( false !== strpos( $id, ',' ) ) {
					$id = array_map( 'intval', explode( ',', $id ) );
				} else {
					$id = intval( $id );
				}
			}

			$deleted_notification_ids = isset( $_POST['deleted_notification_ids'] ) ? sanitize_text_field( $_POST['deleted_notification_ids'] ) : '';
			$deleted_notification_ids = array_map( 'intval', explode( ',', $deleted_notification_ids ) );

			if ( ! empty( $deleted_notification_ids ) ) {
				foreach ( $deleted_notification_ids as $deleted_notification_id ) {
					BP_Notifications_Notification::delete(
						array(
							'id'      => $deleted_notification_id,
							'user_id' => $user_id,
						)
					);
				}
			}

			if ( ! empty( $id ) && 'all' !== $id ) {
				foreach ( $id as $notification_id ) {
					BP_Notifications_Notification::update(
						array( 'is_new' => 0 ),
						array(
							'id'      => $notification_id,
							'user_id' => $user_id,
						)
					);
				}
			} elseif ( 'all' === $id ) {
				$notification_ids = BP_Notifications_Notification::get(
					array(
						'user_id'           => $user_id,
						'order_by'          => 'date_notified',
						'sort_order'        => 'DESC',
						'page'              => 1,
						'per_page'          => 25,
						'update_meta_cache' => false,
					)
				);
				if ( $notification_ids ) {
					foreach ( $notification_ids as $notification_id ) {
						BP_Notifications_Notification::update(
							array( 'is_new' => 0 ),
							array(
								'id'      => $notification_id->id,
								'user_id' => $user_id,
							)
						);
					}
				}
			}
			$response = array();
			ob_start();
			bp_get_template_part( 'header/unread-notifications' );
			$response['contents']            = ob_get_clean();
			$response['total_notifications'] = bp_notifications_get_unread_notification_count( $user_id );
			wp_send_json_success( $response );
		}
	}

}

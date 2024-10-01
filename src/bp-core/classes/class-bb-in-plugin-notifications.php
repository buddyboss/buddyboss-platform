<?php
/**
 * BuddyBoss In-Plugin Notifications.
 *
 * @since   BuddyBoss [BBVERSION]
 *
 * @package BuddyBoss/Core
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BB_In_Plugin_Notifications' ) ) {

	/**
	 * BB_In_Plugin_Notifications.
	 *
	 * Class for logging in-plugin notifications.
	 * Includes:
	 *     Notifications from our remote feed
	 *     Plugin-related notifications (i.e. - recent sales performances)
	 */
	class BB_In_Plugin_Notifications {

		// @todo We need to change this URL to the correct one.
		const SOURCE_URL = 'https://a.omwpapi.com/production/wp/notifications.json';
		const SOURCE_URL_ARGS = array();

		/**
		 * Option value.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @var bool|array
		 */
		public $option = false;

		/**
		 * BB_In_Plugin_Notifications constructor.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		public function __construct() {
			$this->hooks();
		}

		/**
		 * Register hooks.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		public function hooks() {
			add_action( 'in_admin_header', array( $this, 'bb_admin_notification_header' ), 0 );
			add_action( 'bp_admin_enqueue_scripts', array( $this, 'enqueues' ) );
			add_action( 'admin_footer', array( $this, 'admin_menu_append_count' ) );
			add_action( 'admin_init', array( $this, 'schedule_fetch' ) );
			add_action( 'bb_in_plugin_admin_header_notifications', array( $this, 'output' ) );
			add_action( 'buddyboss_in_plugin_admin_notifications_update', array( $this, 'update' ) );
			add_action( 'wp_ajax_buddyboss_in_plugin_notification_dismiss', array( $this, 'dismiss' ) );
		}

		/**
		 * Load the admin notification header template.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return void
		 */
		public function bb_admin_notification_header() {
			global $bp;
			include trailingslashit( $bp->plugin_dir . 'bp-core/admin' ) . 'templates/bb-in-plugin-notifications.php';
		}

		/**
		 * Make sure the feed is fetched when needed.
		 *
		 * @return void
		 */
		public function schedule_fetch() {

			$option = $this->get_option();

			// Update notifications using an async task.
			if ( empty( $option['update'] ) || time() > $option['update'] + 3 * HOUR_IN_SECONDS ) {
				if ( false === wp_next_scheduled( 'bb_in_plugin_admin_notifications_update' ) ) {
					wp_schedule_single_event( time() + 10, 'buddyboss_in_plugin_admin_notifications_update' );
				}
			}

			// $this->update();
		}

		/**
		 * Get option value.
		 *
		 * @param bool $cache Reference property cache if available.
		 *
		 * @return array
		 */
		public function get_option( $cache = true ) {

			if ( $this->option && $cache ) {
				return $this->option;
			}

			$option = bp_get_option( 'bb_in_plugin_admin_notifications', array() );

			$this->option = array(
				'update'    => ! empty( $option['update'] ) ? $option['update'] : 0,
				'events'    => ! empty( $option['events'] ) ? $option['events'] : array(),
				'feed'      => ! empty( $option['feed'] ) ? $option['feed'] : array(),
				'dismissed' => ! empty( $option['dismissed'] ) ? $option['dismissed'] : array(),
			);

			return $this->option;
		}

		/**
		 * Get notification count.
		 *
		 * @return int
		 */
		public function get_count() {
			return count( $this->get() );
		}

		/**
		 * Get notification data.
		 *
		 * @return array
		 */
		public function get() {

			if ( ! self::has_access() ) {
				return array();
			}

			$option = $this->get_option();

			$events = ! empty( $option['events'] ) ? $this->verify_active( $option['events'] ) : array();
			$feed   = ! empty( $option['feed'] ) ? $this->verify_active( $option['feed'] ) : array();

			$notifications              = array();
			$notifications['active']    = array_merge( $events, $feed );
			$notifications['active']    = $this->get_notifications_with_human_readeable_start_time( $notifications['active'] );
			$notifications['active']    = $this->get_notifications_with_formatted_content( $notifications['active'] );
			$notifications['dismissed'] = ! empty( $option['dismissed'] ) ? $option['dismissed'] : array();
			$notifications['dismissed'] = $this->get_notifications_with_human_readeable_start_time( $notifications['dismissed'] );
			$notifications['dismissed'] = $this->get_notifications_with_formatted_content( $notifications['dismissed'] );

			return $notifications;
		}

		/**
		 * Check if user has access and is enabled.
		 *
		 * @return bool
		 */
		public static function has_access() {

			$access = false;

			if ( ! get_option( 'bb_in_plugin_hide_announcements' ) ) {
				$access = true;
			}

			return apply_filters( 'bb_in_plugin_admin_notifications_has_access', $access );
		}

		/**
		 * Verify saved notification data for active notifications.
		 *
		 * @param array $notifications Array of notification items to verify.
		 *
		 * @return array
		 */
		public function verify_active( $notifications ) {

			if ( ! is_array( $notifications ) || empty( $notifications ) ) {
				return array();
			}

			// Remove notifications that are not active.
			foreach ( $notifications as $key => $notification ) {
				if (
					( ! empty( $notification['start'] ) && strtotime( $notification['start'] . ' America/New_York' ) > strtotime( date( 'F j, Y' ) . ' America/New_York' ) ) ||
					( ! empty( $notification['end'] ) && strtotime( $notification['end'] . ' America/New_York' ) < strtotime( date( 'F j, Y' ) . ' America/New_York' ) )
				) {
					unset( $notifications[ $key ] );
				}
			}

			return $notifications;
		}

		/**
		 * Get notifications start time with human time difference
		 *
		 * @return array $notifications
		 */
		public function get_notifications_with_human_readeable_start_time( $notifications ) {

			if ( ! is_array( $notifications ) || empty( $notifications ) ) {
				return;
			}

			foreach ( $notifications as $key => $notification ) {
				if ( ! isset( $notification['start'] ) || empty( $notification['start'] ) ) {
					continue;
				}

				// Translators: Readable time to display.
				$modified_start_time            = sprintf( __( '%1$s ago', 'buddyboss' ), human_time_diff( strtotime( $notification['start'] ), current_time( 'timestamp' ) ) );
				$notifications[ $key ]['start'] = $modified_start_time;
			}

			return $notifications;
		}

		/**
		 * Improve the format of the content of notifications before display. By default, run wpautop.
		 *
		 * @param array $notifications The notifications to be parsed.
		 *
		 * @return mixed
		 */
		public function get_notifications_with_formatted_content( $notifications ) {
			if ( ! is_array( $notifications ) || empty( $notifications ) ) {
				return $notifications;
			}

			foreach ( $notifications as $key => $notification ) {
				if ( ! empty( $notification['content'] ) ) {
					$notifications[ $key ]['content'] = wpautop( $notification['content'] );
					$notifications[ $key ]['content'] = apply_filters( 'bb_in_plugin_admin_notification_content_display', $notifications[ $key ]['content'] );
				}
			}

			return $notifications;
		}

		/**
		 * Add an event notification. This is NOT for feed notifications.
		 * Event notifications are for alerting the user to something internally (e.g. recent sales performances).
		 *
		 * @param array $notification Notification data.
		 */
		public function add( $notification ) {

			if ( empty( $notification['id'] ) ) {
				return;
			}

			$option = $this->get_option();

			// Already dismissed.
			if ( array_key_exists( $notification['id'], $option['dismissed'] ) ) {
				return;
			}

			// Already in events.
			foreach ( $option['events'] as $item ) {
				if ( $item['id'] === $notification['id'] ) {
					return;
				}
			}

			// Associative key is notification id.
			$notification = $this->verify( array( $notification['id'] => $notification ) );

			// The only thing changing here is adding the notification to the events.
			bp_update_option(
				'bb_in_plugin_admin_notifications',
				array(
					'update'    => $option['update'],
					'feed'      => $option['feed'],
					'events'    => array_merge( $notification, $option['events'] ),
					'dismissed' => $option['dismissed'],
				)
			);
		}

		/**
		 * Verify notification data before it is saved.
		 *
		 * @param array $notifications Array of notification items to verify.
		 *
		 * @return array
		 */
		public function verify( $notifications ) {

			$data = array();

			if ( ! is_array( $notifications ) || empty( $notifications ) ) {
				return $data;
			}

			$option = $this->get_option();

			foreach ( $notifications as $id => $notification ) {

				// The message should never be empty - if it is, ignore.
				if ( empty( $notification['content'] ) ) {
					continue;
				}

				// Ignore if expired.
				if ( ! empty( $notification['end'] ) && time() > strtotime( $notification['end'] ) ) {
					continue;
				}

				// Ignore if notification has already been dismissed.
				if ( ! empty( $option['dismissed'] ) && array_key_exists( $notification['id'], $option['dismissed'] ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
					continue;
				}

				$activated = time();

				if (
					! empty( $activated ) &&
					! empty( $notification['start'] ) &&
					$activated > strtotime( $notification['start'] )
				) {
					continue;
				}

				$data[ $id ] = $notification;

				// Check if this notification has already been saved with a timestamp.
				if ( ! empty( $option['feed'][ $id ] ) ) { // Already exists in feed, so use saved time.
					$data[ $id ]['saved'] = $option['feed'][ $id ]['saved'];
				} elseif ( ! empty( $option['events'][ $id ] ) ) { // Already exists in events, so use saved time.
					$data[ $id ]['saved'] = $option['events'][ $id ]['saved'];
				} else { // Doesn't exist in feed or events, so save current time.
					$data[ $id ]['saved'] = time();
				}
			}

			return $data;
		}

		/**
		 * Update notification data from feed.
		 * This pulls the latest notifications from our remote feed.
		 */
		public function update() {

			$feed   = $this->fetch_feed();
			$option = $this->get_option();

			bp_update_option(
				'bb_in_plugin_admin_notifications',
				array(
					'update'    => time(),
					'feed'      => $feed,
					'events'    => $option['events'],
					'dismissed' => $option['dismissed'],
				)
			);
		}

		/**
		 * Fetch notifications from remote feed.
		 *
		 * @return array
		 */
		public function fetch_feed() {

			$res = wp_remote_get( self::SOURCE_URL, self::SOURCE_URL_ARGS );

			if ( is_wp_error( $res ) ) {
				return array();
			}

			$body = wp_remote_retrieve_body( $res );

			if ( empty( $body ) ) {
				return array();
			}

			return $this->verify( json_decode( $body, true ) );
		}

		/**
		 * Admin area enqueues.
		 */
		public function enqueues() {

			if ( ! self::has_access() ) {
				return;
			}

			$notifications = $this->get();

			if ( empty( $notifications ) ) {
				return;
			}

			wp_enqueue_style(
				'bb-in-plugin-admin-notifications',
				buddypress()->plugin_url . 'bp-core/admin/css/bb-admin-notifications.css',
				array( 'bp-admin-common-css' ),
				bp_get_version()
			);

			wp_enqueue_script(
				'bb-in-plugin-admin-notifications',
				buddypress()->plugin_url . 'bp-core/admin/js/bb-admin-notifications.js',
				array( 'jquery' ),
				bp_get_version(),
				true
			);

			wp_localize_script(
				'bb-in-plugin-admin-notifications',
				'BBInPluginAdminNotifications',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'nonce'    => wp_create_nonce( 'bb-in-plugin-admin-notifications' ),
				)
			);
		}

		/**
		 * Admin script for adding notification count to the MemberPress admin menu list item.
		 */
		public function admin_menu_append_count() {

			$notifications = $this->get();

			if ( empty( $notifications['active'] ) || count( $notifications['active'] ) < 1 ) {
				return;
			}

			ob_start();

			?>

			<span class="awaiting-mod">
		  <span class="pending-count" id="BBInPluginAdminMenuUnreadCount" aria-hidden="true"><?php echo count( $notifications['active'] ); ?></span>
		  <span class="comments-in-moderation-text screen-reader-text"><?php printf( _n( '%s unread message', '%s unread messages', count( $notifications['active'] ), 'buddyboss' ), count( $notifications['active'] ) ); ?></span>
		</span>

			<?php $output = ob_get_clean(); ?>

			<script>
				jQuery( document ).ready( function ( $ ) {
					$( 'li.toplevel_page_buddyboss-platform .wp-menu-name' ).append( `<?php echo $output; ?>` );
				} );
			</script>

			<?php
		}

		/**
		 * Output notifications in MemberPress admin area.
		 */
		public function output() {

			$notifications = $this->get();

			if ( empty( $notifications['active'] ) && empty( $notifications['dismissed'] ) ) {
				return;
			}

			$notifications_html = '<div class="active-messages">';
			if ( ! empty( $notifications['active'] ) ) {
				foreach ( $notifications['active'] as $notification ) {

					// Buttons HTML.
					$buttons_html = '';
					if ( ! empty( $notification['buttons'] ) && is_array( $notification['buttons'] ) ) {
						foreach ( $notification['buttons'] as $btn_type => $btn ) {
							if ( empty( $btn['url'] ) || empty( $btn['text'] ) ) {
								continue;
							}
							$buttons_html .= sprintf(
								'<a href="%1$s" class="button button-%2$s"%3$s>%4$s</a>',
								! empty( $btn['url'] ) ? esc_url( $btn['url'] ) : '',
								$btn_type === 'main' ? 'primary' : 'secondary',
								! empty( $btn['target'] ) && $btn['target'] === '_blank' ? ' target="_blank" rel="noopener noreferrer"' : '',
								! empty( $btn['text'] ) ? sanitize_text_field( $btn['text'] ) : ''
							);
						}
						$buttons_html .= sprintf( '<button class="bb-in-plugin-admin-notifications-notice-dismiss" data-message-id="%s">%s</button>', $notification['id'], __( 'Dismiss', 'buddyboss' ) );
						$buttons_html = ! empty( $buttons_html ) ? '<div class="buddyboss-notifications-buttons">' . $buttons_html . '</div>' : '';
					}

					// Icon HTML
					$icon_html = '';
					if ( ! empty( $notification['icon'] ) ) {
						$icon_html = '<img src="' . esc_url( sanitize_text_field( $notification['icon'] ) ) . '" width="32" height="32">';
					}

					$time_diff        = ceil( ( time() - $notification['saved'] ) );
					$time_diff_string = '';
					if ( $time_diff < MINUTE_IN_SECONDS ) {
						$time_diff_string = sprintf( _n( '%s second ago', '%s seconds ago', $time_diff, 'buddyboss' ), $time_diff );
					} elseif ( $time_diff < HOUR_IN_SECONDS ) {
						$time_diff_string = sprintf( _n( '%s minute ago', '%s minutes ago', ceil( ( $time_diff / MINUTE_IN_SECONDS ) ), 'buddyboss' ), ceil( ( $time_diff / MINUTE_IN_SECONDS ) ) );
					} elseif ( $time_diff < DAY_IN_SECONDS ) {
						$time_diff_string = sprintf( _n( '%s hour ago', '%s hours ago', ceil( ( $time_diff / HOUR_IN_SECONDS ) ), 'buddyboss' ), ceil( ( $time_diff / HOUR_IN_SECONDS ) ) );
					} elseif ( $time_diff < WEEK_IN_SECONDS ) {
						$time_diff_string = sprintf( _n( '%s day ago', '%s days ago', ceil( ( $time_diff / DAY_IN_SECONDS ) ), 'buddyboss' ), ceil( ( $time_diff / DAY_IN_SECONDS ) ) );
					} elseif ( $time_diff < MONTH_IN_SECONDS ) {
						$time_diff_string = sprintf( _n( '%s week ago', '%s weeks ago', ceil( ( $time_diff / WEEK_IN_SECONDS ) ), 'buddyboss' ), ceil( ( $time_diff / WEEK_IN_SECONDS ) ) );
					} elseif ( $time_diff < YEAR_IN_SECONDS ) {
						$time_diff_string = sprintf( _n( '%s month ago', '%s months ago', ceil( ( $time_diff / MONTH_IN_SECONDS ) ), 'buddyboss' ), ceil( ( $time_diff / MONTH_IN_SECONDS ) ) );
					} else {
						$time_diff_string = sprintf( _n( '%s year ago', '%s years ago', ceil( ( $time_diff / YEAR_IN_SECONDS ) ), 'buddyboss' ), ceil( ( $time_diff / YEAR_IN_SECONDS ) ) );
					}
					// Notification HTML.
					$notifications_html .= sprintf(
						'<div id="buddyboss-notifications-message-%4$s" class="buddyboss-notifications-message" data-message-id="%4$s">
              <div class="buddyboss-notification-icon-title">
              %5$s
              <h3 class="buddyboss-notifications-title">%1$s</h3>
              <time datetime="%6$s">%7$s</time>
              </div>
              <div class="buddyboss-notifications-content">%2$s</div>
              %3$s
            </div>',
						! empty( $notification['title'] ) ? sanitize_text_field( $notification['title'] ) : '',
						! empty( $notification['content'] ) ? apply_filters( 'the_content', $notification['content'] ) : '',
						$buttons_html,
						! empty( $notification['id'] ) ? esc_attr( sanitize_text_field( $notification['id'] ) ) : 0,
						$icon_html,
						date( 'Y-m-d G:i a', $notification['saved'] ),
						$time_diff_string
					);
				}
			}
			$notifications_html .= sprintf( '<div class="buddyboss-notifications-none" %s>%s</div>', empty( $notifications['active'] ) || count( $notifications['active'] ) < 1 ? '' : 'style="display: none;"', __( 'You\'re all caught up!', 'buddyboss' ) );
			$notifications_html .= '</div>';

			$notifications_html .= '<div class="dismissed-messages">';
			if ( ! empty( $notifications['dismissed'] ) ) {
				foreach ( $notifications['dismissed'] as $notification ) {

					// Buttons HTML.
					$buttons_html = '';
					if ( ! empty( $notification['buttons'] ) && is_array( $notification['buttons'] ) ) {
						foreach ( $notification['buttons'] as $btn_type => $btn ) {
							if ( empty( $btn['url'] ) || empty( $btn['text'] ) ) {
								continue;
							}
							$buttons_html .= sprintf(
								'<a href="%1$s" class="button button-%2$s"%3$s>%4$s</a>',
								! empty( $btn['url'] ) ? esc_url( $btn['url'] ) : '',
								$btn_type === 'main' ? 'primary' : 'secondary',
								! empty( $btn['target'] ) && $btn['target'] === '_blank' ? ' target="_blank" rel="noopener noreferrer"' : '',
								! empty( $btn['text'] ) ? sanitize_text_field( $btn['text'] ) : ''
							);
						}
						$buttons_html .= sprintf( '<button class="bb-in-plugin-admin-notifications-notice-dismiss" data-message-id="%s">%s</button>', $notification['id'], __( 'Dismiss', 'buddyboss' ) );
						$buttons_html = ! empty( $buttons_html ) ? '<div class="buddyboss-notifications-buttons">' . $buttons_html . '</div>' : '';
					}

					$time_diff        = ceil( ( time() - $notification['saved'] ) );
					$time_diff_string = '';
					if ( $time_diff < MINUTE_IN_SECONDS ) {
						$time_diff_string = sprintf( _n( '%s second ago', '%s seconds ago', $time_diff, 'buddyboss' ), $time_diff );
					} elseif ( $time_diff < HOUR_IN_SECONDS ) {
						$time_diff_string = sprintf( _n( '%s minute ago', '%s minutes ago', ceil( ( $time_diff / MINUTE_IN_SECONDS ) ), 'buddyboss' ), ceil( ( $time_diff / MINUTE_IN_SECONDS ) ) );
					} elseif ( $time_diff < DAY_IN_SECONDS ) {
						$time_diff_string = sprintf( _n( '%s hour ago', '%s hours ago', ceil( ( $time_diff / HOUR_IN_SECONDS ) ), 'buddyboss' ), ceil( ( $time_diff / HOUR_IN_SECONDS ) ) );
					} elseif ( $time_diff < WEEK_IN_SECONDS ) {
						$time_diff_string = sprintf( _n( '%s day ago', '%s days ago', ceil( ( $time_diff / DAY_IN_SECONDS ) ), 'buddyboss' ), ceil( ( $time_diff / DAY_IN_SECONDS ) ) );
					} elseif ( $time_diff < MONTH_IN_SECONDS ) {
						$time_diff_string = sprintf( _n( '%s week ago', '%s weeks ago', ceil( ( $time_diff / WEEK_IN_SECONDS ) ), 'buddyboss' ), ceil( ( $time_diff / WEEK_IN_SECONDS ) ) );
					} elseif ( $time_diff < YEAR_IN_SECONDS ) {
						$time_diff_string = sprintf( _n( '%s month ago', '%s months ago', ceil( ( $time_diff / MONTH_IN_SECONDS ) ), 'buddyboss' ), ceil( ( $time_diff / MONTH_IN_SECONDS ) ) );
					} else {
						$time_diff_string = sprintf( _n( '%s year ago', '%s years ago', ceil( ( $time_diff / YEAR_IN_SECONDS ) ), 'buddyboss' ), ceil( ( $time_diff / YEAR_IN_SECONDS ) ) );
					}

					// Notification HTML.
					$notifications_html .= sprintf(
						'<div id="buddyboss-notifications-message-%4$s" class="buddyboss-notifications-message" data-message-id="%4$s">
              <div class="buddyboss-notification-icon-title">
              <img src="%5$s" width="32" height="32">
              <h3 class="buddyboss-notifications-title">%1$s</h3>
              <time datetime="%6$s">%7$s</time>
              </div>
              <div class="buddyboss-notifications-content">%2$s</div>
              %3$s
            </div>',
						! empty( $notification['title'] ) ? sanitize_text_field( $notification['title'] ) : '',
						! empty( $notification['content'] ) ? apply_filters( 'the_content', $notification['content'] ) : '',
						$buttons_html,
						! empty( $notification['id'] ) ? esc_attr( sanitize_text_field( $notification['id'] ) ) : 0,
						! empty( $notification['icon'] ) ? esc_url( sanitize_text_field( $notification['icon'] ) ) : '',
						date( 'Y-m-d G:i a', $notification['saved'] ),
						$time_diff_string
					);
				}
			}
			$notifications_html .= '</div>';
			?>

			<div id="buddyboss-notifications">

				<div class="buddyboss-notifications-container">

					<div class="buddyboss-notifications-top-title">
						<div class="buddyboss-notifications-top-title__left">
							<svg width="24" height="15" viewBox="0 0 24 15" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M23.6667 7.03125C23.8889 7.34375 24 7.69531 24 8.08594V13.125C24 13.6458 23.8056 14.0885 23.4167 14.4531C23.0278 14.8177 22.5556 15 22 15H2C1.44444 15 0.972222 14.8177 0.583333 14.4531C0.194444 14.0885 0 13.6458 0 13.125V8.08594C0 7.69531 0.111111 7.34375 0.333333 7.03125L4.75 0.820312C4.86111 0.690104 5 0.559896 5.16667 0.429688C5.36111 0.299479 5.56944 0.195312 5.79167 0.117188C6.01389 0.0390625 6.22222 0 6.41667 0H17.5833C17.8889 0 18.1944 0.0911458 18.5 0.273438C18.8333 0.429688 19.0833 0.611979 19.25 0.820312L23.6667 7.03125ZM6.75 2.5L3.20833 7.5H8.33333L9.66667 10H14.3333L15.6667 7.5H20.7917L17.25 2.5H6.75Z" fill="white"></path>
							</svg>
							<h3><?php _e( 'Inbox', 'buddyboss' ); ?></h3>
						</div>
						<div class="buddyboss-notifications-top-title__right actions">
							<a href="#" id="viewDismissed"><?php _e( 'View Dismissed', 'buddyboss' ); ?></a>
							<a href="#" id="viewActive"><?php _e( 'View Active', 'buddyboss' ); ?></a>
							<a href="#" id="buddybossNotificationsClose" class="close" title="<?php _e( 'Close', 'buddyboss' ); ?>">
								<svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
									<path d="M8.28409 6L11.6932 9.40909C11.8977 9.61364 12 9.86364 12 10.1591C12 10.4545 11.8977 10.7159 11.6932 10.9432L10.9432 11.6932C10.7159 11.8977 10.4545 12 10.1591 12C9.86364 12 9.61364 11.8977 9.40909 11.6932L6 8.28409L2.59091 11.6932C2.38636 11.8977 2.13636 12 1.84091 12C1.54545 12 1.28409 11.8977 1.05682 11.6932L0.306818 10.9432C0.102273 10.7159 0 10.4545 0 10.1591C0 9.86364 0.102273 9.61364 0.306818 9.40909L3.71591 6L0.306818 2.59091C0.102273 2.38636 0 2.13636 0 1.84091C0 1.54545 0.102273 1.28409 0.306818 1.05682L1.05682 0.306818C1.28409 0.102273 1.54545 0 1.84091 0C2.13636 0 2.38636 0.102273 2.59091 0.306818L6 3.71591L9.40909 0.306818C9.61364 0.102273 9.86364 0 10.1591 0C10.4545 0 10.7159 0.102273 10.9432 0.306818L11.6932 1.05682C11.8977 1.28409 12 1.54545 12 1.84091C12 2.13636 11.8977 2.38636 11.6932 2.59091L8.28409 6Z" fill="white"></path>
								</svg>
							</a>
						</div>
					</div>
					<div class="buddyboss-notifications-header <?php echo ! empty( $notifications['active'] ) && count( $notifications['active'] ) < 10 ? 'single-digit' : ''; ?>">
						<div class="buddyboss-notifications-header-bell">
							<div class="buddyboss-notifications-bell">
								<svg viewBox="0 0 512 512" width="30" xmlns="http://www.w3.org/2000/svg">
									<path fill="#777777" d="m381.7 225.9c0-97.6-52.5-130.8-101.6-138.2 0-.5.1-1 .1-1.6 0-12.3-10.9-22.1-24.2-22.1s-23.8 9.8-23.8 22.1c0 .6 0 1.1.1 1.6-49.2 7.5-102 40.8-102 138.4 0 113.8-28.3 126-66.3 158h384c-37.8-32.1-66.3-44.4-66.3-158.2z" />
									<path fill="#777777" d="m256.2 448c26.8 0 48.8-19.9 51.7-43h-103.4c2.8 23.1 24.9 43 51.7 43z" />
								</svg>
								<?php if ( ! empty( $notifications['active'] ) ) : ?>
									<span id="bbNotificationsCount" class="buddyboss-notifications-count"><?php echo count( $notifications['active'] ); ?></span>
								<?php endif; ?>
							</div>
							<div class="buddyboss-notifications-title"><?php esc_html_e( 'Notifications', 'buddyboss' ); ?></div>
						</div>
						<?php if ( ! empty( $notifications['active'] ) ) : ?>
							<button id="dismissAll" class="dismiss-all"><?php _e( 'Dismiss All', 'buddyboss' ); ?></button>
						<?php endif; ?>
					</div>

					<div class="buddyboss-notifications-body">
						<div class="buddyboss-notifications-messages">
							<?php echo $notifications_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>
					</div>

				</div>

			</div>
			<?php
		}

		/**
		 * Dismiss notification(s) via AJAX.
		 */
		public function dismiss() {

			// Run a security check.
			check_ajax_referer( 'bb-in-plugin-admin-notifications', 'nonce' );

			// Check for access and required param.
			if ( ! self::has_access() || empty( $_POST['id'] ) ) {
				wp_send_json_error();
			}

			$id     = sanitize_text_field( wp_unslash( $_POST['id'] ) );
			$option = $this->get_option();

			if ( 'all' === $id ) { // Dismiss all notifications.

				// Feed notifications.
				if ( ! empty( $option['feed'] ) ) {
					foreach ( $option['feed'] as $key => $notification ) {
						$option['dismissed'][ $key ] = $option['feed'][ $key ];
						unset( $option['feed'][ $key ] );
					}
				}

				// Event notifications.
				if ( ! empty( $option['events'] ) ) {
					foreach ( $option['events'] as $key => $notification ) {
						$option['dismissed'][ $key ] = $option['events'][ $key ];
						unset( $option['events'][ $key ] );
					}
				}
			} else { // Dismiss one notification.

				// Event notifications need a prefix to distinguish them from feed notifications
				// For a naming convention, we'll use "event_{timestamp}"
				// If the notification ID includes "event_", we know it's an even notification.
				$type = false !== strpos( $id, 'event_' ) ? 'events' : 'feed';

				if ( $type == 'events' ) {
					if ( ! empty( $option[ $type ] ) ) {
						foreach ( $option[ $type ] as $index => $event_notification ) {
							if ( $event_notification['id'] == $id ) {
								unset( $option[ $type ][ $index ] );
								break;
							}
						}
					}
				} else {
					if ( ! empty( $option[ $type ][ $id ] ) ) {
						$option['dismissed'][ $id ] = $option[ $type ][ $id ];
						unset( $option[ $type ][ $id ] );
					}
				}
			}

			bp_update_option( 'bb_in_plugin_admin_notifications', $option );

			wp_send_json_success();
		}

		public function dismiss_events( $type ) {

			$option = $this->get_option();

			// Event notifications.
			if ( ! empty( $option['events'] ) ) {
				$found = 0;
				foreach ( $option['events'] as $key => $notification ) {
					// We found event.
					if ( $type === $notification['type'] ) {
						unset( $option['events'][ $key ] );
						$found = 1;
					}
				}

				if ( $found ) {
					bp_update_option( 'bb_in_plugin_admin_notifications', $option );
				}
			}
		}
	}
}

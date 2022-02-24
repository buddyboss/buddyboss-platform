<?php

/**
 * BuddyBoss Member Switching class
 *
 * @package BuddyBoss\Members
 * @since BuddyBoss 1.0.0
 */
class BP_Core_Members_Switching {

	/**
	 * The name used to identify the application during a WordPress redirect.
	 *
	 * @var string
	 */
	public static $application = 'BuddyBoss/Members Switching';

	/**
	 * Sets up all the filters and actions.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function init_hooks() {

		// Required functionality:
		add_filter( 'user_has_cap', array( $this, 'filter_user_has_cap' ), 10, 4 );
		add_filter( 'map_meta_cap', array( $this, 'filter_map_meta_cap' ), 10, 4 );
		add_filter( 'user_row_actions', array( $this, 'filter_user_row_actions' ), 10, 2 );
		add_action( 'plugins_loaded', array( $this, 'action_plugins_loaded' ) );
		add_action( 'init', array( $this, 'action_init' ), 11 );
		add_action( 'all_admin_notices', array( $this, 'action_admin_notices' ), 1 );
		add_action( 'wp_logout', 'bp_member_switching_clear_olduser_cookie' );
		add_action( 'wp_login', 'bp_member_switching_clear_olduser_cookie' );

		// Nice-to-haves:
		add_filter( 'ms_user_row_actions', array( $this, 'filter_user_row_actions' ), 10, 2 );
		add_filter( 'login_message', array( $this, 'filter_login_message' ), 1 );
		add_filter( 'removable_query_args', array( $this, 'filter_removable_query_args' ) );
		add_action( 'wp_meta', array( $this, 'action_wp_meta' ) );
		add_action( 'admin_footer', array( $this, 'action_admin_footer' ) );
		add_action( 'personal_options', array( $this, 'action_personal_options' ) );
		add_action( 'admin_bar_menu', array( $this, 'action_admin_bar_menu' ), 100 );
		add_action( 'bbp_template_after_user_details', array( $this, 'action_bbpress_button' ) );
		// add_filter( 'show_admin_bar', array( $this, 'filter_show_admin_bar' ), 999, 1 );
	}

	/**
	 * Defines the names of the cookies used by Member Switching.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function action_plugins_loaded() {

		// Member Switching's auth_cookie
		if ( ! defined( 'BP_MEMBER_SWITCHING_COOKIE' ) ) {
			define( 'BP_MEMBER_SWITCHING_COOKIE', 'buddyboss_user_sw_' . COOKIEHASH );
		}

		// Member Switching's secure_auth_cookie
		if ( ! defined( 'BP_MEMBER_SWITCHING_SECURE_COOKIE' ) ) {
			define( 'BP_MEMBER_SWITCHING_SECURE_COOKIE', 'buddyboss_user_sw_secure_' . COOKIEHASH );
		}

		// Member Switching's logged_in_cookie
		if ( ! defined( 'BP_MEMBER_SWITCHING_OLDUSER_COOKIE' ) ) {
			define( 'BP_MEMBER_SWITCHING_OLDUSER_COOKIE', 'buddyboss_user_sw_olduser_' . COOKIEHASH );
		}
	}

	/**
	 * Outputs the 'View As' link on the user editing screen if the current user has permission to switch to them.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param WP_User $user User object for this screen.
	 */
	public function action_personal_options( WP_User $user ) {
		$link = self::maybe_switch_url( $user );

		if ( ! $link ) {
			return;
		}

		?>
		<tr>
			<th scope="row"><?php echo esc_html__( 'Member Switching', 'buddyboss' ); ?></th>
			<td><a href="<?php echo esc_url( $link ); ?>"><?php esc_html_e( 'View As', 'buddyboss' ); ?></a>
			</td>
		</tr>
		<?php
	}

	/**
	 * Returns whether or not the current logged in user is being remembered in the form of a persistent browser cookie
	 * (ie. they checked the 'Remember Me' check box when they logged in). This is used to persist the 'remember me'
	 * value when the user switches to another user.
	 *
	 * @since BuddyBoss 1.0.0
	 * @return bool Whether the current user is being 'remembered' or not.
	 */
	public static function remember() {
		/** This filter is documented in wp-includes/pluggable.php */
		$cookie_life = apply_filters( 'auth_cookie_expiration', 172800, get_current_user_id(), false );
		$current     = wp_parse_auth_cookie( '', 'logged_in' );

		// Here we calculate the expiration length of the current auth cookie and compare it to the default expiration.
		// If it's greater than this, then we know the user checked 'Remember Me' when they logged in.
		return ( ( $current['expiration'] - time() ) > $cookie_life );
	}

	/**
	 * Loads localisation files and routes actions depending on the 'action' query var.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function action_init() {

		if ( ! isset( $_REQUEST['action'] ) ) {
			return;
		}

		$current_user = ( is_user_logged_in() ) ? wp_get_current_user() : null;

		switch ( $_REQUEST['action'] ) {

			// We're attempting to switch to another user:
			case 'switch_to_user':
				if ( isset( $_REQUEST['user_id'] ) ) {
					$user_id = absint( $_REQUEST['user_id'] );
				} else {
					$user_id = 0;
				}

				// Check authentication:
				$old_user = bp_current_member_switched();
				if ( ! current_user_can( 'switch_to_user', $user_id ) && ( ! $old_user || ! user_can( $old_user, 'switch_to_user' ) ) ) {
					wp_die( esc_html__( 'Could not switch users.', 'buddyboss' ) );
				}

				// Check intent:
				check_admin_referer( "switch_to_user_{$user_id}" );

				// Switch user:
				$user = bp_member_switch_to( $user_id, self::remember() );
				if ( $user ) {
					$redirect_to = ( ! empty( $_GET['redirect_to'] ) ) ? $_GET['redirect_to'] : self::get_redirect( $user, $current_user );

					// Redirect to the dashboard or the home URL depending on capabilities:
					$args = array(
						'user_switched' => 'true',
					);

					if ( $redirect_to ) {
						wp_safe_redirect( add_query_arg( $args, $redirect_to ), 302, self::$application );
					} elseif ( ! current_user_can( 'read' ) ) {
						wp_safe_redirect( add_query_arg( $args, home_url() ), 302, self::$application );
					} else {
						wp_safe_redirect( add_query_arg( $args, admin_url() ), 302, self::$application );
					}
					exit;
				} else {
					wp_die( esc_html__( 'Could not switch users.', 'buddyboss' ) );
				}
				break;

			// We're attempting to switch back to the originating user:
			case 'switch_to_olduser':
				// Fetch the originating user data:
				$old_user = self::get_old_user();
				if ( ! $old_user ) {
					wp_die( esc_html__( 'Could not switch users.', 'buddyboss' ) );
				}

				// Check authentication:
				if ( ! self::authenticate_old_user( $old_user ) ) {
					wp_die( esc_html__( 'Could not switch users.', 'buddyboss' ) );
				}

				// Check intent:
				check_admin_referer( "switch_to_olduser_{$old_user->ID}" );

				// Switch user:
				if ( bp_member_switch_to( $old_user->ID, self::remember(), false ) ) {

					if ( ! empty( $_REQUEST['interim-login'] ) ) {
						$GLOBALS['interim_login'] = 'success'; // @codingStandardsIgnoreLine
						login_header( '', '' );
						exit;
					}

					$redirect_to = self::get_redirect( $old_user, $current_user );
					$args        = array(
						'user_switched' => 'true',
						'switched_back' => 'true',
					);

					if ( $redirect_to ) {
						wp_safe_redirect( add_query_arg( $args, $redirect_to ), 302, self::$application );
					} else {
						wp_safe_redirect( add_query_arg( $args, admin_url( 'users.php' ) ), 302, self::$application );
					}
					exit;
				} else {
					wp_die( esc_html__( 'Could not switch users.', 'buddyboss' ) );
				}
				break;

		}
	}

	/**
	 * Fetches the URL to redirect to for a given user (used after switching).
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param  WP_User $new_user Optional. The new user's WP_User object.
	 * @param  WP_User $old_user Optional. The old user's WP_User object.
	 *
	 * @return string The URL to redirect to.
	 */
	protected static function get_redirect( WP_User $new_user = null, WP_User $old_user = null ) {

		if ( ! $new_user ) {
			$redirect_to = bp_core_get_user_domain( $old_user->ID );
		} else {
			$redirect_to = bp_core_get_user_domain( $new_user->ID );
		}

		return $redirect_to;
	}

	/**
	 * Displays the 'Switched to {user}' and 'Switch back to {user}' messages in the admin area.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function action_admin_notices() {
		$user     = wp_get_current_user();
		$old_user = self::get_old_user();

		if ( $old_user ) {
			?>
			<div id="bp_member_switching" class="updated notice is-dismissible">
				<p><span class="dashicons dashicons-admin-users" aria-hidden="true"></span>
					<?php
					$message       = '';
					$just_switched = isset( $_GET['user_switched'] );
					if ( $just_switched ) {
						$message = esc_html(
							sprintf(
								/* Translators: 1: user display name; 2: username; */
								__( 'Switched to %1$s (%2$s).', 'buddyboss' ),
								$user->display_name,
								$user->user_login
							)
						);
					}
					$switch_back_url = add_query_arg(
						array(
							'redirect_to' => urlencode( self::current_url() ),
						),
						self::switch_back_url( $old_user )
					);

					$message .= sprintf(
						' <a href="%s">%s</a>.',
						esc_url( $switch_back_url ),
						esc_html(
							sprintf(
								/* Translators: 1: user display name; 2: username; */
								__( 'Switch back to %1$s (%2$s)', 'buddyboss' ),
								$old_user->display_name,
								$old_user->user_login
							)
						)
					);

					/**
					 * Filters the contents of the message that's displayed to switched users in the admin area.
					 *
					 * @since BuddyBoss 1.0.0
					 *
					 * @param string $message The message displayed to the switched user.
					 * @param WP_User $user The current user object.
					 * @param WP_User $old_user The old user object.
					 * @param string $switch_back_url The switch back URL.
					 * @param bool $just_switched Whether the user made the switch on this page request.
					 */
					$message = apply_filters( 'bp_member_switching_switched_message', $message, $user, $old_user, $switch_back_url, $just_switched );
					echo $message; // WPCS: XSS ok.
					?>
				</p>
			</div>
			<?php
		} elseif ( isset( $_GET['user_switched'] ) ) {
			?>
			<div id="bp_member_switching" class="updated notice is-dismissible">
				<p>
					<?php
					if ( isset( $_GET['switched_back'] ) ) {
						echo esc_html(
							sprintf(
								/* Translators: 1: user display name; 2: username; */
								__( 'Switched back to %1$s (%2$s).', 'buddyboss' ),
								$user->display_name,
								$user->user_login
							)
						);
					} else {
						echo esc_html(
							sprintf(
								/* Translators: 1: user display name; 2: username; */
								__( 'Switched to %1$s (%2$s).', 'buddyboss' ),
								$user->display_name,
								$user->user_login
							)
						);
					}
					?>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Validates the old user cookie and returns its user data.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @return false|WP_User False if there's no old user cookie or it's invalid, WP_User object if it's present and valid.
	 */
	public static function get_old_user() {
		$cookie = bp_member_switching_get_olduser_cookie();
		if ( ! empty( $cookie ) ) {
			$old_user_id = wp_validate_auth_cookie( $cookie, 'logged_in' );

			if ( $old_user_id ) {
				return get_userdata( $old_user_id );
			}
		}

		return false;
	}

	/**
	 * Authenticates an old user by verifying the latest entry in the auth cookie.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param WP_User $user A WP_User object (usually from the logged_in cookie).
	 *
	 * @return bool Whether verification with the auth cookie passed.
	 */
	public static function authenticate_old_user( WP_User $user ) {
		$cookie = bp_member_switching_get_auth_cookie();
		if ( ! empty( $cookie ) ) {
			if ( self::secure_auth_cookie() ) {
				$scheme = 'secure_auth';
			} else {
				$scheme = 'auth';
			}

			$old_user_id = wp_validate_auth_cookie( end( $cookie ), $scheme );

			if ( $old_user_id ) {
				return ( $user->ID === $old_user_id );
			}
		}

		return false;
	}

	/**
	 * Adds a 'Switch back to {user}' link in WordPress' admin bar.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param WP_Admin_Bar $wp_admin_bar The admin bar object.
	 */
	public function action_admin_bar_menu( WP_Admin_Bar $wp_admin_bar ) {
		if ( ! function_exists( 'is_admin_bar_showing' ) ) {
			return;
		}
		if ( ! is_admin_bar_showing() ) {
			return;
		}

		$old_user = self::get_old_user();

		if ( $old_user ) {
			$wp_admin_bar->add_menu(
				array(
					'parent' => 'top-secondary',
					'id'     => 'switch-back',
					'title'  => esc_html(
						sprintf(
							__( 'Switch back to Admin', 'buddyboss' ),
							$old_user->display_name,
							$old_user->user_login
						)
					),
					'href'   => add_query_arg(
						array(
							'redirect_to' => urlencode( self::current_url() ),
						),
						self::switch_back_url( $old_user )
					),
				)
			);
		}

	}

	/**
	 * Adds a 'Switch back to {user}' link to the Meta sidebar widget.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function action_wp_meta() {
		$old_user = self::get_old_user();

		if ( $old_user instanceof WP_User ) {
			$link = sprintf(
				/* Translators: 1: user display name; 2: username; */
				__( 'Switch back to %1$s (%2$s)', 'buddyboss' ),
				$old_user->display_name,
				$old_user->user_login
			);
			$url = add_query_arg(
				array(
					'redirect_to' => urlencode( self::current_url() ),
				),
				self::switch_back_url( $old_user )
			);
			echo '<li id="bp_member_switching_switch_on"><a href="' . esc_url( $url ) . '">' . esc_html( $link ) . '</a></li>';
		}
	}

	/**
	 * Adds a 'Switch back to {user}' link to the WordPress footer if the admin toolbar isn't showing.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function action_admin_footer() {
		if ( ! function_exists( 'is_admin_bar_showing' ) ) {
			return;
		}
		if ( ! is_admin_bar_showing() ) {
			return;
		}

		$old_user = self::get_old_user();

		if ( $old_user instanceof WP_User ) {
			$colors = self::admin_bar_link_color_scheme();
			if ( isset( $colors['background'] ) && isset( $colors['color'] ) ) {
				?>
				<style>
					/* Member Switching */
					#wpadminbar #wp-admin-bar-top-secondary li#wp-admin-bar-switch-back a {
						background: <?php echo $colors['background']; ?>;
						color: <?php echo $colors['color']; ?>;;
					}
				</style>
				<?php
			}
		}
	}

	/**
	 * Adds a 'Switch back to {user}' link to the WordPress login screen.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param  string $message The login screen message.
	 *
	 * @return string The login screen message.
	 */
	public function filter_login_message( $message ) {
		$old_user = self::get_old_user();

		if ( $old_user instanceof WP_User ) {
			$link = sprintf(
				/* Translators: 1: user display name; 2: username; */
				__( 'Switch back to %1$s (%2$s)', 'buddyboss' ),
				$old_user->display_name,
				$old_user->user_login
			);
			$url = self::switch_back_url( $old_user );

			if ( ! empty( $_REQUEST['interim-login'] ) ) {
				$url = add_query_arg(
					array(
						'interim-login' => '1',
					),
					$url
				);
			} elseif ( ! empty( $_REQUEST['redirect_to'] ) ) {
				$url = add_query_arg(
					array(
						'redirect_to' => urlencode( wp_unslash( $_REQUEST['redirect_to'] ) ), // WPCS: sanitization ok
					),
					$url
				);
			}

			$message .= '<div class="message" id="bp_member_switching_switch_on">';
			$message .= '<a href="' . esc_url( $url ) . '" onclick="window.location.href=\'' . esc_url( $url ) . '\';return false;">' . esc_html( $link ) . '</a>';
			$message .= '</div>';
		}

		return $message;
	}

	/**
	 * Adds a 'View As' link to each list of user actions on the Users screen.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param string[] $actions The actions to display for this user row.
	 * @param WP_User  $user The user object displayed in this row.
	 *
	 * @return string[] The actions to display for this user row.
	 */
	public function filter_user_row_actions( array $actions, WP_User $user ) {
		$link = self::maybe_switch_url( $user );

		if ( ! $link ) {
			return $actions;
		}

		$actions['switch_to_user'] = '<a href="' . esc_url( $link ) . '">' . esc_html__( 'View As', 'buddyboss' ) . '</a>';

		return $actions;
	}

	/**
	 * Adds a 'View As' link to each member's profile page and profile listings in BuddyPress.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function action_bp_button() {
		$user = null;

		if ( bp_is_user() ) {
			$user = get_userdata( bp_displayed_user_id() );
		} elseif ( bp_is_members_directory() ) {
			$user = get_userdata( bp_get_member_user_id() );
		}

		if ( ! $user ) {
			return;
		}

		$link = self::maybe_switch_url( $user );

		if ( ! $link ) {
			return;
		}

		$link = add_query_arg(
			array(
				'redirect_to' => urlencode( bp_core_get_user_domain( $user->ID ) ),
			),
			$link
		);

		$components = array_keys( buddypress()->active_components );

		echo bp_get_button(
			array(
				'id'         => 'bp_member_switching',
				'component'  => reset( $components ),
				'link_href'  => esc_url( $link ),
				'link_text'  => esc_html__( 'View As', 'buddyboss' ),
				'wrapper_id' => 'bp_member_switching_switch_to',
			)
		);
	}

	/**
	 * Adds a 'View As' link to each member's profile page in bbPress.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function action_bbpress_button() {
		$user = get_userdata( bbp_get_user_id() );

		if ( ! $user ) {
			return;
		}

		$link = self::maybe_switch_url( $user );

		if ( ! $link ) {
			return;
		}

		$link = add_query_arg(
			array(
				'redirect_to' => urlencode( bbp_get_user_profile_url( $user->ID ) ),
			),
			$link
		);

		?>
		<ul id="bp_member_switching_switch_to">
			<li><a href="<?php echo esc_url( $link ); ?>"><?php esc_html_e( 'View As', 'buddyboss' ); ?></a>
			</li>
		</ul>
		<?php
	}

	/**
	 * Filters the list of query arguments which get removed from admin area URLs in WordPress.
	 *
	 * @link https://core.trac.wordpress.org/ticket/23367
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param string[] $args List of removable query arguments.
	 *
	 * @return string[] Updated list of removable query arguments.
	 */
	public function filter_removable_query_args( array $args ) {
		return array_merge(
			$args,
			array(
				'user_switched',
				'switched_off',
				'switched_back',
			)
		);
	}

	/**
	 * Returns the switch to or switch back URL for a given user.
	 *
	 * @param  WP_User $user The user to be switched to.
	 *
	 * @return string|false The required URL, or false if there's no old user or the user doesn't have the required capability.
	 */
	public static function maybe_switch_url( WP_User $user ) {
		$old_user = self::get_old_user();

		if ( $old_user && ( $old_user->ID === $user->ID || bp_is_my_profile() ) ) {
			return self::switch_back_url( $old_user );
		} elseif ( current_user_can( 'switch_to_user', $user->ID ) || user_can( $old_user, 'switch_to_user', $user->ID ) ) {
			return self::switch_to_url( $user );
		} else {
			return false;
		}
	}

	/**
	 * Returns the nonce-secured URL needed to switch to a given user ID.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param  WP_User $user The user to be switched to.
	 *
	 * @return string The required URL.
	 */
	public static function switch_to_url( WP_User $user ) {
		return wp_nonce_url(
			add_query_arg(
				array(
					'action'  => 'switch_to_user',
					'user_id' => $user->ID,
					'nr'      => 1,
				),
				wp_login_url()
			),
			"switch_to_user_{$user->ID}"
		);
	}

	/**
	 * Returns the nonce-secured URL needed to switch back to the originating user.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param  WP_User $user The old user.
	 *
	 * @return string        The required URL.
	 */
	public static function switch_back_url( WP_User $user ) {
		return wp_nonce_url(
			add_query_arg(
				array(
					'action' => 'switch_to_olduser',
					'nr'     => 1,
				),
				wp_login_url()
			),
			"switch_to_olduser_{$user->ID}"
		);
	}

	/**
	 * Returns the nonce-secured URL needed to switch off the current user.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param  WP_User $user The user to be switched off.
	 *
	 * @return string        The required URL.
	 */
	public static function switch_off_url( WP_User $user ) {
		return wp_nonce_url(
			add_query_arg(
				array(
					'action' => 'switch_off',
					'nr'     => 1,
				),
				wp_login_url()
			),
			"switch_off_{$user->ID}"
		);
	}

	/**
	 * Returns the current URL.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @return string The current URL.
	 */
	public static function current_url() {
		return ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; // @codingStandardsIgnoreLine
	}

	/**
	 * Removes a list of common confirmation-style query args from a URL.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param  string $url A URL.
	 *
	 * @return string The URL with query args removed.
	 */
	public static function remove_query_args( $url ) {
		if ( function_exists( 'wp_removable_query_args' ) ) {
			$url = remove_query_arg( wp_removable_query_args(), $url );
		}

		return $url;
	}

	/**
	 * Returns whether or not Member Switching's equivalent of the 'logged_in' cookie should be secure.
	 *
	 * This is used to set the 'secure' flag on the old user cookie, for enhanced security.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @link https://core.trac.wordpress.org/ticket/15330
	 *
	 * @return bool Should the old user cookie be secure?
	 */
	public static function secure_olduser_cookie() {
		return ( is_ssl() && ( 'https' === parse_url( home_url(), PHP_URL_SCHEME ) ) );
	}

	/**
	 * Returns whether or not Member Switching's equivalent of the 'auth' cookie should be secure.
	 *
	 * This is used to determine whether to set a secure auth cookie or not.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @return bool Should the auth cookie be secure?
	 */
	public static function secure_auth_cookie() {
		return ( is_ssl() && ( 'https' === parse_url( wp_login_url(), PHP_URL_SCHEME ) ) );
	}

	/**
	 * Returns background and color value base on current color scheme
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @return array Should the array contain background and text color
	 */
	public static function admin_bar_link_color_scheme() {
		$current_color = get_user_option( 'admin_color' );
		switch ( $current_color ) {
			case 'fresh':
				return array(
					'background' => '#0073aa',
					'color'      => '#fff',
				);
				break;
			case 'light':
				return array(
					'background' => '#888',
					'color'      => '#fff',
				);
				break;
			case 'blue':
				return array(
					'background' => '#096484',
					'color'      => '#fff',
				);
				break;
			case 'coffee':
				return array(
					'background' => '#c7a589',
					'color'      => '#fff',
				);
				break;
			case 'ectoplasm':
				return array(
					'background' => '#a3b745',
					'color'      => '#fff',
				);
			case 'midnight':
				return array(
					'background' => '#e14d43',
					'color'      => '#fff',
				);
			case 'ocean':
				return array(
					'background' => '#9ebaa0',
					'color'      => '#fff',
				);
			case 'sunrise':
				return array(
					'background' => '#dd823b',
					'color'      => '#fff',
				);
				break;
		}
	}

	/**
	 * Filters a user's capabilities so they can be altered at runtime.
	 *
	 * This is used to:
	 *  - Grant the 'bp_member_switch_to' capability to the user if they have the ability to edit the user they're trying to
	 *    switch to (and that user is not themselves).
	 *  - Grant the 'switch_off' capability to the user if they can edit other users.
	 *
	 * Important: This does not get called for Super Admins. See filter_map_meta_cap() below.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param bool[]   $user_caps Array of key/value pairs where keys represent a capability name and boolean values
	 *                                  represent whether the user has that capability.
	 * @param string[] $required_caps Required primitive capabilities for the requested capability.
	 * @param array    $args {
	 *        Arguments that accompany the requested capability check.
	 *
	 * @type string    $0 Requested capability.
	 * @type int       $1 Concerned user ID.
	 * @type mixed  ...$2 Optional second and further parameters.
	 * }
	 *
	 * @param WP_User  $user Concerned user object.
	 *
	 * @return bool[] Concerned user's capabilities.
	 */
	public function filter_user_has_cap( array $user_caps, array $required_caps, array $args, WP_User $user ) {
		if ( 'switch_to_user' === $args[0] ) {
			if ( array_key_exists( 'switch_users', $user_caps ) ) {
				$user_caps['switch_to_user'] = $user_caps['switch_users'];

				return $user_caps;
			}

			if ( ! isset( $args[2] ) ) {
				$args[2] = array();
			}

			$user_caps['switch_to_user'] = ( user_can( $user->ID, 'edit_user', $args[2] ) && ( $args[2] !== $user->ID ) );
		} elseif ( 'switch_off' === $args[0] ) {
			if ( array_key_exists( 'switch_users', $user_caps ) ) {
				$user_caps['switch_off'] = $user_caps['switch_users'];

				return $user_caps;
			}

			$user_caps['switch_off'] = user_can( $user->ID, 'edit_users' );
		}

		return $user_caps;
	}

	/**
	 * Filters the required primitive capabilities for the given primitive or meta capability.
	 *
	 * This is used to:
	 *  - Add the 'do_not_allow' capability to the list of required capabilities when a Super Admin is trying to switch
	 *    to themselves.
	 *
	 * It affects nothing else as Super Admins can do everything by default.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param string[] $required_caps Required primitive capabilities for the requested capability.
	 * @param string   $cap Capability or meta capability being checked.
	 * @param int      $user_id Concerned user ID.
	 * @param array    $args {
	 *        Arguments that accompany the requested capability check.
	 *
	 * @type mixed ...$0 Optional second and further parameters.
	 * }
	 * @return string[] Required capabilities for the requested action.
	 */
	public function filter_map_meta_cap( array $required_caps, $cap, $user_id, array $args ) {
		if ( isset( $args[0] ) && ( 'switch_to_user' === $cap ) && ( $args[0] === $user_id ) ) {
			$required_caps[] = 'do_not_allow';
		}

		return $required_caps;
	}

	/**
	 * Filter force show the admin bar while switched to the user
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param $retval
	 *
	 * @return bool
	 */
	public function filter_show_admin_bar( $retval ) {
		$old_user = self::get_old_user();

		if ( $old_user instanceof WP_User ) {
			return true;
		}

		return $retval;
	}

	/**
	 * Singleton instance.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @return BP_Core_Members_Switching Member Switching instance.
	 */
	public static function get_instance() {
		static $instance;

		if ( ! isset( $instance ) ) {
			$instance = new self();
		}

		return $instance;
	}

	/**
	 * Private class constructor. Use `get_instance()` to get the instance.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	final private function __construct() {
	}

}

$GLOBALS['bp_members_switching'] = BP_Core_Members_Switching::get_instance();
$GLOBALS['bp_members_switching']->init_hooks();

<?php
/**
 * Functions of BuddyPress's "Nouveau" template pack.
 *
 * @since BuddyPress 3.0.0
 * @version 3.1.0
 *
 * @buddypress-template-pack {
 *   Template Pack ID:       nouveau
 *   Template Pack Name:     BP Nouveau
 *   Version:                1.0.0
 *   WP required version:    4.5.0
 *   BP required version:    3.0.0
 *   Description:            A new template pack for BuddyPress!
 *   Text Domain:            bp-nouveau
 *   Domain Path:            /languages/
 *   Author:                 The BuddyPress community
 *   Template Pack Supports: activity, blogs, friends, groups, messages, notifications, settings, xprofile
 * }}
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/** Theme Setup ***************************************************************/

/**
 * Loads BuddyPress Nouveau Template pack functionality.
 *
 * See @link BP_Theme_Compat() for more.
 *
 * @since BuddyPress 3.0.0
 */
class BP_Nouveau extends BP_Theme_Compat {
	/**
	 * Instance of this class.
	 */
	protected static $instance = null;

	/**
	 * Return the instance of this class.
	 *
	 * @since BuddyPress 3.0.0
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * The BP Nouveau constructor.
	 *
	 * @since BuddyPress 3.0.0
	 */
	public function __construct() {
		parent::start();

		$this->includes();
		$this->setup_support();
	}

	/**
	 * BP Nouveau global variables.
	 *
	 * @since BuddyPress 3.0.0
	 */
	protected function setup_globals() {
		$bp = buddypress();

		foreach ( $bp->theme_compat->packages['nouveau'] as $property => $value ) {
			$this->{$property} = $value;
		}

		$this->includes_dir  = trailingslashit( $this->dir ) . 'includes/';
		$this->directory_nav = new BP_Core_Nav();
	}

	/**
	 * Includes!
	 *
	 * @since BuddyPress 3.0.0
	 */
	protected function includes() {
		require $this->includes_dir . 'functions.php';
		require $this->includes_dir . 'classes.php';
		require $this->includes_dir . 'template-tags.php';

		// Test suite requires the AJAX functions early.
		if ( function_exists( 'tests_add_filter' ) ) {
			require $this->includes_dir . 'ajax.php';

		// Load AJAX code only on AJAX requests.
		} else {
			add_action( 'admin_init', function() {
				if ( defined( 'DOING_AJAX' ) && true === DOING_AJAX ) {
					require $this->includes_dir . 'ajax.php';
				}
			}, 0 );
		}

		add_action( 'bp_customize_register', function() {
			if ( bp_is_root_blog() && current_user_can( 'customize' ) ) {
				require $this->includes_dir . 'customizer.php';
			}
		}, 0 );

		foreach ( bp_core_get_packaged_component_ids() as $component ) {
			$component_loader = trailingslashit( $this->includes_dir ) . $component . '/loader.php';

			if ( ! bp_is_active( $component ) || ! file_exists( $component_loader ) ) {
				continue;
			}

			require( $component_loader );
		}

		if ( bp_is_active( 'activity' ) && bp_is_activity_follow_active() ) {
			$component_loader = trailingslashit( $this->includes_dir ) . 'follow/loader.php';

			if ( file_exists( $component_loader ) ) {
				require( $component_loader );
			}
		}

		/**
		 * Fires after all of the BuddyPress Nouveau includes have been loaded. Passed by reference.
		 *
		 * @since BuddyPress 3.0.0
		 *
		 * @param BP_Nouveau $value Current BP_Nouveau instance.
		 */
		do_action_ref_array( 'bp_nouveau_includes', array( &$this ) );
	}

	/**
	 * Setup the Template Pack features support.
	 *
	 * @since BuddyPress 3.0.0
	 */
	protected function setup_support() {
		$width         = 1178;
		$top_offset    = 200;

		/** This filter is documented in bp-core/bp-core-avatars.php. */
		$avatar_height = apply_filters( 'bp_core_avatar_full_height', $top_offset );

		if ( $avatar_height > $top_offset ) {
			$top_offset = $avatar_height;
		}

		bp_set_theme_compat_feature( $this->id, array(
			'name'     => 'cover_image',
			'settings' => array(
				'components'   => array( 'xprofile', 'groups' ),
				'width'        => $width,
				'height'       => $top_offset + round( $avatar_height / 2 ),
				'callback'     => 'bp_nouveau_theme_cover_image',
				'theme_handle' => 'bp-nouveau',
			),
		) );
	}

	/**
	 * Setup the Template Pack common actions.
	 *
	 * @since BuddyPress 3.0.0
	 */
	protected function setup_actions() {
		// Filter BuddyPress template hierarchy and look for page templates.
		add_filter( 'bp_get_buddypress_template', array( $this, 'theme_compat_page_templates' ), 10, 1 );

		// Add our "buddypress" div wrapper to theme compat template parts.
		add_filter( 'bp_replace_the_content', array( $this, 'theme_compat_wrapper' ), 999 );

		// We need to neutralize the BuddyPress core "bp_core_render_message()" once it has been added.
		add_action( 'bp_actions', array( $this, 'neutralize_core_template_notices' ), 6 );

		// Scripts.
		add_action( 'bp_enqueue_scripts', array( $this, 'register_scripts' ), 2 ); // Register theme JS.
		remove_action( 'bp_enqueue_scripts', 'bp_core_confirmation_js' );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ), 1 ); // Enqueue theme CSS.
		add_action( 'bp_admin_enqueue_scripts', array( $this, 'enqueue_styles' ) ); // Enqueue theme CSS.
		add_action( 'bp_enqueue_scripts', array( $this, 'enqueue_scripts' ) ); // Enqueue theme JS.
		add_filter( 'bp_enqueue_scripts', array( $this, 'localize_scripts' ) ); // Enqueue theme script localization.
		add_filter( 'wp_enqueue_scripts', array( $this, 'check_heartbeat_api' ), PHP_INT_MAX );
		add_filter( 'wp_enqueue_scripts', array( $this, 'presence_localize_scripts' ), PHP_INT_MAX ); // Enqueue theme script localization.

		// Register login and forgot password popup link.
		add_action( 'login_enqueue_scripts', array( $this, 'register_scripts' ), 2 );
		add_action( 'login_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'login_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'login_head', array( $this, 'platform_login_scripts' ) );

		// Body no-js class.
		add_filter( 'body_class', array( $this, 'add_nojs_body_class' ), 20, 1 );

		// Ajax querystring.
		add_filter( 'bp_ajax_querystring', 'bp_nouveau_ajax_querystring', 10, 2 );

		// Register directory nav items.
		add_action( 'bp_screens', array( $this, 'setup_directory_nav' ), 15 );

		// Set the BP Uri for the Ajax customizer preview.
		add_filter( 'bp_uri', array( $this, 'customizer_set_uri' ), 10, 1 );

		// Set the forum slug on edit page from backend.
		add_action( 'save_post', array( $this, 'bp_change_forum_slug_on_edit_save_page'), 10, 2 );

		// Set the Forums to selected in menu items.
		add_filter( 'nav_menu_css_class', array( $this, 'bbp_set_forum_selected_menu_class'), 10, 3 );

		/** Override **********************************************************/

		/**
		 * Fires after all of the BuddyPress theme compat actions have been added.
		 *
		 * @since BuddyPress 3.0.0
		 *
		 * @param BP_Nouveau $this Current BP_Nouveau instance.
		 */
		do_action_ref_array( 'bp_theme_compat_actions', array( &$this ) );
	}

	/**
	 * Set the Forums to selected in menu items
	 *
	 * @param array $classes
	 * @param bool $item
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @return array
	 */
	public function bbp_set_forum_selected_menu_class( $classes = array(), $item = false ) {

		// Return if forums not active.
		if ( ! bp_is_active( 'forums' ) ) {
			return $classes;
		}

		// Protocol
		$url = ( is_ssl() ? 'https' : 'http' ) . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

		// Get current URL
		$current_url = trailingslashit( $url );

		// Get homepage URL
		$homepage_url = trailingslashit( get_bloginfo( 'url' ) );

		// Exclude 404 and homepage
		if ( is_404() || ( isset( $item->url ) && $item->url == $homepage_url ) ) {
			return $classes;
		}

		if ( bbp_get_forum_post_type() === get_post_type() || bbp_get_topic_post_type() === get_post_type() ) {
			// Unset current_page_parent class if exists.
			if ( in_array( 'current_page_parent', $classes ) ) {
				unset( $classes[ array_search( 'current_page_parent', $classes ) ] );
			}
			if ( ! empty( $item->url ) && ! empty( $current_url ) && strstr( $current_url, $item->url ) ) {
				$classes[] = 'current-menu-item';
			}
		}

		return $classes;
	}

	/**
	 * Set the forum slug on edit page from backend.
	 *
	 * @param $post_id
	 * @param $post
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function bp_change_forum_slug_on_edit_save_page( $post_id, $post ) {
		// if called by autosave, then bail here
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return;

		// if this "post" post type?
		if ( $post->post_type != 'page' )
			return;

		// does this user have permissions?
		if ( ! current_user_can( 'edit_post', $post_id ) )
			return;

		// update!
		$forum_page_id = (int) bp_get_option('_bbp_root_slug_custom_slug');

		if ( $forum_page_id > 0  && $forum_page_id === $post_id ) {
			$slug    = get_page_uri( $forum_page_id );
			if ( '' !== $slug ) {
				bp_update_option( '_bbp_root_slug', urldecode( $slug ) );
				bp_update_option( 'rewrite_rules', '' );
			}
		}
	}

	public function platform_login_scripts() {
		?>
		<script>
			jQuery( document ).ready( function () {
				if ( jQuery('.popup-modal-register').length ) {
					jQuery('.popup-modal-register').magnificPopup({
						type: 'inline',
						preloader: false,
						fixedContentPos: true,
						modal: true
					});
					jQuery('.popup-modal-dismiss').click(function (e) {
						e.preventDefault();
						$.magnificPopup.close();
					});
				}
				if ( jQuery('.popup-modal-login').length ) {
					jQuery('.popup-modal-login').magnificPopup({
                        type: 'inline',
    					preloader: false,
                        fixedBgPos: true,
    					fixedContentPos: true
					});
					jQuery('.popup-modal-dismiss').click(function (e) {
						e.preventDefault();
						$.magnificPopup.close();
					});
				}
			});
		</script>
		<?php

	}

	/**
	 * Enqueue the template pack css files
	 *
	 * @since BuddyPress 3.0.0
	 */
	public function enqueue_styles() {
		global $pagenow;

		if (
			! empty( $pagenow ) &&
			in_array( $pagenow, array( 'plugin-editor.php', 'theme-editor.php' ), true )
		) {
			return;
		}

		$min = bp_core_get_minified_asset_suffix();
		$rtl = '';

		if ( is_rtl() ) {
			$rtl = '-rtl';
		}

		// BB icon version.
		$bb_icon_version = function_exists( 'bb_icon_font_map_data' ) ? bb_icon_font_map_data( 'version' ) : '';
		$bb_icon_version = ! empty( $bb_icon_version ) ? $bb_icon_version : $this->version;

		/**
		 * Filters the BuddyPress Nouveau CSS dependencies.
		 *
		 * @since BuddyPress 3.0.0
		 *
		 * @param array $value Array of style dependencies. Default Dashicons.
		 */
		$css_dependencies = apply_filters( 'bp_nouveau_css_dependencies', array( 'dashicons' ) );

		/**
		 * Filters the styles to enqueue for BuddyPress Nouveau.
		 *
		 * This filter provides a multidimensional array that will map to arguments used for wp_enqueue_style().
		 * The primary index should have the stylesheet handle to use, and be assigned an array that has indexes for
		 * file location, dependencies, and version.
		 *
		 * @since BuddyPress 3.0.0
		 *
		 * @param array $value Array of styles to enqueue.
		 */
		$styles = apply_filters(
			'bp_nouveau_enqueue_styles',
			array(
				'bp-nouveau-icons-map' => array(
					'file'         => 'icons/css/icons-map%s.css',
					'dependencies' => array(),
					'version'      => $this->version,
				),
				'bp-nouveau-bb-icons'  => array(
					'file'         => 'icons/css/bb-icons%1$s%2$s.css',
					'dependencies' => array(),
					'version'      => $bb_icon_version,
				),
				'bp-nouveau'           => array(
					'file'         => 'css/buddypress%1$s%2$s.css',
					'dependencies' => $css_dependencies,
					'version'      => $this->version,
				),
			)
		);

		if ( $styles ) {

			foreach ( $styles as $handle => $style ) {
				if ( ! isset( $style['file'] ) ) {
					continue;
				}

				if ( 'bp-nouveau-icons-map' === $handle ) {
					$file = sprintf( $style['file'], $min );
				} else {
					$file = sprintf( $style['file'], $rtl, $min );
				}

				// Locate the asset if needed.
				if ( false === strpos( $style['file'], '://' ) ) {
					$asset = bp_locate_template_asset( $file );

					if ( empty( $asset['uri'] ) || false === strpos( $asset['uri'], '://' ) ) {
						continue;
					}

					$file = $asset['uri'];
				}

				$data = bp_parse_args( $style, array(
					'dependencies' => array(),
					'version'      => $this->version,
					'type'         => 'screen',
				) );

				wp_enqueue_style( $handle, $file, $data['dependencies'], $data['version'], $data['type'] );

				if ( $min ) {
					wp_style_add_data( $handle, 'suffix', $min );
				}
			}
		}
	}

	/**
	 * Register Template Pack JavaScript files
	 *
	 * @since BuddyPress 3.0.0
	 */
	public function register_scripts() {
		$min          = bp_core_get_minified_asset_suffix();
		$dependencies = bp_core_get_js_dependencies();
		$bp_confirm   = array_search( 'bp-confirm', $dependencies );

		unset( $dependencies[ $bp_confirm ] );

		/**
		 * Filters the scripts to enqueue for BuddyPress Nouveau.
		 *
		 * This filter provides a multidimensional array that will map to arguments used for wp_register_script().
		 * The primary index should have the script handle to use, and be assigned an array that has indexes for
		 * file location, dependencies, version and if it should load in the footer or not.
		 *
		 * @since BuddyPress 3.0.0
		 *
		 * @param array $value Array of scripts to register.
		 */
		$scripts = apply_filters( 'bp_nouveau_register_scripts', array(
			'bp-nouveau' => array(
				'file'         => 'js/buddypress-nouveau%s.js',
				'dependencies' => $dependencies,
				'version'      => $this->version,
				'footer'       => true,
			),
			'guillotine-js' => array(
				'file'         => 'js/jquery.guillotine.min.js',
				'dependencies' => $dependencies,
				'version'      => $this->version,
				'footer'       => true,
			),
		) );

		// Bail if no scripts
		if ( empty( $scripts ) ) {
			return;
		}

		// Add The password verify if needed.
		if ( bp_is_active( 'settings' ) || bp_get_signup_allowed() ) {
			$scripts['bp-nouveau-password-verify'] = array(
				'file'         => 'js/password-verify%s.js',
				'dependencies' => array( 'bp-nouveau', 'password-strength-meter' ),
				'footer'       => true,
			);
		}

		$scripts['bp-nouveau-magnific-popup'] = array(
			'file'         => buddypress()->plugin_url . 'bp-core/js/vendor/magnific-popup.js',
			'dependencies' => array( 'jquery' ),
			'footer'       => false,
		);

		if ( bp_is_active( 'media' ) ) {

			$scripts['bp-nouveau-codemirror'] = array(
				'file'         => buddypress()->plugin_url . 'bp-core/js/vendor/codemirror%s.js',
				'dependencies' => array(),
				'footer'       => true,
			);

			$scripts['bp-nouveau-codemirror-css'] = array(
				'file'         => buddypress()->plugin_url . 'bp-core/js/vendor/css%s.js',
				'dependencies' => array(),
				'footer'       => true,
			);

		}

		foreach ( $scripts as $handle => $script ) {
			if ( ! isset( $script['file'] ) ) {
				continue;
			}

			$file = sprintf( $script['file'], $min );

			// Locate the asset if needed.
			if ( false === strpos( $script['file'], '://' ) ) {
				$asset = bp_locate_template_asset( $file );

				if ( empty( $asset['uri'] ) || false === strpos( $asset['uri'], '://' ) ) {
					continue;
				}

				$file = $asset['uri'];
			}

			$data = bp_parse_args( $script, array(
				'dependencies' => array(),
				'version'      => $this->version,
				'footer'       => false,
			) );

			wp_register_script( $handle, $file, $data['dependencies'], $data['version'], $data['footer'] );
		}

		wp_localize_script( 'bp-nouveau-messages-at', 'BP_Mentions_Options', bp_at_mention_default_options() );
	}

	/**
	 * Enqueue the required JavaScript files
	 *
	 * @since BuddyPress 3.0.0
	 */
	public function enqueue_scripts() {

		if ( bp_is_register_page() || ( isset( $GLOBALS['pagenow'] ) && 'wp-login.php' === $GLOBALS['pagenow'] ) || bp_is_active( 'moderation' ) ) {
		    wp_enqueue_script( 'bp-nouveau-magnific-popup' );
	    }

		wp_enqueue_script( 'bp-nouveau' );
		wp_enqueue_script( 'guillotine-js' );


		if ( bp_is_register_page() || bp_is_user_settings_general() ) {
			wp_enqueue_script( 'bp-nouveau-password-verify' );
		}

		if ( is_singular() && bp_is_blog_page() && get_option( 'thread_comments' ) ) {
			wp_enqueue_script( 'comment-reply' );
		}

		wp_enqueue_script( 'heartbeat' );

		/**
		 * Fires after all of the BuddyPress Nouveau scripts have been enqueued.
		 *
		 * @since BuddyPress 3.0.0
		 */
		do_action( 'bp_nouveau_enqueue_scripts' );
	}

	/**
	 * Check the Heartbeat API if it is enabled or not on front end
     *
     * @since BuddyBoss 1.1.2
	 */
	public function check_heartbeat_api() {
		if ( ! wp_script_is( 'heartbeat', 'registered' ) && ! is_admin() ) {
			update_option( 'bp_wp_heartbeat_disabled', '1' );
		} else {
			update_option( 'bp_wp_heartbeat_disabled', '0' );
        }
    }

	/**
	 * Adds the no-js class to the body tag.
	 *
	 * This function ensures that the <body> element will have the 'no-js' class by default. If you're
	 * using JavaScript for some visual functionality in your theme, and you want to provide noscript
	 * support, apply those styles to body.no-js.
	 *
	 * The no-js class is removed by the JavaScript created in buddypress.js.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param array $classes Array of classes to append to body tag.
	 *
	 * @return array $classes
	 */
	public function add_nojs_body_class( $classes ) {
		$classes[] = 'no-js';
		return array_unique( $classes );
	}

	/**
	 * Load localizations for topic script.
	 *
	 * These localizations require information that may not be loaded even by init.
	 *
	 * @since BuddyPress 3.0.0
	 */
	public function localize_scripts() {

		$params = array(
			'ajaxurl'            => bp_core_ajax_url(),
			'only_admin_notice'  => __( 'As you are the only organizer of this group, you cannot leave it. You can either delete the group or promote another member to be an organizer first and then leave the group.', 'buddyboss' ),
			'is_friend_confirm'  => __( 'Are you sure you want to remove your connection with this member?', 'buddyboss' ),
			'confirm'            => __( 'Are you sure?', 'buddyboss' ),
			'confirm_delete_set' => __( 'Are you sure you want to delete this set? This cannot be undone.', 'buddyboss' ),
			'show_x_comments'    => __( 'View previous comments', 'buddyboss' ),
			'unsaved_changes'    => __( 'Your profile has unsaved changes. If you leave the page, the changes will be lost.', 'buddyboss' ),
			'object_nav_parent'  => '#buddypress',
			'anchorPlaceholderText' => __( 'Paste or type a link', 'buddyboss' ),
			'empty_field'        => __( 'New Field', 'buddyboss' ),
			'close'              => __( 'Close', 'buddyboss' ),
		);

		// If the Object/Item nav are in the sidebar
		if ( bp_nouveau_is_object_nav_in_sidebar() ) {
			$params['object_nav_parent'] = '.buddypress_object_nav';
		}

		/**
		 * Filters the supported BuddyPress Nouveau components.
		 *
		 * @since BuddyPress 3.0.0
		 *
		 * @param array $value Array of supported components.
		 */
		$supported_objects = (array) apply_filters( 'bp_nouveau_supported_components', bp_core_get_packaged_component_ids() );
		$object_nonces     = array();
		$group_sub_objects = false;

		foreach ( $supported_objects as $key_object => $object ) {
			if ( ! bp_is_active( $object ) || 'forums' === $object ) {
				unset( $supported_objects[ $key_object ] );
				continue;
			}

			if ( 'groups' === $object ) {
				$group_sub_objects = true;
			}

			$object_nonces[ $object ] = wp_create_nonce( 'bp_nouveau_' . $object );
		}

		if ( true === $group_sub_objects ) {
			$supported_objects = array_merge( $supported_objects, array( 'group_members', 'group_requests', 'group_subgroups' ) );
		}

//		if ( bp_is_active( 'media' ) ) {
//			$supported_objects = array_merge( $supported_objects, array( 'document' ) );
//		}

		// Add components & nonces
		$params['objects'] = $supported_objects;
		$params['nonces']  = $object_nonces;

		// Used to transport the settings inside the Ajax requests
		if ( is_customize_preview() ) {
			$params['customizer_settings'] = bp_nouveau_get_temporary_setting( 'any' );
		}

		/**
		 * Filters core JavaScript strings for internationalization before AJAX usage.
		 *
		 * @since BuddyPress 3.0.0
		 *
		 * @param array $params Array of key/value pairs for AJAX usage.
		 */
		wp_localize_script( 'bp-nouveau', 'BP_Nouveau', apply_filters( 'bp_core_get_js_strings', $params ) );
	}

	/**
	 * Load localizations for presence script.
	 *
	 * These localizations require information that may not be loaded even by init.
	 *
	 * @since BuddyBoss 2.1.4
	 */
	public function presence_localize_scripts() {

		$params = array(
			'heartbeat_enabled'         => bb_is_heartbeat_enabled(),
			'presence_interval'         => bb_presence_interval(),
			'presence_default_interval' => bb_presence_default_interval(),
			'presence_time_span'        => bb_presence_time_span(),
			'idle_inactive_span'        => bb_idle_inactive_span(),
			'rest_nonce'                => wp_create_nonce( 'wp_rest' ),
			'native_presence'           => (bool) bp_get_option( 'bb_use_core_native_presence', false ),
			'native_presence_url'       => buddypress()->plugin_url . 'bp-core/bb-core-native-presence.php',
			'presence_rest_url'         => home_url( 'wp-json/buddyboss/v1/members/presence' ),
		);

		/**
		 * Filters core JavaScript strings for internationalization before AJAX usage.
		 *
		 * @since BuddyBoss 2.1.4
		 *
		 * @param array $params Array of key/value pairs for AJAX usage.
		 */
		wp_localize_script( 'bp-nouveau', 'BB_Nouveau_Presence', apply_filters( 'presence_localize_scripts', $params ) );
	}

	/**
	 * Filter the default theme compatibility root template hierarchy, and prepend
	 * a page template to the front if it's set.
	 *
	 * @see https://buddypress.trac.wordpress.org/ticket/6065
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param array $templates Array of templates.
	 *
	 * @return array
	 */
	public function theme_compat_page_templates( $templates = array() ) {
		/**
		 * Filters whether or not we are looking at a directory to determine if to return early.
		 *
		 * @since BuddyPress 3.0.0
		 *
		 * @param bool $value Whether or not we are viewing a directory.
		 */
		if ( true === (bool) apply_filters( 'bp_nouveau_theme_compat_page_templates_directory_only', ! bp_is_directory() ) ) {
			return $templates;
		}

		// No page ID yet.
		$page_id = 0;

		// Get the WordPress Page ID for the current view.
		foreach ( (array) buddypress()->pages as $component => $bp_page ) {

			// Handles the majority of components.
			if ( bp_is_current_component( $component ) ) {
				$page_id = (int) $bp_page->id;
			}

			// Stop if not on a user page.
			if ( ! bp_is_user() && ! empty( $page_id ) ) {
				break;
			}

			// The Members component requires an explicit check due to overlapping components.
			if ( bp_is_user() && ( 'members' === $component ) ) {
				$page_id = (int) $bp_page->id;
				break;
			}
		}

		// Bail if no directory page set.
		if ( 0 === $page_id ) {
			return $templates;
		}

		// Check for page template.
		$page_template = get_page_template_slug( $page_id );

		// Add it to the beginning of the templates array so it takes precedence over the default hierarchy.
		if ( ! empty( $page_template ) ) {

			/**
			 * Check for existence of template before adding it to template
			 * stack to avoid accidentally including an unintended file.
			 *
			 * @see https://buddypress.trac.wordpress.org/ticket/6190
			 */
			if ( '' !== locate_template( $page_template ) ) {
				array_unshift( $templates, $page_template );
			}
		}

		return $templates;
	}

	/**
	 * Add our special 'buddypress' div wrapper to the theme compat template part.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @see bp_buffer_template_part()
	 *
	 * @param string $retval Current template part contents.
	 *
	 * @return string
	 */
	public function theme_compat_wrapper( $retval ) {
		if ( false !== strpos( $retval, '<div id="buddypress"' ) ) {
			return $retval;
		}

		// Add our 'buddypress' div wrapper.
		return sprintf(
			'<div id="buddypress" class="%1$s">%2$s</div><!-- #buddypress -->%3$s',
			esc_attr( bp_nouveau_get_container_classes() ),
			$retval,  // Constructed HTML.
			"\n"
		);
	}

	/**
	 * Define the directory nav items
	 *
	 * @since BuddyPress 3.0.0
	 */
	public function setup_directory_nav() {
		$nav_items = array();

		if ( bp_is_members_directory() ) {
			$nav_items = bp_nouveau_get_members_directory_nav_items();
		} elseif ( bp_is_activity_directory() ) {
			$nav_items = bp_nouveau_get_activity_directory_nav_items();
		} elseif ( bp_is_groups_directory() ) {
			$nav_items = bp_nouveau_get_groups_directory_nav_items();
		} elseif ( bp_is_blogs_directory() ) {
			$nav_items = bp_nouveau_get_blogs_directory_nav_items();
		} elseif ( bp_is_media_directory() ) {
			$nav_items = bp_nouveau_get_media_directory_nav_items();
		} elseif ( bp_is_document_directory() ) {
			$nav_items = bp_nouveau_get_document_directory_nav_items();
		} elseif ( bp_is_video_directory() ) {
			$nav_items = bp_nouveau_get_video_directory_nav_items();
		}

		if ( empty( $nav_items ) ) {
			return;
		}

		foreach ( $nav_items as $nav_item ) {
			if ( empty( $nav_item['component'] ) || $nav_item['component'] !== bp_current_component() ) {
				continue;
			}

			// Define the primary nav for the current component's directory
			$this->directory_nav->add_nav( $nav_item );
		}
	}

	/**
	 * We'll handle template notices from BP Nouveau.
	 *
	 * @since BuddyPress 3.0.0
	 */
	public function neutralize_core_template_notices() {
		remove_action( 'template_notices', 'bp_core_render_message' );
	}

	/**
	 * Set the BP Uri for the customizer in case of Ajax requests.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param  string $path the BP Uri.
	 * @return string       the BP Uri.
	 */
	public function customizer_set_uri( $path ) {
		if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
			return $path;
		}

		$uri = parse_url( $path );

		if ( false === strpos( $uri['path'], 'customize.php' ) ) {
			return $path;
		} else {
			$vars = bp_parse_args( $uri['query'], array() );

			if ( ! empty( $vars['url'] ) ) {
				$path = str_replace( get_site_url(), '', urldecode( $vars['url'] ) );
			}
		}

		return $path;
	}
}

/**
 * Get a unique instance of BP Nouveau
 *
 * @since BuddyPress 3.0.0
 *
 * @return BP_Nouveau the main instance of the class
 */
function bp_nouveau() {
	return BP_Nouveau::get_instance();
}

/**
 * Launch BP Nouveau!
 */
bp_nouveau();

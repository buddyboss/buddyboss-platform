<?php
/**
 * BP REST: BP_REST_Theme_Settings_Endpoint class
 *
 * @package BuddyBoss
 * @since 0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Theme Settings endpoints.
 *
 * @since 0.1.0
 */
class BP_REST_Theme_Settings_Endpoint extends WP_REST_Controller {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->namespace = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base = 'theme-settings';
	}

	/**
	 * Register the component routes.
	 *
	 * @since 0.1.0
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base.'/header-menu-icons',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_icons' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
			)
		);
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base.'/social-icons',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_social_icons' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
			)
		);
	}


	/**
	 * Retrieve Header Menu Icons.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/theme-settings Theme Settings
	 * @apiGroup       Theme Settings
	 * @apiDescription Retrieve header menu icons.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 */
	public function get_icons( $request ) {
        $icon = false;
        $header_menu_icons = array();
        if ( function_exists( 'bp_is_active' ) && has_nav_menu( 'header-my-account' ) ) {
            $menu_name = 'header-my-account';
            if ( ( $locations = get_nav_menu_locations() ) && isset( $locations[ $menu_name ] ) ) {
                $menu = wp_get_nav_menu_object( $locations[ $menu_name ] );
                $menu_items = wp_get_nav_menu_items( $menu->term_id );
                if(!empty($menu_items)){
                    foreach( $menu_items as $item ) {
                        if ( class_exists( 'Menu_Icons' ) || class_exists( 'Buddyboss_Menu_Icons' ) ) {
                            $meta = Menu_Icons_Meta::get( $item->ID );
                            if ( ! class_exists( 'Menu_Icons_Front_End' ) ) {
                                $path = ABSPATH . 'wp-content/themes/buddyboss-theme/inc/plugins/buddyboss-menu-icons/includes/front.php';
                                if ( file_exists( $path ) ) {
                                    require_once $path;
                                    Menu_Icons_Front_End::init();
                                    $icon = Menu_Icons_Front_End::get_icon( $meta );
                                }
                            } else {
                                $icon = Menu_Icons_Front_End::get_icon( $meta );
                            }
                        }
        
                        if ( ! $icon ) {
                            if ( in_array( 'bp-menu', $item->classes ) ) {
                                if ( 'bp-profile-nav' === $item->classes[1] ) {
                                    $icon = 'bb-icon-user-alt';
                                } elseif ( 'bp-settings-nav' === $item->classes[1] ) {
                                    $icon = 'bb-icon-settings';
                                } elseif ( 'bp-activity-nav' === $item->classes[1] ) {
                                    $icon = 'bb-icon-activity';
                                } elseif ( 'bp-notifications-nav' === $item->classes[1] ) {
                                    $icon = 'bb-icon-bell-small';
                                } elseif ( 'bp-messages-nav' === $item->classes[1] ) {
                                    $icon = 'bb-icon-inbox-small';
                                } elseif ( 'bp-friends-nav' === $item->classes[1] ) {
                                    $icon = 'bb-icon-users';
                                } elseif ( 'bp-groups-nav' === $item->classes[1] ) {
                                    $icon = 'bb-icon-groups';
                                } elseif ( 'bp-forums-nav' === $item->classes[1] ) {
                                    $icon = 'bb-icon-discussion';
                                } elseif ( 'bp-videos-nav' === $item->classes[1] ) {
                                    $icon = 'bb-icon-video';
                                } elseif ( 'bp-documents-nav' === $item->classes[1] ) {
                                    $icon = 'bb-icon-folder-stacked';
                                } elseif ( 'bp-photos-nav' === $item->classes[1] ) {
                                    $icon = 'bb-icon-image-square';
                                } elseif ( 'bp-invites-nav' === $item->classes[1] ) {
                                    $icon = 'bb-icon-mail';
                                } elseif ( 'bp-logout-nav' === $item->classes[1] ) {
                                    $icon = 'bb-icon-log-out';
                                } elseif ( 'bp-login-nav' === $item->classes[1] ) {
                                    $icon = 'bb-icon-log-in';
                                } elseif ( 'bp-register-nav' === $item->classes[1] ) {
                                    $icon = 'bb-icon-clipboard';
                                } elseif ( 'bp-courses-nav' === $item->classes[1] ) {
                                    $icon = 'bb-icon-graduation-cap';
                                }
                            }
                        }
                        $header_menu_icons[$item->classes[1]] = array(
                            'icon' => $icon,
                        );
                    }
                }
                else{
                    $header_menu_icons = $this->bp_rest_default_menu_icons();
                }
            }
            else{
                $header_menu_icons = $this->bp_rest_default_menu_icons();
            }
        }
        else{
            $header_menu_icons = $this->bp_rest_default_menu_icons();
        }
		      
    
        $response = rest_ensure_response( $header_menu_icons );
    
        /**
         * Fires after header menu icon are fetched via the REST API.
         *
         * @param WP_REST_Response $response The response data.
         * @param WP_REST_Request  $request  The request sent to the API.
         *
         * @since 0.1.0
         */
        do_action( 'bp_rest_theme_settings_get_icons', $response, $request );

		return $response;
	}

    /**
	 * Retrieve Social Menu Icons.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/theme-settings Theme Settings
	 * @apiGroup       Theme Settings
	 * @apiDescription Retrieve social icons.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 */
	public function get_social_icons( $request ) {
        $footer_socials = buddyboss_theme_get_option( 'boss_footer_social_links' );
        $response = array();
        if( !empty( $footer_socials ) ){
            foreach( $footer_socials as $network => $url ){
                if( !empty( $url ) ){
                    $response[$network] = array(
                        'icon' => 'bb-icon-rounded-' . $network,
                        'url'  => $url,
                    );
                }
            }
            $response = rest_ensure_response( $response );
        }
        /**
         * Fires after social icon are fetched via the REST API.
         *
         * @param WP_REST_Response $response The response data.
         * @param WP_REST_Request  $request  The request sent to the API.
         *
         * @since 0.1.0
         */
        do_action( 'bp_rest_theme_settings_get_social_icons', $response, $request );

        return $response;
    }

	/**
	 * Check if a given request has access to theme settings.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|bool
	 * @since 0.1.0
	 */
	public function get_items_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to see the theme settings.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval = true;
		}

		/**
		 * Filter the account settings `get_items` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_theme_settings_get_items_permissions_check', $retval, $request );
	}

    protected function bp_rest_default_menu_icons() {
		$items = array();

		if ( bp_is_active( 'xprofile' ) ) {
            $icon = 'bb-icon-user-alt';
			$item_xprofile = array(
				'icon' => $icon,
			);
			$items['bp-profile-nav'] = $item_xprofile;
		}

		if ( bp_is_active( 'settings' ) ) {
            $icon = 'bb-icon-settings';
			$item_settings = array(
				'icon' => $icon,
			);
			$items['bp-settings-nav'] = $item_settings;
		}

		if ( bp_is_active( 'activity' ) ) {
            $icon = 'bb-icon-activity';
			$item_activity = array(
				'icon' => $icon,
			);
			$items['bp-activity-nav'] = $item_activity;
		}

		if ( bp_is_active( 'notifications' ) ) {
            $icon = 'bb-icon-bell-small';
			$item_notification = array(
				'icon' => $icon,
			);
			$items['bp-notifications-nav'] = $item_notification;
		}

		if ( bp_is_active( 'messages' ) ) {
            $icon = 'bb-icon-inbox-small';
			$item_messages = array(
				'icon' => $icon,
			);
			$items['bp-messages-nav'] = $item_messages;
		}

		if ( bp_is_active( 'friends' ) ) {
            $icon = 'bb-icon-users';
			$item_friends = array(
				'icon' => $icon,
			);
			$items['bp-friends-nav'] = $item_friends;
		}

		if ( bp_is_active( 'groups' ) ) {
			
            $icon = 'bb-icon-groups';
			
			$item_groups = array(
				'icon' => $icon,
			);

			$items['bp-groups-nav'] = $item_groups;

		}

		if ( bp_is_active( 'forums' ) ) {
            $icon = 'bb-icon-discussion';
			$item_forums = array(
				'icon' => $icon,
			);
			$items['bp-forums-nav'] = $item_forums;
		}

		if ( bp_is_active( 'media' ) && function_exists( 'bp_is_profile_media_support_enabled' ) && bp_is_profile_media_support_enabled() ) {
            $icon = 'bb-icon-image-square';
			$item_media = array(
				'icon' => $icon,
			);

			$items['bp-photos-nav'] = $item_media;
		}

		if ( bp_is_active( 'media' ) && function_exists( 'bp_is_profile_document_support_enabled' ) && bp_is_profile_document_support_enabled() ) {
            $icon = 'bb-icon-folder-stacked';
			$item_documents = array(
				'icon' => $icon,
			);
			$items['bp-documents-nav'] = $item_documents;
		}

		if ( bp_is_active( 'invites' ) && function_exists( 'bp_allow_user_to_send_invites' ) && true === bp_allow_user_to_send_invites() ) {
            $icon = 'bb-icon-mail';
			$item_invites = array(
				'icon' => $icon,
			);
			$items['bp-invites-nav'] = $item_invites;
		}

		return $items;
	}
}

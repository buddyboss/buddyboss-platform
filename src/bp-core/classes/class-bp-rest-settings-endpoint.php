<?php
/**
 * BP REST: BP_REST_Settings_Endpoint class
 *
 * @package BuddyBoss
 * @since 0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Settings endpoints.
 *
 * @since 0.1.0
 */
class BP_REST_Settings_Endpoint extends WP_REST_Controller {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->namespace = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base = 'settings';
	}

	/**
	 * Register the component settings routes.
	 *
	 * @since 0.1.0
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);
	}

	/**
	 * Retrieve settings.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/settings Settings
	 * @apiName        GetBBSettings
	 * @apiGroup       Settings
	 * @apiDescription Retrieve settings
	 * @apiVersion     1.0.0
	 */
	public function get_items( $request ) {
		$args = array();

		/**
		 * Filter the query arguments for the request.
		 *
		 * @param array           $args    Key value array of query var to query value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		$args = apply_filters( 'bp_rest_settings_get_items_query_args', $args, $request );

		$bp_plugin_file                 = 'buddypress/bp-loader.php';
		$bb_plugin_file                 = 'bbpress/bbpress.php';
		$buddyboss_platform_plugin_file = 'buddyboss-platform/bp-loader.php';

		$results = array();

		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		// Check BuddyBoss Platform activate and get the settings.
		if ( is_plugin_active( $buddyboss_platform_plugin_file ) ) {
			$platform_settings   = $this->get_buddyboss_platform_settings();
			$results['platform'] = apply_filters( 'bp_rest_platform_settings', $platform_settings );
		} else {

			// Check BuddyPress activate and get the settings.
			if ( is_plugin_active( $bp_plugin_file ) ) {
				$buddypress_settings   = $this->get_buddypress_settings();
				$results['buddypress'] = apply_filters( 'bp_rest_buddypress_settings', $buddypress_settings );
			}

			// Check bbPress activate and get the settings.
			if ( is_plugin_active( $bb_plugin_file ) ) {
				$bbpress_settings   = $this->get_bbpress_settings();
				$results['bbpress'] = apply_filters( 'bp_rest_bbpress_settings', $bbpress_settings );
			}
		}

		$response = rest_ensure_response( $results );

		/**
		 * Fires after a list of settings is fetched via the REST API.
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_settings_get_items', $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to list settings.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function get_items_permissions_check( $request ) {
		$retval = true;

		/**
		 * Filter the settings `get_items` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_settings_get_items_permissions_check', $retval, $request );
	}

	/**
	 * Get the settings schema, conforming to JSON Schema.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'bp_settings',
			'type'       => 'object',
			'properties' => array(
				'name'        => array(
					'context'     => array( 'view' ),
					'description' => __( 'Name of the setting.', 'buddyboss' ),
					'type'        => 'string',
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_key',
					),
				),
				'status'      => array(
					'context'     => array( 'view' ),
					'description' => __( 'Whether the setting is active or inactive.', 'buddyboss' ),
					'type'        => 'string',
					'enum'        => array( 'active', 'inactive' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_key',
					),
				),
				'title'       => array(
					'context'     => array( 'view' ),
					'description' => __( 'Title of the setting.', 'buddyboss' ),
					'type'        => 'string',
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'description' => array(
					'context'     => array( 'view' ),
					'description' => __( 'Description of the setting.', 'buddyboss' ),
					'type'        => 'string',
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			),
		);

		/**
		 * Filters the settings schema.
		 *
		 * @param string $schema The endpoint schema.
		 */
		return apply_filters( 'bp_rest_settings_schema', $this->add_additional_fields_schema( $schema ) );
	}

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_collection_params() {
		$params['context']['default'] = 'view';

		/**
		 * Filters the collection query params.
		 *
		 * @param array $params Query params.
		 */
		return apply_filters( 'bp_rest_settings_collection_params', $params );
	}

	/**
	 * Unserialize array values.
	 *
	 * @param array $array Array with serialize data.
	 *
	 * @return array
	 */
	public function get_unserialize_data( $array ) {
		if ( empty( $array ) ) {
			return $array;
		}

		if ( ! empty( $array ) ) {
			$new_array = array();
			foreach ( $array as $key => $value ) {
				$new_array[ $key ] = maybe_unserialize( $value );
			}

			return $new_array;
		}

		return $array;

	}

	/**
	 * Get BuddyBoss Platform Settings.
	 *
	 * @return array
	 */
	public function get_buddyboss_platform_settings() {
		$results = array(
			// General settings.
			'bp-enable-site-registration'              => bp_enable_site_registration(),
			'allow-custom-registration'                => bp_allow_custom_registration(),
			'register-confirm-email'                   => bp_register_confirm_email(),
			'register-legal-agreement'                 => ( function_exists( 'bb_register_legal_agreement' ) ? bb_register_legal_agreement() : false ),
			'register-confirm-password'                => bp_register_confirm_password(),
			'bp-disable-account-deletion'              => bp_disable_account_deletion(),
			'bp-enable-private-network'                => ! bp_enable_private_network(),
			'bp-enable-private-network-public-content' => bp_enable_private_network_public_content(),

			// Profile settings.
			'bp-display-name-format'                   => bp_core_display_name_format(),
			'bp-hide-nickname-first-name'              => bp_hide_nickname_first_name(),
			'bp-hide-nickname-last-name'               => bp_hide_nickname_last_name(),
			'bp-profile-avatar-type'                   => function_exists( 'bb_get_profile_avatar_type' ) ? bb_get_profile_avatar_type() : '',
			'bp-disable-avatar-uploads'                => bp_disable_avatar_uploads(),
			'bp-default-profile-avatar-type'           => function_exists( 'bb_get_default_profile_avatar_type' ) ? bb_get_default_profile_avatar_type() : '',
			'bp-default-custom-profile-avatar'         => function_exists( 'bb_get_default_custom_upload_profile_avatar' ) ? bb_get_default_custom_upload_profile_avatar() : '',
			'bp-enable-profile-gravatar'               => bp_enable_profile_gravatar(),
			'bp-disable-cover-image-uploads'           => bp_disable_cover_image_uploads(),
			'bp-default-profile-cover-type'            => function_exists( 'bb_get_default_profile_cover_type' ) ? bb_get_default_profile_cover_type() : '',
			'bp-default-custom-profile-cover'          => function_exists( 'bb_get_default_custom_upload_profile_cover' ) ? bb_get_default_custom_upload_profile_cover() : '',
			'bp-member-type-enable-disable'            => bp_member_type_enable_disable(),
			'bp-member-type-display-on-profile'        => ! empty( bp_member_type_enable_disable() ) && bp_member_type_display_on_profile(),
			'bp-member-type-default-on-registration'   => bp_member_type_default_on_registration(),
			'bp-enable-profile-search'                 => ! bp_disable_advanced_profile_search(),
			'bp-profile-layout-format'                 => bp_get_option( 'bp-profile-layout-format', 'list_grid' ),
			'bp-profile-layout-default-format'         => bp_profile_layout_default_format(),
			'bb-web-notification-enabled'              => function_exists( 'bb_web_notification_enabled' ) && bb_web_notification_enabled(),
			'bb-app-notification-enabled'              => function_exists( 'bb_app_notification_enabled' ) && bb_app_notification_enabled(),
			'bb_enabled_legacy_email_preference'       => function_exists( 'bb_enabled_legacy_email_preference' ) && bb_enabled_legacy_email_preference(),
		);

		if ( bp_is_active( 'moderation' ) ) {
			$confirmation = array();
			if ( bp_is_active( 'activity' ) ) {
				$confirmation[] = esc_html__( 'See blocked member\'s posts', 'buddyboss' );
			}

			$confirmation[] = esc_html__( 'Mention this member in posts', 'buddyboss' );

			if ( bp_is_active( 'groups' ) ) {
				$confirmation[] = esc_html__( 'Invite this member to groups', 'buddyboss' );
			}

			if ( bp_is_active( 'messages' ) ) {
				$confirmation[] = esc_html__( 'Message this member', 'buddyboss' );
			}

			if ( bp_is_active( 'friends' ) ) {
				$confirmation[] = esc_html__( 'Add this member as a connection', 'buddyboss' );
			}

			$notes = '';
			if ( bp_is_active( 'friends' ) ) {
				$notes .= esc_html__( 'This action will also remove this member from your connections and send a report to the site admin. ', 'buddyboss' );
			}

			$notes .= esc_html__( 'Please allow a few minutes for this process to complete.', 'buddyboss' );

			// Blocking Settings.
			$results['bpm_blocking_member_blocking']        = bp_is_moderation_member_blocking_enable( false );
			$results['bpm_blocking_member_reporting']       = bb_is_moderation_member_reporting_enable( false );
			$results['bpm_blocking_auto_suspend']           = bp_is_moderation_auto_suspend_enable( false );
			$results['bpm_blocking_auto_suspend_threshold'] = bp_moderation_get_setting( 'bpm_blocking_auto_suspend_threshold', '5' );
			$results['bpm_blocking_email_notification']     = bp_is_moderation_blocking_email_notification_enable( false );
			$results['bpm_blocking_confirmation']           = $confirmation;
			$results['bpm_blocking_note']                   = $notes;

			// Reporting Settings.
			$results['bpm_reporting_content_reporting']   = $this->bp_rest_reporting_content_type();
			$results['bpm_reporting_auto_hide']           = $this->bp_rest_reporting_auto_hide();
			$results['bpm_reporting_auto_hide_threshold'] = $this->bp_rest_reporting_auto_hide_threshold();
			$results['bpm_reporting_email_notification']  = bp_is_moderation_reporting_email_notification_enable( false );

		}

		// Groups settings.
		if ( bp_is_active( 'groups' ) ) {
			// Group Settings.
			$results['bp_restrict_group_creation']           = ! bp_user_can_create_groups();
			$results['bp-disable-group-avatar-uploads']      = bp_disable_group_avatar_uploads();
			$results['bp-default-group-avatar-type']         = function_exists( 'bb_get_default_group_avatar_type' ) ? bb_get_default_group_avatar_type() : '';
			$results['bp-default-custom-group-avatar']       = function_exists( 'bb_get_default_custom_upload_group_avatar' ) ? bb_get_default_custom_upload_group_avatar() : '';
			$results['bp-disable-group-cover-image-uploads'] = bp_disable_group_cover_image_uploads();
			$results['bp-default-group-cover-type']          = function_exists( 'bb_get_default_group_cover_type' ) ? bb_get_default_group_cover_type() : '';
			$results['bp-default-custom-group-cover']        = function_exists( 'bb_get_default_custom_upload_group_cover' ) ? bb_get_default_custom_upload_group_cover() : '';

			// Group Types.
			$results['bp-disable-group-type-creation'] = bp_disable_group_type_creation();
			$results['bp-enable-group-auto-join']      = bp_enable_group_auto_join();

			// Group Hierarchies.
			$results['bp-enable-group-hierarchies']      = bp_enable_group_hierarchies();
			$results['bp-enable-group-restrict-invites'] = bp_enable_group_restrict_invites();

			// Group Directories.
			$results['bp-group-layout-format']         = bp_get_option( 'bp-group-layout-format', 'list_grid' );
			$results['bp-group-layout-default-format'] = bp_group_layout_default_format();
		}

		// Forums settings.
		if ( bp_is_active( 'forums' ) ) {
			// Forum User Settings.
			$results['bbp_edit_lock']       = get_option( '_bbp_edit_lock', '5' );
			$results['bbp_throttle_time']   = get_option( '_bbp_throttle_time', '10' );
			$results['bbp_allow_anonymous'] = bbp_allow_anonymous();

			// Forum Features.
			$results['bbp_allow_revisions']        = bbp_allow_revisions();
			$results['bbp_enable_favorites']       = bbp_is_favorites_active();
			$results['bbp_enable_subscriptions']   = bbp_is_subscriptions_active();
			$results['bbp_allow_topic_tags']       = bbp_allow_topic_tags();
			$results['bbp_allow_search']           = bbp_allow_search();
			$results['bbp_use_wp_editor']          = bbp_use_wp_editor();
			$results['bbp_use_autoembed']          = bbp_use_autoembed();
			$results['bbp_allow_threaded_replies'] = bbp_allow_threaded_replies();
			$results['bbp_thread_replies_depth']   = bbp_thread_replies_depth();

			// Discussions and Replies Per Page.
			$results['bbp_forums_per_page']  = bbp_get_forums_per_page();
			$results['bbp_topics_per_page']  = bbp_get_topics_per_page();
			$results['bbp_replies_per_page'] = bbp_get_replies_per_page();

			// Discussions and Replies Per RSS Page.
			$results['bbp_topics_per_rss_page']  = bbp_get_topics_per_rss_page();
			$results['bbp_replies_per_rss_page'] = bbp_get_replies_per_rss_page();

			// Forums Directory.
			$results['bbp_include_root'] = bbp_include_root_slug();
			$results['bbp_show_on_root'] = bbp_show_on_root();

			// Group Forums.
			$results['bbp_enable_group_forums']  = bbp_is_group_forums_active();
			$results['bbp_group_forums_root_id'] = bbp_get_group_forums_root_id();

			// Check Permalinks for platform forms slugs.
			$permalinks_settings    = $this->bp_rest_get_forum_slugs_settings();
			$results['forum_slugs'] = apply_filters( 'bp_rest_forum_slugs_settings', $permalinks_settings );
		}

		// Activity settings.
		if ( bp_is_active( 'activity' ) ) {
			// Activity Settings.
			$results['bp_enable_activity_edit']         = bp_is_activity_edit_enabled();
			$results['bp_activity_edit_time']           = bp_get_activity_edit_time( - 1 );
			$results['bb_enable_activity_comment_edit'] = bb_is_activity_comment_edit_enabled();
			$results['bb_activity_comment_edit_time']   = bb_get_activity_comment_edit_time( - 1 );
			$results['bp_enable_heartbeat_refresh']     = bp_is_activity_heartbeat_active();
			$results['bp_enable_activity_autoload']     = bp_is_activity_autoload_active();
			$results['bp_enable_activity_tabs']         = bp_is_activity_tabs_active();
			$results['bp_enable_activity_follow']       = bp_is_activity_follow_active();
			$results['bp_enable_activity_like']         = bp_is_activity_like_active();
			$results['bp_enable_activity_link_preview'] = bp_is_activity_link_preview_active();
			$results['bp_enable_relevant_feed']         = ( function_exists( 'bp_is_relevant_feed_enabled' ) ? bp_is_relevant_feed_enabled() : false );

			// Posts in Activity Feeds.
			$results['bp-feed-platform-new_avatar']            = bp_platform_is_feed_enable( 'bp-feed-platform-new_avatar' );
			$results['bp-feed-platform-updated_profile']       = bp_platform_is_feed_enable( 'bp-feed-platform-updated_profile' );
			$results['bp-feed-platform-new_member']            = bp_platform_is_feed_enable( 'bp-feed-platform-new_member' );
			$results['bp-feed-platform-friendship_created']    = bp_platform_is_feed_enable( 'bp-feed-platform-friendship_created' );
			$results['bp-feed-platform-created_group']         = bp_platform_is_feed_enable( 'bp-feed-platform-created_group' );
			$results['bp-feed-platform-joined_group']          = bp_platform_is_feed_enable( 'bp-feed-platform-joined_group' );
			$results['bp-feed-platform-group_details_updated'] = bp_platform_is_feed_enable( 'bp-feed-platform-group_details_updated' );
			$results['bp-feed-platform-bbp_topic_create']      = bp_platform_is_feed_enable( 'bp-feed-platform-bbp_topic_create' );
			$results['bp-feed-platform-bbp_reply_create']      = bp_platform_is_feed_enable( 'bp-feed-platform-bbp_reply_create' );

			if ( function_exists( 'bb_feed_post_types' ) ) {
				foreach ( bb_feed_post_types() as $single_post ) {
					// check custom post type feed is enabled from the BuddyBoss > Settings > Activity > Custom Post Types metabox settings.
					$results[ 'bp-feed-custom-post-type-' . $single_post ]               = (bool) bp_is_post_type_feed_enable( $single_post );
					$results[ 'bp-feed-custom-post-type-' . $single_post . '-comments' ] = (bool) bb_is_post_type_feed_comment_enable( $single_post );
				}
			} else {
				$results['bp-disable-blogforum-comments'] = bp_disable_blogforum_comments();
				$custom_post_types                        = bp_get_option( 'bp_core_admin_get_active_custom_post_type_feed', array() );
				if ( ! empty( $custom_post_types ) ) {
					foreach ( $custom_post_types as $single_post ) {
						// check custom post type feed is enabled from the BuddyBoss > Settings > Activity > Custom Post Types metabox settings.
						$results[ 'bp-feed-custom-post-type-' . $single_post ] = (bool) bp_is_post_type_feed_enable( $single_post );
					}
				}
			}
		}

		// Media settings.
		if ( bp_is_active( 'media' ) ) {
			// Photo Uploading.
			$results['bp_media_profile_media_support']  = bp_is_profile_media_support_enabled();
			$results['bp_media_profile_albums_support'] = bp_is_profile_albums_support_enabled();
			$results['bp_media_group_media_support']    = bp_is_group_media_support_enabled();
			$results['bp_media_group_albums_support']   = bp_is_group_albums_support_enabled();
			$results['bp_media_messages_media_support'] = bp_is_messages_media_support_enabled();
			$results['bp_media_forums_media_support']   = bp_is_forums_media_support_enabled();
			$results['bp_media_allowed_size']           = bp_media_allowed_upload_media_size();
			$results['bp_media_allowed_per_batch']      = bp_media_allowed_upload_media_per_batch();

			// Emoji.
			$results['bp_media_profiles_emoji_support'] = bp_is_profiles_emoji_support_enabled();
			$results['bp_media_groups_emoji_support']   = bp_is_groups_emoji_support_enabled();
			$results['bp_media_messages_emoji_support'] = bp_is_messages_emoji_support_enabled();
			$results['bp_media_forums_emoji_support']   = bp_is_forums_emoji_support_enabled();

			// Animated GIFs.
			if ( bp_loggedin_user_id() ) {
				$results['bp_media_gif_api_key'] = bp_media_get_gif_api_key();
			}
			$results['bp_media_profiles_gif_support'] = bp_is_profiles_gif_support_enabled();
			$results['bp_media_groups_gif_support']   = bp_is_groups_gif_support_enabled();
			$results['bp_media_messages_gif_support'] = bp_is_messages_gif_support_enabled();
			$results['bp_media_forums_gif_support']   = bp_is_forums_gif_support_enabled();

			// Documents settings.
			if ( bp_is_active( 'document' ) ) {
				$results['bp_media_profile_document_support']     = bp_is_profile_document_support_enabled();
				$results['bp_media_group_document_support']       = bp_is_group_document_support_enabled();
				$results['bp_media_messages_document_support']    = bp_is_messages_document_support_enabled();
				$results['bp_is_forums_document_support_enabled'] = bp_is_forums_document_support_enabled();
				$results['bp_document_allowed_size']              = bp_media_allowed_upload_document_size();
				$results['bp_document_allowed_per_batch']         = bp_media_allowed_upload_document_per_batch();
			}

			// Video settings.
			if ( bp_is_active( 'video' ) ) {
				$results['bp_video_profile_video_support']  = bp_is_profile_video_support_enabled();
				$results['bp_video_group_video_support']    = bp_is_group_video_support_enabled();
				$results['bp_video_messages_video_support'] = bp_is_messages_video_support_enabled();
				$results['bp_video_forums_video_support']   = bp_is_forums_video_support_enabled();
				$results['bp_video_allowed_size']           = bp_video_allowed_upload_video_size();
				$results['bp_video_allowed_per_batch']      = bp_video_allowed_upload_video_per_batch();
			}
		}

		// Connection Settings.
		if ( bp_is_active( 'friends' ) ) {
			$results['bp-force-friendship-to-message'] = bp_force_friendship_to_message();
		}

		// Email Invites Settings.
		if ( bp_is_active( 'invites' ) ) {
			$results['bp-disable-invite-member-email-subject'] = bp_disable_invite_member_email_subject();
			$results['bp-disable-invite-member-email-content'] = bp_disable_invite_member_email_content();
			$results['bp-disable-invite-member-type']          = bp_disable_invite_member_type();

			$member_types = bp_get_active_member_types();
			if ( isset( $member_types ) && ! empty( $member_types ) ) {
				foreach ( $member_types as $member_type_id ) {
					$option_name = bp_get_member_type_key( $member_type_id );
					$results[ 'bp-enable-send-invite-member-type-' . $option_name ] = bp_enable_send_invite_member_type( 'bp-enable-send-invite-member-type-' . $option_name, false );
				}
			}

			$results['bp-email-subject'] = ( true === bp_disable_invite_member_email_subject() ? stripslashes( wp_strip_all_tags( bp_get_member_invitation_subject() ) ) : '' );
			$results['bp-email-content'] = ( true === bp_disable_invite_member_email_content() ? bp_get_member_invites_wildcard_replace( bp_get_member_invitation_message() ) : '' );
		}

		// Network Search.
		if ( bp_is_active( 'search' ) ) {
			$results['bp_search_autocomplete']      = bp_is_search_autocomplete_enable();
			$results['bp_search_number_of_results'] = get_option( 'bp_search_number_of_results', '5' );
		}

		$bp_pages = bp_core_get_directory_page_ids();
		$terms    = isset( $bp_pages['terms'] ) ? $bp_pages['terms'] : '';
		$privacy  = isset( $bp_pages['privacy'] ) ? $bp_pages['privacy'] : '';

		// Additional.
		$results['enable_friendship_connections'] = bp_is_active( 'friends' );
		$results['enable_messages']               = bp_is_active( 'messages' );
		$results['bp_page_privacy']               = $privacy;
		$results['bp_page_terms']                 = $terms;
		$results['wp_page_privacy']               = (int) get_option( 'wp_page_for_privacy_policy' );

		$results['bp-pages'] = array();

		$component_pages = array(
			'members'  => 'xprofile',
			'video'    => 'video',
			'media'    => 'media',
			'document' => 'document',
			'groups'   => 'groups',
			'activity' => 'activity',
			'register' => 'xprofile',
			'terms'    => 'xprofile',
			'privacy'  => 'xprofile',
			'activate' => 'xprofile',
		);

		foreach ( $component_pages as $key => $component ) {
			if ( bp_is_active( $component ) ) {
				$id = (int) ( isset( $bp_pages[ $key ] ) ? $bp_pages[ $key ] : 0 );

				$results['bp-pages'][ $key ] = array(
					'id'    => $id,
					'slug'  => ( 0 !== $id ? get_post_field( 'post_name', $id ) : '' ),
					'title' => ( 0 !== $id ? get_the_title( $id ) : '' ),
				);
			}
		}

		if ( bp_is_active( 'forums' ) ) {
			$id = (int) bp_get_option( '_bbp_root_slug_custom_slug' );

			$results['bp-pages']['forums'] = array(
				'id'    => $id,
				'slug'  => ( 0 !== $id ? get_post_field( 'post_name', $id ) : '' ),
				'title' => ( 0 !== $id ? get_the_title( $id ) : '' ),
			);
		}

		$results['bp-active-components'] = array_keys( apply_filters( 'bp_active_components', bp_get_option( 'bp-active-components' ) ) );

		$results['bb-presence-interval'] = (
			function_exists( 'bb_presence_interval' ) ?
			( bb_presence_interval() <= 60 ? bb_presence_interval() : bb_presence_default_interval() )
			: 0
		);

		if ( function_exists( 'bb_enabled_legacy_email_preference' ) && ! bb_enabled_legacy_email_preference() ) {
			$results['bbp_enable_forum_subscriptions'] = function_exists( 'bb_is_enabled_subscription' ) && bb_is_enabled_subscription( 'forum' );
			$results['bbp_enable_topic_subscriptions'] = function_exists( 'bb_is_enabled_subscription' ) && bb_is_enabled_subscription( 'topic' );
			$results['bb_enable_group_subscriptions']  = function_exists( 'bb_is_enabled_subscription' ) && bb_is_enabled_subscription( 'group' );
		}

		$results['bb-presence-idle-inactive-span'] = function_exists( 'bb_idle_inactive_span' ) ? bb_idle_inactive_span() : 180;

		$native_presence               = (bool) bp_get_option( 'bb_use_core_native_presence', false );
		$results['bb-native-presence'] = true === $native_presence ? buddypress()->plugin_url . 'bp-core/bb-core-native-presence.php' : '';

		// Allowed messaging without connection for profile types.
		if (
			bp_member_type_enable_disable() &&
			bp_is_active( 'messages' ) &&
			bp_is_active( 'friends' ) &&
			true === (bool) bp_get_option( 'bp-force-friendship-to-message', false )
		) {
			$results['bp_member_types_allowed_messaging_without_connection'] = get_option( 'bp_member_types_allowed_messaging_without_connection' );
		}

		return $results;
	}

	/**
	 * Get BuddyPress settings.
	 *
	 * @return array
	 */
	public function get_buddypress_settings() {
		$results = array(
			// General settings.
			'bp-disable-account-deletion' => bp_disable_account_deletion(),
			'bp_theme_package_id'         => bp_get_theme_package_id(),
		);

		// Xprofile settings.
		if ( bp_is_active( 'xprofile' ) ) {
			$results['bp-disable-avatar-uploads']      = bp_disable_avatar_uploads();
			$results['bp-disable-cover-image-uploads'] = bp_disable_cover_image_uploads();
			$results['bp-disable-profile-sync']        = bp_disable_profile_sync();
		}

		// Activity settings.
		if ( bp_is_active( 'activity' ) ) {
			$results['bp-disable-blogforum-comments'] = bp_disable_blogforum_comments();
			$results['bp_enable_heartbeat_refresh']   = bp_is_activity_heartbeat_active();
		}

		// Groups settings.
		if ( bp_is_active( 'groups' ) ) {
			// Group Settings.
			$results['bp_restrict_group_creation']           = bp_restrict_group_creation();
			$results['bp-disable-group-avatar-uploads']      = bp_disable_group_avatar_uploads();
			$results['bp-disable-group-cover-image-uploads'] = bp_disable_group_cover_image_uploads();
		}

		// Additional.
		$results['enable_friendship_connections'] = bp_is_active( 'friends' );
		$results['enable_messages']               = bp_is_active( 'messages' );

		return $results;
	}

	/**
	 * Get bbPress settings.
	 *
	 * @return array
	 */
	public function get_bbpress_settings() {
		$results = array(
			// Forum User Settings.
			'bbp_allow_global_access'    => bbp_allow_global_access(),
			'bbp_default_role'           => bbp_get_default_role(),
			'bbp_allow_content_throttle' => bbp_allow_content_throttle(),
			'bbp_throttle_time'          => get_option( '_bbp_throttle_time', '10' ),
			'bbp_allow_content_edit'     => bbp_allow_content_edit(),
			'bbp_edit_lock'              => bbp_get_edit_lock(),
			'bbp_allow_anonymous'        => bbp_allow_anonymous(),

			// Forum Features.
			'bbp_allow_revisions'        => bbp_allow_revisions(),
			'bbp_enable_favorites'       => bbp_is_favorites_active(),
			'bbp_enable_subscriptions'   => bbp_is_subscriptions_active(),
			'bbp_enable_engagements'     => bbp_is_engagements_active(),
			'bbp_allow_topic_tags'       => bbp_allow_topic_tags(),
			'bbp_allow_forum_mods'       => bbp_allow_forum_mods(),
			'bbp_allow_super_mods'       => bbp_allow_super_mods(),
			'bbp_allow_search'           => bbp_allow_search(),
			'bbp_use_wp_editor'          => bbp_use_wp_editor(),
			'bbp_allow_threaded_replies' => bbp_allow_threaded_replies(),
			'bbp_thread_replies_depth'   => bbp_thread_replies_depth(),

			// Forum Theme Packages.
			'bbp_theme_package_id'       => bbp_get_theme_package_id(),

			// Topics and Replies Per Page.
			'bbp_topics_per_page'        => get_option( '_bbp_topics_per_page', '15' ),
			'bbp_replies_per_page'       => get_option( '_bbp_replies_per_page', '15' ),

			// Topics and Replies Per RSS Page.
			'bbp_topics_per_rss_page'    => get_option( '_bbp_topics_per_rss_page', '25' ),
			'bbp_replies_per_rss_page'   => get_option( '_bbp_replies_per_rss_page', '25' ),

			// Forum Root Slug.
			'bbp_include_root'           => bbp_include_root_slug(),
			'bbp_show_on_root'           => bbp_show_on_root(),

			// Forum Integration for BuddyPress.
			'bbp_enable_group_forums'    => bbp_is_group_forums_active(),
			'bbp_group_forums_root_id'   => bbp_get_group_forums_root_id(),
		);

		// Check Permalinks for platform forms slugs.
		$permalinks_settings    = $this->bp_rest_get_forum_slugs_settings();
		$results['forum_slugs'] = apply_filters( 'bp_rest_forum_slugs_settings', $permalinks_settings );

		return $results;
	}

	/**
	 * Get Permalinks settings.
	 *
	 * @return array
	 */
	public function bp_rest_get_forum_slugs_settings() {
		$results = array(
			// Single forum slugs.
			'forum'         => get_option( '_bbp_forum_slug', 'forum' ),
			'topic'         => get_option( '_bbp_topic_slug', 'discussions' ),
			'topic_tag'     => get_option( '_bbp_topic_tag_slug', 'topic-tag' ),
			'view'          => get_option( '_bbp_view_slug', 'view' ),
			'reply'         => get_option( '_bbp_reply_slug', 'reply' ),
			'search'        => get_option( '_bbp_search_slug', 'search' ),

			// Forum Profile Slugs.
			'replies'       => bbp_get_reply_archive_slug(),
			'favorites'     => bbp_get_user_favorites_slug(),
			'subscriptions' => bbp_get_user_subscriptions_slug(),
		);

		return $results;
	}

	/**
	 * Get the list of Reporting content types.
	 *
	 * @return array|mixed|void
	 */
	protected function bp_rest_reporting_content_type() {
		if ( ! function_exists( 'bp_moderation_content_types' ) ) {
			return array();
		}

		$content_types = bp_moderation_content_types();

		if ( class_exists( 'BP_Moderation_Members' ) && array_key_exists( BP_Moderation_Members::$moderation_type, $content_types ) ) {
			unset( $content_types[ BP_Moderation_Members::$moderation_type ] );
		}

		foreach ( $content_types as $slug => $type ) {
			$content_types[ $slug ] = bp_is_moderation_content_reporting_enable( false, $slug );
		}

		return $content_types;
	}

	/**
	 * Get the list of Reporting Auto Hide.
	 *
	 * @return array|mixed|void
	 */
	protected function bp_rest_reporting_auto_hide() {
		if ( ! function_exists( 'bp_moderation_content_types' ) ) {
			return array();
		}

		$content_types = bp_moderation_content_types();

		if ( class_exists( 'BP_Moderation_Members' ) && array_key_exists( BP_Moderation_Members::$moderation_type, $content_types ) ) {
			unset( $content_types[ BP_Moderation_Members::$moderation_type ] );
		}

		foreach ( $content_types as $slug => $type ) {
			$content_types[ $slug ] = bp_is_moderation_auto_hide_enable( false, $slug );
		}

		return $content_types;
	}

	/**
	 * Get the list of Reporting Auto Hide threshold.
	 *
	 * @return array|mixed|void
	 */
	protected function bp_rest_reporting_auto_hide_threshold() {
		if ( ! function_exists( 'bp_moderation_content_types' ) ) {
			return array();
		}

		$content_types = bp_moderation_content_types();

		if ( class_exists( 'BP_Moderation_Members' ) && array_key_exists( BP_Moderation_Members::$moderation_type, $content_types ) ) {
			unset( $content_types[ BP_Moderation_Members::$moderation_type ] );
		}

		foreach ( $content_types as $slug => $type ) {
			$content_types[ $slug ] = bp_moderation_reporting_auto_hide_threshold( '5', $slug );
		}

		return $content_types;
	}
}

<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Action handlers — executes the actions defined on an automation.
 */
class BB_CRM_Auto_Actions {

	/**
	 * Execute an array of action configs for a user.
	 *
	 * @param array $actions     Array of action config arrays.
	 * @param int   $user_id     Target user.
	 * @param array $trigger_data Trigger context.
	 * @return array Results keyed by action index.
	 */
	public static function execute( $actions, $user_id, $trigger_data = array() ) {
		$results = array();

		foreach ( $actions as $index => $action ) {
			$type   = $action['type'] ?? '';
			$config = $action['config'] ?? array();

			$result = self::run_single( $type, $config, $user_id, $trigger_data );
			$results[ $index ] = array(
				'type'    => $type,
				'success' => $result['success'],
				'message' => $result['message'] ?? '',
			);
		}

		return $results;
	}

	/**
	 * Run a single action by type. Public so the engine can call it directly.
	 */
	public static function run_single( $type, $config, $user_id, $trigger_data ) {
		switch ( $type ) {
			case 'assign_tag':
				return self::action_assign_tag( $config, $user_id );

			case 'remove_tag':
				return self::action_remove_tag( $config, $user_id );

			case 'add_to_list':
				return self::action_add_to_list( $config, $user_id );

			case 'remove_from_list':
				return self::action_remove_from_list( $config, $user_id );

			case 'send_email':
				return self::action_send_email( $config, $user_id, $trigger_data );

			case 'call_webhook':
				return self::action_call_webhook( $config, $user_id, $trigger_data );

			case 'log_activity':
				return self::action_log_activity( $config, $user_id, $trigger_data );

			case 'subscribe_email':
				return self::action_subscribe_email( $user_id );

			case 'unsubscribe_email':
				return self::action_unsubscribe_email( $user_id );

			case 'send_campaign_email':
				return self::action_send_campaign_email( $config, $user_id, $trigger_data );

			case 'cancel_sequence':
				return self::action_cancel_sequence( $config, $user_id );

			case 'wait':
				// Handled upstream by the engine; if somehow called directly, just succeed.
				return array( 'success' => true, 'message' => 'Wait step (no-op when called directly)' );

			case 'loop_repeat':
				// Handled by engine; no-op if called directly.
				return array( 'success' => true, 'message' => 'Loop repeat (handled by engine)' );

			case 'check_condition':
				// Handled by engine; no-op if called directly.
				return array( 'success' => true, 'message' => 'Condition check (handled by engine)' );

			default:
				// Allow third-party action types.
				$result = apply_filters( 'bb_crm_auto_action_' . $type, null, $config, $user_id, $trigger_data );
				if ( $result !== null ) {
					return $result;
				}
				return array( 'success' => false, 'message' => "Unknown action type: {$type}" );
		}
	}

	// ── Action Handlers ─────────────────────────────────────────────────────

	private static function action_assign_tag( $config, $user_id ) {
		$tag_id = absint( $config['tag_id'] ?? 0 );
		if ( ! $tag_id ) return array( 'success' => false, 'message' => 'No tag_id specified' );

		if ( function_exists( 'bb_crm_add_user_tag' ) ) {
			$result = bb_crm_add_user_tag( $user_id, $tag_id, array( 'source' => 'automation' ) );
		} else {
			// Fallback: direct DB insert.
			global $wpdb;
			$exists = $wpdb->get_var( $wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}bb_user_tags WHERE user_id = %d AND tag_id = %d",
				$user_id, $tag_id
			) );
			if ( ! $exists ) {
				$wpdb->insert( $wpdb->prefix . 'bb_user_tags', array( 'user_id' => $user_id, 'tag_id' => $tag_id, 'source' => 'automation' ) );
			}
			$result = true;
		}

		return array( 'success' => (bool) $result, 'message' => "Assigned tag {$tag_id} to user {$user_id}" );
	}

	private static function action_remove_tag( $config, $user_id ) {
		$tag_id = absint( $config['tag_id'] ?? 0 );
		if ( ! $tag_id ) return array( 'success' => false, 'message' => 'No tag_id specified' );

		if ( function_exists( 'bb_crm_remove_user_tag' ) ) {
			$result = bb_crm_remove_user_tag( $user_id, $tag_id );
		} else {
			global $wpdb;
			$result = $wpdb->delete( $wpdb->prefix . 'bb_user_tags', array( 'user_id' => $user_id, 'tag_id' => $tag_id ) );
		}

		return array( 'success' => (bool) $result, 'message' => "Removed tag {$tag_id} from user {$user_id}" );
	}

	private static function action_add_to_list( $config, $user_id ) {
		$list_id = absint( $config['list_id'] ?? 0 );
		if ( ! $list_id ) return array( 'success' => false, 'message' => 'No list_id specified' );

		global $wpdb;
		$exists = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM {$wpdb->prefix}bb_user_list_assignments WHERE list_id = %d AND user_id = %d",
			$list_id, $user_id
		) );

		if ( ! $exists ) {
			$wpdb->insert( $wpdb->prefix . 'bb_user_list_assignments', array( 'list_id' => $list_id, 'user_id' => $user_id ) );
		}

		return array( 'success' => true, 'message' => "Added user {$user_id} to list {$list_id}" );
	}

	private static function action_remove_from_list( $config, $user_id ) {
		$list_id = absint( $config['list_id'] ?? 0 );
		if ( ! $list_id ) return array( 'success' => false, 'message' => 'No list_id specified' );

		global $wpdb;
		$wpdb->delete( $wpdb->prefix . 'bb_user_list_assignments', array( 'list_id' => $list_id, 'user_id' => $user_id ) );

		return array( 'success' => true, 'message' => "Removed user {$user_id} from list {$list_id}" );
	}

	private static function action_send_email( $config, $user_id, $trigger_data ) {
		$user = get_userdata( $user_id );
		if ( ! $user ) return array( 'success' => false, 'message' => 'User not found' );

		$subject = self::interpolate( $config['subject'] ?? '', $user, $trigger_data );
		$body    = self::interpolate( $config['body'] ?? '', $user, $trigger_data );

		if ( empty( $subject ) || empty( $body ) ) {
			return array( 'success' => false, 'message' => 'Missing email subject or body' );
		}

		$result = wp_mail( $user->user_email, $subject, $body );
		return array( 'success' => $result, 'message' => $result ? "Email sent to {$user->user_email}" : 'Email failed' );
	}

	private static function action_call_webhook( $config, $user_id, $trigger_data ) {
		$url = esc_url_raw( $config['url'] ?? '' );
		if ( ! $url ) return array( 'success' => false, 'message' => 'No webhook URL specified' );

		$user    = get_userdata( $user_id );
		$payload = array(
			'user_id'      => $user_id,
			'user_email'   => $user ? $user->user_email : '',
			'trigger_data' => $trigger_data,
		);

		$response = wp_remote_post( $url, array(
			'headers'     => array( 'Content-Type' => 'application/json' ),
			'body'        => wp_json_encode( $payload ),
			'timeout'     => 15,
			'redirection' => 3,
		) );

		if ( is_wp_error( $response ) ) {
			return array( 'success' => false, 'message' => $response->get_error_message() );
		}

		$code = wp_remote_retrieve_response_code( $response );
		return array( 'success' => $code >= 200 && $code < 300, 'message' => "Webhook responded with HTTP {$code}" );
	}

	private static function action_subscribe_email( $user_id ) {
		$user = get_userdata( $user_id );
		if ( ! $user ) return array( 'success' => false, 'message' => 'User not found' );
		if ( ! class_exists( 'BB_Camp_Unsubscribe' ) ) {
			return array( 'success' => false, 'message' => 'Campaigns plugin not active' );
		}
		BB_Camp_Unsubscribe::resubscribe( $user->user_email );
		return array( 'success' => true, 'message' => "Subscribed {$user->user_email} to email campaigns" );
	}

	private static function action_unsubscribe_email( $user_id ) {
		$user = get_userdata( $user_id );
		if ( ! $user ) return array( 'success' => false, 'message' => 'User not found' );
		if ( ! class_exists( 'BB_Camp_Unsubscribe' ) ) {
			return array( 'success' => false, 'message' => 'Campaigns plugin not active' );
		}
		BB_Camp_Unsubscribe::unsubscribe( $user->user_email );
		return array( 'success' => true, 'message' => "Unsubscribed {$user->user_email} from email campaigns" );
	}

	/**
	 * Cancel all pending queue items for a user.
	 * Optionally scoped to a specific automation (0 = all automations).
	 */
	private static function action_cancel_sequence( $config, $user_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'bb_crm_automation_queue';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) !== $table ) {
			return array( 'success' => false, 'message' => 'Queue table not found' );
		}

		$automation_id = absint( $config['automation_id'] ?? 0 );
		$where         = array( 'user_id' => $user_id, 'status' => 'pending' );

		if ( $automation_id ) {
			$where['automation_id'] = $automation_id;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted = $wpdb->delete( $table, $where );
		$scope   = $automation_id ? "automation #{$automation_id}" : 'all automations';

		return array(
			'success' => true,
			'message' => "Cancelled {$deleted} pending sequence(s) for user {$user_id} in {$scope}",
		);
	}

	/**
	 * Send a saved campaign email template to a single user.
	 * Picks subject/from/body from the campaign record + body_post_id post content.
	 */
	private static function action_send_campaign_email( $config, $user_id, $trigger_data ) {
		if ( ! defined( 'BB_CRM_CAMP_VERSION' ) ) {
			return array( 'success' => false, 'message' => 'Campaigns plugin not active' );
		}

		$campaign_id = absint( $config['campaign_id'] ?? 0 );
		if ( ! $campaign_id ) {
			return array( 'success' => false, 'message' => 'No campaign selected' );
		}

		global $wpdb;
		$camp_table = $wpdb->prefix . 'bb_crm_campaigns';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
		$campaign = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$camp_table}` WHERE id = %d", $campaign_id ) );
		if ( ! $campaign ) {
			return array( 'success' => false, 'message' => "Campaign {$campaign_id} not found" );
		}

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return array( 'success' => false, 'message' => 'User not found' );
		}

		$subject = self::interpolate( $campaign->subject ?? '', $user, $trigger_data );
		if ( empty( $subject ) ) {
			return array( 'success' => false, 'message' => 'Campaign has no subject' );
		}

		// Render body from block-editor post.
		$body = '';
		if ( ! empty( $campaign->body_post_id ) ) {
			$post = get_post( absint( $campaign->body_post_id ) );
			if ( $post ) {
				$body = apply_filters( 'the_content', $post->post_content );
			}
		}
		if ( empty( $body ) ) {
			return array( 'success' => false, 'message' => 'Campaign has no email body' );
		}
		$body = self::interpolate( $body, $user, $trigger_data );

		// Embed 1x1 open-tracking pixel.
		global $wpdb;
		$token = bin2hex( random_bytes( 32 ) );
		$wpdb->insert( $wpdb->prefix . 'bb_crm_email_opens', array(
			'user_id'     => $user_id,
			'campaign_id' => $campaign_id,
			'token'       => $token,
			'sent_at'     => current_time( 'mysql' ),
		) );
		$pixel_url = add_query_arg( 'bb_camp_open', $token, home_url( '/' ) );
		$body     .= '<img src="' . esc_url( $pixel_url ) . '" width="1" height="1" alt="" style="display:none">';

		$from_name  = ! empty( $campaign->from_name )  ? $campaign->from_name  : get_bloginfo( 'name' );
		$from_email = ! empty( $campaign->from_email ) ? $campaign->from_email : get_bloginfo( 'admin_email' );
		$headers    = array(
			'Content-Type: text/html; charset=UTF-8',
			"From: {$from_name} <{$from_email}>",
		);

		$result = wp_mail( $user->user_email, $subject, $body, $headers );
		return array(
			'success' => $result,
			'message' => $result
				? "Campaign email '{$campaign->name}' sent to {$user->user_email}"
				: "Failed to send campaign email to {$user->user_email}",
		);
	}

	private static function action_log_activity( $config, $user_id, $trigger_data ) {
		$note = sanitize_text_field( $config['note'] ?? 'Automation triggered' );

		if ( function_exists( 'bb_crm_log_activity' ) ) {
			bb_crm_log_activity( $user_id, 'automation', array( 'note' => $note ) );
		}

		return array( 'success' => true, 'message' => "Activity logged for user {$user_id}" );
	}

	/**
	 * Replace merge tags in email content.
	 * Supports: {{user_name}}, {{user_email}}, {{first_name}}, {{site_name}}, {{site_url}}
	 */
	private static function interpolate( $text, $user, $trigger_data ) {
		$replacements = array(
			'{{user_name}}'  => esc_html( $user->display_name ?? '' ),
			'{{user_email}}' => esc_html( $user->user_email ?? '' ),
			'{{first_name}}' => esc_html( $user->first_name ?? '' ),
			'{{last_name}}'  => esc_html( $user->last_name ?? '' ),
			'{{site_name}}'  => get_bloginfo( 'name' ),
			'{{site_url}}'   => home_url(),
		);
		return str_replace( array_keys( $replacements ), array_values( $replacements ), $text );
	}

	/**
	 * Return all available action types for the admin UI.
	 */
	public static function get_available_actions() {
		$actions = array(
			'wait'              => __( '⏱ Wait / Delay', 'buddyboss-automations' ),
			'loop_repeat'       => __( '🔁 Wait & Repeat from Start', 'buddyboss-automations' ),
			'cancel_sequence'   => __( '🛑 Cancel Pending Sequence', 'buddyboss-automations' ),
			'assign_tag'        => __( 'Assign Tag', 'buddyboss-automations' ),
			'remove_tag'        => __( 'Remove Tag', 'buddyboss-automations' ),
			'add_to_list'       => __( 'Add to List', 'buddyboss-automations' ),
			'remove_from_list'  => __( 'Remove from List', 'buddyboss-automations' ),
			'send_email'        => __( 'Send Custom Email', 'buddyboss-automations' ),
			'call_webhook'      => __( 'Call Webhook', 'buddyboss-automations' ),
			'log_activity'      => __( 'Log Activity Note', 'buddyboss-automations' ),
		);

		if ( defined( 'BB_CRM_CAMP_VERSION' ) ) {
			$actions['send_campaign_email'] = __( 'Send Campaign Email', 'buddyboss-automations' );
		}

		if ( class_exists( 'BB_Camp_Unsubscribe' ) ) {
			$actions['subscribe_email']   = __( 'Subscribe to Email Campaigns', 'buddyboss-automations' );
			$actions['unsubscribe_email'] = __( 'Unsubscribe from Email Campaigns', 'buddyboss-automations' );
		}

		return apply_filters( 'bb_crm_auto_available_actions', $actions );
	}
}

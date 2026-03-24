<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Condition evaluator.
 * Supports AND/OR logic across condition groups.
 */
class BB_CRM_Auto_Conditions {

	/**
	 * Evaluate conditions against a user.
	 *
	 * Conditions structure:
	 * [
	 *   'operator' => 'AND', // AND|OR across groups
	 *   'groups'   => [
	 *     [ 'type' => 'has_tag', 'config' => ['tag_id' => 5] ],
	 *     ...
	 *   ]
	 * ]
	 *
	 * @param array $conditions
	 * @param int   $user_id
	 * @param array $trigger_data
	 * @return bool
	 */
	public static function evaluate( $conditions, $user_id, $trigger_data = array() ) {
		if ( empty( $conditions ) || empty( $conditions['groups'] ) ) {
			return true; // No conditions = always pass.
		}

		$operator = strtoupper( $conditions['operator'] ?? 'AND' );
		$groups   = $conditions['groups'];
		$results  = array();

		foreach ( $groups as $condition ) {
			$results[] = self::evaluate_single( $condition, $user_id, $trigger_data );
		}

		if ( $operator === 'OR' ) {
			return in_array( true, $results, true );
		}

		// Default: AND — all must pass.
		return ! in_array( false, $results, true );
	}

	/**
	 * Evaluate a single condition.
	 */
	private static function evaluate_single( $condition, $user_id, $trigger_data ) {
		$type   = $condition['type'] ?? '';
		$config = $condition['config'] ?? array();
		$negate = ! empty( $condition['negate'] ); // "does NOT" option.

		$result = self::check( $type, $config, $user_id, $trigger_data );

		return $negate ? ! $result : $result;
	}

	private static function check( $type, $config, $user_id, $trigger_data ) {
		switch ( $type ) {
			case 'has_tag':
				return self::cond_has_tag( $config, $user_id );

			case 'not_has_tag':
				return ! self::cond_has_tag( $config, $user_id );

			case 'in_list':
				return self::cond_in_list( $config, $user_id );

			case 'not_in_list':
				return ! self::cond_in_list( $config, $user_id );

			case 'user_role':
				return self::cond_user_role( $config, $user_id );

			case 'profile_field':
				return self::cond_profile_field( $config, $user_id );

			case 'in_group':
				return self::cond_in_group( $config, $user_id );

			case 'registration_days':
				return self::cond_registration_days( $config, $user_id );

			case 'tag_count':
				return self::cond_tag_count( $config, $user_id );

			case 'has_opened_email':
				return self::cond_has_opened_email( $config, $user_id );

			default:
				// Allow third-party conditions.
				$result = apply_filters( 'bb_crm_auto_condition_' . $type, null, $config, $user_id, $trigger_data );
				return $result !== null ? (bool) $result : true;
		}
	}

	// ── Individual Condition Checks ──────────────────────────────────────────

	private static function cond_has_tag( $config, $user_id ) {
		$tag_id = absint( $config['tag_id'] ?? 0 );
		if ( ! $tag_id ) return false;

		if ( function_exists( 'bb_crm_user_has_tag' ) ) {
			return bb_crm_user_has_tag( $user_id, $tag_id );
		}

		global $wpdb;
		return (bool) $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM {$wpdb->prefix}bb_user_tags WHERE user_id = %d AND tag_id = %d",
			$user_id, $tag_id
		) );
	}

	private static function cond_in_list( $config, $user_id ) {
		$list_id = absint( $config['list_id'] ?? 0 );
		if ( ! $list_id ) return false;

		global $wpdb;
		return (bool) $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM {$wpdb->prefix}bb_user_list_assignments WHERE list_id = %d AND user_id = %d",
			$list_id, $user_id
		) );
	}

	private static function cond_user_role( $config, $user_id ) {
		$role = sanitize_text_field( $config['role'] ?? '' );
		if ( ! $role ) return false;

		$user = get_userdata( $user_id );
		return $user && in_array( $role, (array) $user->roles, true );
	}

	private static function cond_profile_field( $config, $user_id ) {
		$field = sanitize_text_field( $config['field'] ?? '' );
		$value = $config['value'] ?? '';
		$op    = $config['operator'] ?? 'equals';

		if ( ! $field ) return false;

		// Try xprofile first (BuddyBoss extended profile).
		if ( function_exists( 'xprofile_get_field_data' ) ) {
			$field_value = xprofile_get_field_data( $field, $user_id );
		} else {
			$field_value = get_user_meta( $user_id, $field, true );
		}

		switch ( $op ) {
			case 'equals':   return $field_value == $value;
			case 'contains': return strpos( (string) $field_value, (string) $value ) !== false;
			case 'not_empty': return ! empty( $field_value );
			case 'empty':     return empty( $field_value );
			default:          return false;
		}
	}

	private static function cond_in_group( $config, $user_id ) {
		$group_id = absint( $config['group_id'] ?? 0 );
		if ( ! $group_id ) return false;

		if ( function_exists( 'groups_is_user_member' ) ) {
			return (bool) groups_is_user_member( $user_id, $group_id );
		}

		global $wpdb;
		return (bool) $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM {$wpdb->prefix}bp_groups_members WHERE group_id = %d AND user_id = %d AND is_confirmed = 1 AND is_banned = 0",
			$group_id, $user_id
		) );
	}

	private static function cond_registration_days( $config, $user_id ) {
		$days     = absint( $config['days'] ?? 0 );
		$operator = $config['operator'] ?? 'greater_than';

		$user = get_userdata( $user_id );
		if ( ! $user ) return false;

		$registered    = strtotime( $user->user_registered );
		$days_since    = floor( ( time() - $registered ) / DAY_IN_SECONDS );

		switch ( $operator ) {
			case 'greater_than': return $days_since > $days;
			case 'less_than':    return $days_since < $days;
			case 'equals':       return $days_since == $days;
			default:             return false;
		}
	}

	private static function cond_tag_count( $config, $user_id ) {
		$count    = absint( $config['count'] ?? 0 );
		$operator = $config['operator'] ?? 'greater_than';

		global $wpdb;
		$user_tag_count = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}bb_user_tags WHERE user_id = %d",
			$user_id
		) );

		switch ( $operator ) {
			case 'greater_than': return $user_tag_count > $count;
			case 'less_than':    return $user_tag_count < $count;
			case 'equals':       return $user_tag_count == $count;
			default:             return false;
		}
	}

	private static function cond_has_opened_email( $config, $user_id ) {
		$campaign_id = absint( $config['campaign_id'] ?? 0 );
		if ( ! $campaign_id ) return false;

		global $wpdb;
		return (bool) $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM {$wpdb->prefix}bb_crm_email_opens WHERE user_id = %d AND campaign_id = %d AND opened_at IS NOT NULL LIMIT 1",
			$user_id, $campaign_id
		) );
	}

	/**
	 * Return all available condition types for the admin UI.
	 */
	public static function get_available_conditions() {
		return apply_filters( 'bb_crm_auto_available_conditions', array(
			'has_tag'           => __( 'Has Tag', 'buddyboss-automations' ),
			'not_has_tag'       => __( 'Does Not Have Tag', 'buddyboss-automations' ),
			'in_list'           => __( 'Is In List', 'buddyboss-automations' ),
			'not_in_list'       => __( 'Is Not In List', 'buddyboss-automations' ),
			'user_role'         => __( 'Has User Role', 'buddyboss-automations' ),
			'in_group'          => __( 'Is In Group', 'buddyboss-automations' ),
			'profile_field'     => __( 'Profile Field Value', 'buddyboss-automations' ),
			'registration_days' => __( 'Days Since Registration', 'buddyboss-automations' ),
			'tag_count'         => __( 'Total Tag Count', 'buddyboss-automations' ),
			'has_opened_email'  => __( 'Opened Email', 'buddyboss-automations' ),
		) );
	}
}

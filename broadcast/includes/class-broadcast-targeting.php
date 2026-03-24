<?php
/**
 * Broadcast_Targeting — server-side targeting rule evaluation.
 *
 * Evaluates whether the current user matches all targeting rules
 * configured for an announcement. Uses BuddyBoss Platform APIs —
 * never bypass with direct $wpdb queries (would invalidate BB object cache).
 *
 * Rule type logic:
 *   - Multiple rule types: AND logic (user must satisfy ALL types)
 *   - group_membership rule with multiple groups: OR logic (any one group matches)
 */

defined( 'ABSPATH' ) || exit;

class Broadcast_Targeting {

    /**
     * Check if a user matches all targeting rules for an announcement.
     *
     * @param int $user_id         WordPress user ID.
     * @param int $announcement_id Announcement row ID.
     * @return bool True if user should see the announcement.
     */
    public static function user_matches( int $user_id, int $announcement_id ): bool {
        $rules = Broadcast_Announcement::get_targeting_rules( $announcement_id );

        // No rules configured — show to all logged-in users.
        if ( empty( $rules ) ) {
            return true;
        }

        foreach ( $rules as $rule ) {
            $config = json_decode( $rule->rule_config, true );

            switch ( $rule->rule_type ) {

                case 'member_type':
                    // BuddyBoss Profile Type check.
                    // bp_has_member_type() uses BP object cache — do not bypass with direct query.
                    if ( ! bp_has_member_type( $user_id, $config['member_type'] ) ) {
                        return false;
                    }
                    break;

                case 'user_role':
                    // WordPress user role check.
                    // Use ->roles array comparison, NOT current_user_can() which checks capabilities.
                    $user = get_userdata( $user_id );
                    if ( ! $user || ! in_array( $config['role'], (array) $user->roles, true ) ) {
                        return false;
                    }
                    break;

                case 'group_membership':
                    // BuddyBoss Group membership check.
                    // OR logic: user must be a member of at least ONE of the configured groups.
                    // groups_is_user_member() uses bp_get_user_groups which is cache-aware.
                    $group_ids = (array) ( $config['group_ids'] ?? [] );
                    if ( empty( $group_ids ) ) {
                        break; // No groups specified — skip this rule.
                    }
                    $in_any_group = false;
                    foreach ( $group_ids as $group_id ) {
                        if ( groups_is_user_member( $user_id, (int) $group_id ) ) {
                            $in_any_group = true;
                            break;
                        }
                    }
                    if ( ! $in_any_group ) {
                        return false;
                    }
                    break;

                case 'group_type':
				// BuddyBoss Group Type check.
				// User must be a member of at least one group of the specified type.
				if ( ! function_exists( 'groups_get_groups' ) || ! function_exists( 'bp_groups_get_group_type' ) ) {
					break; // API absent — skip rule.
				}
				$required_type = $config['group_type'] ?? '';
				if ( ! $required_type ) {
					break;
				}
				$user_groups    = groups_get_groups( array( 'user_id' => $user_id, 'per_page' => false, 'fields' => 'ids' ) );
				$user_group_ids = (array) ( $user_groups['groups'] ?? array() );
				$in_type_group  = false;
				foreach ( $user_group_ids as $gid ) {
					if ( bp_groups_get_group_type( (int) $gid ) === $required_type ) {
						$in_type_group = true;
						break;
					}
				}
				if ( ! $in_type_group ) {
					return false;
				}
				break;

                case 'learndash_course':
                    if ( ! function_exists( 'sfwd_lms_has_access' ) ) {
                        break; // Plugin absent — skip rule, don't block.
                    }
                    $course_id     = (int) ( $config['course_id'] ?? 0 );
                    $require_state = $config['state'] ?? 'enrolled'; // 'enrolled' or 'completed'
                    $enrolled      = (bool) sfwd_lms_has_access( $course_id, $user_id );
                    if ( ! $enrolled ) {
                        return false;
                    }
                    if ( 'completed' === $require_state ) {
                        $progress = learndash_course_progress( array(
                            'user_id'   => $user_id,
                            'course_id' => $course_id,
                            'array'     => true,
                        ) );
                        if ( empty( $progress['completed'] ) ) {
                            return false;
                        }
                    }
                    break;

                case 'memberpress_level':
                    if ( ! class_exists( 'MeprUser' ) ) {
                        break; // Plugin absent — skip rule.
                    }
                    $mepr_user        = new MeprUser( $user_id );
                    $active_products  = $mepr_user->active_product_subscriptions();
                    $required_product = (int) ( $config['product_id'] ?? 0 );
                    if ( ! in_array( $required_product, array_map( 'intval', (array) $active_products ), true ) ) {
                        return false;
                    }
                    break;

                case 'xprofile_field':
                    $field_id      = (int) ( $config['field_id'] ?? 0 );
                    $compare_value = $config['compare_value'] ?? '';
                    $field_data    = xprofile_get_field_data( $field_id, $user_id );
                    $field_values  = is_array( $field_data ) ? $field_data : (array) $field_data;
                    if ( ! in_array( $compare_value, $field_values, true ) ) {
                        return false;
                    }
                    break;

                case 'page_url':
                    // Page/URL restriction is evaluated separately in maybe_serve_announcements().
                    // Not a user-eligibility filter — skip here.
                    break;

                default:
                    // Unknown rule type — ignore rather than block.
                    break;
            }
        }

        return true;
    }

    /**
     * Check if the current page/URL matches a page_url rule config.
     * Evaluated server-side at `wp` hook time (is_page() + REQUEST_URI available).
     *
     * @param array $config {page_ids: int[], url_patterns: string[]}
     * @return bool True if current page matches any configured page ID or URL pattern.
     */
    public static function page_url_matches( array $config ): bool {
        $page_ids     = array_map( 'absint', $config['page_ids'] ?? array() );
        $url_patterns = array_map( 'sanitize_text_field', $config['url_patterns'] ?? array() );

        // If both empty, no restriction — show everywhere.
        if ( empty( $page_ids ) && empty( $url_patterns ) ) {
            return true;
        }

        if ( ! empty( $page_ids ) ) {
            foreach ( $page_ids as $page_id ) {
                if ( is_page( $page_id ) ) {
                    return true;
                }
            }
        }

        if ( ! empty( $url_patterns ) ) {
            $current_uri = sanitize_url( home_url( $_SERVER['REQUEST_URI'] ?? '' ) );
            foreach ( $url_patterns as $pattern ) {
                if ( false !== strpos( $current_uri, $pattern ) ) {
                    return true;
                }
            }
        }

        return false;
    }
}

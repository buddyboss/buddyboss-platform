<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Core automation execution engine.
 * Receives trigger events, evaluates conditions, and dispatches actions.
 */
class BB_CRM_Auto_Engine {

	protected static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function __construct() {
		// Listen for trigger events fired by trigger group classes.
		add_action( 'bb_crm_auto_trigger', array( $this, 'handle_trigger' ), 10, 3 );

		// Cron queue processor.
		add_action( 'bb_crm_auto_process_queue', array( $this, 'process_queue' ) );
	}

	/**
	 * Handle an incoming trigger event.
	 *
	 * @param string $trigger_type  e.g. 'user_registered'
	 * @param int    $user_id       The user involved.
	 * @param array  $trigger_data  Context data from the trigger.
	 */
	public function handle_trigger( $trigger_type, $user_id, $trigger_data = array() ) {
		$automations = $this->get_active_automations_for_trigger( $trigger_type );

		if ( empty( $automations ) ) {
			return;
		}

		foreach ( $automations as $automation ) {
			$this->process_automation( $automation, $user_id, $trigger_data );
		}
	}

	/**
	 * Process a single automation for a user.
	 */
	public function process_automation( $automation, $user_id, $trigger_data = array() ) {
		$conditions = json_decode( $automation->conditions, true ) ?: array();
		$actions    = json_decode( $automation->actions, true ) ?: array();

		// Evaluate conditions.
		$conditions_passed = BB_CRM_Auto_Conditions::evaluate( $conditions, $user_id, $trigger_data );

		if ( ! $conditions_passed ) {
			$this->log( $automation->id, $user_id, $automation->trigger_type, $trigger_data, false, array(), 'skipped' );
			return;
		}

		// Execute actions — engine owns the loop so it can handle wait/delay steps.
		$actions_result = $this->execute_action_sequence( $actions, $automation, $user_id, $trigger_data );

		// Update run count.
		global $wpdb;
		$wpdb->query( $wpdb->prepare(
			"UPDATE {$wpdb->prefix}bp_crm_automations SET run_count = run_count + 1, last_run = NOW() WHERE id = %d",
			$automation->id
		) );

		$this->log( $automation->id, $user_id, $automation->trigger_type, $trigger_data, true, $actions_result, 'success' );
	}

	/**
	 * Execute an ordered list of actions for a user.
	 * Stops at any 'wait' action and queues the remaining steps for later.
	 *
	 * @param array  $actions       Ordered action configs.
	 * @param object $automation    Automation row.
	 * @param int    $user_id
	 * @param array  $trigger_data
	 * @return array Results indexed by position.
	 */
	private function execute_action_sequence( $actions, $automation, $user_id, $trigger_data ) {
		$results = array();

		foreach ( $actions as $i => $action ) {
			$type   = $action['type'] ?? '';
			$config = $action['config'] ?? array();

			if ( 'wait' === $type ) {
				// Queue every action after this wait step.
				$remaining = array_values( array_slice( $actions, $i + 1 ) );
				if ( ! empty( $remaining ) ) {
					$this->enqueue_actions( $automation->id, $user_id, $automation->trigger_type, $trigger_data, $remaining, $config );
					$count = count( $remaining );
					$results[ $i ] = array(
						'type'    => 'wait',
						'success' => true,
						'message' => "Queued {$count} action(s) after delay",
					);
				} else {
					$results[ $i ] = array( 'type' => 'wait', 'success' => true, 'message' => 'Wait at end — nothing to queue' );
				}
				break; // Stop; cron will resume.
			}

			// Loop — re-queues the full automation sequence from the beginning.
			if ( 'loop_repeat' === $type ) {
				$loop_count = absint( $trigger_data['_loop_count'] ?? 0 ) + 1;
				$max_loops  = absint( $config['max_loops'] ?? 10 );

				if ( $loop_count > $max_loops ) {
					$results[ $i ] = array(
						'type'    => 'loop_repeat',
						'success' => false,
						'message' => "Max loops ({$max_loops}) reached — sequence ended",
					);
					break;
				}

				// Re-queue the FULL action list from the start.
				$all_actions       = json_decode( $automation->actions, true ) ?: array();
				$loop_trigger_data = array_merge( $trigger_data, array( '_loop_count' => $loop_count ) );
				$this->enqueue_actions( $automation->id, $user_id, $automation->trigger_type, $loop_trigger_data, $all_actions, $config );

				$results[ $i ] = array(
					'type'    => 'loop_repeat',
					'success' => true,
					'message' => "Loop {$loop_count}/{$max_loops} — rescheduled full sequence after delay",
				);
				break; // Current run ends; cron resumes from step 1.
			}

			// Inline condition check — stops sequence if condition fails.
			if ( 'check_condition' === $type ) {
				$ctype  = $config['condition_type'] ?? '';
				$ccfg   = $config['condition_config'] ?? array();
				$negate = ! empty( $config['negate'] );
				$cond   = array(
					'operator' => 'AND',
					'groups'   => array( array( 'type' => $ctype, 'config' => $ccfg, 'negate' => $negate ) ),
				);
				$passes = BB_CRM_Auto_Conditions::evaluate( $cond, $user_id, $trigger_data );
				$results[ $i ] = array(
					'type'    => 'check_condition',
					'success' => $passes,
					'message' => $passes ? "Condition '{$ctype}' passed — continuing" : "Condition '{$ctype}' failed — sequence stopped",
				);
				if ( ! $passes ) {
					break; // Stop sequence for this user.
				}
				continue;
			}

			$result     = BB_CRM_Auto_Actions::run_single( $type, $config, $user_id, $trigger_data );
			$results[ $i ] = array(
				'type'    => $type,
				'success' => $result['success'],
				'message' => $result['message'] ?? '',
			);
		}

		return $results;
	}

	/**
	 * Insert a batch of pending actions into the queue.
	 */
	private function enqueue_actions( $automation_id, $user_id, $trigger_type, $trigger_data, $actions, $wait_config ) {
		$amount  = absint( $wait_config['amount'] ?? 1 );
		$unit    = sanitize_key( $wait_config['unit'] ?? 'hours' );
		$seconds = $this->wait_to_seconds( $amount, $unit );

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->insert(
			$wpdb->prefix . 'bp_crm_automation_queue',
			array(
				'automation_id'   => $automation_id,
				'user_id'         => $user_id,
				'trigger_type'    => $trigger_type,
				'trigger_data'    => wp_json_encode( $trigger_data ),
				'pending_actions' => wp_json_encode( $actions ),
				'scheduled_at'    => gmdate( 'Y-m-d H:i:s', time() + $seconds ),
				'status'          => 'pending',
				'created_at'      => current_time( 'mysql', true ),
			),
			array( '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
		);
	}

	/**
	 * Convert a wait config amount+unit into seconds.
	 */
	private function wait_to_seconds( $amount, $unit ) {
		switch ( $unit ) {
			case 'minutes': return $amount * 60;
			case 'hours':   return $amount * 3600;
			case 'days':    return $amount * 86400;
			case 'weeks':   return $amount * 604800;
			default:        return $amount * 3600;
		}
	}

	/**
	 * Get all active automations for a given trigger type.
	 */
	private function get_active_automations_for_trigger( $trigger_type ) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}bp_crm_automations WHERE status = 'active' AND trigger_type = %s ORDER BY priority ASC",
			$trigger_type
		) );
	}

	/**
	 * Log an automation execution.
	 */
	private function log( $automation_id, $user_id, $trigger_type, $trigger_data, $conditions_passed, $actions_result, $status, $error = '' ) {
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'bp_crm_automation_log',
			array(
				'automation_id'     => $automation_id,
				'user_id'           => $user_id,
				'trigger_type'      => $trigger_type,
				'trigger_data'      => wp_json_encode( $trigger_data ),
				'conditions_passed' => $conditions_passed ? 1 : 0,
				'actions_result'    => wp_json_encode( $actions_result ),
				'status'            => $status,
				'error_message'     => $error,
			),
			array( '%d', '%d', '%s', '%s', '%d', '%s', '%s', '%s' )
		);
	}

	/**
	 * Process any queued automations (called via WP Cron every 5 minutes).
	 * Resumes paused sequences whose scheduled_at time has passed.
	 */
	public function process_queue() {
		global $wpdb;
		$table = $wpdb->prefix . 'bp_crm_automation_queue';

		// Bail if table doesn't exist yet.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) !== $table ) {
			return;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
		$items = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM `{$table}` WHERE status = 'pending' AND scheduled_at <= %s ORDER BY scheduled_at ASC LIMIT 50",
			current_time( 'mysql', true )
		) );

		foreach ( $items as $item ) {
			// Atomic claim: only process if we can flip pending → processing.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$claimed = $wpdb->query( $wpdb->prepare(
				"UPDATE `{$table}` SET status = 'processing' WHERE id = %d AND status = 'pending'",
				$item->id
			) );
			if ( ! $claimed ) {
				continue; // Another process grabbed it.
			}

			$pending_actions = json_decode( $item->pending_actions, true ) ?: array();
			$trigger_data    = json_decode( $item->trigger_data, true ) ?: array();
			$automation      = self::get_automation( $item->automation_id );

			if ( ! $automation || 'active' !== $automation->status ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->update( $table, array( 'status' => 'cancelled' ), array( 'id' => $item->id ) );
				continue;
			}

			// Re-evaluate conditions: if the user's state changed since the trigger
			// (e.g. they purchased, unsubscribed, got a tag), cancel the queued steps.
			$conditions = json_decode( $automation->conditions, true ) ?: array();
			if ( ! empty( $conditions['groups'] ) ) {
				$still_qualifies = BB_CRM_Auto_Conditions::evaluate( $conditions, $item->user_id, $trigger_data );
				if ( ! $still_qualifies ) {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->update( $table, array( 'status' => 'cancelled' ), array( 'id' => $item->id ) );
					$this->log( $automation->id, $item->user_id, $item->trigger_type, $trigger_data, false, array(), 'queue_condition_failed' );
					continue;
				}
			}

			// Resume sequence — may hit another wait and queue further steps.
			$results = $this->execute_action_sequence( $pending_actions, $automation, $item->user_id, $trigger_data );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update( $table, array( 'status' => 'done' ), array( 'id' => $item->id ) );

			$this->log( $automation->id, $item->user_id, $item->trigger_type, $trigger_data, true, $results, 'queued_success' );
		}

		do_action( 'bb_crm_auto_process_queue_start' );
	}

	/**
	 * Get a single automation by ID.
	 */
	public static function get_automation( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}bp_crm_automations WHERE id = %d",
			absint( $id )
		) );
	}

	/**
	 * Get all automations with optional filters.
	 */
	public static function get_automations( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'status'   => '',
			'trigger'  => '',
			'per_page' => 20,
			'page'     => 1,
			'orderby'  => 'priority',
			'order'    => 'ASC',
		);
		$args = wp_parse_args( $args, $defaults );

		$where = array( '1=1' );
		$params = array();

		if ( ! empty( $args['status'] ) ) {
			$where[] = 'status = %s';
			$params[] = $args['status'];
		}
		if ( ! empty( $args['trigger'] ) ) {
			$where[] = 'trigger_type = %s';
			$params[] = $args['trigger'];
		}

		$where_sql = implode( ' AND ', $where );
		$orderby   = sanitize_sql_orderby( $args['orderby'] . ' ' . $args['order'] ) ?: 'priority ASC';
		$offset    = ( absint( $args['page'] ) - 1 ) * absint( $args['per_page'] );

		$sql = "SELECT * FROM {$wpdb->prefix}bp_crm_automations WHERE {$where_sql} ORDER BY {$orderby} LIMIT %d OFFSET %d";
		$params[] = absint( $args['per_page'] );
		$params[] = $offset;

		return $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
	}

	/**
	 * Count automations.
	 */
	public static function count_automations( $status = '' ) {
		global $wpdb;
		if ( $status ) {
			return (int) $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}bp_crm_automations WHERE status = %s",
				$status
			) );
		}
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}bp_crm_automations" );
	}

	/**
	 * Save (create or update) an automation.
	 */
	public static function save_automation( $data ) {
		global $wpdb;

		$fields = array(
			'name'           => sanitize_text_field( $data['name'] ?? '' ),
			'description'    => sanitize_textarea_field( $data['description'] ?? '' ),
			'status'         => in_array( $data['status'] ?? 'active', array( 'active', 'inactive', 'draft' ) ) ? $data['status'] : 'active',
			'trigger_type'   => sanitize_text_field( $data['trigger_type'] ?? '' ),
			'trigger_config' => wp_json_encode( $data['trigger_config'] ?? array() ),
			'conditions'     => wp_json_encode( $data['conditions'] ?? array() ),
			'actions'        => wp_json_encode( $data['actions'] ?? array() ),
			'priority'       => absint( $data['priority'] ?? 10 ),
		);

		if ( ! empty( $data['id'] ) ) {
			$wpdb->update( $wpdb->prefix . 'bp_crm_automations', $fields, array( 'id' => absint( $data['id'] ) ) );
			return absint( $data['id'] );
		}

		$wpdb->insert( $wpdb->prefix . 'bp_crm_automations', $fields );
		return $wpdb->insert_id;
	}

	/**
	 * Delete an automation and its log entries.
	 */
	public static function delete_automation( $id ) {
		global $wpdb;
		$wpdb->delete( $wpdb->prefix . 'bp_crm_automation_log', array( 'automation_id' => absint( $id ) ) );
		return $wpdb->delete( $wpdb->prefix . 'bp_crm_automations', array( 'id' => absint( $id ) ) );
	}

	/**
	 * Get log entries for an automation.
	 */
	public static function get_logs( $automation_id = 0, $per_page = 20, $page = 1 ) {
		global $wpdb;
		$offset = ( $page - 1 ) * $per_page;

		if ( $automation_id ) {
			return $wpdb->get_results( $wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}bp_crm_automation_log WHERE automation_id = %d ORDER BY created_at DESC LIMIT %d OFFSET %d",
				$automation_id, $per_page, $offset
			) );
		}

		return $wpdb->get_results( $wpdb->prepare(
			"SELECT l.*, a.name as automation_name FROM {$wpdb->prefix}bp_crm_automation_log l
			LEFT JOIN {$wpdb->prefix}bp_crm_automations a ON l.automation_id = a.id
			ORDER BY l.created_at DESC LIMIT %d OFFSET %d",
			$per_page, $offset
		) );
	}
}

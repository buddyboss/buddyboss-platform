<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Admin class — adds the Automations submenu under BuddyBoss CRM.
 */
class BB_CRM_Auto_Admin {

	protected static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function __construct() {
		add_action( 'admin_menu',            array( $this, 'register_menus' ), 110 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_post_bb_crm_auto_save',   array( $this, 'handle_save' ) );
		add_action( 'admin_post_bb_crm_auto_delete', array( $this, 'handle_delete' ) );
		add_action( 'admin_post_bb_crm_auto_toggle', array( $this, 'handle_toggle' ) );

		// AJAX handlers for dynamic dropdowns.
		add_action( 'wp_ajax_bb_crm_auto_get_tags',      array( $this, 'ajax_get_tags' ) );
		add_action( 'wp_ajax_bb_crm_auto_get_lists',     array( $this, 'ajax_get_lists' ) );
		add_action( 'wp_ajax_bb_crm_auto_get_roles',     array( $this, 'ajax_get_roles' ) );
		add_action( 'wp_ajax_bb_crm_auto_get_campaigns',   array( $this, 'ajax_get_campaigns' ) );
		add_action( 'wp_ajax_bb_crm_auto_get_automations', array( $this, 'ajax_get_automations' ) );
	}

	public function register_menus() {
		// Automations page under CRM parent.
		add_submenu_page(
			'buddyboss-crm',
			__( 'Automations', 'buddyboss-crm-automations' ),
			__( 'Automations', 'buddyboss-crm-automations' ),
			'manage_options',
			'buddyboss-crm-automations',
			array( $this, 'render_list_page' )
		);

		// Hidden edit page (no menu item).
		add_submenu_page(
			null,
			__( 'Edit Automation', 'buddyboss-crm-automations' ),
			__( 'Edit Automation', 'buddyboss-crm-automations' ),
			'manage_options',
			'buddyboss-crm-automation-edit',
			array( $this, 'render_edit_page' )
		);

		// Hidden log page.
		add_submenu_page(
			null,
			__( 'Automation Log', 'buddyboss-crm-automations' ),
			__( 'Automation Log', 'buddyboss-crm-automations' ),
			'manage_options',
			'buddyboss-crm-automation-log',
			array( $this, 'render_log_page' )
		);
	}

	public function enqueue_assets( $hook ) {
		if ( ! $hook || strpos( $hook, 'buddyboss-crm-auto' ) === false ) return;

		wp_enqueue_style(
			'bb-crm-auto-admin',
			BB_CRM_AUTO_PLUGIN_URL . 'assets/css/bb-crm-auto-admin.css',
			array(),
			BB_CRM_AUTO_VERSION
		);
		wp_enqueue_script(
			'bb-crm-auto-admin',
			BB_CRM_AUTO_PLUGIN_URL . 'assets/js/bb-crm-auto-admin.js',
			array( 'jquery' ),
			BB_CRM_AUTO_VERSION,
			true
		);
		wp_localize_script( 'bb-crm-auto-admin', 'bbCrmAuto', array(
			'ajax_url'          => admin_url( 'admin-ajax.php' ),
			'nonce'             => wp_create_nonce( 'bb_crm_auto' ),
			'triggers'          => BB_CRM_Auto_Triggers::get_all(),
			'triggerCategories' => BB_CRM_Auto_Triggers::get_categories(),
			'actions'           => BB_CRM_Auto_Actions::get_available_actions(),
			'conditions'        => BB_CRM_Auto_Conditions::get_available_conditions(),
			'strings'    => array(
				'confirm_delete' => __( 'Delete this automation? This cannot be undone.', 'buddyboss-crm-automations' ),
				'add_action'     => __( '+ Add Step', 'buddyboss-crm-automations' ),
				'add_condition'  => __( '+ Add Condition', 'buddyboss-crm-automations' ),
				'remove'         => __( 'Remove', 'buddyboss-crm-automations' ),
			),
		) );
	}

	public function render_list_page() {
		$automations = BB_CRM_Auto_Engine::get_automations( array( 'per_page' => 50 ) );
		$total       = BB_CRM_Auto_Engine::count_automations();
		$active      = BB_CRM_Auto_Engine::count_automations( 'active' );
		include BB_CRM_AUTO_PLUGIN_DIR . 'admin/views/list.php';
	}

	public function render_edit_page() {
		$id         = absint( $_GET['automation_id'] ?? 0 );
		$automation = $id ? BB_CRM_Auto_Engine::get_automation( $id ) : null;
		$triggers   = BB_CRM_Auto_Triggers::get_for_select();
		$actions    = BB_CRM_Auto_Actions::get_available_actions();
		$conditions = BB_CRM_Auto_Conditions::get_available_conditions();

		// Decode JSON fields for editing.
		$trigger_config = $automation ? json_decode( $automation->trigger_config, true ) : array();
		$saved_actions  = $automation ? json_decode( $automation->actions, true ) : array();
		$saved_conditions = $automation ? json_decode( $automation->conditions, true ) : array();

		include BB_CRM_AUTO_PLUGIN_DIR . 'admin/views/edit.php';
	}

	public function render_log_page() {
		$id   = absint( $_GET['automation_id'] ?? 0 );
		$logs = BB_CRM_Auto_Engine::get_logs( $id, 30, 1 );
		$auto = $id ? BB_CRM_Auto_Engine::get_automation( $id ) : null;
		include BB_CRM_AUTO_PLUGIN_DIR . 'admin/views/log.php';
	}

	public function handle_save() {
		check_admin_referer( 'bb_crm_auto_save' );
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );

		$actions_raw    = $_POST['actions'] ?? '[]';
		$conditions_raw = $_POST['conditions'] ?? '[]';

		$data = array(
			'id'             => absint( $_POST['automation_id'] ?? 0 ),
			'name'           => sanitize_text_field( $_POST['name'] ?? '' ),
			'description'    => sanitize_textarea_field( $_POST['description'] ?? '' ),
			'status'         => sanitize_text_field( $_POST['status'] ?? 'active' ),
			'trigger_type'   => sanitize_text_field( $_POST['trigger_type'] ?? '' ),
			'trigger_config' => json_decode( wp_unslash( $actions_raw ), true ) ?? array(),
			'actions'        => json_decode( wp_unslash( $actions_raw ), true ) ?? array(),
			'conditions'     => json_decode( wp_unslash( $conditions_raw ), true ) ?? array(),
			'priority'       => absint( $_POST['priority'] ?? 10 ),
		);

		// Rebuild actions from POST fields.
		$actions = array();
		$posted_actions = $_POST['action_type'] ?? array();
		foreach ( $posted_actions as $i => $action_type ) {
			if ( empty( $action_type ) ) continue;
			$actions[] = array(
				'type'   => sanitize_text_field( $action_type ),
				'config' => $this->sanitize_action_config( sanitize_text_field( $action_type ), $_POST['action_config'][ $i ] ?? array() ),
			);
		}

		// Rebuild conditions from POST fields.
		$conditions = array(
			'operator' => sanitize_text_field( $_POST['conditions_operator'] ?? 'AND' ),
			'groups'   => array(),
		);
		$posted_conditions = $_POST['condition_type'] ?? array();
		foreach ( $posted_conditions as $i => $condition_type ) {
			if ( empty( $condition_type ) ) continue;
			$conditions['groups'][] = array(
				'type'   => sanitize_text_field( $condition_type ),
				'negate' => ! empty( $_POST['condition_negate'][ $i ] ),
				'config' => $this->sanitize_condition_config( sanitize_text_field( $condition_type ), $_POST['condition_config'][ $i ] ?? array() ),
			);
		}

		$data['actions']    = $actions;
		$data['conditions'] = $conditions;

		$saved_id = BB_CRM_Auto_Engine::save_automation( $data );

		wp_redirect( add_query_arg( array(
			'page'          => 'buddyboss-crm-automation-edit',
			'automation_id' => $saved_id,
			'saved'         => '1',
		), admin_url( 'admin.php' ) ) );
		exit;
	}

	public function handle_delete() {
		check_admin_referer( 'bb_crm_auto_delete' );
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );

		$id = absint( $_POST['automation_id'] ?? 0 );
		if ( $id ) {
			BB_CRM_Auto_Engine::delete_automation( $id );
		}

		wp_redirect( add_query_arg( array( 'page' => 'buddyboss-crm-automations', 'deleted' => '1' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	public function handle_toggle() {
		check_admin_referer( 'bb_crm_auto_toggle' );
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );

		$id         = absint( $_POST['automation_id'] ?? 0 );
		$automation = BB_CRM_Auto_Engine::get_automation( $id );
		if ( $automation ) {
			$new_status = $automation->status === 'active' ? 'inactive' : 'active';
			BB_CRM_Auto_Engine::save_automation( array( 'id' => $id, 'status' => $new_status ) + (array) $automation );
		}

		wp_redirect( add_query_arg( array( 'page' => 'buddyboss-crm-automations' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	private function sanitize_action_config( $type, $config ) {
		$clean = array();
		switch ( $type ) {
			case 'assign_tag':
			case 'remove_tag':
				$clean['tag_id'] = absint( $config['tag_id'] ?? 0 );
				break;
			case 'add_to_list':
			case 'remove_from_list':
				$clean['list_id'] = absint( $config['list_id'] ?? 0 );
				break;
			case 'send_email':
				$clean['subject'] = sanitize_text_field( $config['subject'] ?? '' );
				$clean['body']    = wp_kses_post( $config['body'] ?? '' );
				break;
			case 'call_webhook':
				$clean['url'] = esc_url_raw( $config['url'] ?? '' );
				break;
			case 'log_activity':
				$clean['note'] = sanitize_text_field( $config['note'] ?? '' );
				break;
			case 'wait':
				$clean['amount'] = absint( $config['amount'] ?? 1 );
				$clean['unit']   = in_array( $config['unit'] ?? '', array( 'minutes', 'hours', 'days', 'weeks' ) ) ? $config['unit'] : 'hours';
				break;
			case 'send_campaign_email':
				$clean['campaign_id'] = absint( $config['campaign_id'] ?? 0 );
				break;
			case 'cancel_sequence':
				$clean['automation_id'] = absint( $config['automation_id'] ?? 0 );
				break;
			case 'loop_repeat':
				$clean['amount']    = absint( $config['amount'] ?? 3 );
				$clean['unit']      = in_array( $config['unit'] ?? '', array( 'minutes', 'hours', 'days', 'weeks' ) ) ? $config['unit'] : 'days';
				$clean['max_loops'] = min( absint( $config['max_loops'] ?? 10 ), 50 );
				break;
			case 'check_condition':
				$clean['condition_type']   = sanitize_text_field( $config['condition_type'] ?? '' );
				$clean['negate']           = ! empty( $config['negate'] );
				$clean['condition_config'] = $this->sanitize_condition_config( $clean['condition_type'], $config['condition_config'] ?? array() );
				break;
		}
		return $clean;
	}

	// ── AJAX handlers ──────────────────────────────────────────────────────

	public function ajax_get_tags() {
		check_ajax_referer( 'bb_crm_auto', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error();

		global $wpdb;
		$rows = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}bp_tags ORDER BY name ASC" );
		$data = array();
		foreach ( $rows as $row ) {
			$data[ $row->id ] = $row->name;
		}
		wp_send_json_success( $data );
	}

	public function ajax_get_lists() {
		check_ajax_referer( 'bb_crm_auto', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error();

		global $wpdb;
		$rows = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}bp_user_lists ORDER BY name ASC" );
		$data = array();
		foreach ( $rows as $row ) {
			$data[ $row->id ] = $row->name;
		}
		wp_send_json_success( $data );
	}

	public function ajax_get_roles() {
		check_ajax_referer( 'bb_crm_auto', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error();

		$editable = get_editable_roles();
		$data     = array();
		foreach ( $editable as $role_key => $role_info ) {
			$data[ $role_key ] = translate_user_role( $role_info['name'] );
		}
		wp_send_json_success( $data );
	}

	public function ajax_get_automations() {
		check_ajax_referer( 'bb_crm_auto', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error();

		$rows = BB_CRM_Auto_Engine::get_automations( array( 'per_page' => 200 ) );
		$data = array( 0 => __( 'All pending sequences', 'buddyboss-crm-automations' ) );
		foreach ( $rows as $row ) {
			$data[ $row->id ] = $row->name;
		}
		wp_send_json_success( $data );
	}

	public function ajax_get_campaigns() {
		check_ajax_referer( 'bb_crm_auto', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error();

		global $wpdb;
		$table = $wpdb->prefix . 'bp_crm_campaigns';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows  = $wpdb->get_results( "SELECT id, name, subject FROM `{$table}` ORDER BY name ASC" );
		$data  = array();
		foreach ( (array) $rows as $row ) {
			$label       = $row->name;
			if ( ! empty( $row->subject ) ) {
				$label .= ' — ' . $row->subject;
			}
			$data[ $row->id ] = $label;
		}
		wp_send_json_success( $data );
	}

	private function sanitize_condition_config( $type, $config ) {
		$clean = array();
		switch ( $type ) {
			case 'has_tag':
			case 'not_has_tag':
				$clean['tag_id'] = absint( $config['tag_id'] ?? 0 );
				break;
			case 'in_list':
			case 'not_in_list':
				$clean['list_id'] = absint( $config['list_id'] ?? 0 );
				break;
			case 'user_role':
				$clean['role'] = sanitize_text_field( $config['role'] ?? '' );
				break;
			case 'in_group':
				$clean['group_id'] = absint( $config['group_id'] ?? 0 );
				break;
			case 'profile_field':
				$clean['field']    = sanitize_text_field( $config['field'] ?? '' );
				$clean['value']    = sanitize_text_field( $config['value'] ?? '' );
				$clean['operator'] = sanitize_text_field( $config['operator'] ?? 'equals' );
				break;
			case 'registration_days':
			case 'tag_count':
				$clean['count']    = absint( $config['count'] ?? 0 );
				$clean['days']     = absint( $config['days'] ?? 0 );
				$clean['operator'] = sanitize_text_field( $config['operator'] ?? 'greater_than' );
				break;
			case 'has_opened_email':
				$clean['campaign_id'] = absint( $config['campaign_id'] ?? 0 );
				break;
		}
		return $clean;
	}
}

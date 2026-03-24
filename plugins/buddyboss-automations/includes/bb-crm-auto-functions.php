<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/** Get all automations. */
function bb_crm_auto_get_automations( $args = array() ) {
	return BB_CRM_Auto_Engine::get_automations( $args );
}

/** Get a single automation by ID. */
function bb_crm_auto_get_automation( $id ) {
	return BB_CRM_Auto_Engine::get_automation( $id );
}

/** Save (create or update) an automation. */
function bb_crm_auto_save( $data ) {
	return BB_CRM_Auto_Engine::save_automation( $data );
}

/** Delete an automation. */
function bb_crm_auto_delete( $id ) {
	return BB_CRM_Auto_Engine::delete_automation( $id );
}

/** Count automations. */
function bb_crm_auto_count( $status = '' ) {
	return BB_CRM_Auto_Engine::count_automations( $status );
}

/** Get automation logs. */
function bb_crm_auto_get_logs( $automation_id = 0, $per_page = 20, $page = 1 ) {
	return BB_CRM_Auto_Engine::get_logs( $automation_id, $per_page, $page );
}

/** Check if automations feature is active. */
function bb_crm_auto_is_active() {
	return function_exists( 'bb_crm_is_feature_active' )
		? bb_crm_is_feature_active( 'automations' )
		: defined( 'BB_CRM_AUTO_VERSION' );
}

/** Get all registered trigger types. */
function bb_crm_auto_get_triggers() {
	return BB_CRM_Auto_Triggers::get_all();
}

/** Get triggers grouped by category for display. */
function bb_crm_auto_get_triggers_grouped() {
	return BB_CRM_Auto_Triggers::get_grouped();
}

/** Get available action types. */
function bb_crm_auto_get_actions() {
	return BB_CRM_Auto_Actions::get_available_actions();
}

/** Get available condition types. */
function bb_crm_auto_get_conditions() {
	return BB_CRM_Auto_Conditions::get_available_conditions();
}

/** Manually fire a trigger for testing. */
function bb_crm_auto_test_trigger( $trigger_type, $user_id, $data = array() ) {
	do_action( 'bb_crm_auto_trigger', $trigger_type, $user_id, $data );
}

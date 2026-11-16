<?php
/**
 * Deprecated functions.
 *
 * @since BuddyBoss 3.0.0
 * @deprecated BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases-pre — pluggable functions (decision 0013): a site that defined the
// pre-rename name first keeps its override, and Platform's guarded declaration below stands down.
if ( function_exists( 'bb_activate_notification' ) && ! function_exists( 'bboss_activate_notification' ) ) {
	// Pluggable: bb_activate_notification() was defined first (bp-custom.php, a plugin); bboss_activate_notification() delegates to it.
	function bboss_activate_notification( $field, $checked ) {
		return bb_activate_notification( $field, $checked );
	}
}
if ( function_exists( 'bb_activity_pin_unpin_post_deprecation_shim' ) && ! function_exists( 'bboss_activity_pin_unpin_post_deprecation_shim' ) ) {
	// Pluggable: bb_activity_pin_unpin_post_deprecation_shim() was defined first (bp-custom.php, a plugin); bboss_activity_pin_unpin_post_deprecation_shim() delegates to it.
	function bboss_activity_pin_unpin_post_deprecation_shim() {
		return bb_activity_pin_unpin_post_deprecation_shim();
	}
}
if ( function_exists( 'bb_admin_setting_callback_messaging_notification_fields' ) && ! function_exists( 'bboss_admin_setting_callback_messaging_notification_fields' ) ) {
	// Pluggable: bb_admin_setting_callback_messaging_notification_fields() was defined first (bp-custom.php, a plugin); bboss_admin_setting_callback_messaging_notification_fields() delegates to it.
	function bboss_admin_setting_callback_messaging_notification_fields() {
		return bb_admin_setting_callback_messaging_notification_fields();
	}
}
if ( function_exists( 'bb_admin_setting_callback_messaging_notification_warning' ) && ! function_exists( 'bboss_admin_setting_callback_messaging_notification_warning' ) ) {
	// Pluggable: bb_admin_setting_callback_messaging_notification_warning() was defined first (bp-custom.php, a plugin); bboss_admin_setting_callback_messaging_notification_warning() delegates to it.
	function bboss_admin_setting_callback_messaging_notification_warning() {
		return bb_admin_setting_callback_messaging_notification_warning();
	}
}
if ( function_exists( 'bb_admin_setting_callback_register_show_legal_agreement' ) && ! function_exists( 'bboss_admin_setting_callback_register_show_legal_agreement' ) ) {
	// Pluggable: bb_admin_setting_callback_register_show_legal_agreement() was defined first (bp-custom.php, a plugin); bboss_admin_setting_callback_register_show_legal_agreement() delegates to it.
	function bboss_admin_setting_callback_register_show_legal_agreement() {
		return bb_admin_setting_callback_register_show_legal_agreement();
	}
}
if ( function_exists( 'bb_deprecated_forum_import_autoload' ) && ! function_exists( 'bboss_deprecated_forum_import_autoload' ) ) {
	// Pluggable: bb_deprecated_forum_import_autoload() was defined first (bp-custom.php, a plugin); bboss_deprecated_forum_import_autoload() delegates to it.
	function bboss_deprecated_forum_import_autoload( $class_name ) {
		return bb_deprecated_forum_import_autoload( $class_name );
	}
}
if ( function_exists( 'bb_deprecated_forum_import_class_map' ) && ! function_exists( 'bboss_deprecated_forum_import_class_map' ) ) {
	// Pluggable: bb_deprecated_forum_import_class_map() was defined first (bp-custom.php, a plugin); bboss_deprecated_forum_import_class_map() delegates to it.
	function bboss_deprecated_forum_import_class_map() {
		return bb_deprecated_forum_import_class_map();
	}
}
if ( function_exists( 'bb_labs_get_settings_fields' ) && ! function_exists( 'bboss_labs_get_settings_fields' ) ) {
	// Pluggable: bb_labs_get_settings_fields() was defined first (bp-custom.php, a plugin); bboss_labs_get_settings_fields() delegates to it.
	function bboss_labs_get_settings_fields() {
		return bb_labs_get_settings_fields();
	}
}
if ( function_exists( 'bb_labs_get_settings_fields_for_section' ) && ! function_exists( 'bboss_labs_get_settings_fields_for_section' ) ) {
	// Pluggable: bb_labs_get_settings_fields_for_section() was defined first (bp-custom.php, a plugin); bboss_labs_get_settings_fields_for_section() delegates to it.
	function bboss_labs_get_settings_fields_for_section( $section_id = '' ) {
		return bb_labs_get_settings_fields_for_section( $section_id );
	}
}
if ( function_exists( 'bb_labs_get_settings_sections' ) && ! function_exists( 'bboss_labs_get_settings_sections' ) ) {
	// Pluggable: bb_labs_get_settings_sections() was defined first (bp-custom.php, a plugin); bboss_labs_get_settings_sections() delegates to it.
	function bboss_labs_get_settings_sections() {
		return bb_labs_get_settings_sections();
	}
}
if ( function_exists( 'bb_labs_info_section_callback' ) && ! function_exists( 'bboss_labs_info_section_callback' ) ) {
	// Pluggable: bb_labs_info_section_callback() was defined first (bp-custom.php, a plugin); bboss_labs_info_section_callback() delegates to it.
	function bboss_labs_info_section_callback() {
		return bb_labs_info_section_callback();
	}
}
if ( function_exists( 'bb_labs_no_settings_callback' ) && ! function_exists( 'bboss_labs_no_settings_callback' ) ) {
	// Pluggable: bb_labs_no_settings_callback() was defined first (bp-custom.php, a plugin); bboss_labs_no_settings_callback() delegates to it.
	function bboss_labs_no_settings_callback() {
		return bb_labs_no_settings_callback();
	}
}
if ( function_exists( 'bb_messaging_notifications_tutorial' ) && ! function_exists( 'bboss_messaging_notifications_tutorial' ) ) {
	// Pluggable: bb_messaging_notifications_tutorial() was defined first (bp-custom.php, a plugin); bboss_messaging_notifications_tutorial() delegates to it.
	function bboss_messaging_notifications_tutorial() {
		return bb_messaging_notifications_tutorial();
	}
}
if ( function_exists( 'bb_notification_get_settings_fields' ) && ! function_exists( 'bboss_notification_get_settings_fields' ) ) {
	// Pluggable: bb_notification_get_settings_fields() was defined first (bp-custom.php, a plugin); bboss_notification_get_settings_fields() delegates to it.
	function bboss_notification_get_settings_fields() {
		return bb_notification_get_settings_fields();
	}
}
if ( function_exists( 'bb_notification_get_settings_fields_for_section' ) && ! function_exists( 'bboss_notification_get_settings_fields_for_section' ) ) {
	// Pluggable: bb_notification_get_settings_fields_for_section() was defined first (bp-custom.php, a plugin); bboss_notification_get_settings_fields_for_section() delegates to it.
	function bboss_notification_get_settings_fields_for_section( $section_id = '' ) {
		return bb_notification_get_settings_fields_for_section( $section_id );
	}
}
if ( function_exists( 'bb_notification_get_settings_sections' ) && ! function_exists( 'bboss_notification_get_settings_sections' ) ) {
	// Pluggable: bb_notification_get_settings_sections() was defined first (bp-custom.php, a plugin); bboss_notification_get_settings_sections() delegates to it.
	function bboss_notification_get_settings_sections() {
		return bb_notification_get_settings_sections();
	}
}
if ( function_exists( 'bb_readylaunch_register_enqueue_style_script' ) && ! function_exists( 'bboss_readylaunch_register_enqueue_style_script' ) ) {
	// Pluggable: bb_readylaunch_register_enqueue_style_script() was defined first (bp-custom.php, a plugin); bboss_readylaunch_register_enqueue_style_script() delegates to it.
	function bboss_readylaunch_register_enqueue_style_script() {
		return bb_readylaunch_register_enqueue_style_script();
	}
}
if ( function_exists( 'bb_readylaunch_settings_page_enqueue_style_script' ) && ! function_exists( 'bboss_readylaunch_settings_page_enqueue_style_script' ) ) {
	// Pluggable: bb_readylaunch_settings_page_enqueue_style_script() was defined first (bp-custom.php, a plugin); bboss_readylaunch_settings_page_enqueue_style_script() delegates to it.
	function bboss_readylaunch_settings_page_enqueue_style_script( $admin_page ) {
		return bb_readylaunch_settings_page_enqueue_style_script( $admin_page );
	}
}
if ( function_exists( 'bb_readylaunch_settings_page_html' ) && ! function_exists( 'bboss_readylaunch_settings_page_html' ) ) {
	// Pluggable: bb_readylaunch_settings_page_html() was defined first (bp-custom.php, a plugin); bboss_readylaunch_settings_page_html() delegates to it.
	function bboss_readylaunch_settings_page_html() {
		return bb_readylaunch_settings_page_html();
	}
}
if ( function_exists( 'bb_registration_page_tutorial' ) && ! function_exists( 'bboss_registration_page_tutorial' ) ) {
	// Pluggable: bb_registration_page_tutorial() was defined first (bp-custom.php, a plugin); bboss_registration_page_tutorial() delegates to it.
	function bboss_registration_page_tutorial() {
		return bb_registration_page_tutorial();
	}
}
if ( function_exists( 'bbp_admin_get_settings_fields' ) && ! function_exists( 'bboss_bbp_admin_get_settings_fields' ) ) {
	// Pluggable: bbp_admin_get_settings_fields() was defined first (bp-custom.php, a plugin); bboss_bbp_admin_get_settings_fields() delegates to it.
	function bboss_bbp_admin_get_settings_fields() {
		return bbp_admin_get_settings_fields();
	}
}
if ( function_exists( 'bbp_admin_get_settings_fields_for_section' ) && ! function_exists( 'bboss_bbp_admin_get_settings_fields_for_section' ) ) {
	// Pluggable: bbp_admin_get_settings_fields_for_section() was defined first (bp-custom.php, a plugin); bboss_bbp_admin_get_settings_fields_for_section() delegates to it.
	function bboss_bbp_admin_get_settings_fields_for_section( $section_id = '' ) {
		return bbp_admin_get_settings_fields_for_section( $section_id );
	}
}
if ( function_exists( 'bbp_admin_get_settings_sections' ) && ! function_exists( 'bboss_bbp_admin_get_settings_sections' ) ) {
	// Pluggable: bbp_admin_get_settings_sections() was defined first (bp-custom.php, a plugin); bboss_bbp_admin_get_settings_sections() delegates to it.
	function bboss_bbp_admin_get_settings_sections() {
		return bbp_admin_get_settings_sections();
	}
}
if ( function_exists( 'bbp_admin_repair' ) && ! function_exists( 'bboss_bbp_admin_repair' ) ) {
	// Pluggable: bbp_admin_repair() was defined first (bp-custom.php, a plugin); bboss_bbp_admin_repair() delegates to it.
	function bboss_bbp_admin_repair() {
		return bbp_admin_repair();
	}
}
if ( function_exists( 'bbp_admin_repair_handler' ) && ! function_exists( 'bboss_bbp_admin_repair_handler' ) ) {
	// Pluggable: bbp_admin_repair_handler() was defined first (bp-custom.php, a plugin); bboss_bbp_admin_repair_handler() delegates to it.
	function bboss_bbp_admin_repair_handler() {
		return bbp_admin_repair_handler();
	}
}
if ( function_exists( 'bbp_admin_tools_feedback' ) && ! function_exists( 'bboss_bbp_admin_tools_feedback' ) ) {
	// Pluggable: bbp_admin_tools_feedback() was defined first (bp-custom.php, a plugin); bboss_bbp_admin_tools_feedback() delegates to it.
	function bboss_bbp_admin_tools_feedback( $message, $class_name = false ) {
		return bbp_admin_tools_feedback( $message, $class_name );
	}
}
if ( function_exists( 'bbp_converter_settings' ) && ! function_exists( 'bboss_bbp_converter_settings' ) ) {
	// Pluggable: bbp_converter_settings() was defined first (bp-custom.php, a plugin); bboss_bbp_converter_settings() delegates to it.
	function bboss_bbp_converter_settings() {
		return bbp_converter_settings();
	}
}
if ( function_exists( 'bbp_core_get_import_forum_tools_settings_admin_tabs' ) && ! function_exists( 'bboss_bbp_core_get_import_forum_tools_settings_admin_tabs' ) ) {
	// Pluggable: bbp_core_get_import_forum_tools_settings_admin_tabs() was defined first (bp-custom.php, a plugin); bboss_bbp_core_get_import_forum_tools_settings_admin_tabs() delegates to it.
	function bboss_bbp_core_get_import_forum_tools_settings_admin_tabs( $tabs ) {
		return bbp_core_get_import_forum_tools_settings_admin_tabs( $tabs );
	}
}
if ( function_exists( 'bbp_core_get_tools_settings_admin_tabs' ) && ! function_exists( 'bboss_bbp_core_get_tools_settings_admin_tabs' ) ) {
	// Pluggable: bbp_core_get_tools_settings_admin_tabs() was defined first (bp-custom.php, a plugin); bboss_bbp_core_get_tools_settings_admin_tabs() delegates to it.
	function bboss_bbp_core_get_tools_settings_admin_tabs( $tabs ) {
		return bbp_core_get_tools_settings_admin_tabs( $tabs );
	}
}
if ( function_exists( 'bbp_new_converter' ) && ! function_exists( 'bboss_bbp_new_converter' ) ) {
	// Pluggable: bbp_new_converter() was defined first (bp-custom.php, a plugin); bboss_bbp_new_converter() delegates to it.
	function bboss_bbp_new_converter( $platform = '' ) {
		return bbp_new_converter( $platform );
	}
}
if ( function_exists( 'bp_admin_registration_setting_tutorial' ) && ! function_exists( 'bboss_admin_registration_setting_tutorial' ) ) {
	// Pluggable: bp_admin_registration_setting_tutorial() was defined first (bp-custom.php, a plugin); bboss_admin_registration_setting_tutorial() delegates to it.
	function bboss_admin_registration_setting_tutorial() {
		return bp_admin_registration_setting_tutorial();
	}
}
if ( function_exists( 'bp_admin_repair_handler' ) && ! function_exists( 'bboss_admin_repair_handler' ) ) {
	// Pluggable: bp_admin_repair_handler() was defined first (bp-custom.php, a plugin); bboss_admin_repair_handler() delegates to it.
	function bboss_admin_repair_handler() {
		return bp_admin_repair_handler();
	}
}
if ( function_exists( 'bp_admin_setting_callback_enable_send_invite_member_type' ) && ! function_exists( 'bboss_admin_setting_callback_enable_send_invite_member_type' ) ) {
	// Pluggable: bp_admin_setting_callback_enable_send_invite_member_type() was defined first (bp-custom.php, a plugin); bboss_admin_setting_callback_enable_send_invite_member_type() delegates to it.
	function bboss_admin_setting_callback_enable_send_invite_member_type( $args = array() ) {
		return bp_admin_setting_callback_enable_send_invite_member_type( $args );
	}
}
if ( function_exists( 'bp_admin_setting_callback_member_invite_email_content' ) && ! function_exists( 'bboss_admin_setting_callback_member_invite_email_content' ) ) {
	// Pluggable: bp_admin_setting_callback_member_invite_email_content() was defined first (bp-custom.php, a plugin); bboss_admin_setting_callback_member_invite_email_content() delegates to it.
	function bboss_admin_setting_callback_member_invite_email_content() {
		return bp_admin_setting_callback_member_invite_email_content();
	}
}
if ( function_exists( 'bp_admin_setting_callback_member_invite_email_subject' ) && ! function_exists( 'bboss_admin_setting_callback_member_invite_email_subject' ) ) {
	// Pluggable: bp_admin_setting_callback_member_invite_email_subject() was defined first (bp-custom.php, a plugin); bboss_admin_setting_callback_member_invite_email_subject() delegates to it.
	function bboss_admin_setting_callback_member_invite_email_subject() {
		return bp_admin_setting_callback_member_invite_email_subject();
	}
}
if ( function_exists( 'bp_admin_setting_callback_member_invite_member_type' ) && ! function_exists( 'bboss_admin_setting_callback_member_invite_member_type' ) ) {
	// Pluggable: bp_admin_setting_callback_member_invite_member_type() was defined first (bp-custom.php, a plugin); bboss_admin_setting_callback_member_invite_member_type() delegates to it.
	function bboss_admin_setting_callback_member_invite_member_type() {
		return bp_admin_setting_callback_member_invite_member_type();
	}
}
if ( function_exists( 'bp_admin_setting_callback_page_directory_dropdown' ) && ! function_exists( 'bboss_admin_setting_callback_page_directory_dropdown' ) ) {
	// Pluggable: bp_admin_setting_callback_page_directory_dropdown() was defined first (bp-custom.php, a plugin); bboss_admin_setting_callback_page_directory_dropdown() delegates to it.
	function bboss_admin_setting_callback_page_directory_dropdown( $args ) {
		return bp_admin_setting_callback_page_directory_dropdown( $args );
	}
}
if ( function_exists( 'bp_admin_setting_callback_register' ) && ! function_exists( 'bboss_admin_setting_callback_register' ) ) {
	// Pluggable: bp_admin_setting_callback_register() was defined first (bp-custom.php, a plugin); bboss_admin_setting_callback_register() delegates to it.
	function bboss_admin_setting_callback_register() {
		return bp_admin_setting_callback_register();
	}
}
if ( function_exists( 'bp_admin_setting_callback_register_allow_custom_registration' ) && ! function_exists( 'bboss_admin_setting_callback_register_allow_custom_registration' ) ) {
	// Pluggable: bp_admin_setting_callback_register_allow_custom_registration() was defined first (bp-custom.php, a plugin); bboss_admin_setting_callback_register_allow_custom_registration() delegates to it.
	function bboss_admin_setting_callback_register_allow_custom_registration() {
		return bp_admin_setting_callback_register_allow_custom_registration();
	}
}
if ( function_exists( 'bp_admin_setting_callback_register_page_url' ) && ! function_exists( 'bboss_admin_setting_callback_register_page_url' ) ) {
	// Pluggable: bp_admin_setting_callback_register_page_url() was defined first (bp-custom.php, a plugin); bboss_admin_setting_callback_register_page_url() delegates to it.
	function bboss_admin_setting_callback_register_page_url() {
		return bp_admin_setting_callback_register_page_url();
	}
}
if ( function_exists( 'bp_admin_setting_callback_register_show_confirm_email' ) && ! function_exists( 'bboss_admin_setting_callback_register_show_confirm_email' ) ) {
	// Pluggable: bp_admin_setting_callback_register_show_confirm_email() was defined first (bp-custom.php, a plugin); bboss_admin_setting_callback_register_show_confirm_email() delegates to it.
	function bboss_admin_setting_callback_register_show_confirm_email() {
		return bp_admin_setting_callback_register_show_confirm_email();
	}
}
if ( function_exists( 'bp_admin_setting_callback_register_show_confirm_password' ) && ! function_exists( 'bboss_admin_setting_callback_register_show_confirm_password' ) ) {
	// Pluggable: bp_admin_setting_callback_register_show_confirm_password() was defined first (bp-custom.php, a plugin); bboss_admin_setting_callback_register_show_confirm_password() delegates to it.
	function bboss_admin_setting_callback_register_show_confirm_password() {
		return bp_admin_setting_callback_register_show_confirm_password();
	}
}
if ( function_exists( 'bp_admin_tools_default_data_save' ) && ! function_exists( 'bboss_admin_tools_default_data_save' ) ) {
	// Pluggable: bp_admin_tools_default_data_save() was defined first (bp-custom.php, a plugin); bboss_admin_tools_default_data_save() delegates to it.
	function bboss_admin_tools_default_data_save() {
		return bp_admin_tools_default_data_save();
	}
}
if ( function_exists( 'bp_core_admin_components_activation_handler' ) && ! function_exists( 'bboss_core_admin_components_activation_handler' ) ) {
	// Pluggable: bp_core_admin_components_activation_handler() was defined first (bp-custom.php, a plugin); bboss_core_admin_components_activation_handler() delegates to it.
	function bboss_core_admin_components_activation_handler() {
		return bp_core_admin_components_activation_handler();
	}
}
if ( function_exists( 'bp_core_admin_components_options' ) && ! function_exists( 'bboss_core_admin_components_options' ) ) {
	// Pluggable: bp_core_admin_components_options() was defined first (bp-custom.php, a plugin); bboss_core_admin_components_options() delegates to it.
	function bboss_core_admin_components_options() {
		return bp_core_admin_components_options();
	}
}
if ( function_exists( 'bp_core_admin_components_settings' ) && ! function_exists( 'bboss_core_admin_components_settings' ) ) {
	// Pluggable: bp_core_admin_components_settings() was defined first (bp-custom.php, a plugin); bboss_core_admin_components_settings() delegates to it.
	function bboss_core_admin_components_settings() {
		return bp_core_admin_components_settings();
	}
}
if ( function_exists( 'bp_core_admin_components_settings_handler' ) && ! function_exists( 'bboss_core_admin_components_settings_handler' ) ) {
	// Pluggable: bp_core_admin_components_settings_handler() was defined first (bp-custom.php, a plugin); bboss_core_admin_components_settings_handler() delegates to it.
	function bboss_core_admin_components_settings_handler() {
		return bp_core_admin_components_settings_handler();
	}
}
if ( function_exists( 'bp_core_admin_directory_pages_description' ) && ! function_exists( 'bboss_core_admin_directory_pages_description' ) ) {
	// Pluggable: bp_core_admin_directory_pages_description() was defined first (bp-custom.php, a plugin); bboss_core_admin_directory_pages_description() delegates to it.
	function bboss_core_admin_directory_pages_description() {
		return bp_core_admin_directory_pages_description();
	}
}
if ( function_exists( 'bp_core_admin_emails_tabs' ) && ! function_exists( 'bboss_core_admin_emails_tabs' ) ) {
	// Pluggable: bp_core_admin_emails_tabs() was defined first (bp-custom.php, a plugin); bboss_core_admin_emails_tabs() delegates to it.
	function bboss_core_admin_emails_tabs( $active_tab = '' ) {
		return bp_core_admin_emails_tabs( $active_tab );
	}
}
if ( function_exists( 'bp_core_admin_maybe_save_pages_settings' ) && ! function_exists( 'bboss_core_admin_maybe_save_pages_settings' ) ) {
	// Pluggable: bp_core_admin_maybe_save_pages_settings() was defined first (bp-custom.php, a plugin); bboss_core_admin_maybe_save_pages_settings() delegates to it.
	function bboss_core_admin_maybe_save_pages_settings() {
		return bp_core_admin_maybe_save_pages_settings();
	}
}
if ( function_exists( 'bp_core_admin_pages_settings' ) && ! function_exists( 'bboss_core_admin_pages_settings' ) ) {
	// Pluggable: bp_core_admin_pages_settings() was defined first (bp-custom.php, a plugin); bboss_core_admin_pages_settings() delegates to it.
	function bboss_core_admin_pages_settings() {
		return bp_core_admin_pages_settings();
	}
}
if ( function_exists( 'bp_core_admin_register_page_fields' ) && ! function_exists( 'bboss_core_admin_register_page_fields' ) ) {
	// Pluggable: bp_core_admin_register_page_fields() was defined first (bp-custom.php, a plugin); bboss_core_admin_register_page_fields() delegates to it.
	function bboss_core_admin_register_page_fields() {
		return bp_core_admin_register_page_fields();
	}
}
if ( function_exists( 'bp_core_admin_register_registration_page_fields' ) && ! function_exists( 'bboss_core_admin_register_registration_page_fields' ) ) {
	// Pluggable: bp_core_admin_register_registration_page_fields() was defined first (bp-custom.php, a plugin); bboss_core_admin_register_registration_page_fields() delegates to it.
	function bboss_core_admin_register_registration_page_fields() {
		return bp_core_admin_register_registration_page_fields();
	}
}
if ( function_exists( 'bp_core_admin_registration_pages_description' ) && ! function_exists( 'bboss_core_admin_registration_pages_description' ) ) {
	// Pluggable: bp_core_admin_registration_pages_description() was defined first (bp-custom.php, a plugin); bboss_core_admin_registration_pages_description() delegates to it.
	function bboss_core_admin_registration_pages_description() {
		return bp_core_admin_registration_pages_description();
	}
}
if ( function_exists( 'bp_core_admin_tools' ) && ! function_exists( 'bboss_core_admin_tools' ) ) {
	// Pluggable: bp_core_admin_tools() was defined first (bp-custom.php, a plugin); bboss_core_admin_tools() delegates to it.
	function bboss_core_admin_tools() {
		return bp_core_admin_tools();
	}
}
if ( function_exists( 'bp_core_get_emails_admin_tabs' ) && ! function_exists( 'bboss_core_get_emails_admin_tabs' ) ) {
	// Pluggable: bp_core_get_emails_admin_tabs() was defined first (bp-custom.php, a plugin); bboss_core_get_emails_admin_tabs() delegates to it.
	function bboss_core_get_emails_admin_tabs( $active_tab = '' ) {
		return bp_core_get_emails_admin_tabs( $active_tab );
	}
}
if ( function_exists( 'bp_core_get_tools_import_profile_settings_admin_tabs' ) && ! function_exists( 'bboss_core_get_tools_import_profile_settings_admin_tabs' ) ) {
	// Pluggable: bp_core_get_tools_import_profile_settings_admin_tabs() was defined first (bp-custom.php, a plugin); bboss_core_get_tools_import_profile_settings_admin_tabs() delegates to it.
	function bboss_core_get_tools_import_profile_settings_admin_tabs( $tabs ) {
		return bp_core_get_tools_import_profile_settings_admin_tabs( $tabs );
	}
}
if ( function_exists( 'bp_core_get_tools_repair_community_settings_admin_tabs' ) && ! function_exists( 'bboss_core_get_tools_repair_community_settings_admin_tabs' ) ) {
	// Pluggable: bp_core_get_tools_repair_community_settings_admin_tabs() was defined first (bp-custom.php, a plugin); bboss_core_get_tools_repair_community_settings_admin_tabs() delegates to it.
	function bboss_core_get_tools_repair_community_settings_admin_tabs( $tabs ) {
		return bp_core_get_tools_repair_community_settings_admin_tabs( $tabs );
	}
}
if ( function_exists( 'bp_core_get_tools_settings_admin_tabs' ) && ! function_exists( 'bboss_core_get_tools_settings_admin_tabs' ) ) {
	// Pluggable: bp_core_get_tools_settings_admin_tabs() was defined first (bp-custom.php, a plugin); bboss_core_get_tools_settings_admin_tabs() delegates to it.
	function bboss_core_get_tools_settings_admin_tabs( $active_tab = '' ) {
		return bp_core_get_tools_settings_admin_tabs( $active_tab );
	}
}
if ( function_exists( 'bp_core_tools_settings_admin_tabs' ) && ! function_exists( 'bboss_core_tools_settings_admin_tabs' ) ) {
	// Pluggable: bp_core_tools_settings_admin_tabs() was defined first (bp-custom.php, a plugin); bboss_core_tools_settings_admin_tabs() delegates to it.
	function bboss_core_tools_settings_admin_tabs( $active_tab = '' ) {
		return bp_core_tools_settings_admin_tabs( $active_tab );
	}
}
if ( function_exists( 'bp_custom_pages_do_settings_fields' ) && ! function_exists( 'bboss_custom_pages_do_settings_fields' ) ) {
	// Pluggable: bp_custom_pages_do_settings_fields() was defined first (bp-custom.php, a plugin); bboss_custom_pages_do_settings_fields() delegates to it.
	function bboss_custom_pages_do_settings_fields( $page, $section ) {
		return bp_custom_pages_do_settings_fields( $page, $section );
	}
}
if ( function_exists( 'bp_custom_pages_do_settings_sections' ) && ! function_exists( 'bboss_custom_pages_do_settings_sections' ) ) {
	// Pluggable: bp_custom_pages_do_settings_sections() was defined first (bp-custom.php, a plugin); bboss_custom_pages_do_settings_sections() delegates to it.
	function bboss_custom_pages_do_settings_sections( $page ) {
		return bp_custom_pages_do_settings_sections( $page );
	}
}
if ( function_exists( 'bp_dd_clear_db' ) && ! function_exists( 'bboss_dd_clear_db' ) ) {
	// Pluggable: bp_dd_clear_db() was defined first (bp-custom.php, a plugin); bboss_dd_clear_db() delegates to it.
	function bboss_dd_clear_db() {
		return bp_dd_clear_db();
	}
}
if ( function_exists( 'bp_dd_create_forums' ) && ! function_exists( 'bboss_dd_create_forums' ) ) {
	// Pluggable: bp_dd_create_forums() was defined first (bp-custom.php, a plugin); bboss_dd_create_forums() delegates to it.
	function bboss_dd_create_forums( $forum, $users = array() ) {
		return bp_dd_create_forums( $forum, $users );
	}
}
if ( function_exists( 'bp_dd_create_forums_topics' ) && ! function_exists( 'bboss_dd_create_forums_topics' ) ) {
	// Pluggable: bp_dd_create_forums_topics() was defined first (bp-custom.php, a plugin); bboss_dd_create_forums_topics() delegates to it.
	function bboss_dd_create_forums_topics( $topic_data, $forum_id, $users = array() ) {
		return bp_dd_create_forums_topics( $topic_data, $forum_id, $users );
	}
}
if ( function_exists( 'bp_dd_create_forums_topics_replies' ) && ! function_exists( 'bboss_dd_create_forums_topics_replies' ) ) {
	// Pluggable: bp_dd_create_forums_topics_replies() was defined first (bp-custom.php, a plugin); bboss_dd_create_forums_topics_replies() delegates to it.
	function bboss_dd_create_forums_topics_replies( $reply_data, $topic_id, $users = array() ) {
		return bp_dd_create_forums_topics_replies( $reply_data, $topic_id, $users );
	}
}
if ( function_exists( 'bp_dd_delete_dummy_forum' ) && ! function_exists( 'bboss_dd_delete_dummy_forum' ) ) {
	// Pluggable: bp_dd_delete_dummy_forum() was defined first (bp-custom.php, a plugin); bboss_dd_delete_dummy_forum() delegates to it.
	function bboss_dd_delete_dummy_forum() {
		return bp_dd_delete_dummy_forum();
	}
}
if ( function_exists( 'bp_dd_delete_dummy_groups' ) && ! function_exists( 'bboss_dd_delete_dummy_groups' ) ) {
	// Pluggable: bp_dd_delete_dummy_groups() was defined first (bp-custom.php, a plugin); bboss_dd_delete_dummy_groups() delegates to it.
	function bboss_dd_delete_dummy_groups() {
		return bp_dd_delete_dummy_groups();
	}
}
if ( function_exists( 'bp_dd_delete_dummy_members' ) && ! function_exists( 'bboss_dd_delete_dummy_members' ) ) {
	// Pluggable: bp_dd_delete_dummy_members() was defined first (bp-custom.php, a plugin); bboss_dd_delete_dummy_members() delegates to it.
	function bboss_dd_delete_dummy_members() {
		return bp_dd_delete_dummy_members();
	}
}
if ( function_exists( 'bp_dd_delete_dummy_members_related_data' ) && ! function_exists( 'bboss_dd_delete_dummy_members_related_data' ) ) {
	// Pluggable: bp_dd_delete_dummy_members_related_data() was defined first (bp-custom.php, a plugin); bboss_dd_delete_dummy_members_related_data() delegates to it.
	function bboss_dd_delete_dummy_members_related_data() {
		return bp_dd_delete_dummy_members_related_data();
	}
}
if ( function_exists( 'bp_dd_delete_dummy_reply' ) && ! function_exists( 'bboss_dd_delete_dummy_reply' ) ) {
	// Pluggable: bp_dd_delete_dummy_reply() was defined first (bp-custom.php, a plugin); bboss_dd_delete_dummy_reply() delegates to it.
	function bboss_dd_delete_dummy_reply() {
		return bp_dd_delete_dummy_reply();
	}
}
if ( function_exists( 'bp_dd_delete_dummy_topic' ) && ! function_exists( 'bboss_dd_delete_dummy_topic' ) ) {
	// Pluggable: bp_dd_delete_dummy_topic() was defined first (bp-custom.php, a plugin); bboss_dd_delete_dummy_topic() delegates to it.
	function bboss_dd_delete_dummy_topic() {
		return bp_dd_delete_dummy_topic();
	}
}
if ( function_exists( 'bp_dd_delete_dummy_xprofile' ) && ! function_exists( 'bboss_dd_delete_dummy_xprofile' ) ) {
	// Pluggable: bp_dd_delete_dummy_xprofile() was defined first (bp-custom.php, a plugin); bboss_dd_delete_dummy_xprofile() delegates to it.
	function bboss_dd_delete_dummy_xprofile() {
		return bp_dd_delete_dummy_xprofile();
	}
}
if ( function_exists( 'bp_dd_delete_import_records' ) && ! function_exists( 'bboss_dd_delete_import_records' ) ) {
	// Pluggable: bp_dd_delete_import_records() was defined first (bp-custom.php, a plugin); bboss_dd_delete_import_records() delegates to it.
	function bboss_dd_delete_import_records() {
		return bp_dd_delete_import_records();
	}
}
if ( function_exists( 'bp_dd_friends_add_friend_date_fix' ) && ! function_exists( 'bboss_dd_friends_add_friend_date_fix' ) ) {
	// Pluggable: bp_dd_friends_add_friend_date_fix() was defined first (bp-custom.php, a plugin); bboss_dd_friends_add_friend_date_fix() delegates to it.
	function bboss_dd_friends_add_friend_date_fix( $current_time ) {
		return bp_dd_friends_add_friend_date_fix( $current_time );
	}
}
if ( function_exists( 'bp_dd_get_forums_enable_groups_ids' ) && ! function_exists( 'bboss_dd_get_forums_enable_groups_ids' ) ) {
	// Pluggable: bp_dd_get_forums_enable_groups_ids() was defined first (bp-custom.php, a plugin); bboss_dd_get_forums_enable_groups_ids() delegates to it.
	function bboss_dd_get_forums_enable_groups_ids( $count, $output = 'array' ) {
		return bp_dd_get_forums_enable_groups_ids( $count, $output );
	}
}
if ( function_exists( 'bp_dd_get_random_date' ) && ! function_exists( 'bboss_dd_get_random_date' ) ) {
	// Pluggable: bp_dd_get_random_date() was defined first (bp-custom.php, a plugin); bboss_dd_get_random_date() delegates to it.
	function bboss_dd_get_random_date( $days_from = 30, $days_to = 0 ) {
		return bp_dd_get_random_date( $days_from, $days_to );
	}
}
if ( function_exists( 'bp_dd_get_random_forums_ids' ) && ! function_exists( 'bboss_dd_get_random_forums_ids' ) ) {
	// Pluggable: bp_dd_get_random_forums_ids() was defined first (bp-custom.php, a plugin); bboss_dd_get_random_forums_ids() delegates to it.
	function bboss_dd_get_random_forums_ids( $count = 1, $output = 'array' ) {
		return bp_dd_get_random_forums_ids( $count, $output );
	}
}
if ( function_exists( 'bp_dd_get_random_groups_ids' ) && ! function_exists( 'bboss_dd_get_random_groups_ids' ) ) {
	// Pluggable: bp_dd_get_random_groups_ids() was defined first (bp-custom.php, a plugin); bboss_dd_get_random_groups_ids() delegates to it.
	function bboss_dd_get_random_groups_ids( $count = 1, $output = 'array' ) {
		return bp_dd_get_random_groups_ids( $count, $output );
	}
}
if ( function_exists( 'bp_dd_get_random_topics_ids' ) && ! function_exists( 'bboss_dd_get_random_topics_ids' ) ) {
	// Pluggable: bp_dd_get_random_topics_ids() was defined first (bp-custom.php, a plugin); bboss_dd_get_random_topics_ids() delegates to it.
	function bboss_dd_get_random_topics_ids( $count = 1, $output = 'array' ) {
		return bp_dd_get_random_topics_ids( $count, $output );
	}
}
if ( function_exists( 'bp_dd_get_random_users_ids' ) && ! function_exists( 'bboss_dd_get_random_users_ids' ) ) {
	// Pluggable: bp_dd_get_random_users_ids() was defined first (bp-custom.php, a plugin); bboss_dd_get_random_users_ids() delegates to it.
	function bboss_dd_get_random_users_ids( $count = 1, $output = 'array' ) {
		return bp_dd_get_random_users_ids( $count, $output );
	}
}
if ( function_exists( 'bp_dd_get_root_admin_page' ) && ! function_exists( 'bboss_dd_get_root_admin_page' ) ) {
	// Pluggable: bp_dd_get_root_admin_page() was defined first (bp-custom.php, a plugin); bboss_dd_get_root_admin_page() delegates to it.
	function bboss_dd_get_root_admin_page() {
		return bp_dd_get_root_admin_page();
	}
}
if ( function_exists( 'bp_dd_get_time' ) && ! function_exists( 'bboss_dd_get_time' ) ) {
	// Pluggable: bp_dd_get_time() was defined first (bp-custom.php, a plugin); bboss_dd_get_time() delegates to it.
	function bboss_dd_get_time() {
		return bp_dd_get_time();
	}
}
if ( function_exists( 'bp_dd_groups_join_group_date_fix' ) && ! function_exists( 'bboss_dd_groups_join_group_date_fix' ) ) {
	// Pluggable: bp_dd_groups_join_group_date_fix() was defined first (bp-custom.php, a plugin); bboss_dd_groups_join_group_date_fix() delegates to it.
	function bboss_dd_groups_join_group_date_fix( $args ) {
		return bp_dd_groups_join_group_date_fix( $args );
	}
}
if ( function_exists( 'bp_dd_import_forums' ) && ! function_exists( 'bboss_dd_import_forums' ) ) {
	// Pluggable: bp_dd_import_forums() was defined first (bp-custom.php, a plugin); bboss_dd_import_forums() delegates to it.
	function bboss_dd_import_forums( $users = false ) {
		return bp_dd_import_forums( $users );
	}
}
if ( function_exists( 'bp_dd_import_forums_in_groups' ) && ! function_exists( 'bboss_dd_import_forums_in_groups' ) ) {
	// Pluggable: bp_dd_import_forums_in_groups() was defined first (bp-custom.php, a plugin); bboss_dd_import_forums_in_groups() delegates to it.
	function bboss_dd_import_forums_in_groups() {
		return bp_dd_import_forums_in_groups();
	}
}
if ( function_exists( 'bp_dd_import_forums_topics' ) && ! function_exists( 'bboss_dd_import_forums_topics' ) ) {
	// Pluggable: bp_dd_import_forums_topics() was defined first (bp-custom.php, a plugin); bboss_dd_import_forums_topics() delegates to it.
	function bboss_dd_import_forums_topics( $forums = false ) {
		return bp_dd_import_forums_topics( $forums );
	}
}
if ( function_exists( 'bp_dd_import_forums_topics_replies' ) && ! function_exists( 'bboss_dd_import_forums_topics_replies' ) ) {
	// Pluggable: bp_dd_import_forums_topics_replies() was defined first (bp-custom.php, a plugin); bboss_dd_import_forums_topics_replies() delegates to it.
	function bboss_dd_import_forums_topics_replies( $topics = false ) {
		return bp_dd_import_forums_topics_replies( $topics );
	}
}
if ( function_exists( 'bp_dd_import_groups' ) && ! function_exists( 'bboss_dd_import_groups' ) ) {
	// Pluggable: bp_dd_import_groups() was defined first (bp-custom.php, a plugin); bboss_dd_import_groups() delegates to it.
	function bboss_dd_import_groups( $users = false ) {
		return bp_dd_import_groups( $users );
	}
}
if ( function_exists( 'bp_dd_import_groups_activity' ) && ! function_exists( 'bboss_dd_import_groups_activity' ) ) {
	// Pluggable: bp_dd_import_groups_activity() was defined first (bp-custom.php, a plugin); bboss_dd_import_groups_activity() delegates to it.
	function bboss_dd_import_groups_activity() {
		return bp_dd_import_groups_activity();
	}
}
if ( function_exists( 'bp_dd_import_groups_members' ) && ! function_exists( 'bboss_dd_import_groups_members' ) ) {
	// Pluggable: bp_dd_import_groups_members() was defined first (bp-custom.php, a plugin); bboss_dd_import_groups_members() delegates to it.
	function bboss_dd_import_groups_members( $groups = false ) {
		return bp_dd_import_groups_members( $groups );
	}
}
if ( function_exists( 'bp_dd_import_users' ) && ! function_exists( 'bboss_dd_import_users' ) ) {
	// Pluggable: bp_dd_import_users() was defined first (bp-custom.php, a plugin); bboss_dd_import_users() delegates to it.
	function bboss_dd_import_users() {
		return bp_dd_import_users();
	}
}
if ( function_exists( 'bp_dd_import_users_activity' ) && ! function_exists( 'bboss_dd_import_users_activity' ) ) {
	// Pluggable: bp_dd_import_users_activity() was defined first (bp-custom.php, a plugin); bboss_dd_import_users_activity() delegates to it.
	function bboss_dd_import_users_activity() {
		return bp_dd_import_users_activity();
	}
}
if ( function_exists( 'bp_dd_import_users_friends' ) && ! function_exists( 'bboss_dd_import_users_friends' ) ) {
	// Pluggable: bp_dd_import_users_friends() was defined first (bp-custom.php, a plugin); bboss_dd_import_users_friends() delegates to it.
	function bboss_dd_import_users_friends() {
		return bp_dd_import_users_friends();
	}
}
if ( function_exists( 'bp_dd_import_users_messages' ) && ! function_exists( 'bboss_dd_import_users_messages' ) ) {
	// Pluggable: bp_dd_import_users_messages() was defined first (bp-custom.php, a plugin); bboss_dd_import_users_messages() delegates to it.
	function bboss_dd_import_users_messages() {
		return bp_dd_import_users_messages();
	}
}
if ( function_exists( 'bp_dd_import_users_profile' ) && ! function_exists( 'bboss_dd_import_users_profile' ) ) {
	// Pluggable: bp_dd_import_users_profile() was defined first (bp-custom.php, a plugin); bboss_dd_import_users_profile() delegates to it.
	function bboss_dd_import_users_profile() {
		return bp_dd_import_users_profile();
	}
}
if ( function_exists( 'bp_dd_imported_disabled' ) && ! function_exists( 'bboss_dd_imported_disabled' ) ) {
	// Pluggable: bp_dd_imported_disabled() was defined first (bp-custom.php, a plugin); bboss_dd_imported_disabled() delegates to it.
	function bboss_dd_imported_disabled( $group, $import ) {
		return bp_dd_imported_disabled( $group, $import );
	}
}
if ( function_exists( 'bp_dd_is_imported' ) && ! function_exists( 'bboss_dd_is_imported' ) ) {
	// Pluggable: bp_dd_is_imported() was defined first (bp-custom.php, a plugin); bboss_dd_is_imported() delegates to it.
	function bboss_dd_is_imported( $group, $import ) {
		return bp_dd_is_imported( $group, $import );
	}
}
if ( function_exists( 'bp_dd_update_import' ) && ! function_exists( 'bboss_dd_update_import' ) ) {
	// Pluggable: bp_dd_update_import() was defined first (bp-custom.php, a plugin); bboss_dd_update_import() delegates to it.
	function bboss_dd_update_import( $group, $import ) {
		return bp_dd_update_import( $group, $import );
	}
}
if ( function_exists( 'bp_email_invites_tutorial' ) && ! function_exists( 'bboss_email_invites_tutorial' ) ) {
	// Pluggable: bp_email_invites_tutorial() was defined first (bp-custom.php, a plugin); bboss_email_invites_tutorial() delegates to it.
	function bboss_email_invites_tutorial() {
		return bp_email_invites_tutorial();
	}
}
if ( function_exists( 'bp_import_profile_types_admin_menu' ) && ! function_exists( 'bboss_import_profile_types_admin_menu' ) ) {
	// Pluggable: bp_import_profile_types_admin_menu() was defined first (bp-custom.php, a plugin); bboss_import_profile_types_admin_menu() delegates to it.
	function bboss_import_profile_types_admin_menu() {
		return bp_import_profile_types_admin_menu();
	}
}
if ( function_exists( 'bp_media_get_tools_media_settings_admin_tabs' ) && ! function_exists( 'bboss_media_get_tools_media_settings_admin_tabs' ) ) {
	// Pluggable: bp_media_get_tools_media_settings_admin_tabs() was defined first (bp-custom.php, a plugin); bboss_media_get_tools_media_settings_admin_tabs() delegates to it.
	function bboss_media_get_tools_media_settings_admin_tabs( $tabs ) {
		return bp_media_get_tools_media_settings_admin_tabs( $tabs );
	}
}
if ( function_exists( 'bp_member_type_import_submenu_page' ) && ! function_exists( 'bboss_member_type_import_submenu_page' ) ) {
	// Pluggable: bp_member_type_import_submenu_page() was defined first (bp-custom.php, a plugin); bboss_member_type_import_submenu_page() delegates to it.
	function bboss_member_type_import_submenu_page() {
		return bp_member_type_import_submenu_page();
	}
}
if ( function_exists( 'bp_moderation_admin_category_listing_add_tab' ) && ! function_exists( 'bboss_moderation_admin_category_listing_add_tab' ) ) {
	// Pluggable: bp_moderation_admin_category_listing_add_tab() was defined first (bp-custom.php, a plugin); bboss_moderation_admin_category_listing_add_tab() delegates to it.
	function bboss_moderation_admin_category_listing_add_tab() {
		return bp_moderation_admin_category_listing_add_tab();
	}
}
if ( function_exists( 'bp_moderation_get_settings_fields' ) && ! function_exists( 'bboss_moderation_get_settings_fields' ) ) {
	// Pluggable: bp_moderation_get_settings_fields() was defined first (bp-custom.php, a plugin); bboss_moderation_get_settings_fields() delegates to it.
	function bboss_moderation_get_settings_fields() {
		return bp_moderation_get_settings_fields();
	}
}
if ( function_exists( 'bp_moderation_get_settings_fields_for_section' ) && ! function_exists( 'bboss_moderation_get_settings_fields_for_section' ) ) {
	// Pluggable: bp_moderation_get_settings_fields_for_section() was defined first (bp-custom.php, a plugin); bboss_moderation_get_settings_fields_for_section() delegates to it.
	function bboss_moderation_get_settings_fields_for_section( $section_id = '' ) {
		return bp_moderation_get_settings_fields_for_section( $section_id );
	}
}
if ( function_exists( 'bp_moderation_get_settings_sections' ) && ! function_exists( 'bboss_moderation_get_settings_sections' ) ) {
	// Pluggable: bp_moderation_get_settings_sections() was defined first (bp-custom.php, a plugin); bboss_moderation_get_settings_sections() delegates to it.
	function bboss_moderation_get_settings_sections() {
		return bp_moderation_get_settings_sections();
	}
}
if ( function_exists( 'bp_register_learndash_integration' ) && ! function_exists( 'bboss_register_learndash_integration' ) ) {
	// Pluggable: bp_register_learndash_integration() was defined first (bp-custom.php, a plugin); bboss_register_learndash_integration() delegates to it.
	function bboss_register_learndash_integration() {
		return bp_register_learndash_integration();
	}
}
if ( function_exists( 'bp_register_member_type_import_submenu_page' ) && ! function_exists( 'bboss_register_member_type_import_submenu_page' ) ) {
	// Pluggable: bp_register_member_type_import_submenu_page() was defined first (bp-custom.php, a plugin); bboss_register_member_type_import_submenu_page() delegates to it.
	function bboss_register_member_type_import_submenu_page() {
		return bp_register_member_type_import_submenu_page();
	}
}
if ( function_exists( 'bp_repair_community_submenu_page' ) && ! function_exists( 'bboss_repair_community_submenu_page' ) ) {
	// Pluggable: bp_repair_community_submenu_page() was defined first (bp-custom.php, a plugin); bboss_repair_community_submenu_page() delegates to it.
	function bboss_repair_community_submenu_page() {
		return bp_repair_community_submenu_page();
	}
}
if ( function_exists( 'bp_search_get_settings_fields' ) && ! function_exists( 'bboss_search_get_settings_fields' ) ) {
	// Pluggable: bp_search_get_settings_fields() was defined first (bp-custom.php, a plugin); bboss_search_get_settings_fields() delegates to it.
	function bboss_search_get_settings_fields() {
		return bp_search_get_settings_fields();
	}
}
if ( function_exists( 'bp_search_get_settings_fields_for_section' ) && ! function_exists( 'bboss_search_get_settings_fields_for_section' ) ) {
	// Pluggable: bp_search_get_settings_fields_for_section() was defined first (bp-custom.php, a plugin); bboss_search_get_settings_fields_for_section() delegates to it.
	function bboss_search_get_settings_fields_for_section( $section_id = '' ) {
		return bp_search_get_settings_fields_for_section( $section_id );
	}
}
if ( function_exists( 'bp_search_get_settings_sections' ) && ! function_exists( 'bboss_search_get_settings_sections' ) ) {
	// Pluggable: bp_search_get_settings_sections() was defined first (bp-custom.php, a plugin); bboss_search_get_settings_sections() delegates to it.
	function bboss_search_get_settings_sections() {
		return bp_search_get_settings_sections();
	}
}
// @bbwt-com-end compat-layer

// ──────────────────────────────────────────────────────────────────────────────
// Search Settings 2.0 deprecated functions and hook compatibility.
// Legacy settings API functions were removed from bboss-search-settings.php.
// Search settings are now managed by Settings 2.0 (bboss-admin-settings-search.php).
// ──────────────────────────────────────────────────────────────────────────────

if ( ! function_exists( 'bboss_search_get_settings_sections' ) ) {
	/**
	 * Get the Search settings sections.
	 *
	 * @since BuddyBoss 1.0.0
	 * @deprecated BuddyBoss 3.0.0 Search settings are now managed by Settings 2.0.
	 *
	 * @return array Empty array.
	 */
	function bboss_search_get_settings_sections() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'Settings 2.0 Search feature (bb_admin_settings_register_search_feature)' );

		$sections = array();

		/**
		 * Filter the Search settings sections.
		 *
		 * @since BuddyBoss 1.0.0
		 * @deprecated BuddyBoss 3.0.0 Use {@see 'bb_search_after_register_settings_fields'} to register additional settings.
		 *
		 * @param array $sections Search settings sections.
		 */
		return (array) apply_filters_deprecated( 'bp_search_get_settings_sections', array( $sections ), 'BuddyBoss 3.0.0', 'bb_search_after_register_settings_fields' );
	}
}

if ( ! function_exists( 'bboss_search_get_settings_fields' ) ) {
	/**
	 * Get all of the settings fields.
	 *
	 * @since BuddyBoss 1.0.0
	 * @deprecated BuddyBoss 3.0.0 Search settings are now managed by Settings 2.0.
	 *
	 * @return array Empty array.
	 */
	function bboss_search_get_settings_fields() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'Settings 2.0 Search feature (bb_admin_settings_register_search_feature)' );

		$fields = array();

		/**
		 * Filter all Search settings fields.
		 *
		 * @since BuddyBoss 1.0.0
		 * @deprecated BuddyBoss 3.0.0 Use {@see 'bb_search_after_register_settings_fields'} to register additional fields.
		 *
		 * @param array $fields Search settings fields grouped by section.
		 */
		return (array) apply_filters_deprecated( 'bp_search_get_settings_fields', array( $fields ), 'BuddyBoss 3.0.0', 'bb_search_after_register_settings_fields' );
	}
}

if ( ! function_exists( 'bboss_search_get_settings_fields_for_section' ) ) {
	/**
	 * Get settings fields for a section.
	 *
	 * @since BuddyBoss 1.0.0
	 * @deprecated BuddyBoss 3.0.0 Search settings are now managed by Settings 2.0.
	 *
	 * @param string $section_id Section ID.
	 *
	 * @return array Empty array.
	 */
	function bboss_search_get_settings_fields_for_section( $section_id = '' ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'Settings 2.0 Search feature (bb_admin_settings_register_search_feature)' );

		$fields = array();

		/**
		 * Filter Search settings fields for a specific section.
		 *
		 * @since BuddyBoss 1.0.0
		 * @deprecated BuddyBoss 3.0.0 Use {@see 'bb_search_after_register_settings_fields'} to register additional fields.
		 *
		 * @param array  $fields     Settings fields for the section.
		 * @param string $section_id The section ID.
		 */
		return (array) apply_filters_deprecated( 'bp_search_get_settings_fields_for_section', array( $fields, $section_id ), 'BuddyBoss 3.0.0', 'bb_search_after_register_settings_fields' );
	}
}

/**
 * Fire the legacy `bp_admin_setting_search_register_fields` hook after
 * Settings 2.0 finishes registering search fields.
 *
 * The original hook passed a `BP_Admin_Setting_Search` instance. Settings 2.0
 * no longer uses that class, so a no-op stub is passed to satisfy callbacks
 * that call add_section()/add_field() on the argument.
 *
 * @since BuddyBoss 1.2.6
 * @deprecated BuddyBoss 3.0.0 Use {@see 'bb_search_after_register_settings_fields'} instead.
 */
add_action(
	'bb_search_after_register_settings_fields',
	static function () {
		do_action_deprecated(
			'bp_admin_setting_search_register_fields',
			array(
				new class() {
					/**
					 * No-op stub for BBoss_Admin_Setting_tab::add_section().
					 *
					 * @param mixed ...$args Ignored.
					 */
					public function add_section( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found

					/**
					 * No-op stub for BBoss_Admin_Setting_tab::add_field().
					 *
					 * @param mixed ...$args Ignored.
					 */
					public function add_field( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
				},
			),
			'BuddyBoss 3.0.0',
			'bb_search_after_register_settings_fields'
		);
	}
);

// ──────────────────────────────────────────────────────────────────────────────
// Activity Settings 2.0 deprecated hook compatibility.
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Fire the legacy `bp_admin_setting_activity_register_fields` hook after
 * Settings 2.0 finishes registering activity fields.
 *
 * The original hook passed a `BP_Admin_Setting_Activity` instance. Settings 2.0
 * no longer uses that class, so a no-op stub is passed to satisfy callbacks
 * (e.g. Pro access-control) that call add_section()/add_field() on the argument.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss 3.0.0 Use {@see 'bb_activity_after_register_settings_fields'} instead.
 */
add_action(
	'bb_activity_after_register_settings_fields',
	static function () {
		do_action_deprecated(
			'bp_admin_setting_activity_register_fields',
			array(
				new class() {
					/**
					 * No-op stub for BBoss_Admin_Setting_tab::add_section().
					 *
					 * @param mixed ...$args Ignored.
					 */
					public function add_section( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found

					/**
					 * No-op stub for BBoss_Admin_Setting_tab::add_field().
					 *
					 * @param mixed ...$args Ignored.
					 */
					public function add_field( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
				},
			),
			'BuddyBoss 3.0.0',
			'bb_activity_after_register_settings_fields'
		);
	}
);

// ──────────────────────────────────────────────────────────────────────────────
// Messaging Notifications Settings 2.0 deprecated function stubs.
// These functions were in bp-notifications-settings.php and rendered the
// Messaging Notifications section on the legacy Notifications settings page.
// Settings 2.0 replaces them with bb_admin_settings_register_messages_feature().
// ──────────────────────────────────────────────────────────────────────────────

if ( ! function_exists( 'bboss_admin_setting_callback_messaging_notification_warning' ) ) {
	/**
	 * Display a warning when Pusher Live Messaging is enabled but messaging
	 * notification settings are disabled.
	 *
	 * @since BuddyBoss 2.1.4
	 * @deprecated BuddyBoss 3.0.0 Replaced by a notice field in Settings 2.0 Messages feature.
	 */
	function bboss_admin_setting_callback_messaging_notification_warning() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'Settings 2.0 Messages feature (bb-messages-live-messaging-notice field)' );
	}
}

if ( ! function_exists( 'bboss_messaging_notifications_tutorial' ) ) {
	/**
	 * Link to Messaging Notification tutorial.
	 *
	 * @since BuddyBoss 2.1.4
	 * @deprecated BuddyBoss 3.0.0 Tutorial link is now in the side panel help_url in Settings 2.0.
	 */
	function bboss_messaging_notifications_tutorial() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'Settings 2.0 Messages feature (messaging_notifications side panel help_url)' );
	}
}

if ( ! function_exists( 'bboss_admin_setting_callback_messaging_notification_fields' ) ) {
	/**
	 * Callback for rendering the hide/delay messaging notification fields.
	 *
	 * @since BuddyBoss 2.1.4
	 * @deprecated BuddyBoss 3.0.0 Replaced by Settings 2.0 fields in bboss_admin_settings_register_messages_feature().
	 */
	function bboss_admin_setting_callback_messaging_notification_fields() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'Settings 2.0 Messages feature (messaging_notifications panel fields)' );
	}
}

// ──────────────────────────────────────────────────────────────────────────────
// Messages Settings 2.0 deprecated hook compatibility.
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Fire the legacy `bp_admin_setting_messages_register_fields` hook after
 * Settings 2.0 finishes registering messages fields.
 *
 * The original hook passed a `BP_Admin_Setting_Messages` instance. Settings 2.0
 * no longer uses that class, so a no-op stub is passed to satisfy callbacks
 * (e.g. Pro access-control) that call add_section()/add_field() on the argument.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss 3.0.0 Use {@see 'bb_messages_after_register_settings_fields'} instead.
 */
add_action(
	'bb_messages_after_register_settings_fields',
	static function () {
		do_action_deprecated(
			'bp_admin_setting_messages_register_fields',
			array(
				new class() {
					/**
					 * No-op stub for BBoss_Admin_Setting_tab::add_section().
					 *
					 * @param mixed ...$args Ignored.
					 */
					public function add_section( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found

					/**
					 * No-op stub for BBoss_Admin_Setting_tab::add_field().
					 *
					 * @param mixed ...$args Ignored.
					 */
					public function add_field( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
				},
			),
			'BuddyBoss 3.0.0',
			'bb_messages_after_register_settings_fields'
		);
	}
);

// ──────────────────────────────────────────────────────────────────────────────
// Registration Settings 2.0 deprecated hook compatibility.
// Registration migrated to Settings 2.0 — legacy BP_Admin_Setting_Registration class removed.

/**
 * Fire the legacy `bb_admin_setting_general_registration_fields` hook after
 * Settings 2.0 finishes registering registration fields.
 *
 * @since BuddyBoss 2.6.30
 * @deprecated BuddyBoss 3.0.0 Use {@see 'bb_registration_after_general_settings_fields'} instead.
 */
add_action(
	'bb_registration_after_register_settings_fields',
	static function () {
		do_action_deprecated(
			'bb_admin_setting_general_registration_fields',
			array(
				new class() {
					public function add_section( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
					public function add_field( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
				},
			),
			'BuddyBoss 3.0.0',
			'bb_registration_after_general_settings_fields',
			__( 'Registration fields are now registered via bb_register_feature_field() in Settings 2.0.', 'buddyboss' )
		);
	}
);

/**
 * Fire the legacy `bp_admin_setting_registration_register_fields` hook after
 * Settings 2.0 finishes registering registration fields.
 *
 * The original hook passed a `BP_Admin_Setting_Registration` instance. Settings 2.0
 * no longer uses that class, so a no-op stub is passed to satisfy callbacks
 * that call add_section()/add_field() on the argument.
 *
 * @since BuddyPress 1.6.0
 * @deprecated BuddyBoss 3.0.0 Use {@see 'bb_register_feature_field'} instead.
 */
add_action(
	'bb_registration_after_register_settings_fields',
	static function () {
		do_action_deprecated(
			'bp_admin_setting_registration_register_fields',
			array(
				// phpcs:ignore PHPCompatibility.Classes.NewAnonymousClasses.Found -- PHP 7.4+ required.
				new class() {
					/**
					 * No-op stub for BBoss_Admin_Setting_tab::add_section().
					 *
					 * @param mixed ...$args Ignored.
					 */
					public function add_section( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found

					/**
					 * No-op stub for BBoss_Admin_Setting_tab::add_field().
					 *
					 * @param mixed ...$args Ignored.
					 */
					public function add_field( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
				},
			),
			'BuddyBoss 3.0.0',
			'bb_register_feature_field'
		);
	}
);

// ──────────────────────────────────────────────────────────────────────────────
// Media Settings 2.0 deprecated hook compatibility.
// Media is a "super-feature" wrapping bp-media (photos), bp-video, bp-document.
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Fire the legacy `bp_admin_setting_media_register_fields` hook after
 * Settings 2.0 finishes registering media fields.
 *
 * The original hook passed a `BP_Admin_Setting_Media` instance. Settings 2.0
 * no longer uses that class, so a no-op stub is passed to satisfy callbacks
 * (e.g. Pro access-control) that call add_section()/add_field() on the argument.
 *
 * @since BuddyBoss 1.2.6
 * @deprecated BuddyBoss 3.0.0 Use {@see 'bb_media_after_register_settings_fields'} instead.
 */
add_action(
	'bb_media_after_register_settings_fields',
	static function () {
		do_action_deprecated(
			'bp_admin_setting_media_register_fields',
			array(
				new class() {
					/**
					 * No-op stub for BBoss_Admin_Setting_tab::add_section().
					 *
					 * @param mixed ...$args Ignored.
					 */
					public function add_section( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found

					/**
					 * No-op stub for BBoss_Admin_Setting_tab::add_field().
					 *
					 * @param mixed ...$args Ignored.
					 */
					public function add_field( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
				},
			),
			'BuddyBoss 3.0.0',
			'bb_media_after_register_settings_fields'
		);
	}
);

/**
 * Fire the legacy `bp_admin_setting_video_register_fields` hook after
 * Settings 2.0 finishes registering media fields.
 *
 * The original hook passed a `BP_Admin_Setting_Video` instance. Settings 2.0
 * no longer uses that class, so a no-op stub is passed to satisfy callbacks
 * that call add_section()/add_field() on the argument.
 *
 * @since BuddyBoss 1.7.0
 * @deprecated BuddyBoss 3.0.0 Use {@see 'bb_media_after_register_settings_fields'} instead.
 */
add_action(
	'bb_media_after_register_settings_fields',
	static function () {
		do_action_deprecated(
			'bp_admin_setting_video_register_fields',
			array(
				new class() {
					/**
					 * No-op stub for BBoss_Admin_Setting_tab::add_section().
					 *
					 * @param mixed ...$args Ignored.
					 */
					public function add_section( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found

					/**
					 * No-op stub for BBoss_Admin_Setting_tab::add_field().
					 *
					 * @param mixed ...$args Ignored.
					 */
					public function add_field( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
				},
			),
			'BuddyBoss 3.0.0',
			'bb_media_after_register_settings_fields'
		);
	}
);

/**
 * Fire the legacy `bp_admin_setting_document_register_fields` hook after
 * Settings 2.0 finishes registering media fields.
 *
 * The original hook passed a `BP_Admin_Setting_Document` instance. Settings 2.0
 * no longer uses that class, so a no-op stub is passed to satisfy callbacks
 * that call add_section()/add_field() on the argument.
 *
 * @since BuddyBoss 1.2.6
 * @deprecated BuddyBoss 3.0.0 Use {@see 'bb_media_after_register_settings_fields'} instead.
 */
add_action(
	'bb_media_after_register_settings_fields',
	static function () {
		do_action_deprecated(
			'bp_admin_setting_document_register_fields',
			array(
				new class() {
					/**
					 * No-op stub for BBoss_Admin_Setting_tab::add_section().
					 *
					 * @param mixed ...$args Ignored.
					 */
					public function add_section( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found

					/**
					 * No-op stub for BBoss_Admin_Setting_tab::add_field().
					 *
					 * @param mixed ...$args Ignored.
					 */
					public function add_field( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
				},
			),
			'BuddyBoss 3.0.0',
			'bb_media_after_register_settings_fields'
		);
	}
);

// ──────────────────────────────────────────────────────────────────────────────
// Media / Video / Document legacy settings filter bridges.
// These apply_filters hooks were used by legacy getter functions in
// bboss-media-settings.php. Plugins adding custom sections/fields should now use
// bb_register_feature_section() and bb_register_feature_field().
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Fire deprecated media settings filter hooks after media fields are registered.
 *
 * @since BuddyBoss 3.0.0
 */
function bboss_deprecated_media_settings_filter_hooks() {

	/**
	 * Deprecated: bp_media_get_settings_sections.
	 *
	 * Legacy filter on the array of media (photos) setting sections.
	 *
	 * @since      BuddyBoss 1.0.0
	 * @deprecated BuddyBoss 3.0.0 Use {@see bboss_register_feature_section()} instead.
	 */
	apply_filters_deprecated(
		'bp_media_get_settings_sections',
		array( array() ),
		'BuddyBoss 3.0.0',
		'bb_register_feature_section()'
	);

	/**
	 * Deprecated: bp_media_get_settings_fields.
	 *
	 * Legacy filter on the full array of all media (photos) settings fields.
	 *
	 * @since      BuddyBoss 1.0.0
	 * @deprecated BuddyBoss 3.0.0 Use {@see bboss_register_feature_field()} instead.
	 */
	apply_filters_deprecated(
		'bp_media_get_settings_fields',
		array( array() ),
		'BuddyBoss 3.0.0',
		'bb_register_feature_field()'
	);

	/**
	 * Deprecated: bp_media_get_settings_fields_for_section.
	 *
	 * Legacy filter on fields for a specific media section ID.
	 *
	 * @since      BuddyBoss 1.0.0
	 * @deprecated BuddyBoss 3.0.0 Use {@see bboss_register_feature_field()} instead.
	 */
	apply_filters_deprecated(
		'bp_media_get_settings_fields_for_section',
		array( array(), '' ),
		'BuddyBoss 3.0.0',
		'bb_register_feature_field()'
	);

	/**
	 * Deprecated: bp_document_get_settings_sections.
	 *
	 * Legacy filter on the array of document setting sections.
	 *
	 * @since      BuddyBoss 1.0.0
	 * @deprecated BuddyBoss 3.0.0 Use {@see bboss_register_feature_section()} instead.
	 */
	apply_filters_deprecated(
		'bp_document_get_settings_sections',
		array( array() ),
		'BuddyBoss 3.0.0',
		'bb_register_feature_section()'
	);

	/**
	 * Deprecated: bp_document_get_settings_fields.
	 *
	 * Legacy filter on the full array of document settings fields.
	 *
	 * @since      BuddyBoss 1.0.0
	 * @deprecated BuddyBoss 3.0.0 Use {@see bboss_register_feature_field()} instead.
	 */
	apply_filters_deprecated(
		'bp_document_get_settings_fields',
		array( array() ),
		'BuddyBoss 3.0.0',
		'bb_register_feature_field()'
	);

	/**
	 * Deprecated: bp_document_get_settings_fields_for_section.
	 *
	 * Legacy filter on fields for a specific document section ID.
	 *
	 * @since      BuddyBoss 1.0.0
	 * @deprecated BuddyBoss 3.0.0 Use {@see bboss_register_feature_field()} instead.
	 */
	apply_filters_deprecated(
		'bp_document_get_settings_fields_for_section',
		array( array(), '' ),
		'BuddyBoss 3.0.0',
		'bb_register_feature_field()'
	);

	/**
	 * Deprecated: bp_video_get_settings_sections.
	 *
	 * Legacy filter on the array of video setting sections.
	 *
	 * @since      BuddyBoss 1.7.0
	 * @deprecated BuddyBoss 3.0.0 Use {@see bboss_register_feature_section()} instead.
	 */
	apply_filters_deprecated(
		'bp_video_get_settings_sections',
		array( array() ),
		'BuddyBoss 3.0.0',
		'bb_register_feature_section()'
	);

	/**
	 * Deprecated: bp_video_get_settings_fields.
	 *
	 * Legacy filter on the full array of video settings fields.
	 *
	 * @since      BuddyBoss 1.7.0
	 * @deprecated BuddyBoss 3.0.0 Use {@see bboss_register_feature_field()} instead.
	 */
	apply_filters_deprecated(
		'bp_video_get_settings_fields',
		array( array() ),
		'BuddyBoss 3.0.0',
		'bb_register_feature_field()'
	);

	/**
	 * Deprecated: bp_video_get_settings_fields_for_section.
	 *
	 * Legacy filter on fields for a specific video section ID.
	 *
	 * @since      BuddyBoss 1.7.0
	 * @deprecated BuddyBoss 3.0.0 Use {@see bboss_register_feature_field()} instead.
	 */
	apply_filters_deprecated(
		'bp_video_get_settings_fields_for_section',
		array( array(), '' ),
		'BuddyBoss 3.0.0',
		'bb_register_feature_field()'
	);
}

add_action( 'bb_media_after_register_settings_fields', 'bb_deprecated_media_settings_filter_hooks' );

// ──────────────────────────────────────────────────────────────────────────────
// Media settings save hooks (backward-compatible with Settings 1.0 tabs).
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Fire deprecated legacy setting save hooks for media backward compatibility.
 *
 * Legacy Settings 1.0 fires do_action('bp_admin_tab_setting_save', $tab_name)
 * and do_action('bp_admin_tab_setting_saved', $tab_name) when any settings tab
 * is saved. This bridge ensures those hooks still fire when media settings
 * are saved via Settings 2.0.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param string $feature_id Feature ID.
 * @param array  $settings   Full submitted settings.
 * @param array  $saved      Keys and values saved by core.
 */
function bboss_media_fire_deprecated_save_hooks( $feature_id, $settings, $saved ) {
	if ( 'media' !== $feature_id ) {
		return;
	}

	/**
	 * Fires when media settings are saved.
	 *
	 * @since      BuddyBoss 1.0.0
	 *
	 * @param string $tab_name The tab name.
	 *
	 * @deprecated BuddyBoss 3.0.0 Use {@see 'bb_admin_save_feature_settings_after'} with feature_id='media'.
	 */
	do_action_deprecated(
		'bp_admin_tab_setting_save',
		array( 'bp-media' ),
		'BuddyBoss 3.0.0',
		'bb_admin_save_feature_settings_after'
	);

	/**
	 * Fires after media settings have been saved.
	 *
	 * @since      BuddyBoss 1.0.0
	 *
	 * @param string $tab_name The tab name.
	 *
	 * @deprecated BuddyBoss 3.0.0 Use {@see 'bb_admin_save_feature_settings_after'} with feature_id='media'.
	 */
	do_action_deprecated(
		'bp_admin_tab_setting_saved',
		array( 'bp-media' ),
		'BuddyBoss 3.0.0',
		'bb_admin_save_feature_settings_after'
	);

	// Fire for video tab.
	if ( bboss_is_active( 'video' ) ) {

		/** This action is documented above. */
		do_action_deprecated(
			'bp_admin_tab_setting_save',
			array( 'bp-video' ),
			'BuddyBoss 3.0.0',
			'bb_admin_save_feature_settings_after'
		);

		/** This action is documented above. */
		do_action_deprecated(
			'bp_admin_tab_setting_saved',
			array( 'bp-video' ),
			'BuddyBoss 3.0.0',
			'bb_admin_save_feature_settings_after'
		);
	}

	// Fire for document tab.
	if ( bboss_is_active( 'document' ) ) {

		/** This action is documented above. */
		do_action_deprecated(
			'bp_admin_tab_setting_save',
			array( 'bp-document' ),
			'BuddyBoss 3.0.0',
			'bb_admin_save_feature_settings_after'
		);

		/** This action is documented above. */
		do_action_deprecated(
			'bp_admin_tab_setting_saved',
			array( 'bp-document' ),
			'BuddyBoss 3.0.0',
			'bb_admin_save_feature_settings_after'
		);
	}
}

add_action( 'bb_admin_save_feature_settings_after', 'bb_media_fire_deprecated_save_hooks', 99, 3 );

// ──────────────────────────────────────────────────────────────────────────────
// Groups Settings 2.0 deprecated hook compatibility.
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Fire the legacy `bp_admin_setting_groups_register_fields` hook after
 * Settings 2.0 finishes registering groups fields.
 *
 * The original hook passed a BP_Admin_Setting_Groups instance. Settings 2.0
 * no longer uses that class, so a no-op stub is passed to satisfy callbacks
 * that call add_section()/add_field() on the argument.
 *
 * @since BuddyBoss 1.2.6
 * @deprecated BuddyBoss 3.0.0 Use {@see 'bb_groups_after_register_settings_fields'} and bboss_register_feature_field() instead.
 */
add_action(
	'bb_groups_after_register_settings_fields',
	static function () {
		do_action_deprecated(
			'bp_admin_setting_groups_register_fields',
			array(
				new class() {
					/**
					 * No-op stub for BBoss_Admin_Setting_tab::add_section().
					 *
					 * @param mixed ...$args Ignored.
					 */
					public function add_section( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found

					/**
					 * No-op stub for BBoss_Admin_Setting_tab::add_field().
					 *
					 * @param mixed ...$args Ignored.
					 */
					public function add_field( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
				},
			),
			'BuddyBoss 3.0.0',
			'bb_groups_after_register_settings_fields'
		);
	}
);

// ──────────────────────────────────────────────────────────────────────────────
// Groups admin tabs (moved from bboss-core-admin-functions.php).
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Output the tabs in the Groups admin area.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss 3.0.0 Use {@see bboss_register_side_panel()} to register panels in Settings 2.0.
 *
 * @param string $active_tab Name of the tab that is active. Optional.
 */
function bboss_core_admin_groups_tabs( $active_tab = '' ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'bb_register_side_panel()' );

	$tabs_html    = '';
	$idle_class   = 'nav-tab';
	$active_class = 'nav-tab nav-tab-active';

	/**
	 * Filters the admin tabs to be displayed.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param array $value Array of tabs to output to the admin area.
	 */
	$tabs = apply_filters( 'bp_core_admin_groups_tabs', bboss_core_get_groups_admin_tabs( $active_tab ) );

	// Loop through tabs and build navigation.
	foreach ( array_values( $tabs ) as $tab_data ) {
		$is_current = (bool) ( $tab_data['name'] == $active_tab );
		$tab_class  = $is_current ? $tab_data['class'] . ' ' . $active_class : $tab_data['class'] . ' ' . $idle_class;
		$tabs_html .= '<a href="' . esc_url( $tab_data['href'] ) . '" class="' . esc_attr( $tab_class ) . '">' . esc_html( $tab_data['name'] ) . '</a>';
	}

	echo wp_kses_post( $tabs_html );

	/**
	 * Fires after the output of tabs for the admin area.
	 *
	 * @since BuddyBoss 1.0.0
	 * @deprecated BuddyBoss 3.0.0 No replacement in Settings 2.0. Use {@see bboss_register_side_panel()}.
	 */
	do_action_deprecated( 'bp_admin_groups_tabs', array(), 'BuddyBoss 3.0.0', 'bb_register_side_panel()' );
}

/**
 * Register tabs for the BuddyBoss > Groups screens.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss 3.0.0 Use {@see bboss_register_side_panel()} to register panels in Settings 2.0.
 *
 * @param string $active_tab Name of the tab that is active. Optional.
 *
 * @return array
 */
function bboss_core_get_groups_admin_tabs( $active_tab = '' ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'bb_register_side_panel()' );

	$tabs = array();

	$tabs[] = array(
		'href'  => bboss_get_admin_url( add_query_arg( array( 'page' => 'bp-groups' ), 'admin.php' ) ),
		'name'  => __( 'All Groups', 'buddyboss' ),
		'class' => 'bp-all-groups',
	);

	/**
	 * Filters the tab data used in our wp-admin screens.
	 *
	 * @since BuddyBoss 1.0.0
	 * @deprecated BuddyBoss 3.0.0 Use {@see bboss_register_side_panel()} to register panels in Settings 2.0.
	 *
	 * @param array $tabs Tab data.
	 */
	return apply_filters_deprecated(
		'bp_core_get_groups_admin_tabs',
		array( $tabs ),
		'BuddyBoss 3.0.0',
		'bb_register_side_panel()'
	);
}

// ──────────────────────────────────────────────────────────────────────────────
// Legacy Groups admin page functions (moved from bboss-groups-admin.php).
// Settings 2.0 replaces: listing → GroupsListScreen.js,
// edit/create → GroupEditModal.js, delete → React modal.
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Set up the Groups admin page.
 *
 * @since BuddyPress 1.7.0
 * @deprecated BuddyBoss 3.0.0 Groups admin is now handled by Settings 2.0.
 */
function bboss_groups_admin_load() {
	_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'Settings 2.0 Groups admin' );
}

/**
 * Handle save/update of screen options for the Groups component admin screen.
 *
 * @since BuddyPress 1.7.0
 * @deprecated BuddyBoss 3.0.0 Groups admin is now handled by Settings 2.0.
 *
 * @param string $value     Will always be false unless another plugin filters it first.
 * @param string $option    Screen option name.
 * @param string $new_value Screen option form value.
 *
 * @return string|int Option value. False to abandon update.
 */
function bboss_groups_admin_screen_options( $value, $option, $new_value ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'Settings 2.0 Groups admin' );

	if ( 'buddyboss_page_bp_groups_per_page' !== $option && 'buddyboss_page_bp_groups_network_per_page' !== $option ) {
		return $value;
	}

	$new_value = (int) $new_value;
	if ( $new_value < 1 || $new_value > 999 ) {
		return $value;
	}

	return $new_value;
}

/**
 * Select the appropriate Groups admin screen, and output it.
 *
 * @since BuddyPress 1.7.0
 * @deprecated BuddyBoss 3.0.0 Groups admin is now handled by Settings 2.0.
 */
function bboss_groups_admin() {
	_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'Settings 2.0 Groups admin' );

	// Fallback: if the load- redirect didn't fire, show a link to Settings 2.0 instead of a blank page.
	$settings_url = function_exists( 'bboss_get_settings_url' ) ? add_query_arg( 'tab', 'groups', bboss_get_settings_url() ) : '';
	if ( ! empty( $settings_url ) ) {
		printf(
			'<div class="wrap"><h1>%s</h1><p>%s <a href="%s">%s</a></p></div>',
			esc_html__( 'Groups', 'buddyboss' ),
			esc_html__( 'Groups admin has moved.', 'buddyboss' ),
			esc_url( $settings_url ),
			esc_html__( 'Go to Groups Settings', 'buddyboss' )
		);
	}
}

/**
 * Display the single groups edit screen.
 *
 * @since BuddyPress 1.7.0
 * @deprecated BuddyBoss 3.0.0 Use Settings 2.0 Group Edit Modal.
 */
function bboss_groups_admin_edit() {
	_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'Settings 2.0 Group Edit Modal' );
}

/**
 * Display the single groups create screen.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss 3.0.0 Use Settings 2.0 Group Create Modal.
 */
function bboss_groups_admin_create() {
	_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'Settings 2.0 Group Create Modal' );
}

/**
 * Process the data of newly created group from the backend.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss 3.0.0 Use Settings 2.0 {@see BBoss_Admin_Groups_Ajax::create_group()}.
 */
function bboss_process_create_group_admin() {
	_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'BB_Admin_Groups_Ajax::create_group()' );
}

/**
 * Display the Group delete confirmation screen.
 *
 * @since BuddyPress 1.7.0
 * @deprecated BuddyBoss 3.0.0 Use Settings 2.0 Group Delete Modal.
 */
function bboss_groups_admin_delete() {
	_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'Settings 2.0 Group Delete Modal' );
}

/**
 * Display the Groups admin index screen.
 *
 * @since BuddyPress 1.7.0
 * @deprecated BuddyBoss 3.0.0 Use Settings 2.0 {@see GroupsListScreen.js}.
 */
function bboss_groups_admin_index() {
	_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'Settings 2.0 GroupsListScreen' );
}

/**
 * Markup for the single group's Settings metabox.
 *
 * @since BuddyPress 1.7.0
 * @deprecated BuddyBoss 3.0.0 Use Settings 2.0 Group Edit Modal (Permissions tab).
 *
 * @param object $item Information about the current group.
 */
function bboss_groups_admin_edit_metabox_settings( $item ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'Settings 2.0 Group Edit Modal' );
}

/**
 * Markup for the single group's Group Hierarchy metabox.
 *
 * @since BuddyPress 1.7.0
 * @deprecated BuddyBoss 3.0.0 Use Settings 2.0 Group Edit Modal (Integrations tab).
 *
 * @param object $item Information about the current group.
 */
function bboss_groups_admin_edit_metabox_group_parent( $item ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'Settings 2.0 Group Edit Modal' );
}

/**
 * Output the markup for a single group's Add New Members metabox.
 *
 * @since BuddyPress 1.7.0
 * @deprecated BuddyBoss 3.0.0 Use Settings 2.0 Group Edit Modal (Members tab).
 *
 * @param BBoss_Groups_Group $item The BBoss_Groups_Group object for the current group.
 */
function bboss_groups_admin_edit_metabox_add_new_members( $item ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'Settings 2.0 Group Edit Modal' );
}

/**
 * Renders the Members metabox on single group pages.
 *
 * @since BuddyPress 1.7.0
 * @deprecated BuddyBoss 3.0.0 Use Settings 2.0 Group Edit Modal (Members tab).
 *
 * @param BBoss_Groups_Group $item The BBoss_Groups_Group object for the current group.
 */
function bboss_groups_admin_edit_metabox_members( $item ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'Settings 2.0 Group Edit Modal' );
}

/**
 * Renders the Status metabox for the Groups admin edit screen.
 *
 * @since BuddyPress 1.7.0
 * @deprecated BuddyBoss 3.0.0 Use Settings 2.0 Group Edit Modal.
 *
 * @param object $item Information about the currently displayed group.
 */
function bboss_groups_admin_edit_metabox_status( $item ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'Settings 2.0 Group Edit Modal' );
}

/**
 * Create pagination links out of a BBoss_Group_Member_Query.
 *
 * @since BuddyPress 1.8.0
 * @deprecated BuddyBoss 3.0.0 Use Settings 2.0 Group Edit Modal (Members tab) with AJAX pagination.
 *
 * @param BBoss_Group_Member_Query $query       A BBoss_Group_Member_Query object.
 * @param string                $member_type member|mod|admin|banned.
 *
 * @return string Empty string.
 */
function bboss_groups_admin_create_pagination_links( $query, $member_type ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'Settings 2.0 Group Edit Modal' );

	return '';
}

/**
 * Get a set of usernames corresponding to a set of user IDs.
 *
 * @since BuddyPress 1.7.0
 * @deprecated BuddyBoss 3.0.0 Groups admin is now handled by Settings 2.0.
 *
 * @param array $user_ids Array of user IDs.
 *
 * @return array Array of user_logins corresponding to $user_ids.
 */
function bboss_groups_admin_get_usernames_from_ids( $user_ids = array() ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'Settings 2.0 Groups admin' );

	$usernames = array();
	$users     = new WP_User_Query(
		array(
			'blog_id' => 0,
			'include' => $user_ids,
		)
	);

	foreach ( (array) $users->results as $user ) {
		$usernames[] = $user->user_login;
	}

	return $usernames;
}

/**
 * AJAX handler for group member autocomplete requests.
 *
 * @since BuddyPress 1.7.0
 * @deprecated BuddyBoss 3.0.0 Use Settings 2.0 {@see BBoss_Admin_Groups_Ajax::member_autocomplete()}.
 */
function bboss_groups_admin_autocomplete_handler() {
	_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'BB_Admin_Groups_Ajax::member_autocomplete()' );

	wp_die( -1 );
}

// Register deprecated hooks so third-party code that triggers them gets a deprecation notice.
add_action( 'wp_ajax_bp_group_admin_member_autocomplete', 'bp_groups_admin_autocomplete_handler' );
add_action( 'admin_post_bp_create_group_admin', 'bp_process_create_group_admin' );

// Note: bp_groups_list_table_get_views is fired via do_action_deprecated() inside
// BB_Admin_Groups_Ajax::get_groups() each time the admin groups list is loaded.
// It is deprecated in favour of the 'bb_admin_groups_list_views' filter.
// See: src/bboss-core/admin/classes/class-bboss-admin-groups-ajax.php

// Note: bp_groups_admin_comment_row_actions is no longer fired.
// Row actions are now handled natively by the Settings 2.0 React UI.
// No consumers found in Platform or Pro — safe to drop without apply_filters_deprecated().

// Note: bp_groups_admin_row_class is no longer fired.
// Settings 2.0 renders group rows via React; CSS classes are managed client-side.

// ──────────────────────────────────────────────────────────────────────────────
// Legacy Group Type cache clearing (replaced by BB_Admin_Groups_Ajax::bb_clear_group_type_cache()).
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Clear the group_type cache when group type post is updated.
 *
 * @since BuddyBoss 2.0.0
 * @deprecated BuddyBoss 3.0.0 Use {@see BBoss_Admin_Groups_Ajax::bb_clear_group_type_cache()}.
 *
 * @param int $post_id Post ID.
 */
function bboss_groups_clear_group_type_cache_on_update( $post_id ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'BB_Admin_Groups_Ajax::bb_clear_group_type_cache()' );
}

// ──────────────────────────────────────────────────────────────────────────────
// Legacy Group Type CPT functions (replaced by Settings 2.0 Group Types AJAX).
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Save group type post meta box data.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss 3.0.0 Use Settings 2.0 {@see BBoss_Admin_Groups_Ajax::create_group_type()} / {@see BBoss_Admin_Groups_Ajax::update_group_type()}.
 *
 * @param int $post_id Post ID of the group type.
 */
function bboss_save_group_type_post_meta_box_data( $post_id ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'BB_Admin_Groups_Ajax::create_group_type() / BB_Admin_Groups_Ajax::update_group_type()' );
}

/**
 * Save group type role labels post meta box data.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss 3.0.0 Use Settings 2.0 {@see BBoss_Admin_Groups_Ajax::create_group_type()} / {@see BBoss_Admin_Groups_Ajax::update_group_type()}.
 *
 * @param int $post_id Post ID of the group type.
 */
function bboss_save_group_type_role_labels_post_meta_box_data( $post_id ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'BB_Admin_Groups_Ajax::create_group_type() / BB_Admin_Groups_Ajax::update_group_type()' );
}

/**
 * Register actions and filters for group types admin.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss 3.0.0 Group types are now managed via Settings 2.0.
 */
function bboss_register_group_type_sections_filters_actions() {
	_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'Settings 2.0 Group Types admin' );
}

/**
 * Process bulk group type changes from admin dropdown.
 *
 * @since BuddyPress 2.7.0
 * @deprecated BuddyBoss 3.0.0 Use Settings 2.0 {@see BBoss_Admin_Groups_Ajax::bulk_action()}.
 *
 * @param string $doaction Current bulk action being processed.
 */
function bboss_groups_admin_process_group_type_bulk_changes( $doaction ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'BB_Admin_Groups_Ajax::bulk_action()' );
}

/**
 * Display admin notice upon group type bulk update.
 *
 * @since BuddyPress 2.7.0
 * @deprecated BuddyBoss 3.0.0 Use Settings 2.0 toast notifications.
 */
function bboss_groups_admin_groups_type_change_notice() {
	_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'Settings 2.0 Groups admin' );
}

/**
 * Add custom columns to group type post list table.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss 3.0.0 Group types are now listed via Settings 2.0.
 *
 * @param array $columns Existing columns.
 *
 * @return array
 */
function bboss_group_type_add_column( $columns ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'Settings 2.0 Group Types admin' );

	return $columns;
}

/**
 * Display data for group type columns in list table.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss 3.0.0 Group types are now listed via Settings 2.0.
 *
 * @param string $column  Column name.
 * @param int    $post_id Post ID.
 */
function bboss_group_type_show_data( $column, $post_id ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'Settings 2.0 Group Types admin' );
}

/**
 * Make group type list table columns sortable.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss 3.0.0 Group types are now listed via Settings 2.0.
 *
 * @param array $columns Sortable columns.
 *
 * @return array
 */
function bboss_group_type_add_sortable_columns( $columns ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'Settings 2.0 Group Types admin' );

	return $columns;
}

/**
 * Hide quick edit link from group type post row actions.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss 3.0.0 Group types are now managed via Settings 2.0 modals.
 *
 * @param array   $actions Row actions.
 * @param WP_Post $post    Post object.
 *
 * @return array
 */
function bboss_group_type_hide_quick_edit( $actions, $post ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'Settings 2.0 Group Types admin' );

	return $actions;
}

/**
 * Register meta boxes for group type post type.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss 3.0.0 Group type editing is now handled by Settings 2.0 modals.
 */
function bboss_group_type_custom_meta_boxes() {
	_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'Settings 2.0 Group Types admin' );
}

/**
 * Generate group type label meta box.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss 3.0.0 Use Settings 2.0 Group Type modal.
 *
 * @param WP_Post $post Post object.
 */
function bboss_group_type_labels_meta_box( $post ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'Settings 2.0 Group Types admin' );
}

/**
 * Generate group type permissions meta box.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss 3.0.0 Use Settings 2.0 Group Type modal.
 *
 * @param WP_Post $post Post object.
 */
function bboss_group_type_permissions_meta_box( $post ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'Settings 2.0 Group Types admin' );
}

/**
 * Display label color selector metabox for group types.
 *
 * @since BuddyBoss 2.0.0
 * @deprecated BuddyBoss 3.0.0 Use Settings 2.0 Group Type modal.
 *
 * @param WP_Post $post Post object.
 */
function bboss_group_type_labelcolor_metabox( $post ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'Settings 2.0 Group Types admin' );
}

/**
 * Display shortcode metabox for group type admin edit.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss 3.0.0 Use Settings 2.0 Group Type modal.
 *
 * @param WP_Post $post Post object.
 */
function bboss_group_shortcode_meta_box( $post ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'Settings 2.0 Group Types admin' );
}

/**
 * Render Group Type metabox on the single group admin edit screen.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss 3.0.0 Use Settings 2.0 Group Edit Modal.
 *
 * @param BBoss_Groups_Group|null $group Group object.
 */
function bboss_groups_admin_edit_metabox_group_type( ?BBoss_Groups_Group $group = null ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'Settings 2.0 Group Edit Modal' );
}

/**
 * Process group type update from admin edit screen.
 *
 * Hooked to `bp_group_admin_edit_after`. Bails when legacy nonce is absent.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss 3.0.0 Use Settings 2.0 {@see BBoss_Admin_Groups_Ajax::save_group()}.
 *
 * @param int $group_id Group ID.
 */
function bboss_groups_process_group_type_update( $group_id ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'BB_Admin_Groups_Ajax::save_group()' );
}

/**
 * Output jQuery to highlight Groups menu when on Group Types CPT page.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss 3.0.0 Group Types are now managed via Settings 2.0.
 */
function bboss_group_type_show_correct_current_menu() {
	_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'Settings 2.0 Group Types admin' );
}

/**
 * Set correct menu parent for Group Types CPT screens.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss 3.0.0 Group Types are now managed via Settings 2.0.
 *
 * @param string $parent_file The parent file.
 *
 * @return string
 */
function bboss_group_type_set_platform_tab_submenu_active( $parent_file ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'Settings 2.0 Group Types admin' );

	return $parent_file;
}

/**
 * Output Groups admin tabs on the Group Types listing page.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss 3.0.0 Group Types are now managed via Settings 2.0.
 */
function bboss_groups_admin_group_type_listing_add_groups_tab() {
	_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'Settings 2.0 Group Types admin' );
}

/**
 * Add sorting filter for Group Types CPT list table.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss 3.0.0 Group Types are now managed via Settings 2.0.
 */
function bboss_group_type_add_request_filter() {
	_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'Settings 2.0 Group Types admin' );
}

/**
 * Sort Group Types CPT list table.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss 3.0.0 Group Types are now managed via Settings 2.0.
 *
 * @param array $qv Query vars.
 *
 * @return array
 */
function bboss_group_type_sort_items( $qv ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'Settings 2.0 Group Types admin' );

	return $qv;
}


// ──────────────────────────────────────────────────────────────────────────────
// Members / Connections settings hooks (moved to Settings 2.0).
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Fire deprecated xprofile register_fields hook for backward compatibility.
 *
 * Legacy Settings 1.0 passed a BBoss_Admin_Setting_tab instance that third-party
 * plugins called ->add_section() / ->add_field() on. Settings 2.0 uses
 * bboss_register_feature_field() instead. Third-party/Pro plugins should hook into
 * 'bb_members_after_register_settings_fields'.
 *
 * @since BuddyBoss 3.0.0
 */
function bboss_deprecated_xprofile_register_fields_hook() {
	if ( ! function_exists( 'bboss_register_feature' ) || ! bboss_is_active( 'xprofile' ) ) {
		return;
	}

	/**
	 * Fires to register xProfile tab settings fields and section.
	 *
	 * The original hook passed a BP_Admin_Setting_Xprofile instance. Settings 2.0
	 * no longer uses that class, so a no-op stub is passed to satisfy callbacks
	 * that call add_section()/add_field() on the argument.
	 *
	 * @since BuddyBoss 1.2.6
	 * @deprecated BuddyBoss 3.0.0 Use {@see 'bb_members_after_register_settings_fields'} hook with bboss_register_feature_field().
	 *
	 * @param object $deprecated No-op stub (was BBoss_Admin_Setting_tab instance).
	 */
	do_action_deprecated(
		'bp_admin_setting_xprofile_register_fields',
		array(
			new class() {
				/**
				 * No-op stub for BBoss_Admin_Setting_tab::add_section().
				 *
				 * @param mixed ...$args Ignored.
				 */
				public function add_section( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found

				/**
				 * No-op stub for BBoss_Admin_Setting_tab::add_field().
				 *
				 * @param mixed ...$args Ignored.
				 */
				public function add_field( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
			},
		),
		'BuddyBoss 3.0.0',
		'bb_members_after_register_settings_fields'
	);
}

add_action( 'bb_members_after_register_settings_fields', 'bb_deprecated_xprofile_register_fields_hook', 0 );

/**
 * Fire deprecated friends register_fields hook for backward compatibility.
 *
 * @since BuddyBoss 3.0.0
 */
function bboss_deprecated_friends_register_fields_hook() {
	if ( ! function_exists( 'bboss_register_feature' ) || ! bboss_is_active( 'friends' ) ) {
		return;
	}

	/**
	 * Fires to register Friends tab settings fields and section.
	 *
	 * The original hook passed a BP_Admin_Setting_Friends instance. Settings 2.0
	 * no longer uses that class, so a no-op stub is passed to satisfy callbacks
	 * that call add_section()/add_field() on the argument.
	 *
	 * @since BuddyBoss 1.2.6
	 * @deprecated BuddyBoss 3.0.0 Use {@see 'bb_members_after_register_settings_fields'} hook with bboss_register_feature_field().
	 *
	 * @param object $deprecated No-op stub (was BBoss_Admin_Setting_tab instance).
	 */
	do_action_deprecated(
		'bp_admin_setting_friends_register_fields',
		array(
			new class() {
				/**
				 * No-op stub for BBoss_Admin_Setting_tab::add_section().
				 *
				 * @param mixed ...$args Ignored.
				 */
				public function add_section( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found

				/**
				 * No-op stub for BBoss_Admin_Setting_tab::add_field().
				 *
				 * @param mixed ...$args Ignored.
				 */
				public function add_field( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
			},
		),
		'BuddyBoss 3.0.0',
		'bb_members_after_register_settings_fields'
	);
}

add_action( 'bb_members_after_register_settings_fields', 'bb_deprecated_friends_register_fields_hook', 0 );

// ──────────────────────────────────────────────────────────────────────────────
// Members settings save hooks (backward-compatible with Settings 1.0 tabs).
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Fire deprecated legacy setting save hooks for backward compatibility.
 *
 * Legacy Settings 1.0 fires do_action('bp_admin_tab_setting_save', $tab_name)
 * when any settings tab is saved. Third-party plugins may hook into this for
 * 'bp-xprofile' or 'bp-friends' tab names. This bridge ensures those hooks
 * still fire when members settings are saved via Settings 2.0.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param string $feature_id Feature ID.
 * @param array  $settings   Full submitted settings.
 * @param array  $saved      Keys and values saved by core.
 */
function bboss_members_fire_deprecated_save_hooks( $feature_id, $settings, $saved ) {
	if ( 'members' !== $feature_id ) {
		return;
	}

	/**
	 * Fires when xprofile settings are saved.
	 *
	 * @since BuddyBoss 1.0.0
	 * @deprecated BuddyBoss 3.0.0 Use {@see 'bb_admin_save_feature_settings_after'} with feature_id='members'.
	 *
	 * @param string $tab_name The tab name.
	 */
	do_action_deprecated(
		'bp_admin_tab_setting_save',
		array( 'bp-xprofile' ),
		'BuddyBoss 3.0.0',
		'bb_admin_save_feature_settings_after'
	);

	// Fire for friends tab if connection settings were included.
	if ( bboss_is_active( 'friends' ) ) {

		/**
		 * Fires when friends settings are saved.
		 *
		 * @since BuddyBoss 1.0.0
		 * @deprecated BuddyBoss 3.0.0 Use {@see 'bb_admin_save_feature_settings_after'} with feature_id='members'.
		 *
		 * @param string $tab_name The tab name.
		 */
		do_action_deprecated(
			'bp_admin_tab_setting_save',
			array( 'bp-friends' ),
			'BuddyBoss 3.0.0',
			'bb_admin_save_feature_settings_after'
		);
	}

	/**
	 * Fires after xprofile settings have been saved.
	 *
	 * @since BuddyBoss 1.0.0
	 * @deprecated BuddyBoss 3.0.0 Use {@see 'bb_admin_save_feature_settings_after'} with feature_id='members'.
	 *
	 * @param string $tab_name The tab name.
	 */
	do_action_deprecated(
		'bp_admin_tab_setting_saved',
		array( 'bp-xprofile' ),
		'BuddyBoss 3.0.0',
		'bb_admin_save_feature_settings_after'
	);

	if ( bboss_is_active( 'friends' ) ) {

		/**
		 * Fires after friends settings have been saved.
		 *
		 * @since BuddyBoss 1.0.0
		 * @deprecated BuddyBoss 3.0.0 Use {@see 'bb_admin_save_feature_settings_after'} with feature_id='members'.
		 *
		 * @param string $tab_name The tab name.
		 */
		do_action_deprecated(
			'bp_admin_tab_setting_saved',
			array( 'bp-friends' ),
			'BuddyBoss 3.0.0',
			'bb_admin_save_feature_settings_after'
		);
	}
}

add_action( 'bb_admin_save_feature_settings_after', 'bb_members_fire_deprecated_save_hooks', 99, 3 );

// ──────────────────────────────────────────────────────────────────────────────
// XProfile admin rendering hooks (replaced by Settings 2.0 React UI).
// These hooks only fired in the legacy wp-admin XProfile field editor.
// The data hooks (xprofile_group_before_save, xprofile_field_before_save, etc.)
// are preserved because they fire from within BP_XProfile_Group::save() and
// BP_XProfile_Field::save() which are still used by the AJAX handler.
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Register deprecation notices for legacy XProfile admin rendering hooks.
 *
 * These hooks were used to add custom UI in the legacy XProfile field editor
 * admin page. Since Settings 2.0 uses a React interface, these rendering hooks
 * no longer fire. Third-party plugins should extend the React UI instead.
 *
 * @since BuddyBoss 3.0.0
 */
function bboss_deprecated_xprofile_admin_rendering_hooks() {
	$deprecated_hooks = array(
		'xprofile_group_admin_after_description' => __( 'XProfile group admin after description', 'buddyboss' ),
		'xprofile_group_before_submitbox'        => __( 'XProfile group before submitbox', 'buddyboss' ),
		'xprofile_group_submitbox_start'         => __( 'XProfile group submitbox start', 'buddyboss' ),
		'xprofile_group_after_submitbox'         => __( 'XProfile group after submitbox', 'buddyboss' ),
		'xprofile_field_before_contentbox'       => __( 'XProfile field before contentbox', 'buddyboss' ),
		'xprofile_field_after_contentbox'        => __( 'XProfile field after contentbox', 'buddyboss' ),
		'xprofile_field_before_submitbox'        => __( 'XProfile field before submitbox', 'buddyboss' ),
		'xprofile_field_submitbox_start'         => __( 'XProfile field submitbox start', 'buddyboss' ),
		'xprofile_field_after_submitbox'         => __( 'XProfile field after submitbox', 'buddyboss' ),
		'xprofile_field_after_sidebarbox'        => __( 'XProfile field after sidebarbox', 'buddyboss' ),
		'xprofile_field_additional_options'      => __( 'XProfile field additional options', 'buddyboss' ),
		'xprofile_admin_field_name_legend'       => __( 'XProfile admin field name legend', 'buddyboss' ),
		'xprofile_admin_field_action'            => __( 'XProfile admin field action', 'buddyboss' ),
	);

	foreach ( $deprecated_hooks as $hook => $description ) {
		if ( has_action( $hook ) ) {
			_deprecated_hook(
				esc_html( $hook ),
				'BuddyBoss 3.0.0',
				'',
				sprintf(
					/* translators: %s: hook name */
					esc_html__( 'The %s hook is no longer fired in the Settings 2.0 React admin interface. Extend the React UI via custom JavaScript instead.', 'buddyboss' ),
					esc_html( $hook )
				)
			);
		}
	}
}

add_action( 'admin_init', 'bb_deprecated_xprofile_admin_rendering_hooks' );

/**
 * Register deprecation notices for legacy Profile admin tab hooks.
 *
 * The `bp_core_admin_users_tabs` and `bp_core_get_users_admin_tabs` filters
 * were used to add tabs to the legacy bp-profile-setup admin page. Since
 * Settings 2.0, Profile Fields, Profile Types, Profile Search, and Profile
 * Navigation are managed via the React admin interface.
 *
 * @since BuddyBoss 3.0.0
 */
function bboss_deprecated_profile_admin_tab_hooks() {
	$deprecated_filters = array(
		'bp_core_admin_users_tabs',
		'bp_core_get_users_admin_tabs',
	);

	foreach ( $deprecated_filters as $hook ) {
		if ( has_filter( $hook ) ) {
			_deprecated_hook(
				esc_html( $hook ),
				'BuddyBoss 3.0.0',
				'bb_register_side_panel()',
				sprintf(
					/* translators: %s: hook name */
					esc_html__( 'The %s filter is no longer used. Profile admin tabs are now managed via Settings 2.0 side panels.', 'buddyboss' ),
					esc_html( $hook )
				)
			);
		}
	}
}

add_action( 'admin_init', 'bb_deprecated_profile_admin_tab_hooks' );

/**
 * Backward-compatible stub for legacy Profile admin tab renderer.
 *
 * The admin tab bar for bp-profile-setup has been removed in Settings 2.0.
 * This stub prevents fatal errors if third-party code or legacy model class
 * `render_admin_form()` methods call the function.
 *
 * @since BuddyBoss 3.0.0
 * @deprecated BuddyBoss 3.0.0 Use Settings 2.0 side panels via bboss_register_side_panel().
 *
 * @param string $active_tab Active tab slug (ignored).
 */
function bboss_core_admin_users_tabs( $active_tab = '' ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'bb_register_side_panel()' );
}

/**
 * Backward-compatible stub for legacy Profile admin tabs data.
 *
 * @since BuddyBoss 3.0.0
 * @deprecated BuddyBoss 3.0.0 Use Settings 2.0 side panels via bboss_register_side_panel().
 *
 * @param string $active_tab Active tab slug (ignored).
 *
 * @return array Empty array.
 */
function bboss_core_get_users_admin_tabs( $active_tab = '' ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'bb_register_side_panel()' );

	return array();
}

/**
 * Backward-compatible stub for legacy field type dropdown renderer.
 *
 * Called from BBoss_XProfile_Field::render_admin_form(). No longer needed since
 * field type selection is handled by the Settings 2.0 React modal.
 *
 * @since BuddyBoss 3.0.0
 * @deprecated BuddyBoss 3.0.0
 *
 * @param string $select_field_type Currently selected field type.
 */
function bboss_xprofile_admin_form_field_types( $select_field_type = '' ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0' );
}

// ──────────────────────────────────────────────────────────────────────────────
// Forums Settings 2.0 deprecated hook compatibility.
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Fire the legacy `bp_admin_setting_forums_register_fields` hook after
 * Settings 2.0 finishes registering forum fields.
 *
 * The original hook passed a `BP_Admin_Setting_Forums` instance. Settings 2.0
 * no longer uses that class, so a no-op stub is passed to satisfy callbacks
 * that call add_section()/add_field() on the argument.
 *
 * @since BuddyBoss 1.2.6
 * @deprecated BuddyBoss 3.0.0 Use {@see 'bb_forums_after_register_settings_fields'} instead.
 */
add_action(
	'bb_forums_after_register_settings_fields',
	static function () {
		do_action_deprecated(
			'bp_admin_setting_forums_register_fields',
			array(
				new class() {
					/**
					 * No-op stub for BBoss_Admin_Setting_tab::add_section().
					 *
					 * @param mixed ...$args Ignored.
					 */
					public function add_section( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found

					/**
					 * No-op stub for BBoss_Admin_Setting_tab::add_field().
					 *
					 * @param mixed ...$args Ignored.
					 */
					public function add_field( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
				},
			),
			'BuddyBoss 3.0.0',
			'bb_forums_after_register_settings_fields'
		);
	}
);

/**
 * Fire the legacy `bp_admin_tab_setting_save` and `bp_admin_tab_setting_saved`
 * hooks when forums settings are saved via Settings 2.0.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss 3.0.0 Use Settings 2.0 per-field sanitize/validate callbacks.
 */
add_action(
	'bb_forums_after_save_settings',
	static function () {
		do_action_deprecated(
			'bp_admin_tab_setting_save',
			array( 'bp-forums' ),
			'BuddyBoss 3.0.0',
			'bb_forums_after_save_settings'
		);

		do_action_deprecated(
			'bp_admin_tab_setting_saved',
			array( 'bp-forums' ),
			'BuddyBoss 3.0.0',
			'bb_forums_after_save_settings'
		);
	}
);

/**
 * Fire the legacy `bbp_admin_get_settings_sections` filter after Settings 2.0
 * finishes registering forum fields.
 *
 * In legacy bbPress, this filter was applied inside bboss_bbp_admin_get_settings_sections()
 * allowing third-party plugins to add settings sections to the WordPress Settings API
 * pages. Settings 2.0 no longer calls that function for registration; this deprecation
 * wrapper notifies developers to use bboss_register_feature_section() instead.
 *
 * @since bbPress (r4001)
 * @deprecated BuddyBoss 3.0.0 Use {@see bboss_register_feature_section()} to register forum settings sections.
 */
add_action(
	'bb_forums_after_register_settings_fields',
	static function () {
		apply_filters_deprecated(
			'bbp_admin_get_settings_sections',
			array( array() ),
			'BuddyBoss 3.0.0',
			'bb_register_feature_section()'
		);
	}
);

/**
 * Fire the legacy `bbp_admin_get_settings_fields` filter after Settings 2.0
 * finishes registering forum fields.
 *
 * In legacy bbPress, this filter was applied inside bboss_bbp_admin_get_settings_fields()
 * allowing third-party plugins to add settings fields to the WordPress Settings API
 * pages. Settings 2.0 no longer calls that function for registration; this deprecation
 * wrapper notifies developers to use bboss_register_feature_field() instead.
 *
 * @since bbPress (r4001)
 * @deprecated BuddyBoss 3.0.0 Use {@see bboss_register_feature_field()} to register forum settings fields.
 */
add_action(
	'bb_forums_after_register_settings_fields',
	static function () {
		apply_filters_deprecated(
			'bbp_admin_get_settings_fields',
			array( array() ),
			'BuddyBoss 3.0.0',
			'bb_register_feature_field()'
		);
	}
);

/**
 * Fire the legacy `bbp_admin_get_settings_fields_for_section` filter after
 * Settings 2.0 finishes registering forum fields.
 *
 * In legacy bbPress, this filter was applied inside
 * bboss_bbp_admin_get_settings_fields_for_section() allowing third-party plugins
 * to modify fields for a specific section. Settings 2.0 no longer calls that
 * function for registration; this deprecation wrapper notifies developers to
 * use bboss_register_feature_field() instead.
 *
 * @since bbPress (r4001)
 * @deprecated BuddyBoss 3.0.0 Use {@see bboss_register_feature_field()} to register forum settings fields.
 */
add_action(
	'bb_forums_after_register_settings_fields',
	static function () {
		apply_filters_deprecated(
			'bbp_admin_get_settings_fields_for_section',
			array( array(), '' ),
			'BuddyBoss 3.0.0',
			'bb_register_feature_field()'
		);
	}
);

// ──────────────────────────────────────────────────────────────────────────────
// Forums Settings API function stubs.
// These functions were defined in bboss-forums/admin/settings.php (deleted).
// Third-party code calling them directly would get a fatal error without stubs.
// ──────────────────────────────────────────────────────────────────────────────

if ( ! function_exists( 'bboss_bbp_admin_get_settings_sections' ) ) {
	/**
	 * Get the bbPress admin settings sections.
	 *
	 * @since bbPress (r4001)
	 * @deprecated BuddyBoss 3.0.0 Forum settings are now managed by Settings 2.0.
	 *
	 * @return array Empty array.
	 */
	function bboss_bbp_admin_get_settings_sections() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'Settings 2.0 Forums feature (bb_admin_settings_register_forums_feature)' );

		return array();
	}
}

if ( ! function_exists( 'bboss_bbp_admin_get_settings_fields' ) ) {
	/**
	 * Get the bbPress admin settings fields.
	 *
	 * @since bbPress (r4001)
	 * @deprecated BuddyBoss 3.0.0 Forum settings are now managed by Settings 2.0.
	 *
	 * @return array Empty array.
	 */
	function bboss_bbp_admin_get_settings_fields() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'Settings 2.0 Forums feature (bb_admin_settings_register_forums_feature)' );

		return array();
	}
}

if ( ! function_exists( 'bboss_bbp_admin_get_settings_fields_for_section' ) ) {
	/**
	 * Get the bbPress admin settings fields for a specific section.
	 *
	 * @since bbPress (r4001)
	 * @deprecated BuddyBoss 3.0.0 Forum settings are now managed by Settings 2.0.
	 *
	 * @param string $section_id Section ID.
	 *
	 * @return array Empty array.
	 */
	function bboss_bbp_admin_get_settings_fields_for_section( $section_id = '' ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'Settings 2.0 Forums feature (bb_admin_settings_register_forums_feature)' );

		return array();
	}
}

// ──────────────────────────────────────────────────────────────────────────────
// Forums Settings 2.0 deprecated AJAX endpoint stubs.
// These AJAX actions were in BBP_Admin and have been replaced by
// Settings 2.0 AJAX handlers (BB_Admin_Topics_Ajax, BB_Admin_Replies_Ajax).
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Deprecated: Topic suggest AJAX handler.
 *
 * @since bbPress (r4261)
 * @deprecated BuddyBoss 3.0.0 Use bb_admin_discussion_autocomplete instead.
 */
add_action(
	'wp_ajax_bbp_suggest_topic',
	static function () {
		_deprecated_function( 'bbp_suggest_topic AJAX action', 'BuddyBoss 3.0.0', 'bb_admin_discussion_autocomplete' );
		wp_send_json_error( array( 'message' => __( 'This endpoint has been deprecated. Use bb_admin_discussion_autocomplete instead.', 'buddyboss' ) ) );
	}
);

/**
 * Deprecated: Reply suggest AJAX handler.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss 3.0.0 Use bb_admin_reply_autocomplete instead.
 */
add_action(
	'wp_ajax_bbp_suggest_reply',
	static function () {
		_deprecated_function( 'bbp_suggest_reply AJAX action', 'BuddyBoss 3.0.0', 'bb_admin_reply_autocomplete' );
		wp_send_json_error( array( 'message' => __( 'This endpoint has been deprecated. Use bb_admin_reply_autocomplete instead.', 'buddyboss' ) ) );
	}
);

/**
 * Deprecated: User suggest AJAX handler.
 *
 * @since bbPress (r5014)
 * @deprecated BuddyBoss 3.0.0 Use standard WordPress user search instead.
 */
add_action(
	'wp_ajax_bbp_suggest_user',
	static function () {
		_deprecated_function( 'bbp_suggest_user AJAX action', 'BuddyBoss 3.0.0', 'WordPress user search' );
		wp_send_json_error( array( 'message' => __( 'This endpoint has been deprecated.', 'buddyboss' ) ) );
	}
);

// ──────────────────────────────────────────────────────────────────────────────
// Notifications Settings 2.0 deprecated hook compatibility.
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Fire the legacy `bb_admin_setting_notifications_register_fields` hook after
 * Settings 2.0 finishes registering notification fields.
 *
 * The original hook passed a `BB_Admin_Setting_Notifications` instance. Settings 2.0
 * no longer uses that class, so a no-op stub is passed to satisfy callbacks
 * (e.g. Pro extensions) that call add_section()/add_field() on the argument.
 *
 * @since BuddyBoss 3.0.0
 * @deprecated BuddyBoss 3.0.0 Use {@see 'bb_notifications_after_register_settings_fields'} instead.
 */
add_action(
	'bb_notifications_after_register_settings_fields',
	static function () {
		do_action_deprecated(
			'bb_admin_setting_notifications_register_fields',
			array(
				new class() {
					/**
					 * No-op stub for BBoss_Admin_Setting_tab::add_section().
					 *
					 * @param mixed ...$args Ignored.
					 */
					public function add_section( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found

					/**
					 * No-op stub for BBoss_Admin_Setting_tab::add_field().
					 *
					 * @param mixed ...$args Ignored.
					 */
					public function add_field( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
				},
			),
			'BuddyBoss 3.0.0',
			'bb_notifications_after_register_settings_fields'
		);
	}
);

/**
 * Fire the legacy `bb_notification_web_push_notification_settings` filter after
 * Settings 2.0 finishes registering web push notification fields.
 *
 * The original filter received and returned an array of bbPress-style field
 * definitions for the web push settings section. Settings 2.0 uses
 * `bboss_register_feature_field()` instead, so this fires with an empty array
 * solely to trigger deprecation notices for third-party code still filtering it.
 *
 * @since BuddyBoss 3.0.0
 * @deprecated BuddyBoss 3.0.0 Use {@see 'bb_notifications_web_push_after_settings_fields'} instead.
 */
add_action(
	'bb_notifications_web_push_after_settings_fields',
	static function () {
		apply_filters_deprecated(
			'bb_notification_web_push_notification_settings',
			array( array() ),
			'BuddyBoss 3.0.0',
			'bb_notifications_web_push_after_settings_fields'
		);
	}
);

/**
 * Fire the legacy `bp_admin_tab_setting_save` and `bp_admin_tab_setting_saved`
 * hooks when notification settings are saved via Settings 2.0.
 *
 * Follows the same pattern as `bboss_media_fire_deprecated_save_hooks()` and
 * `bboss_members_fire_deprecated_save_hooks()`.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param string $feature_id Feature ID.
 * @param array  $settings   Full submitted settings.
 * @param array  $saved      Keys and values saved by core.
 */
function bboss_notifications_fire_deprecated_save_hooks( $feature_id, $settings, $saved ) {
	if ( 'notifications' !== $feature_id ) {
		return;
	}

	/**
	 * Fires before the notification settings are saved.
	 *
	 * @since BuddyBoss 1.9.3
	 * @deprecated BuddyBoss 3.0.0 Use {@see 'bb_admin_save_feature_settings_after'} with feature_id='notifications'.
	 *
	 * @param string $tab_name The tab name.
	 */
	do_action_deprecated(
		'bp_admin_tab_setting_save',
		array( 'bp-notifications' ),
		'BuddyBoss 3.0.0',
		'bb_admin_save_feature_settings_after'
	);

	/**
	 * Fires after the notification settings have been saved.
	 *
	 * @since BuddyBoss 1.9.3
	 * @deprecated BuddyBoss 3.0.0 Use {@see 'bb_admin_save_feature_settings_after'} with feature_id='notifications'.
	 *
	 * @param string $tab_name The tab name.
	 */
	do_action_deprecated(
		'bp_admin_tab_setting_saved',
		array( 'bp-notifications' ),
		'BuddyBoss 3.0.0',
		'bb_admin_save_feature_settings_after'
	);
}

add_action( 'bb_admin_save_feature_settings_after', 'bb_notifications_fire_deprecated_save_hooks', 99, 3 );

// ──────────────────────────────────────────────────────────────────────────────
// Deprecated notification settings public API functions.
// Removed in Settings 2.0 migration. Stubs prevent fatal errors in third-party
// code that may call these directly.
// ──────────────────────────────────────────────────────────────────────────────

if ( ! function_exists( 'bboss_notification_get_settings_sections' ) ) {
	/**
	 * Get the Notification settings sections.
	 *
	 * @since BuddyBoss 1.9.3
	 * @deprecated BuddyBoss 3.0.0 Notification settings migrated to Settings 2.0.
	 *
	 * @return array Empty array.
	 */
	function bboss_notification_get_settings_sections() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'Settings 2.0 feature registration' );

		return apply_filters_deprecated(
			'bb_notification_get_settings_sections',
			array( array() ),
			'BuddyBoss 3.0.0',
			'Settings 2.0 feature registration'
		);
	}
}

if ( ! function_exists( 'bboss_notification_get_settings_fields' ) ) {
	/**
	 * Get all the notification settings fields.
	 *
	 * @since BuddyBoss 1.9.3
	 * @deprecated BuddyBoss 3.0.0 Notification settings migrated to Settings 2.0.
	 *
	 * @return array Empty array.
	 */
	function bboss_notification_get_settings_fields() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'Settings 2.0 feature registration' );

		return apply_filters_deprecated(
			'bb_notification_get_settings_fields',
			array( array() ),
			'BuddyBoss 3.0.0',
			'Settings 2.0 feature registration'
		);
	}
}

if ( ! function_exists( 'bboss_notification_get_settings_fields_for_section' ) ) {
	/**
	 * Get settings fields by section.
	 *
	 * @since BuddyBoss 1.9.3
	 * @deprecated BuddyBoss 3.0.0 Notification settings migrated to Settings 2.0.
	 *
	 * @param string $section_id Section id.
	 *
	 * @return array Empty array.
	 */
	function bboss_notification_get_settings_fields_for_section( $section_id = '' ) {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'Settings 2.0 feature registration' );

		return apply_filters_deprecated(
			'bb_notification_get_settings_fields_for_section',
			array( array(), $section_id ),
			'BuddyBoss 3.0.0',
			'Settings 2.0 feature registration'
		);
	}
}

if ( ! function_exists( 'bboss_activate_notification' ) ) {
	/**
	 * Render a notification type toggle checkbox.
	 *
	 * This was a rendering helper used by the legacy notification types admin UI.
	 * Settings 2.0 renders notification types via the React `notification_types`
	 * field type instead.
	 *
	 * @since BuddyBoss 1.9.3
	 * @deprecated BuddyBoss 3.0.0 Use Settings 2.0 notification_types field type.
	 *
	 * @param array $field   Notification field definition.
	 * @param bool  $checked Whether the checkbox is checked.
	 */
	function bboss_activate_notification( $field, $checked ) {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'Settings 2.0 notification_types field type' );
	}
}

// ──────────────────────────────────────────────────────────────────────────────
// Moderation Settings 2.0 deprecated functions and hook compatibility.
// Legacy settings API functions were removed from bboss-moderation-settings.php.
// Moderation settings are now managed by Settings 2.0 (bboss-admin-settings-moderation.php).
// ──────────────────────────────────────────────────────────────────────────────

if ( ! function_exists( 'bboss_moderation_get_settings_sections' ) ) {
	/**
	 * Get the Moderation settings sections.
	 *
	 * @since BuddyBoss 1.5.6
	 * @deprecated BuddyBoss 3.0.0 Moderation settings are now managed by Settings 2.0.
	 *
	 * @return array Empty array.
	 */
	function bboss_moderation_get_settings_sections() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'Settings 2.0 Moderation feature (bb_admin_settings_register_moderation_feature)' );

		$sections = array();

		/**
		 * Filter the Moderation settings sections.
		 *
		 * @since BuddyBoss 1.5.6
		 * @deprecated BuddyBoss 3.0.0 Use {@see 'bb_moderation_after_register_settings_fields'} to register additional settings.
		 *
		 * @param array $sections Moderation settings sections.
		 */
		return (array) apply_filters_deprecated( 'bp_moderation_get_settings_sections', array( $sections ), 'BuddyBoss 3.0.0', 'bb_moderation_after_register_settings_fields' );
	}
}

if ( ! function_exists( 'bboss_moderation_get_settings_fields' ) ) {
	/**
	 * Get all of the Moderation settings fields.
	 *
	 * @since BuddyBoss 1.5.6
	 * @deprecated BuddyBoss 3.0.0 Moderation settings are now managed by Settings 2.0.
	 *
	 * @return array Empty array.
	 */
	function bboss_moderation_get_settings_fields() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'Settings 2.0 Moderation feature (bb_admin_settings_register_moderation_feature)' );

		$fields = array();

		/**
		 * Filter all Moderation settings fields.
		 *
		 * @since BuddyBoss 1.5.6
		 * @deprecated BuddyBoss 3.0.0 Use {@see 'bb_moderation_after_register_settings_fields'} to register additional fields.
		 *
		 * @param array $fields Moderation settings fields grouped by section.
		 */
		return (array) apply_filters_deprecated( 'bp_moderation_get_settings_fields', array( $fields ), 'BuddyBoss 3.0.0', 'bb_moderation_after_register_settings_fields' );
	}
}

if ( ! function_exists( 'bboss_moderation_get_settings_fields_for_section' ) ) {
	/**
	 * Get Moderation settings fields for a section.
	 *
	 * @since BuddyBoss 1.5.6
	 * @deprecated BuddyBoss 3.0.0 Moderation settings are now managed by Settings 2.0.
	 *
	 * @param string $section_id Section ID.
	 *
	 * @return array Empty array.
	 */
	function bboss_moderation_get_settings_fields_for_section( $section_id = '' ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'Settings 2.0 Moderation feature (bb_admin_settings_register_moderation_feature)' );

		$fields = array();

		/**
		 * Filter Moderation settings fields for a specific section.
		 *
		 * @since BuddyBoss 1.5.6
		 * @deprecated BuddyBoss 3.0.0 Use {@see 'bb_moderation_after_register_settings_fields'} to register additional fields.
		 *
		 * @param array  $fields     Settings fields for the section.
		 * @param string $section_id The section ID.
		 */
		return (array) apply_filters_deprecated( 'bp_moderation_get_settings_fields_for_section', array( $fields, $section_id ), 'BuddyBoss 3.0.0', 'bb_moderation_after_register_settings_fields' );
	}
}

/**
 * Fire the legacy `bp_admin_setting_moderation_register_fields` hook after
 * Settings 2.0 finishes registering moderation fields.
 *
 * The original hook passed a `BP_Admin_Setting_Moderation` instance. Settings 2.0
 * no longer uses that class, so a no-op stub is passed to satisfy callbacks
 * that call add_section()/add_field() on the argument.
 *
 * @since BuddyBoss 1.5.6
 * @deprecated BuddyBoss 3.0.0 Use {@see 'bb_moderation_after_register_settings_fields'} instead.
 */
add_action(
	'bb_moderation_after_register_settings_fields',
	static function () {
		do_action_deprecated(
			'bp_admin_setting_moderation_register_fields',
			array(
				new class() {
					/**
					 * No-op stub for BBoss_Admin_Setting_tab::add_section().
					 *
					 * @param mixed ...$args Ignored.
					 */
					public function add_section( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found

					/**
					 * No-op stub for BBoss_Admin_Setting_tab::add_field().
					 *
					 * @param mixed ...$args Ignored.
					 */
					public function add_field( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
				},
			),
			'BuddyBoss 3.0.0',
			'bb_moderation_after_register_settings_fields'
		);
	}
);

if ( ! function_exists( 'bboss_moderation_admin_category_listing_add_tab' ) ) {
	/**
	 * Legacy navigation tab for Moderation > Reporting Categories.
	 *
	 * @since BuddyBoss 1.5.6
	 * @deprecated BuddyBoss 3.0.0 Reporting Categories are now managed by Settings 2.0.
	 */
	function bboss_moderation_admin_category_listing_add_tab() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'Settings 2.0 Moderation feature (bboss-admin-settings-moderation.php)' );
	}
}

// ──────────────────────────────────────────────────────────────────────────────
// Email Invites Settings 2.0 deprecated functions.
// Legacy settings render callbacks were removed from bboss-core-admin-settings.php.
// Invites settings are now managed by Settings 2.0 (bboss-admin-settings-invites.php).
// ──────────────────────────────────────────────────────────────────────────────

if ( ! function_exists( 'bboss_admin_setting_callback_member_invite_email_subject' ) ) {
	/**
	 * Legacy render callback for the Email Subject invite setting.
	 *
	 * @since BuddyBoss 1.0.0
	 * @deprecated BuddyBoss 3.0.0 Invites settings are now managed by Settings 2.0.
	 */
	function bboss_admin_setting_callback_member_invite_email_subject() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'Settings 2.0 Invites feature (bboss-admin-settings-invites.php)' );
	}
}

if ( ! function_exists( 'bboss_admin_setting_callback_member_invite_email_content' ) ) {
	/**
	 * Legacy render callback for the Email Content invite setting.
	 *
	 * @since BuddyBoss 1.0.0
	 * @deprecated BuddyBoss 3.0.0 Invites settings are now managed by Settings 2.0.
	 */
	function bboss_admin_setting_callback_member_invite_email_content() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'Settings 2.0 Invites feature (bboss-admin-settings-invites.php)' );
	}
}

if ( ! function_exists( 'bboss_admin_setting_callback_member_invite_member_type' ) ) {
	/**
	 * Legacy render callback for the Set Profile Type invite setting.
	 *
	 * @since BuddyBoss 1.0.0
	 * @deprecated BuddyBoss 3.0.0 Invites settings are now managed by Settings 2.0.
	 */
	function bboss_admin_setting_callback_member_invite_member_type() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'Settings 2.0 Invites feature (bboss-admin-settings-invites.php)' );
	}
}

if ( ! function_exists( 'bboss_admin_setting_callback_enable_send_invite_member_type' ) ) {
	/**
	 * Legacy render callback for per-profile-type invite setting.
	 *
	 * @since BuddyBoss 1.0.0
	 * @deprecated BuddyBoss 3.0.0 Invites settings are now managed by Settings 2.0.
	 *
	 * @param array $args Field arguments.
	 */
	function bboss_admin_setting_callback_enable_send_invite_member_type( $args = array() ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'Settings 2.0 Invites feature (bboss-admin-settings-invites.php)' );
	}
}

if ( ! function_exists( 'bboss_email_invites_tutorial' ) ) {
	/**
	 * Legacy tutorial link for Email Invites section.
	 *
	 * @since BuddyBoss 1.0.0
	 * @deprecated BuddyBoss 3.0.0 Invites settings are now managed by Settings 2.0.
	 */
	function bboss_email_invites_tutorial() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'Settings 2.0 Invites feature (bboss-admin-settings-invites.php)' );
	}
}

// ──────────────────────────────────────────────────────────────────────────────
// Invites settings save hooks (backward-compatible with Settings 1.0 tabs).
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Fire deprecated legacy setting save hooks for invites backward compatibility.
 *
 * Legacy Settings 1.0 fires do_action('bp_admin_tab_setting_save', $tab_name)
 * and do_action('bp_admin_tab_setting_saved', $tab_name) when any settings tab
 * is saved. This bridge ensures those hooks still fire when invites settings
 * are saved via Settings 2.0.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param string $feature_id Feature ID.
 * @param array  $settings   Full submitted settings.
 * @param array  $saved      Keys and values saved by core.
 */
function bboss_invites_fire_deprecated_save_hooks( $feature_id, $settings, $saved ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
	if ( 'invites' !== $feature_id ) {
		return;
	}

	/**
	 * Fires when invites settings are saved.
	 *
	 * @since      BuddyBoss 1.0.0
	 *
	 * @param string $tab_name The tab name.
	 *
	 * @deprecated BuddyBoss 3.0.0 Use {@see 'bb_admin_save_feature_settings_after'} with feature_id='invites'.
	 */
	do_action_deprecated(
		'bp_admin_tab_setting_save',
		array( 'bp-invites' ),
		'BuddyBoss 3.0.0',
		'bb_admin_save_feature_settings_after'
	);

	/**
	 * Fires after invites settings have been saved.
	 *
	 * @since      BuddyBoss 1.0.0
	 *
	 * @param string $tab_name The tab name.
	 *
	 * @deprecated BuddyBoss 3.0.0 Use {@see 'bb_admin_save_feature_settings_after'} with feature_id='invites'.
	 */
	do_action_deprecated(
		'bp_admin_tab_setting_saved',
		array( 'bp-invites' ),
		'BuddyBoss 3.0.0',
		'bb_admin_save_feature_settings_after'
	);
}

add_action( 'bb_admin_save_feature_settings_after', 'bb_invites_fire_deprecated_save_hooks', 99, 3 );

// ──────────────────────────────────────────────────────────────────────────────
// Email Templates Settings 2.0 deprecated functions.
// Legacy email admin tabs were removed from bboss-core-admin-functions.php.
// Email Templates are now managed by Settings 2.0 (bboss-admin-settings-emails.php).
// ──────────────────────────────────────────────────────────────────────────────

if ( ! function_exists( 'bboss_core_admin_emails_tabs' ) ) {
	/**
	 * Legacy admin tab output for BuddyBoss > Emails screens.
	 *
	 * @since BuddyBoss 1.0.0
	 * @deprecated BuddyBoss 3.0.0 Email Templates are now managed by Settings 2.0.
	 *
	 * @param string $active_tab Name of the active tab.
	 */
	function bboss_core_admin_emails_tabs( $active_tab = '' ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'Settings 2.0 Emails feature (bboss-admin-settings-emails.php)' );
	}
}

if ( ! function_exists( 'bboss_core_get_emails_admin_tabs' ) ) {
	/**
	 * Legacy tab data builder for BuddyBoss > Emails screens.
	 *
	 * @since BuddyBoss 1.0.0
	 * @deprecated BuddyBoss 3.0.0 Email Templates are now managed by Settings 2.0.
	 *
	 * @param string $active_tab Name of the active tab.
	 * @return array Empty array (legacy data no longer applicable).
	 */
	function bboss_core_get_emails_admin_tabs( $active_tab = '' ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'Settings 2.0 Emails feature (bboss-admin-settings-emails.php)' );
		return array();
	}
}

/**
 * Fire deprecated bp_admin_tab_setting_save/saved hooks for the legacy bp-registration tab.
 *
 * Follows the same pattern as `bboss_advanced_fire_deprecated_save_hooks()`.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param string $feature_id Feature ID.
 * @param array  $settings   Full submitted settings.
 * @param array  $saved      Keys and values saved by core.
 */
function bboss_registration_fire_deprecated_save_hooks( $feature_id, $settings, $saved ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
	if ( 'registration' !== $feature_id ) {
		return;
	}

	/**
	 * Fires when registration settings are saved (legacy bridge).
	 *
	 * @since      BuddyBoss 1.0.0
	 * @deprecated BuddyBoss 3.0.0 Use {@see 'bb_admin_save_feature_settings_after'} with feature_id='registration'.
	 *
	 * @param string $tab_name Tab name.
	 */
	do_action_deprecated( 'bp_admin_tab_setting_save', array( 'bp-registration' ), 'BuddyBoss 3.0.0', 'bb_admin_save_feature_settings_after' );

	/**
	 * Fires after registration settings have been saved (legacy bridge).
	 *
	 * @since      BuddyBoss 1.0.0
	 * @deprecated BuddyBoss 3.0.0 Use {@see 'bb_admin_save_feature_settings_after'} with feature_id='registration'.
	 *
	 * @param string $tab_name Tab name.
	 */
	do_action_deprecated( 'bp_admin_tab_setting_saved', array( 'bp-registration' ), 'BuddyBoss 3.0.0', 'bb_admin_save_feature_settings_after' );
}
add_action( 'bb_admin_save_feature_settings_after', 'bb_registration_fire_deprecated_save_hooks', 99, 3 );

// ──────────────────────────────────────────────────────────────────────────────
// Registration Settings 2.0 deprecated render callbacks.
// Legacy render callbacks were removed from bboss-core-admin-settings.php.
// Registration settings are now managed by Settings 2.0 (bboss-admin-settings-registration.php).
// ──────────────────────────────────────────────────────────────────────────────

if ( ! function_exists( 'bboss_admin_setting_callback_register' ) ) {
	/**
	 * Legacy render callback for the Enable Registration checkbox.
	 *
	 * @since BuddyPress 1.6.0
	 * @deprecated BuddyBoss 3.0.0 Registration settings are now managed by Settings 2.0.
	 */
	function bboss_admin_setting_callback_register() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'Settings 2.0 Registration feature (bboss-admin-settings-registration.php)' );
	}
}

if ( ! function_exists( 'bboss_admin_setting_callback_register_show_confirm_email' ) ) {
	/**
	 * Legacy render callback for the Confirm Email checkbox.
	 *
	 * @since BuddyBoss 1.1.6
	 * @deprecated BuddyBoss 3.0.0 Registration settings are now managed by Settings 2.0.
	 */
	function bboss_admin_setting_callback_register_show_confirm_email() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'Settings 2.0 Registration feature (bboss-admin-settings-registration.php)' );
	}
}

if ( ! function_exists( 'bboss_admin_setting_callback_register_show_legal_agreement' ) ) {
	/**
	 * Legacy render callback for the Legal Agreement checkbox.
	 *
	 * @since BuddyBoss 1.5.8.3
	 * @deprecated BuddyBoss 3.0.0 Registration settings are now managed by Settings 2.0.
	 */
	function bboss_admin_setting_callback_register_show_legal_agreement() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'Settings 2.0 Registration feature (bboss-admin-settings-registration.php)' );
	}
}

if ( ! function_exists( 'bboss_admin_setting_callback_register_show_confirm_password' ) ) {
	/**
	 * Legacy render callback for the Confirm Password checkbox.
	 *
	 * @since BuddyBoss 1.1.6
	 * @deprecated BuddyBoss 3.0.0 Registration settings are now managed by Settings 2.0.
	 */
	function bboss_admin_setting_callback_register_show_confirm_password() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'Settings 2.0 Registration feature (bboss-admin-settings-registration.php)' );
	}
}

if ( ! function_exists( 'bboss_admin_setting_callback_register_allow_custom_registration' ) ) {
	/**
	 * Legacy render callback for the Registration Form select.
	 *
	 * @since BuddyBoss 1.2.8
	 * @deprecated BuddyBoss 3.0.0 Registration settings are now managed by Settings 2.0.
	 */
	function bboss_admin_setting_callback_register_allow_custom_registration() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'Settings 2.0 Registration feature (bboss-admin-settings-registration.php)' );
	}
}

if ( ! function_exists( 'bboss_admin_setting_callback_register_page_url' ) ) {
	/**
	 * Legacy render callback for the Custom Registration Page URL input.
	 *
	 * @since BuddyBoss 1.2.8
	 * @deprecated BuddyBoss 3.0.0 Registration settings are now managed by Settings 2.0.
	 */
	function bboss_admin_setting_callback_register_page_url() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'Settings 2.0 Registration feature (bboss-admin-settings-registration.php)' );
	}
}

if ( ! function_exists( 'bboss_admin_registration_setting_tutorial' ) ) {
	/**
	 * Legacy tutorial link for the Registration settings section.
	 *
	 * @since BuddyBoss 1.2.8
	 * @deprecated BuddyBoss 3.0.0 Registration settings are now managed by Settings 2.0.
	 */
	function bboss_admin_registration_setting_tutorial() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0', 'Settings 2.0 Registration feature (bboss-admin-settings-registration.php)' );
	}
}

// ──────────────────────────────────────────────────────────────────────────────
// General Settings (Privacy, Toolbar, Content Counts) — migrated to Advanced.
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Fire deprecated `bp_admin_setting_general_register_fields` hook.
 *
 * Legacy General tab fired this action so third-party plugins (e.g., BuddyBoss Sharing)
 * could add custom fields. Settings 2.0 fires `bb_advanced_after_register_settings_fields`
 * instead. This bridge ensures legacy listeners still fire (with a deprecation notice).
 *
 * @since BuddyBoss 3.0.0
 */
add_action(
	'bb_advanced_after_register_settings_fields',
	function () {
		/**
		 * Fires after General tab settings fields are registered.
		 *
		 * @since BuddyBoss 1.2.6
		 * @deprecated BuddyBoss 3.0.0 Use {@see 'bb_advanced_after_register_settings_fields'} instead.
		 *
		 * @param object $stub No-op stub (legacy passed BP_Admin_Setting_General instance).
		 */
		do_action_deprecated(
			'bp_admin_setting_general_register_fields',
			array(
				new class() {
					/**
					 * No-op: legacy add_section stub.
					 *
					 * @param mixed ...$args Arguments.
					 */
					public function add_section( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
					/**
					 * No-op: legacy add_field stub.
					 *
					 * @param mixed ...$args Arguments.
					 */
					public function add_field( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
				},
			),
			'BuddyBoss 3.0.0',
			'bb_advanced_after_register_settings_fields'
		);
	}
);

// ──────────────────────────────────────────────────────────────────────────────
// Advanced/Performance Settings (Page Requests, Activity Loading, Telemetry).
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Fire deprecated `bp_admin_setting_performance_register_fields` hook.
 *
 * @since BuddyBoss 3.0.0
 */
add_action(
	'bb_advanced_after_register_settings_fields',
	function () {
		/**
		 * Fires after Performance/Advanced tab settings fields are registered.
		 *
		 * @since BuddyBoss 2.5.80
		 * @deprecated BuddyBoss 3.0.0 Use {@see 'bb_advanced_after_register_settings_fields'} instead.
		 *
		 * @param object $stub No-op stub (legacy passed BP_Admin_Setting_Performance instance).
		 */
		do_action_deprecated(
			'bp_admin_setting_performance_register_fields',
			array(
				new class() {
					/**
					 * No-op stub.
					 *
					 * @param mixed ...$args Arguments.
					 */
					public function add_section( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
					/**
					 * No-op stub.
					 *
					 * @param mixed ...$args Arguments.
					 */
					public function add_field( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
				},
			),
			'BuddyBoss 3.0.0',
			'bb_advanced_after_register_settings_fields'
		);
	}
);

/**
 * Fire deprecated save hooks for General and Advanced tabs.
 *
 * Bridges `bp_admin_tab_setting_save` / `bp_admin_tab_setting_saved` for
 * `bp-general` and `bp-advanced` tab names so third-party plugins that hooked
 * into these get proper deprecation notices.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param string $feature_id Feature ID.
 * @param array  $settings   Submitted settings.
 * @param array  $saved      Saved settings.
 */
function bboss_advanced_fire_deprecated_save_hooks( $feature_id, $settings, $saved ) {
	if ( 'advanced' !== $feature_id ) {
		return;
	}

	/**
	 * Fires before General tab settings are saved.
	 *
	 * @since BuddyBoss 1.0.0
	 * @deprecated BuddyBoss 3.0.0
	 *
	 * @param string $tab_name Tab name.
	 */
	do_action_deprecated( 'bp_admin_tab_setting_save', array( 'bp-general' ), 'BuddyBoss 3.0.0', 'bb_admin_save_feature_settings_after' );

	/**
	 * Fires after General tab settings are saved.
	 *
	 * @since BuddyBoss 1.0.0
	 * @deprecated BuddyBoss 3.0.0
	 *
	 * @param string $tab_name Tab name.
	 */
	do_action_deprecated( 'bp_admin_tab_setting_saved', array( 'bp-general' ), 'BuddyBoss 3.0.0', 'bb_admin_save_feature_settings_after' );

	/**
	 * Fires before Advanced tab settings are saved.
	 *
	 * @since BuddyBoss 2.5.80
	 * @deprecated BuddyBoss 3.0.0
	 *
	 * @param string $tab_name Tab name.
	 */
	do_action_deprecated( 'bp_admin_tab_setting_save', array( 'bp-advanced' ), 'BuddyBoss 3.0.0', 'bb_admin_save_feature_settings_after' );

	/**
	 * Fires after Advanced tab settings are saved.
	 *
	 * @since BuddyBoss 2.5.80
	 * @deprecated BuddyBoss 3.0.0
	 *
	 * @param string $tab_name Tab name.
	 */
	do_action_deprecated( 'bp_admin_tab_setting_saved', array( 'bp-advanced' ), 'BuddyBoss 3.0.0', 'bb_admin_save_feature_settings_after' );
}
add_action( 'bb_admin_save_feature_settings_after', 'bb_advanced_fire_deprecated_save_hooks', 99, 3 );

// ──────────────────────────────────────────────────────────────────────────────
// Labs tab deprecated functions and hook compatibility.
// The Labs tab (bp-settings&tab=bp-labs), its class (BB_Admin_Setting_Labs),
// section/field helpers, and template callbacks were removed in Settings 2.0.
// These stubs keep third-party code that still references the public helpers
// from fataling; all return empty to indicate "no Labs features".
// ──────────────────────────────────────────────────────────────────────────────

if ( ! function_exists( 'bboss_labs_get_settings_sections' ) ) {
	/**
	 * Register the labs settings section.
	 *
	 * @since      BuddyBoss 1.9.3
	 * @deprecated BuddyBoss 3.0.0 The Labs tab has been removed.
	 *
	 * @return array Empty array.
	 */
	function bboss_labs_get_settings_sections() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0' );

		/**
		 * Filter the (now empty) Labs settings sections.
		 *
		 * @since      BuddyBoss 1.9.3
		 * @deprecated BuddyBoss 3.0.0 The Labs tab has been removed.
		 *
		 * @param array $settings Labs sections.
		 */
		return (array) apply_filters_deprecated(
			'bb_labs_get_settings_sections',
			array( array() ),
			'BuddyBoss 3.0.0'
		);
	}
}

if ( ! function_exists( 'bboss_labs_get_settings_fields_for_section' ) ) {
	/**
	 * Get Labs settings fields by section.
	 *
	 * @since      BuddyBoss 1.9.3
	 * @deprecated BuddyBoss 3.0.0 The Labs tab has been removed.
	 *
	 * @param string $section_id Section id.
	 *
	 * @return array Empty array.
	 */
	function bboss_labs_get_settings_fields_for_section( $section_id = '' ) {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0' );

		return (array) apply_filters_deprecated(
			'bb_labs_get_settings_fields_for_section',
			array( array(), $section_id ),
			'BuddyBoss 3.0.0'
		);
	}
}

if ( ! function_exists( 'bboss_labs_get_settings_fields' ) ) {
	/**
	 * Get all of the Labs settings fields.
	 *
	 * @since      BuddyBoss 1.9.3
	 * @deprecated BuddyBoss 3.0.0 The Labs tab has been removed.
	 *
	 * @return array Empty array.
	 */
	function bboss_labs_get_settings_fields() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0' );

		return (array) apply_filters_deprecated(
			'bb_labs_get_settings_fields',
			array( array() ),
			'BuddyBoss 3.0.0'
		);
	}
}

if ( ! function_exists( 'bboss_labs_info_section_callback' ) ) {
	/**
	 * Labs settings section info callback.
	 *
	 * @since      BuddyBoss 1.9.3
	 * @deprecated BuddyBoss 3.0.0 The Labs tab has been removed.
	 */
	function bboss_labs_info_section_callback() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0' );
	}
}

if ( ! function_exists( 'bboss_labs_no_settings_callback' ) ) {
	/**
	 * Labs empty-state callback.
	 *
	 * @since      BuddyBoss 2.1.5.1
	 * @deprecated BuddyBoss 3.0.0 The Labs tab has been removed.
	 */
	function bboss_labs_no_settings_callback() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0' );
	}
}

/* ----------------------------------------------------------------------------
 * Legacy "Components" admin page — removed in Settings 2.0.
 *
 * The classic BuddyBoss > Components screen and its POST handlers were
 * replaced by the React-based Settings 2.0 feature grid. The four stubs below
 * preserve the public function names so that third-party callers receive a
 * clear `_deprecated_function` notice instead of a fatal error.
 *
 * The two data helpers that lived alongside these — `bp_core_admin_get_components()`
 * and `bp_core_admin_get_active_components_from_submitted_settings()` — were
 * relocated to `src/bboss-core/admin/bboss-core-admin-functions.php` because they are
 * pure data utilities still consumed by the test suite and may be hooked by
 * third-party code via the `bp_core_admin_get_components` filter.
 * ------------------------------------------------------------------------- */

if ( ! function_exists( 'bboss_core_admin_components_settings' ) ) {
	/**
	 * Renders the legacy Component Setup admin panel.
	 *
	 * @since      BuddyPress 1.6.0
	 * @deprecated BuddyBoss 3.0.0 The legacy Components admin page has been removed in Settings 2.0.
	 *                                   Components are now managed as feature cards in the Settings grid.
	 */
	function bboss_core_admin_components_settings() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0' );
	}
}

if ( ! function_exists( 'bboss_core_admin_components_options' ) ) {
	/**
	 * Renders the legacy Component Setup option markup.
	 *
	 * @since      BuddyPress 1.6.0
	 * @deprecated BuddyBoss 3.0.0 The legacy Components admin page has been removed in Settings 2.0.
	 */
	function bboss_core_admin_components_options() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0' );
	}
}

if ( ! function_exists( 'bboss_core_admin_components_settings_handler' ) ) {
	/**
	 * Handles legacy Component Setup form submissions.
	 *
	 * @since      BuddyPress 1.6.0
	 * @deprecated BuddyBoss 3.0.0 The legacy Components admin page has been removed in Settings 2.0.
	 *                                   Feature toggles are saved via the bb_admin_toggle_feature AJAX endpoint.
	 */
	function bboss_core_admin_components_settings_handler() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0' );
	}
}

if ( ! function_exists( 'bboss_core_admin_components_activation_handler' ) ) {
	/**
	 * Handles individual component activation requests from the legacy admin page.
	 *
	 * @since      BuddyPress 2.0.0
	 * @deprecated BuddyBoss 3.0.0 The legacy Components admin page has been removed in Settings 2.0.
	 */
	function bboss_core_admin_components_activation_handler() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.0.0' );
	}
}

// ──────────────────────────────────────────────────────────────────────────────
// LearnDash integration extracted to buddyboss-learndash addon.
// `bp_register_learndash_integration()` used to live in
// `bboss-integrations/learndash/bp-learndash-loader.php` and registered the
// bundled integration on `bp_setup_integrations`. The bundled integration was
// removed in BuddyBoss 3.0.0; the addon registers its own copy via
// `bb_learndash_register_integration()`. Any third-party code that called
// `bp_register_learndash_integration()` directly will hit this stub instead
// of a fatal.
// ──────────────────────────────────────────────────────────────────────────────

if ( ! function_exists( 'bboss_register_learndash_integration' ) ) {
	/**
	 * Deprecated: previously registered the bundled LearnDash integration.
	 *
	 * @since      BuddyBoss 1.0.0
	 * @deprecated BuddyBoss 3.0.0 The LearnDash integration moved to the
	 *             standalone `buddyboss-learndash` addon, which registers
	 *             itself via `bb_learndash_register_integration()`.
	 *
	 * @return void
	 */
	function bboss_register_learndash_integration() {
		_deprecated_function(
			__FUNCTION__,
			'BuddyBoss 3.0.0',
			'bb_learndash_register_integration() in the buddyboss-learndash addon'
		);
	}
}

// ──────────────────────────────────────────────────────────────────────────────
// ReadyLaunch legacy admin page deprecated functions.
// The standalone `admin.php?page=bb-readylaunch` page was retired in
// BuddyBoss 3.0.0. Its URL now redirects to the Appearance feature in
// Settings 2.0 (`admin.php?page=bb-settings&tab=appearance&panel=general`).
// These stubs prevent fatals in third-party code that still calls the helpers.
//
// NOTE: The `BB_Readylaunch::bb_rest_readylaunch_platform_settings()` filter
// callback that projected `bb_rl_*` keys onto `/buddyboss/v1/settings` was
// also removed in this release. NO deprecation stub is provided for that
// filter — ReadyLaunch is web-only (the BuddyBoss App plugin never consumed
// those keys), every Platform/Pro internal read moved to `bp_get_option()`,
// and the old admin React client that consumed them was deleted. Third-party
// REST clients reading `bb_rl_*` from the settings endpoint need to migrate
// to direct option reads via the REST options API or their own endpoint.
// Documented in the release notes.
// ──────────────────────────────────────────────────────────────────────────────

if ( ! function_exists( 'bboss_readylaunch_settings_page_html' ) ) {
	/**
	 * Render the legacy ReadyLaunch React Settings page.
	 *
	 * @since BuddyBoss 2.9.00
	 * @deprecated BuddyBoss 3.0.0 ReadyLaunch settings moved to Settings 2.0 Appearance feature.
	 *
	 * @return void
	 */
	function bboss_readylaunch_settings_page_html() {
		_deprecated_function(
			__FUNCTION__,
			'BuddyBoss 3.0.0',
			'Settings 2.0 Appearance feature (admin.php?page=bb-settings&tab=appearance)'
		);
	}
}

if ( ! function_exists( 'bboss_readylaunch_register_enqueue_style_script' ) ) {
	/**
	 * Register legacy ReadyLaunch React Settings page styles + scripts.
	 *
	 * @since BuddyBoss 2.9.00
	 * @deprecated BuddyBoss 3.0.0 ReadyLaunch admin page retired.
	 *
	 * @return void
	 */
	function bboss_readylaunch_register_enqueue_style_script() {
		_deprecated_function(
			__FUNCTION__,
			'BuddyBoss 3.0.0',
			'Settings 2.0 Appearance feature (admin.php?page=bb-settings&tab=appearance)'
		);
	}
}

if ( ! function_exists( 'bboss_readylaunch_settings_page_enqueue_style_script' ) ) {
	/**
	 * Enqueue legacy ReadyLaunch React Settings page styles + scripts.
	 *
	 * @since BuddyBoss 2.9.00
	 * @deprecated BuddyBoss 3.0.0 ReadyLaunch admin page retired.
	 *
	 * @param string $admin_page The admin page hook suffix (unused, kept for signature compat).
	 * @return void
	 */
	function bboss_readylaunch_settings_page_enqueue_style_script( $admin_page ) {
		_deprecated_function(
			__FUNCTION__,
			'BuddyBoss 3.0.0',
			'Settings 2.0 Appearance feature (admin.php?page=bb-settings&tab=appearance)'
		);
		unset( $admin_page );
	}
}

// ──────────────────────────────────────────────────────────────────────────────
// Invites Settings 2.0 deprecated hook compatibility.
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Fire the legacy `bp_admin_setting_invites_register_fields` hook after
 * Settings 2.0 finishes registering invites fields.
 *
 * The original hook passed a `BP_Admin_Setting_Invites` instance. Settings 2.0
 * no longer uses that class, so a no-op stub is passed to satisfy callbacks
 * that call add_section()/add_field() on the argument.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss 3.0.0 Use {@see 'bb_invites_after_register_settings_fields'} instead.
 */
add_action(
	'bb_invites_after_register_settings_fields',
	static function () {
		do_action_deprecated(
			'bp_admin_setting_invites_register_fields',
			array(
				new class() {
					/**
					 * No-op stub for BBoss_Admin_Setting_tab::add_section().
					 *
					 * @param mixed ...$args Ignored.
					 */
					public function add_section( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found

					/**
					 * No-op stub for BBoss_Admin_Setting_tab::add_field().
					 *
					 * @param mixed ...$args Ignored.
					 */
					public function add_field( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
				},
			),
			'BuddyBoss 3.0.0',
			'bb_invites_after_register_settings_fields'
		);
	}
);

// ──────────────────────────────────────────────────────────────────────────────
// Legacy bp-pages admin screen deprecated functions.
// The standalone `admin.php?page=bp-pages` page was retired in BuddyBoss
// 3.0.0 in favour of Appearance → Pages inside the Settings 2.0 React
// admin. The `?page=bp-pages` URL now redirects via
// `bb_redirect_bp_settings_before_permission_check()`. Each function below
// replaces its original counterpart from the deleted
// `src/bboss-core/admin/bp-core-admin-pages.php` — third parties that did
// `require_once` against that absolute path will see a file-not-found
// warning, which is the intended migration signal. Data helpers
// `bp_core_admin_get_directory_pages()` and `bp_core_admin_get_static_pages()`
// were deliberately NOT deprecated — the new React panel reads them from
// `src/bboss-core/admin/bboss-core-admin-slugs.php` which stays live.
// ──────────────────────────────────────────────────────────────────────────────

if ( ! function_exists( 'bboss_core_admin_pages_settings' ) ) {
	/**
	 * Render the legacy Pages Setup admin panel.
	 *
	 * @since      BuddyBoss 1.0.0
	 * @deprecated BuddyBoss 3.0.0 Moved to Settings 2.0 Appearance → Pages.
	 */
	function bboss_core_admin_pages_settings() {
		_deprecated_function(
			__FUNCTION__,
			'BuddyBoss 3.0.0',
			'Settings 2.0 Appearance → Pages (admin.php?page=bb-settings&tab=appearance&panel=pages)'
		);
	}
}

if ( ! function_exists( 'bboss_custom_pages_do_settings_sections' ) ) {
	/**
	 * Output the legacy Pages settings sections.
	 *
	 * @since      BuddyBoss 1.0.0
	 * @deprecated BuddyBoss 3.0.0 Replaced by React rendering in SettingsForm.js.
	 *
	 * @param string $page Legacy settings page slug (unused).
	 */
	function bboss_custom_pages_do_settings_sections( $page ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Deprecation stub preserves original signature.
		_deprecated_function(
			__FUNCTION__,
			'BuddyBoss 3.0.0',
			'Settings 2.0 React admin (SettingsForm.js)'
		);
	}
}

if ( ! function_exists( 'bboss_custom_pages_do_settings_fields' ) ) {
	/**
	 * Output the legacy Pages settings fields for a section.
	 *
	 * @since      BuddyBoss 1.0.0
	 * @deprecated BuddyBoss 3.0.0 Replaced by React rendering in SettingsForm.js.
	 *
	 * @param string $page    Legacy settings page slug (unused).
	 * @param string $section Legacy section slug (unused).
	 */
	function bboss_custom_pages_do_settings_fields( $page, $section ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- Deprecation stub preserves original signature.
		_deprecated_function(
			__FUNCTION__,
			'BuddyBoss 3.0.0',
			'Settings 2.0 React admin (SettingsForm.js)'
		);
	}
}

if ( ! function_exists( 'bboss_core_admin_register_page_fields' ) ) {
	/**
	 * Register the legacy Component Pages settings fields.
	 *
	 * @since      BuddyBoss 1.0.0
	 * @deprecated BuddyBoss 3.0.0 Fields now registered via bb_register_feature_field in pages-panel.php.
	 */
	function bboss_core_admin_register_page_fields() {
		_deprecated_function(
			__FUNCTION__,
			'BuddyBoss 3.0.0',
			'bb_appearance_register_pages_fields (Settings 2.0 Appearance → Pages panel)'
		);
	}
}

if ( ! function_exists( 'bboss_core_admin_register_registration_page_fields' ) ) {
	/**
	 * Register the legacy Registration Pages settings fields.
	 *
	 * @since      BuddyBoss 1.0.0
	 * @deprecated BuddyBoss 3.0.0 Fields now registered via bb_register_feature_field in pages-panel.php.
	 */
	function bboss_core_admin_register_registration_page_fields() {
		_deprecated_function(
			__FUNCTION__,
			'BuddyBoss 3.0.0',
			'bb_appearance_register_pages_fields (Settings 2.0 Appearance → Pages panel)'
		);
	}
}

if ( ! function_exists( 'bboss_core_admin_directory_pages_description' ) ) {
	/**
	 * Print the legacy Component Pages section description.
	 *
	 * @since      BuddyBoss 1.0.0
	 * @deprecated BuddyBoss 3.0.0 Description is now a plain `description` arg on the component_pages section.
	 */
	function bboss_core_admin_directory_pages_description() {
		_deprecated_function(
			__FUNCTION__,
			'BuddyBoss 3.0.0',
			'Settings 2.0 section description arg'
		);
	}
}

if ( ! function_exists( 'bboss_core_admin_registration_pages_description' ) ) {
	/**
	 * Print the legacy Registration Pages section description.
	 *
	 * @since      BuddyBoss 1.0.0
	 * @deprecated BuddyBoss 3.0.0 Description logic moved to bboss_appearance_get_registration_section_meta().
	 */
	function bboss_core_admin_registration_pages_description() {
		_deprecated_function(
			__FUNCTION__,
			'BuddyBoss 3.0.0',
			'bboss_appearance_get_registration_section_meta'
		);
	}
}

if ( ! function_exists( 'bboss_admin_setting_callback_page_directory_dropdown' ) ) {
	/**
	 * Render the legacy page-directory dropdown control.
	 *
	 * @since      BuddyBoss 1.0.0
	 * @deprecated BuddyBoss 3.0.0 Dropdown is now the `async_select` field type driven by bb_admin_search_pages_list.
	 *
	 * @param array $args Legacy callback args (unused).
	 */
	function bboss_admin_setting_callback_page_directory_dropdown( $args ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Deprecation stub preserves original signature.
		_deprecated_function(
			__FUNCTION__,
			'BuddyBoss 3.0.0',
			'async_select field type + bb_admin_search_pages_list AJAX handler'
		);
	}
}

if ( ! function_exists( 'bboss_core_admin_maybe_save_pages_settings' ) ) {
	/**
	 * Save handler for the retired Pages admin screen.
	 *
	 * @since      BuddyBoss 1.0.0
	 * @deprecated BuddyBoss 3.0.0 Save path moved to bboss_appearance_pages_save_side_effects().
	 *
	 * @return false Always returns false — legacy signature preserved.
	 */
	function bboss_core_admin_maybe_save_pages_settings() {
		_deprecated_function(
			__FUNCTION__,
			'BuddyBoss 3.0.0',
			'bboss_appearance_pages_save_side_effects'
		);
		return false;
	}
}

if ( ! function_exists( 'bboss_registration_page_tutorial' ) ) {
	/**
	 * Render the legacy "View Tutorial" button on the Registration section.
	 *
	 * @since      BuddyBoss 1.5.8
	 * @deprecated BuddyBoss 3.0.0 Tutorial link removed — help_url args on section/panel replace the inline button.
	 */
	function bboss_registration_page_tutorial() {
		_deprecated_function(
			__FUNCTION__,
			'BuddyBoss 3.0.0',
			'help_url arg on bb_register_feature_section'
		);
	}
}

// ──────────────────────────────────────────────────────────────────────────────
// Settings 1.0 register-fields hook compatibility — remaining 3 hooks.
// Most legacy `*_register_fields` action hooks already have per-panel
// `do_action_deprecated()` shims earlier in this file (search, activity,
// messages, registration, media, video, document, groups, forums,
// notifications, moderation, general, friends, xprofile, and the bp_ prefix
// performance variant — each fires from its own
// `bb_{panel}_after_register_settings_fields` listener).
//
// The three hooks below were not covered by any existing shim:
//   - bb_admin_setting_reactions_register_fields  — fired from the reactions
//     feature settings file via the new bb_reactions_after_register_settings_fields hook.
//   - bb_admin_setting_performance_register_fields — bb_ prefix variant; the bp_
//     prefix variant is already shimmed in the bb_advanced listener block above.
//   - bb_admin_setting_labs_register_fields       — Labs panel was retired; no
//     specific bb_*_after_register_settings_fields hook exists, so we fire on
//     bb_register_features priority 99 as a fallback so any third-party
//     callbacks at least see the deprecation notice.
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Fire the legacy `bb_admin_setting_reactions_register_fields` hook after
 * Settings 2.0 finishes registering reactions fields.
 *
 * The original hook passed a `BB_Admin_Setting_Reactions` instance. Settings 2.0
 * no longer uses that class, so a no-op stub is passed to satisfy callbacks
 * that call add_section()/add_field() on the argument.
 *
 * @since BuddyBoss 1.7.0
 * @deprecated BuddyBoss 3.0.0 Use {@see 'bb_reactions_after_register_settings_fields'} instead.
 */
add_action(
	'bb_reactions_after_register_settings_fields',
	static function () {
		do_action_deprecated(
			'bb_admin_setting_reactions_register_fields',
			array(
				new class() {
					/**
					 * No-op stub for BBoss_Admin_Setting_tab::add_section().
					 *
					 * @param mixed ...$args Ignored.
					 */
					public function add_section( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found

					/**
					 * No-op stub for BBoss_Admin_Setting_tab::add_field().
					 *
					 * @param mixed ...$args Ignored.
					 */
					public function add_field( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
				},
			),
			'BuddyBoss 3.0.0',
			'bb_reactions_after_register_settings_fields'
		);
	}
);

/**
 * Fire the legacy `bb_admin_setting_performance_register_fields` hook (bb_ prefix
 * variant) after Settings 2.0 features register. The bp_ prefix variant is shimmed
 * in the bb_advanced listener above; this covers third-party code that hooked the
 * bb_ variant introduced for the Performance tab in 1.5.x.
 *
 * @since BuddyBoss 1.5.0
 * @deprecated BuddyBoss 3.0.0 Use {@see 'bb_advanced_after_register_settings_fields'} instead.
 */
add_action(
	'bb_register_features',
	static function () {
		if ( ! is_admin() ) {
			return;
		}
		do_action_deprecated(
			'bb_admin_setting_performance_register_fields',
			array(
				new class() {
					/**
					 * No-op stub.
					 *
					 * @param mixed ...$args Ignored.
					 */
					public function add_section( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found

					/**
					 * No-op stub.
					 *
					 * @param mixed ...$args Ignored.
					 */
					public function add_field( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
				},
			),
			'BuddyBoss 3.0.0',
			'bb_advanced_after_register_settings_fields'
		);
	},
	99
);

/**
 * Fire the legacy `bb_admin_setting_labs_register_fields` hook for backward
 * compatibility. The Labs panel was retired; no replacement hook exists, so we
 * fall back to bb_register_features priority 99 so third-party callbacks at
 * least see the deprecation notice.
 *
 * @since BuddyBoss 2.3.6
 * @deprecated BuddyBoss 3.0.0 Labs panel removed; no replacement.
 */
add_action(
	'bb_register_features',
	static function () {
		if ( ! is_admin() ) {
			return;
		}
		do_action_deprecated(
			'bb_admin_setting_labs_register_fields',
			array(
				new class() {
					/**
					 * No-op stub.
					 *
					 * @param mixed ...$args Ignored.
					 */
					public function add_section( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found

					/**
					 * No-op stub.
					 *
					 * @param mixed ...$args Ignored.
					 */
					public function add_field( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
				},
			),
			'BuddyBoss 3.0.0',
			''
		);
	},
	99
);

// ─────────────────────────────────────────────────────────────────────────
// Tools page (?page=bp-tools), Repair Community (?page=bp-repair-community),
// and Repair Forums (?page=bbp-repair) page renderers + form handlers
// retired in BuddyBoss 3.1.0. All three URLs now redirect to
// ?page=bb-settings&tab=tools&panel=repair_platform via
// bb_redirect_bp_settings_before_permission_check(). The function stubs
// below cover direct PHP callers (third-party code, legacy menu hooks).
// ─────────────────────────────────────────────────────────────────────────

if ( ! function_exists( 'bboss_repair_community_submenu_page' ) ) {
	/**
	 * Render the legacy Repair Community standalone submenu page.
	 *
	 * @since BuddyBoss 1.5.6
	 * @since BuddyBoss 3.1.0 Deprecated.
	 * @deprecated 3.1.0 admin.php?page=bb-settings&tab=tools&panel=repair_platform
	 */
	function bboss_repair_community_submenu_page() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.1.0', 'admin.php?page=bb-settings&tab=tools&panel=repair_platform' );
	}
}

if ( ! function_exists( 'bboss_bbp_admin_repair' ) ) {
	/**
	 * Render the legacy Forums Repair admin page.
	 *
	 * @since bbPress (r2613)
	 * @since BuddyBoss 3.1.0 Deprecated. Replaced by Settings 2.0 Repair Platform.
	 * @deprecated 3.1.0 admin.php?page=bb-settings&tab=tools&panel=repair_platform
	 */
	function bboss_bbp_admin_repair() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.1.0', 'admin.php?page=bb-settings&tab=tools&panel=repair_platform' );
	}
}

if ( ! function_exists( 'bboss_bbp_admin_repair_handler' ) ) {
	/**
	 * Process the legacy Forums Repair form POST submission.
	 *
	 * @since bbPress (r2613)
	 * @since BuddyBoss 3.1.0 Deprecated.
	 * @deprecated 3.1.0 wp_ajax_bp_admin_forum_repair_tools_wrapper_function
	 */
	function bboss_bbp_admin_repair_handler() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.1.0', 'wp_ajax_bp_admin_forum_repair_tools_wrapper_function' );
	}
}

if ( ! function_exists( 'bboss_bbp_admin_tools_feedback' ) ) {
	/**
	 * Render the legacy admin notice for the Forums Repair page.
	 *
	 * @since bbPress (r2613)
	 * @since BuddyBoss 3.1.0 Deprecated.
	 * @deprecated 3.1.0 Legacy admin-page-only helper.
	 *
	 * @param string $message Ignored.
	 * @param mixed  $class_name Ignored. Renamed from `$class` for PHP reserved-keyword compliance.
	 * @return false
	 */
	function bboss_bbp_admin_tools_feedback( $message, $class_name = false ) {
		unset( $message, $class_name );
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.1.0' );
		return false;
	}
}

if ( ! function_exists( 'bboss_core_admin_tools' ) ) {
	/**
	 * Render the legacy `?page=bp-tools` admin page (top-level Tools landing).
	 *
	 * @since BuddyPress 2.0.0
	 * @since BuddyBoss 3.1.0 Deprecated. Replaced by Settings 2.0 Tools panel.
	 * @deprecated 3.1.0 admin.php?page=bb-settings&tab=tools
	 */
	function bboss_core_admin_tools() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.1.0', 'admin.php?page=bb-settings&tab=tools' );
	}
}

if ( ! function_exists( 'bboss_admin_repair_handler' ) ) {
	/**
	 * Process POST submissions from the legacy Tools page repair form.
	 *
	 * @since BuddyPress 2.0.0
	 * @since BuddyBoss 3.1.0 Deprecated. Form-driven flow replaced by
	 *                              the React Repair Platform panel which calls
	 *                              `wp_ajax_bp_admin_repair_tools_wrapper_function`.
	 * @deprecated 3.1.0 wp_ajax_bp_admin_repair_tools_wrapper_function
	 */
	function bboss_admin_repair_handler() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.1.0', 'wp_ajax_bp_admin_repair_tools_wrapper_function' );
	}
}

if ( ! function_exists( 'bboss_core_tools_settings_admin_tabs' ) ) {
	/**
	 * Render the legacy Tools sub-tab bar (Default Data | Repair Community | …).
	 *
	 * @since BuddyPress 1.5.0
	 * @since BuddyBoss 3.1.0 Deprecated. Tab bar has no remaining live page.
	 * @deprecated 3.1.0 admin.php?page=bb-settings&tab=tools
	 *
	 * @param string $active_tab Ignored.
	 */
	function bboss_core_tools_settings_admin_tabs( $active_tab = '' ) {
		unset( $active_tab );
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.1.0', 'admin.php?page=bb-settings&tab=tools' );
	}
}

if ( ! function_exists( 'bboss_core_get_tools_settings_admin_tabs' ) ) {
	/**
	 * Return the legacy Tools sub-tab seed array.
	 *
	 * @since BuddyBoss 1.0.0
	 * @since BuddyBoss 3.1.0 Deprecated alongside the bp-tools tab bar.
	 * @deprecated 3.1.0 admin.php?page=bb-settings&tab=tools
	 *
	 * @param string $active_tab Ignored.
	 * @return array Empty array — no tabs remain.
	 */
	function bboss_core_get_tools_settings_admin_tabs( $active_tab = '' ) {
		unset( $active_tab );
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.1.0', 'admin.php?page=bb-settings&tab=tools' );
		return array();
	}
}

if ( ! function_exists( 'bboss_core_get_tools_repair_community_settings_admin_tabs' ) ) {
	/**
	 * Filter callback that appended the legacy "Repair Community" sub-tab.
	 *
	 * @since BuddyBoss 1.0.0
	 * @since BuddyBoss 3.1.0 Deprecated. Filter no longer fires anywhere.
	 * @deprecated 3.1.0 admin.php?page=bb-settings&tab=tools&panel=repair_platform
	 *
	 * @param array $tabs Existing tabs.
	 * @return array Tabs unchanged.
	 */
	function bboss_core_get_tools_repair_community_settings_admin_tabs( $tabs ) {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.1.0', 'admin.php?page=bb-settings&tab=tools&panel=repair_platform' );
		return $tabs;
	}
}

if ( ! function_exists( 'bboss_bbp_core_get_tools_settings_admin_tabs' ) ) {
	/**
	 * Filter callback that appended the legacy "Repair Forums" sub-tab.
	 *
	 * @since BuddyBoss 1.0.0
	 * @since BuddyBoss 3.1.0 Deprecated. Filter no longer fires anywhere.
	 * @deprecated 3.1.0 admin.php?page=bb-settings&tab=tools&panel=repair_platform
	 *
	 * @param array $tabs Existing tabs.
	 * @return array Tabs unchanged.
	 */
	function bboss_bbp_core_get_tools_settings_admin_tabs( $tabs ) {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.1.0', 'admin.php?page=bb-settings&tab=tools&panel=repair_platform' );
		return $tabs;
	}
}

if ( ! function_exists( 'bboss_bbp_core_get_import_forum_tools_settings_admin_tabs' ) ) {
	/**
	 * Filter callback that appended the legacy "Forum Import" sub-tab.
	 *
	 * The Forum Converter (bbPress importer) was extracted to the
	 * buddyboss-tools plugin and now renders inside the Settings 2.0
	 * Migration Tools panel, so this filter no longer fires anywhere.
	 *
	 * @since BuddyBoss 1.0.0
	 * @since BuddyBoss 3.1.0 Deprecated. Filter no longer fires anywhere.
	 * @deprecated 3.1.0 admin.php?page=bb-settings&tab=tools&panel=migration_tools
	 *
	 * @param array $tabs Existing tabs.
	 * @return array Tabs unchanged.
	 */
	function bboss_bbp_core_get_import_forum_tools_settings_admin_tabs( $tabs ) {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.1.0', 'admin.php?page=bb-settings&tab=tools&panel=migration_tools' );
		return $tabs;
	}
}

if ( ! function_exists( 'bboss_bbp_converter_settings' ) ) {
	/**
	 * Rendered the legacy Forum Converter settings section.
	 *
	 * The Forum Converter (bbPress importer) was extracted to the
	 * buddyboss-tools plugin and now renders inside the Settings 2.0
	 * Migration Tools panel.
	 *
	 * @since BuddyBoss 2.0.0
	 * @since BuddyBoss 3.1.0 Deprecated. Converter moved to buddyboss-tools.
	 * @deprecated 3.1.0 admin.php?page=bb-settings&tab=tools&panel=migration_tools
	 */
	function bboss_bbp_converter_settings() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.1.0', 'admin.php?page=bb-settings&tab=tools&panel=migration_tools' );
	}
}

if ( ! function_exists( 'bboss_media_get_tools_media_settings_admin_tabs' ) ) {
	/**
	 * Filter callback that appended the legacy "Import Media" sub-tab.
	 *
	 * @since BuddyBoss 1.0.0
	 * @since BuddyBoss 3.1.0 Deprecated. Filter no longer fires anywhere.
	 *                              Import Media remains reachable at its own
	 *                              standalone admin page (`?page=bp-media-import`).
	 * @deprecated 3.1.0 admin.php?page=bp-media-import
	 *
	 * @param array $tabs Existing tabs.
	 * @return array Tabs unchanged.
	 */
	function bboss_media_get_tools_media_settings_admin_tabs( $tabs ) {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.1.0', 'admin.php?page=bp-media-import' );
		return $tabs;
	}
}

/**
 * Fire `_deprecated_hook()` notices for the three retired Tools-page hooks
 * (two filters + one action) when any third-party callback is registered.
 *
 * The calling functions (`bboss_core_get_tools_settings_admin_tabs()` and
 * `bboss_core_tools_settings_admin_tabs()`) no longer fire these hooks because
 * the legacy `?page=bp-tools` tab bar is gone. Third-party plugins that had
 * `add_filter()` / `add_action()` callbacks on them would otherwise sit
 * dormant with no signal that their integration broke. This hook-deprecation
 * shim runs once per admin pageload (~3 µs when no callbacks are registered)
 * and points the developer at the React feature-registry replacement.
 *
 * @since BuddyBoss 3.1.0
 *
 * @return void
 */
function bboss_deprecated_tools_admin_tabs_hooks_notice() {
	$retired = array(
		'bp_core_get_tools_settings_admin_tabs' => "bb_register_side_panel( 'tools', \$panel_id, \$args ) on the bb_after_register_features action",
		'bp_core_tools_settings_admin_tabs'     => "bb_register_side_panel( 'tools', \$panel_id, \$args ) on the bb_after_register_features action",
		'bp_tools_settings_admin_tabs'          => 'bb_after_register_features',
	);

	foreach ( $retired as $hook => $replacement ) {
		if ( has_filter( $hook ) || has_action( $hook ) ) {
			_deprecated_hook( esc_html( $hook ), 'BuddyBoss 3.1.0', esc_html( $replacement ) );
		}
	}
}
add_action( 'admin_init', 'bb_deprecated_tools_admin_tabs_hooks_notice' );

// ─────────────────────────────────────────────────────────────────────────
// Default Data (bp_dd_*) family — moved to the buddyboss-tools plugin in
// BuddyBoss 3.1.0. Every legacy bp_dd_* function gets a wrapper here
// that delegates to its bb_tools_dd_* counterpart when the addon is active
// and no-ops with the original return type when the addon is inactive.
//
// `bp_admin_tools_default_data_save()` is included too — it shares the same
// retirement schedule even though its name doesn't carry the bp_dd_ prefix.
//
// Return-type buckets below reflect ACTUAL legacy behavior (verified during
// Phase 2 inventory). Removal scheduled BBVERSION + 2 per project
// deprecation convention.
// ─────────────────────────────────────────────────────────────────────────

// `BP_DEFAULT_DATA_DIR` / `BP_DEFAULT_DATA_URL` were defined inside the
// legacy `bp_core_admin_tools()` page renderer (bboss-core-admin-tools.php
// lines 18-25). Task 2.10 deletes that define block. Third-party code
// reading either constant directly would hit "Use of undefined constant" —
// notice on PHP 7.4, fatal on PHP 8.x. Define empty-string fallbacks so
// callers degrade gracefully. The fixture data the constants pointed at
// moved to buddyboss-tools/sample-data/data/.
if ( ! defined( 'BBOSS_DEFAULT_DATA_DIR' ) ) {
	define( 'BBOSS_DEFAULT_DATA_DIR', '' );
}
if ( ! defined( 'BBOSS_DEFAULT_DATA_URL' ) ) {
	define( 'BBOSS_DEFAULT_DATA_URL', '' );
}

/* ─── Returns void (no return statement; some echo or call side-effects) ─── */

if ( ! function_exists( 'bboss_admin_tools_default_data_save' ) ) {
	/**
	 * Deprecated. Legacy Default-Data form-save handler.
	 *
	 * The Default Data form-post path was retired in BuddyBoss 3.1.0 —
	 * the React Sample Data panel in buddyboss-tools posts AJAX directly via
	 * `wp_ajax_bb_tools_sample_data_import` instead. This wrapper preserves
	 * the legacy name for third-party code that may post to a re-created
	 * form, delegating to `bb_tools_dd_admin_default_data_save()` (in
	 * buddyboss-tools) when the addon is active.
	 *
	 * @since BuddyBoss 1.0.0
	 * @since BuddyBoss 3.1.0 Deprecated. Moved to buddyboss-tools plugin.
	 * @deprecated 3.1.0 bb_tools_dd_admin_default_data_save (in buddyboss-tools)
	 *
	 * @return void
	 */
	function bboss_admin_tools_default_data_save() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.1.0', 'bb_tools_dd_admin_default_data_save' );
		if ( function_exists( 'bb_tools_dd_admin_default_data_save' ) ) {
			bb_tools_dd_admin_default_data_save();
		}
	}
}

if ( ! function_exists( 'bboss_dd_clear_db' ) ) {
	/**
	 * @since BuddyBoss 1.0.0
	 * @since BuddyBoss 3.1.0 Deprecated. Moved to buddyboss-tools plugin.
	 * @deprecated 3.1.0 bb_tools_dd_clear_db (in buddyboss-tools)
	 *
	 * @return void
	 */
	function bboss_dd_clear_db() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.1.0', 'bb_tools_dd_clear_db' );
		if ( function_exists( 'bb_tools_dd_clear_db' ) ) {
			bb_tools_dd_clear_db();
		}
	}
}

if ( ! function_exists( 'bboss_dd_delete_dummy_members_related_data' ) ) {
	/**
	 * @since BuddyBoss 1.0.0
	 * @since BuddyBoss 3.1.0 Deprecated. Moved to buddyboss-tools plugin.
	 * @deprecated 3.1.0 bb_tools_dd_delete_dummy_members_related_data (in buddyboss-tools)
	 *
	 * @return void
	 */
	function bboss_dd_delete_dummy_members_related_data() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.1.0', 'bb_tools_dd_delete_dummy_members_related_data' );
		if ( function_exists( 'bb_tools_dd_delete_dummy_members_related_data' ) ) {
			bb_tools_dd_delete_dummy_members_related_data();
		}
	}
}

if ( ! function_exists( 'bboss_dd_delete_dummy_members' ) ) {
	/**
	 * @since BuddyBoss 1.0.0
	 * @since BuddyBoss 3.1.0 Deprecated. Moved to buddyboss-tools plugin.
	 * @deprecated 3.1.0 bb_tools_dd_delete_dummy_members (in buddyboss-tools)
	 *
	 * @return void
	 */
	function bboss_dd_delete_dummy_members() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.1.0', 'bb_tools_dd_delete_dummy_members' );
		if ( function_exists( 'bb_tools_dd_delete_dummy_members' ) ) {
			bb_tools_dd_delete_dummy_members();
		}
	}
}

if ( ! function_exists( 'bboss_dd_delete_dummy_reply' ) ) {
	/**
	 * @since BuddyBoss 1.0.0
	 * @since BuddyBoss 3.1.0 Deprecated. Moved to buddyboss-tools plugin.
	 * @deprecated 3.1.0 bb_tools_dd_delete_dummy_reply (in buddyboss-tools)
	 *
	 * @return void
	 */
	function bboss_dd_delete_dummy_reply() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.1.0', 'bb_tools_dd_delete_dummy_reply' );
		if ( function_exists( 'bb_tools_dd_delete_dummy_reply' ) ) {
			bb_tools_dd_delete_dummy_reply();
		}
	}
}

if ( ! function_exists( 'bboss_dd_delete_dummy_topic' ) ) {
	/**
	 * @since BuddyBoss 1.0.0
	 * @since BuddyBoss 3.1.0 Deprecated. Moved to buddyboss-tools plugin.
	 * @deprecated 3.1.0 bb_tools_dd_delete_dummy_topic (in buddyboss-tools)
	 *
	 * @return void
	 */
	function bboss_dd_delete_dummy_topic() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.1.0', 'bb_tools_dd_delete_dummy_topic' );
		if ( function_exists( 'bb_tools_dd_delete_dummy_topic' ) ) {
			bb_tools_dd_delete_dummy_topic();
		}
	}
}

if ( ! function_exists( 'bboss_dd_delete_dummy_forum' ) ) {
	/**
	 * @since BuddyBoss 1.0.0
	 * @since BuddyBoss 3.1.0 Deprecated. Moved to buddyboss-tools plugin.
	 * @deprecated 3.1.0 bb_tools_dd_delete_dummy_forum (in buddyboss-tools)
	 *
	 * @return void
	 */
	function bboss_dd_delete_dummy_forum() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.1.0', 'bb_tools_dd_delete_dummy_forum' );
		if ( function_exists( 'bb_tools_dd_delete_dummy_forum' ) ) {
			bb_tools_dd_delete_dummy_forum();
		}
	}
}

if ( ! function_exists( 'bboss_dd_delete_dummy_groups' ) ) {
	/**
	 * @since BuddyBoss 1.0.0
	 * @since BuddyBoss 3.1.0 Deprecated. Moved to buddyboss-tools plugin.
	 * @deprecated 3.1.0 bb_tools_dd_delete_dummy_groups (in buddyboss-tools)
	 *
	 * @return void
	 */
	function bboss_dd_delete_dummy_groups() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.1.0', 'bb_tools_dd_delete_dummy_groups' );
		if ( function_exists( 'bb_tools_dd_delete_dummy_groups' ) ) {
			bb_tools_dd_delete_dummy_groups();
		}
	}
}

if ( ! function_exists( 'bboss_dd_delete_dummy_xprofile' ) ) {
	/**
	 * @since BuddyBoss 1.0.0
	 * @since BuddyBoss 3.1.0 Deprecated. Moved to buddyboss-tools plugin.
	 * @deprecated 3.1.0 bb_tools_dd_delete_dummy_xprofile (in buddyboss-tools)
	 *
	 * @return void
	 */
	function bboss_dd_delete_dummy_xprofile() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.1.0', 'bb_tools_dd_delete_dummy_xprofile' );
		if ( function_exists( 'bb_tools_dd_delete_dummy_xprofile' ) ) {
			bb_tools_dd_delete_dummy_xprofile();
		}
	}
}

if ( ! function_exists( 'bboss_dd_imported_disabled' ) ) {
	/**
	 * Legacy echoed `disabled="disabled" checked="checked"` attrs for the
	 * Default Data form inputs. Form is retired in BuddyBoss 3.1.0; the
	 * React Sample Data panel reads import state via AJAX instead.
	 *
	 * @since BuddyBoss 1.0.0
	 * @since BuddyBoss 3.1.0 Deprecated. Moved to buddyboss-tools plugin.
	 * @deprecated 3.1.0 bb_tools_dd_imported_disabled (in buddyboss-tools)
	 *
	 * @param string $group  Possible values: users, groups, forums.
	 * @param string $import What exactly was imported.
	 * @return void
	 */
	function bboss_dd_imported_disabled( $group, $import ) {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.1.0', 'bb_tools_dd_imported_disabled' );
		if ( function_exists( 'bb_tools_dd_imported_disabled' ) ) {
			bb_tools_dd_imported_disabled( $group, $import );
		}
	}
}

if ( ! function_exists( 'bboss_dd_delete_import_records' ) ) {
	/**
	 * @since BuddyBoss 1.0.0
	 * @since BuddyBoss 3.1.0 Deprecated. Moved to buddyboss-tools plugin.
	 * @deprecated 3.1.0 bb_tools_dd_delete_import_records (in buddyboss-tools)
	 *
	 * @return void
	 */
	function bboss_dd_delete_import_records() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.1.0', 'bb_tools_dd_delete_import_records' );
		if ( function_exists( 'bb_tools_dd_delete_import_records' ) ) {
			bb_tools_dd_delete_import_records();
		}
	}
}

/* ─── Returns array (fallback: empty array) ─── */

if ( ! function_exists( 'bboss_dd_get_random_groups_ids' ) ) {
	/**
	 * @since BuddyBoss 1.0.0
	 * @since BuddyBoss 3.1.0 Deprecated. Moved to buddyboss-tools plugin.
	 * @deprecated 3.1.0 bb_tools_dd_get_random_groups_ids (in buddyboss-tools)
	 *
	 * @param int    $count  If you need all, use 0.
	 * @param string $output 'array' or 'string' (comma-separated).
	 * @return array|string
	 */
	function bboss_dd_get_random_groups_ids( $count = 1, $output = 'array' ) {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.1.0', 'bb_tools_dd_get_random_groups_ids' );
		if ( function_exists( 'bb_tools_dd_get_random_groups_ids' ) ) {
			return bb_tools_dd_get_random_groups_ids( $count, $output );
		}
		return 'string' === $output ? '' : array();
	}
}

if ( ! function_exists( 'bboss_dd_get_forums_enable_groups_ids' ) ) {
	/**
	 * @since BuddyBoss 1.0.0
	 * @since BuddyBoss 3.1.0 Deprecated. Moved to buddyboss-tools plugin.
	 * @deprecated 3.1.0 bb_tools_dd_get_forums_enable_groups_ids (in buddyboss-tools)
	 *
	 * @param int    $count  Required — legacy signature has no default.
	 * @param string $output 'array' or 'string' (comma-separated).
	 * @return array|string
	 */
	function bboss_dd_get_forums_enable_groups_ids( $count, $output = 'array' ) {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.1.0', 'bb_tools_dd_get_forums_enable_groups_ids' );
		if ( function_exists( 'bb_tools_dd_get_forums_enable_groups_ids' ) ) {
			return bb_tools_dd_get_forums_enable_groups_ids( $count, $output );
		}
		return 'string' === $output ? '' : array();
	}
}

if ( ! function_exists( 'bboss_dd_get_random_forums_ids' ) ) {
	/**
	 * @since BuddyBoss 1.0.0
	 * @since BuddyBoss 3.1.0 Deprecated. Moved to buddyboss-tools plugin.
	 * @deprecated 3.1.0 bb_tools_dd_get_random_forums_ids (in buddyboss-tools)
	 *
	 * @param int    $count  If you need all, use 0.
	 * @param string $output 'array' or 'string' (comma-separated).
	 * @return array|string
	 */
	function bboss_dd_get_random_forums_ids( $count = 1, $output = 'array' ) {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.1.0', 'bb_tools_dd_get_random_forums_ids' );
		if ( function_exists( 'bb_tools_dd_get_random_forums_ids' ) ) {
			return bb_tools_dd_get_random_forums_ids( $count, $output );
		}
		return 'string' === $output ? '' : array();
	}
}

if ( ! function_exists( 'bboss_dd_get_random_topics_ids' ) ) {
	/**
	 * @since BuddyBoss 1.0.0
	 * @since BuddyBoss 3.1.0 Deprecated. Moved to buddyboss-tools plugin.
	 * @deprecated 3.1.0 bb_tools_dd_get_random_topics_ids (in buddyboss-tools)
	 *
	 * @param int    $count  If you need all, use 0.
	 * @param string $output 'array' or 'string' (comma-separated).
	 * @return array|string
	 */
	function bboss_dd_get_random_topics_ids( $count = 1, $output = 'array' ) {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.1.0', 'bb_tools_dd_get_random_topics_ids' );
		if ( function_exists( 'bb_tools_dd_get_random_topics_ids' ) ) {
			return bb_tools_dd_get_random_topics_ids( $count, $output );
		}
		return 'string' === $output ? '' : array();
	}
}

if ( ! function_exists( 'bboss_dd_get_random_users_ids' ) ) {
	/**
	 * @since BuddyBoss 1.0.0
	 * @since BuddyBoss 3.1.0 Deprecated. Moved to buddyboss-tools plugin.
	 * @deprecated 3.1.0 bb_tools_dd_get_random_users_ids (in buddyboss-tools)
	 *
	 * @param int    $count  If you need all, use 0.
	 * @param string $output 'array' or 'string' (comma-separated).
	 * @return array|string
	 */
	function bboss_dd_get_random_users_ids( $count = 1, $output = 'array' ) {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.1.0', 'bb_tools_dd_get_random_users_ids' );
		if ( function_exists( 'bb_tools_dd_get_random_users_ids' ) ) {
			return bb_tools_dd_get_random_users_ids( $count, $output );
		}
		return 'string' === $output ? '' : array();
	}
}

if ( ! function_exists( 'bboss_dd_import_users' ) ) {
	/**
	 * @since BuddyBoss 1.0.0
	 * @since BuddyBoss 3.1.0 Deprecated. Moved to buddyboss-tools plugin.
	 * @deprecated 3.1.0 bb_tools_dd_import_users (in buddyboss-tools)
	 *
	 * @return array Imported user IDs — empty when Tools inactive.
	 */
	function bboss_dd_import_users() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.1.0', 'bb_tools_dd_import_users' );
		if ( function_exists( 'bb_tools_dd_import_users' ) ) {
			return bb_tools_dd_import_users();
		}
		return array();
	}
}

if ( ! function_exists( 'bboss_dd_import_users_messages' ) ) {
	/**
	 * @since BuddyBoss 1.0.0
	 * @since BuddyBoss 3.1.0 Deprecated. Moved to buddyboss-tools plugin.
	 * @deprecated 3.1.0 bb_tools_dd_import_users_messages (in buddyboss-tools)
	 *
	 * @return array Imported message IDs — empty when Tools inactive.
	 */
	function bboss_dd_import_users_messages() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.1.0', 'bb_tools_dd_import_users_messages' );
		if ( function_exists( 'bb_tools_dd_import_users_messages' ) ) {
			return bb_tools_dd_import_users_messages();
		}
		return array();
	}
}

if ( ! function_exists( 'bboss_dd_import_groups' ) ) {
	/**
	 * @since BuddyBoss 1.0.0
	 * @since BuddyBoss 3.1.0 Deprecated. Moved to buddyboss-tools plugin.
	 * @deprecated 3.1.0 bb_tools_dd_import_groups (in buddyboss-tools)
	 *
	 * @param bool|array $users Users list (defaults to currently registered users).
	 * @return array Group IDs — empty when Tools inactive.
	 */
	function bboss_dd_import_groups( $users = false ) {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.1.0', 'bb_tools_dd_import_groups' );
		if ( function_exists( 'bb_tools_dd_import_groups' ) ) {
			return bb_tools_dd_import_groups( $users );
		}
		return array();
	}
}

if ( ! function_exists( 'bboss_dd_import_groups_members' ) ) {
	/**
	 * @since BuddyBoss 1.0.0
	 * @since BuddyBoss 3.1.0 Deprecated. Moved to buddyboss-tools plugin.
	 * @deprecated 3.1.0 bb_tools_dd_import_groups_members (in buddyboss-tools)
	 *
	 * @param bool|array $groups Groups list (defaults to random imported groups).
	 * @return array Group IDs joined — empty when Tools inactive.
	 */
	function bboss_dd_import_groups_members( $groups = false ) {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.1.0', 'bb_tools_dd_import_groups_members' );
		if ( function_exists( 'bb_tools_dd_import_groups_members' ) ) {
			return bb_tools_dd_import_groups_members( $groups );
		}
		return array();
	}
}

if ( ! function_exists( 'bboss_dd_import_forums' ) ) {
	/**
	 * @since BuddyBoss 1.0.0
	 * @since BuddyBoss 3.1.0 Deprecated. Moved to buddyboss-tools plugin.
	 * @deprecated 3.1.0 bb_tools_dd_import_forums (in buddyboss-tools)
	 *
	 * @param bool|array $users Users list (defaults to random imported users).
	 * @return array Forum IDs — empty when Tools inactive.
	 */
	function bboss_dd_import_forums( $users = false ) {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.1.0', 'bb_tools_dd_import_forums' );
		if ( function_exists( 'bb_tools_dd_import_forums' ) ) {
			return bb_tools_dd_import_forums( $users );
		}
		return array();
	}
}

if ( ! function_exists( 'bboss_dd_import_forums_topics' ) ) {
	/**
	 * @since BuddyBoss 1.0.0
	 * @since BuddyBoss 3.1.0 Deprecated. Moved to buddyboss-tools plugin.
	 * @deprecated 3.1.0 bb_tools_dd_import_forums_topics (in buddyboss-tools)
	 *
	 * @param bool|array $forums Forum IDs list (defaults to random imported forums).
	 * @return array Topic IDs — empty when Tools inactive.
	 */
	function bboss_dd_import_forums_topics( $forums = false ) {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.1.0', 'bb_tools_dd_import_forums_topics' );
		if ( function_exists( 'bb_tools_dd_import_forums_topics' ) ) {
			return bb_tools_dd_import_forums_topics( $forums );
		}
		return array();
	}
}

if ( ! function_exists( 'bboss_dd_import_forums_topics_replies' ) ) {
	/**
	 * @since BuddyBoss 1.0.0
	 * @since BuddyBoss 3.1.0 Deprecated. Moved to buddyboss-tools plugin.
	 * @deprecated 3.1.0 bb_tools_dd_import_forums_topics_replies (in buddyboss-tools)
	 *
	 * @param bool|array $topics Topic IDs list (defaults to random imported topics).
	 * @return array Reply IDs — empty when Tools inactive.
	 */
	function bboss_dd_import_forums_topics_replies( $topics = false ) {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.1.0', 'bb_tools_dd_import_forums_topics_replies' );
		if ( function_exists( 'bb_tools_dd_import_forums_topics_replies' ) ) {
			return bb_tools_dd_import_forums_topics_replies( $topics );
		}
		return array();
	}
}

if ( ! function_exists( 'bboss_dd_import_forums_in_groups' ) ) {
	/**
	 * @since BuddyBoss 1.0.0
	 * @since BuddyBoss 3.1.0 Deprecated. Moved to buddyboss-tools plugin.
	 * @deprecated 3.1.0 bb_tools_dd_import_forums_in_groups (in buddyboss-tools)
	 *
	 * @return array Group IDs that received forums — empty when Tools inactive.
	 */
	function bboss_dd_import_forums_in_groups() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.1.0', 'bb_tools_dd_import_forums_in_groups' );
		if ( function_exists( 'bb_tools_dd_import_forums_in_groups' ) ) {
			return bb_tools_dd_import_forums_in_groups();
		}
		return array();
	}
}

/* ─── Returns int (fallback: 0) ─── */

if ( ! function_exists( 'bboss_dd_get_time' ) ) {
	/**
	 * @since BuddyBoss 1.0.0
	 * @since BuddyBoss 3.1.0 Deprecated. Moved to buddyboss-tools plugin.
	 * @deprecated 3.1.0 bb_tools_dd_get_time (in buddyboss-tools)
	 *
	 * @return int Current blog timestamp — 0 when Tools inactive.
	 */
	function bboss_dd_get_time() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.1.0', 'bb_tools_dd_get_time' );
		if ( function_exists( 'bb_tools_dd_get_time' ) ) {
			return bb_tools_dd_get_time();
		}
		return 0;
	}
}

if ( ! function_exists( 'bboss_dd_import_users_profile' ) ) {
	/**
	 * @since BuddyBoss 1.0.0
	 * @since BuddyBoss 3.1.0 Deprecated. Moved to buddyboss-tools plugin.
	 * @deprecated 3.1.0 bb_tools_dd_import_users_profile (in buddyboss-tools)
	 *
	 * @return int Profile field entries created — 0 when Tools inactive.
	 */
	function bboss_dd_import_users_profile() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.1.0', 'bb_tools_dd_import_users_profile' );
		if ( function_exists( 'bb_tools_dd_import_users_profile' ) ) {
			return bb_tools_dd_import_users_profile();
		}
		return 0;
	}
}

if ( ! function_exists( 'bboss_dd_import_users_activity' ) ) {
	/**
	 * @since BuddyBoss 1.0.0
	 * @since BuddyBoss 3.1.0 Deprecated. Moved to buddyboss-tools plugin.
	 * @deprecated 3.1.0 bb_tools_dd_import_users_activity (in buddyboss-tools)
	 *
	 * @return int Activity items created — 0 when Tools inactive.
	 */
	function bboss_dd_import_users_activity() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.1.0', 'bb_tools_dd_import_users_activity' );
		if ( function_exists( 'bb_tools_dd_import_users_activity' ) ) {
			return bb_tools_dd_import_users_activity();
		}
		return 0;
	}
}

if ( ! function_exists( 'bboss_dd_import_users_friends' ) ) {
	/**
	 * @since BuddyBoss 1.0.0
	 * @since BuddyBoss 3.1.0 Deprecated. Moved to buddyboss-tools plugin.
	 * @deprecated 3.1.0 bb_tools_dd_import_users_friends (in buddyboss-tools)
	 *
	 * @return int Friendships created — 0 when Tools inactive.
	 */
	function bboss_dd_import_users_friends() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.1.0', 'bb_tools_dd_import_users_friends' );
		if ( function_exists( 'bb_tools_dd_import_users_friends' ) ) {
			return bb_tools_dd_import_users_friends();
		}
		return 0;
	}
}

if ( ! function_exists( 'bboss_dd_import_groups_activity' ) ) {
	/**
	 * @since BuddyBoss 1.0.0
	 * @since BuddyBoss 3.1.0 Deprecated. Moved to buddyboss-tools plugin.
	 * @deprecated 3.1.0 bb_tools_dd_import_groups_activity (in buddyboss-tools)
	 *
	 * @return int Group activity items created — 0 when Tools inactive.
	 */
	function bboss_dd_import_groups_activity() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.1.0', 'bb_tools_dd_import_groups_activity' );
		if ( function_exists( 'bb_tools_dd_import_groups_activity' ) ) {
			return bb_tools_dd_import_groups_activity();
		}
		return 0;
	}
}

/* ─── Returns bool (fallback: false) ─── */

if ( ! function_exists( 'bboss_dd_is_imported' ) ) {
	/**
	 * @since BuddyBoss 1.0.0
	 * @since BuddyBoss 3.1.0 Deprecated. Moved to buddyboss-tools plugin.
	 * @deprecated 3.1.0 bb_tools_dd_is_imported (in buddyboss-tools)
	 *
	 * @param string $group  Possible values: users, groups, forums.
	 * @param string $import What exactly was imported.
	 * @return bool
	 */
	function bboss_dd_is_imported( $group, $import ) {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.1.0', 'bb_tools_dd_is_imported' );
		if ( function_exists( 'bb_tools_dd_is_imported' ) ) {
			return bb_tools_dd_is_imported( $group, $import );
		}
		return false;
	}
}

if ( ! function_exists( 'bboss_dd_update_import' ) ) {
	/**
	 * @since BuddyBoss 1.0.0
	 * @since BuddyBoss 3.1.0 Deprecated. Moved to buddyboss-tools plugin.
	 * @deprecated 3.1.0 bb_tools_dd_update_import (in buddyboss-tools)
	 *
	 * @param string $group  Possible values: users, groups, forums.
	 * @param string $import What exactly was imported.
	 * @return bool Result of `bboss_update_option()`.
	 */
	function bboss_dd_update_import( $group, $import ) {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.1.0', 'bb_tools_dd_update_import' );
		if ( function_exists( 'bb_tools_dd_update_import' ) ) {
			return bb_tools_dd_update_import( $group, $import );
		}
		return false;
	}
}

if ( ! function_exists( 'bboss_dd_create_forums' ) ) {
	/**
	 * @since BuddyBoss 1.0.0
	 * @since BuddyBoss 3.1.0 Deprecated. Moved to buddyboss-tools plugin.
	 * @deprecated 3.1.0 bb_tools_dd_create_forums (in buddyboss-tools)
	 *
	 * @param array $forum Forum data.
	 * @param array $users Optional creators list.
	 * @return bool|int|\WP_Error Forum ID on success, false when Tools inactive.
	 */
	function bboss_dd_create_forums( $forum, $users = array() ) {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.1.0', 'bb_tools_dd_create_forums' );
		if ( function_exists( 'bb_tools_dd_create_forums' ) ) {
			return bb_tools_dd_create_forums( $forum, $users );
		}
		return false;
	}
}

if ( ! function_exists( 'bboss_dd_create_forums_topics' ) ) {
	/**
	 * @since BuddyBoss 1.0.0
	 * @since BuddyBoss 3.1.0 Deprecated. Moved to buddyboss-tools plugin.
	 * @deprecated 3.1.0 bb_tools_dd_create_forums_topics (in buddyboss-tools)
	 *
	 * @param array $topic_data Topic data.
	 * @param int   $forum_id   Parent forum ID.
	 * @param array $users      Optional creators list.
	 * @return bool|int|\WP_Error Topic ID on success, false when Tools inactive.
	 */
	function bboss_dd_create_forums_topics( $topic_data, $forum_id, $users = array() ) {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.1.0', 'bb_tools_dd_create_forums_topics' );
		if ( function_exists( 'bb_tools_dd_create_forums_topics' ) ) {
			return bb_tools_dd_create_forums_topics( $topic_data, $forum_id, $users );
		}
		return false;
	}
}

if ( ! function_exists( 'bboss_dd_create_forums_topics_replies' ) ) {
	/**
	 * @since BuddyBoss 1.0.0
	 * @since BuddyBoss 3.1.0 Deprecated. Moved to buddyboss-tools plugin.
	 * @deprecated 3.1.0 bb_tools_dd_create_forums_topics_replies (in buddyboss-tools)
	 *
	 * @param string $reply_data Reply content.
	 * @param int    $topic_id   Parent topic ID.
	 * @param array  $users      Optional creators list.
	 * @return bool|int|\WP_Error Reply ID on success, false when Tools inactive.
	 */
	function bboss_dd_create_forums_topics_replies( $reply_data, $topic_id, $users = array() ) {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.1.0', 'bb_tools_dd_create_forums_topics_replies' );
		if ( function_exists( 'bb_tools_dd_create_forums_topics_replies' ) ) {
			return bb_tools_dd_create_forums_topics_replies( $reply_data, $topic_id, $users );
		}
		return false;
	}
}

/* ─── Returns string (fallback: '') ─── */

if ( ! function_exists( 'bboss_dd_get_random_date' ) ) {
	/**
	 * @since BuddyBoss 1.0.0
	 * @since BuddyBoss 3.1.0 Deprecated. Moved to buddyboss-tools plugin.
	 * @deprecated 3.1.0 bb_tools_dd_get_random_date (in buddyboss-tools)
	 *
	 * @param int $days_from
	 * @param int $days_to
	 * @return string Y-m-d H:i:s formatted date — empty string when Tools inactive.
	 */
	function bboss_dd_get_random_date( $days_from = 30, $days_to = 0 ) {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.1.0', 'bb_tools_dd_get_random_date' );
		if ( function_exists( 'bb_tools_dd_get_random_date' ) ) {
			return bb_tools_dd_get_random_date( $days_from, $days_to );
		}
		return '';
	}
}

if ( ! function_exists( 'bboss_dd_get_root_admin_page' ) ) {
	/**
	 * @since BuddyBoss 1.0.0
	 * @since BuddyBoss 3.1.0 Deprecated. Moved to buddyboss-tools plugin.
	 * @deprecated 3.1.0 bb_tools_dd_get_root_admin_page (in buddyboss-tools)
	 *
	 * @return string Settings 2.0 Sample Data URL — empty string when Tools inactive.
	 */
	function bboss_dd_get_root_admin_page() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.1.0', 'bb_tools_dd_get_root_admin_page' );
		if ( function_exists( 'bb_tools_dd_get_root_admin_page' ) ) {
			return bb_tools_dd_get_root_admin_page();
		}
		return '';
	}
}

/* ─── Filter-callback passthroughs (return the input arg unchanged when inactive) ─── */

if ( ! function_exists( 'bboss_dd_groups_join_group_date_fix' ) ) {
	/**
	 * @since BuddyBoss 1.0.0
	 * @since BuddyBoss 3.1.0 Deprecated. Moved to buddyboss-tools plugin.
	 * @deprecated 3.1.0 bb_tools_dd_groups_join_group_date_fix (in buddyboss-tools)
	 *
	 * @param array $args bboss_activity_add() args.
	 * @return array
	 */
	function bboss_dd_groups_join_group_date_fix( $args ) {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.1.0', 'bb_tools_dd_groups_join_group_date_fix' );
		if ( function_exists( 'bb_tools_dd_groups_join_group_date_fix' ) ) {
			return bb_tools_dd_groups_join_group_date_fix( $args );
		}
		return $args;
	}
}

if ( ! function_exists( 'bboss_dd_friends_add_friend_date_fix' ) ) {
	/**
	 * @since BuddyBoss 1.0.0
	 * @since BuddyBoss 3.1.0 Deprecated. Moved to buddyboss-tools plugin.
	 * @deprecated 3.1.0 bb_tools_dd_friends_add_friend_date_fix (in buddyboss-tools)
	 *
	 * @param string $current_time Default BuddyBoss current timestamp.
	 * @return string
	 */
	function bboss_dd_friends_add_friend_date_fix( $current_time ) {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.1.0', 'bb_tools_dd_friends_add_friend_date_fix' );
		if ( function_exists( 'bb_tools_dd_friends_add_friend_date_fix' ) ) {
			return bb_tools_dd_friends_add_friend_date_fix( $current_time );
		}
		return $current_time;
	}
}

// ─────────────────────────────────────────────────────────────────────────
// Profile Types (bp_member_type_import_*, bp_import_profile_types_*,
// bp_register_member_type_import_submenu_page,
// bp_core_get_tools_import_profile_settings_admin_tabs) — moved to the
// buddyboss-tools plugin's `migration/profile-types/` folder in BuddyBoss
// 3.1.0. The legacy `?page=bp-member-type-import` admin page is
// retired; 301 redirect lives in bboss-core-admin-actions.php.
//
// Removal scheduled BBVERSION + 2 per project deprecation convention.
// See PHASE-3-INVENTORY.md (gitignored) for the legacy code audit.
// ─────────────────────────────────────────────────────────────────────────

if ( ! function_exists( 'bboss_register_member_type_import_submenu_page' ) ) {
	/**
	 * Deprecated stub for the hidden Profile Types submenu wrapper.
	 *
	 * @since BuddyBoss 1.0.0
	 * @since BuddyBoss 3.1.0 Deprecated. Hidden submenu wrapper retired alongside the Profile Types page.
	 * @deprecated BuddyBoss 3.1.0
	 *
	 * @return void
	 */
	function bboss_register_member_type_import_submenu_page() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.1.0', 'Migration Tools panel in buddyboss-tools' );
	}
}

if ( ! function_exists( 'bboss_member_type_import_submenu_page' ) ) {
	/**
	 * Deprecated stub for the Profile Types admin page renderer + POST handler.
	 *
	 * @since BuddyBoss 1.0.0
	 * @since BuddyBoss 3.1.0 Deprecated. Moved to buddyboss-tools plugin (Migration Tools panel).
	 * @deprecated BuddyBoss 3.1.0 Use the Migration Tools panel at ?page=bb-settings&tab=tools&panel=migration_tools.
	 *
	 * @return void
	 */
	function bboss_member_type_import_submenu_page() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.1.0', 'Migration Tools panel in buddyboss-tools' );
		// No delegation target — the legacy function rendered a full admin page
		// and ran the import body inline. The new path is the React panel + AJAX.
	}
}

if ( ! function_exists( 'bboss_import_profile_types_admin_menu' ) ) {
	/**
	 * Deprecated stub for the Profile Types visible-submenu registrar.
	 *
	 * @since BuddyBoss 1.0.0
	 * @since BuddyBoss 3.1.0 Deprecated. The submenu was retired in favor of the Settings 2.0 Migration Tools panel.
	 * @deprecated BuddyBoss 3.1.0 The Forum Import submenu was preserved via bb_register_legacy_bbp_converter_submenu() — Retired in Phase 4.
	 *
	 * @return void
	 */
	function bboss_import_profile_types_admin_menu() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.1.0', 'Migration Tools panel in buddyboss-tools' );
	}
}

if ( ! function_exists( 'bboss_core_get_tools_import_profile_settings_admin_tabs' ) ) {
	/**
	 * Deprecated stub for the Profile Types Tools-tab filter callback.
	 *
	 * @since BuddyBoss 1.0.0
	 * @since BuddyBoss 3.1.0 Deprecated. The Tools-tab entry was removed in favor of the Settings 2.0 Migration Tools panel.
	 * @deprecated BuddyBoss 3.1.0
	 *
	 * @param array $tabs Existing tab list.
	 * @return array The tab list unchanged (the legacy entry is no longer appended).
	 */
	function bboss_core_get_tools_import_profile_settings_admin_tabs( $tabs ) {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.1.0', 'Migration Tools panel in buddyboss-tools' );
		return is_array( $tabs ) ? $tabs : array();
	}
}

/* ============================================================================
 * Phase 4 - Forum Import (PROD-9940 / PROD-9936)
 * BBP_Converter, BBP_Converter_Base, 22 vendor classes, and bbp_new_converter()
 * moved into the buddyboss-tools plugin.
 *
 * BC strategy:
 *   - Tools active: spl_autoload registers class_alias on first lookup so
 *     third-party `extends BBP_Converter` etc. work at parse time.
 *   - Tools inactive: stub classes defined inline with magic-method dispatch
 *     that emits _deprecated_function and otherwise no-ops.
 *
 * Known BC limitation when Tools is inactive: PHP's parent::method() uses
 * compile-time static binding and bypasses __call() magic dispatch. Third-party
 * `class MyImporter extends phpBB` whose methods call `parent::convert_topics()`
 * will fatal because the stub `phpBB` has no real `convert_topics()` method.
 * The _deprecated_function replacement message below surfaces this limitation
 * so operators get an actionable hint. v1 accepts the hole; richer stubs are
 * a follow-up if real-world fatal reports emerge.
 *
 * Removal schedule: BuddyBoss [BBVERSION + 2].
 * ============================================================================ */

if ( ! function_exists( 'bboss_deprecated_forum_import_class_map' ) ) {
	/**
	 * Map legacy to moved class names for forum-import deprecation.
	 *
	 * @since BuddyBoss 3.1.0
	 *
	 * @return array Map of legacy => moved class name.
	 */
	function bboss_deprecated_forum_import_class_map() {
		return array(
			'BBP_Converter'      => 'BB_Tools_BBP_Converter',
			'BBP_Converter_Base' => 'BB_Tools_BBP_Converter_Base',
			// 22 vendor classes - names preserved across the move; alias is from the
			// LEGACY scope (where the alias is registered) to the same name in TOOLS'
			// global scope (where the moved class actually lives).
			'AEF'          => 'AEF',
			'bbPress1'     => 'bbPress1',
			'Drupal7'      => 'Drupal7',
			'FluxBB'       => 'FluxBB',
			'Invision'     => 'Invision',
			'Kunena1'      => 'Kunena1',
			'Kunena2'      => 'Kunena2',
			'Kunena3'      => 'Kunena3',
			'Mingle'       => 'Mingle',
			'MyBB'         => 'MyBB',
			'PHPFox3'      => 'PHPFox3',
			'PHPWind'      => 'PHPWind',
			'Phorum'       => 'Phorum',
			'PunBB'        => 'PunBB',
			'SMF'          => 'SMF',
			'SimplePress5' => 'SimplePress5',
			'Vanilla'      => 'Vanilla',
			'XMB'          => 'XMB',
			'XenForo'      => 'XenForo',
			'phpBB'        => 'phpBB',
			'vBulletin'    => 'vBulletin',
			'vBulletin3'   => 'vBulletin3',
		);
	}
}

if ( ! function_exists( 'bboss_deprecated_forum_import_autoload' ) ) {
	/**
	 * Autoload-driven BC for moved forum-import classes.
	 *
	 * Fires before WP's autoloader runs for an unknown class name. If the name
	 * matches a moved class:
	 *   - Tools active (target class exists): alias legacy to target.
	 *   - Tools inactive: define a stub with magic-method dispatch that emits a
	 *     _deprecated_function notice and no-ops on method calls.
	 *
	 * @since BuddyBoss 3.1.0
	 *
	 * @param string $class_name Class name PHP is attempting to autoload.
	 * @return void
	 */
	function bboss_deprecated_forum_import_autoload( $class_name ) {
		$map = bboss_deprecated_forum_import_class_map();
		if ( ! isset( $map[ $class_name ] ) ) {
			return;
		}

		$target = $map[ $class_name ];

		// Same-name alias (vendor classes): Tools' class is already loaded with
		// the same name in global scope, so we'd be aliasing to ourselves.
		// Only alias when the legacy name differs from the target.
		if ( $class_name !== $target ) {
			if ( class_exists( $target, false ) ) {
				class_alias( $target, $class_name );
				return;
			}
		} elseif ( class_exists( $target, false ) ) {
			// Vendor class already loaded by Tools - nothing to do.
			return;
		} elseif ( defined( 'BB_TOOLS_PLUGIN_DIR' ) ) {
			// Same-name vendor case (e.g. SMF -> SMF) and Tools IS active,
			// but its bb_tools_bbp_new_converter() lazy loader hasn't yet
			// required the file. Third-party code that triggered this autoload
			// (e.g. `class MySMFExt extends SMF` or `new SMF()` from a custom
			// converter extension) would otherwise get the eval-stub below,
			// which then blocks Tools' own require_once with
			// "Cannot declare class SMF, because the name is already in use".
			// Load Tools' real converter file so the proper class wins; Tools'
			// subsequent require_once on the same path is a no-op.
			$vendor_file = BB_TOOLS_PLUGIN_DIR . 'migration/buddyboss-forums/converters/' . $class_name . '.php';
			if ( is_readable( $vendor_file ) ) {
				require_once $vendor_file;
				return;
			}
		}

		// Tools is inactive - alias the legacy name to one shared stub class. The stub:
		//   - emits _deprecated_function on construction
		//   - dispatches all $this->method() calls to __call / __callStatic returning null
		//   - declares no parent (the legacy parent class is itself aliased to the stub)
		// A class alias can be extended, so third-party `extends` declarations still resolve.
		if ( ! class_exists( 'BBoss_Deprecated_Forum_Import_Stub', false ) ) {
			require_once __DIR__ . '/class-bboss-deprecated-forum-import-stub.php';
		}
		class_alias( 'BBoss_Deprecated_Forum_Import_Stub', $class_name );
	}

	spl_autoload_register( 'bboss_deprecated_forum_import_autoload' );
}

if ( ! function_exists( 'bboss_bbp_new_converter' ) ) {
	/**
	 * Deprecated factory wrapper for the moved bboss_bbp_new_converter().
	 *
	 * When Platform's own src/bboss-forums/admin/converter.php is still present (Task 4.5
	 * not yet run), that file defines bboss_bbp_new_converter() first and this wrapper is
	 * silently skipped via the function_exists() guard - no redeclaration fatal occurs.
	 * After Task 4.5 removes converter.php, this wrapper becomes the canonical stub.
	 *
	 * @since BuddyBoss 3.1.0
	 * @deprecated BuddyBoss 3.1.0 Moved to buddyboss-tools as bb_tools_bbp_new_converter().
	 *
	 * @param string $platform Vendor class name (e.g. "phpBB").
	 * @return object|null Vendor converter instance when Tools is active; null otherwise.
	 */
	function bboss_bbp_new_converter( $platform = '' ) {
		_deprecated_function( __FUNCTION__, 'BuddyBoss 3.1.0', 'bb_tools_bbp_new_converter' );
		if ( function_exists( 'bb_tools_bbp_new_converter' ) ) {
			return bb_tools_bbp_new_converter( $platform );
		}
		return null;
	}
}

/**
 * Deprecation shim for bboss_activity_pin_unpin_post().
 *
 * The Pinned Posts feature moved out of Platform into the BuddyBoss Addons
 * plugin, which now defines bboss_activity_pin_unpin_post() (at bp_include:20).
 * External callers that were not updated — notably the separately-released
 * BuddyBoss Platform API plugin's activity "pin" REST route — call this function
 * unguarded, which would fatal on a site without the add-on.
 *
 * The shim is registered on bp_init (AFTER bp_include, where the add-on both
 * defines the real function and evaluates its dormancy marker — which is this
 * same function name) and only defines a no-op fallback when no provider has
 * supplied the real implementation. Registering it any earlier would trip the
 * add-on's dormancy check and stop Pinned Posts loading.
 *
 * @since BuddyBoss 3.4.0
 */
if ( ! function_exists( 'bboss_activity_pin_unpin_post_deprecation_shim' ) ) {
	function bboss_activity_pin_unpin_post_deprecation_shim() {
		if ( function_exists( 'bboss_activity_pin_unpin_post' ) ) {
			return;
		}

		/**
		 * Fallback for the removed Pinned Posts mutation function.
		 *
		 * @since      BuddyBoss 2.4.60
		 * @deprecated BuddyBoss 3.4.0 Moved to the BuddyBoss Addons plugin.
		 *
		 * @param array $args Pin/unpin arguments (ignored).
		 * @return bool Always false — the feature is unavailable without the add-on.
		 */
		function bboss_activity_pin_unpin_post( $args = array() ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
			_deprecated_function( __FUNCTION__, 'BuddyBoss 3.4.0', 'the BuddyBoss Addons plugin' );
			return false;
		}

		/**
		 * Marker: the no-op fallback above is in place, i.e. no real provider loaded.
		 *
		 * Callers cannot use `function_exists( 'bb_activity_pin_unpin_post' )` to
		 * detect availability once the shim has installed, because the shim always
		 * defines that name. They test for this marker instead — it exists only on
		 * the fallback path.
		 *
		 * @since BuddyBoss 3.4.0
		 *
		 * @return bool Always true.
		 */
		function bboss_activity_pin_unpin_post_is_stub() {
			return true;
		}
	}
	add_action( 'bp_init', 'bb_activity_pin_unpin_post_deprecation_shim', 1 );
}

// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename names of this file's symbols (decision 0013): declared
// here, guarded on the renamed declaration, so each old name exists exactly when the new one does.
if ( function_exists( 'bboss_activate_notification' ) && ! function_exists( 'bb_activate_notification' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_activate_notification()}.
	 *
	 * @deprecated Use bboss_activate_notification() instead.
	 */
	function bb_activate_notification( $field, $checked ) {
		return bboss_activate_notification( $field, $checked );
	}
}
if ( function_exists( 'bboss_activity_pin_unpin_post' ) && ! function_exists( 'bb_activity_pin_unpin_post' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_activity_pin_unpin_post()}.
	 *
	 * @deprecated Use bboss_activity_pin_unpin_post() instead.
	 */
	function bb_activity_pin_unpin_post( $args = array() ) {
		return bboss_activity_pin_unpin_post( $args );
	}
}
if ( function_exists( 'bboss_activity_pin_unpin_post_deprecation_shim' ) && ! function_exists( 'bb_activity_pin_unpin_post_deprecation_shim' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_activity_pin_unpin_post_deprecation_shim()}.
	 *
	 * @deprecated Use bboss_activity_pin_unpin_post_deprecation_shim() instead.
	 */
	function bb_activity_pin_unpin_post_deprecation_shim() {
		return bboss_activity_pin_unpin_post_deprecation_shim();
	}
}
if ( function_exists( 'bboss_activity_pin_unpin_post_is_stub' ) && ! function_exists( 'bb_activity_pin_unpin_post_is_stub' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_activity_pin_unpin_post_is_stub()}.
	 *
	 * @deprecated Use bboss_activity_pin_unpin_post_is_stub() instead.
	 */
	function bb_activity_pin_unpin_post_is_stub() {
		return bboss_activity_pin_unpin_post_is_stub();
	}
}
if ( function_exists( 'bboss_admin_setting_callback_messaging_notification_fields' ) && ! function_exists( 'bb_admin_setting_callback_messaging_notification_fields' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_admin_setting_callback_messaging_notification_fields()}.
	 *
	 * @deprecated Use bboss_admin_setting_callback_messaging_notification_fields() instead.
	 */
	function bb_admin_setting_callback_messaging_notification_fields() {
		return bboss_admin_setting_callback_messaging_notification_fields();
	}
}
if ( function_exists( 'bboss_admin_setting_callback_messaging_notification_warning' ) && ! function_exists( 'bb_admin_setting_callback_messaging_notification_warning' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_admin_setting_callback_messaging_notification_warning()}.
	 *
	 * @deprecated Use bboss_admin_setting_callback_messaging_notification_warning() instead.
	 */
	function bb_admin_setting_callback_messaging_notification_warning() {
		return bboss_admin_setting_callback_messaging_notification_warning();
	}
}
if ( function_exists( 'bboss_admin_setting_callback_register_show_legal_agreement' ) && ! function_exists( 'bb_admin_setting_callback_register_show_legal_agreement' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_admin_setting_callback_register_show_legal_agreement()}.
	 *
	 * @deprecated Use bboss_admin_setting_callback_register_show_legal_agreement() instead.
	 */
	function bb_admin_setting_callback_register_show_legal_agreement() {
		return bboss_admin_setting_callback_register_show_legal_agreement();
	}
}
if ( function_exists( 'bboss_advanced_fire_deprecated_save_hooks' ) && ! function_exists( 'bb_advanced_fire_deprecated_save_hooks' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_advanced_fire_deprecated_save_hooks()}.
	 *
	 * @deprecated Use bboss_advanced_fire_deprecated_save_hooks() instead.
	 */
	function bb_advanced_fire_deprecated_save_hooks( $feature_id, $settings, $saved ) {
		return bboss_advanced_fire_deprecated_save_hooks( $feature_id, $settings, $saved );
	}
}
if ( function_exists( 'bboss_deprecated_forum_import_autoload' ) && ! function_exists( 'bb_deprecated_forum_import_autoload' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_deprecated_forum_import_autoload()}.
	 *
	 * @deprecated Use bboss_deprecated_forum_import_autoload() instead.
	 */
	function bb_deprecated_forum_import_autoload( $class_name ) {
		return bboss_deprecated_forum_import_autoload( $class_name );
	}
}
if ( function_exists( 'bboss_deprecated_forum_import_class_map' ) && ! function_exists( 'bb_deprecated_forum_import_class_map' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_deprecated_forum_import_class_map()}.
	 *
	 * @deprecated Use bboss_deprecated_forum_import_class_map() instead.
	 */
	function bb_deprecated_forum_import_class_map() {
		return bboss_deprecated_forum_import_class_map();
	}
}
if ( function_exists( 'bboss_deprecated_friends_register_fields_hook' ) && ! function_exists( 'bb_deprecated_friends_register_fields_hook' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_deprecated_friends_register_fields_hook()}.
	 *
	 * @deprecated Use bboss_deprecated_friends_register_fields_hook() instead.
	 */
	function bb_deprecated_friends_register_fields_hook() {
		return bboss_deprecated_friends_register_fields_hook();
	}
}
if ( function_exists( 'bboss_deprecated_media_settings_filter_hooks' ) && ! function_exists( 'bb_deprecated_media_settings_filter_hooks' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_deprecated_media_settings_filter_hooks()}.
	 *
	 * @deprecated Use bboss_deprecated_media_settings_filter_hooks() instead.
	 */
	function bb_deprecated_media_settings_filter_hooks() {
		return bboss_deprecated_media_settings_filter_hooks();
	}
}
if ( function_exists( 'bboss_deprecated_profile_admin_tab_hooks' ) && ! function_exists( 'bb_deprecated_profile_admin_tab_hooks' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_deprecated_profile_admin_tab_hooks()}.
	 *
	 * @deprecated Use bboss_deprecated_profile_admin_tab_hooks() instead.
	 */
	function bb_deprecated_profile_admin_tab_hooks() {
		return bboss_deprecated_profile_admin_tab_hooks();
	}
}
if ( function_exists( 'bboss_deprecated_tools_admin_tabs_hooks_notice' ) && ! function_exists( 'bb_deprecated_tools_admin_tabs_hooks_notice' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_deprecated_tools_admin_tabs_hooks_notice()}.
	 *
	 * @deprecated Use bboss_deprecated_tools_admin_tabs_hooks_notice() instead.
	 */
	function bb_deprecated_tools_admin_tabs_hooks_notice() {
		return bboss_deprecated_tools_admin_tabs_hooks_notice();
	}
}
if ( function_exists( 'bboss_deprecated_xprofile_admin_rendering_hooks' ) && ! function_exists( 'bb_deprecated_xprofile_admin_rendering_hooks' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_deprecated_xprofile_admin_rendering_hooks()}.
	 *
	 * @deprecated Use bboss_deprecated_xprofile_admin_rendering_hooks() instead.
	 */
	function bb_deprecated_xprofile_admin_rendering_hooks() {
		return bboss_deprecated_xprofile_admin_rendering_hooks();
	}
}
if ( function_exists( 'bboss_deprecated_xprofile_register_fields_hook' ) && ! function_exists( 'bb_deprecated_xprofile_register_fields_hook' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_deprecated_xprofile_register_fields_hook()}.
	 *
	 * @deprecated Use bboss_deprecated_xprofile_register_fields_hook() instead.
	 */
	function bb_deprecated_xprofile_register_fields_hook() {
		return bboss_deprecated_xprofile_register_fields_hook();
	}
}
if ( function_exists( 'bboss_group_type_labelcolor_metabox' ) && ! function_exists( 'bb_group_type_labelcolor_metabox' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_group_type_labelcolor_metabox()}.
	 *
	 * @deprecated Use bboss_group_type_labelcolor_metabox() instead.
	 */
	function bb_group_type_labelcolor_metabox( $post ) {
		return bboss_group_type_labelcolor_metabox( $post );
	}
}
if ( function_exists( 'bboss_groups_clear_group_type_cache_on_update' ) && ! function_exists( 'bb_groups_clear_group_type_cache_on_update' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_groups_clear_group_type_cache_on_update()}.
	 *
	 * @deprecated Use bboss_groups_clear_group_type_cache_on_update() instead.
	 */
	function bb_groups_clear_group_type_cache_on_update( $post_id ) {
		return bboss_groups_clear_group_type_cache_on_update( $post_id );
	}
}
if ( function_exists( 'bboss_invites_fire_deprecated_save_hooks' ) && ! function_exists( 'bb_invites_fire_deprecated_save_hooks' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_invites_fire_deprecated_save_hooks()}.
	 *
	 * @deprecated Use bboss_invites_fire_deprecated_save_hooks() instead.
	 */
	function bb_invites_fire_deprecated_save_hooks( $feature_id, $settings, $saved ) {
		return bboss_invites_fire_deprecated_save_hooks( $feature_id, $settings, $saved );
	}
}
if ( function_exists( 'bboss_labs_get_settings_fields' ) && ! function_exists( 'bb_labs_get_settings_fields' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_labs_get_settings_fields()}.
	 *
	 * @deprecated Use bboss_labs_get_settings_fields() instead.
	 */
	function bb_labs_get_settings_fields() {
		return bboss_labs_get_settings_fields();
	}
}
if ( function_exists( 'bboss_labs_get_settings_fields_for_section' ) && ! function_exists( 'bb_labs_get_settings_fields_for_section' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_labs_get_settings_fields_for_section()}.
	 *
	 * @deprecated Use bboss_labs_get_settings_fields_for_section() instead.
	 */
	function bb_labs_get_settings_fields_for_section( $section_id = '' ) {
		return bboss_labs_get_settings_fields_for_section( $section_id );
	}
}
if ( function_exists( 'bboss_labs_get_settings_sections' ) && ! function_exists( 'bb_labs_get_settings_sections' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_labs_get_settings_sections()}.
	 *
	 * @deprecated Use bboss_labs_get_settings_sections() instead.
	 */
	function bb_labs_get_settings_sections() {
		return bboss_labs_get_settings_sections();
	}
}
if ( function_exists( 'bboss_labs_info_section_callback' ) && ! function_exists( 'bb_labs_info_section_callback' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_labs_info_section_callback()}.
	 *
	 * @deprecated Use bboss_labs_info_section_callback() instead.
	 */
	function bb_labs_info_section_callback() {
		return bboss_labs_info_section_callback();
	}
}
if ( function_exists( 'bboss_labs_no_settings_callback' ) && ! function_exists( 'bb_labs_no_settings_callback' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_labs_no_settings_callback()}.
	 *
	 * @deprecated Use bboss_labs_no_settings_callback() instead.
	 */
	function bb_labs_no_settings_callback() {
		return bboss_labs_no_settings_callback();
	}
}
if ( function_exists( 'bboss_media_fire_deprecated_save_hooks' ) && ! function_exists( 'bb_media_fire_deprecated_save_hooks' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_media_fire_deprecated_save_hooks()}.
	 *
	 * @deprecated Use bboss_media_fire_deprecated_save_hooks() instead.
	 */
	function bb_media_fire_deprecated_save_hooks( $feature_id, $settings, $saved ) {
		return bboss_media_fire_deprecated_save_hooks( $feature_id, $settings, $saved );
	}
}
if ( function_exists( 'bboss_members_fire_deprecated_save_hooks' ) && ! function_exists( 'bb_members_fire_deprecated_save_hooks' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_members_fire_deprecated_save_hooks()}.
	 *
	 * @deprecated Use bboss_members_fire_deprecated_save_hooks() instead.
	 */
	function bb_members_fire_deprecated_save_hooks( $feature_id, $settings, $saved ) {
		return bboss_members_fire_deprecated_save_hooks( $feature_id, $settings, $saved );
	}
}
if ( function_exists( 'bboss_messaging_notifications_tutorial' ) && ! function_exists( 'bb_messaging_notifications_tutorial' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_messaging_notifications_tutorial()}.
	 *
	 * @deprecated Use bboss_messaging_notifications_tutorial() instead.
	 */
	function bb_messaging_notifications_tutorial() {
		return bboss_messaging_notifications_tutorial();
	}
}
if ( function_exists( 'bboss_notification_get_settings_fields' ) && ! function_exists( 'bb_notification_get_settings_fields' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_notification_get_settings_fields()}.
	 *
	 * @deprecated Use bboss_notification_get_settings_fields() instead.
	 */
	function bb_notification_get_settings_fields() {
		return bboss_notification_get_settings_fields();
	}
}
if ( function_exists( 'bboss_notification_get_settings_fields_for_section' ) && ! function_exists( 'bb_notification_get_settings_fields_for_section' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_notification_get_settings_fields_for_section()}.
	 *
	 * @deprecated Use bboss_notification_get_settings_fields_for_section() instead.
	 */
	function bb_notification_get_settings_fields_for_section( $section_id = '' ) {
		return bboss_notification_get_settings_fields_for_section( $section_id );
	}
}
if ( function_exists( 'bboss_notification_get_settings_sections' ) && ! function_exists( 'bb_notification_get_settings_sections' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_notification_get_settings_sections()}.
	 *
	 * @deprecated Use bboss_notification_get_settings_sections() instead.
	 */
	function bb_notification_get_settings_sections() {
		return bboss_notification_get_settings_sections();
	}
}
if ( function_exists( 'bboss_notifications_fire_deprecated_save_hooks' ) && ! function_exists( 'bb_notifications_fire_deprecated_save_hooks' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_notifications_fire_deprecated_save_hooks()}.
	 *
	 * @deprecated Use bboss_notifications_fire_deprecated_save_hooks() instead.
	 */
	function bb_notifications_fire_deprecated_save_hooks( $feature_id, $settings, $saved ) {
		return bboss_notifications_fire_deprecated_save_hooks( $feature_id, $settings, $saved );
	}
}
if ( function_exists( 'bboss_readylaunch_register_enqueue_style_script' ) && ! function_exists( 'bb_readylaunch_register_enqueue_style_script' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_readylaunch_register_enqueue_style_script()}.
	 *
	 * @deprecated Use bboss_readylaunch_register_enqueue_style_script() instead.
	 */
	function bb_readylaunch_register_enqueue_style_script() {
		return bboss_readylaunch_register_enqueue_style_script();
	}
}
if ( function_exists( 'bboss_readylaunch_settings_page_enqueue_style_script' ) && ! function_exists( 'bb_readylaunch_settings_page_enqueue_style_script' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_readylaunch_settings_page_enqueue_style_script()}.
	 *
	 * @deprecated Use bboss_readylaunch_settings_page_enqueue_style_script() instead.
	 */
	function bb_readylaunch_settings_page_enqueue_style_script( $admin_page ) {
		return bboss_readylaunch_settings_page_enqueue_style_script( $admin_page );
	}
}
if ( function_exists( 'bboss_readylaunch_settings_page_html' ) && ! function_exists( 'bb_readylaunch_settings_page_html' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_readylaunch_settings_page_html()}.
	 *
	 * @deprecated Use bboss_readylaunch_settings_page_html() instead.
	 */
	function bb_readylaunch_settings_page_html() {
		return bboss_readylaunch_settings_page_html();
	}
}
if ( function_exists( 'bboss_registration_fire_deprecated_save_hooks' ) && ! function_exists( 'bb_registration_fire_deprecated_save_hooks' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_registration_fire_deprecated_save_hooks()}.
	 *
	 * @deprecated Use bboss_registration_fire_deprecated_save_hooks() instead.
	 */
	function bb_registration_fire_deprecated_save_hooks( $feature_id, $settings, $saved ) {
		return bboss_registration_fire_deprecated_save_hooks( $feature_id, $settings, $saved );
	}
}
if ( function_exists( 'bboss_registration_page_tutorial' ) && ! function_exists( 'bb_registration_page_tutorial' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_registration_page_tutorial()}.
	 *
	 * @deprecated Use bboss_registration_page_tutorial() instead.
	 */
	function bb_registration_page_tutorial() {
		return bboss_registration_page_tutorial();
	}
}
if ( function_exists( 'bboss_bbp_admin_get_settings_fields' ) && ! function_exists( 'bbp_admin_get_settings_fields' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_bbp_admin_get_settings_fields()}.
	 *
	 * @deprecated Use bboss_bbp_admin_get_settings_fields() instead.
	 */
	function bbp_admin_get_settings_fields() {
		return bboss_bbp_admin_get_settings_fields();
	}
}
if ( function_exists( 'bboss_bbp_admin_get_settings_fields_for_section' ) && ! function_exists( 'bbp_admin_get_settings_fields_for_section' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_bbp_admin_get_settings_fields_for_section()}.
	 *
	 * @deprecated Use bboss_bbp_admin_get_settings_fields_for_section() instead.
	 */
	function bbp_admin_get_settings_fields_for_section( $section_id = '' ) {
		return bboss_bbp_admin_get_settings_fields_for_section( $section_id );
	}
}
if ( function_exists( 'bboss_bbp_admin_get_settings_sections' ) && ! function_exists( 'bbp_admin_get_settings_sections' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_bbp_admin_get_settings_sections()}.
	 *
	 * @deprecated Use bboss_bbp_admin_get_settings_sections() instead.
	 */
	function bbp_admin_get_settings_sections() {
		return bboss_bbp_admin_get_settings_sections();
	}
}
if ( function_exists( 'bboss_bbp_admin_repair' ) && ! function_exists( 'bbp_admin_repair' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_bbp_admin_repair()}.
	 *
	 * @deprecated Use bboss_bbp_admin_repair() instead.
	 */
	function bbp_admin_repair() {
		return bboss_bbp_admin_repair();
	}
}
if ( function_exists( 'bboss_bbp_admin_repair_handler' ) && ! function_exists( 'bbp_admin_repair_handler' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_bbp_admin_repair_handler()}.
	 *
	 * @deprecated Use bboss_bbp_admin_repair_handler() instead.
	 */
	function bbp_admin_repair_handler() {
		return bboss_bbp_admin_repair_handler();
	}
}
if ( function_exists( 'bboss_bbp_admin_tools_feedback' ) && ! function_exists( 'bbp_admin_tools_feedback' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_bbp_admin_tools_feedback()}.
	 *
	 * @deprecated Use bboss_bbp_admin_tools_feedback() instead.
	 */
	function bbp_admin_tools_feedback( $message, $class_name = false ) {
		return bboss_bbp_admin_tools_feedback( $message, $class_name );
	}
}
if ( function_exists( 'bboss_bbp_converter_settings' ) && ! function_exists( 'bbp_converter_settings' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_bbp_converter_settings()}.
	 *
	 * @deprecated Use bboss_bbp_converter_settings() instead.
	 */
	function bbp_converter_settings() {
		return bboss_bbp_converter_settings();
	}
}
if ( function_exists( 'bboss_bbp_core_get_import_forum_tools_settings_admin_tabs' ) && ! function_exists( 'bbp_core_get_import_forum_tools_settings_admin_tabs' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_bbp_core_get_import_forum_tools_settings_admin_tabs()}.
	 *
	 * @deprecated Use bboss_bbp_core_get_import_forum_tools_settings_admin_tabs() instead.
	 */
	function bbp_core_get_import_forum_tools_settings_admin_tabs( $tabs ) {
		return bboss_bbp_core_get_import_forum_tools_settings_admin_tabs( $tabs );
	}
}
if ( function_exists( 'bboss_bbp_core_get_tools_settings_admin_tabs' ) && ! function_exists( 'bbp_core_get_tools_settings_admin_tabs' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_bbp_core_get_tools_settings_admin_tabs()}.
	 *
	 * @deprecated Use bboss_bbp_core_get_tools_settings_admin_tabs() instead.
	 */
	function bbp_core_get_tools_settings_admin_tabs( $tabs ) {
		return bboss_bbp_core_get_tools_settings_admin_tabs( $tabs );
	}
}
if ( function_exists( 'bboss_bbp_new_converter' ) && ! function_exists( 'bbp_new_converter' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_bbp_new_converter()}.
	 *
	 * @deprecated Use bboss_bbp_new_converter() instead.
	 */
	function bbp_new_converter( $platform = '' ) {
		return bboss_bbp_new_converter( $platform );
	}
}
if ( function_exists( 'bboss_admin_registration_setting_tutorial' ) && ! function_exists( 'bp_admin_registration_setting_tutorial' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_admin_registration_setting_tutorial()}.
	 *
	 * @deprecated Use bboss_admin_registration_setting_tutorial() instead.
	 */
	function bp_admin_registration_setting_tutorial() {
		return bboss_admin_registration_setting_tutorial();
	}
}
if ( function_exists( 'bboss_admin_repair_handler' ) && ! function_exists( 'bp_admin_repair_handler' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_admin_repair_handler()}.
	 *
	 * @deprecated Use bboss_admin_repair_handler() instead.
	 */
	function bp_admin_repair_handler() {
		return bboss_admin_repair_handler();
	}
}
if ( function_exists( 'bboss_admin_setting_callback_enable_send_invite_member_type' ) && ! function_exists( 'bp_admin_setting_callback_enable_send_invite_member_type' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_admin_setting_callback_enable_send_invite_member_type()}.
	 *
	 * @deprecated Use bboss_admin_setting_callback_enable_send_invite_member_type() instead.
	 */
	function bp_admin_setting_callback_enable_send_invite_member_type( $args = array() ) {
		return bboss_admin_setting_callback_enable_send_invite_member_type( $args );
	}
}
if ( function_exists( 'bboss_admin_setting_callback_member_invite_email_content' ) && ! function_exists( 'bp_admin_setting_callback_member_invite_email_content' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_admin_setting_callback_member_invite_email_content()}.
	 *
	 * @deprecated Use bboss_admin_setting_callback_member_invite_email_content() instead.
	 */
	function bp_admin_setting_callback_member_invite_email_content() {
		return bboss_admin_setting_callback_member_invite_email_content();
	}
}
if ( function_exists( 'bboss_admin_setting_callback_member_invite_email_subject' ) && ! function_exists( 'bp_admin_setting_callback_member_invite_email_subject' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_admin_setting_callback_member_invite_email_subject()}.
	 *
	 * @deprecated Use bboss_admin_setting_callback_member_invite_email_subject() instead.
	 */
	function bp_admin_setting_callback_member_invite_email_subject() {
		return bboss_admin_setting_callback_member_invite_email_subject();
	}
}
if ( function_exists( 'bboss_admin_setting_callback_member_invite_member_type' ) && ! function_exists( 'bp_admin_setting_callback_member_invite_member_type' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_admin_setting_callback_member_invite_member_type()}.
	 *
	 * @deprecated Use bboss_admin_setting_callback_member_invite_member_type() instead.
	 */
	function bp_admin_setting_callback_member_invite_member_type() {
		return bboss_admin_setting_callback_member_invite_member_type();
	}
}
if ( function_exists( 'bboss_admin_setting_callback_page_directory_dropdown' ) && ! function_exists( 'bp_admin_setting_callback_page_directory_dropdown' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_admin_setting_callback_page_directory_dropdown()}.
	 *
	 * @deprecated Use bboss_admin_setting_callback_page_directory_dropdown() instead.
	 */
	function bp_admin_setting_callback_page_directory_dropdown( $args ) {
		return bboss_admin_setting_callback_page_directory_dropdown( $args );
	}
}
if ( function_exists( 'bboss_admin_setting_callback_register' ) && ! function_exists( 'bp_admin_setting_callback_register' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_admin_setting_callback_register()}.
	 *
	 * @deprecated Use bboss_admin_setting_callback_register() instead.
	 */
	function bp_admin_setting_callback_register() {
		return bboss_admin_setting_callback_register();
	}
}
if ( function_exists( 'bboss_admin_setting_callback_register_allow_custom_registration' ) && ! function_exists( 'bp_admin_setting_callback_register_allow_custom_registration' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_admin_setting_callback_register_allow_custom_registration()}.
	 *
	 * @deprecated Use bboss_admin_setting_callback_register_allow_custom_registration() instead.
	 */
	function bp_admin_setting_callback_register_allow_custom_registration() {
		return bboss_admin_setting_callback_register_allow_custom_registration();
	}
}
if ( function_exists( 'bboss_admin_setting_callback_register_page_url' ) && ! function_exists( 'bp_admin_setting_callback_register_page_url' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_admin_setting_callback_register_page_url()}.
	 *
	 * @deprecated Use bboss_admin_setting_callback_register_page_url() instead.
	 */
	function bp_admin_setting_callback_register_page_url() {
		return bboss_admin_setting_callback_register_page_url();
	}
}
if ( function_exists( 'bboss_admin_setting_callback_register_show_confirm_email' ) && ! function_exists( 'bp_admin_setting_callback_register_show_confirm_email' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_admin_setting_callback_register_show_confirm_email()}.
	 *
	 * @deprecated Use bboss_admin_setting_callback_register_show_confirm_email() instead.
	 */
	function bp_admin_setting_callback_register_show_confirm_email() {
		return bboss_admin_setting_callback_register_show_confirm_email();
	}
}
if ( function_exists( 'bboss_admin_setting_callback_register_show_confirm_password' ) && ! function_exists( 'bp_admin_setting_callback_register_show_confirm_password' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_admin_setting_callback_register_show_confirm_password()}.
	 *
	 * @deprecated Use bboss_admin_setting_callback_register_show_confirm_password() instead.
	 */
	function bp_admin_setting_callback_register_show_confirm_password() {
		return bboss_admin_setting_callback_register_show_confirm_password();
	}
}
if ( function_exists( 'bboss_admin_tools_default_data_save' ) && ! function_exists( 'bp_admin_tools_default_data_save' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_admin_tools_default_data_save()}.
	 *
	 * @deprecated Use bboss_admin_tools_default_data_save() instead.
	 */
	function bp_admin_tools_default_data_save() {
		return bboss_admin_tools_default_data_save();
	}
}
if ( function_exists( 'bboss_core_admin_components_activation_handler' ) && ! function_exists( 'bp_core_admin_components_activation_handler' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_core_admin_components_activation_handler()}.
	 *
	 * @deprecated Use bboss_core_admin_components_activation_handler() instead.
	 */
	function bp_core_admin_components_activation_handler() {
		return bboss_core_admin_components_activation_handler();
	}
}
if ( function_exists( 'bboss_core_admin_components_options' ) && ! function_exists( 'bp_core_admin_components_options' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_core_admin_components_options()}.
	 *
	 * @deprecated Use bboss_core_admin_components_options() instead.
	 */
	function bp_core_admin_components_options() {
		return bboss_core_admin_components_options();
	}
}
if ( function_exists( 'bboss_core_admin_components_settings' ) && ! function_exists( 'bp_core_admin_components_settings' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_core_admin_components_settings()}.
	 *
	 * @deprecated Use bboss_core_admin_components_settings() instead.
	 */
	function bp_core_admin_components_settings() {
		return bboss_core_admin_components_settings();
	}
}
if ( function_exists( 'bboss_core_admin_components_settings_handler' ) && ! function_exists( 'bp_core_admin_components_settings_handler' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_core_admin_components_settings_handler()}.
	 *
	 * @deprecated Use bboss_core_admin_components_settings_handler() instead.
	 */
	function bp_core_admin_components_settings_handler() {
		return bboss_core_admin_components_settings_handler();
	}
}
if ( function_exists( 'bboss_core_admin_directory_pages_description' ) && ! function_exists( 'bp_core_admin_directory_pages_description' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_core_admin_directory_pages_description()}.
	 *
	 * @deprecated Use bboss_core_admin_directory_pages_description() instead.
	 */
	function bp_core_admin_directory_pages_description() {
		return bboss_core_admin_directory_pages_description();
	}
}
if ( function_exists( 'bboss_core_admin_emails_tabs' ) && ! function_exists( 'bp_core_admin_emails_tabs' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_core_admin_emails_tabs()}.
	 *
	 * @deprecated Use bboss_core_admin_emails_tabs() instead.
	 */
	function bp_core_admin_emails_tabs( $active_tab = '' ) {
		return bboss_core_admin_emails_tabs( $active_tab );
	}
}
if ( function_exists( 'bboss_core_admin_groups_tabs' ) && ! function_exists( 'bp_core_admin_groups_tabs' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_core_admin_groups_tabs()}.
	 *
	 * @deprecated Use bboss_core_admin_groups_tabs() instead.
	 */
	function bp_core_admin_groups_tabs( $active_tab = '' ) {
		return bboss_core_admin_groups_tabs( $active_tab );
	}
}
if ( function_exists( 'bboss_core_admin_maybe_save_pages_settings' ) && ! function_exists( 'bp_core_admin_maybe_save_pages_settings' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_core_admin_maybe_save_pages_settings()}.
	 *
	 * @deprecated Use bboss_core_admin_maybe_save_pages_settings() instead.
	 */
	function bp_core_admin_maybe_save_pages_settings() {
		return bboss_core_admin_maybe_save_pages_settings();
	}
}
if ( function_exists( 'bboss_core_admin_pages_settings' ) && ! function_exists( 'bp_core_admin_pages_settings' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_core_admin_pages_settings()}.
	 *
	 * @deprecated Use bboss_core_admin_pages_settings() instead.
	 */
	function bp_core_admin_pages_settings() {
		return bboss_core_admin_pages_settings();
	}
}
if ( function_exists( 'bboss_core_admin_register_page_fields' ) && ! function_exists( 'bp_core_admin_register_page_fields' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_core_admin_register_page_fields()}.
	 *
	 * @deprecated Use bboss_core_admin_register_page_fields() instead.
	 */
	function bp_core_admin_register_page_fields() {
		return bboss_core_admin_register_page_fields();
	}
}
if ( function_exists( 'bboss_core_admin_register_registration_page_fields' ) && ! function_exists( 'bp_core_admin_register_registration_page_fields' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_core_admin_register_registration_page_fields()}.
	 *
	 * @deprecated Use bboss_core_admin_register_registration_page_fields() instead.
	 */
	function bp_core_admin_register_registration_page_fields() {
		return bboss_core_admin_register_registration_page_fields();
	}
}
if ( function_exists( 'bboss_core_admin_registration_pages_description' ) && ! function_exists( 'bp_core_admin_registration_pages_description' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_core_admin_registration_pages_description()}.
	 *
	 * @deprecated Use bboss_core_admin_registration_pages_description() instead.
	 */
	function bp_core_admin_registration_pages_description() {
		return bboss_core_admin_registration_pages_description();
	}
}
if ( function_exists( 'bboss_core_admin_tools' ) && ! function_exists( 'bp_core_admin_tools' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_core_admin_tools()}.
	 *
	 * @deprecated Use bboss_core_admin_tools() instead.
	 */
	function bp_core_admin_tools() {
		return bboss_core_admin_tools();
	}
}
if ( function_exists( 'bboss_core_admin_users_tabs' ) && ! function_exists( 'bp_core_admin_users_tabs' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_core_admin_users_tabs()}.
	 *
	 * @deprecated Use bboss_core_admin_users_tabs() instead.
	 */
	function bp_core_admin_users_tabs( $active_tab = '' ) {
		return bboss_core_admin_users_tabs( $active_tab );
	}
}
if ( function_exists( 'bboss_core_get_emails_admin_tabs' ) && ! function_exists( 'bp_core_get_emails_admin_tabs' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_core_get_emails_admin_tabs()}.
	 *
	 * @deprecated Use bboss_core_get_emails_admin_tabs() instead.
	 */
	function bp_core_get_emails_admin_tabs( $active_tab = '' ) {
		return bboss_core_get_emails_admin_tabs( $active_tab );
	}
}
if ( function_exists( 'bboss_core_get_groups_admin_tabs' ) && ! function_exists( 'bp_core_get_groups_admin_tabs' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_core_get_groups_admin_tabs()}.
	 *
	 * @deprecated Use bboss_core_get_groups_admin_tabs() instead.
	 */
	function bp_core_get_groups_admin_tabs( $active_tab = '' ) {
		return bboss_core_get_groups_admin_tabs( $active_tab );
	}
}
if ( function_exists( 'bboss_core_get_tools_import_profile_settings_admin_tabs' ) && ! function_exists( 'bp_core_get_tools_import_profile_settings_admin_tabs' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_core_get_tools_import_profile_settings_admin_tabs()}.
	 *
	 * @deprecated Use bboss_core_get_tools_import_profile_settings_admin_tabs() instead.
	 */
	function bp_core_get_tools_import_profile_settings_admin_tabs( $tabs ) {
		return bboss_core_get_tools_import_profile_settings_admin_tabs( $tabs );
	}
}
if ( function_exists( 'bboss_core_get_tools_repair_community_settings_admin_tabs' ) && ! function_exists( 'bp_core_get_tools_repair_community_settings_admin_tabs' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_core_get_tools_repair_community_settings_admin_tabs()}.
	 *
	 * @deprecated Use bboss_core_get_tools_repair_community_settings_admin_tabs() instead.
	 */
	function bp_core_get_tools_repair_community_settings_admin_tabs( $tabs ) {
		return bboss_core_get_tools_repair_community_settings_admin_tabs( $tabs );
	}
}
if ( function_exists( 'bboss_core_get_tools_settings_admin_tabs' ) && ! function_exists( 'bp_core_get_tools_settings_admin_tabs' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_core_get_tools_settings_admin_tabs()}.
	 *
	 * @deprecated Use bboss_core_get_tools_settings_admin_tabs() instead.
	 */
	function bp_core_get_tools_settings_admin_tabs( $active_tab = '' ) {
		return bboss_core_get_tools_settings_admin_tabs( $active_tab );
	}
}
if ( function_exists( 'bboss_core_get_users_admin_tabs' ) && ! function_exists( 'bp_core_get_users_admin_tabs' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_core_get_users_admin_tabs()}.
	 *
	 * @deprecated Use bboss_core_get_users_admin_tabs() instead.
	 */
	function bp_core_get_users_admin_tabs( $active_tab = '' ) {
		return bboss_core_get_users_admin_tabs( $active_tab );
	}
}
if ( function_exists( 'bboss_core_tools_settings_admin_tabs' ) && ! function_exists( 'bp_core_tools_settings_admin_tabs' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_core_tools_settings_admin_tabs()}.
	 *
	 * @deprecated Use bboss_core_tools_settings_admin_tabs() instead.
	 */
	function bp_core_tools_settings_admin_tabs( $active_tab = '' ) {
		return bboss_core_tools_settings_admin_tabs( $active_tab );
	}
}
if ( function_exists( 'bboss_custom_pages_do_settings_fields' ) && ! function_exists( 'bp_custom_pages_do_settings_fields' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_custom_pages_do_settings_fields()}.
	 *
	 * @deprecated Use bboss_custom_pages_do_settings_fields() instead.
	 */
	function bp_custom_pages_do_settings_fields( $page, $section ) {
		return bboss_custom_pages_do_settings_fields( $page, $section );
	}
}
if ( function_exists( 'bboss_custom_pages_do_settings_sections' ) && ! function_exists( 'bp_custom_pages_do_settings_sections' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_custom_pages_do_settings_sections()}.
	 *
	 * @deprecated Use bboss_custom_pages_do_settings_sections() instead.
	 */
	function bp_custom_pages_do_settings_sections( $page ) {
		return bboss_custom_pages_do_settings_sections( $page );
	}
}
if ( function_exists( 'bboss_dd_clear_db' ) && ! function_exists( 'bp_dd_clear_db' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_dd_clear_db()}.
	 *
	 * @deprecated Use bboss_dd_clear_db() instead.
	 */
	function bp_dd_clear_db() {
		return bboss_dd_clear_db();
	}
}
if ( function_exists( 'bboss_dd_create_forums' ) && ! function_exists( 'bp_dd_create_forums' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_dd_create_forums()}.
	 *
	 * @deprecated Use bboss_dd_create_forums() instead.
	 */
	function bp_dd_create_forums( $forum, $users = array() ) {
		return bboss_dd_create_forums( $forum, $users );
	}
}
if ( function_exists( 'bboss_dd_create_forums_topics' ) && ! function_exists( 'bp_dd_create_forums_topics' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_dd_create_forums_topics()}.
	 *
	 * @deprecated Use bboss_dd_create_forums_topics() instead.
	 */
	function bp_dd_create_forums_topics( $topic_data, $forum_id, $users = array() ) {
		return bboss_dd_create_forums_topics( $topic_data, $forum_id, $users );
	}
}
if ( function_exists( 'bboss_dd_create_forums_topics_replies' ) && ! function_exists( 'bp_dd_create_forums_topics_replies' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_dd_create_forums_topics_replies()}.
	 *
	 * @deprecated Use bboss_dd_create_forums_topics_replies() instead.
	 */
	function bp_dd_create_forums_topics_replies( $reply_data, $topic_id, $users = array() ) {
		return bboss_dd_create_forums_topics_replies( $reply_data, $topic_id, $users );
	}
}
if ( function_exists( 'bboss_dd_delete_dummy_forum' ) && ! function_exists( 'bp_dd_delete_dummy_forum' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_dd_delete_dummy_forum()}.
	 *
	 * @deprecated Use bboss_dd_delete_dummy_forum() instead.
	 */
	function bp_dd_delete_dummy_forum() {
		return bboss_dd_delete_dummy_forum();
	}
}
if ( function_exists( 'bboss_dd_delete_dummy_groups' ) && ! function_exists( 'bp_dd_delete_dummy_groups' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_dd_delete_dummy_groups()}.
	 *
	 * @deprecated Use bboss_dd_delete_dummy_groups() instead.
	 */
	function bp_dd_delete_dummy_groups() {
		return bboss_dd_delete_dummy_groups();
	}
}
if ( function_exists( 'bboss_dd_delete_dummy_members' ) && ! function_exists( 'bp_dd_delete_dummy_members' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_dd_delete_dummy_members()}.
	 *
	 * @deprecated Use bboss_dd_delete_dummy_members() instead.
	 */
	function bp_dd_delete_dummy_members() {
		return bboss_dd_delete_dummy_members();
	}
}
if ( function_exists( 'bboss_dd_delete_dummy_members_related_data' ) && ! function_exists( 'bp_dd_delete_dummy_members_related_data' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_dd_delete_dummy_members_related_data()}.
	 *
	 * @deprecated Use bboss_dd_delete_dummy_members_related_data() instead.
	 */
	function bp_dd_delete_dummy_members_related_data() {
		return bboss_dd_delete_dummy_members_related_data();
	}
}
if ( function_exists( 'bboss_dd_delete_dummy_reply' ) && ! function_exists( 'bp_dd_delete_dummy_reply' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_dd_delete_dummy_reply()}.
	 *
	 * @deprecated Use bboss_dd_delete_dummy_reply() instead.
	 */
	function bp_dd_delete_dummy_reply() {
		return bboss_dd_delete_dummy_reply();
	}
}
if ( function_exists( 'bboss_dd_delete_dummy_topic' ) && ! function_exists( 'bp_dd_delete_dummy_topic' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_dd_delete_dummy_topic()}.
	 *
	 * @deprecated Use bboss_dd_delete_dummy_topic() instead.
	 */
	function bp_dd_delete_dummy_topic() {
		return bboss_dd_delete_dummy_topic();
	}
}
if ( function_exists( 'bboss_dd_delete_dummy_xprofile' ) && ! function_exists( 'bp_dd_delete_dummy_xprofile' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_dd_delete_dummy_xprofile()}.
	 *
	 * @deprecated Use bboss_dd_delete_dummy_xprofile() instead.
	 */
	function bp_dd_delete_dummy_xprofile() {
		return bboss_dd_delete_dummy_xprofile();
	}
}
if ( function_exists( 'bboss_dd_delete_import_records' ) && ! function_exists( 'bp_dd_delete_import_records' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_dd_delete_import_records()}.
	 *
	 * @deprecated Use bboss_dd_delete_import_records() instead.
	 */
	function bp_dd_delete_import_records() {
		return bboss_dd_delete_import_records();
	}
}
if ( function_exists( 'bboss_dd_friends_add_friend_date_fix' ) && ! function_exists( 'bp_dd_friends_add_friend_date_fix' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_dd_friends_add_friend_date_fix()}.
	 *
	 * @deprecated Use bboss_dd_friends_add_friend_date_fix() instead.
	 */
	function bp_dd_friends_add_friend_date_fix( $current_time ) {
		return bboss_dd_friends_add_friend_date_fix( $current_time );
	}
}
if ( function_exists( 'bboss_dd_get_forums_enable_groups_ids' ) && ! function_exists( 'bp_dd_get_forums_enable_groups_ids' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_dd_get_forums_enable_groups_ids()}.
	 *
	 * @deprecated Use bboss_dd_get_forums_enable_groups_ids() instead.
	 */
	function bp_dd_get_forums_enable_groups_ids( $count, $output = 'array' ) {
		return bboss_dd_get_forums_enable_groups_ids( $count, $output );
	}
}
if ( function_exists( 'bboss_dd_get_random_date' ) && ! function_exists( 'bp_dd_get_random_date' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_dd_get_random_date()}.
	 *
	 * @deprecated Use bboss_dd_get_random_date() instead.
	 */
	function bp_dd_get_random_date( $days_from = 30, $days_to = 0 ) {
		return bboss_dd_get_random_date( $days_from, $days_to );
	}
}
if ( function_exists( 'bboss_dd_get_random_forums_ids' ) && ! function_exists( 'bp_dd_get_random_forums_ids' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_dd_get_random_forums_ids()}.
	 *
	 * @deprecated Use bboss_dd_get_random_forums_ids() instead.
	 */
	function bp_dd_get_random_forums_ids( $count = 1, $output = 'array' ) {
		return bboss_dd_get_random_forums_ids( $count, $output );
	}
}
if ( function_exists( 'bboss_dd_get_random_groups_ids' ) && ! function_exists( 'bp_dd_get_random_groups_ids' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_dd_get_random_groups_ids()}.
	 *
	 * @deprecated Use bboss_dd_get_random_groups_ids() instead.
	 */
	function bp_dd_get_random_groups_ids( $count = 1, $output = 'array' ) {
		return bboss_dd_get_random_groups_ids( $count, $output );
	}
}
if ( function_exists( 'bboss_dd_get_random_topics_ids' ) && ! function_exists( 'bp_dd_get_random_topics_ids' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_dd_get_random_topics_ids()}.
	 *
	 * @deprecated Use bboss_dd_get_random_topics_ids() instead.
	 */
	function bp_dd_get_random_topics_ids( $count = 1, $output = 'array' ) {
		return bboss_dd_get_random_topics_ids( $count, $output );
	}
}
if ( function_exists( 'bboss_dd_get_random_users_ids' ) && ! function_exists( 'bp_dd_get_random_users_ids' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_dd_get_random_users_ids()}.
	 *
	 * @deprecated Use bboss_dd_get_random_users_ids() instead.
	 */
	function bp_dd_get_random_users_ids( $count = 1, $output = 'array' ) {
		return bboss_dd_get_random_users_ids( $count, $output );
	}
}
if ( function_exists( 'bboss_dd_get_root_admin_page' ) && ! function_exists( 'bp_dd_get_root_admin_page' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_dd_get_root_admin_page()}.
	 *
	 * @deprecated Use bboss_dd_get_root_admin_page() instead.
	 */
	function bp_dd_get_root_admin_page() {
		return bboss_dd_get_root_admin_page();
	}
}
if ( function_exists( 'bboss_dd_get_time' ) && ! function_exists( 'bp_dd_get_time' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_dd_get_time()}.
	 *
	 * @deprecated Use bboss_dd_get_time() instead.
	 */
	function bp_dd_get_time() {
		return bboss_dd_get_time();
	}
}
if ( function_exists( 'bboss_dd_groups_join_group_date_fix' ) && ! function_exists( 'bp_dd_groups_join_group_date_fix' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_dd_groups_join_group_date_fix()}.
	 *
	 * @deprecated Use bboss_dd_groups_join_group_date_fix() instead.
	 */
	function bp_dd_groups_join_group_date_fix( $args ) {
		return bboss_dd_groups_join_group_date_fix( $args );
	}
}
if ( function_exists( 'bboss_dd_import_forums' ) && ! function_exists( 'bp_dd_import_forums' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_dd_import_forums()}.
	 *
	 * @deprecated Use bboss_dd_import_forums() instead.
	 */
	function bp_dd_import_forums( $users = false ) {
		return bboss_dd_import_forums( $users );
	}
}
if ( function_exists( 'bboss_dd_import_forums_in_groups' ) && ! function_exists( 'bp_dd_import_forums_in_groups' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_dd_import_forums_in_groups()}.
	 *
	 * @deprecated Use bboss_dd_import_forums_in_groups() instead.
	 */
	function bp_dd_import_forums_in_groups() {
		return bboss_dd_import_forums_in_groups();
	}
}
if ( function_exists( 'bboss_dd_import_forums_topics' ) && ! function_exists( 'bp_dd_import_forums_topics' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_dd_import_forums_topics()}.
	 *
	 * @deprecated Use bboss_dd_import_forums_topics() instead.
	 */
	function bp_dd_import_forums_topics( $forums = false ) {
		return bboss_dd_import_forums_topics( $forums );
	}
}
if ( function_exists( 'bboss_dd_import_forums_topics_replies' ) && ! function_exists( 'bp_dd_import_forums_topics_replies' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_dd_import_forums_topics_replies()}.
	 *
	 * @deprecated Use bboss_dd_import_forums_topics_replies() instead.
	 */
	function bp_dd_import_forums_topics_replies( $topics = false ) {
		return bboss_dd_import_forums_topics_replies( $topics );
	}
}
if ( function_exists( 'bboss_dd_import_groups' ) && ! function_exists( 'bp_dd_import_groups' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_dd_import_groups()}.
	 *
	 * @deprecated Use bboss_dd_import_groups() instead.
	 */
	function bp_dd_import_groups( $users = false ) {
		return bboss_dd_import_groups( $users );
	}
}
if ( function_exists( 'bboss_dd_import_groups_activity' ) && ! function_exists( 'bp_dd_import_groups_activity' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_dd_import_groups_activity()}.
	 *
	 * @deprecated Use bboss_dd_import_groups_activity() instead.
	 */
	function bp_dd_import_groups_activity() {
		return bboss_dd_import_groups_activity();
	}
}
if ( function_exists( 'bboss_dd_import_groups_members' ) && ! function_exists( 'bp_dd_import_groups_members' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_dd_import_groups_members()}.
	 *
	 * @deprecated Use bboss_dd_import_groups_members() instead.
	 */
	function bp_dd_import_groups_members( $groups = false ) {
		return bboss_dd_import_groups_members( $groups );
	}
}
if ( function_exists( 'bboss_dd_import_users' ) && ! function_exists( 'bp_dd_import_users' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_dd_import_users()}.
	 *
	 * @deprecated Use bboss_dd_import_users() instead.
	 */
	function bp_dd_import_users() {
		return bboss_dd_import_users();
	}
}
if ( function_exists( 'bboss_dd_import_users_activity' ) && ! function_exists( 'bp_dd_import_users_activity' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_dd_import_users_activity()}.
	 *
	 * @deprecated Use bboss_dd_import_users_activity() instead.
	 */
	function bp_dd_import_users_activity() {
		return bboss_dd_import_users_activity();
	}
}
if ( function_exists( 'bboss_dd_import_users_friends' ) && ! function_exists( 'bp_dd_import_users_friends' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_dd_import_users_friends()}.
	 *
	 * @deprecated Use bboss_dd_import_users_friends() instead.
	 */
	function bp_dd_import_users_friends() {
		return bboss_dd_import_users_friends();
	}
}
if ( function_exists( 'bboss_dd_import_users_messages' ) && ! function_exists( 'bp_dd_import_users_messages' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_dd_import_users_messages()}.
	 *
	 * @deprecated Use bboss_dd_import_users_messages() instead.
	 */
	function bp_dd_import_users_messages() {
		return bboss_dd_import_users_messages();
	}
}
if ( function_exists( 'bboss_dd_import_users_profile' ) && ! function_exists( 'bp_dd_import_users_profile' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_dd_import_users_profile()}.
	 *
	 * @deprecated Use bboss_dd_import_users_profile() instead.
	 */
	function bp_dd_import_users_profile() {
		return bboss_dd_import_users_profile();
	}
}
if ( function_exists( 'bboss_dd_imported_disabled' ) && ! function_exists( 'bp_dd_imported_disabled' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_dd_imported_disabled()}.
	 *
	 * @deprecated Use bboss_dd_imported_disabled() instead.
	 */
	function bp_dd_imported_disabled( $group, $import ) {
		return bboss_dd_imported_disabled( $group, $import );
	}
}
if ( function_exists( 'bboss_dd_is_imported' ) && ! function_exists( 'bp_dd_is_imported' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_dd_is_imported()}.
	 *
	 * @deprecated Use bboss_dd_is_imported() instead.
	 */
	function bp_dd_is_imported( $group, $import ) {
		return bboss_dd_is_imported( $group, $import );
	}
}
if ( function_exists( 'bboss_dd_update_import' ) && ! function_exists( 'bp_dd_update_import' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_dd_update_import()}.
	 *
	 * @deprecated Use bboss_dd_update_import() instead.
	 */
	function bp_dd_update_import( $group, $import ) {
		return bboss_dd_update_import( $group, $import );
	}
}
if ( function_exists( 'bboss_email_invites_tutorial' ) && ! function_exists( 'bp_email_invites_tutorial' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_email_invites_tutorial()}.
	 *
	 * @deprecated Use bboss_email_invites_tutorial() instead.
	 */
	function bp_email_invites_tutorial() {
		return bboss_email_invites_tutorial();
	}
}
if ( function_exists( 'bboss_group_shortcode_meta_box' ) && ! function_exists( 'bp_group_shortcode_meta_box' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_group_shortcode_meta_box()}.
	 *
	 * @deprecated Use bboss_group_shortcode_meta_box() instead.
	 */
	function bp_group_shortcode_meta_box( $post ) {
		return bboss_group_shortcode_meta_box( $post );
	}
}
if ( function_exists( 'bboss_group_type_add_column' ) && ! function_exists( 'bp_group_type_add_column' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_group_type_add_column()}.
	 *
	 * @deprecated Use bboss_group_type_add_column() instead.
	 */
	function bp_group_type_add_column( $columns ) {
		return bboss_group_type_add_column( $columns );
	}
}
if ( function_exists( 'bboss_group_type_add_request_filter' ) && ! function_exists( 'bp_group_type_add_request_filter' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_group_type_add_request_filter()}.
	 *
	 * @deprecated Use bboss_group_type_add_request_filter() instead.
	 */
	function bp_group_type_add_request_filter() {
		return bboss_group_type_add_request_filter();
	}
}
if ( function_exists( 'bboss_group_type_add_sortable_columns' ) && ! function_exists( 'bp_group_type_add_sortable_columns' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_group_type_add_sortable_columns()}.
	 *
	 * @deprecated Use bboss_group_type_add_sortable_columns() instead.
	 */
	function bp_group_type_add_sortable_columns( $columns ) {
		return bboss_group_type_add_sortable_columns( $columns );
	}
}
if ( function_exists( 'bboss_group_type_custom_meta_boxes' ) && ! function_exists( 'bp_group_type_custom_meta_boxes' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_group_type_custom_meta_boxes()}.
	 *
	 * @deprecated Use bboss_group_type_custom_meta_boxes() instead.
	 */
	function bp_group_type_custom_meta_boxes() {
		return bboss_group_type_custom_meta_boxes();
	}
}
if ( function_exists( 'bboss_group_type_hide_quick_edit' ) && ! function_exists( 'bp_group_type_hide_quick_edit' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_group_type_hide_quick_edit()}.
	 *
	 * @deprecated Use bboss_group_type_hide_quick_edit() instead.
	 */
	function bp_group_type_hide_quick_edit( $actions, $post ) {
		return bboss_group_type_hide_quick_edit( $actions, $post );
	}
}
if ( function_exists( 'bboss_group_type_labels_meta_box' ) && ! function_exists( 'bp_group_type_labels_meta_box' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_group_type_labels_meta_box()}.
	 *
	 * @deprecated Use bboss_group_type_labels_meta_box() instead.
	 */
	function bp_group_type_labels_meta_box( $post ) {
		return bboss_group_type_labels_meta_box( $post );
	}
}
if ( function_exists( 'bboss_group_type_permissions_meta_box' ) && ! function_exists( 'bp_group_type_permissions_meta_box' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_group_type_permissions_meta_box()}.
	 *
	 * @deprecated Use bboss_group_type_permissions_meta_box() instead.
	 */
	function bp_group_type_permissions_meta_box( $post ) {
		return bboss_group_type_permissions_meta_box( $post );
	}
}
if ( function_exists( 'bboss_group_type_set_platform_tab_submenu_active' ) && ! function_exists( 'bp_group_type_set_platform_tab_submenu_active' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_group_type_set_platform_tab_submenu_active()}.
	 *
	 * @deprecated Use bboss_group_type_set_platform_tab_submenu_active() instead.
	 */
	function bp_group_type_set_platform_tab_submenu_active( $parent_file ) {
		return bboss_group_type_set_platform_tab_submenu_active( $parent_file );
	}
}
if ( function_exists( 'bboss_group_type_show_correct_current_menu' ) && ! function_exists( 'bp_group_type_show_correct_current_menu' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_group_type_show_correct_current_menu()}.
	 *
	 * @deprecated Use bboss_group_type_show_correct_current_menu() instead.
	 */
	function bp_group_type_show_correct_current_menu() {
		return bboss_group_type_show_correct_current_menu();
	}
}
if ( function_exists( 'bboss_group_type_show_data' ) && ! function_exists( 'bp_group_type_show_data' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_group_type_show_data()}.
	 *
	 * @deprecated Use bboss_group_type_show_data() instead.
	 */
	function bp_group_type_show_data( $column, $post_id ) {
		return bboss_group_type_show_data( $column, $post_id );
	}
}
if ( function_exists( 'bboss_group_type_sort_items' ) && ! function_exists( 'bp_group_type_sort_items' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_group_type_sort_items()}.
	 *
	 * @deprecated Use bboss_group_type_sort_items() instead.
	 */
	function bp_group_type_sort_items( $qv ) {
		return bboss_group_type_sort_items( $qv );
	}
}
if ( function_exists( 'bboss_groups_admin' ) && ! function_exists( 'bp_groups_admin' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_groups_admin()}.
	 *
	 * @deprecated Use bboss_groups_admin() instead.
	 */
	function bp_groups_admin() {
		return bboss_groups_admin();
	}
}
if ( function_exists( 'bboss_groups_admin_autocomplete_handler' ) && ! function_exists( 'bp_groups_admin_autocomplete_handler' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_groups_admin_autocomplete_handler()}.
	 *
	 * @deprecated Use bboss_groups_admin_autocomplete_handler() instead.
	 */
	function bp_groups_admin_autocomplete_handler() {
		return bboss_groups_admin_autocomplete_handler();
	}
}
if ( function_exists( 'bboss_groups_admin_create' ) && ! function_exists( 'bp_groups_admin_create' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_groups_admin_create()}.
	 *
	 * @deprecated Use bboss_groups_admin_create() instead.
	 */
	function bp_groups_admin_create() {
		return bboss_groups_admin_create();
	}
}
if ( function_exists( 'bboss_groups_admin_create_pagination_links' ) && ! function_exists( 'bp_groups_admin_create_pagination_links' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_groups_admin_create_pagination_links()}.
	 *
	 * @deprecated Use bboss_groups_admin_create_pagination_links() instead.
	 */
	function bp_groups_admin_create_pagination_links( $query, $member_type ) {
		return bboss_groups_admin_create_pagination_links( $query, $member_type );
	}
}
if ( function_exists( 'bboss_groups_admin_delete' ) && ! function_exists( 'bp_groups_admin_delete' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_groups_admin_delete()}.
	 *
	 * @deprecated Use bboss_groups_admin_delete() instead.
	 */
	function bp_groups_admin_delete() {
		return bboss_groups_admin_delete();
	}
}
if ( function_exists( 'bboss_groups_admin_edit' ) && ! function_exists( 'bp_groups_admin_edit' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_groups_admin_edit()}.
	 *
	 * @deprecated Use bboss_groups_admin_edit() instead.
	 */
	function bp_groups_admin_edit() {
		return bboss_groups_admin_edit();
	}
}
if ( function_exists( 'bboss_groups_admin_edit_metabox_add_new_members' ) && ! function_exists( 'bp_groups_admin_edit_metabox_add_new_members' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_groups_admin_edit_metabox_add_new_members()}.
	 *
	 * @deprecated Use bboss_groups_admin_edit_metabox_add_new_members() instead.
	 */
	function bp_groups_admin_edit_metabox_add_new_members( $item ) {
		return bboss_groups_admin_edit_metabox_add_new_members( $item );
	}
}
if ( function_exists( 'bboss_groups_admin_edit_metabox_group_parent' ) && ! function_exists( 'bp_groups_admin_edit_metabox_group_parent' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_groups_admin_edit_metabox_group_parent()}.
	 *
	 * @deprecated Use bboss_groups_admin_edit_metabox_group_parent() instead.
	 */
	function bp_groups_admin_edit_metabox_group_parent( $item ) {
		return bboss_groups_admin_edit_metabox_group_parent( $item );
	}
}
if ( function_exists( 'bboss_groups_admin_edit_metabox_group_type' ) && ! function_exists( 'bp_groups_admin_edit_metabox_group_type' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_groups_admin_edit_metabox_group_type()}.
	 *
	 * @deprecated Use bboss_groups_admin_edit_metabox_group_type() instead.
	 */
	function bp_groups_admin_edit_metabox_group_type( ?BBoss_Groups_Group $group = null ) {
		return bboss_groups_admin_edit_metabox_group_type( $group );
	}
}
if ( function_exists( 'bboss_groups_admin_edit_metabox_members' ) && ! function_exists( 'bp_groups_admin_edit_metabox_members' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_groups_admin_edit_metabox_members()}.
	 *
	 * @deprecated Use bboss_groups_admin_edit_metabox_members() instead.
	 */
	function bp_groups_admin_edit_metabox_members( $item ) {
		return bboss_groups_admin_edit_metabox_members( $item );
	}
}
if ( function_exists( 'bboss_groups_admin_edit_metabox_settings' ) && ! function_exists( 'bp_groups_admin_edit_metabox_settings' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_groups_admin_edit_metabox_settings()}.
	 *
	 * @deprecated Use bboss_groups_admin_edit_metabox_settings() instead.
	 */
	function bp_groups_admin_edit_metabox_settings( $item ) {
		return bboss_groups_admin_edit_metabox_settings( $item );
	}
}
if ( function_exists( 'bboss_groups_admin_edit_metabox_status' ) && ! function_exists( 'bp_groups_admin_edit_metabox_status' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_groups_admin_edit_metabox_status()}.
	 *
	 * @deprecated Use bboss_groups_admin_edit_metabox_status() instead.
	 */
	function bp_groups_admin_edit_metabox_status( $item ) {
		return bboss_groups_admin_edit_metabox_status( $item );
	}
}
if ( function_exists( 'bboss_groups_admin_get_usernames_from_ids' ) && ! function_exists( 'bp_groups_admin_get_usernames_from_ids' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_groups_admin_get_usernames_from_ids()}.
	 *
	 * @deprecated Use bboss_groups_admin_get_usernames_from_ids() instead.
	 */
	function bp_groups_admin_get_usernames_from_ids( $user_ids = array() ) {
		return bboss_groups_admin_get_usernames_from_ids( $user_ids );
	}
}
if ( function_exists( 'bboss_groups_admin_group_type_listing_add_groups_tab' ) && ! function_exists( 'bp_groups_admin_group_type_listing_add_groups_tab' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_groups_admin_group_type_listing_add_groups_tab()}.
	 *
	 * @deprecated Use bboss_groups_admin_group_type_listing_add_groups_tab() instead.
	 */
	function bp_groups_admin_group_type_listing_add_groups_tab() {
		return bboss_groups_admin_group_type_listing_add_groups_tab();
	}
}
if ( function_exists( 'bboss_groups_admin_groups_type_change_notice' ) && ! function_exists( 'bp_groups_admin_groups_type_change_notice' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_groups_admin_groups_type_change_notice()}.
	 *
	 * @deprecated Use bboss_groups_admin_groups_type_change_notice() instead.
	 */
	function bp_groups_admin_groups_type_change_notice() {
		return bboss_groups_admin_groups_type_change_notice();
	}
}
if ( function_exists( 'bboss_groups_admin_index' ) && ! function_exists( 'bp_groups_admin_index' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_groups_admin_index()}.
	 *
	 * @deprecated Use bboss_groups_admin_index() instead.
	 */
	function bp_groups_admin_index() {
		return bboss_groups_admin_index();
	}
}
if ( function_exists( 'bboss_groups_admin_load' ) && ! function_exists( 'bp_groups_admin_load' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_groups_admin_load()}.
	 *
	 * @deprecated Use bboss_groups_admin_load() instead.
	 */
	function bp_groups_admin_load() {
		return bboss_groups_admin_load();
	}
}
if ( function_exists( 'bboss_groups_admin_process_group_type_bulk_changes' ) && ! function_exists( 'bp_groups_admin_process_group_type_bulk_changes' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_groups_admin_process_group_type_bulk_changes()}.
	 *
	 * @deprecated Use bboss_groups_admin_process_group_type_bulk_changes() instead.
	 */
	function bp_groups_admin_process_group_type_bulk_changes( $doaction ) {
		return bboss_groups_admin_process_group_type_bulk_changes( $doaction );
	}
}
if ( function_exists( 'bboss_groups_admin_screen_options' ) && ! function_exists( 'bp_groups_admin_screen_options' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_groups_admin_screen_options()}.
	 *
	 * @deprecated Use bboss_groups_admin_screen_options() instead.
	 */
	function bp_groups_admin_screen_options( $value, $option, $new_value ) {
		return bboss_groups_admin_screen_options( $value, $option, $new_value );
	}
}
if ( function_exists( 'bboss_groups_process_group_type_update' ) && ! function_exists( 'bp_groups_process_group_type_update' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_groups_process_group_type_update()}.
	 *
	 * @deprecated Use bboss_groups_process_group_type_update() instead.
	 */
	function bp_groups_process_group_type_update( $group_id ) {
		return bboss_groups_process_group_type_update( $group_id );
	}
}
if ( function_exists( 'bboss_import_profile_types_admin_menu' ) && ! function_exists( 'bp_import_profile_types_admin_menu' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_import_profile_types_admin_menu()}.
	 *
	 * @deprecated Use bboss_import_profile_types_admin_menu() instead.
	 */
	function bp_import_profile_types_admin_menu() {
		return bboss_import_profile_types_admin_menu();
	}
}
if ( function_exists( 'bboss_media_get_tools_media_settings_admin_tabs' ) && ! function_exists( 'bp_media_get_tools_media_settings_admin_tabs' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_media_get_tools_media_settings_admin_tabs()}.
	 *
	 * @deprecated Use bboss_media_get_tools_media_settings_admin_tabs() instead.
	 */
	function bp_media_get_tools_media_settings_admin_tabs( $tabs ) {
		return bboss_media_get_tools_media_settings_admin_tabs( $tabs );
	}
}
if ( function_exists( 'bboss_member_type_import_submenu_page' ) && ! function_exists( 'bp_member_type_import_submenu_page' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_member_type_import_submenu_page()}.
	 *
	 * @deprecated Use bboss_member_type_import_submenu_page() instead.
	 */
	function bp_member_type_import_submenu_page() {
		return bboss_member_type_import_submenu_page();
	}
}
if ( function_exists( 'bboss_moderation_admin_category_listing_add_tab' ) && ! function_exists( 'bp_moderation_admin_category_listing_add_tab' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_moderation_admin_category_listing_add_tab()}.
	 *
	 * @deprecated Use bboss_moderation_admin_category_listing_add_tab() instead.
	 */
	function bp_moderation_admin_category_listing_add_tab() {
		return bboss_moderation_admin_category_listing_add_tab();
	}
}
if ( function_exists( 'bboss_moderation_get_settings_fields' ) && ! function_exists( 'bp_moderation_get_settings_fields' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_moderation_get_settings_fields()}.
	 *
	 * @deprecated Use bboss_moderation_get_settings_fields() instead.
	 */
	function bp_moderation_get_settings_fields() {
		return bboss_moderation_get_settings_fields();
	}
}
if ( function_exists( 'bboss_moderation_get_settings_fields_for_section' ) && ! function_exists( 'bp_moderation_get_settings_fields_for_section' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_moderation_get_settings_fields_for_section()}.
	 *
	 * @deprecated Use bboss_moderation_get_settings_fields_for_section() instead.
	 */
	function bp_moderation_get_settings_fields_for_section( $section_id = '' ) {
		return bboss_moderation_get_settings_fields_for_section( $section_id );
	}
}
if ( function_exists( 'bboss_moderation_get_settings_sections' ) && ! function_exists( 'bp_moderation_get_settings_sections' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_moderation_get_settings_sections()}.
	 *
	 * @deprecated Use bboss_moderation_get_settings_sections() instead.
	 */
	function bp_moderation_get_settings_sections() {
		return bboss_moderation_get_settings_sections();
	}
}
if ( function_exists( 'bboss_process_create_group_admin' ) && ! function_exists( 'bp_process_create_group_admin' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_process_create_group_admin()}.
	 *
	 * @deprecated Use bboss_process_create_group_admin() instead.
	 */
	function bp_process_create_group_admin() {
		return bboss_process_create_group_admin();
	}
}
if ( function_exists( 'bboss_register_group_type_sections_filters_actions' ) && ! function_exists( 'bp_register_group_type_sections_filters_actions' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_register_group_type_sections_filters_actions()}.
	 *
	 * @deprecated Use bboss_register_group_type_sections_filters_actions() instead.
	 */
	function bp_register_group_type_sections_filters_actions() {
		return bboss_register_group_type_sections_filters_actions();
	}
}
if ( function_exists( 'bboss_register_learndash_integration' ) && ! function_exists( 'bp_register_learndash_integration' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_register_learndash_integration()}.
	 *
	 * @deprecated Use bboss_register_learndash_integration() instead.
	 */
	function bp_register_learndash_integration() {
		return bboss_register_learndash_integration();
	}
}
if ( function_exists( 'bboss_register_member_type_import_submenu_page' ) && ! function_exists( 'bp_register_member_type_import_submenu_page' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_register_member_type_import_submenu_page()}.
	 *
	 * @deprecated Use bboss_register_member_type_import_submenu_page() instead.
	 */
	function bp_register_member_type_import_submenu_page() {
		return bboss_register_member_type_import_submenu_page();
	}
}
if ( function_exists( 'bboss_repair_community_submenu_page' ) && ! function_exists( 'bp_repair_community_submenu_page' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_repair_community_submenu_page()}.
	 *
	 * @deprecated Use bboss_repair_community_submenu_page() instead.
	 */
	function bp_repair_community_submenu_page() {
		return bboss_repair_community_submenu_page();
	}
}
if ( function_exists( 'bboss_save_group_type_post_meta_box_data' ) && ! function_exists( 'bp_save_group_type_post_meta_box_data' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_save_group_type_post_meta_box_data()}.
	 *
	 * @deprecated Use bboss_save_group_type_post_meta_box_data() instead.
	 */
	function bp_save_group_type_post_meta_box_data( $post_id ) {
		return bboss_save_group_type_post_meta_box_data( $post_id );
	}
}
if ( function_exists( 'bboss_save_group_type_role_labels_post_meta_box_data' ) && ! function_exists( 'bp_save_group_type_role_labels_post_meta_box_data' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_save_group_type_role_labels_post_meta_box_data()}.
	 *
	 * @deprecated Use bboss_save_group_type_role_labels_post_meta_box_data() instead.
	 */
	function bp_save_group_type_role_labels_post_meta_box_data( $post_id ) {
		return bboss_save_group_type_role_labels_post_meta_box_data( $post_id );
	}
}
if ( function_exists( 'bboss_search_get_settings_fields' ) && ! function_exists( 'bp_search_get_settings_fields' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_search_get_settings_fields()}.
	 *
	 * @deprecated Use bboss_search_get_settings_fields() instead.
	 */
	function bp_search_get_settings_fields() {
		return bboss_search_get_settings_fields();
	}
}
if ( function_exists( 'bboss_search_get_settings_fields_for_section' ) && ! function_exists( 'bp_search_get_settings_fields_for_section' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_search_get_settings_fields_for_section()}.
	 *
	 * @deprecated Use bboss_search_get_settings_fields_for_section() instead.
	 */
	function bp_search_get_settings_fields_for_section( $section_id = '' ) {
		return bboss_search_get_settings_fields_for_section( $section_id );
	}
}
if ( function_exists( 'bboss_search_get_settings_sections' ) && ! function_exists( 'bp_search_get_settings_sections' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_search_get_settings_sections()}.
	 *
	 * @deprecated Use bboss_search_get_settings_sections() instead.
	 */
	function bp_search_get_settings_sections() {
		return bboss_search_get_settings_sections();
	}
}
if ( function_exists( 'bboss_xprofile_admin_form_field_types' ) && ! function_exists( 'bp_xprofile_admin_form_field_types' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_xprofile_admin_form_field_types()}.
	 *
	 * @deprecated Use bboss_xprofile_admin_form_field_types() instead.
	 */
	function bp_xprofile_admin_form_field_types( $select_field_type = '' ) {
		return bboss_xprofile_admin_form_field_types( $select_field_type );
	}
}
// @bbwt-com-end compat-layer

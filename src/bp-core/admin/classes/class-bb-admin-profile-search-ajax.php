<?php
/**
 * BuddyBoss Profile Search Admin AJAX Handler
 *
 * Handles AJAX requests for Profile Search form fields CRUD
 * in the Settings 2.0 admin interface.
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Core\Administration
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class BB_Admin_Profile_Search_Ajax
 *
 * @since BuddyBoss [BBVERSION]
 */
class BB_Admin_Profile_Search_Ajax {

	/**
	 * Nonce action (shared with BB_Admin_Settings_Ajax).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'bb_admin_settings';

	/**
	 * Verify AJAX request (capability + nonce).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	private function bb_verify_request() {
		bb_admin_verify_ajax_request( self::NONCE_ACTION );
	}

	/**
	 * Constructor.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function __construct() {
		$this->bb_register_ajax_handlers();
	}

	/**
	 * Register AJAX handlers.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	private function bb_register_ajax_handlers() {
		add_action( 'wp_ajax_bb_admin_get_profile_search_fields', array( $this, 'get_search_fields' ) );
		add_action( 'wp_ajax_bb_admin_save_profile_search_field', array( $this, 'save_search_field' ) );
		add_action( 'wp_ajax_bb_admin_delete_profile_search_field', array( $this, 'delete_search_field' ) );
		add_action( 'wp_ajax_bb_admin_reorder_profile_search_fields', array( $this, 'reorder_search_fields' ) );

		// Register profile search toggle in the allowed platform options whitelist.
		add_filter( 'bb_admin_allowed_platform_settings', array( $this, 'bb_register_allowed_options' ) );
	}

	/**
	 * Register profile search options in the allowed platform settings whitelist.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $allowed Allowed options map.
	 *
	 * @return array
	 */
	public function bb_register_allowed_options( $allowed ) {
		$allowed['bp-enable-profile-search'] = 'absint';

		return $allowed;
	}

	/**
	 * Get the main form ID, auto-creating if needed.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return int Form post ID.
	 */
	private function bb_get_form_id() {
		$form_id = bp_profile_search_main_form();

		if ( empty( $form_id ) || ! get_post( $form_id ) ) {
			bp_profile_search_add_main_form();
			$form_id = bp_profile_search_main_form();
		}

		return $form_id;
	}

	/**
	 * Get all profile search fields (saved + available).
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function get_search_fields() {
		$this->bb_verify_request();

		$form_id = $this->bb_get_form_id();
		$meta    = bp_ps_meta( $form_id );

		list( $groups, $fields ) = bp_ps_get_fields();

		// Build saved fields list.
		$saved_fields = array();
		$field_codes  = isset( $meta['field_code'] ) ? $meta['field_code'] : array();
		$field_labels = isset( $meta['field_label'] ) ? $meta['field_label'] : array();
		$field_descs  = isset( $meta['field_desc'] ) ? $meta['field_desc'] : array();
		$field_modes  = isset( $meta['field_mode'] ) ? $meta['field_mode'] : array();

		foreach ( $field_codes as $index => $code ) {
			$field_obj = isset( $fields[ $code ] ) ? $fields[ $code ] : null;

			// Skip fields that no longer exist in available fields (matches legacy behavior).
			if ( empty( $field_obj ) ) {
				continue;
			}

			// Build available search modes for this field.
			$available_modes = array();
			$filter_labels   = bp_ps_Fields::get_filters( $field_obj );
			foreach ( $filter_labels as $filter_value => $filter_label ) {
				$available_modes[] = array(
					'value' => $filter_value,
					'label' => $filter_label,
				);
			}

			// Note: 'id' is the array index in bp_ps_options meta, NOT a DB primary key.
			// JS uses this value for delete (field_index) and reorder (field_order).
			// After reorder, JS must reload fields to get updated indices.
			$saved_fields[] = array(
				'id'                => $index,
				'code'              => $code,
				'name'              => $field_obj->name,
				'label'             => isset( $field_labels[ $index ] ) ? $field_labels[ $index ] : '',
				'description'       => isset( $field_descs[ $index ] ) ? $field_descs[ $index ] : '',
				'search_mode'       => isset( $field_modes[ $index ] ) ? $field_modes[ $index ] : '',
				'type'              => isset( $field_obj->type ) ? $field_obj->type : '',
				'format'            => isset( $field_obj->format ) ? $field_obj->format : '',
				'available_modes'   => $available_modes,
				'is_repeater_group' => $this->bb_is_repeater_group_field( $field_obj ),
			);
		}

		// Build available fields grouped by group name.
		$available_groups = array();
		foreach ( $groups as $group_name => $group_fields ) {
			$group_items = array();
			foreach ( $group_fields as $gf ) {
				$field_obj     = isset( $fields[ $gf['id'] ] ) ? $fields[ $gf['id'] ] : null;
				$filter_labels = $field_obj ? bp_ps_Fields::get_filters( $field_obj ) : array();
				$modes         = array();
				foreach ( $filter_labels as $fv => $fl ) {
					$modes[] = array(
						'value' => $fv,
						'label' => $fl,
					);
				}
				$group_items[] = array(
					'code'              => $gf['id'],
					'name'              => $gf['name'],
					'type'              => $field_obj && isset( $field_obj->type ) ? $field_obj->type : '',
					'format'            => $field_obj && isset( $field_obj->format ) ? $field_obj->format : '',
					'available_modes'   => $modes,
					'is_repeater_group' => $this->bb_is_repeater_group_field( $field_obj ),
				);
			}
			$available_groups[] = array(
				'label'  => $group_name,
				'fields' => $group_items,
			);
		}

		wp_send_json_success(
			array(
				'fields'           => $saved_fields,
				'available_fields' => $available_groups,
			)
		);
	}

	/**
	 * Save (add or update) a profile search field.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function save_search_field() {
		$this->bb_verify_request();

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() above.
		$field_code  = isset( $_POST['field_code'] ) ? sanitize_key( wp_unslash( $_POST['field_code'] ) ) : '';
		$field_label = isset( $_POST['field_label'] ) ? sanitize_text_field( wp_unslash( $_POST['field_label'] ) ) : '';
		$field_desc  = isset( $_POST['field_desc'] ) ? sanitize_textarea_field( wp_unslash( $_POST['field_desc'] ) ) : '';
		$field_mode  = isset( $_POST['field_mode'] ) ? sanitize_key( wp_unslash( $_POST['field_mode'] ) ) : '';
		$field_index = isset( $_POST['field_index'] ) && '' !== $_POST['field_index'] ? absint( wp_unslash( $_POST['field_index'] ) ) : null;
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( empty( $field_code ) ) {
			wp_send_json_error( array( 'message' => __( 'Please select a field.', 'buddyboss' ) ) );
		}

		// Validate field code exists in available fields.
		list( , $fields ) = bp_ps_get_fields();
		if ( ! isset( $fields[ $field_code ] ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid field selected.', 'buddyboss' ) ) );
		}

		// Validate search mode.
		$field_obj  = $fields[ $field_code ];
		$field_mode = bp_ps_Fields::valid_filter( $field_obj, $field_mode );

		$form_id = $this->bb_get_form_id();
		$meta    = bp_ps_meta( $form_id );

		$codes  = isset( $meta['field_code'] ) ? $meta['field_code'] : array();
		$labels = isset( $meta['field_label'] ) ? $meta['field_label'] : array();
		$descs  = isset( $meta['field_desc'] ) ? $meta['field_desc'] : array();
		$modes  = isset( $meta['field_mode'] ) ? $meta['field_mode'] : array();

		if ( null !== $field_index && isset( $codes[ $field_index ] ) ) {
			// Updating existing field — allow same code at same index, reject duplicate at other index.
			if ( 'heading' !== $field_code && in_array( $field_code, $codes, true ) && $codes[ $field_index ] !== $field_code ) {
				wp_send_json_error( array( 'message' => __( 'This field has already been added.', 'buddyboss' ) ) );
			}

			$codes[ $field_index ]  = $field_code;
			$labels[ $field_index ] = $field_label;
			$descs[ $field_index ]  = $field_desc;
			$modes[ $field_index ]  = $field_mode;
		} else {
			// Adding new field — reject duplicates (except heading, which can appear multiple times).
			if ( 'heading' !== $field_code && in_array( $field_code, $codes, true ) ) {
				wp_send_json_error( array( 'message' => __( 'This field has already been added.', 'buddyboss' ) ) );
			}

			$codes[]  = $field_code;
			$labels[] = $field_label;
			$descs[]  = $field_desc;
			$modes[]  = $field_mode;
		}

		$meta['field_code']  = array_values( $codes );
		$meta['field_label'] = array_values( $labels );
		$meta['field_desc']  = array_values( $descs );
		$meta['field_mode']  = array_values( $modes );

		update_post_meta( $form_id, 'bp_ps_options', $meta );

		/**
		 * Fires after a profile search field is saved (created or updated).
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param int    $form_id    Form post ID.
		 * @param string $field_code Field code identifier.
		 * @param array  $meta       Updated form meta after save.
		 */
		do_action( 'bb_profile_search_field_saved', $form_id, $field_code, $meta );

		wp_send_json_success( array( 'message' => __( 'Field saved.', 'buddyboss' ) ) );
	}

	/**
	 * Delete a profile search field by index.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function delete_search_field() {
		$this->bb_verify_request();

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() above.
		if ( ! isset( $_POST['field_index'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid field index.', 'buddyboss' ) ) );
		}
		$field_index = absint( wp_unslash( $_POST['field_index'] ) );
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		$form_id = $this->bb_get_form_id();
		$meta    = bp_ps_meta( $form_id );

		$codes = isset( $meta['field_code'] ) ? $meta['field_code'] : array();

		if ( ! isset( $codes[ $field_index ] ) ) {
			wp_send_json_error( array( 'message' => __( 'Field not found.', 'buddyboss' ) ) );
		}

		// Remove the field at the given index from all arrays.
		unset( $meta['field_code'][ $field_index ] );
		unset( $meta['field_label'][ $field_index ] );
		unset( $meta['field_desc'][ $field_index ] );
		unset( $meta['field_mode'][ $field_index ] );

		// Reindex arrays.
		$meta['field_code']  = array_values( $meta['field_code'] );
		$meta['field_label'] = array_values( $meta['field_label'] );
		$meta['field_desc']  = array_values( $meta['field_desc'] );
		$meta['field_mode']  = array_values( $meta['field_mode'] );

		update_post_meta( $form_id, 'bp_ps_options', $meta );

		/**
		 * Fires after a profile search field is deleted.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param int   $form_id     Form post ID.
		 * @param int   $field_index Deleted field index.
		 * @param array $meta        Updated form meta after deletion.
		 */
		do_action( 'bb_profile_search_field_deleted', $form_id, $field_index, $meta );

		wp_send_json_success( array( 'message' => __( 'Field removed.', 'buddyboss' ) ) );
	}

	/**
	 * Reorder profile search fields.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function reorder_search_fields() {
		$this->bb_verify_request();

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by $this->bb_verify_request() above.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized via array_map( 'absint' ).
		$field_order = isset( $_POST['field_order'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['field_order'] ) ) : array();
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( empty( $field_order ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid field order.', 'buddyboss' ) ) );
		}

		$form_id = $this->bb_get_form_id();
		$meta    = bp_ps_meta( $form_id );

		$codes  = isset( $meta['field_code'] ) ? $meta['field_code'] : array();
		$labels = isset( $meta['field_label'] ) ? $meta['field_label'] : array();
		$descs  = isset( $meta['field_desc'] ) ? $meta['field_desc'] : array();
		$modes  = isset( $meta['field_mode'] ) ? $meta['field_mode'] : array();

		// Resolve the live profile-field map so we can distinguish orphan codes
		// (codes whose underlying xprofile field has been deleted) from live
		// ones. `get_search_fields()` already filters orphans before sending
		// the list to the React UI, so the client's `field_order` payload only
		// contains the live indices — without this resolution the count check
		// below would compare 2 client IDs against 3 stored codes and reject
		// the reorder with a "field count mismatch" error even though the
		// client sent every index it could see.
		list( , $live_fields ) = bp_ps_get_fields();

		$new_codes  = array();
		$new_labels = array();
		$new_descs  = array();
		$new_modes  = array();

		foreach ( $field_order as $old_index ) {
			if ( isset( $codes[ $old_index ] ) ) {
				$new_codes[]  = $codes[ $old_index ];
				$new_labels[] = isset( $labels[ $old_index ] ) ? $labels[ $old_index ] : '';
				$new_descs[]  = isset( $descs[ $old_index ] ) ? $descs[ $old_index ] : '';
				$new_modes[]  = isset( $modes[ $old_index ] ) ? $modes[ $old_index ] : '';
			}
		}

		// Count live codes — those whose underlying xprofile field still
		// resolves. Mirrors the orphan filter in `get_search_fields()` so the
		// validation check uses the same denominator the client used when it
		// built `$field_order`.
		$live_count = 0;
		foreach ( $codes as $code ) {
			if ( isset( $live_fields[ $code ] ) ) {
				++$live_count;
			}
		}

		// Validate that no live fields were lost during reorder. Orphans are
		// allowed to drop off — they were never visible in the UI and storing
		// them indefinitely just bloats the meta.
		if ( count( $new_codes ) !== $live_count ) {
			wp_send_json_error( array( 'message' => __( 'Invalid field order — field count mismatch.', 'buddyboss' ) ) );
		}

		$meta['field_code']  = $new_codes;
		$meta['field_label'] = $new_labels;
		$meta['field_desc']  = $new_descs;
		$meta['field_mode']  = $new_modes;

		update_post_meta( $form_id, 'bp_ps_options', $meta );

		wp_send_json_success( array( 'message' => __( 'Order updated.', 'buddyboss' ) ) );
	}

	/**
	 * Check if a profile search field belongs to a repeater group.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param object|null $field_obj Profile search field object.
	 *
	 * @return bool True if the field belongs to a repeater-enabled group.
	 */
	private function bb_is_repeater_group_field( $field_obj ) {
		if ( empty( $field_obj ) || empty( $field_obj->group_id ) ) {
			return false;
		}

		return 'on' === bp_xprofile_get_meta( $field_obj->group_id, 'group', 'is_repeater_enabled', true );
	}
}

new BB_Admin_Profile_Search_Ajax();

<?php
/**
 * BuddyBoss Moderation Admin AJAX Handler
 *
 * Handles AJAX requests for Reporting Categories CRUD
 * in the Settings 2.0 admin interface.
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Core\Administration
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class BB_Admin_Moderation_Ajax
 *
 * @since BuddyBoss [BBVERSION]
 */
class BB_Admin_Moderation_Ajax {

	/**
	 * Nonce action (shared with BB_Admin_Settings_Ajax).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'bb_admin_settings';

	/**
	 * Taxonomy name.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var string
	 */
	const TAXONOMY = 'bpm_category';

	/**
	 * Term meta key for "show when reporting".
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var string
	 */
	const META_KEY = 'bb_category_show_when_reporting';

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
		add_action( 'wp_ajax_bb_admin_get_reporting_categories', array( $this, 'bb_get_reporting_categories' ) );
		add_action( 'wp_ajax_bb_admin_create_reporting_category', array( $this, 'bb_create_reporting_category' ) );
		add_action( 'wp_ajax_bb_admin_update_reporting_category', array( $this, 'bb_update_reporting_category' ) );
		add_action( 'wp_ajax_bb_admin_delete_reporting_category', array( $this, 'bb_delete_reporting_category' ) );
	}

	/**
	 * Verify AJAX request (nonce + capability).
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	private function bb_verify_request() {
		if ( ! bp_current_user_can( 'bp_moderate' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'You do not have permission to perform this action.', 'buddyboss' ) ),
				403
			);
		}

		check_ajax_referer( self::NONCE_ACTION, 'nonce' );
	}

	/**
	 * Get all reporting categories.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function bb_get_reporting_categories() {
		$this->bb_verify_request();

		$terms = get_terms(
			array(
				'taxonomy'   => self::TAXONOMY,
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
				'number'     => 500,
			)
		);

		if ( is_wp_error( $terms ) ) {
			wp_send_json_error( array( 'message' => $terms->get_error_message() ) );
		}

		$categories = array();

		foreach ( $terms as $term ) {
			$show_when = get_term_meta( $term->term_id, self::META_KEY, true );
			if ( empty( $show_when ) ) {
				$show_when = 'content_members';
			}

			// Decode HTML entities — WP escapes term name/description for HTML context,
			// but React handles its own escaping so we send plain text via JSON.
			$categories[] = array(
				'id'                        => $term->term_id,
				'name'                      => wp_specialchars_decode( $term->name, ENT_QUOTES ),
				'description'               => wp_specialchars_decode( $term->description, ENT_QUOTES ),
				'show_when_reporting'       => $show_when,
				'show_when_reporting_label' => wp_specialchars_decode( $this->bb_get_show_when_label( $show_when ), ENT_QUOTES ),
			);
		}

		// Also return the "show when" options for the modal select.
		// Labels may contain HTML entities from esc_html__(), decode for React.
		$show_when_options = array();
		if ( function_exists( 'bb_moderation_get_reporting_category_fields_array' ) ) {
			$raw = bb_moderation_get_reporting_category_fields_array();
			foreach ( $raw as $value => $label ) {
				$show_when_options[] = array(
					'label' => wp_specialchars_decode( $label, ENT_QUOTES ),
					'value' => $value,
				);
			}
		}

		wp_send_json_success(
			array(
				'categories'       => $categories,
				'show_when_options' => $show_when_options,
			)
		);
	}

	/**
	 * Create a new reporting category.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function bb_create_reporting_category() {
		$this->bb_verify_request();

		$name        = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$description = isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '';
		$show_when   = isset( $_POST['show_when_reporting'] ) ? sanitize_key( wp_unslash( $_POST['show_when_reporting'] ) ) : 'content_members';

		if ( empty( $name ) ) {
			wp_send_json_error( array( 'message' => __( 'Category name is required.', 'buddyboss' ) ) );
		}

		// Validate show_when value.
		$valid_values = array( 'content', 'members', 'content_members' );
		if ( ! in_array( $show_when, $valid_values, true ) ) {
			$show_when = 'content_members';
		}

		$result = wp_insert_term(
			$name,
			self::TAXONOMY,
			array( 'description' => $description )
		);

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		$term_id = $result['term_id'];
		update_term_meta( $term_id, self::META_KEY, $show_when );

		$term = get_term( $term_id, self::TAXONOMY );

		wp_send_json_success(
			array(
				'category' => array(
					'id'                        => $term->term_id,
					'name'                      => wp_specialchars_decode( $term->name, ENT_QUOTES ),
					'description'               => wp_specialchars_decode( $term->description, ENT_QUOTES ),
					'show_when_reporting'        => $show_when,
					'show_when_reporting_label'  => wp_specialchars_decode( $this->bb_get_show_when_label( $show_when ), ENT_QUOTES ),
				),
			)
		);
	}

	/**
	 * Update an existing reporting category.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function bb_update_reporting_category() {
		$this->bb_verify_request();

		$term_id     = isset( $_POST['term_id'] ) ? absint( $_POST['term_id'] ) : 0;
		$name        = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$description = isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '';
		$show_when   = isset( $_POST['show_when_reporting'] ) ? sanitize_key( wp_unslash( $_POST['show_when_reporting'] ) ) : '';

		if ( empty( $term_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid category ID.', 'buddyboss' ) ) );
		}

		if ( empty( $name ) ) {
			wp_send_json_error( array( 'message' => __( 'Category name is required.', 'buddyboss' ) ) );
		}

		// Validate show_when value.
		$valid_values = array( 'content', 'members', 'content_members' );
		if ( ! empty( $show_when ) && ! in_array( $show_when, $valid_values, true ) ) {
			$show_when = 'content_members';
		}

		$result = wp_update_term(
			$term_id,
			self::TAXONOMY,
			array(
				'name'        => $name,
				'description' => $description,
			)
		);

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		if ( ! empty( $show_when ) ) {
			update_term_meta( $term_id, self::META_KEY, $show_when );
		}

		$term      = get_term( $term_id, self::TAXONOMY );
		$meta_val  = get_term_meta( $term_id, self::META_KEY, true );
		$show_val  = $meta_val ?: 'content_members';

		wp_send_json_success(
			array(
				'category' => array(
					'id'                        => $term->term_id,
					'name'                      => wp_specialchars_decode( $term->name, ENT_QUOTES ),
					'description'               => wp_specialchars_decode( $term->description, ENT_QUOTES ),
					'show_when_reporting'        => $show_val,
					'show_when_reporting_label'  => wp_specialchars_decode( $this->bb_get_show_when_label( $show_val ), ENT_QUOTES ),
				),
			)
		);
	}

	/**
	 * Delete a reporting category.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function bb_delete_reporting_category() {
		$this->bb_verify_request();

		$term_id = isset( $_POST['term_id'] ) ? absint( $_POST['term_id'] ) : 0;

		if ( empty( $term_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid category ID.', 'buddyboss' ) ) );
		}

		$result = wp_delete_term( $term_id, self::TAXONOMY );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		if ( false === $result ) {
			wp_send_json_error( array( 'message' => __( 'Category not found.', 'buddyboss' ) ) );
		}

		wp_send_json_success();
	}

	/**
	 * Get human-readable label for show_when_reporting value.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $value Meta value.
	 * @return string Label.
	 */
	private function bb_get_show_when_label( $value ) {
		if ( function_exists( 'bb_moderation_get_reporting_category_fields_array' ) ) {
			$options = bb_moderation_get_reporting_category_fields_array();
			if ( isset( $options[ $value ] ) ) {
				return $options[ $value ];
			}
		}

		// Fallback labels.
		$labels = array(
			'content'         => __( 'Content', 'buddyboss' ),
			'members'         => __( 'Members', 'buddyboss' ),
			'content_members' => __( 'Content & Members', 'buddyboss' ),
		);

		return isset( $labels[ $value ] ) ? $labels[ $value ] : __( 'Content', 'buddyboss' );
	}
}

// Initialize.
new BB_Admin_Moderation_Ajax();

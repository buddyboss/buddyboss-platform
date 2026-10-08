<?php
/**
 * BuddyBoss Moderation Admin AJAX Handler
 *
 * Handles AJAX requests for Reporting Categories CRUD
 * in the Settings 2.0 admin interface.
 *
 * @since BuddyBoss 3.0.0
 * @package BuddyBoss\Core\Administration
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class BB_Admin_Moderation_Ajax
 *
 * @since BuddyBoss 3.0.0
 */
class BB_Admin_Moderation_Ajax {

	/**
	 * Nonce action (shared with BB_Admin_Settings_Ajax).
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'bb_admin_settings';

	/**
	 * Taxonomy name.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @var string
	 */
	const TAXONOMY = 'bpm_category';

	/**
	 * Term meta key for "show when reporting".
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @var string
	 */
	const META_KEY = 'bb_category_show_when_reporting';

	/**
	 * Valid "show when reporting" values.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @var array
	 */
	const VALID_SHOW_WHEN = array( 'content', 'members', 'content_members' );

	/**
	 * Constructor.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	public function __construct() {
		$this->bb_register_ajax_handlers();
	}

	/**
	 * Register AJAX handlers.
	 *
	 * @since BuddyBoss 3.0.0
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
	 * @since BuddyBoss 3.0.0
	 */
	private function bb_verify_request() {
		if ( ! bp_current_user_can( 'bp_moderate' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'You do not have permission to perform this action.', 'buddyboss-platform' ) ),
				403
			);
		}

		check_ajax_referer( self::NONCE_ACTION, 'nonce' );
	}

	/**
	 * Get all reporting categories.
	 *
	 * @since BuddyBoss 3.0.0
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
				$show_when = 'content';
			}

			$categories[] = $this->bb_format_category( $term, $show_when );
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
				'categories'        => $categories,
				'show_when_options' => $show_when_options,
			)
		);
	}

	/**
	 * Create a new reporting category.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	public function bb_create_reporting_category() {
		$this->bb_verify_request();

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified in bb_verify_request() above.
		$name        = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$description = isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '';
		$show_when   = isset( $_POST['show_when_reporting'] ) ? sanitize_key( wp_unslash( $_POST['show_when_reporting'] ) ) : 'content';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( empty( $name ) ) {
			wp_send_json_error( array( 'message' => __( 'Category name is required.', 'buddyboss-platform' ) ) );
		}

		// Validate show_when value.
		$valid_values = self::VALID_SHOW_WHEN;
		if ( ! in_array( $show_when, $valid_values, true ) ) {
			$show_when = 'content';
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
				'category' => $this->bb_format_category( $term, $show_when ),
			)
		);
	}

	/**
	 * Update an existing reporting category.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	public function bb_update_reporting_category() {
		$this->bb_verify_request();

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified in bb_verify_request() above.
		$term_id     = isset( $_POST['term_id'] ) ? absint( $_POST['term_id'] ) : 0;
		$name        = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$description = isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '';
		$show_when   = isset( $_POST['show_when_reporting'] ) ? sanitize_key( wp_unslash( $_POST['show_when_reporting'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( empty( $term_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid category ID.', 'buddyboss-platform' ) ) );
		}

		if ( empty( $name ) ) {
			wp_send_json_error( array( 'message' => __( 'Category name is required.', 'buddyboss-platform' ) ) );
		}

		// Validate show_when value.
		$valid_values = self::VALID_SHOW_WHEN;
		if ( ! empty( $show_when ) && ! in_array( $show_when, $valid_values, true ) ) {
			$show_when = 'content';
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

		$term     = get_term( $term_id, self::TAXONOMY );
		$meta_val = get_term_meta( $term_id, self::META_KEY, true );
		$show_val = ! empty( $meta_val ) ? $meta_val : 'content';

		wp_send_json_success(
			array(
				'category' => $this->bb_format_category( $term, $show_val ),
			)
		);
	}

	/**
	 * Delete a reporting category.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	public function bb_delete_reporting_category() {
		$this->bb_verify_request();

		$term_id = isset( $_POST['term_id'] ) ? absint( $_POST['term_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in bb_verify_request() above.

		if ( empty( $term_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid category ID.', 'buddyboss-platform' ) ) );
		}

		$result = wp_delete_term( $term_id, self::TAXONOMY );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		if ( false === $result ) {
			wp_send_json_error( array( 'message' => __( 'Category not found.', 'buddyboss-platform' ) ) );
		}

		wp_send_json_success();
	}

	/**
	 * Get human-readable label for show_when_reporting value.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $value Meta value.
	 * @return string Label.
	 */
	private function bb_get_show_when_label( $value ) {
		$options = bb_moderation_get_reporting_category_fields_array();

		return isset( $options[ $value ] ) ? $options[ $value ] : __( 'Content', 'buddyboss-platform' );
	}

	/**
	 * Format a category term into a response array.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param WP_Term $term      Term object.
	 * @param string  $show_when Show-when-reporting meta value.
	 *
	 * @return array Formatted category data.
	 */
	private function bb_format_category( $term, $show_when ) {
		return array(
			'id'                        => $term->term_id,
			'name'                      => wp_specialchars_decode( $term->name, ENT_QUOTES ),
			'description'               => wp_specialchars_decode( $term->description, ENT_QUOTES ),
			'show_when_reporting'       => $show_when,
			'show_when_reporting_label' => wp_specialchars_decode( $this->bb_get_show_when_label( $show_when ), ENT_QUOTES ),
		);
	}
}

// Initialize.
new BB_Admin_Moderation_Ajax();

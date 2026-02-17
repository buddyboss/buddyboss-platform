<?php
/**
 * BuddyBoss Admin Meta Field Registry
 *
 * Global registry for meta fields displayed in admin edit modals (Activity, Groups, Forums, etc.).
 * Plugins register fields via PHP and the AJAX handler + React modal
 * automatically handle fetching, rendering, and saving.
 *
 * @package BuddyBoss\Core\Administration
 * @since   BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * BB_Admin_Meta_Field_Registry class.
 *
 * Singleton registry that stores field definitions per component and provides
 * helpers to collect field data for AJAX responses and to persist
 * submitted values back to the database.
 *
 * @since BuddyBoss [BBVERSION]
 */
class BB_Admin_Meta_Field_Registry {

	/**
	 * Singleton instance.
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @var BB_Admin_Meta_Field_Registry|null
	 */
	private static $instance = null;

	/**
	 * Registered fields keyed by component.
	 * Structure: $fields[ $component ][ $field_id ] = array( ... )
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @var array
	 */
	private $fields = array();

	/**
	 * Components whose registration action has already fired.
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @var array
	 */
	private $did_register = array();

	/**
	 * Get singleton instance.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return BB_Admin_Meta_Field_Registry
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Private constructor.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	private function __construct() {}

	/**
	 * Fire the registration action for a component (once per request per component).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $component Component identifier (e.g. 'activity', 'groups', 'forums').
	 */
	public function ensure_registered( $component ) {
		if ( isset( $this->did_register[ $component ] ) ) {
			return;
		}

		$this->did_register[ $component ] = true;

		/**
		 * Fires so that plugins can register admin meta fields for a specific component.
		 *
		 * The dynamic portion of the hook name, `$component`, refers to the component
		 * identifier (e.g. 'activity', 'groups', 'forums').
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param BB_Admin_Meta_Field_Registry $registry  The registry instance.
		 * @param string                       $component The component identifier.
		 */
		do_action( "bb_register_{$component}_meta_fields", $this, $component );
	}

	/**
	 * Register a field for a component.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $component Component identifier (e.g. 'activity', 'groups').
	 * @param string $field_id  Unique field identifier within the component.
	 * @param array  $args      {
	 *     Field arguments.
	 *
	 *     @type string   $label             Field label.
	 *     @type string   $type              Field type: 'text', 'number', 'url', 'select', 'richtext', 'readonly'.
	 *     @type int      $order             Display order. Default 100.
	 *     @type string   $context           'normal' (inside form) or 'after' (below form). Default 'normal'.
	 *     @type string   $layout            'default' (full width) or 'half' (half width, grouped with adjacent half fields). Default 'default'.
	 *     @type string   $save_phase        'before' (set object properties before save) or 'after' (save meta after save). Default 'after'.
	 *     @type callable $get_value         Required. function( $item ) returning mixed.
	 *     @type callable $get_options       Optional. function( $item ) returning array for 'select' type.
	 *     @type callable $save_value        Optional. function( $item, $value ). Null = read-only.
	 *     @type callable $sanitize_callback Optional. Sanitize before save. Default 'sanitize_text_field'.
	 *     @type callable $is_visible        Optional. function( $item ) returning bool. Default true.
	 * }
	 * @return bool True on success.
	 */
	public function register( $component, $field_id, $args = array() ) {
		if ( empty( $component ) || empty( $field_id ) || ! is_string( $field_id ) ) {
			return false;
		}

		$defaults = array(
			'label'             => '',
			'type'              => 'text',
			'order'             => 100,
			'context'           => 'normal',
			'layout'            => 'default',
			'save_phase'        => 'after',
			'get_value'         => null,
			'get_options'       => null,
			'save_value'        => null,
			'sanitize_callback' => 'sanitize_text_field',
			'is_visible'        => null,
		);

		$args = wp_parse_args( $args, $defaults );

		// get_value is required.
		if ( ! is_callable( $args['get_value'] ) ) {
			return false;
		}

		if ( ! isset( $this->fields[ $component ] ) ) {
			$this->fields[ $component ] = array();
		}

		$this->fields[ $component ][ $field_id ] = $args;

		return true;
	}

	/**
	 * Get all registered fields for a component, sorted by order.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $component Component identifier.
	 * @return array
	 */
	public function get_fields( $component ) {
		$this->ensure_registered( $component );

		if ( empty( $this->fields[ $component ] ) ) {
			return array();
		}

		$fields = $this->fields[ $component ];

		// Sort by order.
		uasort(
			$fields,
			function ( $a, $b ) {
				return (int) $a['order'] - (int) $b['order'];
			}
		);

		return $fields;
	}

	/**
	 * Get fields data for a specific item (used in AJAX response).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $component Component identifier.
	 * @param object $item      The item being edited (e.g. BP_Activity_Activity, BP_Groups_Group).
	 * @return array Array of field data for JSON response.
	 */
	public function get_fields_data( $component, $item ) {
		$fields = $this->get_fields( $component );
		$data   = array();

		foreach ( $fields as $field_id => $args ) {
			// Check visibility.
			$visible = true;
			if ( is_callable( $args['is_visible'] ) ) {
				$visible = (bool) call_user_func( $args['is_visible'], $item );
			}

			$field_data = array(
				'id'       => $field_id,
				'label'    => $args['label'],
				'type'     => $args['type'],
				'context'  => $args['context'],
				'layout'   => $args['layout'],
				'visible'  => $visible,
				'value'    => null,
				'options'  => array(),
				'readonly' => ( null === $args['save_value'] ),
			);

			// Get current value.
			if ( is_callable( $args['get_value'] ) ) {
				$field_data['value'] = call_user_func( $args['get_value'], $item );
			}

			// Get options for select type.
			if ( 'select' === $args['type'] && is_callable( $args['get_options'] ) ) {
				$field_data['options'] = call_user_func( $args['get_options'], $item );
			}

			$data[] = $field_data;
		}

		return $data;
	}

	/**
	 * Save fields data from POST for a specific item.
	 *
	 * Fields are filtered by save_phase so callers can run "before" fields
	 * (which set object properties) prior to $item->save(), then run "after"
	 * fields (which persist meta) afterward.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $component Component identifier.
	 * @param object $item      The item being saved.
	 * @param string $phase     Save phase to process: 'before', 'after', or 'all'. Default 'all'.
	 */
	public function save_fields_data( $component, $item, $phase = 'all' ) {
		$fields = $this->get_fields( $component );

		foreach ( $fields as $field_id => $args ) {
			// Filter by phase.
			if ( 'all' !== $phase && $args['save_phase'] !== $phase ) {
				continue;
			}

			// Skip read-only fields.
			if ( null === $args['save_value'] || ! is_callable( $args['save_value'] ) ) {
				continue;
			}

			// Check visibility - don't save invisible fields.
			if ( is_callable( $args['is_visible'] ) && ! call_user_func( $args['is_visible'], $item ) ) {
				continue;
			}

			// phpcs:disable WordPress.Security.NonceVerification.Missing
			$post_key = 'registered_field_' . $field_id;
			if ( ! isset( $_POST[ $post_key ] ) ) {
				continue;
			}

			$raw_value = wp_unslash( $_POST[ $post_key ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			// phpcs:enable WordPress.Security.NonceVerification.Missing

			// Sanitize.
			if ( is_callable( $args['sanitize_callback'] ) ) {
				$raw_value = call_user_func( $args['sanitize_callback'], $raw_value );
			}

			// Save.
			call_user_func( $args['save_value'], $item, $raw_value );
		}
	}
}

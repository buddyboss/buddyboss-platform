<?php
/**
 * BuddyBoss Admin Meta Field Registry
 *
 * Global registry for meta fields displayed in admin edit modals (Activity, Groups, Forums, etc.).
 * Plugins register fields via PHP and the AJAX handler + React modal
 * automatically handle fetching, rendering, and saving.
 *
 * @package BuddyBoss\Core\Administration
 * @since   BuddyBoss 3.0.0
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
 * @since BuddyBoss 3.0.0
 */
class BB_Admin_Meta_Field_Registry {

	/**
	 * Singleton instance.
	 *
	 * @since BuddyBoss 3.0.0
	 * @var BB_Admin_Meta_Field_Registry|null
	 */
	private static $instance = null;

	/**
	 * Registered fields keyed by component.
	 * Structure: $fields[ $component ][ $field_id ] = array( ... )
	 *
	 * @since BuddyBoss 3.0.0
	 * @var array
	 */
	private $fields = array();

	/**
	 * Components whose registration action has already fired.
	 *
	 * @since BuddyBoss 3.0.0
	 * @var array
	 */
	private $did_register = array();

	/**
	 * Cached sorted fields per component.
	 *
	 * @since BuddyBoss 3.0.0
	 * @var array
	 */
	private $sorted_cache = array();

	/**
	 * Get singleton instance.
	 *
	 * @since BuddyBoss 3.0.0
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
	 * @since BuddyBoss 3.0.0
	 */
	private function __construct() {}

	/**
	 * Fire the registration action for a component (once per request per component).
	 *
	 * @since BuddyBoss 3.0.0
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
		 * @since BuddyBoss 3.0.0
		 *
		 * @param BB_Admin_Meta_Field_Registry $registry  The registry instance.
		 * @param string                       $component The component identifier.
		 */
		do_action( "bb_register_{$component}_meta_fields", $this, $component );
	}

	/**
	 * Register a field for a component.
	 *
	 * @since BuddyBoss 3.0.0
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
	 *     @type string   $layout            'default' (full width), 'half' (half width), or 'third' (third width). Default 'default'.
	 *     @type string   $save_phase        'before' (set object properties before save) or 'after' (save meta after save). Default 'after'.
	 *     @type callable $get_value         Required. function( $item ) returning mixed.
	 *     @type callable $get_options       Optional. function( $item ) returning array for 'select' type.
	 *     @type callable $save_value        Optional. function( $item, $value ). Null = read-only.
	 *     @type callable $sanitize_callback Optional. Sanitize before save. Default 'sanitize_text_field'.
	 *     @type callable $is_visible        Optional. function( $item ) returning bool. Default true.
	 *     @type string   $tab               Optional tab identifier for tabbed modals. Default ''.
	 *     @type callable $get_extra_data    Optional. function( $item ) returning array of extra data for JS.
	 *     @type string   $async_action      Optional. AJAX action for 'async_select' field type. Default ''.
	 *     @type string   $async_depends_on  Optional. Field ID whose value is passed as extra param for cascading async selects. Default ''.
	 *     @type array    $conditional      Optional. Client-side dependency: array( 'field' => 'field_id', 'value' => 'expected_value' ).
	 * }
	 * @return bool True on success.
	 */
	public function register( $component, $field_id, $args = array() ) {
		if ( empty( $component ) || empty( $field_id ) || ! is_string( $field_id ) ) {
			return false;
		}

		$defaults = array(
			'label'             => '',
			'description'       => '',
			'placeholder'       => '',
			'type'              => 'text',
			'order'             => 100,
			'context'           => 'normal',
			'layout'            => 'default',
			'tab'               => '',
			'save_phase'        => 'after',
			'get_value'         => null,
			'get_options'       => null,
			'get_extra_data'    => null,
			'save_value'        => null,
			'sanitize_callback' => null, // Must be explicitly set for non-scalar (array) fields.
			'is_visible'        => null,
			'async_action'      => '',
			'async_depends_on'  => '',
			'conditional'       => null,
			// Visual grouping: when a contiguous run of fields shares the same
			// `field_group` identifier (typically a third-party metabox id),
			// the React modal renders them inside one bordered section with
			// the `field_group_label` as a heading. Empty values mean the
			// field is ungrouped — preserves the historical flat layout for
			// every existing core registration.
			'field_group'       => '',
			'field_group_label' => '',
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

		// Invalidate sorted cache for this component.
		unset( $this->sorted_cache[ $component ] );

		return true;
	}

	/**
	 * Get all registered fields for a component, sorted by order.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $component Component identifier.
	 * @return array
	 */
	public function get_fields( $component ) {
		$this->ensure_registered( $component );

		if ( empty( $this->fields[ $component ] ) ) {
			return array();
		}

		if ( isset( $this->sorted_cache[ $component ] ) ) {
			return $this->sorted_cache[ $component ];
		}

		$fields = $this->fields[ $component ];

		// Sort by order.
		uasort(
			$fields,
			function ( $a, $b ) {
				return (int) $a['order'] - (int) $b['order'];
			}
		);

		$this->sorted_cache[ $component ] = $fields;

		return $fields;
	}

	/**
	 * Get fields data for a specific item (used in AJAX response).
	 *
	 * @since BuddyBoss 3.0.0
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
				'id'                => $field_id,
				'label'             => $args['label'],
				'description'       => $args['description'],
				'placeholder'       => $args['placeholder'],
				'type'              => $args['type'],
				'context'           => $args['context'],
				'layout'            => $args['layout'],
				'tab'               => $args['tab'],
				'visible'           => $visible,
				'value'             => null,
				'options'           => array(),
				'readonly'          => ( null === $args['save_value'] ),
				'conditional'       => $args['conditional'],
				'async_action'      => $args['async_action'],
				'async_depends_on'  => $args['async_depends_on'],
				// Visual grouping data — empty string when ungrouped. The
				// label is rendered as a React text child (no HTML parsing),
				// so we sanitize to plain text on the server. Any inline
				// markup a legacy metabox embeds in its title (`<strong>` etc.)
				// would display as literal characters in the modal, so strip
				// it here for a clean heading.
				'field_group'       => is_string( $args['field_group'] ) ? $args['field_group'] : '',
				'field_group_label' => is_string( $args['field_group_label'] )
					? sanitize_text_field( $args['field_group_label'] )
					: '',
			);

			// Skip fetching value, options, and extra data for invisible fields
			// to avoid unnecessary DB queries for disabled components.
			if ( $visible ) {
				// Get current value.
				if ( is_callable( $args['get_value'] ) ) {
					$field_data['value'] = call_user_func( $args['get_value'], $item );
				}

				// Get options for select, radio, checkbox, and toggle_list types.
				if ( in_array( $args['type'], array( 'select', 'radio', 'checkbox', 'toggle_list' ), true ) && is_callable( $args['get_options'] ) ) {
					$field_data['options'] = call_user_func( $args['get_options'], $item );
				}

				// Get extra data (e.g. base_url for permalink fields).
				if ( is_callable( $args['get_extra_data'] ) ) {
					$field_data['extra_data'] = call_user_func( $args['get_extra_data'], $item );
				}
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
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $component Component identifier.
	 * @param object $item      The item being saved.
	 * @param string $phase     Save phase to process: 'before', 'after', or 'all'. Default 'all'.
	 */
	public function save_fields_data( $component, $item, $phase = 'all' ) {
		$fields = $this->get_fields( $component );

		/**
		 * Fires before the registry iterates fields to save. Used by the
		 * legacy meta-bridge to replay hidden inputs (nonces, CSRF tokens,
		 * stable hidden state) from third-party metaboxes into $_POST so
		 * their save_post_<post_type> handlers can verify and persist data.
		 *
		 * Listeners must filter by `$component` (e.g.
		 * `if ( 'forums' !== $component ) { return; }`) — this action fires
		 * once per save_fields_data() call regardless of which component is
		 * being saved, so unscoped listeners would run against unrelated
		 * components and either no-op or perform redundant work.
		 *
		 * @since BuddyBoss 3.0.0
		 *
		 * @param string $component Component identifier.
		 * @param object $item      Item being saved (may be null on create).
		 * @param string $phase     Save phase: 'before', 'after', or 'all'.
		 */
		do_action( 'bb_admin_meta_field_registry_before_save', $component, $item, $phase );

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

			// Honor `conditional` on save (in addition to render). The contract
			// of a `conditional` field is: "render this field only when the
			// parent field's current value matches". Without this guard the
			// registry would still call save_value for every dependent whose
			// POST key is present — even when the user just toggled the parent
			// off — re-applying the dependent's stale React state and undoing
			// the parent's intent. Concrete example: unchecking
			// `ld_group_enable` runs that field's save_value (desync from
			// LearnDash), but `ld_group_id` still sits in the POST with its
			// previous value; without this guard the next iteration would
			// immediately re-associate the BP group to that LD group.
			//
			// Backward-compat note: when the parent's POST key is NOT present
			// (e.g. parent is read-only or the React form chose to omit it),
			// fall through to the historical behavior. This avoids surprising
			// fields whose conditional points at something the registry never
			// receives.
			// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by the calling AJAX handler before invoking save_fields_data().
			// A `disable` conditional only greys the field out — it stays visible
			// and its value must still persist (e.g. WP Fusion stores `allow_tags`
			// even when its `lock_content` gate is off; the classic metabox
			// submits them as disabled-but-present inputs). Only a hide-style
			// conditional (the default) means "not applicable → don't save".
			$cond_is_disable = ! empty( $args['conditional']['action'] ) && 'disable' === $args['conditional']['action'];
			if ( ! empty( $args['conditional'] ) && is_array( $args['conditional'] ) && ! $cond_is_disable ) {
				$cond_field = isset( $args['conditional']['field'] ) ? (string) $args['conditional']['field'] : '';
				$cond_value = isset( $args['conditional']['value'] ) ? $args['conditional']['value'] : null;

				if ( '' !== $cond_field ) {
					$cond_post_key = 'registered_field_' . $cond_field;
					if ( isset( $_POST[ $cond_post_key ] ) ) {
						$cond_actual = is_array( $_POST[ $cond_post_key ] )
							? array_map( 'strval', wp_unslash( $_POST[ $cond_post_key ] ) ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
							: (string) wp_unslash( $_POST[ $cond_post_key ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
						$allowed     = is_array( $cond_value )
							? array_map( 'strval', $cond_value )
							: array( (string) $cond_value );

						$matched = is_array( $cond_actual )
							? (bool) array_intersect( $cond_actual, $allowed )
							: in_array( $cond_actual, $allowed, true );

						if ( ! $matched ) {
							continue;
						}
					}
				}
			}

			$post_key = 'registered_field_' . $field_id;
			if ( ! isset( $_POST[ $post_key ] ) ) {
				continue;
			}

			$raw_value = wp_unslash( $_POST[ $post_key ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			// phpcs:enable WordPress.Security.NonceVerification.Missing

			// Sanitize using the field's registered callback.
			// No default: array-type fields must register their own sanitize_callback to avoid
			// sanitize_text_field() corrupting array values. Scalar fields without a callback
			// fall back to sanitize_text_field(); array fields fall back to map_deep().
			if ( is_callable( $args['sanitize_callback'] ) ) {
				$raw_value = call_user_func( $args['sanitize_callback'], $raw_value );
			} elseif ( is_array( $raw_value ) ) {
				$raw_value = map_deep( $raw_value, 'sanitize_text_field' );
			} else {
				$raw_value = sanitize_text_field( $raw_value );
			}

			// Save.
			call_user_func( $args['save_value'], $item, $raw_value );
		}
	}
}

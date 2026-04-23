<?php
/**
 * BuddyBoss Admin Groups AJAX Handler
 *
 * Handles AJAX requests for groups management in the admin.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class BB_Admin_Groups_Ajax
 *
 * Handles all AJAX requests for groups management.
 *
 * @since BuddyBoss 3.0.0
 */
class BB_Admin_Groups_Ajax {

	/**
	 * Nonce action for AJAX requests.
	 *
	 * @since BuddyBoss 3.0.0
	 * @var string
	 */
	const NONCE_ACTION = 'bb_admin_settings_2_0';

	/**
	 * Constructor.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	public function __construct() {
		$this->register_ajax_handlers();
	}

	/**
	 * Register AJAX handlers.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	private function register_ajax_handlers() {
		// Groups.
		add_action( 'wp_ajax_bb_admin_get_groups', array( $this, 'get_groups' ) );
		add_action( 'wp_ajax_bb_admin_get_group', array( $this, 'get_group' ) );
		add_action( 'wp_ajax_bb_admin_create_group', array( $this, 'create_group' ) );
		add_action( 'wp_ajax_bb_admin_update_group', array( $this, 'update_group' ) );
		add_action( 'wp_ajax_bb_admin_delete_group', array( $this, 'delete_group' ) );

		// Group Types.
		add_action( 'wp_ajax_bb_admin_get_group_types', array( $this, 'get_group_types' ) );
		add_action( 'wp_ajax_bb_admin_create_group_type', array( $this, 'create_group_type' ) );
		add_action( 'wp_ajax_bb_admin_update_group_type', array( $this, 'update_group_type' ) );
		add_action( 'wp_ajax_bb_admin_delete_group_type', array( $this, 'delete_group_type' ) );
	}

	/**
	 * Verify AJAX request.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @return bool|WP_Error True if valid, WP_Error otherwise.
	 */
	private function verify_request() {
		if ( ! check_ajax_referer( self::NONCE_ACTION, 'nonce', false ) ) {
			return new WP_Error( 'invalid_nonce', __( 'Invalid security token.', 'buddyboss' ), array( 'status' => 403 ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error( 'unauthorized', __( 'You do not have permission to perform this action.', 'buddyboss' ), array( 'status' => 403 ) );
		}

		return true;
	}

	/**
	 * Get groups list.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	public function get_groups() {
		$verify = $this->verify_request();
		if ( is_wp_error( $verify ) ) {
			wp_send_json_error( $verify->get_error_message(), $verify->get_error_data()['status'] ?? 403 );
		}

		$page         = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
		$per_page     = isset( $_POST['per_page'] ) ? absint( $_POST['per_page'] ) : 20;
		$orderby      = isset( $_POST['orderby'] ) ? sanitize_text_field( wp_unslash( $_POST['orderby'] ) ) : 'last_activity';
		$order        = isset( $_POST['order'] ) ? sanitize_text_field( wp_unslash( $_POST['order'] ) ) : 'desc';
		$show_hidden  = isset( $_POST['show_hidden'] ) ? filter_var( wp_unslash( $_POST['show_hidden'] ), FILTER_VALIDATE_BOOLEAN ) : true;
		$search_terms = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
		$status       = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '';
		$group_type   = isset( $_POST['group_type'] ) ? sanitize_text_field( wp_unslash( $_POST['group_type'] ) ) : '';

		$args = array(
			'page'        => $page,
			'per_page'    => $per_page,
			'orderby'     => $orderby,
			'order'       => strtoupper( $order ),
			'show_hidden' => $show_hidden,
		);

		if ( ! empty( $search_terms ) ) {
			$args['search_terms'] = $search_terms;
		}

		if ( ! empty( $status ) && 'all' !== $status ) {
			$args['status'] = array( $status );
		}

		if ( ! empty( $group_type ) && 'all' !== $group_type ) {
			$args['group_type'] = $group_type;
		}

		// Get groups.
		$groups_query = groups_get_groups( $args );
		$groups       = array();

		foreach ( $groups_query['groups'] as $group ) {
			$groups[] = $this->prepare_group_for_response( $group );
		}

		// Send response with pagination headers.
		$total       = $groups_query['total'];
		$total_pages = ceil( $total / $per_page );

		wp_send_json_success(
			array(
				'groups'      => $groups,
				'total'       => $total,
				'total_pages' => $total_pages,
				'page'        => $page,
				'per_page'    => $per_page,
			)
		);
	}

	/**
	 * Get single group.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	public function get_group() {
		$verify = $this->verify_request();
		if ( is_wp_error( $verify ) ) {
			wp_send_json_error( $verify->get_error_message(), $verify->get_error_data()['status'] ?? 403 );
		}

		$group_id = isset( $_POST['group_id'] ) ? absint( $_POST['group_id'] ) : 0;

		if ( empty( $group_id ) ) {
			wp_send_json_error( __( 'Group ID is required.', 'buddyboss' ), 400 );
		}

		$group = groups_get_group( $group_id );

		if ( empty( $group->id ) ) {
			wp_send_json_error( __( 'Group not found.', 'buddyboss' ), 404 );
		}

		wp_send_json_success( $this->prepare_group_for_response( $group ) );
	}

	/**
	 * Create a new group.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	public function create_group() {
		$verify = $this->verify_request();
		if ( is_wp_error( $verify ) ) {
			wp_send_json_error( $verify->get_error_message(), $verify->get_error_data()['status'] ?? 403 );
		}

		$name        = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$description = isset( $_POST['description'] ) ? wp_kses_post( wp_unslash( $_POST['description'] ) ) : '';
		$status      = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : 'public';
		$creator_id  = isset( $_POST['creator_id'] ) ? absint( $_POST['creator_id'] ) : get_current_user_id();
		$group_type  = isset( $_POST['group_type'] ) ? sanitize_text_field( wp_unslash( $_POST['group_type'] ) ) : '';

		if ( empty( $name ) ) {
			wp_send_json_error( __( 'Group name is required.', 'buddyboss' ), 400 );
		}

		$group_id = groups_create_group(
			array(
				'name'        => $name,
				'description' => $description,
				'status'      => $status,
				'creator_id'  => $creator_id,
			)
		);

		if ( ! $group_id ) {
			wp_send_json_error( __( 'Failed to create group.', 'buddyboss' ), 500 );
		}

		// Set group type if provided.
		if ( ! empty( $group_type ) ) {
			bp_groups_set_group_type( $group_id, $group_type );
		}

		$group = groups_get_group( $group_id );
		wp_send_json_success( $this->prepare_group_for_response( $group ) );
	}

	/**
	 * Update a group.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	public function update_group() {
		$verify = $this->verify_request();
		if ( is_wp_error( $verify ) ) {
			wp_send_json_error( $verify->get_error_message(), $verify->get_error_data()['status'] ?? 403 );
		}

		$group_id = isset( $_POST['group_id'] ) ? absint( $_POST['group_id'] ) : 0;

		if ( empty( $group_id ) ) {
			wp_send_json_error( __( 'Group ID is required.', 'buddyboss' ), 400 );
		}

		$group = groups_get_group( $group_id );
		if ( empty( $group->id ) ) {
			wp_send_json_error( __( 'Group not found.', 'buddyboss' ), 404 );
		}

		$args = array( 'group_id' => $group_id );

		if ( isset( $_POST['name'] ) ) {
			$args['name'] = sanitize_text_field( wp_unslash( $_POST['name'] ) );
		}
		if ( isset( $_POST['description'] ) ) {
			$args['description'] = wp_kses_post( wp_unslash( $_POST['description'] ) );
		}
		if ( isset( $_POST['status'] ) ) {
			$args['status'] = sanitize_text_field( wp_unslash( $_POST['status'] ) );
		}

		$updated = groups_create_group( $args );

		if ( ! $updated ) {
			wp_send_json_error( __( 'Failed to update group.', 'buddyboss' ), 500 );
		}

		// Update group type if provided.
		if ( isset( $_POST['group_type'] ) ) {
			$group_type = sanitize_text_field( wp_unslash( $_POST['group_type'] ) );
			if ( ! empty( $group_type ) ) {
				bp_groups_set_group_type( $group_id, $group_type );
			} else {
				bp_groups_remove_group_type( $group_id, bp_groups_get_group_type( $group_id ) );
			}
		}

		$group = groups_get_group( $group_id );
		wp_send_json_success( $this->prepare_group_for_response( $group ) );
	}

	/**
	 * Delete a group.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	public function delete_group() {
		$verify = $this->verify_request();
		if ( is_wp_error( $verify ) ) {
			wp_send_json_error( $verify->get_error_message(), $verify->get_error_data()['status'] ?? 403 );
		}

		$group_id = isset( $_POST['group_id'] ) ? absint( $_POST['group_id'] ) : 0;

		if ( empty( $group_id ) ) {
			wp_send_json_error( __( 'Group ID is required.', 'buddyboss' ), 400 );
		}

		$group = groups_get_group( $group_id );
		if ( empty( $group->id ) ) {
			wp_send_json_error( __( 'Group not found.', 'buddyboss' ), 404 );
		}

		$deleted = groups_delete_group( $group_id );

		if ( ! $deleted ) {
			wp_send_json_error( __( 'Failed to delete group.', 'buddyboss' ), 500 );
		}

		wp_send_json_success( array( 'deleted' => true ) );
	}

	/**
	 * Get group types.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	public function get_group_types() {
		$verify = $this->verify_request();
		if ( is_wp_error( $verify ) ) {
			wp_send_json_error( $verify->get_error_message(), $verify->get_error_data()['status'] ?? 403 );
		}

		$types = bp_groups_get_group_types( array(), 'objects' );
		$data  = array();

		foreach ( $types as $type_name => $type_object ) {
			// Get the post ID for this group type.
			$post_id = bp_group_get_group_type_id( $type_name );

			// Count groups with this type.
			$groups_count = 0;
			if ( function_exists( 'bp_groups_get_group_type' ) ) {
				$groups_query = groups_get_groups(
					array(
						'group_type' => $type_name,
						'per_page'   => 1,
						'fields'     => 'ids',
					)
				);
				$groups_count = $groups_query['total'];
			}

			$data[] = array(
				'id'           => $post_id,
				'name'         => $type_name,
				'label'        => isset( $type_object->labels['singular_name'] ) ? $type_object->labels['singular_name'] : $type_name,
				'description'  => isset( $type_object->description ) ? $type_object->description : '',
				'groups_count' => $groups_count,
				'visibility'   => isset( $type_object->show_in_list ) && $type_object->show_in_list ? 'public' : 'private',
			);
		}

		wp_send_json_success( $data );
	}

	/**
	 * Create a group type.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	public function create_group_type() {
		$verify = $this->verify_request();
		if ( is_wp_error( $verify ) ) {
			wp_send_json_error( $verify->get_error_message(), $verify->get_error_data()['status'] ?? 403 );
		}

		$name        = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$label       = isset( $_POST['label'] ) ? sanitize_text_field( wp_unslash( $_POST['label'] ) ) : '';
		$description = isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '';

		if ( empty( $name ) ) {
			wp_send_json_error( __( 'Group type name is required.', 'buddyboss' ), 400 );
		}

		// Create the group type post.
		$post_id = wp_insert_post(
			array(
				'post_title'   => $label ? $label : $name,
				'post_name'    => sanitize_title( $name ),
				'post_content' => $description,
				'post_type'    => bp_get_group_type_post_type(),
				'post_status'  => 'publish',
			)
		);

		if ( is_wp_error( $post_id ) ) {
			wp_send_json_error( $post_id->get_error_message(), 500 );
		}

		// Update meta.
		update_post_meta( $post_id, '_bp_group_type_key', sanitize_key( $name ) );
		update_post_meta( $post_id, '_bp_group_type_label_singular_name', $label ? $label : $name );

		wp_send_json_success(
			array(
				'id'          => $post_id,
				'name'        => sanitize_key( $name ),
				'label'       => $label ? $label : $name,
				'description' => $description,
			)
		);
	}

	/**
	 * Update a group type.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	public function update_group_type() {
		$verify = $this->verify_request();
		if ( is_wp_error( $verify ) ) {
			wp_send_json_error( $verify->get_error_message(), $verify->get_error_data()['status'] ?? 403 );
		}

		$type_id = isset( $_POST['type_id'] ) ? absint( $_POST['type_id'] ) : 0;

		if ( empty( $type_id ) ) {
			wp_send_json_error( __( 'Group type ID is required.', 'buddyboss' ), 400 );
		}

		$post = get_post( $type_id );
		if ( ! $post || bp_get_group_type_post_type() !== $post->post_type ) {
			wp_send_json_error( __( 'Group type not found.', 'buddyboss' ), 404 );
		}

		$args = array( 'ID' => $type_id );

		if ( isset( $_POST['label'] ) ) {
			$args['post_title'] = sanitize_text_field( wp_unslash( $_POST['label'] ) );
		}
		if ( isset( $_POST['description'] ) ) {
			$args['post_content'] = sanitize_textarea_field( wp_unslash( $_POST['description'] ) );
		}

		$updated = wp_update_post( $args );

		if ( is_wp_error( $updated ) ) {
			wp_send_json_error( $updated->get_error_message(), 500 );
		}

		if ( isset( $_POST['label'] ) ) {
			update_post_meta( $type_id, '_bp_group_type_label_singular_name', sanitize_text_field( wp_unslash( $_POST['label'] ) ) );
		}

		$post = get_post( $type_id );
		wp_send_json_success(
			array(
				'id'          => $type_id,
				'name'        => get_post_meta( $type_id, '_bp_group_type_key', true ),
				'label'       => $post->post_title,
				'description' => $post->post_content,
			)
		);
	}

	/**
	 * Delete a group type.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	public function delete_group_type() {
		$verify = $this->verify_request();
		if ( is_wp_error( $verify ) ) {
			wp_send_json_error( $verify->get_error_message(), $verify->get_error_data()['status'] ?? 403 );
		}

		$type_id = isset( $_POST['type_id'] ) ? absint( $_POST['type_id'] ) : 0;

		if ( empty( $type_id ) ) {
			wp_send_json_error( __( 'Group type ID is required.', 'buddyboss' ), 400 );
		}

		$post = get_post( $type_id );
		if ( ! $post || bp_get_group_type_post_type() !== $post->post_type ) {
			wp_send_json_error( __( 'Group type not found.', 'buddyboss' ), 404 );
		}

		$deleted = wp_delete_post( $type_id, true );

		if ( ! $deleted ) {
			wp_send_json_error( __( 'Failed to delete group type.', 'buddyboss' ), 500 );
		}

		wp_send_json_success( array( 'deleted' => true ) );
	}

	/**
	 * Prepare group for response.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param BP_Groups_Group $group Group object.
	 * @return array Prepared group data.
	 */
	private function prepare_group_for_response( $group ) {
		// Get avatar.
		$avatar = bp_core_fetch_avatar(
			array(
				'item_id' => $group->id,
				'object'  => 'group',
				'type'    => 'thumb',
				'html'    => false,
			)
		);

		// Get group type.
		$group_type       = bp_groups_get_group_type( $group->id, false );
		$group_type_label = '';
		if ( ! empty( $group_type ) ) {
			$type_object = bp_groups_get_group_type_object( is_array( $group_type ) ? $group_type[0] : $group_type );
			if ( $type_object ) {
				$group_type_label = isset( $type_object->labels['singular_name'] ) ? $type_object->labels['singular_name'] : $group_type;
			}
		}

		// Get member count.
		$member_count = groups_get_total_member_count( $group->id );

		// Get last activity.
		$last_activity = '';
		if ( ! empty( $group->last_activity ) ) {
			$last_activity = bp_core_time_since( $group->last_activity );
		}

		return array(
			'id'               => $group->id,
			'name'             => $group->name,
			'slug'             => $group->slug,
			'description'      => $group->description,
			'status'           => $group->status,
			'avatar'           => $avatar,
			'avatar_urls'      => array(
				'thumb' => $avatar,
				'full'  => bp_core_fetch_avatar(
					array(
						'item_id' => $group->id,
						'object'  => 'group',
						'type'    => 'full',
						'html'    => false,
					)
				),
			),
			'group_type'       => is_array( $group_type ) ? ( ! empty( $group_type ) ? $group_type[0] : '' ) : $group_type,
			'group_type_label' => $group_type_label,
			'types'            => is_array( $group_type ) ? $group_type : ( $group_type ? array( $group_type ) : array() ),
			'member_count'     => $member_count,
			'total_member_count' => $member_count,
			'last_activity'    => $group->last_activity,
			'last_activity_diff' => $last_activity,
			'creator_id'       => $group->creator_id,
			'date_created'     => $group->date_created,
			'link'             => bp_get_group_permalink( $group ),
		);
	}
}

// Initialize the class.
new BB_Admin_Groups_Ajax();

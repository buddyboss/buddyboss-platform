<?php
/**
 * BP REST: BP_REST_Learndash_Courses_Endpoint class
 *
 * @package BuddyBoss
 * @since 0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * LearnDash Courses endpoints.
 *
 * @since 0.1.0
 */
class BP_REST_Learndash_Courses_Endpoint extends WP_REST_Controller {


	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->namespace = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base = 'learndash/courses';
	}

	/**
	 * Register the component routes.
	 *
	 * @since 0.1.0
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);
	}

	/**
	 * Retrieve courses.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/learndash/courses LearnDash Courses
	 * @apiName        GetBBLearndashCourses
	 * @apiGroup       Learndash
	 * @apiDescription Retrieve courses.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} [page=1] Current page of the collection.
	 * @apiParam {Number} [per_page=10] Maximum number of items to be returned in result set.
	 * @apiParam {String} [search] Limit results to those matching a string.
	 * @apiParam {Number} [author] Limit result set to posts assigned to specific authors.
	 * @apiParam {Array} [author_exclude] Ensure result set excludes posts assigned to specific authors.
	 * @apiParam {String} [after] Limit response to resources published after a given ISO8601 compliant date.
	 * @apiParam {String} [before] Limit response to resources published before a given ISO8601 compliant date.
	 * @apiParam {Array} [exclude] Ensure result set excludes specific IDs.
	 * @apiParam {Array} [include] Limit result set to specific IDs.
	 * @apiParam {Number} [offset] Offset the result set by a specific number of items.
	 * @apiParam {String=asc,desc} [order=asc] Order sort attribute ascending or descending.
	 * @apiParam {String=author,date,id,include,modified,parent,relevance,slug,title,menu_order} [orderby=date] Sort collection by object attribute.
	 * @apiParam {Array=publish,future,draft,pending,private,trash,auto-draft,inherit,request-pending,request-confirmed,request-failed,request-completed,closed,spam,orphan,hidden,graded,not_graded,any} [status=date] Sort collection by object attribute.
	 * @apiParam {Array} [course_category] Limit result set to all items that have the specified term assigned in the ld_course_category taxonomy.
	 * @apiParam {Array} [course_category_exclude] Limit result set to all items except those that have the specified term assigned in the ld_course_category taxonomy.
	 * @apiParam {Array} [course_tag] Limit result set to all items that have the specified term assigned in the ld_course_tag taxonomy.
	 * @apiParam {Array} [course_tag_exclude] Limit result set to all items except those that have the specified term assigned in the ld_course_tag taxonomy.
	 * @apiParam {Number} [group_id] Limit response to specific buddypress group.
	 */
	public function get_items( $request ) {

		$args = array(
			'post_type'      => 'sfwd-courses',
			'order'          => ( ! empty( $request['order'] ) ? $request['order'] : 'desc' ),
			'orderby'        => ( ! empty( $request['orderby'] ) ? $request['orderby'] : 'date' ),
			'paged'          => ( ! empty( $request['page'] ) ? $request['page'] : 1 ),
			'posts_per_page' => ( ! empty( $request['per_page'] ) ? $request['per_page'] : 10 ),
			'post_status'    => ( ! empty( $request['status'] ) ? implode( ' ', $request['status'] ) : 'publish' ),
		);

		if ( ! empty( $request['search'] ) ) {
			$args['s'] = urldecode( trim( $request['search'] ) );
		}

		if ( ! empty( $request['author'] ) ) {
			$args['author'] = $request['author'];
		}

		if ( ! empty( $request['author_exclude'] ) ) {
			$args['author__not_in'] = $request['author_exclude'];
		}

		if ( ! empty( $request['exclude'] ) ) {
			$args['post__not_in'] = $request['exclude'];
		}

		if ( ! empty( $request['include'] ) ) {
			$args['post__in'] = $request['include'];
		}

		if ( ! empty( $request['offset'] ) ) {
			$args['offset'] = $request['offset'];
		}

		$args['date_query'] = array();
		// Set before into date query. Date query must be specified as an array of an array.
		if ( isset( $request['before'] ) ) {
			$args['date_query'][0]['before'] = $request['before'];
		}

		// Set after into date query. Date query must be specified as an array of an array.
		if ( isset( $request['after'] ) ) {
			$args['date_query'][0]['after'] = $request['after'];
		}

		if ( ! empty( $request['course_category'] ) ) {
			if ( in_array( 0, $request['course_category'], true ) ) {
				$args['tax_query'][] = array(
					'taxonomy' => 'ld_course_category',
					'operator' => 'NOT EXISTS',
				);
				$categories          = array();
				foreach ( $request['course_category'] as $k => $v ) {
					if ( ! empty( $v ) ) {
						$categories[] = $v;
					}
				}
				$request['course_category'] = $categories;
				unset( $categories );
			}

			if ( ! empty( $request['course_category'] ) ) {
				$args['tax_query'][] = array(
					'taxonomy'         => 'ld_course_category',
					'field'            => 'term_id',
					'terms'            => $request['course_category'],
					'include_children' => false,
				);
			}
		}

		if ( ! empty( $request['course_category_exclude'] ) ) {
			$args['tax_query'][] = array(
				'taxonomy'         => 'ld_course_category',
				'field'            => 'term_id',
				'terms'            => $request['course_category_exclude'],
				'include_children' => false,
				'operator'         => 'NOT IN',
			);
		}

		if ( ! empty( $request['course_tag'] ) ) {
			$args['tax_query'][] = array(
				'taxonomy'         => 'ld_course_tag',
				'field'            => 'term_id',
				'terms'            => $request['course_tag'],
				'include_children' => false,
			);
		}

		if ( ! empty( $request['course_tag_exclude'] ) ) {
			$args['tax_query'][] = array(
				'taxonomy'         => 'ld_course_tag',
				'field'            => 'term_id',
				'terms'            => $request['course_tag_exclude'],
				'include_children' => false,
				'operator'         => 'NOT IN',
			);
		}

		if ( isset( $request['group_id'] ) && ! empty( $request['group_id'] ) ) {
			$group_id         = bp_ld_sync( 'buddypress' )->helpers->getLearndashGroupId( $request['group_id'] );
			$course_ids       = learndash_group_enrolled_courses( $group_id );
			$args['post__in'] = ! empty( $course_ids ) ? $course_ids : array( 0 );
			unset( $args['author'] );
		}

		/**
		 * Filter the query arguments for the request.
		 *
		 * @param array           $args    Key value array of query var to query value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		$args = apply_filters( 'bp_rest_learndash_courses_get_items_query_args', $args, $request );

		$courses_query = new WP_Query( $args );
		$courses       = $courses_query->posts;

		$retval = array();
		if ( ! empty( $courses ) ) {
			foreach ( $courses as $course ) {
				$retval[] = $this->prepare_response_for_collection(
					$this->prepare_item_for_response( $course, $request )
				);
			}
		}

		$response = rest_ensure_response( $retval );
		$response = bp_rest_response_add_total_headers( $response, $courses_query->found_posts, $args['posts_per_page'] );

		/**
		 * Fires after a list of courses is fetched via the REST API.
		 *
		 * @param array            $courses  Fetched Courses.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_learndash_courses_get_items', $courses, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to list courses.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function get_items_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to view courses.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval = true;
		}

		/**
		 * Filter the courses `get_items` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_learndash_courses_get_items_permissions_check', $retval, $request );
	}

	/**
	 * Prepares component data for return as an object.
	 *
	 * @param array           $course  The component and its values.
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response
	 * @since 0.1.0
	 */
	public function prepare_item_for_response( $course, $request ) {
		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';

		// Base fields for every post.
		$data = array(
			'id'             => $course->ID,
			'date'           => $this->prepare_date_response( $course->post_date_gmt, $course->post_date ),
			'date_gmt'       => $this->prepare_date_response( $course->post_date_gmt ),
			'guid'           => array(
				'rendered' => esc_url( get_the_permalink( $course->ID ) ),
				'raw'      => $course->guid,
			),
			'modified'       => $this->prepare_date_response( $course->post_modified_gmt, $course->post_modified ),
			'modified_gmt'   => $this->prepare_date_response( $course->post_modified_gmt ),
			'slug'           => $course->post_name,
			'status'         => $course->post_status,
			'type'           => $course->post_type,
			'author'         => (int) $course->post_author,
			'menu_order'     => (int) $course->menu_order,
			'featured_media' => $this->get_feature_media( $course ),
			'lessons_count'  => count( learndash_get_lesson_list( $course->ID ) ),
			'has_access'     => sfwd_lms_has_access( $course->ID, get_current_user_id() ),
			'purchasable'    => $this->is_purchasable( $course ),
			'status_bubble'  => $this->get_bubble_status( $course ),
			'progress'       => learndash_course_progress(
				array(
					'user_id'   => get_current_user_id(),
					'course_id' => $course->ID,
					'array'     => true,
				)
			),
			'last_activity'  => $this->get_last_activity( $course ),
		);

		$data['title'] = array(
			'raw'      => $course->post_title,
			'rendered' => bbp_get_topic_title( $course->ID ),
		);

		/* Prepare content */
		if ( ! empty( $course->post_password ) ) {
			$this->prepare_password_response( $course->post_password );
		}

		$data['excerpt'] = get_the_excerpt( $course );

		$content = apply_filters( 'the_content', $course->post_content );

		$data['content'] = array(
			'raw'      => $course->post_content,
			'rendered' => $content,
		);

		// Don't leave our cookie lying around: https://github.com/WP-API/WP-API/issues/1055.
		if ( ! empty( $course->post_password ) ) {
			$_COOKIE[ 'wp-postpass_' . COOKIEHASH ] = '';
		}
		/* -- Prepare content */

		$data = $this->add_additional_fields_to_object( $data, $request );
		$data = $this->filter_response_by_context( $data, $context );

		// @todo add prepare_links
		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $course ) );

		/**
		 * Filter a component value returned from the API.
		 *
		 * @param WP_REST_Response $response The Response data.
		 * @param WP_REST_Request  $request  Request used to generate the response.
		 * @param array            $course   The component and its values.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_learndash_course_prepare_value', $response, $request, $course );
	}

	/**
	 * Get the forums schema, conforming to JSON Schema.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'learndash_courses',
			'type'       => 'object',
			'properties' => array(
				'id'             => array(
					'description' => __( 'Unique identifier for the course.', 'buddyboss' ),
					'type'        => 'integer',
					'context'     => array( 'embed', 'view', 'edit' ),
					'readonly'    => true,
				),
				'date'           => array(
					'description' => __( 'The date the object was published, in the site\'s timezone.', 'buddyboss' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
				'date_gmt'       => array(
					'description' => __( 'The date the object was published, as GMT.', 'buddyboss' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
				'guid'           => array(
					'description' => __( 'The url identifier for the course.', 'buddyboss' ),
					'type'        => 'object',
					'context'     => array( 'embed', 'view', 'edit' ),
					'readonly'    => true,
					'properties'  => array(
						'raw'      => array(
							'description' => __( 'GUID for the course, as it exists in the database.', 'buddyboss' ),
							'type'        => 'string',
							'context'     => array( 'embed', 'view', 'edit' ),
						),
						'rendered' => array(
							'description' => __( 'GUID for the course, transformed for display.', 'buddyboss' ),
							'type'        => 'string',
							'context'     => array( 'embed', 'view', 'edit' ),
						),
					),
				),
				'modified'       => array(
					'description' => __( 'The date for course was last modified, in the site\'s timezone.', 'buddyboss' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'embed', 'view', 'edit' ),
					'readonly'    => true,
				),
				'modified_gmt'   => array(
					'description' => __( 'The date for course was last modified, as GMT.', 'buddyboss' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'embed', 'view', 'edit' ),
					'readonly'    => true,
				),
				'slug'           => array(
					'description' => __( 'An alphanumeric unique identifier for the course.', 'buddyboss' ),
					'type'        => 'string',
					'context'     => array( 'embed', 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_title',
					),
				),
				'status'         => array(
					'description' => __( 'The current status of the course.', 'buddyboss' ),
					'type'        => 'string',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
				'type'           => array(
					'description' => __( 'Post type slug.', 'buddyboss' ),
					'type'        => 'string',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
				'author'         => array(
					'description' => __( 'The ID for the author of the course.', 'buddyboss' ),
					'type'        => 'integer',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
				'menu_order'     => array(
					'description' => __( 'Order number of the course.', 'buddyboss' ),
					'type'        => 'integer',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
				'featured_media' => array(
					'description' => __( 'The featured media for the course.', 'buddyboss' ),
					'type'        => 'object',
					'context'     => array( 'embed', 'view', 'edit' ),
					'properties'  => array(
						'large'  => array(
							'description' => __( 'Large size featured media for the course', 'buddyboss' ),
							'type'        => 'string',
							'context'     => array( 'embed', 'view', 'edit' ),
						),
						'medium' => array(
							'description' => __( 'Medium size featured media for the course', 'buddyboss' ),
							'type'        => 'string',
							'context'     => array( 'embed', 'view', 'edit' ),
						),
						'thumb'  => array(
							'description' => __( 'Thumbnail size featured media for the course', 'buddyboss' ),
							'type'        => 'string',
							'context'     => array( 'embed', 'view', 'edit' ),
						),
					),
				),
				'lessons_count'  => array(
					'description' => __( 'Lessons count for the course.', 'buddyboss' ),
					'type'        => 'integer',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
				'has_access'     => array(
					'description' => __( 'Whether the current user has access for the course or not.', 'buddyboss' ),
					'type'        => 'boolean',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
				'purchasable'    => array(
					'description' => __( 'Whether the course is purchasable or not.', 'buddyboss' ),
					'type'        => 'boolean',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
				'status_bubble'  => array(
					'description' => __( 'Course bubble strip text.', 'buddyboss' ),
					'type'        => 'string',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
				'progress'       => array(
					'description' => __( 'Course progress status.', 'buddyboss' ),
					'type'        => 'object',
					'context'     => array( 'embed', 'view', 'edit' ),
					'properties'  => array(
						'percentage' => array(
							'description' => __( 'Completed course in percentage.', 'buddyboss' ),
							'type'        => 'string',
							'context'     => array( 'embed', 'view', 'edit' ),
						),
						'completed'  => array(
							'description' => __( 'Completed steps for the course.', 'buddyboss' ),
							'type'        => 'string',
							'context'     => array( 'embed', 'view', 'edit' ),
						),
						'total'      => array(
							'description' => __( 'Total steps count for the course.', 'buddyboss' ),
							'type'        => 'string',
							'context'     => array( 'embed', 'view', 'edit' ),
						),
					),
				),
				'last_activity'  => array(
					'description' => __( 'Last activity for the course.', 'buddyboss' ),
					'type'        => 'string',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
				'title'          => array(
					'description' => __( 'The title of the course.', 'buddyboss' ),
					'context'     => array( 'embed', 'view', 'edit' ),
					'type'        => 'object',
					'properties'  => array(
						'raw'      => array(
							'description' => __( 'Content for the title of the course, as it exists in the database.', 'buddyboss' ),
							'type'        => 'string',
							'context'     => array( 'embed', 'view', 'edit' ),
						),
						'rendered' => array(
							'description' => __( 'The title of the course, transformed for display.', 'buddyboss' ),
							'type'        => 'string',
							'context'     => array( 'embed', 'view', 'edit' ),
						),
					),
				),
				'excerpt'        => array(
					'description' => __( 'Short Content of the course.', 'buddyboss' ),
					'type'        => 'string',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
				'content'        => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The content of the course.', 'buddyboss' ),
					'type'        => 'object',
					'properties'  => array(
						'raw'      => array(
							'description' => __( 'Content for the course, as it exists in the database.', 'buddyboss' ),
							'type'        => 'string',
							'context'     => array( 'embed', 'view', 'edit' ),
						),
						'rendered' => array(
							'description' => __( 'HTML content for the course, transformed for display.', 'buddyboss' ),
							'type'        => 'string',
							'context'     => array( 'embed', 'view', 'edit' ),
						),
					),
				),
			),
		);

		/**
		 * Filters the course schema.
		 *
		 * @param string $schema The endpoint schema.
		 */
		return apply_filters( 'bp_rest_learndash_course_schema', $this->add_additional_fields_schema( $schema ) );
	}

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_collection_params() {
		$params                       = parent::get_collection_params();
		$params['context']['default'] = 'view';

		$params['author'] = array(
			'description'       => __( 'Limit result set to posts assigned to specific authors.', 'buddyboss' ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['author_exclude'] = array(
			'description'       => __( 'Ensure result set excludes posts assigned to specific authors.', 'buddyboss' ),
			'type'              => 'array',
			'items'             => array( 'type' => 'integer' ),
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['after'] = array(
			'description'       => __( 'Limit response to resources published after a given ISO8601 compliant date.', 'buddyboss' ),
			'type'              => 'string',
			'format'            => 'date-time',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['before'] = array(
			'description'       => __( 'Limit response to resources published before a given ISO8601 compliant date.', 'buddyboss' ),
			'type'              => 'string',
			'format'            => 'date-time',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['exclude'] = array(
			'description'       => __( 'Ensure result set excludes specific IDs.', 'buddyboss' ),
			'type'              => 'array',
			'items'             => array( 'type' => 'integer' ),
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['include'] = array(
			'description'       => __( 'Limit result set to specific IDs.', 'buddyboss' ),
			'type'              => 'array',
			'items'             => array( 'type' => 'integer' ),
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['offset'] = array(
			'description'       => __( 'Offset the result set by a specific number of items.', 'buddyboss' ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['order'] = array(
			'description'       => __( 'Order sort attribute ascending or descending.', 'buddyboss' ),
			'type'              => 'string',
			'default'           => 'asc',
			'enum'              => array( 'asc', 'desc' ),
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['orderby'] = array(
			'description'       => __( 'Sort collection by object attribute.', 'buddyboss' ),
			'type'              => 'string',
			'default'           => 'date',
			'enum'              => array(
				'author',
				'date',
				'id',
				'include',
				'modified',
				'parent',
				'relevance',
				'slug',
				'include_slugs',
				'title',
				'menu_order',
			),
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['status'] = array(
			'description'       => __( 'Limit result set to posts assigned one or more statuses.', 'buddyboss' ),
			'type'              => 'array',
			'enum'              => array(
				'publish',
				'future',
				'draft',
				'pending',
				'private',
				'trash',
				'auto-draft',
				'inherit',
				'request-pending',
				'request-confirmed',
				'request-failed',
				'request-completed',
				'closed',
				'spam',
				'orphan',
				'hidden',
				'graded',
				'not_graded',
				'any',
			),
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['course_category'] = array(
			'description'       => __( 'Limit result set to all items that have the specified term assigned in the ld_course_category taxonomy.', 'buddyboss' ),
			'type'              => 'array',
			'default'           => array(),
			'items'             => array( 'type' => 'integer' ),
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['course_category_exclude'] = array(
			'description'       => __( 'Limit result set to all items except those that have the specified term assigned in the ld_course_category taxonomy.', 'buddyboss' ),
			'type'              => 'array',
			'default'           => array(),
			'items'             => array( 'type' => 'integer' ),
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['course_tag'] = array(
			'description'       => __( 'Limit result set to all items that have the specified term assigned in the ld_course_tag taxonomy.', 'buddyboss' ),
			'type'              => 'array',
			'default'           => array(),
			'items'             => array( 'type' => 'integer' ),
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['course_tag_exclude'] = array(
			'description'       => __( 'Limit result set to all items except those that have the specified term assigned in the ld_course_tag taxonomy.', 'buddyboss' ),
			'type'              => 'array',
			'default'           => array(),
			'items'             => array( 'type' => 'integer' ),
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['group_id'] = array(
			'description'       => __( 'Limit response to specific buddypress group.', 'buddyboss' ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		/**
		 * Filters the collection query params.
		 *
		 * @param array $params Query params.
		 */
		return apply_filters( 'bp_rest_learndash_courses_collection_params', $params );
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param WP_Post $course Course object.
	 *
	 * @return array Links for the given plugin.
	 * @since 0.1.0
	 */
	protected function prepare_links( $course ) {
		$base = sprintf( '/%s/%s/', $this->namespace, $this->rest_base );

		// Entity meta.
		$links = array(
			'collection' => array(
				'href' => rest_url( $base ),
			),
			'user'       => array(
				'href'       => rest_url( bp_rest_get_user_url( $course->post_author ) ),
				'embeddable' => true,
			),
		);

		/**
		 * Filter links prepared for the REST response.
		 *
		 * @param array   $links  The prepared links of the REST response.
		 * @param WP_post $course Course object.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_learndash_course_prepare_links', $links, $course );
	}

	/**
	 * Check the post_date_gmt or modified_gmt and prepare any post or
	 * modified date for single post output.
	 *
	 * @param string      $date_gmt GMT date format.
	 * @param string|null $date     forum date.
	 *
	 * @return string|null ISO8601/RFC3339 formatted datetime.
	 */
	public function prepare_date_response( $date_gmt, $date = null ) {
		// Use the date if passed.
		if ( isset( $date ) ) {
			return mysql_to_rfc3339( $date ); // phpcs:ignore WordPress.DB.RestrictedFunctions.mysql_to_rfc3339, PHPCompatibility.Extensions.RemovedExtensions.mysql_DeprecatedRemoved
		}

		// Return null if $date_gmt is empty/zeros.
		if ( '0000-00-00 00:00:00' === $date_gmt ) {
			return null;
		}

		// Return the formatted datetime.
		return mysql_to_rfc3339( $date_gmt ); // phpcs:ignore WordPress.DB.RestrictedFunctions.mysql_to_rfc3339, PHPCompatibility.Extensions.RemovedExtensions.mysql_DeprecatedRemoved
	}

	/**
	 * Prepare response for the password protected posts.
	 *
	 * @param string $password WP_Post password.
	 *
	 * @return mixed
	 */
	public function prepare_password_response( $password ) {
		if ( ! empty( $password ) ) {
			/**
			 * Fake the correct cookie to fool post_password_required().
			 * Without this, get_the_content() will give a password form.
			 */
			require_once ABSPATH . 'wp-includes/class-phpass.php';

			$hasher = new PasswordHash( 8, true );
			$value  = $hasher->HashPassword( $password );

			$_COOKIE[ 'wp-postpass_' . COOKIEHASH ] = wp_slash( $value );
		}

		return $password;
	}

	/**
	 * Get Featured Media.
	 *
	 * @param WP_Post $post Object of wp_post.
	 *
	 * @return array
	 */
	public function get_feature_media( $post ) {
		$return = array(
			'large'  => null,
			'medium' => null,
			'thumb'  => null,
		);

		if ( empty( get_post_thumbnail_id( $post->ID ) ) ) {
			return $return;
		}

		return array(
			'large'  => wp_get_attachment_image_url( get_post_thumbnail_id( $post->ID ), 'large' ),
			'medium' => wp_get_attachment_image_url( get_post_thumbnail_id( $post->ID ), 'medium' ),
			'thumb'  => wp_get_attachment_image_url( get_post_thumbnail_id( $post->ID ), 'thumbnail' ),
		);
	}

	/**
	 * Check the course is purchasable or not.
	 *
	 * @param WP_Post $post Object of wp_post.
	 *
	 * @return bool
	 */
	public function is_purchasable( $post ) {
		$type  = $this->bp_rest_learndash_get_course_meta_setting( $post->ID, 'course_price_type' );
		$price = (float) $this->bp_rest_learndash_get_course_meta_setting( $post->ID, 'course_price' );
		switch ( $type ) {
			case 'subscribe':
			case 'paynow':
			case 'closed':
				$is_paid = empty( $price ) ? false : true;
				break;
			default:
				$is_paid = false;
		}

		return $is_paid;
	}

	/**
	 * Deprecated support for get_course_meta_setting.
	 *
	 * @param int    $course_id   Post ID.
	 * @param string $setting_key Meta key slug.
	 *
	 * @return array|mixed
	 */
	public function bp_rest_learndash_get_course_meta_setting( $course_id, $setting_key ) {
		if ( function_exists( 'learndash_get_course_meta_setting' ) ) {
			return learndash_get_course_meta_setting( $course_id, $setting_key );
		} else {
			return get_course_meta_setting( $course_id, $setting_key );
		}
	}

	/**
	 * Get course Last Activity.
	 *
	 * @param WP_Post $post Object of wp_post.
	 *
	 * @return string
	 */
	public function get_last_activity( $post ) {
		$course_activity = learndash_get_user_activity(
			array(
				'course_id'     => $post->ID,
				'user_id'       => get_current_user_id(),
				'post_id'       => $post->ID,
				'activity_type' => 'course',
			)
		);

		if ( $course_activity ) {
			return sprintf(
			// translators: Last activity date in infobar.
				esc_html_x( 'Last activity on %s', 'Last activity date in infobar', 'buddyboss' ),
				learndash_adjust_date_time_display( $course_activity->activity_updated, get_option( 'date_format' ) )
			);
		}

		return '';
	}

	/**
	 * Get bubble status.
	 *
	 * @param WP_Post $post Object of wp_post.
	 *
	 * @return string|void
	 */
	public function get_bubble_status( $post ) {
		$progress = learndash_course_progress(
			array(
				'user_id'   => get_current_user_id(),
				'course_id' => $post->ID,
				'array'     => true,
			)
		);

		$status         = ( 100 === $progress['percentage'] ) ? 'completed' : 'notcompleted';
		$has_access     = sfwd_lms_has_access( $post->ID, get_current_user_id() );
		$course_pricing = learndash_get_course_price( $post->ID );

		if ( $progress['percentage'] > 0 && 100 !== $progress['percentage'] ) {
			$status = 'progress';
		}

		if ( is_user_logged_in() && isset( $has_access ) && $has_access ) {

			if (
				( 'open' === $course_pricing['type'] && 0 === $progress['percentage'] )
				|| ( 'open' !== $course_pricing['type'] && $has_access && 0 === $progress['percentage'] )
			) {
				return sprintf(
					// translators: placeholder: Start ribbon.
					esc_html_x( 'Start %s ', 'Start ribbon', 'buddyboss' ),
					LearnDash_Custom_Label::get_label( 'course' )
				);
			} else {
				return wp_strip_all_tags( learndash_status_bubble( $status, '', false ) );
			}
		} elseif ( 'free' === $course_pricing['type'] ) {
			return __( 'Free', 'buddyboss' );
		} elseif ( 'open' !== $course_pricing['type'] ) {
			return __( 'Not Enrolled', 'buddyboss' );
		} elseif ( 'open' === $course_pricing['type'] ) {
			return __( 'Start ', 'buddyboss' ) . LearnDash_Custom_Label::get_label( 'course' );
		}

		return '';
	}
}

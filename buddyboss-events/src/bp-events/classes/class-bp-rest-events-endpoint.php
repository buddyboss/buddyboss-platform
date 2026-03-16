<?php
/**
 * REST API: Events Endpoint.
 *
 * @package BuddyBoss\Events\REST
 * @since BuddyBoss Events 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * BP_REST_Events_Endpoint class.
 *
 * Handles /buddyboss/v1/events routes.
 *
 * @since BuddyBoss Events 1.0.0
 */
class BP_REST_Events_Endpoint extends WP_REST_Controller {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->namespace = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base = 'events';
	}

	/**
	 * Register routes.
	 *
	 * @since BuddyBoss Events 1.0.0
	 */
	public function register_routes() {

		// Collection: /events.
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
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);

		// Single event: /events/{id}.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				'args'   => array(
					'id' => array(
						'description' => __( 'Unique identifier for the event.', 'buddyboss' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);

		// Publish: POST /events/{id}/publish.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/publish',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'publish_item' ),
				'permission_callback' => array( $this, 'update_item_permissions_check' ),
			)
		);

		// Cancel: POST /events/{id}/cancel.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/cancel',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'cancel_item' ),
				'permission_callback' => array( $this, 'update_item_permissions_check' ),
			)
		);

		// Occurrences: GET /events/{id}/occurrences.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/occurrences',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_occurrences' ),
				'permission_callback' => array( $this, 'get_item_permissions_check' ),
			)
		);

		// Edit occurrence: PUT /events/{id}/occurrence.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/occurrence',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_occurrence' ),
				'permission_callback' => array( $this, 'update_item_permissions_check' ),
				'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
			)
		);

		// Edit series: PUT /events/{id}/series.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/series',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_series' ),
				'permission_callback' => array( $this, 'update_item_permissions_check' ),
				'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
			)
		);

		// iCal export: GET /events/{id}/ical.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/ical',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_ical' ),
				'permission_callback' => '__return_true',
			)
		);

		// Google Calendar URL: GET /events/{id}/gcal-url.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/gcal-url',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_gcal_url' ),
				'permission_callback' => '__return_true',
			)
		);

		// RSVP: POST /events/{id}/rsvp and DELETE /events/{id}/rsvp.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/rsvp',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'rsvp_item' ),
					'permission_callback' => array( $this, 'rsvp_item_permissions_check' ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'cancel_rsvp_item' ),
					'permission_callback' => array( $this, 'rsvp_item_permissions_check' ),
				),
			)
		);

		// Attendees: GET /events/{id}/attendees.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/attendees',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_attendees' ),
				'permission_callback' => '__return_true',
			)
		);

		// Invite: POST /events/{id}/invite.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/invite',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'invite_item' ),
				'permission_callback' => array( $this, 'update_item_permissions_check' ),
				'args'                => array(
					'user_ids' => array(
						'description'       => __( 'Array of user IDs to invite.', 'buddyboss' ),
						'type'              => 'array',
						'items'             => array( 'type' => 'integer' ),
						'required'          => true,
						'sanitize_callback' => function( $value ) {
							return array_map( 'absint', (array) $value );
						},
					),
				),
			)
		);
	}

	/**
	 * GET /events — list events.
	 *
	 * When ?_fc=1 is present, the endpoint operates in FullCalendar feed mode:
	 * - start/end query params (ISO8601) are mapped to the from/to filter args.
	 * - per_page defaults to 200 to load the full calendar range in one request.
	 * - Each event is prepared with the FullCalendar JSON shape.
	 *
	 * @since BuddyBoss Events 1.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response
	 */
	public function get_items( $request ) {
		// Privacy guard: block non-members from seeing private/hidden group events.
		// bp_events_get_events() does NOT enforce group privacy when group_id is
		// passed — it returns all events for the group regardless of membership.
		// This guard must run BEFORE bp_events_get_events() is called.
		$group_id_param = (int) $request->get_param( 'group_id' );
		if ( $group_id_param > 0 ) {
			$group = groups_get_group( $group_id_param );
			if ( $group && 'public' !== $group->status ) {
				// Private or hidden group — must be a member.
				if ( ! groups_is_user_member( $group_id_param, get_current_user_id() ) ) {
					return new WP_Error(
						'bp_events_rest_group_forbidden',
						__( 'You must be a member of this group to view its events.', 'buddyboss' ),
						array( 'status' => 403 )
					);
				}
			}
		}

		// FullCalendar feed mode: map start/end params to from/to filter args.
		$is_fc    = (bool) $request->get_param( '_fc' );
		$from     = $request->get_param( 'start' ) ?: $request->get_param( 'from' );
		$to       = $request->get_param( 'end' ) ?: $request->get_param( 'to' );
		$per_page = $request->get_param( 'per_page' );

		// FullCalendar requests the full range in a single call; default to 200.
		if ( $is_fc && ! $per_page ) {
			$per_page = 200;
		}

		$args = array(
			'group_id'     => $request->get_param( 'group_id' ),
			'organizer_id' => $request->get_param( 'organizer_id' ),
			'status'       => sanitize_key( $request->get_param( 'status' ) ?: 'published' ),
			'type'         => $request->get_param( 'type' ),
			'from'         => $from,
			'to'           => $to,
			'search'       => $request->get_param( 'search' ),
			'per_page'     => absint( $per_page ?: 20 ),
			'page'         => absint( $request->get_param( 'page' ) ?: 1 ),
			'orderby'      => $request->get_param( 'orderby' ) ?: 'start_date',
			'order'        => $request->get_param( 'order' ) ?: 'ASC',
		);

		// Non-admins can only see published events.
		if ( ! bp_current_user_can( 'bp_moderate' ) ) {
			$args['status'] = 'published';
		}

		$result = bp_events_get_events( $args );

		$data = array();
		foreach ( $result['events'] as $event ) {
			if ( $event->user_can_view() ) {
				$prepared = $this->prepare_item_for_response( $event, $request );
				$data[]   = $is_fc ? $prepared->get_data() : $prepared;
			}
		}

		$response = rest_ensure_response( $data );
		$response->header( 'X-WP-Total', $result['total'] );
		$response->header( 'X-WP-TotalPages', ceil( $result['total'] / ( $args['per_page'] ?: 20 ) ) );

		return $response;
	}

	/**
	 * POST /events — create event.
	 */
	public function create_item( $request ) {
		$event_id = bp_events_create_event( array(
			'title'           => sanitize_text_field( $request->get_param( 'title' ) ),
			'description'     => wp_kses_post( $request->get_param( 'description' ) ),
			'organizer_id'    => bp_loggedin_user_id(),
			'group_id'        => $request->get_param( 'group_id' ),
			'type'            => sanitize_text_field( $request->get_param( 'type' ) ?? 'in-person' ),
			'venue_name'      => sanitize_text_field( $request->get_param( 'venue_name' ) ),
			'venue_address'   => sanitize_text_field( $request->get_param( 'venue_address' ) ),
			'venue_lat'       => $request->get_param( 'venue_lat' ),
			'venue_lng'       => $request->get_param( 'venue_lng' ),
			'virtual_url'     => esc_url_raw( $request->get_param( 'virtual_url' ) ?? '' ),
			'virtual_type'    => sanitize_text_field( $request->get_param( 'virtual_type' ) ?? '' ),
			'start_date'      => sanitize_text_field( $request->get_param( 'start_date' ) ),
			'end_date'        => sanitize_text_field( $request->get_param( 'end_date' ) ),
			'timezone'        => sanitize_text_field( $request->get_param( 'timezone' ) ?? 'UTC' ),
			'capacity'        => $request->get_param( 'capacity' ),
			'status'          => sanitize_text_field( $request->get_param( 'status' ) ?? 'draft' ),
			'recurrence_rule' => sanitize_text_field( $request->get_param( 'recurrence_rule' ) ?? '' ),
		) );

		if ( ! $event_id ) {
			return new WP_Error(
				'bp_rest_events_create_failed',
				__( 'Could not create event. Check your permissions and required fields.', 'buddyboss' ),
				array( 'status' => 400 )
			);
		}

		// Save RSVP group restriction meta.
		$rsvp_group_id = (int) $request->get_param( 'rsvp_group_id' );
		if ( $rsvp_group_id > 0 ) {
			$group = groups_get_group( $rsvp_group_id );
			if ( ! empty( $group->id ) ) {
				bp_events_update_meta( $event_id, 'rsvp_group_id', $rsvp_group_id );
			}
		}

		$event    = bp_events_get_event( $event_id );
		$response = $this->prepare_item_for_response( $event, $request );
		$response->set_status( 201 );

		return rest_ensure_response( $response );
	}

	/**
	 * GET /events/{id} — single event.
	 */
	public function get_item( $request ) {
		$event = bp_events_get_event( (int) $request->get_param( 'id' ) );

		if ( ! $event ) {
			return new WP_Error(
				'bp_rest_events_not_found',
				__( 'Event not found.', 'buddyboss' ),
				array( 'status' => 404 )
			);
		}

		return rest_ensure_response( $this->prepare_item_for_response( $event, $request ) );
	}

	/**
	 * PUT /events/{id} — update event.
	 */
	public function update_item( $request ) {
		$event_id = (int) $request->get_param( 'id' );
		$params   = $request->get_params();

		$allowed  = array( 'title', 'description', 'type', 'venue_name', 'venue_address',
			'venue_lat', 'venue_lng', 'virtual_url', 'virtual_type', 'start_date',
			'end_date', 'timezone', 'capacity', 'status', 'recurrence_rule' );

		$args = array_intersect_key( $params, array_flip( $allowed ) );

		$result = bp_events_update_event( $event_id, $args );

		if ( ! $result ) {
			return new WP_Error(
				'bp_rest_events_update_failed',
				__( 'Could not update event.', 'buddyboss' ),
				array( 'status' => 500 )
			);
		}

		// Save or remove RSVP group restriction meta.
		if ( $request->has_param( 'rsvp_group_id' ) ) {
			$rsvp_group_id = (int) $request->get_param( 'rsvp_group_id' );
			if ( $rsvp_group_id > 0 ) {
				$group = groups_get_group( $rsvp_group_id );
				if ( ! empty( $group->id ) ) {
					bp_events_update_meta( $event_id, 'rsvp_group_id', $rsvp_group_id );
				}
			} else {
				bp_events_delete_meta( $event_id, 'rsvp_group_id' );
			}
		}

		// Trigger waitlist notification if capacity changed and spots opened.
		// Called AFTER bp_events_update_event() so the new capacity is already
		// saved when the waitlist broadcast fires.
		if ( $request->has_param( 'capacity' ) ) {
			bp_events_update_capacity( $event_id, $request->get_param( 'capacity' ) );
		}

		$event = bp_events_get_event( $event_id );
		return rest_ensure_response( $this->prepare_item_for_response( $event, $request ) );
	}

	/**
	 * DELETE /events/{id} — delete event.
	 */
	public function delete_item( $request ) {
		$event    = bp_events_get_event( (int) $request->get_param( 'id' ) );
		$previous = $this->prepare_item_for_response( $event, $request );

		$result = bp_events_delete_event( (int) $request->get_param( 'id' ) );

		if ( ! $result ) {
			return new WP_Error(
				'bp_rest_events_delete_failed',
				__( 'Could not delete event.', 'buddyboss' ),
				array( 'status' => 500 )
			);
		}

		$response = new WP_REST_Response();
		$response->set_data( array( 'deleted' => true, 'previous' => $previous->get_data() ) );
		return $response;
	}

	/**
	 * POST /events/{id}/publish.
	 */
	public function publish_item( $request ) {
		$result = bp_events_update_event( (int) $request->get_param( 'id' ), array( 'status' => 'published' ) );

		if ( ! $result ) {
			return new WP_Error( 'bp_rest_events_publish_failed', __( 'Could not publish event.', 'buddyboss' ), array( 'status' => 500 ) );
		}

		$event = bp_events_get_event( (int) $request->get_param( 'id' ) );
		return rest_ensure_response( $this->prepare_item_for_response( $event, $request ) );
	}

	/**
	 * POST /events/{id}/cancel.
	 */
	public function cancel_item( $request ) {
		$result = bp_events_update_event( (int) $request->get_param( 'id' ), array( 'status' => 'cancelled' ) );

		if ( ! $result ) {
			return new WP_Error( 'bp_rest_events_cancel_failed', __( 'Could not cancel event.', 'buddyboss' ), array( 'status' => 500 ) );
		}

		$event = bp_events_get_event( (int) $request->get_param( 'id' ) );
		return rest_ensure_response( $this->prepare_item_for_response( $event, $request ) );
	}

	/**
	 * GET /events/{id}/occurrences — list occurrences of a recurring event.
	 */
	public function get_occurrences( $request ) {
		$result = bp_events_get_events( array(
			'parent_event_id' => (int) $request->get_param( 'id' ),
			'status'          => null,
		) );

		$data = array_map( function( $event ) use ( $request ) {
			return $this->prepare_item_for_response( $event, $request );
		}, $result['events'] );

		return rest_ensure_response( $data );
	}

	/**
	 * PUT /events/{id}/occurrence — edit a single occurrence only.
	 */
	public function update_occurrence( $request ) {
		// For a single occurrence edit, we detach it from the series by clearing parent_event_id.
		$args            = $request->get_params();
		$args['parent_event_id'] = null; // Detach from series.

		$result = bp_events_update_event( (int) $request->get_param( 'id' ), $args );

		if ( ! $result ) {
			return new WP_Error( 'bp_rest_events_update_failed', __( 'Could not update occurrence.', 'buddyboss' ), array( 'status' => 500 ) );
		}

		$event = bp_events_get_event( (int) $request->get_param( 'id' ) );
		return rest_ensure_response( $this->prepare_item_for_response( $event, $request ) );
	}

	/**
	 * PUT /events/{id}/series — edit this and all future occurrences.
	 */
	public function update_series( $request ) {
		global $wpdb;
		$bp = buddypress();

		$event = bp_events_get_event( (int) $request->get_param( 'id' ) );
		if ( ! $event ) {
			return new WP_Error( 'bp_rest_events_not_found', __( 'Event not found.', 'buddyboss' ), array( 'status' => 404 ) );
		}

		// Get all future occurrences (same parent, starting from this event's date).
		$parent_id = $event->parent_event_id ?? $event->id;
		$future_ids = $wpdb->get_col( $wpdb->prepare(
			"SELECT id FROM {$bp->events->table_name} WHERE (id = %d OR parent_event_id = %d) AND start_date >= %s",
			$parent_id, $parent_id, $event->start_date
		) );

		$args    = $request->get_params();
		$allowed = array( 'title', 'description', 'type', 'venue_name', 'venue_address',
			'venue_lat', 'venue_lng', 'virtual_url', 'virtual_type', 'timezone', 'capacity' );
		$args    = array_intersect_key( $args, array_flip( $allowed ) );

		foreach ( $future_ids as $future_id ) {
			bp_events_update_event( (int) $future_id, $args );
		}

		$event = bp_events_get_event( (int) $request->get_param( 'id' ) );
		return rest_ensure_response( $this->prepare_item_for_response( $event, $request ) );
	}

	/**
	 * GET /events/{id}/ical — download iCal file.
	 */
	public function get_ical( $request ) {
		$event = bp_events_get_event( (int) $request->get_param( 'id' ) );

		if ( ! $event || ! $event->user_can_view() ) {
			return new WP_Error( 'bp_rest_events_not_found', __( 'Event not found.', 'buddyboss' ), array( 'status' => 404 ) );
		}

		$start  = gmdate( 'Ymd\THis\Z', strtotime( $event->start_date ) );
		$end    = gmdate( 'Ymd\THis\Z', strtotime( $event->end_date ) );
		$stamp  = gmdate( 'Ymd\THis\Z' );
		$uid    = 'bp-event-' . $event->id . '@' . parse_url( home_url(), PHP_URL_HOST );

		$location = 'in-person' === $event->type ? $event->venue_address : $event->virtual_url;

		$ical = "BEGIN:VCALENDAR\r\n"
			. "VERSION:2.0\r\n"
			. "PRODID:-//BuddyBoss Events//EN\r\n"
			. "BEGIN:VEVENT\r\n"
			. "UID:{$uid}\r\n"
			. "DTSTAMP:{$stamp}\r\n"
			. "DTSTART:{$start}\r\n"
			. "DTEND:{$end}\r\n"
			. "SUMMARY:" . bp_events_ical_escape( $event->title ) . "\r\n"
			. "DESCRIPTION:" . bp_events_ical_escape( wp_strip_all_tags( $event->description ) ) . "\r\n"
			. "LOCATION:" . bp_events_ical_escape( $location ) . "\r\n"
			. "URL:" . bp_get_event_permalink( $event ) . "\r\n"
			. "END:VEVENT\r\n"
			. "END:VCALENDAR\r\n";

		$response = new WP_REST_Response( $ical );
		$response->header( 'Content-Type', 'text/calendar; charset=utf-8' );
		$response->header( 'Content-Disposition', 'attachment; filename="event-' . $event->id . '.ics"' );

		return $response;
	}

	/**
	 * GET /events/{id}/gcal-url — Google Calendar link.
	 */
	public function get_gcal_url( $request ) {
		$event = bp_events_get_event( (int) $request->get_param( 'id' ) );

		if ( ! $event || ! $event->user_can_view() ) {
			return new WP_Error( 'bp_rest_events_not_found', __( 'Event not found.', 'buddyboss' ), array( 'status' => 404 ) );
		}

		$start    = gmdate( 'Ymd\THis\Z', strtotime( $event->start_date ) );
		$end      = gmdate( 'Ymd\THis\Z', strtotime( $event->end_date ) );
		$location = 'in-person' === $event->type ? $event->venue_address : $event->virtual_url;

		$url = add_query_arg(
			array(
				'action'   => 'TEMPLATE',
				'text'     => rawurlencode( $event->title ),
				'dates'    => $start . '/' . $end,
				'details'  => rawurlencode( wp_strip_all_tags( $event->description ) ),
				'location' => rawurlencode( $location ),
			),
			'https://calendar.google.com/calendar/render'
		);

		return rest_ensure_response( array( 'url' => $url ) );
	}

	/** Permission checks ******************************************************/

	public function get_items_permissions_check( $request ) {
		return true; // Public. Privacy enforced in query.
	}

	public function create_item_permissions_check( $request ) {
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'bp_rest_authorization_required',
				__( 'You must be logged in to create an event.', 'buddyboss' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}
		return true;
	}

	public function get_item_permissions_check( $request ) {
		$event = bp_events_get_event( (int) $request->get_param( 'id' ) );

		if ( ! $event ) {
			return new WP_Error( 'bp_rest_events_not_found', __( 'Event not found.', 'buddyboss' ), array( 'status' => 404 ) );
		}

		if ( ! $event->user_can_view() ) {
			return new WP_Error(
				'bp_rest_authorization_required',
				__( 'You do not have permission to view this event.', 'buddyboss' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	public function update_item_permissions_check( $request ) {
		$event = bp_events_get_event( (int) $request->get_param( 'id' ) );

		if ( ! $event ) {
			return new WP_Error( 'bp_rest_events_not_found', __( 'Event not found.', 'buddyboss' ), array( 'status' => 404 ) );
		}

		if ( ! $event->user_can_edit() ) {
			return new WP_Error(
				'bp_rest_authorization_required',
				__( 'You do not have permission to edit this event.', 'buddyboss' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	public function delete_item_permissions_check( $request ) {
		return $this->update_item_permissions_check( $request );
	}

	/** Schema & response ******************************************************/

	/**
	 * Prepare an event for REST response.
	 *
	 * When the request includes ?_fc=1 the response is shaped as a FullCalendar
	 * event object with ISO8601 dates (T separator) and an extendedProps block.
	 * Otherwise the full BP_Event field set is returned.
	 *
	 * @since BuddyBoss Events 1.0.0
	 *
	 * @param BP_Event        $event   Event object.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function prepare_item_for_response( $event, $request ) {
		$is_fc = (bool) $request->get_param( '_fc' );

		if ( $is_fc ) {
			// FullCalendar 6 event object shape.
			// MySQL datetimes use a space separator; FullCalendar requires 'T'.
			$data = array(
				'id'            => $event->id,
				'title'         => $event->title,
				'start'         => str_replace( ' ', 'T', $event->start_date ),
				'end'           => str_replace( ' ', 'T', $event->end_date ),
				'url'           => esc_url_raw( bp_get_event_permalink( $event ) ),
				'extendedProps' => array(
					'type'   => $event->type,
					'venue'  => $event->venue_name ?: $event->virtual_url,
					'status' => $event->status,
				),
			);

			return rest_ensure_response( $data );
		}

		// Standard full-field response.
		$data = array(
			'id'              => $event->id,
			'title'           => $event->title,
			'description'     => $event->description,
			'slug'            => $event->slug,
			'permalink'       => bp_get_event_permalink( $event ),
			'organizer_id'    => $event->organizer_id,
			'group_id'        => $event->group_id,
			'type'            => $event->type,
			'venue_name'      => $event->venue_name,
			'venue_address'   => $event->venue_address,
			'venue_lat'       => $event->venue_lat,
			'venue_lng'       => $event->venue_lng,
			'virtual_url'     => $event->virtual_url,
			'virtual_type'    => $event->virtual_type,
			'start_date'      => $event->start_date,
			'end_date'        => $event->end_date,
			'timezone'        => $event->timezone,
			'capacity'        => $event->capacity,
			'status'          => $event->status,
			'recurrence_rule' => $event->recurrence_rule,
			'parent_event_id' => $event->parent_event_id,
			'date_created'    => $event->date_created,
			'date_modified'   => $event->date_modified,
			'user_can_edit'   => $event->user_can_edit(),
		);

		return rest_ensure_response( $data );
	}

	/**
	 * Get REST API item schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'bp_event',
			'type'       => 'object',
			'properties' => array(
				'id'              => array( 'type' => 'integer', 'readonly' => true ),
				'title'           => array( 'type' => 'string', 'required' => true ),
				'description'     => array( 'type' => 'string' ),
				'slug'            => array( 'type' => 'string', 'readonly' => true ),
				'permalink'       => array( 'type' => 'string', 'format' => 'uri', 'readonly' => true ),
				'organizer_id'    => array( 'type' => 'integer' ),
				'group_id'        => array( 'type' => array( 'integer', 'null' ) ),
				'type'            => array( 'type' => 'string', 'enum' => array( 'in-person', 'virtual', 'hybrid' ) ),
				'venue_name'      => array( 'type' => 'string' ),
				'venue_address'   => array( 'type' => 'string' ),
				'venue_lat'       => array( 'type' => array( 'number', 'null' ) ),
				'venue_lng'       => array( 'type' => array( 'number', 'null' ) ),
				'virtual_url'     => array( 'type' => 'string', 'format' => 'uri' ),
				'virtual_type'    => array( 'type' => 'string', 'enum' => array( 'zoom', 'meet', 'other', '' ) ),
				'start_date'      => array( 'type' => 'string', 'format' => 'date-time', 'required' => true ),
				'end_date'        => array( 'type' => 'string', 'format' => 'date-time', 'required' => true ),
				'timezone'        => array( 'type' => 'string' ),
				'capacity'        => array( 'type' => array( 'integer', 'null' ) ),
				'status'          => array( 'type' => 'string', 'enum' => array( 'draft', 'pending', 'published', 'cancelled' ) ),
				'recurrence_rule' => array( 'type' => 'string' ),
				'parent_event_id' => array( 'type' => array( 'integer', 'null' ) ),
				'date_created'    => array( 'type' => 'string', 'format' => 'date-time', 'readonly' => true ),
				'date_modified'   => array( 'type' => 'string', 'format' => 'date-time', 'readonly' => true ),
				'user_can_edit'   => array( 'type' => 'boolean', 'readonly' => true ),
			),
		);
	}

	/** RSVP methods *************************************************************/

	/**
	 * Permission check for RSVP and cancel-RSVP endpoints.
	 *
	 * @since BuddyBoss Events 1.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if allowed, WP_Error on failure.
	 */
	public function rsvp_item_permissions_check( $request ) {
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'bp_rest_authorization_required',
				__( 'You must be logged in to RSVP.', 'buddyboss' ),
				array( 'status' => 401 )
			);
		}

		return bp_events_user_can_rsvp( (int) $request->get_param( 'id' ) );
	}

	/**
	 * POST /events/{id}/rsvp — RSVP to an event.
	 *
	 * @since BuddyBoss Events 1.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function rsvp_item( $request ) {
		global $wpdb;

		$bp       = buddypress();
		$event_id = (int) $request->get_param( 'id' );

		$result = bp_events_rsvp_event( $event_id );

		if ( false === $result ) {
			return new WP_Error(
				'bp_rest_events_rsvp_failed',
				__( 'Could not complete RSVP. Please try again.', 'buddyboss' ),
				array( 'status' => 500 )
			);
		}

		// Recompute at_capacity for the response.
		$event            = bp_events_get_event( $event_id );
		$registered_count = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$bp->events->table_name_attendees} WHERE event_id = %d AND status = 'registered'",
			$event_id
		) );
		$at_capacity = $event && ! is_null( $event->capacity ) && $registered_count >= (int) $event->capacity;

		return new WP_REST_Response(
			array(
				'status'      => $result,
				'at_capacity' => $at_capacity,
			),
			200
		);
	}

	/**
	 * DELETE /events/{id}/rsvp — cancel an RSVP.
	 *
	 * Event organizers and admins may pass user_id in the body to cancel on behalf of another user.
	 *
	 * @since BuddyBoss Events 1.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function cancel_rsvp_item( $request ) {
		$event_id = (int) $request->get_param( 'id' );
		$user_id  = (int) $request->get_param( 'user_id' ) ?: bp_loggedin_user_id();

		// If cancelling on behalf of another user, verify permission.
		if ( $user_id !== bp_loggedin_user_id() ) {
			$event = bp_events_get_event( $event_id );

			$is_organizer = $event && ( (int) $event->organizer_id === bp_loggedin_user_id() );
			$is_admin     = current_user_can( 'administrator' );

			if ( ! $is_organizer && ! $is_admin ) {
				return new WP_Error(
					'bp_rest_authorization_required',
					__( 'You do not have permission to cancel this RSVP.', 'buddyboss' ),
					array( 'status' => 403 )
				);
			}
		}

		$result = bp_events_cancel_rsvp( $event_id, $user_id );

		return new WP_REST_Response(
			array( 'cancelled' => $result ),
			200
		);
	}

	/**
	 * POST /events/{id}/invite — send invites to multiple users.
	 *
	 * @since BuddyBoss Events 1.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function invite_item( $request ) {
		$event_id = (int) $request->get_param( 'id' );
		$user_ids = (array) $request->get_param( 'user_ids' );
		$results  = array();

		foreach ( $user_ids as $invitee_id ) {
			$invitee_id = (int) $invitee_id;
			if ( $invitee_id < 1 ) {
				continue;
			}
			$result = bp_events_invite_member( $event_id, $invitee_id );
			$results[ $invitee_id ] = is_wp_error( $result )
				? array( 'success' => false, 'error' => $result->get_error_message() )
				: array( 'success' => (bool) $result );
		}

		return new WP_REST_Response( array( 'invites' => $results ), 200 );
	}

	/**
	 * GET /events/{id}/attendees — list registered attendees.
	 *
	 * @since BuddyBoss Events 1.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response
	 */
	public function get_attendees( $request ) {
		$event_id = (int) $request->get_param( 'id' );
		$rows     = bp_events_get_attendees( $event_id, 'registered' );

		$attendees = array_map( function( $row ) {
			return array(
				'user_id'      => (int) $row->user_id,
				'display_name' => get_the_author_meta( 'display_name', $row->user_id ),
				'avatar_url'   => bp_core_fetch_avatar( array(
					'item_id' => $row->user_id,
					'type'    => 'thumb',
					'html'    => false,
				) ),
				'status'       => $row->status,
			);
		}, $rows );

		return new WP_REST_Response( $attendees, 200 );
	}

	/**
	 * Get collection query parameters.
	 *
	 * Includes standard pagination params plus events-specific filters and
	 * FullCalendar feed params (start, end, _fc).
	 *
	 * @since BuddyBoss Events 1.0.0
	 *
	 * @return array
	 */
	public function get_collection_params() {
		return array_merge(
			parent::get_collection_params(),
			array(
				'group_id'     => array(
				'description'       => __( 'Limit results to events belonging to this group ID.', 'buddyboss' ),
				'type'              => 'integer',
				'default'           => 0,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			),
				'organizer_id' => array( 'type' => 'integer', 'sanitize_callback' => 'absint' ),
				'status'       => array(
					'type'              => 'string',
					'description'       => __( 'Filter by event status.', 'buddyboss' ),
					'sanitize_callback' => 'sanitize_key',
					'default'           => 'published',
				),
				'type'         => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ),
				'from'         => array(
					'type'        => 'string',
					'description' => __( 'Start date filter (MySQL or ISO8601).', 'buddyboss' ),
				),
				'to'           => array(
					'type'        => 'string',
					'description' => __( 'End date filter (MySQL or ISO8601).', 'buddyboss' ),
				),
				'start'        => array(
					'type'        => 'string',
					'description' => __( 'FullCalendar range start (ISO8601). Maps to the from filter.', 'buddyboss' ),
				),
				'end'          => array(
					'type'        => 'string',
					'description' => __( 'FullCalendar range end (ISO8601). Maps to the to filter.', 'buddyboss' ),
				),
				'_fc'          => array(
					'type'        => 'integer',
					'description' => __( 'FullCalendar feed mode. Pass 1 to receive FC-shaped event objects.', 'buddyboss' ),
					'default'     => 0,
				),
				'orderby'      => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => 'start_date' ),
				'order'        => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => 'ASC' ),
			)
		);
	}
}

/**
 * Escape a string for iCal format.
 *
 * @param string $string
 * @return string
 */
function bp_events_ical_escape( $string ) {
	return addcslashes( $string, ",;\\n" );
}

<?php
/**
 * BuddyBoss Events Event Class.
 *
 * @package BuddyBoss\Events
 * @since BuddyBoss Events 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * BuddyBoss Event class.
 *
 * @since BuddyBoss Events 1.0.0
 */
class BP_Event {

	/** Properties *************************************************************/

	/** @var int Event ID. */
	public $id = 0;

	/** @var string Event title. */
	public $title = '';

	/** @var string Event description (HTML). */
	public $description = '';

	/** @var string URL slug. */
	public $slug = '';

	/** @var int Organizer user ID. */
	public $organizer_id = 0;

	/** @var int|null Associated group ID. */
	public $group_id = null;

	/** @var string Event type: in-person|virtual|hybrid */
	public $type = 'in-person';

	/** @var string Venue name (in-person). */
	public $venue_name = '';

	/** @var string Full venue address. */
	public $venue_address = '';

	/** @var float|null Venue latitude. */
	public $venue_lat = null;

	/** @var float|null Venue longitude. */
	public $venue_lng = null;

	/** @var string Virtual meeting URL. */
	public $virtual_url = '';

	/** @var string Virtual type: zoom|meet|other */
	public $virtual_type = '';

	/** @var string Start datetime (MySQL format). */
	public $start_date = '';

	/** @var string End datetime (MySQL format). */
	public $end_date = '';

	/** @var string Timezone string. */
	public $timezone = 'UTC';

	/** @var int|null Capacity limit (null = unlimited). */
	public $capacity = null;

	/** @var string Status: draft|pending|published|cancelled */
	public $status = 'draft';

	/** @var string RRULE string for recurring events. */
	public $recurrence_rule = '';

	/** @var int|null Parent event ID for occurrences. */
	public $parent_event_id = null;

	/** @var string Created at datetime. */
	public $date_created = '';

	/** @var string Updated at datetime. */
	public $date_modified = '';

	/** @var array Event meta. */
	public $data = null;

	/** Methods ****************************************************************/

	/**
	 * Constructor.
	 *
	 * @param int|null $id Event ID to populate, or null for new event.
	 */
	public function __construct( $id = null ) {
		if ( ! empty( $id ) ) {
			$this->id = (int) $id;
			$this->populate();
		}
	}

	/**
	 * Populate the event from the database.
	 *
	 * @since BuddyBoss Events 1.0.0
	 */
	public function populate() {
		global $wpdb;

		$bp    = buddypress();
		$event = wp_cache_get( $this->id, 'bp_events' );

		if ( false === $event ) {
			$event = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$bp->events->table_name} WHERE id = %d",
					$this->id
				)
			);

			if ( empty( $event ) ) {
				$this->id = 0;
				return;
			}

			wp_cache_set( $this->id, $event, 'bp_events' );
		}

		$this->id              = (int) $event->id;
		$this->title           = $event->title;
		$this->description     = $event->description;
		$this->slug            = $event->slug;
		$this->organizer_id    = (int) $event->organizer_id;
		$this->group_id        = ! empty( $event->group_id ) ? (int) $event->group_id : null;
		$this->type            = $event->type;
		$this->venue_name      = $event->venue_name;
		$this->venue_address   = $event->venue_address;
		$this->venue_lat       = ! empty( $event->venue_lat ) ? (float) $event->venue_lat : null;
		$this->venue_lng       = ! empty( $event->venue_lng ) ? (float) $event->venue_lng : null;
		$this->virtual_url     = $event->virtual_url;
		$this->virtual_type    = $event->virtual_type;
		$this->start_date      = $event->start_date;
		$this->end_date        = $event->end_date;
		$this->timezone        = $event->timezone;
		$this->capacity        = ! empty( $event->capacity ) ? (int) $event->capacity : null;
		$this->status          = $event->status;
		$this->recurrence_rule = $event->recurrence_rule;
		$this->parent_event_id = ! empty( $event->parent_event_id ) ? (int) $event->parent_event_id : null;
		$this->date_created    = $event->date_created;
		$this->date_modified   = $event->date_modified;
	}

	/**
	 * Save the event to the database (insert or update).
	 *
	 * @since BuddyBoss Events 1.0.0
	 * @return bool True on success, false on failure.
	 */
	public function save() {
		global $wpdb;

		$bp = buddypress();

		/**
		 * Fires before an event is saved.
		 *
		 * @param BP_Event $event The event object.
		 */
		do_action( 'bp_events_before_event_save', $this );

		$data = array(
			'title'            => $this->title,
			'description'      => $this->description,
			'slug'             => $this->slug,
			'organizer_id'     => $this->organizer_id,
			'group_id'         => $this->group_id,
			'type'             => $this->type,
			'venue_name'       => $this->venue_name,
			'venue_address'    => $this->venue_address,
			'venue_lat'        => $this->venue_lat,
			'venue_lng'        => $this->venue_lng,
			'virtual_url'      => $this->virtual_url,
			'virtual_type'     => $this->virtual_type,
			'start_date'       => $this->start_date,
			'end_date'         => $this->end_date,
			'timezone'         => $this->timezone,
			'capacity'         => $this->capacity,
			'status'           => $this->status,
			'recurrence_rule'  => $this->recurrence_rule,
			'parent_event_id'  => $this->parent_event_id,
			'date_modified'    => bp_core_current_time(),
		);

		$formats = array( '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%f', '%f', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%d', '%s' );

		if ( empty( $this->id ) ) {
			$data['date_created'] = bp_core_current_time();
			$formats[]            = '%s';

			$result = $wpdb->insert( $bp->events->table_name, $data, $formats );
			if ( false === $result ) {
				return false;
			}
			$this->id = $wpdb->insert_id;
		} else {
			$result = $wpdb->update(
				$bp->events->table_name,
				$data,
				array( 'id' => $this->id ),
				$formats,
				array( '%d' )
			);
			if ( false === $result ) {
				return false;
			}
		}

		wp_cache_delete( $this->id, 'bp_events' );

		/**
		 * Fires after an event is saved.
		 *
		 * @param BP_Event $event The event object.
		 */
		do_action( 'bp_events_after_event_save', $this );

		return true;
	}

	/**
	 * Delete the event.
	 *
	 * @since BuddyBoss Events 1.0.0
	 * @return bool True on success, false on failure.
	 */
	public function delete() {
		global $wpdb;

		$bp = buddypress();

		do_action( 'bp_events_before_event_delete', $this );

		$result = $wpdb->delete( $bp->events->table_name, array( 'id' => $this->id ), array( '%d' ) );

		if ( false === $result ) {
			return false;
		}

		// Delete all meta.
		$wpdb->delete( $bp->events->table_name_meta, array( 'event_id' => $this->id ), array( '%d' ) );

		// Delete attendees.
		$wpdb->delete( $bp->events->table_name_attendees, array( 'event_id' => $this->id ), array( '%d' ) );

		// Delete invites.
		$wpdb->delete( $bp->events->table_name_invites, array( 'event_id' => $this->id ), array( '%d' ) );

		wp_cache_delete( $this->id, 'bp_events' );

		do_action( 'bp_events_after_event_delete', $this );

		return true;
	}

	/**
	 * Check if the event is accessible to the current user, respecting group privacy.
	 *
	 * @since BuddyBoss Events 1.0.0
	 * @return bool
	 */
	public function user_can_view() {
		// Published events with no group are public.
		if ( 'published' === $this->status && empty( $this->group_id ) ) {
			return true;
		}

		// Admins can see everything.
		if ( bp_current_user_can( 'bp_moderate' ) ) {
			return true;
		}

		// Group privacy check.
		if ( ! empty( $this->group_id ) ) {
			$group = groups_get_group( $this->group_id );

			if ( 'public' === $group->status ) {
				return 'published' === $this->status;
			}

			// Private/hidden: must be a group member.
			return groups_is_user_member( bp_loggedin_user_id(), $this->group_id );
		}

		// Organizer can always see their own event.
		if ( bp_loggedin_user_id() === $this->organizer_id ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if the current user can edit this event.
	 *
	 * @since BuddyBoss Events 1.0.0
	 * @return bool
	 */
	public function user_can_edit() {
		if ( bp_current_user_can( 'bp_moderate' ) ) {
			return true;
		}

		return bp_loggedin_user_id() === $this->organizer_id;
	}
}

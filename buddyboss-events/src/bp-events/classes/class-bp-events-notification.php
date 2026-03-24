<?php
/**
 * BuddyBoss Events Notification Class.
 *
 * Registers all event-related notification types, email templates, and
 * in-app notification formatting for the BuddyBoss Events component.
 * Extends BP_Core_Notification_Abstract so all types appear automatically
 * in both the admin Notification settings and the front-end user preferences.
 *
 * Notification types registered:
 *   bb_event_waitlist_spot_open  – A spot opened on an event you're waitlisted for.
 *   bb_event_member_invited      – You have been invited to an event.
 *   bb_event_rsvp_confirmed      – Your RSVP to an event has been confirmed.
 *   bb_event_approved            – Your submitted event has been approved.
 *   bb_event_rejected            – Your submitted event has been rejected.
 *
 * @package BuddyBoss\Events
 * @since   BuddyBoss Events 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Set up the BP_Events_Notification class.
 *
 * @since BuddyBoss Events 1.0.0
 */
class BP_Events_Notification extends BP_Core_Notification_Abstract {

	/**
	 * Instance of this class.
	 *
	 * @since BuddyBoss Events 1.0.0
	 *
	 * @var object
	 */
	private static $instance = null;

	/**
	 * Get the instance of this class.
	 *
	 * @since BuddyBoss Events 1.0.0
	 *
	 * @return null|BP_Events_Notification|object
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor method.
	 *
	 * @since BuddyBoss Events 1.0.0
	 */
	public function __construct() {
		// Must hook before priority 20 (the abstract default) so the group
		// and types are registered before components run their own hooks.
		add_action( 'bp_init', array( $this, 'start' ), 5 );
	}

	/**
	 * Register all notification types, email types, and filters.
	 *
	 * Called by BP_Core_Notification_Abstract::start().
	 *
	 * @since BuddyBoss Events 1.0.0
	 *
	 * @return void
	 */
	public function load() {
		// Register the "Events" group tab in notification preferences.
		// Priority 15 positions Events between Social Groups (9) and Member Connections (22).
		$this->register_notification_group(
			'events',
			esc_html__( 'Events', 'buddyboss' ),
			esc_html__( 'Events', 'buddyboss' ),
			15
		);

		// Individual notification types.
		$this->register_notification_for_waitlist_spot_open();
		$this->register_notification_for_member_invited();
		$this->register_notification_for_rsvp_confirmed();
		$this->register_notification_for_event_approved();
		$this->register_notification_for_event_rejected();

		// Notification bell dropdown filters.
		$this->register_notification_filter(
			esc_html__( 'Event invitations', 'buddyboss' ),
			array( 'bb_event_member_invited' ),
			55
		);

		$this->register_notification_filter(
			esc_html__( 'Event RSVPs and waitlist', 'buddyboss' ),
			array( 'bb_event_waitlist_spot_open', 'bb_event_rsvp_confirmed' ),
			56
		);

		$this->register_notification_filter(
			esc_html__( 'Event approvals', 'buddyboss' ),
			array( 'bb_event_approved', 'bb_event_rejected' ),
			57
		);
	}

	// -------------------------------------------------------------------------
	// Registration helpers
	// -------------------------------------------------------------------------

	/**
	 * Register notification for waitlist spot opening.
	 *
	 * Triggered when an attendee cancels or capacity is raised, freeing a
	 * spot for users who are on the waitlist.
	 *
	 * @since BuddyBoss Events 1.0.0
	 */
	public function register_notification_for_waitlist_spot_open() {
		$this->register_notification_type(
			'bb_event_waitlist_spot_open',
			esc_html__( 'A spot opens on an event you are waitlisted for', 'buddyboss' ),
			esc_html__( 'A member on the waitlist is notified that a spot has opened', 'buddyboss' ),
			'events'
		);

		$this->register_email_type(
			'events-waitlist-spot-open',
			array(
				/* translators: do not remove {} brackets or translate its contents. */
				'email_title'         => __( '[{{{site.name}}}] A spot has opened for "{{event.name}}"', 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_content'       => __( "Good news — a spot has opened up for the event &quot;<a href=\"{{{event.url}}}\">{{event.name}}</a>&quot; that you were waitlisted for.\n\nHead over to the event page to confirm your spot before it fills up again.\n\n<a href=\"{{{event.url}}}\">View event and confirm your spot &rarr;</a>", 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_plain_content' => __( "Good news — a spot has opened up for the event \"{{event.name}}\" that you were waitlisted for.\n\nHead over to the event page to confirm your spot before it fills up again:\n{{{event.url}}}", 'buddyboss' ),
				'situation_label'     => __( 'A member on the waitlist is notified that a spot has opened', 'buddyboss' ),
				'unsubscribe_text'    => __( 'You will no longer receive emails when a spot opens on an event you are waitlisted for.', 'buddyboss' ),
			),
			'bb_event_waitlist_spot_open'
		);

		$this->register_notification(
			'events',
			'bb_event_waitlist_spot_open',
			'bb_event_waitlist_spot_open'
		);

		add_filter(
			'bp_events_bb_event_waitlist_spot_open_notification',
			array( $this, 'bb_format_events_notification' ),
			10,
			7
		);
	}

	/**
	 * Register notification for event invite.
	 *
	 * Triggered when an organizer invites a member to an event.
	 *
	 * @since BuddyBoss Events 1.0.0
	 */
	public function register_notification_for_member_invited() {
		$this->register_notification_type(
			'bb_event_member_invited',
			esc_html__( 'You receive an invitation to an event', 'buddyboss' ),
			esc_html__( 'A member receives an invitation to an event', 'buddyboss' ),
			'events'
		);

		$this->register_email_type(
			'events-member-invited',
			array(
				/* translators: do not remove {} brackets or translate its contents. */
				'email_title'         => __( '[{{{site.name}}}] You have been invited to "{{event.name}}"', 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_content'       => __( "<a href=\"{{{inviter.url}}}\">{{inviter.name}}</a> has invited you to attend the event &quot;<a href=\"{{{event.url}}}\">{{event.name}}</a>&quot;.\n\n<a href=\"{{{event.url}}}\">View event details and RSVP &rarr;</a>", 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_plain_content' => __( "{{inviter.name}} has invited you to attend the event \"{{event.name}}\".\n\nTo view the event details and RSVP, visit:\n{{{event.url}}}\n\nTo view {{inviter.name}}'s profile, visit:\n{{{inviter.url}}}", 'buddyboss' ),
				'situation_label'     => __( 'A member receives an invitation to an event', 'buddyboss' ),
				'unsubscribe_text'    => __( 'You will no longer receive emails when you are invited to an event.', 'buddyboss' ),
			),
			'bb_event_member_invited'
		);

		$this->register_notification(
			'events',
			'bb_event_member_invited',
			'bb_event_member_invited'
		);

		add_filter(
			'bp_events_bb_event_member_invited_notification',
			array( $this, 'bb_format_events_notification' ),
			10,
			7
		);
	}

	/**
	 * Register notification for RSVP confirmation.
	 *
	 * Triggered when a member's RSVP to a capacity-limited event is confirmed
	 * (either immediately on sign-up or after being promoted from the waitlist).
	 *
	 * @since BuddyBoss Events 1.0.0
	 */
	public function register_notification_for_rsvp_confirmed() {
		$this->register_notification_type(
			'bb_event_rsvp_confirmed',
			esc_html__( 'Your RSVP to an event is confirmed', 'buddyboss' ),
			esc_html__( 'A member\'s RSVP to an event is confirmed', 'buddyboss' ),
			'events'
		);

		$this->register_email_type(
			'events-rsvp-confirmed',
			array(
				/* translators: do not remove {} brackets or translate its contents. */
				'email_title'         => __( '[{{{site.name}}}] Your RSVP for "{{event.name}}" is confirmed', 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_content'       => __( "You are confirmed as an attendee for the event &quot;<a href=\"{{{event.url}}}\">{{event.name}}</a>&quot;. We look forward to seeing you there!\n\n<a href=\"{{{event.url}}}\">View event details &rarr;</a>", 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_plain_content' => __( "You are confirmed as an attendee for the event \"{{event.name}}\". We look forward to seeing you there!\n\nTo view the event details, visit:\n{{{event.url}}}", 'buddyboss' ),
				'situation_label'     => __( 'A member\'s RSVP to an event is confirmed', 'buddyboss' ),
				'unsubscribe_text'    => __( 'You will no longer receive emails when your RSVP to an event is confirmed.', 'buddyboss' ),
			),
			'bb_event_rsvp_confirmed'
		);

		$this->register_notification(
			'events',
			'bb_event_rsvp_confirmed',
			'bb_event_rsvp_confirmed'
		);

		add_filter(
			'bp_events_bb_event_rsvp_confirmed_notification',
			array( $this, 'bb_format_events_notification' ),
			10,
			7
		);
	}

	/**
	 * Register notification for event approved.
	 *
	 * Triggered when an admin approves a pending event submitted via the
	 * front-end submission form (Phase 7).
	 *
	 * @since BuddyBoss Events 1.0.0
	 */
	public function register_notification_for_event_approved() {
		$this->register_notification_type(
			'bb_event_approved',
			esc_html__( 'Your submitted event is approved', 'buddyboss' ),
			esc_html__( 'An organizer\'s submitted event is approved', 'buddyboss' ),
			'events'
		);

		$this->register_email_type(
			'events-approved',
			array(
				/* translators: do not remove {} brackets or translate its contents. */
				'email_title'         => __( '[{{{site.name}}}] Your event "{{event.name}}" has been approved', 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_content'       => __( "Great news — your event &quot;<a href=\"{{{event.url}}}\">{{event.name}}</a>&quot; has been reviewed and approved. It is now live and visible to members.\n\n<a href=\"{{{event.url}}}\">View your event &rarr;</a>", 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_plain_content' => __( "Great news — your event \"{{event.name}}\" has been reviewed and approved. It is now live and visible to members.\n\nTo view your event, visit:\n{{{event.url}}}", 'buddyboss' ),
				'situation_label'     => __( 'An organizer\'s submitted event is approved by an admin', 'buddyboss' ),
				'unsubscribe_text'    => __( 'You will no longer receive emails when your submitted event is approved.', 'buddyboss' ),
			),
			'bb_event_approved'
		);

		$this->register_notification(
			'events',
			'bb_event_approved',
			'bb_event_approved'
		);

		add_filter(
			'bp_events_bb_event_approved_notification',
			array( $this, 'bb_format_events_notification' ),
			10,
			7
		);
	}

	/**
	 * Register notification for event rejected.
	 *
	 * Triggered when an admin rejects a pending event submitted via the
	 * front-end submission form (Phase 7). The optional rejection reason
	 * is passed as a token when sending the email.
	 *
	 * @since BuddyBoss Events 1.0.0
	 */
	public function register_notification_for_event_rejected() {
		$this->register_notification_type(
			'bb_event_rejected',
			esc_html__( 'Your submitted event is not approved', 'buddyboss' ),
			esc_html__( 'An organizer\'s submitted event is rejected', 'buddyboss' ),
			'events'
		);

		$this->register_email_type(
			'events-rejected',
			array(
				/* translators: do not remove {} brackets or translate its contents. */
				'email_title'         => __( '[{{{site.name}}}] Your event "{{event.name}}" was not approved', 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_content'       => __( "Unfortunately your event &quot;{{event.name}}&quot; was reviewed and was not approved at this time.{{rejection.reason}}\n\nIf you believe this was a mistake or would like more information, please contact the site administrator.", 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_plain_content' => __( "Unfortunately your event \"{{event.name}}\" was reviewed and was not approved at this time.{{rejection.reason}}\n\nIf you believe this was a mistake or would like more information, please contact the site administrator.", 'buddyboss' ),
				'situation_label'     => __( 'An organizer\'s submitted event is rejected by an admin', 'buddyboss' ),
				'unsubscribe_text'    => __( 'You will no longer receive emails when your submitted event is not approved.', 'buddyboss' ),
			),
			'bb_event_rejected'
		);

		$this->register_notification(
			'events',
			'bb_event_rejected',
			'bb_event_rejected'
		);

		add_filter(
			'bp_events_bb_event_rejected_notification',
			array( $this, 'bb_format_events_notification' ),
			10,
			7
		);
	}

	// -------------------------------------------------------------------------
	// Notification bell formatting
	// -------------------------------------------------------------------------

	/**
	 * Format the notification content returned to get_notifications_for_user().
	 *
	 * This method is the abstract requirement from BP_Core_Notification_Abstract.
	 * Actual formatting is delegated to bb_format_events_notification() via the
	 * per-type action filters registered in load().
	 *
	 * @since BuddyBoss Events 1.0.0
	 *
	 * @param string $content               Default notification content.
	 * @param int    $item_id               Notification item ID (event post ID).
	 * @param int    $secondary_item_id     Secondary item ID (actor user ID).
	 * @param int    $total_items           Number of notifications with the same action.
	 * @param string $component_action_name Canonical notification action.
	 * @param string $component_name        Notification component ID.
	 * @param int    $notification_id       Notification ID.
	 * @param string $screen                Notification screen type.
	 *
	 * @return string|array
	 */
	public function format_notification( $content, $item_id, $secondary_item_id, $total_items, $component_action_name, $component_name, $notification_id, $screen ) {
		return $content;
	}

	/**
	 * Format events notification content for the notification bell and push.
	 *
	 * Handles all bb_event_* notification actions. The $item_id is the event
	 * post ID; $secondary_item_id is the actor's user ID where applicable.
	 *
	 * @since BuddyBoss Events 1.0.0
	 *
	 * @param string $content           Notification content.
	 * @param int    $item_id           Notification item ID (event post ID).
	 * @param int    $secondary_item_id Secondary item ID (actor user ID).
	 * @param int    $total_items       Total notifications with the same action.
	 * @param string $format            'string' or 'object'.
	 * @param int    $notification_id   Notification ID.
	 * @param string $screen            Screen type: 'web', 'web_push', 'mobile'.
	 *
	 * @return string|array
	 */
	public function bb_format_events_notification( $content, $item_id, $secondary_item_id, $total_items, $format, $notification_id, $screen ) {
		$notification = bp_notifications_get_notification( $notification_id );

		if ( empty( $notification ) || 'events' !== $notification->component_name ) {
			return $content;
		}

		$event_title = get_the_title( (int) $item_id );
		$event_url   = get_permalink( (int) $item_id );
		$actor_name  = $secondary_item_id ? bp_core_get_user_displayname( (int) $secondary_item_id ) : '';
		$amount      = 'single';

		switch ( $notification->component_action ) {

			case 'bb_event_waitlist_spot_open':
				if ( 'web_push' === $screen ) {
					$text              = esc_html__( 'A spot has opened for an event on your waitlist', 'buddyboss' );
					$notification_link = $event_url;
				} elseif ( (int) $total_items > 1 ) {
					$text = sprintf(
						/* translators: %d: number of events */
						esc_html__( 'Spots have opened on %d events you are waitlisted for', 'buddyboss' ),
						(int) $total_items
					);
					$notification_link = bp_loggedin_user_domain();
					$amount            = 'multiple';
				} else {
					$text = sprintf(
						/* translators: %s: event title */
						esc_html__( 'A spot has opened for "%s"', 'buddyboss' ),
						$event_title
					);
					$notification_link = $event_url;
				}
				break;

			case 'bb_event_member_invited':
				if ( 'web_push' === $screen ) {
					$text              = esc_html__( 'You have been invited to an event', 'buddyboss' );
					$notification_link = $event_url;
				} elseif ( (int) $total_items > 1 ) {
					$text = sprintf(
						/* translators: %d: number of event invitations */
						esc_html__( 'You have %d pending event invitations', 'buddyboss' ),
						(int) $total_items
					);
					$notification_link = bp_loggedin_user_domain();
					$amount            = 'multiple';
				} else {
					$text = $actor_name
						? sprintf(
							/* translators: 1: inviter name, 2: event title */
							esc_html__( '%1$s invited you to "%2$s"', 'buddyboss' ),
							$actor_name,
							$event_title
						)
						: sprintf(
							/* translators: %s: event title */
							esc_html__( 'You have been invited to "%s"', 'buddyboss' ),
							$event_title
						);
					$notification_link = $event_url;
				}
				break;

			case 'bb_event_rsvp_confirmed':
				if ( 'web_push' === $screen ) {
					$text              = esc_html__( 'Your RSVP has been confirmed', 'buddyboss' );
					$notification_link = $event_url;
				} else {
					$text = sprintf(
						/* translators: %s: event title */
						esc_html__( 'Your RSVP for "%s" is confirmed', 'buddyboss' ),
						$event_title
					);
					$notification_link = $event_url;
				}
				break;

			case 'bb_event_approved':
				if ( 'web_push' === $screen ) {
					$text              = esc_html__( 'Your event has been approved', 'buddyboss' );
					$notification_link = $event_url;
				} else {
					$text = sprintf(
						/* translators: %s: event title */
						esc_html__( 'Your event "%s" has been approved', 'buddyboss' ),
						$event_title
					);
					$notification_link = $event_url;
				}
				break;

			case 'bb_event_rejected':
				if ( 'web_push' === $screen ) {
					$text              = esc_html__( 'Your event was not approved', 'buddyboss' );
					$notification_link = bp_loggedin_user_domain();
				} else {
					$text = sprintf(
						/* translators: %s: event title */
						esc_html__( 'Your event "%s" was not approved', 'buddyboss' ),
						$event_title
					);
					$notification_link = bp_loggedin_user_domain();
				}
				break;

			default:
				return $content;
		}

		$content = apply_filters(
			'bb_events_' . $amount . '_' . $notification->component_action . '_notification',
			array(
				'link'  => $notification_link,
				'text'  => $text,
				'title' => $event_title,
				'image' => bb_notification_avatar_url( $notification ),
			),
			$notification,
			$text,
			$notification_link,
			$screen
		);

		// Normalise to string or array depending on $format.
		if (
			! empty( $content ) &&
			is_array( $content ) &&
			isset( $content['text'] ) &&
			isset( $content['link'] )
		) {
			if ( 'string' === $format ) {
				$content = empty( $content['link'] )
					? esc_html( $content['text'] )
					: '<a href="' . esc_url( $content['link'] ) . '">' . esc_html( $content['text'] ) . '</a>';
			} else {
				$content = array(
					'text'  => $content['text'],
					'link'  => $content['link'],
					'title' => isset( $content['title'] ) ? $content['title'] : '',
					'image' => isset( $content['image'] ) ? $content['image'] : '',
				);
			}
		}

		return $content;
	}
}

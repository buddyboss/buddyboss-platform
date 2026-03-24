<?php
/**
 * BuddyBoss Events Moderation Class.
 *
 * Registers 'events' as a reportable content type in the BuddyBoss moderation
 * system by extending BP_Moderation_Abstract.
 *
 * @package BuddyBoss\Events\Moderation
 * @since BuddyBoss Events 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BP_Moderation_Events' ) ) :

	/**
	 * Class BP_Moderation_Events
	 *
	 * Integrates events as a reportable content type in the BuddyBoss moderation
	 * queue. Extends BP_Moderation_Abstract following the same pattern as
	 * BP_Moderation_Groups in the BuddyBoss Platform.
	 *
	 * @since BuddyBoss Events 1.0.0
	 */
	class BP_Moderation_Events extends BP_Moderation_Abstract {

		/**
		 * The moderation item type identifier.
		 *
		 * @since BuddyBoss Events 1.0.0
		 *
		 * @var string
		 */
		public static $moderation_type = 'events';

		/**
		 * Constructor.
		 *
		 * Registers this moderation type with the parent class, wires the
		 * 'bp_moderation_content_types' filter, and conditionally registers the
		 * validation filter (skipped in admin non-AJAX and when reporting is disabled).
		 *
		 * @since BuddyBoss Events 1.0.0
		 */
		public function __construct() {
			parent::$moderation[ self::$moderation_type ] = self::class;
			$this->item_type                              = self::$moderation_type;

			add_filter( 'bp_moderation_content_types', array( $this, 'add_content_types' ) );

			if ( ( is_admin() && ! wp_doing_ajax() ) || self::admin_bypass_check() ) {
				return;
			}

			if ( ! bp_is_moderation_content_reporting_enable( 0, self::$moderation_type ) ) {
				return;
			}

			add_filter( "bp_moderation_{$this->item_type}_validate", array( $this, 'validate_single_item' ), 10, 2 );
		}

		/**
		 * Add 'events' to the list of reportable content types.
		 *
		 * @since BuddyBoss Events 1.0.0
		 *
		 * @param array $content_types Existing content types map.
		 * @return array Updated content types map with 'events' added.
		 */
		public function add_content_types( $content_types ) {
			$content_types[ self::$moderation_type ] = __( 'Events', 'buddyboss' );
			return $content_types;
		}

		/**
		 * Return the permalink for a reported event.
		 *
		 * @since BuddyBoss Events 1.0.0
		 *
		 * @param int $event_id The event ID.
		 * @return string Permalink URL.
		 */
		public static function get_permalink( $event_id ) {
			return bp_get_event_permalink( bp_events_get_event( $event_id ) );
		}

		/**
		 * Validate that a reported event still exists and is published.
		 *
		 * @since BuddyBoss Events 1.0.0
		 *
		 * @param bool $validated Current validation state.
		 * @param int  $event_id  The event ID being validated.
		 * @return bool True if the event exists and is published; false otherwise.
		 */
		public function validate_single_item( $validated, $event_id ) {
			$event = bp_events_get_event( $event_id );
			return ! empty( $event ) && 'published' === $event->status;
		}
	}

endif;

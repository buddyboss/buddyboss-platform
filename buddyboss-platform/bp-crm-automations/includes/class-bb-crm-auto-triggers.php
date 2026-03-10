<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Trigger registry — central catalogue of all available triggers.
 * Trigger group classes register themselves here on boot.
 */
class BB_CRM_Auto_Triggers {

	private static $triggers = array();

	/**
	 * Register a trigger definition.
	 *
	 * @param string $type     Unique key e.g. 'user_registered'
	 * @param array  $args {
	 *   @type string $label       Human-readable label.
	 *   @type string $category    Category key.
	 *   @type string $description Short description.
	 *   @type array  $fields      Optional extra config fields.
	 * }
	 */
	public static function register( $type, $args ) {
		self::$triggers[ $type ] = wp_parse_args( $args, array(
			'label'       => $type,
			'category'    => 'general',
			'description' => '',
			'fields'      => array(),
		) );
	}

	/** Return all registered triggers. */
	public static function get_all() {
		return self::$triggers;
	}

	/** Return triggers grouped by category. */
	public static function get_grouped() {
		$grouped = array();
		foreach ( self::$triggers as $type => $args ) {
			$grouped[ $args['category'] ][ $type ] = $args;
		}
		return $grouped;
	}

	/** Return a single trigger definition. */
	public static function get( $type ) {
		return self::$triggers[ $type ] ?? null;
	}

	/** Return category metadata — single source of truth for both PHP and JS. */
	public static function get_categories() {
		return array(
			'user'         => array(
				'label' => __( 'User & Member', 'buddyboss-crm-automations' ),
				'icon'  => 'admin-users',
				'color' => '#2271b1',
				'desc'  => __( 'Registration, login, roles and profile activity.', 'buddyboss-crm-automations' ),
			),
			'group'        => array(
				'label' => __( 'Groups', 'buddyboss-crm-automations' ),
				'icon'  => 'networking',
				'color' => '#00a0d2',
				'desc'  => __( 'Joining, leaving, roles and group content.', 'buddyboss-crm-automations' ),
			),
			'activity'     => array(
				'label' => __( 'Activity', 'buddyboss-crm-automations' ),
				'icon'  => 'activity',
				'color' => '#f56e28',
				'desc'  => __( 'Posts, comments, likes, reactions and media.', 'buddyboss-crm-automations' ),
			),
			'gamification' => array(
				'label' => __( 'Courses & Gamification', 'buddyboss-crm-automations' ),
				'icon'  => 'awards',
				'color' => '#9b59b6',
				'desc'  => __( 'LearnDash courses, GamiPress points and memberships.', 'buddyboss-crm-automations' ),
			),
			'profile'      => array(
				'label' => __( 'Profile & Social', 'buddyboss-crm-automations' ),
				'icon'  => 'id-alt',
				'color' => '#27ae60',
				'desc'  => __( 'Profile fields, follows, friendships and moderation.', 'buddyboss-crm-automations' ),
			),
			'tag'          => array(
				'label' => __( 'Tags, Lists & CRM', 'buddyboss-crm-automations' ),
				'icon'  => 'tag',
				'color' => '#c0392b',
				'desc'  => __( 'CRM tags, lists, email invites and forum events.', 'buddyboss-crm-automations' ),
			),
		);
	}

	/** Return triggers as a flat array for <select> options. */
	public static function get_for_select() {
		$categories = wp_list_pluck( self::get_categories(), 'label' );

		$options = array();
		foreach ( $categories as $cat_key => $cat_label ) {
			$group = array();
			foreach ( self::$triggers as $type => $args ) {
				if ( $args['category'] === $cat_key ) {
					$group[ $type ] = $args['label'];
				}
			}
			if ( ! empty( $group ) ) {
				$options[ $cat_label ] = $group;
			}
		}
		return $options;
	}
}

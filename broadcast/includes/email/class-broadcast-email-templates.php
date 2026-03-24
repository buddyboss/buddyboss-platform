<?php
/**
 * Broadcast Email Templates — runtime overlay for BuddyBoss email templates.
 *
 * Provides:
 * - apply_overlay() hooked to bp_send_email — injects custom subject/body
 *   into the BP_Email object AFTER BuddyBoss has loaded the CPT defaults but
 *   BEFORE delivery.  Overrides stored in the broadcast_email_overrides option
 *   survive bp_core_install_emails() ("Repair emails") because that operation
 *   only rewrites CPT posts, never WordPress options.
 * - get_template_list() — enumerates all bp-email CPT posts with type slugs
 *   and any saved overrides; used by the admin template list UI (TMPL-01).
 * - save_override() / delete_override() — CRUD for the option store (TMPL-02,
 *   TMPL-03).
 * - get_tokens() — surfaces available BP_Email_Tokens for the editor UI
 *   (TMPL-05).
 *
 * @package Broadcast
 */

defined( 'ABSPATH' ) || exit;

class Broadcast_Email_Templates {

	/**
	 * WordPress option key that stores all subject/body overrides.
	 * Keyed by BuddyBoss email type slug (e.g. 'activity-at-message').
	 */
	const OPTION_KEY = 'broadcast_email_overrides';

	/**
	 * Register the bp_send_email action hook.
	 * Call from Broadcast::init() or an equivalent boot point.
	 */
	public static function init() {
		add_action( 'bp_send_email', array( __CLASS__, 'apply_overlay' ), 10, 4 );
	}

	/**
	 * Apply any saved override to a BP_Email object before delivery.
	 *
	 * bp_send_email fires via do_action_ref_array(), so $email is passed by
	 * reference — mutations made here propagate back to the caller.
	 *
	 * @param BP_Email $email      The email object (passed by reference).
	 * @param string   $email_type The email type slug (e.g. 'activity-at-message').
	 * @param array    $to         Recipients array.
	 * @param array    $args       Additional arguments from bp_send_email().
	 */
	public static function apply_overlay( &$email, $email_type, $to, $args ) {
		$overrides = get_option( self::OPTION_KEY, array() );

		if ( empty( $overrides[ $email_type ] ) ) {
			return; // No override saved for this type — leave BuddyBoss default intact.
		}

		$override = $overrides[ $email_type ];

		if ( ! empty( $override['subject'] ) ) {
			$email->set_subject( $override['subject'] );
		}

		if ( ! empty( $override['body'] ) ) {
			$email->set_content_html( $override['body'] );
			$email->set_content_plaintext( wp_strip_all_tags( $override['body'] ) );
		}
	}

	/**
	 * Return a list of all BuddyBoss email types with their defaults and any
	 * saved overrides.
	 *
	 * Each entry contains:
	 *   'post_id'         int     — ID of the bp-email CPT post.
	 *   'slug'            string  — Email type slug from the bp-email-type taxonomy.
	 *   'default_subject' string  — Default subject (post_title).
	 *   'default_body'    string  — Default HTML body (post_content).
	 *   'current_subject' string  — Saved override subject, or '' if none.
	 *   'current_body'    string  — Saved override body, or '' if none.
	 *   'has_override'    bool    — True when any override is saved for this slug.
	 *
	 * @return array
	 */
	public static function get_template_list() {
		$posts = get_posts(
			array(
				'post_type'      => bp_get_email_post_type(),
				'post_status'    => 'publish',
				'numberposts'    => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'no_found_rows'  => true,
			)
		);

		$overrides = get_option( self::OPTION_KEY, array() );
		$templates = array();

		foreach ( $posts as $post ) {
			$terms = wp_get_object_terms( $post->ID, bp_get_email_tax_type() );
			$slug  = ( ! empty( $terms ) && ! is_wp_error( $terms ) ) ? $terms[0]->slug : '';

			$templates[] = array(
				'post_id'         => $post->ID,
				'slug'            => $slug,
				'default_subject' => $post->post_title,
				'default_body'    => $post->post_content,
				'current_subject' => $overrides[ $slug ]['subject'] ?? '',
				'current_body'    => $overrides[ $slug ]['body'] ?? '',
				'has_override'    => ! empty( $overrides[ $slug ] ),
			);
		}

		return $templates;
	}

	/**
	 * Save (or remove) a subject/body override for an email type slug.
	 *
	 * If both $subject and $body are empty the existing override is removed.
	 * Values are sanitised before storage:
	 *   - subject via sanitize_text_field()
	 *   - body    via wp_kses_post()
	 *
	 * @param string $slug    Email type slug (e.g. 'core-user-registration').
	 * @param string $subject Custom subject line, or '' to leave unchanged.
	 * @param string $body    Custom HTML body, or '' to leave unchanged.
	 */
	public static function save_override( string $slug, string $subject, string $body ) {
		$overrides = get_option( self::OPTION_KEY, array() );

		if ( '' === $subject && '' === $body ) {
			unset( $overrides[ $slug ] );
		} else {
			$overrides[ $slug ] = array(
				'subject' => sanitize_text_field( $subject ),
				'body'    => wp_kses_post( $body ),
			);
		}

		update_option( self::OPTION_KEY, $overrides );
	}

	/**
	 * Remove any saved override for an email type slug.
	 *
	 * @param string $slug Email type slug.
	 */
	public static function delete_override( string $slug ) {
		$overrides = get_option( self::OPTION_KEY, array() );
		unset( $overrides[ $slug ] );
		update_option( self::OPTION_KEY, $overrides );
	}

	/**
	 * Return the list of available email tokens from BuddyBoss.
	 *
	 * Proxies BP_Email_Tokens::get_tokens() when the class exists.
	 * Returns an empty array in environments where BuddyBoss is not active.
	 *
	 * @return array Token key => metadata array (typically includes 'description').
	 */
	public static function get_tokens() {
		if ( class_exists( 'BP_Email_Tokens' ) ) {
			$obj = new BP_Email_Tokens();
			return $obj->get_tokens();
		}

		return array();
	}
}

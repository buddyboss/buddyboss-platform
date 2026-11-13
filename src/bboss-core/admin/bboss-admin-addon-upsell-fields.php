<?php
/**
 * BuddyBoss Admin add-on upsell field helpers.
 *
 * Which Settings 2.0 features and fields belong to the features that moved
 * into the BuddyBoss Addons plugin, whether a real provider for such a field
 * is loaded on this site, and the 'start' tier default for their upsell modal.
 * Used by the Settings 2.0 AJAX handlers to gate those fields on their
 * provider (display and save), independent of the remote field-upgrades
 * catalog in bboss-admin-field-upgrades.php.
 *
 * @package BuddyBoss\Core\Administration
 * @since   BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Feature IDs whose upsell should point at the BuddyBoss Addons plan.
 *
 * These features moved out of BuddyBoss Platform Pro into the standalone
 * BuddyBoss Addons plugin, so — when they appear as a locked upsell (the
 * add-on plugin is not installed/licensed) — their modal CTA should read
 * "UPGRADE START" (tier 'start') rather than "UPGRADE LAUNCH".
 *
 * This is a LOCAL default used only until the remote field-upgrades catalog
 * carries the correct `upgrade_tier` for these features; a catalog-supplied
 * tier always wins (see bboss_admin_apply_addon_upsell_tier()).
 *
 * @since BuddyBoss 3.4.0
 *
 * @return string[] Filterable list of feature IDs.
 */
function bboss_admin_addon_upsell_feature_ids() {
	return apply_filters(
		'bb_admin_addon_upsell_feature_ids',
		array( 'reaction', 'reactions', 'polls', 'sso' )
	);
}

/**
 * Field names whose upsell should point at the BuddyBoss Addons plan.
 *
 * Some add-on features surface as a `pro_only` FIELD inside a feature that is
 * NOT itself an add-on feature — e.g. the "Enable Social Login" field
 * (`bb-social-login`) lives under the core `registration` feature. Matching on
 * the feature id alone (see bboss_admin_addon_upsell_feature_ids()) would miss
 * these fields, and re-tiering the whole host feature would be wrong, so they
 * are allow-listed here by field name instead.
 *
 * LOCAL default until the remote field-upgrades catalog carries the correct
 * `upgrade_tier` for these fields; a catalog-supplied tier always wins.
 *
 * @since BuddyBoss 3.4.0
 *
 * @return string[] Filterable list of field names.
 */
function bboss_admin_addon_upsell_field_names() {
	return apply_filters(
		'bb_admin_addon_upsell_field_names',
		array( 'bb-social-login', '_bb-sso-providers', '_bb_enable_activity_post_polls', '_bb_enable_activity_pinned_posts', 'bb_enable_group_subscriptions' )
	);
}

/**
 * Whether a moved-feature field actually has a working provider on this site.
 *
 * These fields are registered by Platform as `pro_only` placeholders, but the
 * code behind them now lives in the BuddyBoss Addons plugin. Availability must
 * therefore be decided by asking "is the feature present?", never by
 * `function_exists( 'bb_platform_pro' )` — Platform Pro 3.2.0+ no longer ships
 * Polls, Reactions or Social Login, so its mere presence would unlock a toggle
 * that saves happily and then does nothing.
 *
 * This is also why the fields cannot be resolved through
 * `bboss_admin_settings_get_pro_notice()`'s `type` argument: they live under
 * Platform's own `activity` and `registration` features, not under a `polls` or
 * `sso` feature, so a type-based lookup never sees them.
 *
 * Probes are deliberately CLASS-based wherever a class exists. Function names are
 * not safe probes here: both Platform and Platform Pro ship `_deprecated_function()`
 * shims under the moved features' old function names (`bb_load_polls()`,
 * `bb_enable_sso()`, `bboss_activity_pin_unpin_post()`, …) so that un-updated external
 * callers degrade instead of fatalling. Those shims make `function_exists()` return
 * true on a site with no provider at all. A deprecation shim never declares a class,
 * so `class_exists()` cannot be fooled the same way.
 *
 * Every probe is guarded so Platform never hard-depends on an optional plugin.
 *
 * @since BuddyBoss 3.4.0
 *
 * @param string $field_name Registered field name.
 * @return bool True when a real provider for that field's feature is loaded.
 */
function bboss_admin_addon_field_has_provider( $field_name ) {
	switch ( $field_name ) {
		case 'bb-social-login':
		case '_bb-sso-providers':
			// The add-on's SSO module and legacy Pro both instantiate BB_SSO at load.
			return class_exists( 'BB_SSO' );

		case '_bb_enable_activity_post_polls':
			// Both providers call BB_Polls::instance() at load.
			return class_exists( 'BB_Polls' );

		case '_bb_enable_activity_pinned_posts':
			// Pinned Posts has no class, so test the mutation function AND rule out
			// Platform's own no-op shim, which advertises itself via the marker
			// below. Correct at both call times: the add-on defines the real
			// function at bp_include:20, while the shim installs later at bp_init:1.
			return function_exists( 'bboss_activity_pin_unpin_post' )
				&& ! function_exists( 'bboss_activity_pin_unpin_post_is_stub' );

		case 'bb_enable_group_subscriptions':
			// Only the add-on's Group Subscription module defines the sender.
			return function_exists( 'bb_send_subscribed_group_notifications' );

		default:
			// Unknown field — a third party extended the allow-list via
			// `bb_admin_addon_upsell_field_names`. Assume a provider exists rather
			// than locking a field we know nothing about.
			return true;
	}
}

/**
 * Default an add-on feature's upsell modal to the 'start' tier.
 *
 * Only rewrites the tier when it is still the generic 'pro' fallback (or
 * empty), so an explicit catalog-supplied tier is never overridden. Matches
 * either by host feature id (add-on features) or by field name (add-on fields
 * embedded in a core feature, e.g. social login under registration).
 *
 * @since BuddyBoss 3.4.0
 *
 * @param array  $modal      Modal payload from bboss_field_upgrade_to_modal_payload().
 * @param string $feature_id The feature the modal belongs to.
 * @param string $field_name Optional. The field the modal belongs to.
 * @return array Possibly-adjusted modal payload.
 */
function bboss_admin_apply_addon_upsell_tier( $modal, $feature_id, $field_name = '' ) {
	if ( ! is_array( $modal ) ) {
		return $modal;
	}

	$is_addon_upsell =
		in_array( $feature_id, bboss_admin_addon_upsell_feature_ids(), true )
		|| ( '' !== $field_name && in_array( $field_name, bboss_admin_addon_upsell_field_names(), true ) );

	if ( $is_addon_upsell && ( empty( $modal['tier'] ) || 'pro' === $modal['tier'] ) ) {
		$modal['tier'] = 'start';
	}

	return $modal;
}

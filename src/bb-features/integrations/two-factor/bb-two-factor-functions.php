<?php
/**
 * Two-Factor integration public API.
 *
 * Every read of the Two Factor plugin goes through these wrappers. Several
 * Two_Factor_Core methods wp_die() or fatal for a member whose configured
 * providers no longer resolve, so no caller may reach the class directly.
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Features\Integrations\TwoFactor
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Whether the Two Factor plugin is active.
 *
 * Reads the active-plugins options rather than calling is_plugin_active(), which
 * lives in wp-admin/includes/plugin.php and is absent on the front end.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return bool True when the plugin is active for this site.
 */
function bb_two_factor_plugin_is_active() {
	$basename = bb_two_factor_plugin_basename();
	$plugins  = (array) get_option( 'active_plugins', array() );

	if ( in_array( $basename, $plugins, true ) ) {
		return true;
	}

	if ( is_multisite() ) {
		$network_plugins = (array) get_site_option( 'active_sitewide_plugins', array() );

		return isset( $network_plugins[ $basename ] );
	}

	return false;
}

/**
 * Get the version of the Two Factor plugin that is loaded.
 *
 * The constant is defined unconditionally when the plugin's main file runs, so
 * an empty return means the plugin has not loaded.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return string Version string, or an empty string when the plugin is absent.
 */
function bb_two_factor_plugin_version() {
	return defined( 'TWO_FACTOR_VERSION' ) ? (string) TWO_FACTOR_VERSION : '';
}

/**
 * Whether the Two Factor plugin is active, loaded and new enough to build on.
 *
 * The class check is required as well as the option check: the option can say
 * "active" before the plugin's own code has run.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return bool True when the integration can safely call into the plugin.
 */
function bb_two_factor_is_supported() {
	if ( ! bb_two_factor_plugin_is_active() || ! class_exists( 'Two_Factor_Core' ) ) {
		return false;
	}

	$version = bb_two_factor_plugin_version();

	if ( '' === $version ) {
		return false;
	}

	return version_compare( $version, bb_two_factor_min_plugin_version(), '>=' );
}

/**
 * Whether the Two-Factor feature is switched on in the admin.
 *
 * Reads `bb-active-features` directly rather than going through the Feature
 * Registry: this runs at bp_include, before feature discovery, where
 * BB_Feature_Registry::bb_is_feature_active() returns false for a feature it has
 * not registered yet. An absent key means on, matching reCAPTCHA and Reactions.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return bool True when the feature is enabled.
 */
function bb_two_factor_feature_is_on() {
	$active_features = bp_get_option( 'bb-active-features', array() );

	if ( ! is_array( $active_features ) || ! array_key_exists( 'two-factor', $active_features ) ) {
		$is_on = true;
	} else {
		$is_on = ! empty( $active_features['two-factor'] );
	}

	/**
	 * Filters whether the Two-Factor feature is enabled.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param bool $is_on Whether the feature is enabled.
	 */
	return (bool) apply_filters( 'bb_two_factor_is_enabled', $is_on );
}

/**
 * The guard every consumer of this integration uses.
 *
 * Never use bp_is_active( 'two-factor' ) or bb_add_action_if_active() instead:
 * both read `bp-active-components`, which the Integration Bridge writes only
 * after the first admin toggle, so a fresh install would read as off.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return bool True when the integration should do anything at all.
 */
function bb_two_factor_is_active() {
	return bb_two_factor_is_supported() && bb_two_factor_feature_is_on();
}

/**
 * URL of a member's Security tab.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param int $user_id Optional. Defaults to the displayed user, then the logged-in user.
 * @return string
 */
function bb_two_factor_get_settings_url( $user_id = 0 ) {
	if ( $user_id ) {
		$domain = bp_core_get_user_domain( $user_id );
	} elseif ( bp_displayed_user_domain() ) {
		$domain = bp_displayed_user_domain();
	} else {
		$domain = bp_loggedin_user_domain();
	}

	// An unresolved member would otherwise produce a relative path.
	if ( empty( $domain ) ) {
		return '';
	}

	return trailingslashit( $domain . bp_get_settings_slug() . '/security' );
}

/**
 * Get a member's unusable two-factor configuration, if they have one.
 *
 * Two_Factor_Core::get_available_providers_for_user() returns a WP_Error when the
 * member has enabled-provider meta, none of those providers are registered any
 * more, and Two_Factor_Email is unavailable as the fallback. The plugin's own
 * renderer then passes that WP_Error to array_keys() and to the wp_die() inside
 * get_primary_provider_for_user(), so this must be checked before rendering.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param int $user_id Member ID.
 * @return WP_Error|false The plugin's error, or false when the configuration resolves.
 */
function bb_two_factor_get_broken_config( $user_id ) {
	if ( ! bb_two_factor_is_active() ) {
		return false;
	}

	$available = Two_Factor_Core::get_available_providers_for_user( $user_id );

	return is_wp_error( $available ) ? $available : false;
}

/**
 * Whether the current user may change two-factor settings right now.
 *
 * Reimplements Two_Factor_Core::current_user_can_update_two_factor_options(),
 * which reaches get_primary_provider_for_user() and its wp_die(). The branch
 * order is kept identical, including that a non-two-factor session is refused
 * before the grace period is consulted.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $context 'display' or 'save'. Save has twice the grace time.
 * @return bool
 */
function bb_two_factor_current_user_can_manage( $context = 'display' ) {
	if ( ! is_user_logged_in() || ! bb_two_factor_is_active() ) {
		return false;
	}

	$user_id   = get_current_user_id();
	$available = Two_Factor_Core::get_available_providers_for_user( $user_id );

	// Not using two-factor, or a configuration that no longer resolves: nothing to revalidate against.
	if ( is_wp_error( $available ) || empty( $available ) ) {
		return true;
	}

	$last_validated = Two_Factor_Core::is_current_user_session_two_factor();

	if ( ! $last_validated ) {
		return false;
	}

	/** This filter is documented in two-factor/class-two-factor-core.php */
	$grace = (int) apply_filters( 'two_factor_revalidate_time', 10 * MINUTE_IN_SECONDS, $user_id, $context );

	if ( 'save' === $context ) {
		$grace *= 2;
	}

	// A falsey grace time is the plugin's documented way to disable revalidation.
	if ( ! $grace ) {
		return true;
	}

	return ( time() - (int) $last_validated ) <= $grace;
}

/**
 * Drain the plugin's private profile-error store into a WP_Error.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return WP_Error
 */
function bb_two_factor_drain_errors() {
	$errors = new WP_Error();

	if ( bb_two_factor_is_active() ) {
		Two_Factor_Core::action_user_profile_update_errors( $errors );
	}

	return $errors;
}

/**
 * Point the plugin's revalidation link back at the Security tab.
 *
 * Two_Factor_Core builds the link's redirect_to from get_user_settings_page_url(),
 * which is protected and unfiltered, so it always names a wp-admin screen. The
 * plugin then carries redirect_to through the whole round trip - hidden field on
 * the challenge form, then the login_redirect filter before wp_safe_redirect() -
 * so replacing it here is enough to land the member back on the tab.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $html       Markup from the plugin's options renderer.
 * @param string $return_url Where revalidation should return to.
 * @return string
 */
function bb_two_factor_set_revalidate_return( $html, $return_url ) {
	if ( '' === $return_url || false === strpos( $html, 'action=revalidate_2fa' ) ) {
		return $html;
	}

	$url = add_query_arg(
		'redirect_to',
		rawurlencode( $return_url ),
		Two_Factor_Core::get_user_two_factor_revalidate_url()
	);

	return (string) preg_replace_callback(
		'/href="[^"]*action=revalidate_2fa[^"]*"/i',
		static function () use ( $url ) {
			return 'href="' . esc_url( $url ) . '"';
		},
		$html
	);
}

/**
 * Render the plugin's own two-factor options for a member.
 *
 * Output is the plugin's wp-admin profile section verbatim, so every provider it
 * knows about - including ones from third-party plugins - appears unchanged.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param WP_User $user The member.
 */
function bb_two_factor_render_options( $user ) {
	if ( ! bb_two_factor_is_active() || ! ( $user instanceof WP_User ) ) {
		return;
	}

	if ( bb_two_factor_get_broken_config( $user->ID ) ) {
		return;
	}

	ob_start();
	Two_Factor_Core::user_two_factor_options( $user );
	$html = ob_get_clean();

	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup is produced and escaped by Two_Factor_Core.
	echo bb_two_factor_set_revalidate_return( $html, bb_two_factor_get_settings_url( $user->ID ) );
}

/**
 * Render the Security section.
 *
 * Used by both pack templates and by the bp_template_content fallback, so a theme
 * that overrides members/single/settings.php without a 'security' case still gets
 * the section through members/single/plugins.php.
 *
 * Restricted to the logged-in member's own profile. The sub-nav already enforces
 * this; repeating it here keeps the guarantee local to the markup that exposes
 * the settings.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_two_factor_render_section() {
	if ( ! bb_two_factor_is_active() || ! bp_is_my_profile() || bp_current_member_switched() ) {
		return;
	}

	$user = wp_get_current_user();

	if ( ! $user->exists() ) {
		return;
	}

	$broken = bb_two_factor_get_broken_config( $user->ID );

	if ( $broken ) {
		printf(
			'<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html( $broken->get_error_message() )
		);

		return;
	}
	?>
	<p class="info security-info"><?php esc_html_e( 'Add a second step to your sign-in so a stolen password is not enough to reach your account.', 'buddyboss' ); ?></p>

	<form action="<?php echo esc_url( bb_two_factor_get_settings_url() ); ?>" method="post" class="standard-form bb-two-factor-form" id="settings-form">

		<?php bb_two_factor_render_options( $user ); ?>

		<?php bp_nouveau_submit_button( 'bb-two-factor-settings' ); ?>

	</form>
	<?php
}

<?php
/**
 * Two-Factor integration class.
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Features\Integrations\TwoFactor
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * BuddyBoss Two-Factor integration.
 *
 * Passes the real plugin basename as required_plugin. Integrations that pass an
 * empty array are never activated, so the base class would not hook includes().
 *
 * @since BuddyBoss [BBVERSION]
 */
class BB_Two_Factor_Integration extends BP_Integration {

	/**
	 * Legacy integrations tab file. Empty: Settings 2.0 owns the admin UI.
	 *
	 * Declared because BP_Integration reads it on every
	 * bp_register_admin_integrations pass without declaring it.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var string
	 */
	public $admin_tab = '';

	/**
	 * Constructor.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function __construct() {
		$this->start(
			'two-factor',
			__( 'Two-Factor Authentication', 'buddyboss' ),
			'two-factor',
			array(
				'required_plugin' => bb_two_factor_plugin_basename(),
			)
		);
	}

	/**
	 * Directory of this integration.
	 *
	 * BP_Integration::start() resolves $this->path under bp-integrations/, which is
	 * the wrong tree for this integration.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return string
	 */
	private function get_integration_dir() {
		return trailingslashit( buddypress()->plugin_dir ) . 'bb-features/integrations/' . $this->id . '/';
	}

	/**
	 * Load the runtime files.
	 *
	 * Runs on bp_include @8 when is_activated() is true. The extra guard covers an
	 * unsupported plugin version and the feature toggle.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $includes Unused; signature inherited.
	 */
	public function includes( $includes = array() ) {
		if ( ! bb_two_factor_is_active() ) {
			return;
		}

		$dir = $this->get_integration_dir();

		require_once $dir . 'bb-two-factor-actions.php';
		require_once $dir . 'bb-two-factor-filters.php';
	}
}

<?php
/**
 * Two-Factor integration admin settings registration.
 *
 * Step 3 registers the side panel, sections and fields in the Feature Registry. Loaded
 * from bb-feature-config.php in admin, AJAX, REST and CLI contexts only, so the panel
 * exists even while the feature is switched off.
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Features\Integrations\TwoFactor
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

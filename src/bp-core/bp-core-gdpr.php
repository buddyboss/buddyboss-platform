<?php
/**
 * BuddyBoss Core Gdpr Loader.
 *
 * Core contains the commonly used functions, classes, and APIs.
 *
 * @package BuddyBoss
 * @subpackage Core
 * @since BuddyBoss 3.1.1
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the bp-core component.
 *
 * @since BuddyBoss 3.1.1
 */
function bp_setup_gdpr() {
    new BP_Core_Gdpr();
}
add_action( 'bp_loaded', 'bp_setup_gdpr', 0 );

<?php
/**
 * Two-Factor integration public API.
 *
 * Every read of the Two Factor plugin goes through the bb_two_factor_*() wrappers in
 * this file. Three of the plugin's own methods reach a wp_die() for a member whose
 * configured providers no longer resolve, so no direct Two_Factor_Core:: status call
 * may appear outside this file.
 *
 * Step 2 adds the state helpers; step 5 adds the per-user wrappers and memoization.
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Features\Integrations\TwoFactor
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

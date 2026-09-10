<?php
/**
 * Two-Factor integration class.
 *
 * Step 4 adds BB_Two_Factor_Integration extends BP_Integration, declaring the required
 * plugin basename so BP_Integration::is_activated() gates the runtime includes.
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Features\Integrations\TwoFactor
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

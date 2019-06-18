<?php
// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Remove buddypress follow init hook action
 */
remove_action( 'bp_include', 'bp_follow_init' );
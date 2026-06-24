<?php
/**
 * ReadyLaunch - The template for activity document preview main.
 *
 * @since   BuddyBoss 2.9.00
 * @package BuddyBoss\Core
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Doc/PDF/image extension files preview.
bp_get_template_part( 'document/doc-preview' );

// Audio extension files preview.
bp_get_template_part( 'document/audio-preview' );

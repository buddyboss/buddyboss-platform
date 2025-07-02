<?php
/**
 * Template for displaying content before single course in ReadyLaunch
 *
 * @since BuddyBoss 2.9.00
 * @package BuddyBoss\ReadyLaunch
 */

defined( 'ABSPATH' ) || exit;

// Add any content that should appear before the course content
echo '<div class="bb-rl-lifterlms-single-course-wrapper">';
echo '<div class="bb-rl-lifterlms-single-course-header">';
echo '<h1 class="bb-rl-lifterlms-course-title">' . get_the_title() . '</h1>';
echo '</div>';
?> 
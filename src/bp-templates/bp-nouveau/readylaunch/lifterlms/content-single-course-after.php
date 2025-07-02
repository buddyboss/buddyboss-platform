<?php
/**
 * Template for displaying content after single course in ReadyLaunch
 *
 * @since BuddyBoss 2.9.00
 * @package BuddyBoss\ReadyLaunch
 */

defined( 'ABSPATH' ) || exit;



// Close the wrapper div
echo '</div>'; // .bb-rl-lifterlms-single-course-wrapper

// Add any additional content after the course
echo '<div class="bb-rl-lifterlms-single-course-footer">';
echo '<div class="bb-rl-lifterlms-course-navigation">';
echo '<a href="' . esc_url( home_url( '/courses/' ) ) . '" class="bb-rl-lifterlms-back-to-courses">';
echo '<span class="bb-rl-lifterlms-back-arrow">←</span> ' . __( 'Back to Courses', 'buddyboss' );
echo '</a>';
echo '</div>';
echo '</div>';
?> 
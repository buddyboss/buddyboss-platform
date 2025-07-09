<?php
/**
 * LearnDash Registration Template for ReadyLaunch.
 *
 * This template is loaded via the ReadyLaunch layout system and provides
 * the registration form content for LearnDash courses.
 *
 * @since   BuddyBoss 2.9.00
 * @package BuddyBoss\Core
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Prevent infinite loops by checking if we're already processing this template.
static $processing_template = false;
if ( $processing_template ) {
	return;
}
$processing_template = true;

// Get course/group ID from URL parameters.
$ld_register_id = 0;
if ( isset( $_GET['ld_register_id'] ) ) {
	$ld_register_id = absint( $_GET['ld_register_id'] );
} elseif ( isset( $_GET['course_id'] ) ) {
	$ld_register_id = absint( $_GET['course_id'] );
} elseif ( isset( $_GET['group_id'] ) ) {
	$ld_register_id = absint( $_GET['group_id'] );
}

// Simple validation - just check if we have an ID
$is_valid = $ld_register_id > 0;

// If not valid, show error message
if ( ! $is_valid ) {
	echo '<div class="bb-rl-error">Invalid registration request.</div>';
	$processing_template = false;
	return;
}

// Get basic post info without triggering loops
$post_type = '';
$post_title = '';

// Use a simple query to get post info
$post_query = new WP_Query( array(
	'p' => $ld_register_id,
	'post_type' => array( 'sfwd-courses', 'groups' ),
	'posts_per_page' => 1,
	'no_found_rows' => true,
	'update_post_meta_cache' => false,
	'update_post_term_cache' => false,
) );

if ( $post_query->have_posts() ) {
	$post_query->the_post();
	$post_type = get_post_type();
	$post_title = get_the_title();
	wp_reset_postdata();
} else {
	echo '<div class="bb-rl-error">Course or group not found.</div>';
	$processing_template = false;
	return;
}
?>

<div class="bb-learndash-content-wrap">
	<article id="post-<?php echo esc_attr( $ld_register_id ); ?>" class="bb-rl-learndash-registration bb-rl-lms-registration bb-rl-lms-course">
		<div class="bb-rl-lms-inner-block">
			<header class="bb-rl-heading">
				<div class="bb-rl-registration-overview">
					<h1 class="bb-rl-entry-title">
						<?php 
						if ( 'sfwd-courses' === $post_type ) {
							printf( 
								/* translators: %s: Course title */
								esc_html__( 'Register for %s', 'buddyboss' ), 
								esc_html( $post_title ) 
							);
						} else {
							printf( 
								/* translators: %s: Group title */
								esc_html__( 'Join %s', 'buddyboss' ), 
								esc_html( $post_title ) 
							);
						}
						?>
					</h1>
					<div class="bb-rl-registration-description">
						<?php 
						if ( 'sfwd-courses' === $post_type ) {
							esc_html_e( 'Complete the registration form below to enroll in this course.', 'buddyboss' );
						} else {
							esc_html_e( 'Complete the registration form below to join this group.', 'buddyboss' );
						}
						?>
					</div>
				</div>
			</header>

			<div class="bb-rl-registration-content">
				<div class="bb-rl-registration-content-inner">
					<div class="bb-rl-entry-content">
						<div class="learndash-wrapper">
							<?php
							// Load the LearnDash registration form
							if ( ! is_user_logged_in() ) {
								// Call the original LearnDash registration function directly
								if ( function_exists( 'learndash_registration_output' ) ) {
									// Temporarily remove our shortcode override to avoid loops
									remove_shortcode( 'ld_registration' );
									
									// Call the original function
									learndash_registration_output( array( 'course_id' => $ld_register_id ) );
									
									// Re-add our shortcode override
									add_shortcode( 'ld_registration', array( 'BB_Readylaunch_Learndash_Helper', 'bb_rl_learndash_registration_shortcode' ) );
								} else {
									// Fallback to login form
									wp_login_form( array(
										'redirect' => add_query_arg( array( 'ld_register_id' => $ld_register_id ), home_url() ),
									) );
								}
							} else {
								echo '<div class="bb-rl-registration-form">';
								echo '<p>' . esc_html__( 'You are already logged in. Please contact the administrator for enrollment.', 'buddyboss' ) . '</p>';
								echo '</div>';
							}
							?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</article>
</div>

<?php
$processing_template = false;
?> 
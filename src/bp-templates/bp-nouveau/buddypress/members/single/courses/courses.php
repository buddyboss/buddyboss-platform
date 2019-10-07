<?php
/**
 * @package WordPress
 * @subpackage BuddyPress for LearnDash
 */
?>
<?php
$filepath = locate_template(
	array(
		'learndash/learndash_template_script.min.js',
		'learndash/learndash_template_script.js',
		'learndash_template_script.min.js',
		'learndash_template_script.js'
	)
);

$view = llms_page_display();

if ( !empty( $filepath ) ) {
	wp_enqueue_script( 'learndash_template_script_js', str_replace( ABSPATH, '/', $filepath ), array( 'jquery' ), LEARNDASH_VERSION, true );
	$learndash_assets_loaded['scripts']['learndash_template_script_js'] = __FUNCTION__;
} else if ( file_exists( LEARNDASH_LMS_PLUGIN_DIR .'/templates/learndash_template_script'. ( ( defined( 'LEARNDASH_SCRIPT_DEBUG' ) && ( LEARNDASH_SCRIPT_DEBUG === true ) ) ? '' : '.min') .'.js' ) ) {
	wp_enqueue_script( 'learndash_template_script_js', LEARNDASH_LMS_PLUGIN_URL . 'templates/learndash_template_script'. ( ( defined( 'LEARNDASH_SCRIPT_DEBUG' ) && ( LEARNDASH_SCRIPT_DEBUG === true ) ) ? '' : '.min') .'.js', array( 'jquery' ), LEARNDASH_VERSION, true );
	$learndash_assets_loaded['scripts']['learndash_template_script_js'] = __FUNCTION__;

	$data = array();
	$data['ajaxurl'] = admin_url('admin-ajax.php');
	$data = array( 'json' => json_encode( $data ) );
	wp_localize_script( 'learndash_template_script_js', 'sfwd_data', $data );

}

//LD_QuizPro::showModalWindow();
add_action( 'wp_footer', array( 'LD_QuizPro', 'showModalWindow' ), 20 );
?>

<?php
$user_id            = bp_displayed_user_id();
$defaults = array(
	'user_id'				=>	get_current_user_id(),
	'per_page'				=>	false,
	'order' 				=> 'DESC',
	'orderby' 				=> 'ID',
	'course_points_user' 	=> 'yes',
	'expand_all'			=> false
);
$atts               = apply_filters( 'bp_learndash_user_courses_atts', $defaults );
$atts = wp_parse_args( $atts, $defaults );
if ( $atts['per_page'] === false ) {
	$atts['per_page'] = $atts['quiz_num'] = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Per_Page', 'per_page' );
} else {
	$atts['per_page'] = intval( $atts['per_page'] );
}

if ( $atts['per_page'] > 0 ) {
	$atts['paged'] = 1;
} else {
	unset( $atts['paged'] );
	$atts['nopaging'] = true;
}
$user_courses       = apply_filters( 'bp_learndash_user_courses', ld_get_mycourses( $user_id,  $atts ) );
$usermeta           = get_user_meta( $user_id, '_sfwd-quizzes', true );
$quiz_attempts_meta = empty($usermeta) ?  false : $usermeta;
$quiz_attempts      = array();


$profile_pager = array();

if ( ( isset( $atts['per_page'] ) ) && ( intval( $atts['per_page'] ) > 0 ) ) {
	$atts['per_page'] = intval( $atts['per_page'] );

	//$paged = get_query_var( 'page', 1 );
	//error_log('paged['. $paged .']');

	if ( ( isset( $_GET['ld-profile-page'] ) ) && ( !empty( $_GET['ld-profile-page'] ) ) ) {
		$profile_pager['paged'] = intval( $_GET['ld-profile-page'] );
	} else {
		$profile_pager['paged'] = 1;
	}

	$profile_pager['total_items'] = count( $user_courses );
	$profile_pager['total_pages'] = ceil( count( $user_courses ) / $atts['per_page'] );

	$user_courses = array_slice ( $user_courses, ( $profile_pager['paged'] * $atts['per_page'] ) - $atts['per_page'], $atts['per_page'], false );
}

if(!empty($quiz_attempts_meta)){
	foreach($quiz_attempts_meta as $quiz_attempt) {
		$c = learndash_certificate_details($quiz_attempt['quiz'], $user_id);
		$quiz_attempt['post'] = get_post( $quiz_attempt['quiz'] );
		$quiz_attempt["percentage"]  = !empty($quiz_attempt["percentage"])? $quiz_attempt["percentage"]:(!empty($quiz_attempt["count"])? $quiz_attempt["score"]*100/$quiz_attempt["count"]:0  );

		if($user_id == get_current_user_id() && !empty($c["certificateLink"]) && ((isset($quiz_attempt['percentage']) && $quiz_attempt['percentage'] >= $c["certificate_threshold"] * 100)))
			$quiz_attempt['certificate'] = $c;
		$quiz_attempts[learndash_get_course_id($quiz_attempt['quiz'])][] = $quiz_attempt;
	}
}
?>

<div id="bb-learndash_profile" class="<?php echo empty( $user_courses ) ? 'user-has-no-lessons' : '';  ?>">
    <div id="learndash-content" class="learndash-course-list">
        <form id="bb-courses-directory-form" class="bb-courses-directory" method="get" action="">
            <div class="flex align-items-center bb-courses-header">

                <div id="courses-dir-search" class="bs-dir-search" role="search"></div>

                <div class="bb-secondary-list-tabs flex align-items-center" id="subnav"
                     aria-label="Members directory secondary navigation" role="navigation">
					<?php
					if ( function_exists( 'bp_get_template_part' ) ) {
						bp_get_template_part( 'common/filters/grid-filters' );
					}
					?>
                </div>
            </div>
            <div class="grid-view bb-grid">
                <div id="course-dir-list" class="course-dir-list bs-dir-list">
					<?php if ( ! empty( $user_courses ) ) : ?>
						<?php
						global $post;
						$_post = $post;
						?>
                        <ul class="bb-course-list bb-course-items bb-grid list-view <?php echo 'list' != $view ? 'hide' : '';?>" aria-live="assertive"
                            aria-relevant="all">
							<?php foreach ( $user_courses as $course_id ) : ?>
								<?php
								$course = get_post( $course_id);
								$post = $course;
								get_template_part( 'learndash/ld30/template-course-item' );
							endforeach;
							?></ul>
                        <ul class="bb-card-list bb-course-items grid-view bb-grid <?php echo 'grid' != $view ? 'hide' : '';?>" aria-live="assertive"
                            aria-relevant="all"><?php
						foreach ( $user_courses as $course_id ) : ?>
							<?php
							$course = get_post( $course_id);
							$post = $course;
							get_template_part( 'learndash/ld30/template-course-item' );
						endforeach;
						?></ul><?php
						$post = $_post;
						?>
					<?php endif; ?>
					<?php
					echo SFWD_LMS::get_template(
						'learndash_pager.php',
						array(
							'pager_results' => $profile_pager,
							'pager_context' => 'profile'
						)
					);
					?>
                </div>
            </div>
        </form>
    </div>
</div>

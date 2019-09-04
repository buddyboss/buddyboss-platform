<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

function bp_learndash_courses_page(){
    add_action( 'bp_template_title', 'bp_learndash_courses_expand' );
    add_action( 'bp_template_title', 'bp_learndash_courses_page_title' );
    add_action( 'bp_template_content', 'bp_learndash_courses_page_content' );
    bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
}

function bp_learndash_courses_expand(){
    ?>
    <div class="expand_collapse"><a href="#" onClick="return flip_expand_all('#course_list');"><?php _e('Expand All', 'buddypress-learndash'); ?></a> <span class="sep"><?php _e('/', 'buddypress-learndash'); ?></span> <a href="#" onClick="return flip_collapse_all('#course_list');"><?php _e('Collapse All','buddypress-learndash'); ?></a></div>
    <?php 
}

function bp_learndash_courses_page_title(){
    $title = sprintf( __( 'Registered %s', 'buddypress-learndash' ), LearnDash_Custom_Label::get_label( 'course' ) );
	echo apply_filters( 'bp_learndash_courses_page_title',$title );
}

function bp_learndash_courses_page_content(){

    do_action('template_notices');

    do_action( 'bp_learndash_before_courses_page_content' );

    bp_learndash_load_template( 'courses' );
}

/**
 * When on Course Group, the 'Course' nav should go back to the course NOT group.
 */
function bp_learndash_courses_forum_alter_nav(){
    if( !function_exists( 'buddypress' ) || !bp_is_active( 'groups' ) )
        return;

    /**
     * Filter on Course Group, the 'Course' nav should go back to the course NOT group.
     * @since 1.0.7
     */
    $group_experiences_redirect_single_course = apply_filters( 'group_experiences_redirect_single_course', false );

    if( bp_current_component()=='groups' && $group_experiences_redirect_single_course ){
        if( ( $course_id = groups_get_groupmeta( bp_get_group_id(), 'bp_course_attached', true ) ) != false ){
            ?>
            <script type='text/javascript'>
                jQuery('document').ready(function($){
                    $('#nav-experiences-groups-li > a').attr( 'href', '<?php echo get_permalink( $course_id );?>' );
                });
            </script>
            <?php 
        }
    }
}
add_action( 'wp_footer', 'bp_learndash_courses_forum_alter_nav' );

/**
 * groups>experiences screen - redirect to single course screen
 */
function bp_learndash_redirect_group_course_tab(){
    if( is_admin() || !function_exists( 'buddypress' ) || !bp_is_active( 'groups' ) ){
        return;
    }

    if( bp_current_component()=='groups' && bp_current_action()=='experiences' ){
        if( ( $course_id = groups_get_groupmeta( bp_get_group_id(), 'bp_course_attached', true ) ) != false ){
            wp_redirect( get_permalink( $course_id ) );
            exit();
        }
    }
}
//add_action( 'bp_template_redirect', 'bp_learndash_redirect_group_course_tab', 12 );

/**
 * Detach group from the course when group is deleted
 *
 * @param $group_id
 */
function bp_learndash_detach_course_group( $group_id ) {
    $course_attached = groups_get_groupmeta( $group_id, 'bp_course_attached', true );
    if ($course_attached)
        delete_post_meta( (int) $course_attached, 'bp_course_group' );
}

add_action( 'groups_before_delete_group', 'bp_learndash_detach_course_group', 10, 1 );
<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

function bp_learndash_load_template($template){
    $template .= '.php';
    if(file_exists(STYLESHEETPATH.'/bp-learndash/'.$template))
        include_once(STYLESHEETPATH.'/bp-learndash/'.$template);
    else if(file_exists(TEMPLATEPATH.'/bp-learndash/'.$template))
        include_once (TEMPLATEPATH.'/bp-learndash/'.$template);
    else{
        $template_dir = apply_filters('bp_learndash_templates_dir_filter', buddypress_learndash()->templates_dir, $template );
        include_once trailingslashit($template_dir) . $template;
    }
}

function bp_learndash_buffer_template_part( $template, $echo=true ){
    ob_start();

    bp_learndash_load_template( $template );
    // Get the output buffer contents
    $output = ob_get_clean();

    // Echo or return the output buffer contents
    if ( true === $echo ) {
        echo $output;
    } else {
        return $output;
    }
}

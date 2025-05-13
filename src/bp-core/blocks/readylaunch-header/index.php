<?php
/**
 * Functions related to the Ready Launch Header block.
 *
 * @package BuddyBoss\Blocks
 * @since 2.8.30
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register the Ready Launch Header block.
 *
 * @since 2.8.30
 */
function bp_register_readylaunch_header_block() {
    register_block_type(
        __DIR__,
        array(
            'render_callback' => 'bp_block_render_readylaunch_header_block',
        )
    );
}
add_action( 'init', 'bp_register_readylaunch_header_block' );

/**
 * Callback function to render the Ready Launch Header block.
 *
 * @since 2.8.30
 *
 * @param array $attributes The block attributes.
 *
 * @return string HTML output.
 */
function bp_block_render_readylaunch_header_block( $attributes ) {
    $header_text = isset( $attributes['headerText'] ) ? $attributes['headerText'] : '';
    
    $block_output = '<div class="bb-readylaunch-header">';
    $block_output .= '<h2>' . esc_html( $header_text ) . '</h2>';
    $block_output .= '</div>';
    
    return $block_output;
} 
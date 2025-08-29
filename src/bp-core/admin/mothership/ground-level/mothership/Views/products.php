<?php
/**
 * Products/Add-ons view template.
 *
 * @package BuddyBoss
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Add refresh button
echo '<div class="mosh-addons-header">';
echo '<h3>' . esc_html__( 'Available Add-ons', 'buddyboss' ) . '</h3>';
echo '<form method="post" style="display: inline;">';
echo '<input type="submit" name="submit-button-mosh-refresh-addon" class="button button-secondary" value="' . esc_attr__( 'Refresh Add-ons', 'buddyboss' ) . '">';
echo '</form>';
echo '</div>';

if ( empty( $products ) ) {
    echo '<div class="notice notice-info"><p>' . esc_html__( 'No add-ons available.', 'buddyboss' ) . '</p></div>';
    return;
}

echo '<div class="mosh-addons-grid">';
foreach ( $products as $product ) {
    $plugin_file = $product->main_file ?? '';
    $is_installed = file_exists( WP_PLUGIN_DIR . '/' . $plugin_file );
    $is_active = is_plugin_active( $plugin_file );
    
    echo '<div class="mosh-addon-item">';
    echo '<h3>' . esc_html( $product->title ?? '' ) . '</h3>';
    echo '<p>' . esc_html( $product->description ?? '' ) . '</p>';
    
    if ( $is_installed ) {
        if ( $is_active ) {
            echo '<button class="button button-secondary mosh-deactivate-addon" data-plugin="' . esc_attr( $plugin_file ) . '">' . esc_html__( 'Deactivate', 'buddyboss' ) . '</button>';
        } else {
            echo '<button class="button button-primary mosh-activate-addon" data-plugin="' . esc_attr( $plugin_file ) . '">' . esc_html__( 'Activate', 'buddyboss' ) . '</button>';
        }
    } else {
        echo '<button class="button button-primary mosh-install-addon" data-plugin="' . esc_attr( $product->_embedded->{'version-latest'}->url ?? '' ) . '">' . esc_html__( 'Install', 'buddyboss' ) . '</button>';
    }
    
    echo '</div>';
}
echo '</div>';
?>

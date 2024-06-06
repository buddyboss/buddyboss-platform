<?php
/*
   Plugin Name: WP Performance Tester
   Plugin URI: https://wordpress.org/plugins/wpperformancetester/
   Version: 2.0.0
   Author: <a href="https://reviewsignal.com">Kevin Ohashi</a>
   Description: Tests WordPress Performance
   Text Domain: wp-performance-tester
   License: GPLv3
  */

/*
    "WordPress Plugin Template" Copyright (C) 2015 Michael Simpson  (email : michael.d.simpson@gmail.com)

    This following part of this file is part of WordPress Plugin Template for WordPress.

    WordPress Plugin Template is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    WordPress Plugin Template is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Contact Form to Database Extension.
    If not, see http://www.gnu.org/licenses/gpl-3.0.html
*/

$WPPerformanceTester_minimalRequiredPhpVersion = '5.0';

/**
 * Check the PHP version and give a useful error message if the user's version is less than the required version
 * @return boolean true if version check passed. If false, triggers an error which WP will handle, by displaying
 * an error message on the Admin page
 */
function WPPerformanceTester_noticePhpVersionWrong() {
    global $WPPerformanceTester_minimalRequiredPhpVersion;
    echo '<div class="updated fade">' .
      esc_html__('Error: plugin "WP Performance Tester" requires a newer version of PHP to be running.',  'wp-performance-tester').
            '<br/>' . esc_html__('Minimal version of PHP required: ', 'wp-performance-tester') . '<strong>' . esc_html( $WPPerformanceTester_minimalRequiredPhpVersion ) . '</strong>' .
            '<br/>' . esc_html__('Your server\'s PHP version: ', 'wp-performance-tester') . '<strong>' . esc_html( phpversion() ) . '</strong>' .
         '</div>';
}


function WPPerformanceTester_PhpVersionCheck() {
    global $WPPerformanceTester_minimalRequiredPhpVersion;
    if (version_compare(phpversion(), $WPPerformanceTester_minimalRequiredPhpVersion) < 0) {
        add_action('admin_notices', 'WPPerformanceTester_noticePhpVersionWrong');
        return false;
    }
    return true;
}


/**
 * Initialize internationalization (i18n) for this plugin.
 * References:
 *      http://codex.wordpress.org/I18n_for_WordPress_Developers
 *      http://www.wdmac.com/how-to-create-a-po-language-translation#more-631
 * @return void
 */
function WPPerformanceTester_i18n_init() {
    $pluginDir = dirname(plugin_basename(__FILE__));
    load_plugin_textdomain('wp-performance-tester', false, $pluginDir . '/languages/');
}


//////////////////////////////////
// Run initialization
/////////////////////////////////

// First initialize i18n
WPPerformanceTester_i18n_init();


// Next, run the version check.
// If it is successful, continue with initialization for this plugin
if ( WPPerformanceTester_PhpVersionCheck() ) {
    // Only load and run the init function if we know PHP version can parse it
    require_once( 'wp-performance-tester_init.php' );
    WPPerformanceTester_init( __FILE__ );
}

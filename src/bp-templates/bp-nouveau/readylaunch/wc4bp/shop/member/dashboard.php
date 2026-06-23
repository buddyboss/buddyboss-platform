<?php
/**
 * ReadyLaunch WC4BP Dashboard Template
 *
 * This template is a Readylaunch-specific version of the WC4BP dashboard template
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch\WC4BP
 * @since BuddyBoss [BBVERSION]
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="woocommerce woocommerce--bb-rl">
	<p>
		<?php
		$dashboard_desc = __( 'On this page you can find all the content added by third-party plugins within WooCommerce. In case the tab you want is not shown, we recommend going to <strong>WooCommerce - Settings - Advanced</strong> and checking the configuration of the endpoints', 'buddyboss' );
		echo wp_kses( $dashboard_desc, array( 'strong' => array() ) );
		?>
	</p>

	<?php
	/**
	 * My Account dashboard.
	 *
	 * @since 2.6.0
	 */
	do_action( 'woocommerce_account_dashboard' );

	/**
	 * Deprecated woocommerce_before_my_account action.
	 *
	 * @deprecated 2.6.0
	 */
	do_action( 'woocommerce_before_my_account' );

	/**
	 * Deprecated woocommerce_after_my_account action.
	 *
	 * @deprecated 2.6.0
	 */
	do_action( 'woocommerce_after_my_account' );
	?>
</div>


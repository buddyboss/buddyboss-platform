<?php
/**
 * BuddyBoss Platform Mothership Admin Template
 *
 * @package BuddyBoss Platform
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="wrap">
	<h1><?php esc_html_e( 'BuddyBoss Platform License', 'buddyboss' ); ?></h1>

	<?php // BB_Mothership_Admin::instance()->print_settings_tabs(); ?>

	<div class="bb-platform-mothership-content">
		<?php BB_Mothership_Admin::instance()->print_settings_content(); ?>
	</div>
</div>

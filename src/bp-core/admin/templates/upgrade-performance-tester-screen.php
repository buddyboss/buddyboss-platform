<?php
/**
 * BuddyBoss Performance Tester Admin Screen.
 *
 * @since BuddyBoss 2.6.30
 *
 * @package BuddyBoss
 */

$bb_wpt = bb_web_performance_tester();
?>

<div class="advance-performance-wrapper">
	<div id="bb-upgrade">
		<a href="
		<?php
		echo esc_url(
			bp_get_admin_url(
				add_query_arg(
					array(
						'page' => 'bb-upgrade',
						'tab'  => 'bb-upgrade',
					),
					'admin.php'
				)
			)
		);
		?>
		" class="advance-action-link advance-action-link--back"><i class="bb-icon-l bb-icon-arrow-left"></i><?php esc_html_e( 'Go back', 'buddyboss' ); ?></a>
		<?php $bb_wpt->settings_page(); ?>
	</div>
</div>

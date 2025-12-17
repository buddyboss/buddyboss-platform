<?php
/**
 * The footer for ReadyLaunch.
 *
 * This template handles the footer section for the ReadyLaunch theme.
 * It includes closing HTML tags and WordPress footer hooks.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
	</div> <!-- .bb-rl-container -->
</main>
<?php
	do_action( 'bb_rl_footer' );
	wp_footer();

?>
</body>

</html>

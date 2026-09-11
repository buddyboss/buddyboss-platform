<?php
/**
 * BuddyBoss - Members Settings ( Security )
 *
 * Renders the Two Factor plugin's own two-factor options for the displayed member.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau
 * @since BuddyBoss [BBVERSION]
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

bp_nouveau_member_hook( 'before', 'settings_template' );
?>

<h2 class="screen-heading security-settings-screen"><?php esc_html_e( 'Security', 'buddyboss' ); ?></h2>
<?php
if ( function_exists( 'bb_two_factor_render_section' ) ) {
	bb_two_factor_render_section();
}
?>

<?php
bp_nouveau_member_hook( 'after', 'settings_template' );

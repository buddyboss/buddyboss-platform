<?php
/**
 * ReadyLaunch - Member Settings Security template.
 *
 * Renders the Two Factor plugin's own two-factor options for the displayed member.
 *
 * @package BuddyBoss\Template
 * @subpackage BBoss_Nouveau\ReadyLaunch
 * @since BuddyBoss 3.6.0
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

bboss_nouveau_member_hook( 'before', 'settings_template' );
?>

<h2 class="screen-heading security-settings-screen"><?php esc_html_e( 'Security', 'buddyboss' ); ?></h2>
<?php
if ( function_exists( 'bboss_two_factor_render_section' ) ) {
	bboss_two_factor_render_section();
}
?>

<?php
bboss_nouveau_member_hook( 'after', 'settings_template' );

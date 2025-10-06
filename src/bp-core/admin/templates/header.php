<?php
/**
 * BuddyBoss Header Template.
 *
 * @package BuddyBoss
 *
 * @since 2.14.0
 */

defined( 'ABSPATH' ) || exit;

?>
<div class="bb-tab-header">
	<div class="bb-branding-header">
		<img alt="" class="bb-branding-logo" src="<?php echo esc_url( buddypress()->plugin_url . 'bp-core/images/admin/BBLogo.png' ); ?>" />
	</div>
	<div class="bb-header-actions">
		<?php do_action( 'bb_admin_header_actions' ); ?>
	</div>
</div>

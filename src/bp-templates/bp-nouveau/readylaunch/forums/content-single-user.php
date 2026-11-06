<?php
/**
 * Single User Content Template
 *
 * @package BuddyBoss\Template
 * @subpackage BBoss_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>

<div id="bbpress-forums">

	<?php do_action( 'bbp_template_notices' ); ?>

	<div id="bbp-user-wrapper">
		<?php bboss_bbp_get_template_part( 'user', 'details' ); ?>

		<div id="bbp-user-body">
			<?php
			if ( bboss_bbp_is_favorites() ) {
				bboss_bbp_get_template_part( 'user', 'favorites' );}
			?>
			<?php
			if ( bboss_bbp_is_subscriptions() ) {
				bboss_bbp_get_template_part( 'user', 'subscriptions' );}
			?>
			<?php
			if ( bboss_bbp_is_single_user_topics() ) {
				bboss_bbp_get_template_part( 'user', 'topics-created' );}
			?>
			<?php
			if ( bboss_bbp_is_single_user_replies() ) {
				bboss_bbp_get_template_part( 'user', 'replies-created' );}
			?>
			<?php
			if ( bboss_bbp_is_single_user_edit() ) {
				bboss_bbp_get_template_part( 'form', 'user-edit' );}
			?>
			<?php
			if ( bboss_bbp_is_single_user_profile() ) {
				bboss_bbp_get_template_part( 'user', 'profile' );}
			?>
		</div>
	</div>
</div>

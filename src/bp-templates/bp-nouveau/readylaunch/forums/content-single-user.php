<?php
/**
 * Single User Content Template
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>

<div id="bbpress-forums">

	<?php do_action( 'bbp_template_notices' ); ?>

	<div id="bbp-user-wrapper">
		<?php bbp_get_template_part( 'user', 'details' ); ?>

		<div id="bbp-user-body">
			<?php
			if ( bbp_is_favorites() ) {
				bbp_get_template_part( 'user', 'favorites' );}
			?>
			<?php
			if ( bbp_is_subscriptions() ) {
				bbp_get_template_part( 'user', 'subscriptions' );}
			?>
			<?php
			if ( bbp_is_single_user_topics() ) {
				bbp_get_template_part( 'user', 'topics-created' );}
			?>
			<?php
			if ( bbp_is_single_user_replies() ) {
				bbp_get_template_part( 'user', 'replies-created' );}
			?>
			<?php
			if ( bbp_is_single_user_edit() ) {
				bbp_get_template_part( 'form', 'user-edit' );}
			?>
			<?php
			if ( bbp_is_single_user_profile() ) {
				bbp_get_template_part( 'user', 'profile' );}
			?>
		</div>
	</div>
</div>

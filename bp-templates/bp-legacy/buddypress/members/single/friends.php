<?php
/**
 * BuddyBoss - Users Friends
 *
 * @package BuddyBoss
 * @subpackage bp-legacy
 * @version 3.0.0
 */

?>

<div class="item-list-tabs no-ajax" id="subnav" aria-label="<?php esc_attr_e( 'Member secondary navigation', 'buddyboss' ); ?>" role="navigation">
	<ul>
		<?php if ( bp_is_my_profile() ) bp_get_options_nav(); ?>

		<?php if ( !bp_is_current_action( 'requests' ) ) : ?>

			<li id="members-order-select" class="last filter">

				<label for="members-friends"><?php _e( 'Order By:', 'buddyboss' ); ?></label>
				<select id="members-friends">
					<option value="active"><?php _e( 'Last Active', 'buddyboss' ); ?></option>
					<option value="newest"><?php _e( 'Newest Registered', 'buddyboss' ); ?></option>
					<option value="alphabetical"><?php _e( 'Alphabetical', 'buddyboss' ); ?></option>

					<?php

					/**
					 * Fires inside the members friends order options select input.
					 *
					 * @since 2.0.0
					 */
					do_action( 'bp_member_friends_order_options' ); ?>

				</select>
			</li>

		<?php endif; ?>

	</ul>
</div>

<?php
switch ( bp_current_action() ) :

	// Home/My Friends
	case 'my-friends' :

		/**
		 * Fires before the display of member friends content.
		 *
		 * @since 1.2.0
		 */
		do_action( 'bp_before_member_friends_content' ); ?>

		<?php if (is_user_logged_in() ) : ?>
			<h2 class="bp-screen-reader-text"><?php
				/* translators: accessibility text */
				_e( 'My friends', 'buddyboss' );
			?></h2>
		<?php else : ?>
			<h2 class="bp-screen-reader-text"><?php
				/* translators: accessibility text */
				_e( 'Friends', 'buddyboss' );
			?></h2>
		<?php endif ?>

		<div class="members friends">

			<?php bp_get_template_part( 'members/members-loop' ) ?>

		</div><!-- .members.friends -->

		<?php

		/**
		 * Fires after the display of member friends content.
		 *
		 * @since 1.2.0
		 */
		do_action( 'bp_after_member_friends_content' );
		break;

	case 'requests' :
		bp_get_template_part( 'members/single/friends/requests' );
		break;

	// Any other
	default :
		bp_get_template_part( 'members/single/plugins' );
		break;
endswitch;

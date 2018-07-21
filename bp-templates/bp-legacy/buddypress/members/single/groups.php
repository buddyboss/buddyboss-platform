<?php
/**
 * BuddyBoss - Users Groups
 *
 * @package BuddyBoss
 * @subpackage bp-legacy
 * @version 3.0.0
 */

?>

<div class="item-list-tabs no-ajax" id="subnav" aria-label="<?php esc_attr_e( 'Member secondary navigation', 'buddyboss' ); ?>" role="navigation">
	<ul>
		<?php if ( bp_is_my_profile() ) bp_get_options_nav(); ?>

		<?php if ( !bp_is_current_action( 'invites' ) ) : ?>

			<li id="groups-order-select" class="last filter">

				<label for="groups-order-by"><?php _e( 'Order By:', 'buddyboss' ); ?></label>
				<select id="groups-order-by">
					<option value="active"><?php _e( 'Last Active', 'buddyboss' ); ?></option>
					<option value="popular"><?php _e( 'Most Members', 'buddyboss' ); ?></option>
					<option value="newest"><?php _e( 'Newly Created', 'buddyboss' ); ?></option>
					<option value="alphabetical"><?php _e( 'Alphabetical', 'buddyboss' ); ?></option>

					<?php

					/**
					 * Fires inside the members group order options select input.
					 *
					 * @since 1.2.0
					 */
					do_action( 'bp_member_group_order_options' ); ?>

				</select>
			</li>

		<?php endif; ?>

	</ul>
</div><!-- .item-list-tabs -->

<?php

switch ( bp_current_action() ) :

	// Home/My Groups
	case 'my-groups' :

		/**
		 * Fires before the display of member groups content.
		 *
		 * @since 1.2.0
		 */
		do_action( 'bp_before_member_groups_content' ); ?>

		<?php if ( is_user_logged_in() ) : ?>
			<h2 class="bp-screen-reader-text"><?php
				/* translators: accessibility text */
				_e( 'My groups', 'buddyboss' );
			?></h2>
		<?php else : ?>
			<h2 class="bp-screen-reader-text"><?php
				/* translators: accessibility text */
				_e( 'Member\'s groups', 'buddyboss' );
			?></h2>
		<?php endif; ?>

		<div class="groups mygroups">

			<?php bp_get_template_part( 'groups/groups-loop' ); ?>

		</div>

		<?php

		/**
		 * Fires after the display of member groups content.
		 *
		 * @since 1.2.0
		 */
		do_action( 'bp_after_member_groups_content' );
		break;

	// Group Invitations
	case 'invites' :
		bp_get_template_part( 'members/single/groups/invites' );
		break;

	// Any other
	default :
		bp_get_template_part( 'members/single/plugins' );
		break;
endswitch;

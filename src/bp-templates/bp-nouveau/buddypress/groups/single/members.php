<?php
/**
 * BuddyBoss - Groups Members
 *
 * This template can be overridden by copying it to yourtheme/buddypress/groups/single/members.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */
?>

<?php bp_get_template_part( 'groups/single/parts/members-subnav' ); ?>

    <div class="subnav-filters filters clearfix no-subnav">

		<?php bp_nouveau_search_form(); ?>

		<?php bp_get_template_part( 'common/filters/groups-screens-filters' ); ?>

		<?php bp_get_template_part( 'common/filters/grid-filters' ); ?>

    </div>

<?php
switch ( bp_action_variable( 0 ) ) :

	// Groups/All Members
	case 'all-members':
		?>

        <div id="members-group-list" class="group_members dir-list" data-bp-list="group_members">

            <div id="bp-ajax-loader"><?php bp_nouveau_user_feedback( 'group-members-loading' ); ?></div>

        </div><!-- .group_members.dir-list -->

		<?php
		break;

	case 'leaders':
		?>

        <div id="members-group-list" class="group_members dir-list" data-bp-list="group_members">

            <div id="bp-ajax-loader"><?php bp_nouveau_user_feedback( 'group-leaders-loading' ); ?></div>

        </div><!-- .group_leaders.dir-list -->

		<?php
		break;

	// Any other
	default:
		bp_get_template_part( 'members/single/plugins' );
		break;
endswitch;

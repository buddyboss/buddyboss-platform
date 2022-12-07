<?php
/**
 * The template for BP Nouveau Search & filters bar
 *
 * This template can be overridden by copying it to yourtheme/buddypress/common/search-and-filters-bar.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */

?>
<div class="subnav-filters filters no-ajax" id="subnav-filters">

	<?php if ( 'friends' !== bp_current_component() ) : ?>

        <?php if ( 'members' !== bp_current_component() || bp_disable_advanced_profile_search() ) : ?>
            <div class="subnav-search clearfix">

                <?php bp_nouveau_search_form(); ?>

            </div>
        <?php endif; ?>

	<?php endif; ?>
    
    <?php if ( ( 'members' === bp_current_component() || 'groups' === bp_current_component() || 'friends' === bp_current_component() ) && ! bp_is_current_action( 'requests' ) ): ?>
        <?php bp_get_template_part( 'common/filters/grid-filters' ); ?>
    <?php endif; ?>

	<?php if ( bp_is_user() && ( ! bp_is_current_action( 'requests' ) && ! bp_is_current_action( 'mutual' ) ) ): ?>
		<?php bp_get_template_part( 'common/filters/directory-filters' ); ?>
	<?php endif; ?>

    <?php if ( 'members' === bp_current_component() || ( 'friends' === bp_current_component() && 'my-friends' === bp_current_action() ) ): ?>
        <?php bp_get_template_part( 'common/filters/member-filters' ); ?>
    <?php endif; ?>

	<?php if ( 'groups' === bp_current_component() ): ?>
		<?php bp_get_template_part( 'common/filters/group-filters' ); ?>
	<?php endif; ?>

</div><!-- search & filters -->

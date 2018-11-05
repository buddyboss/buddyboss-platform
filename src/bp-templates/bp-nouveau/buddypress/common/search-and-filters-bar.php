<?php
/**
 * BP Nouveau Search & filters bar
 *
 * @since BuddyPress 3.0.0
 * @version 3.1.0
 */
?>
<div class="subnav-filters filters no-ajax" id="subnav-filters">

	<?php if ( 'friends' !== bp_current_component() ) : ?>
	<div class="subnav-search clearfix">

		<?php bp_nouveau_search_form(); ?>

	</div>
	<?php endif; ?>
    
    <?php if ( bp_is_members_directory() ): ?>
        <div class="grid-filters">
			<a href="#" class="layout-grid-view active"><i class="dashicons dashicons-screenoptions" aria-hidden="true"></i></a>
			<a href="#" class="layout-list-view"><i class="dashicons dashicons-menu" aria-hidden="true"></i></a>
		</div>
    <?php endif; ?>

	<?php if ( ! ( bp_is_user() && ! bp_is_current_action( 'requests' ) ) && 'groups' !== bp_current_component() ): ?>
		<?php bp_get_template_part( 'common/filters/directory-filters' ); ?>
	<?php endif; ?>

</div><!-- search & filters -->

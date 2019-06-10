<?php
/**
 * BP Nouveau Component's grid filters template.
 *
 * @since BuddyBoss 1.0.0
 */
?>

<div class="grid-filters" data-object="<?php echo bp_current_component(); ?>">
    <a href="#" class="layout-view layout-grid-view active bp-tooltip"  data-view="grid" data-bp-tooltip-pos="up"
       data-bp-tooltip="<?php _e( 'Grid View', 'buddyboss' ); ?>">
        <i class="dashicons dashicons-screenoptions" aria-hidden="true"></i>
    </a>

    <a href="#" class="layout-view layout-list-view bp-tooltip" data-view="list" data-bp-tooltip-pos="up"
       data-bp-tooltip="<?php _e( 'List View', 'buddyboss' ); ?>">
        <i class="dashicons dashicons-menu" aria-hidden="true"></i>
    </a>
</div>
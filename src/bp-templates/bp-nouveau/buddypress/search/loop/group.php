<li class="bboss_search_item bboss_search_item_group">
    <div class="item-avatar">
        <a href="<?php bp_group_permalink(); ?>"><?php bp_group_avatar( 'type=full&width=70&height=70' ); ?></a>
    </div>

    <div class="item">
        <div class="item-title"><a href="<?php bp_group_permalink(); ?>"><?php bp_group_name(); ?></a></div>
        <div class="item-meta"><span class="activity"><?php printf( __( 'active %s', 'buddypress-global-search' ), bp_get_group_last_active() ); ?></span></div>

        <div class="item-desc"><?php bp_group_description_excerpt(); ?></div>

        <?php do_action( 'bp_directory_groups_item' ); ?>

    </div>

    <div class="action">

        <?php do_action( 'bp_directory_groups_actions' ); ?>

        <div class="meta">

            <?php bp_group_type(); ?> / <?php bp_group_member_count(); ?>

        </div>

    </div>
</li>
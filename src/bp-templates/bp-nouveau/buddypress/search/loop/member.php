<li class="bboss_search_item bboss_search_item_member">
    <div class="item-avatar">
        <a href="<?php bp_member_permalink(); ?>"><?php bp_member_avatar('type=full&width=70&height=70'); ?></a>
    </div>

    <div class="item">
        <div class="item-title">
            <a href="<?php bp_member_permalink(); ?>"><?php bp_member_name(); ?></a>
        </div>

        <div class="item-meta">
            <span class="activity">
                <?php bp_member_last_active(); ?>
            </span>
        </div>

        <div class="item-desc">
            <p>
                <?php if ( bp_get_member_latest_update() ) : ?>
                    <?php bp_member_latest_update( array( 'view_link' => true ) ); ?>
                <?php endif; ?>
            </p>
        </div>

        <?php
         /***
          * If you want to show specific profile fields here you can,
          * but it'll add an extra query for each member in the loop
          * (only one regardless of the number of fields you show):
          *
          * bp_member_profile_data( 'field=the field name' );
          */
        ?>
    </div>

    <div class="action">

        <?php do_action( 'bp_directory_members_actions' ); ?>

    </div>
</li>

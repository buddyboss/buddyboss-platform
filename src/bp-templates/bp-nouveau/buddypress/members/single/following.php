<?php
/**
 * BuddyBoss - Users Following
 *
 * @since BuddyBoss 1.4.7
 * @version 1.0.0
 */
?>

<?php bp_get_template_part( 'members/single/parts/item-subnav' ); ?>

<?php bp_get_template_part( 'common/search-and-filters-bar' ); ?>

<?php bp_nouveau_member_hook( 'before', 'following_content' ); ?>

<div class="members following" data-bp-follow="following" data-bp-list="members_following">

    <div id="bp-ajax-loader"><?php bp_nouveau_user_feedback( 'member-following-loading' ); ?></div>

</div><!-- .members.following -->

<?php bp_nouveau_member_hook( 'after', 'following_content' ); ?>
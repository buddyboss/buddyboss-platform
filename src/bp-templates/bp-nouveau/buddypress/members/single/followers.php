<?php
/**
 * BuddyBoss - Users Followers
 *
 * @since BuddyBoss 1.4.7
 * @version 1.0.0
 */
?>

<?php bp_get_template_part( 'members/single/parts/item-subnav' ); ?>

<?php bp_get_template_part( 'common/search-and-filters-bar' ); ?>

<?php bp_nouveau_member_hook( 'before', 'followers_content' ); ?>

<div class="members followers" data-bp-follow="followers" data-bp-list="members_followers">

    <div id="bp-ajax-loader"><?php bp_nouveau_user_feedback( 'member-followers-loading' ); ?></div>

</div><!-- .members.followers -->

<?php bp_nouveau_member_hook( 'after', 'followers_content' ); ?>
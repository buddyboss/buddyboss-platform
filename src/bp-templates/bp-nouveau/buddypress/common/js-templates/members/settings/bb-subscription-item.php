<?php
/**
 * BP Nouveau member subscription item template
 *
 * This template can be overridden by copying it to yourtheme/buddypress/common/js-templates/members/settings/bb-subscription-item.php.
 *
 * @since   BuddyBoss [BBVERSION]
 * @version 1.0.0
 */
?>

<script type="text/html" id="tmpl-bb-subscription-item">
    <a href="#" class="subscription-item_anchor">
        <div class="subscription-item_image">
            <img src="https://source.unsplash.com/user/c_v_r/100x100" alt="" />
        </div>
        <div class="subscription-item_detail">
            <span class="subscription-item_title">Ask Anything Random Here</span>
        </div>
    </a>
    <button type="button" class="subscription-item_remove" aria-label="<?php esc_html_e( 'Unsubscribe', 'buddyboss' ); ?>" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_html_e( 'Unsubscribe', 'buddyboss' ); ?>">
        <i class="bb-icon-lined bb-icon-times"></i>
    </button>
</script>

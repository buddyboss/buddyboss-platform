<?php
/**
 * BP Nouveau messages hook template
 *
 * This template can be overridden by copying it to yourtheme/buddypress/messages/parts/bp-messages-hook.php.
 *
 * @since   1.0.0
 * @version 1.0.0
 */

/**
 * Fires before the message hook template.
 *
 * @since BuddyBoss 2.1.0
 */
do_action( 'bp_nouveau_messages_hook_before_js_template' );
?>

<script type="text/html" id="tmpl-bp-messages-hook">
	{{{data.extraContent}}}
</script>

<?php
/**
 * Fires after the message hook template.
 *
 * @since BuddyBoss 2.1.0
 */
do_action( 'bp_nouveau_messages_hook_after_js_template' );

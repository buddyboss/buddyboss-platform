<?php
/**
 * BP Nouveau messages single template
 *
 * This template can be overridden by copying it to yourtheme/buddypress/messages/parts/bp-messages-single.php.
 *
 * @since   1.0.0
 * @version 1.0.0
 */

$os = bb_core_get_os();

?>

<script type="text/html" id="tmpl-bp-messages-single">
	<?php bp_nouveau_messages_hook( 'before', 'thread_content' ); ?>

	<div id="bp-message-thread-header" class="message-thread-header"></div>
	<div id="bp-message-load-more"></div>
	<div class="bp-messages-feedback"></div>

	<?php bp_nouveau_messages_hook( 'before', 'thread_list' ); ?>

	<ul id="bp-message-thread-list"></ul>

	<?php bp_nouveau_messages_hook( 'after', 'thread_list' ); ?>

	<?php bp_nouveau_messages_hook( 'before', 'thread_reply' ); ?>

	<div class="bp-messages-notice"></div>
	<form id="send-reply" class="standard-form send-reply">
		<div class="message-box">
			<div class="bp-send-message-notices"></div>
			<div class="message-metadata">

				<?php bp_nouveau_messages_hook( 'before', 'reply_meta' ); ?>

				<div class="avatar-box">
					<strong><?php esc_html_e( 'Send a Reply', 'buddyboss' ); ?></strong>
				</div>

				<?php bp_nouveau_messages_hook( 'after', 'reply_meta' ); ?>

			</div><!-- .message-metadata -->

			<div class="bp-message-content-wrap">

				<?php bp_nouveau_messages_hook( 'before', 'reply_box' ); ?>

				<label for="message_content" class="bp-screen-reader-text"><?php esc_html_e( 'Reply to Message', 'buddyboss' ); ?></label>
				<div id="bp-message-content"></div>
				<?php
				if ( 'mac' === $os ) {
					?>
					<p class="bp-message-content_foot_note"><span class="space_note"><strong><?php esc_html_e( 'Return', 'buddyboss' ); ?></strong><?php esc_html_e( ' to Send', 'buddyboss' ); ?></span><strong><?php esc_html_e( 'Return+Shift', 'buddyboss' ); ?> </strong> <?php esc_html_e( 'to add a new line', 'buddyboss' ); ?></p>
					<?php
				} elseif ( 'window' === $os ) {
					?>
					<p class="bp-message-content_foot_note"><span class="space_note"><strong><?php esc_html_e( 'Enter', 'buddyboss' ); ?></strong><?php esc_html_e( ' to Send', 'buddyboss' ); ?></span><strong><?php esc_html_e( 'Shift+Enter', 'buddyboss' ); ?> </strong> <?php esc_html_e( 'to add a new line', 'buddyboss' ); ?></p>
					<?php
				}
				?>

				<?php bp_nouveau_messages_hook( 'after', 'reply_box' ); ?>

			</div><!-- .message-content -->

		</div><!-- .message-box -->
	</form>

	<?php bp_nouveau_messages_hook( 'after', 'thread_reply' ); ?>

	<?php bp_nouveau_messages_hook( 'after', 'thread_content' ); ?>
</script>
<script type="text/html" id="tmpl-bp-messages-reply-form-submit">
	<input type="submit" name="send" value="<?php esc_attr_e( 'Send Reply', 'buddyboss' ); ?>" id="send_reply_button" class="small" />
</script>

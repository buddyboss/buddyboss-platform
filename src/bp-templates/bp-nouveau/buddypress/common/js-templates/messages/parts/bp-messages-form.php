<?php
/**
 * BP Nouveau messages form template
 *
 * This template can be overridden by copying it to yourtheme/buddypress/messages/parts/bp-messages-form.php.
 *
 * @since   1.0.0
 * @version 1.0.0
 */

$os = bb_core_get_os();

?>

<script type="text/html" id="tmpl-bp-messages-form">
	<?php bp_nouveau_messages_hook( 'before', 'compose_content' ); ?>

	<div class="bp-messages-form-header">
		<label class="bp-new-message-heading" for="send-to-input"><?php esc_html_e( 'New Message', 'buddyboss' ); ?></label>
		<a href="#" class="bp-close-compose-form"><span class="bb-icon-l bb-icon-times"></span></a>
	</div>

	<div class="bp-messages-feedback"></div>

	<div class="bp-messages-recipient">
		<span><?php esc_html_e( 'To:', 'buddyboss' ); ?></span>

		<select
			name="send_to[]"
			class="send-to-input"
			id="send-to-input"
			placeholder="<?php esc_html_e( 'Type the names of one or more people', 'buddyboss' ); ?>"
			autocomplete="off"
			multiple="multiple"
			style="width: 100%"
		>
			<?php
			if ( ! empty( $_GET['r'] ) ) :

				if ( bp_is_username_compatibility_mode() ) {
					$user_id = bp_core_get_userid( urldecode( $_GET['r'] ) );
				} else {
					$user_id = bp_core_get_userid_from_nicename( $_GET['r'] );
				}
				$name = bp_core_get_user_displayname( $user_id );
				?>
				<option value="@<?php echo esc_attr( $_GET['r'] ); ?>" selected data-action="<?php echo $user_id; ?>"><?php echo esc_html( $name ); ?></option>
			<?php endif; ?>

		</select>
	</div>

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

	<?php bp_nouveau_messages_hook( 'after', 'compose_content' ); ?>
</script>
<script type="text/html" id="tmpl-bp-messages-form-submit">
	<input type="button" id="bp-messages-send" class="button bp-primary-action" value="<?php esc_attr_e( 'Send', 'buddyboss' ); ?>"/>
</script>

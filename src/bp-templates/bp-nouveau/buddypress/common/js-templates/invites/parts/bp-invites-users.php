<?php
/**
 * Invites users Templates
 *
 * This template can be overridden by copying it to yourtheme/buddypress/common/js-templates/invites/parts/bp-invites-users.php.
 *
 * @since   1.0.0
 * @version 1.0.0
 */

?>
<script type="text/html" id="tmpl-bp-invites-users">
	<div class="item-avatar">
		<img src="{{data.avatar}}" class="avatar" alt="">
	</div>

	<div class="item">
		<div class="list-title member-name">
			{{data.name}}
		</div>

		<# if ( undefined !== data.is_sent ) { #>
		<div class="item-meta">
			<# if ( undefined !== data.invited_by ) { #>
			<ul class="group-inviters">
				<li><?php esc_html_e( 'Invited by:', 'buddyboss' ); ?></li>
				<# for ( i in data.invited_by ) { #>
				<li><a href="{{data.invited_by[i].user_link}}" class="bp-tooltip" data-bp-tooltip-pos="left" data-bp-tooltip="{{data.invited_by[i].user_name}}"><img src="{{data.invited_by[i].avatar}}" width="30px" class="avatar mini" alt="{{data.invited_by[i].user_name}}"></a></li>
				<# } #>
			</ul>
			<# } #>

			<p class="status">
				<# if ( false === data.is_sent ) { #>
				<?php esc_html_e( 'The invite has not been sent.', 'buddyboss' ); ?>
				<# } else { #>
				<?php esc_html_e( 'The invite has been sent.', 'buddyboss' ); ?>
				<# } #>
			</p>
		</div>
		<# } #>
	</div>

	<div class="action">
		<# if ( undefined === data.is_sent || ( false === data.is_sent && true === data.can_edit ) ) { #>
		<button type="button" class="button invite-button group-add-remove-invite-button bp-tooltip bp-icons<# if ( data.selected ) { #> selected<# } #>" data-bp-tooltip-pos="left" data-bp-tooltip="<# if ( data.selected ) { #><?php esc_attr_e( 'Cancel invitation', 'buddyboss' ); ?><# } else { #><?php esc_attr_e( 'Invite', 'buddyboss' ); ?><# } #>">
			<span class="icons" aria-hidden="true"></span>
			<span class="bp-screen-reader-text">
					<# if ( data.selected ) { #>
						<?php esc_html_e( 'Cancel invitation', 'buddyboss' ); ?>
					<# } else { #>
						<?php esc_html_e( 'Invite', 'buddyboss' ); ?>
					<# } #>
				</span>
		</button>
		<# } #>

		<# if ( undefined !== data.can_edit && true === data.can_edit ) { #>
		<button type="button" class="button invite-button group-remove-invite-button bp-tooltip bp-icons" data-bp-tooltip-pos="left" data-bp-tooltip="<?php esc_attr_e( 'Cancel invitation', 'buddyboss' ); ?>">
			<span class=" icons" aria-hidden="true"></span>
			<span class="bp-screen-reader-text"><?php esc_attr_e( 'Cancel invitation', 'buddyboss' ); ?></span>
		</button>
		<# } #>
	</div>

</script>

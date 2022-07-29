<?php
/**
 * BP Nouveau messages thread template
 *
 * This template can be overridden by copying it to yourtheme/buddypress/messages/parts/bp-messages-thread.php.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
?>

<script type="text/html" id="tmpl-bp-messages-thread">
	<#
	var other_recipients = _.reject(data.recipients, function(item) {
	return item.is_you;
	});

	var current_user = _.find(data.recipients, function(item) {
	return item.is_you;
	});

	var include_you = other_recipients.length >= 2;
	var first_three = _.first(other_recipients, 3);

	if (first_three.length == 0) {
	include_you = true;
	}
	#>

	<div class="bb_more_options message-thread-options">
		<a href="#" class="bb_more_options_action bp-tooltip">
			<i class="bb-icon-menu-dots-h"></i>
		</a>
		<ul class="bb_more_options_list message_action__list">
			<li class="unread">
				<a data-bp-action="unread" href="#"><?php esc_html_e( 'Mark as unread', 'buddyboss' ); ?></a>
			</li>

			<# if ( data.is_thread_archived ) { #>
			<li class="unhide_thread">
				<a data-bp-action="unhide_thread" href="#"><?php esc_html_e( 'Unarchive Conversation', 'buddyboss' ); ?>
				</a>
			</li>
			<# } else { #>
			<li class="hide_thread">
				<a data-bp-action="hide_thread" href="#"><?php esc_html_e( 'Archive', 'buddyboss' ); ?>
				</a>
			</li>
			<# } #>

			<?php if ( bp_is_active( 'moderation' ) && bp_is_moderation_member_blocking_enable() ) { ?>
				<# if ( data.recipients.count > 1 ) { #>
				<li class="report_thread">
					<a id="mass-block-member" href="#mass-user-block-list" class="mass-block-member" data-thread-id="{{data.id}}" data-cp="1"><?php esc_html_e( 'Block a member', 'buddyboss' ); ?></a>
				</li>
				<# } else if ( other_recipients.length == 1 && other_recipients[0].is_user_blocked ) { #>
				<li class="reported_thread">
					<a href="#"><?php esc_html_e( 'Blocked', 'buddyboss' ); ?></a>
				</li>
				<# } else if( other_recipients.length == 1 && true == other_recipients[0].can_be_blocked ) { #>
				<li class="report_thread">
					<a id="report-content-<?php echo esc_attr( BP_Moderation_Members::$moderation_type ); ?>-{{other_recipients[0].id}}" href="#block-member" class="block-member" data-bp-content-id="{{other_recipients[0].id}}" data-bp-content-type="<?php echo esc_attr( BP_Moderation_Members::$moderation_type ); ?>" data-bp-nonce="<?php echo esc_attr( wp_create_nonce( 'bp-moderation-content' ) ); ?>"><?php esc_html_e( 'Block member', 'buddyboss' ); ?></a>
				</li>
				<# } #>
			<?php } ?>

			<li class="delete_messages">
				<a data-bp-action="delete" href="#"><?php esc_html_e( 'Delete your messages', 'buddyboss' ); ?></a>
			</li>
			<li class="delete_thread">
				<a data-bp-action="delete_thread" href="#"><?php esc_html_e( 'Delete conversation', 'buddyboss' ); ?></a>
			</li>
		</ul>
	</div>

	<# if ( ! data.is_search ) { #>
	<a href="javascript:void(0);" data-bp-thread-id="{{data.id}}" data-bp-action="hide_thread" class="close-conversation"> <i class="dashicons dashicons-no-alt"></i> </a>
	<# } #>
	<a class="bp-message-link bp-message-link-{{data.id}}" href="../view/{{data.id}}/" data-thread-id="{{data.id}}">
		<div class="thread-avatar {{ ( 1 === data.avatars.length && 'user' === data.avatars[0].type ? 'bb-member-status-' + data.avatars[0].id : '' ) }} {{ ( data.is_user_suspended || data.is_user_blocked ) && ! data.is_group_thread ? 'bp-suspended-avatar' : '' }}">
			<# if ( data.avatars && data.avatars.length > 1  ) {
				if( data.avatars.length == 2 ) { #>
					<div class="thread-multiple-avatar">
				<# } #>
					<img class="avatar" src="{{{data.avatars[0].url}}}" alt="{{data.avatars[0].name}}"/>
					<# if( data.avatars[1] ) { #>
						<img class="avatar" src="{{{data.avatars[1].url}}}" alt="{{data.avatars[1].name}}"/>
					<# }
				if( data.avatars.length == 2 ) { #>
					</div>
				<# } #>
			<# } else if ( data.group_avatar && data.group_avatar.length > 1 && data.is_group_thread ) { #>
				<img class="avatar" src="{{{data.group_avatar}}}" alt="{{data.group_name}}" />
			<# } else { #>
				<# if ( other_recipients.length > 1 ) { #>
					<span class="recipients-count">{{other_recipients.length}}</span>
					<img class="avatar" src="{{{data.sender_avatar}}}" alt="{{data.sender_name}}" />
				<# } else { #>
					<# var recipient = _.first(other_recipients)? _.first(other_recipients) : current_user; #>
					<# if ( typeof( recipient ) != "undefined" && recipient !== null && recipient.avatar.length > 1 && recipient.user_name.length > 1 ) { #>
						<img class="avatar" src="{{{recipient.avatar}}}" alt="{{recipient.user_name}}" />
					<# } #>
				<# } #>
			<# } #>
		</div>

		<div class="thread-content {{ ( data.is_user_suspended || data.is_user_blocked ) && ! data.is_group_thread ? 'bp-suspended-content' : '' }}" data-thread-id="{{data.id}}">
			<div class="thread-to">

				<# if ( data.group_name && data.group_name.length && data.is_group_thread ) { #>
					<span class="user-name">{{data.group_name}}</span>
				<# } else { #>
				<# for ( i in first_three ) { #>
					<span class="user-name">{{other_recipients[i].user_name}}<# if ( i != first_three.length - 1  || ( i == first_three.length -1 && data.toOthers ) ) { #><?php esc_html_e( ',', 'buddyboss' ); ?><# } #></span>
				<# } #>
				<# if ( data.toOthers ) { #>
				<span class="num-name">{{data.toOthers}}</span>
				<# } #>

				<# } #>
			</div>

			<div class="thread-subject">
				<div class="typing-indicator bp-hide"></div>
				<# if ( ! data.is_user_suspended && ! data.is_user_blocked ) { #>
					<span class="thread-excerpt">
						<span class="last-message-sender">
							<# if ( data.sender_is_you ) { #>
									<?php _e( 'You', 'buddyboss' ); ?>:
							<# } else { #>
								<# if ( data.sender_name ) { #>
									{{ data.sender_name }}:
								<# } #>
							<# } #>
						</span>
						{{{data.excerpt}}}
					</span>
				<# } #>
				<div class="thread-date">
					<time datetime="{{data.date.toISOString()}}">{{data.display_date}}</time>
				</div>
			</div>

		</div>
	</a>

</script>

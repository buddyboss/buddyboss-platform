<script type="text/html" id="tmpl-bp-messages-single-header">
	<#    var other_recipients = _.reject(data.recipients.members, function(item) {    return item.is_you;    });
	var current_user = _.find(data.recipients.members, function(item) {    return item.is_you == true;    });

	var include_you = other_recipients.length >= 2;

	if (other_recipients.length == 0) {    include_you = true;    }    #>

	<header class="single-message-thread-header">
		<a href="#" class="bp-back-to-thread-list"><span class="dashicons dashicons-arrow-left-alt2"></span></a> <# if ( undefined !== other_recipients ) { #>
		<dl class="thread-participants">
			<dt>
				<# if ( data.group_name.length > 1 && data.is_group_thread ) { #> <span class="participants-name">
						<# if ( data.is_deleted ) { #>
							{{data.group_name}}
						<# } else { #>
							<a href="{{data.group_link}}">{{data.group_name}}</a>
						<# } #>
					</span>
                <# } else { #>
                    <# for ( i in other_recipients ) { #>
                        <span class="participants-name">
                            <# if ( other_recipients[i].is_deleted ) { #>
                                {{other_recipients[i].user_name}}
                             <# } else { #>
                                <# if( other_recipients[i].user_link ) { #><a href="{{other_recipients[i].user_link}}">{{other_recipients[i].user_name}}</a><# } else { #>{{other_recipients[i].user_name}}<# } #><# } #><# if ( i != other_recipients.length -1 || ( i == other_recipients.length -1 ) && data.toOthers ) { #><?php _e( ',', 'buddyboss' ); ?><# } #>
                        </span>
                    <# } #>
					<# if ( ! data.is_group_thread && data.recipients.count > data.recipients.current_count ) { #>
						<a href="javascript:void(0);" id="view_more_members" class="view_more_members view_more_members_cls"
							data-thread-id="{{data.id}}"
							data-tp="{{data.recipients.total_pages}}"
							data-tc="{{data.recipients.count}}"
							data-pp="{{data.recipients.per_page}}"
							data-cp="2"
							data-action="bp_view_more"><?php esc_html_e( 'Load More', 'buddyboss' ); ?></a>
					<# } #>

				<# } #>
			</dt>

			<dd>
				<span class="thread-date"><?php esc_html_e( 'Started', 'buddyboss' ); ?> {{data.started_date}}</span>
			</dd>
		</dl>
		<# } #>
		<div class="actions">
			<?php
			if ( bp_current_user_can( 'bp_moderate' ) ) {
				?>
				<div class="message_actions">
					<a href="#" class="message_action__anchor"> <i class="bb-icon-menu-dots-v"></i> </a>
					<div class="message_action__list">
						<ul>
							<li class="unread"><a data-bp-action="unread" href="#"><?php esc_html_e( 'Mark unread',
											'buddyboss' ); ?></a></li>
							<li class="hide_thread">
								<a data-bp-action="hide_thread" href="#"><?php esc_html_e( 'Hide conversation',
											'buddyboss' ); ?></a>
							</li>
							<?php if ( bp_is_active( 'moderation' ) && bp_is_moderation_member_blocking_enable() ) { ?>
								<# if ( other_recipients.length > 1 ) { #>
	                                <li class="report_thread">
	                                    <a id="mass-block-member" href="#mass-user-block-list" class="mass-block-member" data-thread-id="{{data.id}}" data-cp="1"><?php esc_html_e( 'Block a member', 'buddyboss' ); ?></a>
	                                </li>
								<# } else if ( other_recipients.length == 1 && other_recipients[0].is_blocked ) { #>
	                                <li class="reported_thread">
	                                    <a href="#"><?php esc_html_e( 'Blocked', 'buddyboss' );  ?></a>
	                                </li>
	                            <# } else if( other_recipients.length == 1 && true == other_recipients[0].can_be_blocked ) { #>
	                                <li class="report_thread">
	                                    <a id="report-content-<?php echo esc_attr( BP_Moderation_Members::$moderation_type ) ?>-{{other_recipients[0].id}}" href="#block-member" class="block-member" data-bp-content-id="{{other_recipients[0].id}}" data-bp-content-type="<?php echo esc_attr( BP_Moderation_Members::$moderation_type ); ?>" data-bp-nonce="<?php echo esc_attr( wp_create_nonce( 'bp-moderation-content' ) ); ?>"><?php esc_html_e( 'Block member', 'buddyboss' ); ?></a>
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
				</div>
				<?php
			} else {

				$old_user = false;
				if ( class_exists( 'BP_Core_Members_Switching' ) ) {
					$old_user = BP_Core_Members_Switching::get_old_user();
				}
				?>
				<div class="message_actions">
					<a href="#" class="message_action__anchor"> <i class="bb-icon-menu-dots-v"></i> </a>
					<div class="message_action__list">
						<ul>
							<li class="unread"><a data-bp-action="unread" href="#"><?php esc_html_e( 'Mark unread',
											'buddyboss' ); ?></a></li>
							<li class="hide_thread">
								<a data-bp-action="hide_thread" href="#"><?php esc_html_e( 'Hide conversation',
											'buddyboss' ); ?></a>
							</li>
							<?php if ( bp_is_active( 'moderation' ) && bp_is_moderation_member_blocking_enable() ) { ?>
								<# if ( other_recipients.length > 1 ) { #>
								<li class="report_thread">
									<a id="mass-block-member" href="#mass-user-block-list" class="mass-block-member" data-thread-id="{{data.id}}" data-cp="1"><?php esc_html_e( 'Block a member', 'buddyboss' ); ?></a>
								</li>
								<# } else if ( other_recipients.length == 1 && other_recipients[0].is_blocked ) { #>
	                                <li class="reported_thread">
	                                    <a href="#"><?php esc_html_e( 'Blocked', 'buddyboss' );  ?></a>
	                                </li>
	                            <# } else if( other_recipients.length == 1 && true == other_recipients[0].can_be_blocked ) { #>
	                            <li class="report_thread">
	                                <a id="report-content-<?php echo esc_attr( BP_Moderation_Members::$moderation_type ) ?>-{{other_recipients[0].id}}" href="#block-member" class="block-member" data-bp-content-id="{{other_recipients[0].id}}" data-bp-content-type="<?php echo esc_attr( BP_Moderation_Members::$moderation_type ); ?>" data-bp-nonce="<?php echo esc_attr( wp_create_nonce( 'bp-moderation-content' ) ); ?>"><?php esc_html_e( 'Block member', 'buddyboss' ); ?></a>
	                            </li>
	                            <# } #>
							<?php } ?>
							<li class="delete_messages" data-bp-action="delete">
								<a data-bp-action="delete" href="#"><?php esc_html_e( 'Delete your messages', 'buddyboss' ); ?></a>
							</li>
							<?php
							if ( ! empty( $old_user ) ) {
								?>
								<li class="delete_thread">
									<a data-bp-action="delete_thread" href="#"><?php esc_html_e( 'Delete conversation', 'buddyboss' ); ?></a>
								</li>
								<?php
							}
							?>
						</ul>
					</div>
				</div>
				<?php
			}
			?>
		</div>
		<?php if ( bp_is_active( 'moderation' ) && bp_is_moderation_member_blocking_enable() ) { ?>
			<div id="mass-user-block-list" class="mass-user-block-list moderation-popup mfp-hide">
				<div class="modal-mask bb-white bbm-model-wrap bbm-uploader-model-wrap">
					<div class="modal-wrapper">
						<div class="modal-container">
							<header class="bb-model-header">
								<h4><?php esc_html_e( 'Block a Member?', 'buddyboss' ); ?></h4>
								<button title="<?php esc_attr_e( 'Close (Esc)', 'buddyboss' ); ?>" type="button" class="mfp-close"></button>
							</header>
							<div id="moderated_user_list"></div>
						</div>
					</div>
				</div>
			</div>
			<?php
		}
		?>
	</header>
</script>

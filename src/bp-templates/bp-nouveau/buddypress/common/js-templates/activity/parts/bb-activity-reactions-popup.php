<?php
/**
 * The template for displaying activity reactions in popup modal.
 *
 * This template can be overridden by copying it to yourtheme/buddypress/common/js-templates/activity/parts/bb-activity-reactions-popup.php
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Core
 */

?>
<script type="text/html" id="tmpl-activity-reactions-popup">
	<div class="activity-state-popup">
		<div class="activity-state-popup_overlay"></div>
		<div class="activity-state-popup_inner">
			<# if ( ! data ) { #>
				<p class="reaction-loader"><i class="bb-icon-l bb-icon-spinner animate-spin"></i></p>
			<# } else { #>
				<div class="activity-state-popup_title">
					<#
					var reactionCount = Object.keys(data.reactions).length;
					if ( data.reaction_mode && data.reaction_mode === 'emotions' && reactionCount > 1 ) { #>
						<h4><?php esc_html_e( 'Reactions', 'buddyboss' ); ?></h4>
					<# } else if( data.reaction_mode && data.reaction_mode === 'emotions' && 1 === reactionCount ) { #>
						<h4> {{data.reactions[0].icon_text}} ({{ data.reactions[0].total_count }})</h4>
					<# } else { #>
						<h4><?php esc_html_e( 'Likes', 'buddyboss' ); ?>({{ data.reactions[0].total_count }})</h4>
					<# } #>
				</div>
				<div class="activity-state-popup_tab">
					<# if ( data.reaction_mode && data.reaction_mode === 'emotions' && reactionCount > 1 ) { #>
					<div class="activity-state-popup_tab_panel">
						<ul>
							<#
							var isFirstItem = true;
							jQuery.each( data.reactions, function( key, reaction ) {
							#>
							<li>
								<a href="javascript:void(0);" class="{{ isFirstItem ? 'active' : '' }}" data-tab="activity-state_{{key}}" reaction-id="{{ reaction.id ? reaction.id : '0' }}">
									<# if ( reaction.type === 'all' ) { #>
										{{ reaction.icon_text }}
									<# } else if ( reaction.type === 'bb-icons' ) { #>
										<i class="bb-icon-{{ reaction.icon }}" style="font-weight:200;color:{{ reaction.icon_color }};"></i>
										<span>{{ reaction.total_count }}</span>
									<# } else if ( reaction.icon_path !== '' ) { #>
										<img src="{{ reaction.icon_path }}" class="{{ reaction.type }}" alt="{{ reaction.icon_text }}" />
										<span>{{ reaction.total_count }}</span>
									<# } else { #>
										<i class="bb-icon-thumbs-up" style="font-weight:200;color:#385DFF;"></i>
										<span>{{ reaction.total_count }}</span>
									<# } #>
								</a>
							</li>
							<#
								isFirstItem = false;
							});
							#>
						</ul>
					</div>
					<# } #>
					<div class="activity-state-popup_tab_content">
						<#
						isFirstItem = true;
						jQuery.each( data.reactions, function( key, reaction ) {
							#>
							<div class="activity-state-popup_tab_item activity-state_{{key}} {{isFirstItem ? 'active' : ''}}" data-reaction-id="{{reaction.id ? reaction.id : '0'}}" data-paged="{{reaction.paged}}" data-total-pages="{{reaction.total_pages}}">
								<ul class="activity-state_users">
								<# jQuery.each( reaction.users, function( key, user ) { #>
									<li class="activity-state_user">
										<div class="activity-state_user__avatar">
											<a href="{{ user.profile_url }}">
												<img class="avatar" src="{{ user.avatar }}" alt="{{ user.name }}" />
												<div class="activity-state_user__reaction">
												<# if ( user.reaction.type === 'bb-icons' ) { #>
													<i class="bb-icon-{{ user.reaction.icon }}" style="font-weight:200;color:{{ user.reaction.icon_color }};"></i>
												<# } else if ( user.reaction.icon_path !== '' ) { #>
													<img src="{{ user.reaction.icon_path }}" class="{{ user.reaction.type }}" alt="{{ user.reaction.icon_text }}" />
												<# } else { #>
													<i class="bb-icon-thumbs-up" style="font-weight:200;color:#385DFF;"></i>
												<# } #>
												</div>
											</a>
										</div>
										<div class="activity-state_user__name">
											<a href="{{ user.profile_url }}">{{ user.name }}</a>
										</div>
										<# if ( user.member_type && user.member_type.label ) { #>
										<div class="activity-state_user__role" style="color:{{user.member_type.color.text}}; background-color:{{user.member_type.color.background}};">
											{{ user.member_type.label }}
										</div>
										<# } #>
									</li>
								<# }); #>
								</ul>
							</div>
						<#
							isFirstItem = false;
						});
						#>
					</div>
				</div>
			<# } #>
		</div>
	</div>
</script>

<script type="text/html" id="tmpl-activity-user-reactions-popup-list">
	<#
	if ( data.users ) {
		jQuery.each( data.users, function( key, user ) { #>
			<li class="activity-state_user">
				<div class="activity-state_user__avatar">
					<a href="{{ user.profile_url }}">
						<img class="avatar" src="{{ user.avatar }}" alt="{{ user.name }}" />
						<div class="activity-state_user__reaction">
						<# if ( user.reaction.type === 'bb-icons' ) { #>
							<i class="bb-icon-{{ user.reaction.icon }}" style="font-weight:200;color:{{ user.reaction.icon_color }};"></i>
						<# } else if ( user.reaction.icon_path !== '' ) { #>
							<img src="{{ user.reaction.icon_path }}" class="{{ user.reaction.type }}" alt="{{ user.reaction.icon_text }}" />
						<# } else { #>
							<i class="bb-icon-thumbs-up" style="font-weight:200;color:#385DFF;"></i>
						<# } #>
						</div>
					</a>
				</div>
				<div class="activity-state_user__name">
					<a href="{{ user.profile_url }}">{{ user.name }}</a>
				</div>
				<# if ( user.member_type && user.member_type.label ) { #>
				<div class="activity-state_user__role" style="color:{{user.member_type.color.text}}; background-color:{{user.member_type.color.background}};">
					{{ user.member_type.label }}
				</div>
				<# } #>
			</li>
		<# });
	}#>
</script>

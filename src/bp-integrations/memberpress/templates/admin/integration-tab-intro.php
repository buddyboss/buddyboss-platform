<div class="wrap">
	
	<div class="bp-admin-card section-bp-memberpress-integration">
		<h2><?php _e( 'MemberPress <span>&mdash; requires plugin to activate</span>', 'buddyboss' ); ?></h2>
		<p><?php
			printf(
				__('BuddyBoss Platform has integration settings for %s. If using LearnDash we add the ability to easily enroll users into courses based on their purchased membership level. If using our BuddyBoss Theme we also include styling for MemberPress.', 'buddyboss'),
				sprintf(
					'<a href="%s">%s</a>',
					'https://memberpress.com/buddyboss/home',
					__('MemberPress', 'buddyboss')
				)
			)
		?></p>
		<br />
		<div class="bp-admin-card-bottom">
			<?php
				printf(
					'<a href="%s" class="button-secondary">%s</a>',
					'https://memberpress.com/buddyboss/home',
					__('Get MemberPress', 'buddyboss')
				);
			?>
		</div>
	</div>

</div>
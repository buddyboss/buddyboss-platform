<div class="wrap">

	<div class="bp-admin-card section-bp_ld-integration">
		<?php
		$meta_icon      = bb_admin_icons( 'bp_ld-integration' );
		$meta_icon_html = '';
		if ( ! empty( $meta_icon ) ) {
			$meta_icon_html .= '<i class="' . esc_attr( $meta_icon ) . '"></i>';
		}
		if( ! bp_is_active( 'groups' ) && is_plugin_active( 'sfwd-lms/sfwd_lms.php' ) )  :
			?>
			<h2>
				<?php
				echo wp_kses(
					$meta_icon_html,
					array(
						'i' => array(
							'class' => array()
						)
					)
				);
				esc_html_e( 'Social Groups', 'buddyboss' );
				?>
			</h2>
			<p>
			<?php
				printf(
					__( 'You need to activate the <a href="%s">Social Groups component</a> in order to sync LearnDash groups with Social groups.', 'buddyboss' ),
					add_query_arg(
						array(
							'page' => 'bp-components',
						),
						admin_url( 'admin.php' )
					)
				)
				?>
			</p>
		<?php else: ?>
			<h2 class="has_tutorial_btn">
				<?php
				echo wp_kses(
					$meta_icon_html,
					array(
						'i' => array(
							'class' => array()
						)
					)
				);
				echo sprintf(
				/* translators: 1. Text. 2. Text. */
					'%1$s&nbsp;<span>&mdash; %2$s</span>',
					esc_html__( 'LearnDash', 'buddyboss' ),
					esc_html__( 'requires plugin to activate', 'buddyboss' )
				);
				?>
				<div class="bbapp-tutorial-btn">
					<a class="button" href="<?php echo bp_get_admin_url(
						add_query_arg(
							array(
								'page'    => 'bp-help',
								'article' => 62873,
							),
							'admin.php'
						)
					); ?>"><?php _e( 'View Tutorials', 'buddyboss' ); ?></a>
				</div>
			</h2>
			<p>
			<?php
				printf(
					__( 'BuddyBoss Platform has integration settings for %s. If using LearnDash we add the ability to sync LearnDash groups with social groups, to connect LearnDash courses to social groups, and more. If using our BuddyBoss Theme we also include styling for LearnDash.', 'buddyboss' ),
					sprintf(
						'<a href="%s">%s</a>',
						'https://learndash.idevaffiliate.com/111.html',
						__( 'LearnDash LMS', 'buddyboss' )
					)
				)
				?>
			</p>
		<?php endif; ?>
	</div>

</div>

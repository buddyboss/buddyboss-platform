<?php
/**
 * BuddyBoss - Profile Progression
 *
 * @since BuddyBoss 1.0.0
 */

$bp_nouveau = bp_nouveau();

// If no Profile Completion Progress found then stop.
if ( ! isset( $bp_nouveau->xprofile->profile_completion_widget_para ) ) {
	return;
}

$user_progress  = $bp_nouveau->xprofile->profile_completion_widget_para;
$progress_label = sprintf( __( '%s Complete', 'buddyboss' ), $user_progress['completion_percentage'] . '%' );

?>
<div class="profile_completion_wrap">

	<div class="pc_progress_wrap">
		<div class="progress_text_wrap">
			<span class="progress_text"><?php echo esc_html( $progress_label ); ?></span>
		</div>
		<div class="progress_container">
			<div class="pc_progress" style="width: <?php echo esc_attr( $user_progress['completion_percentage'] ); ?>%;"></div>
		</div>
	</div>

	<div class="pc_detailed_progress_wrap">

		<ul class="pc_detailed_progress">

			<?php
			if( isset( $user_progress['groups'] ) ){
				
				// Loop through all sections and show progress.
				foreach ( $user_progress['groups'] as $single_section_details ) :

					$user_progress_status = ( 0 === $single_section_details['completed'] && $single_section_details['total'] > 0 ) ? 'progress_not_started' : '';
					?>

					<li class="single_section_wrap 
					<?php
					echo ( $single_section_details['is_group_completed'] ) ? esc_attr( 'completed ' ) : esc_attr( 'incomplete ' );
					echo esc_attr( $user_progress_status );
					?>
					">
						<span class="section_number">
							<?php echo esc_html( $single_section_details['number'] ); ?>
						</span>
						<span class="section_name">
							<a href="<?php echo esc_url( $single_section_details['link'] ); ?>" class="group_link"><?php echo esc_html( $single_section_details['label'] ); ?></a>
						</span>
						<span class="progress">
							<span class="completed_staus">
								<span class="completed_steps"><?php echo absint( $single_section_details['completed'] ); ?></span>/<span class="total_steps"><?php echo absint( $single_section_details['total'] ); ?></span>
							</span>
						</span>
					</li>
					<?php
				endforeach;
			}
			?>

		</ul>

	</div>

</div>

<?php
/**
 * The template for profile progression
 *
 * This template can be overridden by copying it to yourtheme/buddypress/members/single/profile/widget.php.
 *
 * @since   BuddyBoss 1.0.0
 * @version 1.0.0
 */

$bp_nouveau = bp_nouveau();

// If no Profile Completion Progress found then stop.
if ( ! isset( $bp_nouveau->xprofile->profile_completion_widget_para ) ) {
	return;
}

$user_progress        = $bp_nouveau->xprofile->profile_completion_widget_para;
$progress_label       = ( $user_progress['completion_percentage'] == 100 ) ? esc_html__( 'Completed', 'buddyboss' ) : esc_html__( 'Complete', 'buddyboss' );
$user_progress_offset = 100 - $user_progress['completion_percentage'];

?>
<div class="profile_completion_wrap">

	<div class="pc_progress_wrap">
		<svg class="pc_progress_graph <?php echo ( 0 === $user_progress['completion_percentage'] ) ? esc_attr( 'pc_progress_graph--blank' ) : esc_attr( '' ); ?>" width="146" height="73" viewBox="0 0 146 73" fill="none">
			<path d="M143 73C143 34.3401 111.66 3 73 3C34.3401 3 3 34.3401 3 73" stroke="#F1F3F5" stroke-width="6"/>
			<path stroke-dasharray="<?php echo esc_attr( $user_progress['completion_percentage'] ); ?>, 100" stroke-dashoffset="-<?php echo esc_attr( $user_progress_offset ); ?>" class="pc_progress_rate" d="M143 73C143 34.3401 111.66 3 73 3C34.3401 3 3 34.3401 3 73" stroke="#F1F3F5" stroke-width="6" pathLength="100"/>
		</svg>
		<div class="progress_text_wrap">
			<h3><span class="progress_text_value"><?php echo esc_html( $user_progress['completion_percentage'] ); ?></span><span class="progress_text_unit"><?php echo __( '%', 'buddyboss' ); ?></span></h3>
			<span class="progress_text_label"><?php echo esc_html( $progress_label ); ?></span>
		</div>
	</div>

	<div class="pc_detailed_progress_wrap">

		<ul class="pc_detailed_progress">

			<?php
			if ( isset( $user_progress['groups'] ) ) {

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
						<span class="section_ico"><i class="bb-icon-l bb-icon-check"></i></span>
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

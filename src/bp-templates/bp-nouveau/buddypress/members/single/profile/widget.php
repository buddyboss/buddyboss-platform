<?php
/**
 * BuddyBoss - Profile Progression
 *
 * @since BuddyPress 3.0.0
 * @version 3.1.0
 */

$bp_nouveau = bp_nouveau();

if( !isset( $bp_nouveau->xprofile->profile_completion_widget_arg ) ){
	return;
}

$user_progress = $bp_nouveau->xprofile->profile_completion_widget_arg;

?>
<div class="profile_completion_wrap" >
	
	<div class="pc_progress_wrap" >
		<div class="progress_text_wrap" >
			<span class="progress_text" ><?php echo $user_progress['completion_percentage']; ?>% Complete</span>
		</div>
		<div class="progress_container" style="border: 1px solid black;" >
			<div class="pc_progress" style="height: 10px; width: <?php echo $user_progress['completion_percentage']; ?>%; background-color: #007cff;" ></div>
		</div>
	</div>
	
	<div class="pc_detailed_progress_wrap" >
		
		<ul class="pc_detailed_progress" >
		
		<?php foreach( $user_progress['groups'] as $single_section_details ): ?>
		
		<li class="single_section_wrap" >
			<span class="section_number" >
				<?php echo $single_section_details['number']; ?>
			</span>
			<span class="section_name" >
				<a href="<?php echo $single_section_details['link']; ?>" class="group_link" ><?php echo $single_section_details['label']; ?></a>
			</span>
			<span class="progress" >
				<span class="completed_staus <?php echo ( $single_section_details['is_group_completed'] ) ? 'completed' : 'incomplete' ?>" >
				<span class="completed_steps" ><?php echo $single_section_details['completed']; ?></span> / <span class="total_steps" ><?php echo $single_section_details['total']; ?></span>
				</span>
			</span>
		</li>
		
		<?php endforeach; ?>
			
		</ul>
		
	</div>
	
</div>
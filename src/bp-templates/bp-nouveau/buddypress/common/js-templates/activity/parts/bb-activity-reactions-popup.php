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
			<div class="activity-state-popup_title">
				<h4>Reactions</h4>
			</div>
			<div class="activity-state-popup_tab">
					<div class="activity-state-popup_tab_panel">
						<ul>
							<li>
								<a href="#" class="active" data-tab="activity-state_all">All</a>
							</li>
							<li>
								<a href="#" data-tab="activity-state_smiley">
									<img src="https://s.w.org/images/core/emoji/14.0.0/svg/1f643.svg" alt="Smiley" />
									<span>55</span>
								</a>
							</li>
							<li>
								<a href="#" data-tab="activity-state_thumbsup">
									<i class="bb-icon-thumbs-up" style="font-weight:200;color:#aeae16;"></i>
									<span>80</span>
								</a>
							</li>
							<li>
								<a href="#" data-tab="activity-state_confused">
									<img src="https://s.w.org/images/core/emoji/14.0.0/svg/1f644.svg" alt="Confused" />
									<span>59</span>
								</a>
							</li>
							<li>
								<a href="#" data-tab="activity-state_sad">
									<img src="https://s.w.org/images/core/emoji/14.0.0/svg/1f622.svg" alt="Sad">
									<span>37</span>
								</a>
							</li>
							<li>
								<a href="#" data-tab="activity-state_thumbsdown">
									<i class="bb-icon-thumbs-down" style="font-weight:200;color:#d33f3f;"></i>
									<span>37</span>
								</a>
							</li>
						</ul>
					</div>
					<div class="activity-state-popup_tab_content">
						<div class="activity-state-popup_tab_item activity-state_all active">
							<ul class="activity-state_users">
								<li class="activity-state_user">
									<div class="activity-state_user__avatar">
										<a href="#">
											<img class="avatar" src="http://localhost:8888/bb/wp-content/uploads/avatars/2/637775668aace-bpfull.jpg" alt="" />
											<div class="activity-state_user__reaction">
												<img src="https://s.w.org/images/core/emoji/14.0.0/svg/1f643.svg" alt="Smiley" />
											</div>
										</a>
									</div>
									<div class="activity-state_user__name">
										<a href="#">John</a>
									</div>
									<div class="activity-state_user__role">
										Admin
									</div>
								</li>
								<li class="activity-state_user">
									<div class="activity-state_user__avatar">
										<a href="#">
											<img class="avatar" src="http://localhost:8888/bb/wp-content/uploads/avatars/1408/653a29995c85e-bpfull.jpg" alt="" />
											<div class="activity-state_user__reaction">
												<i class="bb-icon-thumbs-up" style="font-weight:200;color:#aeae16;"></i>
											</div>
										</a>
									</div>
									<div class="activity-state_user__name">
										<a href="#">Angela</a>
									</div>
									<div class="activity-state_user__role" style="background-color: #8ca884">
										Student
									</div>
								</li>
								<li class="activity-state_user">
									<div class="activity-state_user__avatar">
										<a href="#">
											<img class="avatar" src="http://localhost:8888/bb/wp-content/uploads/avatars/23/5cd020262f7c6-bpfull.jpg" alt="" />
											<div class="activity-state_user__reaction">
												<img src="https://s.w.org/images/core/emoji/14.0.0/svg/1f644.svg" alt="Confused" />
											</div>
										</a>
									</div>
									<div class="activity-state_user__name">
										<a href="#">Adele</a>
									</div>
									<div class="activity-state_user__role" style="background-color: #ca84a3">
										Coach
									</div>
								</li>
								<li class="activity-state_user">
									<div class="activity-state_user__avatar">
										<a href="#">
											<img class="avatar" src="http://localhost:8888/bb/wp-content/uploads/avatars/16/5cca6f227a1db-bpfull.png" alt="" />
											<div class="activity-state_user__reaction">
												<i class="bb-icon-thumbs-down" style="font-weight:200;color:#d33f3f;"></i>
											</div>
										</a>
									</div>
									<div class="activity-state_user__name">
										<a href="#">Arianna</a>
									</div>
									<div class="activity-state_user__role" style="background-color: #8ca884">
										Student
									</div>
								</li>									
								<li class="activity-state_user">
									<div class="activity-state_user__avatar">
										<a href="#">
											<img class="avatar" src="http://localhost:8888/bb/wp-content/uploads/avatars/14/5cca67647676d-bpfull.jpg" alt="" />
											<div class="activity-state_user__reaction">
												<img src="https://s.w.org/images/core/emoji/14.0.0/svg/1f622.svg" alt="Sad">
											</div>
										</a>
									</div>
									<div class="activity-state_user__name">
										<a href="#">Robert</a>
									</div>
									<div class="activity-state_user__role">
										Admin
									</div>
								</li>
							</ul>	
						</div>
					</div>
				</div>
		</div>
	</div>
</script>

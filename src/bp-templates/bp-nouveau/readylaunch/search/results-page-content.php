<?php
/**
 * Template for displaying the search results of the page content
 *
 * the template file to display content search result page
 * instead create a folder 'buddyboss-global-search' inside your theme, copy this file over there, and make changes there
 *
 * This template can be overridden by copying it to yourtheme/buddypress/search/results-page-content.php.
 *
 * @package BuddyBoss\Core
 * @since   BuddyBoss 1.0.0
 * @version 1.0.0
 */

global $bb_rl_search_nav;

$no_results_class = ! BP_Search::instance()->has_search_results() ?  'bp-search-no-results' : '';
?>

<div class="bp-search-page buddypress-wrap bp-dir-hori-nav">

	<div class="bp-search-results-wrapper dir-form <?php echo $no_results_class; ?>">

		<div id="bb-rl-search-modal" class="bb-rl-search-modal" style="display: none;">
			<transition name="modal">
				<div class="modal-mask bb-rl-modal-mask">
					<div class="bb-rl-modal-wrapper">
						<div class="bp-search-form-wrapper dir-search no-ajax">
							<?php bp_search_buffer_template_part('search-form');?>
						</div>
					</div>
				</div>
			</transition>
		</div>

		<div id="bb-rl-network-search-modal" class="bb-rl-network-search-modal bb-rl-search-modal" style="display: none;">
			<transition name="modal">
				<div class="modal-mask bb-rl-modal-mask">
					<div class="bb-rl-modal-wrapper">
						<div class="bp-search-form-wrapper dir-search no-ajax has-results">

							<form action="" method="get" class="bp-dir-search-form" id="search-form">
								<label for="search" class="bp-screen-reader-text"><?php esc_html_e( 'Search', 'buddyboss' ); ?></label>
								<div class="bb-rl-network-search-bar">
									<input id="search" name="s" type="search" value="" placeholder="<?php esc_attr_e( 'Search community', 'buddyboss' ); ?>">
									<button type="submit" id="search-submit" class="nouveau-search-submit">
										<span class="bb-icons-rl-magnifying-glass" aria-hidden="true"></span>
										<span id="button-text" class="bp-screen-reader-text"><?php esc_html_e( 'Search', 'buddyboss' ); ?></span>
									</button>
									<a href="" class="bb-rl-network-search-clear"><?php esc_html_e( 'Clear Search', 'buddyboss' ); ?></a>
									<div class="bb-rl-network-search-filter bb_more_options">
										<a class="bb-rl-filter-tag bb-rl-context-btn bb_more_options_action">
											<span class="search-filter-label"><?php esc_html_e( 'All', 'buddyboss' ); ?></span>
											<i class="bb-icons-rl-caret-down"></i>
										</a>
										<div class="bb-rl-search-options bb_more_options_list bb_more_dropdown">
											<div class="bb-rl-search-filter-option">
												<a href="#"><?php esc_html_e( 'Groups', 'buddyboss' ); ?></a>
											</div>
											<div class="bb-rl-search-filter-option">
												<a href="#"><?php esc_html_e( 'Forum Discussions', 'buddyboss' ); ?></a>
											</div>
											<div class="bb-rl-search-filter-option">
												<a href="#"><?php esc_html_e( 'Forum Replies', 'buddyboss' ); ?></a>
											</div>
											<div class="bb-rl-search-filter-option">
												<a href="#"><?php esc_html_e( 'Members', 'buddyboss' ); ?></a>
											</div>
										</div>
									</div>
								</div>
							</form>
							<div class="bb-rl-ac-results">
								<ul class="ac-results-list">
									<li class="bb-rl-search-post">
										<div class="item-avatar">
											<a href="#" title="" class="bb-rl-avatar-link">
												<img alt="" src="http://localhost/bb-buddyboss-theme-demo/wp-content/uploads/avatars/1/67f6a78532bc0-bpthumb.jpg" class="avatar" height="80" width="80">
											</a>
										</div>
										<div class="item-content">
											<div class="item-title">
												<a href="#" title="" class="bb-rl-author-link">John Travolta</a>
											</div>
											<div class="entry-content">The most versatile, and feature-rich engagement platform. Browse beautifully designed templates, effortlessly customize it to meet your specific requirements.</div>
											<div class="entry-meta">
												<span class="bb-rl-meta-item bb-rl-meta-followers">34 followers</span>
												<span class="bb-rl-meta-item bb-rl-meta-date">12 minutes ago</span>
											</div>
										</div>
									</li>
									<li class="bb-rl-search-post bb-rl-search-post--group">
										<div class="item-avatar">
											<a href="#" title="" class="bb-rl-avatar-link">
												<img alt="" src="http://localhost/bb-buddyboss-theme-demo/wp-content/uploads/avatars/1/67f6a78532bc0-bpthumb.jpg" class="avatar" height="80" width="80">
											</a>
										</div>
										<div class="item-content">
											<div class="item-title">
												<a href="#" title="" class="bb-rl-author-link">Innovation Club</a>
											</div>
											<div class="entry-content">The most versatile, and feature-rich engagement platform. Browse beautifully designed templates, effortlessly customize it to meet your specific requirements.</div>
											<div class="entry-meta">
												<span class="bb-rl-meta-item bb-rl-meta-author">Posted by John Muller</span>
												<span class="bb-rl-meta-item bb-rl-meta-date">12 minutes ago</span>
											</div>
										</div>
									</li>
								</ul>
							</div>

						</div>
					</div>
				</div>
			</transition>
		</div>

		<div class="search_results">
			<?php do_action( 'bp_search_before_result' ); ?>
			<?php bp_search_results();?>
			<?php do_action( 'bp_search_after_result' ); ?>
		</div>

	</div>

</div><!-- .bp-search-page -->

<?php
/**
 * The layout for templates
 */
?>
<!doctype html>
<html <?php language_attributes(); ?>>

<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">

	<?php wp_head(); ?>
	<script type="text/template" id="member-template">
		<li class="item-entry odd is-online is-current-user" data-bp-item-id="<%= id %>" data-bp-item-component="members">
			<div class="list-wrap footer-buttons-on follow-active no-secondary-buttons no-primary-buttons				">
				<div class="list-wrap-inner">
					<div class="item-avatar">
						<a href="<%= url %>" class="">
							<span class="member-status online" data-bb-user-id="2" data-bb-user-presence="online"></span>
							<img src="<%= avatar %>" class="avatar user-2-avatar avatar-300 photo" width="300" height="300" alt="Profile photo of <%= name %>">
						</a>
					</div>
					<div class="item">
						<div class="item-block">
							<p class="item-meta member-type only-grid-view"><span class="bp-member-type bb-current-member-"><%= type %></span></p>
							<h2 class="list-title member-name">
								<a href="<%= url %>"><%= name %></a>
							</h2>
							<p class="item-meta member-type only-list-view"><span class="bp-member-type bb-current-member-"><%= type %></span></p>
							<p class="item-meta last-activity">Joined Apr 2019<span class="separator">•</span>Active now</p>
						</div>
						<div class="flex align-items-center follow-container justify-center">
							<div class="followers-wrap"><strong><%= followers %></strong> followers</div>
						</div>
					</div>
					<!-- // .item -->
					<div class="member-buttons-wrap">
					</div>
					<!-- .member-buttons-wrap -->
				</div>
				<div class="bp-members-list-hook">
					<div class="bp-members-list-hook-inner">
					</div>
				</div>
			</div>
		</li>
	</script>
	<script>
		jQuery(document).ready(function($) {
			var options    = {};
			options.path   = 'buddyboss/v1/members/'
			options.method = 'GET';
			options.data   = {
				page: 1,
				per_page: 20,
			};

			bp.apiRequest( options ).done(
				function ( data ) {
					// Get the container and template
					var container = $('#members-list');
					var template = _.template($('#member-template').html());

					container.empty(); // Clear previous data if any

					// Loop through the data and apply the template
					data.forEach(function(member) {
						var memberHTML = template({
							avatar: member.avatar_urls.full,
							name: member.name,
							id: member.id,
							url: member.link,
							followers: member.followers,
							type: 'Member',
						});

						container.append(memberHTML);
					});
				}
			).fail(
				function () {
					console.log('error');
				}
			);
		});
	</script>
</head>

<body <?php body_class( 'bb-reaylaunch-template' ); ?>>
	<?php wp_body_open(); ?>
	<div id="page" class="site app-layout">
	<header id="masthead" class="site-header-1">
		HEADER
	</header><!-- #masthead -->

	<main id="primary" class="site-main">
		<?php
		if ( have_posts() ) :
			/* Start the Loop */
			while ( have_posts() ) :
				the_post();

//				 the_content();
			endwhile;
		endif;
		?>
		<div id="buddypress" class="buddypress-wrap bp-single-plain-nav bp-dir-hori-nav">
			<div id="members-dir-list" class="members dir-list">
				<ul id="members-list" class="item-list members-list bp-list grid">
					<li class="item-entry odd is-online is-current-user">Loading...</li>
				</ul>
			</div>
		</div>
	</main>
	<?php wp_footer(); ?>
</body>

</html>

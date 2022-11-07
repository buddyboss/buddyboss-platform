<?php
/**
 * BuddyBoss App Admin Screen.
 *
 * This file contains information about BuddyBoss App. The BuddyBoss application to create a native mobile app with your WordPress site.
 *
 * @package BuddyBoss
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<div class="wrap bp-about-wrap">

	<div class="bp-admin-card">
		<h2><?php _e( 'Meet the BuddyBoss Team', 'buddyboss' ); ?></h2>
		<ul class="wp-people-group " id="wp-people-group-core-team">
			<li class="wp-person" id="wp-person-michaeleisenwasser">
				<a class="web" href="https://www.linkedin.com/in/michaeleisenwasser/"><?php echo '<img alt="" class="gravatar" src="' . buddypress()->plugin_url . 'bp-core/images/admin/credits-michael.png' . '" />'; ?>
				Michael Eisenwasser</a>
				<span class="title"><?php _e( 'Software Development', 'buddyboss' ); ?></span>

			</li>
			<li class="wp-person" id="wp-person-tomcheddadi">
				<a class="web" href="https://www.linkedin.com/in/tom-o-cheddadi-ba6939a3/"><?php echo '<img alt="" class="gravatar" src="' . buddypress()->plugin_url . 'bp-core/images/admin/credits-tom.png' . '" />'; ?>
				Tom Cheddadi</a>
				<span class="title"><?php _e( 'Marketing and Strategy', 'buddyboss' ); ?></span>
			</li>
			<li class="wp-person" id="wp-person-buddyboss">
				<a class="web" href="https://www.buddyboss.com/careers/"><?php echo '<img alt="" class="gravatar" src="' . buddypress()->plugin_url . 'bp-core/images/admin/credits-buddyboss.png' . '" />'; ?>
				BuddyBoss Team</a>
				<span class="title"><?php esc_html_e( 'Over 100 and Growing!', 'buddyboss' ); ?></span>
			</li>
		</ul>
	</div>

	<div class="bp-admin-card">
		<h2><?php _e( 'Special thanks to the BuddyPress contributors', 'buddyboss' ); ?></h2>
		<p class="wp-about-description">
			<?php
			printf(
				__( 'The <strong>BuddyBoss Platform</strong> is a fork of the open source project <strong><a class="web" href="%s">BuddyPress</a></strong>. We cannot thank the core BuddyPress team enough for their many years of contributing to the original plugin:', 'buddyboss' ),
				esc_url( 'https://buddypress.org/' )
			);
			?>
			<a class="web" href="https://profiles.wordpress.org/johnjamesjacoby">John James Jacoby</a>,
			<a class="web" href="https://profiles.wordpress.org/boonebgorges">Boone B. Gorges</a>,
			<a class="web" href="https://profiles.wordpress.org/djpaul">Paul Gibbs</a>,
			<a class="web" href="https://profiles.wordpress.org/r-a-y">Ray</a>,
			<a class="web" href="https://profiles.wordpress.org/hnla">Hugo Ashmore</a>,
			<a class="web" href="https://profiles.wordpress.org/imath">Mathieu Viet</a>,
			<a class="web" href="https://profiles.wordpress.org/mercime">Mercime</a>,
			<a class="web" href="https://profiles.wordpress.org/dcavins">David Cavins</a>,
			<a class="web" href="https://profiles.wordpress.org/tw2113">Michael Beckwith</a>,
			<a class="web" href="https://profiles.wordpress.org/henry.wright">Henry Wright</a>,
			<a class="web" href="https://profiles.wordpress.org/danbp">danbp</a>,
			<a class="web" href="https://profiles.wordpress.org/shanebp">shanebp</a>,
			<a class="web" href="https://profiles.wordpress.org/r-a-y">Slava Abakumov</a>,
			<a class="web" href="https://profiles.wordpress.org/Offereins">Laurens Offereins</a>,
			<a class="web" href="https://profiles.wordpress.org/netweb">Stephen Edgar</a>,
			<a class="web" href="https://profiles.wordpress.org/espellcaste">Renato Alves</a>,
			<a class="web" href="https://profiles.wordpress.org/venutius">Venutius</a>,
			<a class="web" href="https://profiles.wordpress.org/apeatling/">Andy Peatling</a>,
			<a class="web" href="https://profiles.wordpress.org/burtadsit">Burt Adsit</a>,
			<a class="web" href="https://profiles.wordpress.org/jeffsayre">Jeff Sayre</a>,
			<a class="web" href="https://profiles.wordpress.org/karmatosed">Tammie Lister</a>,
			<a class="web" href="https://profiles.wordpress.org/modemlooper">modemlooper</a>
		</p>
	</div>

	<div class="bp-admin-card">
		<h2><?php _e( 'Special thanks to these open source projects', 'buddyboss' ); ?></h2>
		<p class="wp-credits-list">
			<a href="https://github.com/ichord/At.js">At.js</a>,
			<a href="https://bbpress.org">bbPress</a>,
			<a href="https://buddypress.org">BuddyPress</a>,
			<a href="https://wordpress.org/plugins/bp-default-data/">BP Default Data</a>,
			<a href="https://wordpress.org/plugins/buddypress-followers/">BP Follow</a>,
			<a href="https://wordpress.org/plugins/buddypress-global-search/">BP Global Search</a>,
			<a href="https://wordpress.org/plugins/bp-profile-search/">BP Profile Search</a>,
			<a href="https://github.com/ichord/Caret.js">Caret.js</a>,
			<a href="https://tedgoas.github.io/Cerberus/">Cerberus</a>,
			<a href="https://wordpress.org/plugins/gs-only-pdf-preview/">GS Only PDF Preview</a>,
			<a href="https://ionicons.com/">Ionicons</a>,
			<a href="https://github.com/carhartl/jquery-cookie">jquery.cookie</a>,
			<a href="https://mattbradley.github.io/livestampjs/">Livestamp.js</a>,
			<a href="https://www.mediawiki.org/wiki/MediaWiki">MediaWiki</a>,
			<a href="https://wordpress.org/plugins/menu-icons/">Menu Icons</a>,
			<a href="https://momentjs.com/">Moment.js</a>,
			<a href="https://wordpress.org/plugins/user-switching/">User Switching</a>,
			<a href="https://wordpress.org">WordPress</a>.
		</p>
	</div>

</div>

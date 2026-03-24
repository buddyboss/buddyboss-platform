<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Registers pre-built email block patterns for the Broadcast email builder.
 */
class Broadcast_Camp_Patterns {

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_category' ), 9 );
		add_action( 'init', array( __CLASS__, 'register_patterns' ), 10 );
	}

	public static function register_category() {
		register_block_pattern_category( 'broadcast-camp-emails', array(
			'label' => __( 'Campaign Email Layouts', 'broadcast' ),
		) );
	}

	public static function register_patterns() {
		// ── Newsletter Header ─────────────────────────────────────────────────
		register_block_pattern( 'broadcast-camp/newsletter-header', array(
			'title'      => __( 'Newsletter Header', 'broadcast' ),
			'categories' => array( 'broadcast-camp-emails' ),
			'content'    => '<!-- wp:group {"style":{"color":{"background":"#1e293b"},"spacing":{"padding":{"top":"24px","bottom":"24px","left":"24px","right":"24px"}}},"textColor":"white","className":"bb-email-header"} -->
<div class="wp-block-group has-white-color has-text-color bb-email-header" style="background-color:#1e293b;padding-top:24px;padding-right:24px;padding-bottom:24px;padding-left:24px">
<!-- wp:columns {"verticalAlignment":"center"} -->
<div class="wp-block-columns are-vertically-aligned-center">
<!-- wp:column {"verticalAlignment":"center","width":"50%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:50%">
<!-- wp:heading {"level":2,"style":{"typography":{"fontSize":"22px"}},"textColor":"white"} -->
<h2 class="wp-block-heading has-white-color has-text-color" style="font-size:22px">Your Site Name</h2>
<!-- /wp:heading -->
</div>
<!-- /wp:column -->
<!-- wp:column {"verticalAlignment":"center","width":"50%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:50%">
<!-- wp:paragraph {"align":"right","textColor":"white","style":{"typography":{"fontSize":"13px"}}} -->
<p class="has-text-align-right has-white-color has-text-color" style="font-size:13px"><a href="#">Home</a>  ·  <a href="#">Products</a>  ·  <a href="#">Contact</a></p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->
</div>
<!-- /wp:group -->',
		) );

		// ── Welcome Email ─────────────────────────────────────────────────────
		register_block_pattern( 'broadcast-camp/welcome-email', array(
			'title'      => __( 'Welcome Email', 'broadcast' ),
			'categories' => array( 'broadcast-camp-emails' ),
			'content'    => '<!-- wp:group {"style":{"color":{"background":"#2271b1"},"spacing":{"padding":{"top":"40px","bottom":"40px","left":"24px","right":"24px"}}}} -->
<div class="wp-block-group" style="background-color:#2271b1;padding-top:40px;padding-right:24px;padding-bottom:40px;padding-left:24px">
<!-- wp:heading {"textAlign":"center","level":1,"style":{"typography":{"fontSize":"32px"}},"textColor":"white"} -->
<h1 class="wp-block-heading has-text-align-center has-white-color has-text-color" style="font-size:32px">Welcome, {{first_name}}! 👋</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center","textColor":"white"} -->
<p class="has-text-align-center has-white-color has-text-color">We\'re thrilled to have you on board. Here\'s everything you need to get started.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:group {"style":{"spacing":{"padding":{"top":"32px","bottom":"32px","left":"24px","right":"24px"}}}} -->
<div class="wp-block-group" style="padding-top:32px;padding-right:24px;padding-bottom:32px;padding-left:24px">
<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">Getting Started</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Your account is ready to go. Here are a few things you can do right now to make the most of your membership:</p>
<!-- /wp:paragraph -->
<!-- wp:list -->
<ul class="wp-block-list"><li>Complete your profile</li><li>Explore the community</li><li>Join a group that interests you</li></ul>
<!-- /wp:list -->
<!-- wp:spacer {"height":"24px"} -->
<div style="height:24px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->
<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons">
<!-- wp:button {"backgroundColor":"vivid-cyan-blue","textColor":"white"} -->
<div class="wp-block-button"><a class="wp-block-button__link has-white-color has-vivid-cyan-blue-background-color has-text-color has-background wp-element-button">Go to Your Dashboard →</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
</div>
<!-- /wp:group -->',
		) );

		// ── Newsletter Digest ─────────────────────────────────────────────────
		register_block_pattern( 'broadcast-camp/newsletter-digest', array(
			'title'      => __( 'Newsletter Digest', 'broadcast' ),
			'categories' => array( 'broadcast-camp-emails' ),
			'content'    => '<!-- wp:group {"style":{"spacing":{"padding":{"top":"32px","bottom":"8px","left":"24px","right":"24px"}}}} -->
<div class="wp-block-group" style="padding-top:32px;padding-right:24px;padding-bottom:8px;padding-left:24px">
<!-- wp:heading {"level":1,"style":{"typography":{"fontSize":"28px"}}} -->
<h1 class="wp-block-heading" style="font-size:28px">This Week\'s Highlights</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"style":{"color":{"text":"#6b7280"}}} -->
<p class="has-text-color" style="color:#6b7280">Here\'s a roundup of the best content from the past week.</p>
<!-- /wp:paragraph -->
<!-- wp:separator -->
<hr class="wp-block-separator has-alpha-channel-opacity"/>
<!-- /wp:separator -->
</div>
<!-- /wp:group -->
<!-- wp:group {"style":{"spacing":{"padding":{"top":"0","bottom":"24px","left":"24px","right":"24px"}}}} -->
<div class="wp-block-group" style="padding-top:0;padding-right:24px;padding-bottom:24px;padding-left:24px">
<!-- wp:columns -->
<div class="wp-block-columns">
<!-- wp:column {"width":"60%"} -->
<div class="wp-block-column" style="flex-basis:60%">
<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">📌 Featured Story</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Write your featured story headline and summary here. Keep it concise and compelling to drive click-through.</p>
<!-- /wp:paragraph -->
<!-- wp:buttons -->
<div class="wp-block-buttons">
<!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button">Read More →</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
</div>
<!-- /wp:column -->
<!-- wp:column {"width":"40%"} -->
<div class="wp-block-column" style="flex-basis:40%">
<!-- wp:image {"sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full"><img alt="Featured story image"/></figure>
<!-- /wp:image -->
</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->
<!-- wp:separator -->
<hr class="wp-block-separator has-alpha-channel-opacity"/>
<!-- /wp:separator -->
<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">More This Week</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>• <a href="#">Article title one</a> — Brief description of the article.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p>• <a href="#">Article title two</a> — Brief description of the article.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p>• <a href="#">Article title three</a> — Brief description of the article.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->',
		) );

		// ── Simple Announcement ───────────────────────────────────────────────
		register_block_pattern( 'broadcast-camp/announcement', array(
			'title'      => __( 'Simple Announcement', 'broadcast' ),
			'categories' => array( 'broadcast-camp-emails' ),
			'content'    => '<!-- wp:group {"style":{"spacing":{"padding":{"top":"40px","bottom":"40px","left":"24px","right":"24px"}}}} -->
<div class="wp-block-group" style="padding-top:40px;padding-right:24px;padding-bottom:40px;padding-left:24px">
<!-- wp:heading {"textAlign":"center","level":1} -->
<h1 class="wp-block-heading has-text-align-center">Big News from {{site_name}}</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center","style":{"color":{"text":"#6b7280"}}} -->
<p class="has-text-align-center has-text-color" style="color:#6b7280">Hi {{first_name}}, we have something exciting to share with you.</p>
<!-- /wp:paragraph -->
<!-- wp:spacer {"height":"24px"} -->
<div style="height:24px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->
<!-- wp:image {"align":"center","sizeSlug":"large","linkDestination":"none"} -->
<figure class="wp-block-image aligncenter size-large"><img alt="Announcement image"/></figure>
<!-- /wp:image -->
<!-- wp:spacer {"height":"24px"} -->
<div style="height:24px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->
<!-- wp:paragraph -->
<p>Write your announcement here. Be clear and concise. Tell your readers what\'s happening, why it matters to them, and what they should do next.</p>
<!-- /wp:paragraph -->
<!-- wp:spacer {"height":"16px"} -->
<div style="height:16px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->
<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons">
<!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button">Learn More</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
</div>
<!-- /wp:group -->',
		) );

		// ── Email Footer ──────────────────────────────────────────────────────
		register_block_pattern( 'broadcast-camp/email-footer', array(
			'title'      => __( 'Email Footer', 'broadcast' ),
			'categories' => array( 'broadcast-camp-emails' ),
			'content'    => '<!-- wp:separator -->
<hr class="wp-block-separator has-alpha-channel-opacity"/>
<!-- /wp:separator -->
<!-- wp:group {"style":{"color":{"background":"#f9fafb"},"spacing":{"padding":{"top":"24px","bottom":"24px","left":"24px","right":"24px"}}}} -->
<div class="wp-block-group" style="background-color:#f9fafb;padding-top:24px;padding-right:24px;padding-bottom:24px;padding-left:24px">
<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"12px"},"color":{"text":"#9ca3af"}}} -->
<p class="has-text-align-center has-text-color" style="font-size:12px;color:#9ca3af">You\'re receiving this email because you\'re a member of {{site_name}}.<br>To unsubscribe, <a href="{{unsubscribe_url}}">click here</a>.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"12px"},"color":{"text":"#9ca3af"}}} -->
<p class="has-text-align-center has-text-color" style="font-size:12px;color:#9ca3af">© {{site_name}} — All rights reserved.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->',
		) );

		// ── Promotional Offer ─────────────────────────────────────────────────
		register_block_pattern( 'broadcast-camp/promotional', array(
			'title'      => __( 'Promotional Offer', 'broadcast' ),
			'categories' => array( 'broadcast-camp-emails' ),
			'content'    => '<!-- wp:group {"style":{"color":{"background":"#fef3c7"},"spacing":{"padding":{"top":"8px","bottom":"8px","left":"24px","right":"24px"}}}} -->
<div class="wp-block-group" style="background-color:#fef3c7;padding-top:8px;padding-right:24px;padding-bottom:8px;padding-left:24px">
<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"13px","fontWeight":"600"},"color":{"text":"#92400e"}}} -->
<p class="has-text-align-center has-text-color" style="font-size:13px;font-weight:600;color:#92400e">🎉 Limited Time Offer — Ends Soon</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:group {"style":{"spacing":{"padding":{"top":"40px","bottom":"40px","left":"24px","right":"24px"}}}} -->
<div class="wp-block-group" style="padding-top:40px;padding-right:24px;padding-bottom:40px;padding-left:24px">
<!-- wp:heading {"textAlign":"center","level":1} -->
<h1 class="wp-block-heading has-text-align-center">Exclusive Offer Just for You</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Hi {{first_name}}, as a valued member of {{site_name}}, we have a special offer you won\'t want to miss.</p>
<!-- /wp:paragraph -->
<!-- wp:spacer {"height":"24px"} -->
<div style="height:24px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->
<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons">
<!-- wp:button {"style":{"color":{"background":"#10b981","text":"#ffffff"}}} -->
<div class="wp-block-button"><a class="wp-block-button__link has-text-color has-background wp-element-button" style="color:#ffffff;background-color:#10b981">Claim Your Discount →</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
</div>
<!-- /wp:group -->',
		) );
	}
}

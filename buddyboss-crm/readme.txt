=== BuddyBoss CRM ===
Contributors: tomjutla
Tags: buddyboss, crm, tagging, automation, community
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Comprehensive CRM system for BuddyBoss Platform with user tagging, automation workflows, and broadcast messaging.

== Description ==

BuddyBoss CRM extends BuddyBoss Platform with powerful community relationship management features:

= Key Features =

**User Tagging System**
* Visual tags with custom colors and icons
* Organize tags into categories
* Create dynamic user lists based on tag combinations
* Auto-expiring tags for time-based management
* Bulk tag operations for efficiency

**Automation Workflows**
* Trigger-based automation (tag added/removed events)
* Conditional logic for complex scenarios
* Multiple action types (assign tags, send emails, webhooks)
* Queue-based execution for performance
* Complete audit trail

**Broadcast Messaging**
* Send targeted messages to user segments
* Integration with tag-based lists
* Template management
* Delivery tracking and analytics

**Performance Optimized**
* Designed for communities with 100,000+ users
* Aggressive caching with smart invalidation
* Indexed database queries
* Async processing for heavy operations

**BuddyBoss Native Integration**
* Seamless integration with BuddyBoss admin UI
* Follows BuddyBoss design patterns
* Works with BuddyBoss Profile Types
* Compatible with BuddyBoss components

= Requirements =

* WordPress 6.0 or higher
* PHP 7.4 or higher
* BuddyBoss Platform (required)

= Documentation =

Full documentation available in the `/Resources` directory of the plugin.

== Installation ==

1. Upload the `buddyboss-crm` folder to the `/wp-content/plugins/` directory
2. Ensure BuddyBoss Platform is installed and activated
3. Activate the BuddyBoss CRM plugin through the 'Plugins' menu in WordPress
4. Navigate to BuddyBoss > CRM to configure settings

== Frequently Asked Questions ==

= Does this work without BuddyBoss Platform? =

No, BuddyBoss CRM requires BuddyBoss Platform to function.

= Can I migrate from BuddyBoss Profile Types? =

Yes! The plugin includes a one-click migration tool to convert Profile Types to User Tags.

= How many users can the system handle? =

The system is optimized for communities with 100,000 - 500,000 users.

= Are tags visible to members? =

Tags have configurable visibility: public, members-only, admin-only, or self-only.

== Changelog ==

= 1.0.0 =
* Initial release
* User tagging system
* Tag categories and lists
* Automation workflows (planned)
* Broadcast messaging (planned)

== Upgrade Notice ==

= 1.0.0 =
Initial release of BuddyBoss CRM.

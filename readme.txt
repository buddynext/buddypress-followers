=== BuddyPress Follow ===
Contributors: apeatling, r-a-y, vapvarun
Tags: buddypress, following, followers, connections, social
Requires at least: WordPress 5.0, BuddyPress 14.4
Tested up to: WordPress 6.7, BuddyPress 15.0
Stable tag: 2.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==

Add Twitter-style follow functionality to your BuddyPress community. Users can follow each other without requiring mutual friendship acceptance.

**Core Features:**

* **Follow/Unfollow** - One-way follower relationships (just like Twitter)
* **Profile Tabs** - Following & Followers lists on user profiles
* **Follow Buttons** - On profiles, member directory, and activity streams
* **AJAX Updates** - Real-time follow/unfollow without page reload
* **Activity Filtering** - "Following" tab to see activity from followed users
* **Notifications** - BuddyPress and email notifications for new followers
* **Customizable Emails** - Uses BuddyPress Core email system with HTML templates (Dashboard > Emails)
* **Gutenberg Blocks** - "Users I'm Following" and "My Followers" blocks for pages/posts
* **Widgets** - "Following" and "Followers" widgets for sidebars
* **WP-CLI Commands** - Manage follows from command line
* **REST API** - Complete RESTful API with v1/v2 auto-detection
* **WP Toolbar** - Quick access menu items

**Modern Architecture:**

* PSR-4 autoloading with namespaces
* Service layer pattern with dependency injection
* Comprehensive hooks and filters for developers
* Theme compatibility layer
* WordPress 6.7+ translation loading compliance

**Translations**

BP Follow has been translated into the following languages by these awesome people:

* Brazilian Portuguese - [espellcaste](https://profiles.wordpress.org/espellcaste)
* French - [lauranshow](https://profiles.wordpress.org/lauranshow)
* German - [solhuebner](https://profiles.wordpress.org/solhuebner)
* Spanish - [saik003](https://github.com/saik003/buddypress-followers)

For bug reports or to add patches or translation files, visit the [BP Follow Github page](https://github.com/r-a-y/buddypress-followers).

== Installation ==

1. Download, install and activate the plugin.
1. To follow a user, simply visit their profile and hit the follow button under their name.


== Frequently Asked Questions ==

Check out the [BP Follow wiki](https://github.com/r-a-y/buddypress-followers/wiki).

== For Developers ==

**Helper Functions:**

`bp_follow_start_following()` - Follow a user
`bp_follow_stop_following()` - Unfollow a user
`bp_follow_is_following()` - Check if following
`bp_follow_get_followers()` - Get followers list
`bp_follow_get_following()` - Get following list
`bp_follow_get_counts()` - Get follower/following counts
`bp_follow_add_follow_button()` - Display follow button

**Service Layer:**

Access services via dependency injection:
`$service = bp_follow_service( '\\Followers\\Service\\FollowService' );`

Available services: FollowService, ButtonService, AjaxService, NotificationService, EmailService

**REST API Endpoints:**

Auto-detects v1 (BP < 15.0) or v2 (BP >= 15.0):

* `GET /wp-json/buddypress/v1/follow/{user_id}/followers` - Get followers
* `GET /wp-json/buddypress/v1/follow/{user_id}/following` - Get following
* `GET /wp-json/buddypress/v1/follow/{user_id}/counts` - Get counts
* `POST /wp-json/buddypress/v1/follow/{leader_id}` - Follow user (auth required)
* `DELETE /wp-json/buddypress/v1/follow/{leader_id}` - Unfollow user (auth required)

**Hooks:**

Actions: `bp_follow_start_following`, `bp_follow_stop_following`, `bp_follow_loaded`, `bp_follow_setup_nav`

Filters: `bp_follow_get_add_follow_button`, `bp_follow_following_nav_position`, `bp_follow_enable_users`, `bp_follow_enable_activity`

See README.md for complete documentation.

== Changelog ==

= 2.0.0 =
**New Features**
* **Gutenberg Block Editor Support** - Add "Users I'm Following" and "My Followers" blocks to pages/posts with customizable settings
* **New "My Followers" Widget** - Display users who follow you in any widget area
* **Email Digest System** - Reduce email fatigue with daily/weekly digests of new followers. Users choose instant or digest mode from Settings > Notifications with automatic cron scheduling
* **WP-CLI Commands** - Manage follows via command line: bulk operations, statistics, send digests, and sync counts (8 commands total)
* **BuddyPress Core Email System** - Migrated to BP email system with customizable HTML templates (Dashboard > Emails). Templates use tokens like {{follower.name}} and automatically install on activation

**Improvements**
* **Better Follow Buttons** - AJAX follow/unfollow works smoothly in all locations (member directory, profiles, followers/following lists)
* **Fixed Page Not Found Errors** - Resolved 404 errors on followers/following pages
* **Modern Grid Layout** - Beautiful grid display with BP Nouveau theme
* **Faster Performance** - 15% code reduction with cleaner architecture
* **WordPress 6.7+ Compatible** - Fixed translation loading for latest WordPress

**For Developers**
* Modern PHP architecture with PSR-4 autoloading and namespaced classes
* Service layer pattern with dependency injection container
* REST API v1/v2 auto-detection (BP 14.4-15.0+ compatibility)
* Comprehensive hooks and filters for customization
* Removed legacy multisite/blog following support (focused on user following)
* Improved file structure: `_inc/` → `includes/`, organized by feature
* Comprehensive PHPDoc comments and translator comments
* Updated minimum requirements: PHP 7.4+, BP 14.4+

= 1.2.2 =
* Fix deprecated notice in widget for those using WordPress 4.3+.
* Fix member filtering when custom follow slugs are in use.
* Increase selector scope in javascript so AJAX button works with pagination in member loops.
* Fix issue with bp_follow_stop_following() when relationship doesn't exist.
* Fix issue with member loop existence and follow user button defaults.
* Only show "Following" tab if user is logged in on member directory.
* Do not query for follow button if a user is on their own profile.
* Decode special characters in email subject and content.
* Do not an email notification to yourself.
* Allow plugins to bail out of saving a follow relationship into the database.

= 1.2.1 =
* Add "Mark as read" support for the Notifications component (only available on BP 1.9+)
* Add "Activity > Following" RSS feed support (only available on BP 1.8+)
* Allow users to immediately unfollow / follow a user after clicking on the "Follow" button
* Dynamically update follow count on profile navigation tabs after clicking on the "Follow" button
* Change follow button text to remove the username by popular request
* Add Brazilian Portuguese translation (props espellcaste)
* Add German translation (props solhuebner)
* Streamline javascript to use event delegation
* Fix various PHP warnings

= 1.2 =
* Add BuddyPress 1.7 theme compatibility
* Add AJAX filtering to a user's "Following" and "Followers" pages
* Refactor plugin to use BP 1.5's component API
* Bump version requirements to use at least BP 1.5 (BP 1.2 is no longer supported)
* Deprecate older templates and use newer format (/buddypress/members/single/follow.php)
* Add ability to change the widget title
* Thanks to the Hamilton-Wentworth District School Board for sponsoring this release

= 1.1.1 =
* Show the following / followers tabs even when empty.
* Add better support for WP Toolbar.
* Add better support for parent / child themes.
* Fix issues with following buttons when javascript is disabled.
* Fix issues with following activity overriding other member activity pages.
* Fix issue when a user has already been notified of their new follower.
* Fix issue when a user has disabled new follow notifications.
* Adjust some hooks so 3rd-party plugins can properly run their code.

= 1.1 =
* Add BuddyPress 1.5 compatibility.
* Add WP Admin Bar support.
* Add localization support.
* Add AJAX functionality to all follow buttons.
* Add follow button to group members page.
* Fix following count when a user is deleted.
* Fix dropdown activity filter for following tabs.
* Fix member profile following pagination
* Fix BuddyBar issues when a logged-in user is on another member's page.
* Thanks to mrjarbenne for sponsoring this release.

= 1.0 =
* Initial release.
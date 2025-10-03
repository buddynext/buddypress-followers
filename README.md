# BuddyPress Followers

Add Twitter/Instagram-style follow functionality to your BuddyPress community! Let members follow each other to stay connected without requiring mutual friendship.

## Features

### Core Functionality
- **One-Way Following** - Twitter-style follow system (no mutual approval needed)
- **Following & Followers Tabs** - Dedicated profile tabs showing connections
- **Follow Counts** - Display follower and following counts on profiles
- **Instant Follow Buttons** - Click to follow/unfollow with AJAX (no page reload)
- **Activity Filtering** - See activity only from people you follow
- **Smart Notifications** - Get notified when someone follows you (BuddyPress + email)
- **BuddyPress Core Emails** - Uses BP's email system with customizable HTML templates (Dashboard > Emails)
- **Block Editor Support** - NEW! Add follower/following lists to any page with Gutenberg blocks
- **Widgets** - Display followers and following lists in sidebars
- **WP-CLI Commands** - Manage follows from command line (admins)
- **REST API** - Full API support for custom integrations

### User Interface
- Follow buttons appear everywhere: profiles, member directory, activity streams
- Beautiful grid layouts with BP Nouveau theme
- Following/Followers profile tabs
- WP Toolbar quick links
- Two widgets: "Users I'm Following" and "My Followers"
- Two Gutenberg blocks in "Community blocks" category

### For Developers
- Modern PHP 7.4+ with OOP architecture
- PSR-4 autoloading and namespaces
- Service layer pattern with dependency injection
- Comprehensive hooks and filters for customization
- REST API v1 & v2 auto-detection
- Composer support with code quality tools
- Full theme compatibility layer

## Requirements

- WordPress 5.0+
- BuddyPress 14.4.0+
- PHP 7.4+

## Installation

1. Upload the plugin files to `/wp-content/plugins/buddypress-followers/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. That's it! The follow functionality is automatically enabled

## File Structure

```
buddypress-followers/
├── buddypress-followers.php    # Main plugin file
├── autoload.php                 # PSR-4 autoloader
│
├── src/                        # Modern namespaced code
│   ├── Component.php           # Main BuddyPress component
│   ├── Container.php           # Dependency injection
│   ├── Service/                # Business logic
│   │   ├── FollowService.php
│   │   ├── ButtonService.php
│   │   ├── AjaxService.php
│   │   ├── NotificationService.php
│   │   └── EmailService.php
│   └── REST/
│       └── FollowController.php
│
├── includes/                   # Legacy procedural code
│   ├── class-follow.php        # BP_Follow model
│   ├── functions.php           # Helper functions
│   ├── class-updater.php       # Database updater
│   ├── activity/               # Activity integration
│   ├── user/                   # User features
│   └── compatibility/          # Legacy compatibility
│
├── assets/
│   └── js/
│       └── follow.js           # AJAX follow button
│
└── templates/
    └── buddypress/
        └── members/single/
            └── follow.php      # Template override
```

## Usage

### For Users

#### Follow a User
1. Visit any user's profile
2. Click the "Follow" button
3. You'll now see their activity in your "Following" feed

#### View Your Followers
1. Go to your profile
2. Click the "Followers" tab
3. See everyone following you

#### View Who You Follow
1. Go to your profile
2. Click the "Following" tab
3. See everyone you're following

### For Developers

#### Access Services

```php
// Get the follow service
$service = bp_follow_service( '\\Followers\\Service\\FollowService' );

// Follow a user
$service->follow( array(
    'leader_id'   => 123,
    'follower_id' => 456,
) );

// Unfollow a user
$service->unfollow( array(
    'leader_id'   => 123,
    'follower_id' => 456,
) );
```

#### Helper Functions

```php
// Start following
bp_follow_start_following( array(
    'leader_id'   => 123,
    'follower_id' => 456,
) );

// Stop following
bp_follow_stop_following( array(
    'leader_id'   => 123,
    'follower_id' => 456,
) );

// Check if following
$is_following = bp_follow_is_following( array(
    'leader_id'   => 123,
    'follower_id' => 456,
) );

// Get followers
$followers = bp_follow_get_followers( array(
    'user_id' => 123,
) );

// Get following
$following = bp_follow_get_following( array(
    'user_id' => 123,
) );

// Get counts
$counts = bp_follow_get_counts( array(
    'user_id' => 123,
) );
// Returns: array( 'followers' => 10, 'following' => 5 )
```

#### Display Follow Button

```php
bp_follow_add_follow_button( array(
    'leader_id'   => bp_displayed_user_id(),
    'follower_id' => bp_loggedin_user_id(),
) );
```

#### Hooks & Filters

**Actions:**

```php
// When user starts following
add_action( 'bp_follow_start_following', function( $follow ) {
    // $follow->leader_id - user being followed
    // $follow->follower_id - user doing the following
    error_log( "User {$follow->follower_id} followed {$follow->leader_id}" );
} );

// When user stops following
add_action( 'bp_follow_stop_following', function( $follow ) {
    error_log( "User {$follow->follower_id} unfollowed {$follow->leader_id}" );
} );

// After follow component loads
add_action( 'bp_follow_loaded', function() {
    // Initialize custom follow features
} );

// Setup navigation items
add_action( 'bp_follow_setup_nav', function( $main_nav, $sub_nav ) {
    // Modify navigation
}, 10, 2 );
```

**Filters:**

```php
// Customize follow button
add_filter( 'bp_follow_get_add_follow_button', function( $button ) {
    $button['link_text'] = 'Subscribe';
    return $button;
} );

// Change following nav position
add_filter( 'bp_follow_following_nav_position', function( $position ) {
    return 70; // Default: 61
} );

// Enable activity following (disabled by default)
add_filter( 'bp_follow_enable_activity', '__return_true' );

// Enable users following (enabled by default)
add_filter( 'bp_follow_enable_users', '__return_true' );
```

## REST API

The plugin provides a complete REST API with auto-detection of BuddyPress REST API versions (v1 for BP < 15.0, v2 for BP >= 15.0).

### Endpoints

#### Get Followers
```bash
GET /wp-json/buddypress/v1/follow/{user_id}/followers
```

**Parameters:**
- `user_id` (required) - User ID to get followers for

**Response:**
```json
{
  "followers": [123, 456, 789],
  "count": 3
}
```

#### Get Following
```bash
GET /wp-json/buddypress/v1/follow/{user_id}/following
```

**Parameters:**
- `user_id` (required) - User ID to get following for

**Response:**
```json
{
  "following": [111, 222, 333],
  "count": 3
}
```

#### Get Counts
```bash
GET /wp-json/buddypress/v1/follow/{user_id}/counts
```

**Parameters:**
- `user_id` (required) - User ID to get counts for

**Response:**
```json
{
  "followers": 10,
  "following": 5
}
```

#### Follow a User
```bash
POST /wp-json/buddypress/v1/follow/{leader_id}
```

**Parameters:**
- `leader_id` (required) - User ID to follow

**Authentication:** Required (logged-in user)

**Response:**
```json
{
  "success": true,
  "message": "You are now following this user."
}
```

#### Unfollow a User
```bash
DELETE /wp-json/buddypress/v1/follow/{leader_id}
```

**Parameters:**
- `leader_id` (required) - User ID to unfollow

**Authentication:** Required (logged-in user)

**Response:**
```json
{
  "success": true,
  "message": "You are no longer following this user."
}
```

### Authentication

The REST API supports BuddyPress authentication methods:

- **Cookie Authentication** - For same-origin requests
- **Application Passwords** - For external applications (WordPress 5.6+)

Example with cURL:
```bash
# Follow a user (requires authentication)
curl -X POST \
  -u username:application_password \
  https://example.com/wp-json/buddypress/v1/follow/123

# Get followers (public)
curl https://example.com/wp-json/buddypress/v1/follow/123/followers
```

## Architecture

### Service Layer

The plugin uses a service-oriented architecture with dependency injection:

```php
// Services are registered in the container
$container = buddypress()->follow->container();

// Access services
$follow_service = $container->get( '\\Followers\\Service\\FollowService' );
$button_service = $container->get( '\\Followers\\Service\\ButtonService' );
$ajax_service = $container->get( '\\Followers\\Service\\AjaxService' );
$notification_service = $container->get( '\\Followers\\Service\\NotificationService' );
$email_service = $container->get( '\\Followers\\Service\\EmailService' );
```

### Database Schema

The plugin creates a single table: `{prefix}_bp_follow`

```sql
CREATE TABLE {prefix}_bp_follow (
  id bigint(20) NOT NULL AUTO_INCREMENT,
  leader_id bigint(20) NOT NULL,
  follower_id bigint(20) NOT NULL,
  PRIMARY KEY (id),
  KEY leader_id (leader_id),
  KEY follower_id (follower_id)
);
```

### Cache Groups

The plugin uses WordPress object caching:

- `bp_follow_data` - Follow relationships and counts

## Widgets & Blocks

### Following Widget

Displays a list of users that the logged-in user is following.

**Widget Usage:**
1. Go to Appearance > Widgets
2. Add "(BP Follow) Users I'm Following" widget to a sidebar
3. Configure title and max users to display

**Block Usage (Gutenberg):**
1. In the block editor, click the (+) button
2. Search for "Users I'm Following"
3. Add the block and configure settings in the sidebar:
   - **Title**: Custom heading for the block
   - **Max members to show**: Number of users to display (1-50)
   - **User ID**: Leave as 0 for logged-in user, or enter a specific user ID to show that user's following list

### Followers Widget

Displays a list of users who follow the logged-in user.

**Widget Usage:**
1. Go to Appearance > Widgets
2. Add "(BP Follow) My Followers" widget to a sidebar
3. Configure title and max users to display

**Block Usage (Gutenberg):**
1. In the block editor, click the (+) button
2. Search for "My Followers"
3. Add the block and configure settings in the sidebar:
   - **Title**: Custom heading for the block
   - **Max members to show**: Number of users to display (1-50)
   - **User ID**: Leave as 0 for logged-in user, or enter a specific user ID to show that user's followers

## Theme Compatibility

### Template Override

To customize the follow template in your theme:

1. Copy `templates/buddypress/members/single/follow.php`
2. Paste to `{your-theme}/buddypress/members/single/follow.php`
3. Customize as needed

### Custom Styling

The follow buttons use BuddyPress default styling. To customize:

```css
/* Follow button */
.generic-button .follow-button {
    /* Your styles */
}

/* Unfollow button */
.generic-button .unfollow-button {
    /* Your styles */
}
```

## Troubleshooting

### Follow Button Not Showing

**Solution:** Ensure:
- User is logged in
- Viewing another user's profile (not your own)
- BuddyPress 14.4+ is active

### 404 on Following/Followers Pages

**Solution:** Flush rewrite rules
```bash
wp rewrite flush
```

Or go to Settings > Permalinks and click "Save Changes"

### REST API Returns 404

**Solution:**
1. Flush permalinks (see above)
2. Verify BuddyPress REST API is enabled
3. Check that BuddyPress 14.4+ is installed

### AJAX Not Working

**Solution:**
1. Check browser console for JavaScript errors
2. Verify `assets/js/follow.js` is loading
3. Ensure jQuery is enqueued

### Translation Loading Warning

If you see a warning about textdomain loading too early, ensure you're running the latest version of the plugin which properly loads translations on the `init` hook.

## Performance

### Caching

The plugin uses WordPress object caching for:
- Follow relationships
- Follower/following counts
- User follow status

To improve performance, use a persistent object cache like Redis or Memcached.

### Database Optimization

For sites with many followers:

1. Ensure indexes exist on `leader_id` and `follower_id`
2. Consider archiving old follow relationships if needed
3. Monitor slow queries and optimize as needed

## Security

- All AJAX actions are nonce-protected
- Follow actions require user authentication
- Input sanitization and validation on all user input
- Prepared statements for all database queries
- Capability checks on admin functions

## Changelog

### 2.0.0 - Major Update

**New Features**

- **Gutenberg Block Editor Support** - Add "Users I'm Following" and "My Followers" blocks to any page or post using the block editor. Find them in the "Community blocks" category! Blocks show followers or following for any user with avatars, names, last active time, customizable titles, and "See all" links.
- **New "My Followers" Widget** - Display users who follow you in any widget area, complementing the existing "Following" widget
- **WP-CLI Commands** - Site administrators can now manage follow relationships from the command line with bulk operations, statistics, and sync counts

**Improvements**

- **Better Follow Buttons** - Follow/unfollow buttons now work smoothly with AJAX in all locations: member directory, profile pages, followers list, following list, and friends tab
- **Fixed Page Not Found Errors** - Resolved 404 errors when clicking follow/unfollow buttons from followers and following pages
- **Modern Grid Layout** - Followers and following pages now display beautifully in grid format when using BP Nouveau theme
- **BuddyPress Core Email System** - Migrated from plain text emails to BuddyPress Core email system with customizable HTML templates. Users receive beautiful, styled emails when someone follows them. Site admins can customize the email template from Dashboard > Emails > "A member starts following your activity". Templates use tokens like {{follower.name}} and support both HTML and plain text versions. Email automatically installs on plugin activation.
- **Faster Performance** - Cleaner, more efficient code with 15% reduction in file size
- **WordPress 6.7+ Compatible** - Fixed translation loading for the latest WordPress version

**For Developers**

- Modern PHP architecture with PSR-4 autoloading and service layer pattern
- Dependency injection container for better code organization
- Comprehensive REST API with auto-detection for BuddyPress v1 & v2
- Composer support with code quality tools (PHPCS, PHPUnit, PHPCompatibility)
- Removed legacy multisite/blog following support (focused on user following only)
- Extensive hooks and filters for customization

## Support

For issues, questions, or feature requests, please use the GitHub issue tracker or WordPress.org support forums.

## License

GPLv2 or later - http://www.gnu.org/licenses/gpl-2.0.html

## Credits

- **Original Author:** Andy Peatling, r-a-y
- **Modernization:** Community contributors

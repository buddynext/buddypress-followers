# Email Digest Feature

## Overview

The Email Digest feature reduces email fatigue by batching follower notifications into daily or weekly summaries instead of sending individual emails for each new follower.

## User Experience

### Before (Without Digest)
- User gets 50 new followers → Receives 50 separate emails 😫
- Email inbox flooded with notifications

### After (With Digest Enabled)
- User gets 50 new followers → Receives 1 weekly digest email 😌
- "You have 50 new followers this week: Alice, Bob, Carol, and 47 others..."

## How to Enable

### For Users

1. Go to **Profile > Settings > Notifications** (`/members/USERNAME/settings/notifications/`)
2. Find the "Follow" section
3. Choose settings:
   - **A member starts following your activity**: Yes/No (master switch)
   - **Send follower notifications as digest**: Yes/No
   - **Digest frequency**: Daily or Weekly (appears when digest enabled)
4. Click "Save Settings"

### For Administrators

**Via WP-CLI:**
```bash
# Send digests manually (useful for testing)
wp bp follow send-digests

# Check cron schedule
wp cron event list

# View user's digest settings
wp user meta get USER_ID notification_follows_digest
wp user meta get USER_ID notification_follows_digest_frequency
```

## How It Works

### Technical Flow

1. **User A follows User B**
   - EmailService checks User B's digest preference
   - If digest disabled → Send instant email
   - If digest enabled → Queue follower for digest

2. **Queueing**
   - Follower stored in `bp_follow_digest_queue` user meta
   - Format: `array( follower_id => timestamp )`
   - Multiple followers accumulate in queue

3. **Cron Processing**
   - **Daily digests**: Every day at 9:00 AM
   - **Weekly digests**: Every Monday at 9:00 AM
   - DigestService processes all queued followers

4. **Sending Digest**
   - Checks last sent timestamp
   - Only sends if enough time passed (1 day for daily, 7 days for weekly)
   - Sends BuddyPress email with follower list
   - Clears queue and updates timestamp

### Database Structure

**User Meta Keys:**
```php
notification_starts_following           // yes|no - Master notification switch
notification_follows_digest            // yes|no - Enable digest mode
notification_follows_digest_frequency  // daily|weekly - Digest frequency
bp_follow_digest_queue                // array - Queued follower IDs
bp_follow_digest_last_sent            // int - Last digest send timestamp
```

## Email Templates

### Digest Email (customizable in Dashboard > Emails)

**Subject:**
```
[Site Name] You have {{follower.count}} new followers {{digest.period}}
```

**HTML Content:**
```html
Great news! You have <strong>{{follower.count}}</strong> new followers {{digest.period}}:

{{follower.names}}

<a href="{{{followers.url}}}">View all your followers</a> to see who's following you.
```

**Plain Text:**
```
You have {{follower.count}} new followers {{digest.period}}:

{{follower.names_full}}

View all your followers: {{{followers.url}}}
```

**Available Tokens:**
- `{{follower.count}}` - Number of new followers
- `{{follower.names}}` - First 5 follower names (comma-separated)
- `{{follower.names_full}}` - All follower names (newline-separated)
- `{{{followers.url}}}` - Link to followers page
- `{{digest.period}}` - "today" or "this week"
- `{{{unsubscribe}}}` - Unsubscribe link

## WP-CLI Commands

```bash
# Manually trigger digest processing
wp bp follow send-digests

# View follow statistics
wp bp follow stats

# Sync follower/following counts
wp bp follow sync-counts

# List all commands
wp bp follow --help
```

## Cron Schedule

### Automatic Scheduling

Cron jobs are automatically scheduled on `bp_init`:

- `bp_follow_send_daily_digests` - Daily at 9:00 AM
- `bp_follow_send_weekly_digests` - Every Monday at 9:00 AM

### Manual Control

```bash
# View scheduled events
wp cron event list --search=bp_follow

# Run digest cron manually
wp cron event run bp_follow_send_daily_digests

# Unschedule (automatic on plugin deactivation)
wp cron event delete bp_follow_send_daily_digests
```

## Developer Hooks

### Services

```php
// Access digest service
$digest_service = bp_follow_service( '\\Followers\\Service\\DigestService' );

// Check if user has digest enabled
if ( $digest_service->is_digest_enabled( $user_id ) ) {
    // Queue follower for digest
    $digest_service->queue_follower( $leader_id, $follower_id );
}

// Manually send digest for a user
$digest_service->send_digest( $user_id );

// Process all pending digests
$sent_count = $digest_service->process_all_digests();
```

### Filters

```php
// Modify email arguments before sending digest
add_filter( 'bp_follow_digest_email_args', function( $email_args, $user_id, $queue ) {
    // Customize digest email tokens
    $email_args['tokens']['custom.token'] = 'value';
    return $email_args;
}, 10, 3 );
```

### Actions

```php
// Run custom code when digest is sent
add_action( 'bp_follow_digest_sent', function( $user_id, $follower_count ) {
    // Log digest activity, update analytics, etc.
}, 10, 2 );

// Hook into cron processing
add_action( 'bp_follow_send_daily_digests', function() {
    // Custom daily digest logic
} );
```

## Testing

### Test Digest Functionality

```bash
# 1. Enable digest for test user
wp user meta update 1 notification_follows_digest yes
wp user meta update 1 notification_follows_digest_frequency weekly

# 2. Queue some followers (simulate follows)
wp eval '
$service = bp_follow_service("\\Followers\\Service\\DigestService");
$service->queue_follower(1, 2); // User 2 follows User 1
$service->queue_follower(1, 3); // User 3 follows User 1
$service->queue_follower(1, 4); // User 4 follows User 1
'

# 3. Check queue
wp user meta get 1 bp_follow_digest_queue

# 4. Send digest manually
wp bp follow send-digests

# 5. Verify email was sent (check logs or email)
```

### Reset Digest State

```bash
# Clear digest queue
wp user meta delete 1 bp_follow_digest_queue

# Reset last sent timestamp
wp user meta delete 1 bp_follow_digest_last_sent

# Disable digest
wp user meta update 1 notification_follows_digest no
```

## Performance

### Scalability

- **Queue Storage**: User meta (efficient for per-user data)
- **Batch Processing**: All digests processed in single cron run
- **Frequency Control**: Built-in throttling prevents spam
- **Memory Usage**: Processes users one at a time (no memory issues)

### Optimization Tips

1. **Limit follower names displayed**: Currently shows first 5 in HTML, all in plain text
2. **Adjust cron timing**: Change from 9:00 AM to off-peak hours if needed
3. **Monitor queue sizes**: Large queues may indicate popular users (feature working!)

## Troubleshooting

### Digests Not Sending

**Check:**
1. Is cron running? `wp cron event list`
2. Is digest enabled? `wp user meta get USER_ID notification_follows_digest`
3. Any queued followers? `wp user meta get USER_ID bp_follow_digest_queue`
4. Check last sent time: `wp user meta get USER_ID bp_follow_digest_last_sent`

**Solutions:**
```bash
# Force send digest
wp bp follow send-digests

# Re-register cron events
wp plugin deactivate buddypress-followers
wp plugin activate buddypress-followers
```

### Users Not Receiving Digests

**Check:**
1. User has digest enabled in settings
2. Enough time has passed since last digest (1 day for daily, 7 days for weekly)
3. Queue is not empty
4. Email template exists: `wp post list --post_type=bp-email --s=digest`

### Digest Email Template Missing

```bash
# Reinstall email templates
wp eval 'bp_follow_install_emails();'

# Verify installation
wp post list --post_type=bp-email --s=digest --fields=ID,post_title
```

## Future Enhancements

Potential improvements for future versions:

1. **Activity Digest**: Include recent activity from followed users
2. **Customizable Send Times**: Let users choose when to receive digests
3. **Monthly Digest Option**: Add monthly frequency
4. **Digest Preview**: Show users what their digest would look like
5. **Analytics**: Track digest open rates and engagement
6. **Smart Batching**: Group followers by mutual connections
7. **Rich Notifications**: Include follower avatars in email

## Files

### Created
- `src/Service/DigestService.php` - Core digest logic
- `includes/digest.php` - Cron scheduling and WP-CLI integration

### Modified
- `src/Component.php` - Registered DigestService
- `src/Service/EmailService.php` - Added digest routing logic
- `includes/emails.php` - Added digest email template
- `includes/user/notifications.php` - Added digest UI settings
- `includes/cli/class-follow-command.php` - Added send-digests command

## Support

For issues or questions:
- GitHub: https://github.com/r-a-y/buddypress-followers/issues
- WordPress.org: https://wordpress.org/support/plugin/buddypress-followers/

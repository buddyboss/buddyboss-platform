# BuddyBoss Platform - DRM & In-Plugin Notifications Implementation

## Overview

This implementation adds Digital Rights Management (DRM) and in-plugin notification functionality to BuddyBoss Platform, mirroring the system used in MemberPress. It leverages the existing GroundLevel infrastructure already present in BuddyBoss Platform.

## Architecture

```
┌─────────────────────────────────────────────────────────────┐
│ BuddyBoss Platform Bootstrap                                │
│ ├─ bp-core/admin/mothership/mothership-init.php            │
│ │  └─ buddyboss_init_drm()                                  │
└──────────────┬──────────────────────────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────────────────────────┐
│ DRM System (bp-core/admin/drm/)                             │
│ ├─ autoload.php                  (Class loader)             │
│ ├─ class-bb-drm-helper.php       (License validation)       │
│ ├─ class-bb-base-drm.php         (Base DRM logic)           │
│ ├─ class-bb-drm-nokey.php        (No license handler)       │
│ ├─ class-bb-drm-invalid.php      (Invalid license handler)  │
│ ├─ class-bb-drm-controller.php   (Main controller)          │
│ └─ class-bb-notifications.php    (GroundLevel adapter)      │
└──────────────┬──────────────────────────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────────────────────────┐
│ GroundLevel Services (vendor/caseproof/)                    │
│ ├─ ground-level-container        (DI container)             │
│ ├─ ground-level-mothership        (License API)             │
│ └─ ground-level-in-product-notifications  (Notifications)   │
└─────────────────────────────────────────────────────────────┘
```

## Files Created

### 1. **class-bb-drm-helper.php**
- License validation methods
- Days elapsed calculations
- DRM status management (LOW/MEDIUM/LOCKED)
- Messaging generation for different scenarios
- DRM link generation with tracking

### 2. **class-bb-base-drm.php**
- Abstract base class for DRM implementations
- Event management (create, update, retrieve)
- Notification generation (email & in-plugin)
- Admin notice rendering
- Status checking methods

### 3. **class-bb-drm-nokey.php**
- Handles scenario when no license key exists
- Timeline: 14-20 days (LOW) → 21-29 days (MEDIUM) → 30+ days (LOCKED)
- Fires action: `bb_drm_no_license_event`

### 4. **class-bb-drm-invalid.php**
- Handles scenario when license is expired/invalid
- Timeline: 7-20 days (MEDIUM) → 21+ days (LOCKED)
- Fires action: `bb_drm_invalid_license_event`

### 5. **class-bb-drm-controller.php**
- Main orchestration class
- Hooks into license activation/deactivation events
- Manages DRM checks on `admin_init`
- Handles AJAX notice dismissals
- Cleans up events and notifications on license reactivation

### 6. **class-bb-notifications.php**
- Adapter between BuddyBoss and GroundLevel
- Transforms notification data to GroundLevel format
- Manages notification persistence via Store service
- Handles bulk dismissal of DRM notifications

### 7. **autoload.php**
- SPL autoloader for DRM classes
- Explicit requires for core classes
- Namespace handling for BuddyBoss\Core\Admin\DRM

## Files Modified

### **mothership-init.php**
- Added `buddyboss_init_drm()` function
- Integrated DRM initialization after Mothership setup

## DRM Status Workflow

### Scenario 1: No License Key

```
Day 0:  License deactivated or never entered
        ├─ Option 'bb_drm_no_license' = true
        └─ Event created: 'bb_drm_event_no-license'

Day 14: LOW status triggered
        ├─ Admin notice: "Did You Forget Something?"
        ├─ Email sent to admin
        └─ In-plugin notification created

Day 21: MEDIUM status triggered
        ├─ Admin notice: "WARNING! Your Community is at Risk"
        ├─ Email sent to admin
        └─ In-plugin notification created

Day 30: LOCKED status triggered
        ├─ Admin notice: "ALERT! Backend is Deactivated"
        ├─ Email sent to admin
        ├─ In-plugin notification created
        └─ Backend features may be restricted
```

### Scenario 2: Invalid/Expired License

```
Day 0:  License expires or becomes invalid
        ├─ Option 'bb_drm_invalid_license' = true
        └─ Event created: 'bb_drm_event_invalid-license'

Day 7:  MEDIUM status triggered
        ├─ Admin notice: "Your license key is expired"
        ├─ Email sent to admin
        └─ In-plugin notification created

Day 21: LOCKED status triggered
        ├─ Admin notice: "ALERT! Backend is Deactivated"
        ├─ Email sent to admin
        ├─ In-plugin notification created
        └─ Backend features may be restricted
```

## Integration Points

### WordPress Hooks Used

**Actions:**
- `{PLATFORM_EDITION}_license_activated` - Clear DRM state
- `{PLATFORM_EDITION}_license_deactivated` - Trigger no-license DRM
- `{PLATFORM_EDITION}_license_expired` - Trigger invalid-license DRM
- `{PLATFORM_EDITION}_license_invalidated` - Trigger invalid-license DRM
- `admin_init` - Run DRM checks (priority 20)
- `admin_notices` - Display admin notices
- `admin_body_class` - Add CSS classes when locked

**AJAX Actions:**
- `wp_ajax_bb_dismiss_notice_drm` - Handle notice dismissal

### Custom Actions Fired

- `bb_drm_no_license_event( $event, $days, $status )`
- `bb_drm_invalid_license_event( $event, $days, $status )`

### Filters Available

- `bb_drm_info` - Modify DRM messaging
- `bb_drm_links` - Customize tracking links

## Data Storage

### WordPress Options

- `bb_drm_no_license` (bool) - Flag for no license scenario
- `bb_drm_invalid_license` (bool) - Flag for invalid license scenario
- `bb_drm_event_no-license` (array) - Event data for no license
- `bb_drm_event_invalid-license` (array) - Event data for invalid license

### GroundLevel Store

- Option key: `{plugin_id}_ipn_store`
- Contains: Array of notification objects
- Managed by: GroundLevel Store service

## Testing Checklist

### 1. No License Scenario
- [ ] Deactivate license
- [ ] Verify `bb_drm_no_license` option created
- [ ] Wait/simulate 14 days - check LOW warning
- [ ] Wait/simulate 21 days - check MEDIUM warning
- [ ] Wait/simulate 30 days - check LOCKED alert
- [ ] Verify emails sent
- [ ] Verify in-plugin notifications appear
- [ ] Activate license - verify cleanup

### 2. Invalid License Scenario
- [ ] Expire license (or manually trigger)
- [ ] Verify `bb_drm_invalid_license` option created
- [ ] Wait/simulate 7 days - check MEDIUM warning
- [ ] Wait/simulate 21 days - check LOCKED alert
- [ ] Verify emails sent
- [ ] Verify in-plugin notifications appear
- [ ] Renew license - verify cleanup

### 3. Notification System
- [ ] Verify notification bell appears in admin
- [ ] Verify unread count displays
- [ ] Click bell - verify panel opens
- [ ] Verify notification formatting
- [ ] Click "Contact Us" button
- [ ] Dismiss notification - verify removed
- [ ] Verify notification stays dismissed for 24 hours

### 4. Edge Cases
- [ ] New install (no license ever entered)
- [ ] Multisite activation
- [ ] Multiple rapid license changes
- [ ] License reactivation before LOCKED
- [ ] Admin notice dismissal persistence

## Customization

### Change DRM Timelines

Edit `class-bb-drm-nokey.php` or `class-bb-drm-invalid.php`:

```php
// In run() method
if ( $days >= 14 && $days <= 20 ) {  // Change these values
    $this->set_status( BB_DRM_Helper::DRM_LOW );
}
```

### Customize Messaging

Use the `bb_drm_info` filter:

```php
add_filter( 'bb_drm_info', function( $info, $status, $event, $purpose ) {
    if ( $status === BB_DRM_Helper::DRM_LOW ) {
        $info['heading'] = 'Custom Warning Message';
    }
    return $info;
}, 10, 4 );
```

### Customize Tracking Links

Use the `bb_drm_links` filter:

```php
add_filter( 'bb_drm_links', function( $links ) {
    $links[ BB_DRM_Helper::DRM_LOW ]['general']['support'] = 'https://custom-url.com/support';
    return $links;
});
```

### Add Custom DRM Actions

```php
add_action( 'bb_drm_no_license_event', function( $event, $days, $status ) {
    // Custom logic when no license event fires
    error_log( "DRM No License: {$days} days, Status: {$status}" );
}, 10, 3 );
```

## Maintenance

### Manual Event Reset

```php
// Reset no-license event
delete_option( 'bb_drm_no_license' );
delete_option( 'bb_drm_event_no-license' );

// Reset invalid-license event
delete_option( 'bb_drm_invalid_license' );
delete_option( 'bb_drm_event_invalid-license' );
```

### Clear All Notifications

```php
$notifications = new BuddyBoss\Core\Admin\DRM\BB_Notifications();
$notifications->dismiss_events( 'bb-drm' );
```

### Debug Mode

Add to `wp-config.php`:

```php
// Enable DRM debug logging
define( 'BB_DRM_DEBUG', true );
```

Then add logging in methods:

```php
if ( defined( 'BB_DRM_DEBUG' ) && BB_DRM_DEBUG ) {
    error_log( 'DRM Status: ' . $this->drm_status );
}
```

## Support & Troubleshooting

### Issue: Notifications not appearing

1. Check if GroundLevel IPN is initialized:
   ```php
   $loader = new BB_Mothership_Loader();
   $container = $loader->getContainer();
   var_dump( $container->has( Store::class ) );
   ```

2. Check notification store:
   ```php
   $store = $container->get( Store::class )->fetch();
   var_dump( $store->notifications() );
   ```

### Issue: Events not triggering

1. Verify license status:
   ```php
   var_dump( BB_DRM_Helper::is_valid() );
   var_dump( BB_DRM_Helper::has_key() );
   ```

2. Check DRM options:
   ```php
   var_dump( get_option( 'bb_drm_no_license' ) );
   var_dump( get_option( 'bb_drm_invalid_license' ) );
   ```

### Issue: Timeline not matching

1. Check event creation date:
   ```php
   $event = get_option( 'bb_drm_event_no-license' );
   var_dump( $event['created_at'] );
   var_dump( BB_DRM_Helper::days_elapsed( $event['created_at'] ) );
   ```

## Additional Notes

- **Icon File**: Place `alert-icon.png` in `bp-core/admin/assets/images/`
- **Multisite**: DRM runs per-site, not network-wide
- **Performance**: DRM checks only run on `admin_init` with priority 20
- **Caching**: No caching needed - uses WordPress options
- **Security**: All user inputs sanitized, nonces verified
- **i18n**: All strings wrapped in `__()` for translation

## Future Enhancements

Potential additions for future versions:

1. **Grace Period Settings** - Admin UI to customize timelines
2. **Frontend Restrictions** - Optionally lock frontend features
3. **Analytics Integration** - Track DRM event occurrences
4. **Multiple License Tiers** - Different DRM rules per edition
5. **Auto-renewal Reminders** - Proactive notifications before expiration
6. **REST API Integration** - Programmatic DRM status checks

## Credits

Implementation based on MemberPress DRM system by Caseproof, LLC.
Adapted for BuddyBoss Platform by following the same architecture patterns.

---

**Version:** 1.0.0
**Last Updated:** 2025-01-15
**Maintainer:** BuddyBoss Development Team

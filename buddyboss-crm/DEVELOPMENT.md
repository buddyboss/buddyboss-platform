# BuddyBoss CRM - Development Guide

## What's Been Built

### ✅ Phase 1: Foundation (COMPLETE)

**Database Layer:**
- ✅ 10 optimized database tables created
  - Tags, User Tags, Categories, User Lists
  - List relationships and assignments
  - Automation rules, queue, and logs
  - Tag history (audit trail)

**Core API:**
- ✅ Tag CRUD operations
  - `bb_crm_create_tag()` - Create new tags
  - `bb_crm_get_tag()` - Retrieve tag by ID
  - `bb_crm_get_tags()` - Query tags with filters
  - `bb_crm_add_user_tag()` - Assign tag to user
  - `bb_crm_remove_user_tag()` - Remove tag from user
  - `bb_crm_get_user_tags()` - Get all tags for a user
  - `bb_crm_get_tag_users()` - Get users with a tag
  - `bb_crm_count_tag_users()` - Count users with a tag
- ✅ Tag history tracking
- ✅ Caching layer for performance

**Admin Interface:**
- ✅ Dashboard with stats and quick actions
- ✅ Tags management page
- ✅ Placeholder pages for:
  - Categories
  - User management
  - User Lists
  - Automations (coming v2.0)
  - Broadcasts (coming v2.0)
  - Settings
- ✅ Clean, modern UI with responsive design
- ✅ BuddyBoss menu integration

**Assets:**
- ✅ Professional admin CSS styling
- ✅ JavaScript for interactions
- ✅ Empty state designs
- ✅ Tag badge components

## File Structure

```
buddyboss-crm/
├── buddyboss-crm.php              ✅ Main plugin file
├── readme.txt                     ✅ WordPress.org format
├── README.md                      ✅ Development docs
│
├── includes/
│   ├── class-bb-crm-install.php   ✅ Database installation
│   ├── bb-crm-functions.php       ✅ Core API functions
│   │
│   └── admin/
│       ├── class-bb-crm-admin.php ✅ Admin init & menus
│       └── views/                 ✅ All admin page templates
│           ├── dashboard.php
│           ├── tags.php
│           ├── categories.php
│           ├── users.php
│           ├── lists.php
│           ├── automations.php
│           ├── broadcasts.php
│           └── settings.php
│
└── assets/
    ├── css/
    │   └── bb-crm-admin.css       ✅ Admin styles
    └── js/
        └── bb-crm-admin.js        ✅ Admin JavaScript
```

## Testing Your Setup

### 1. Complete WordPress Installation

1. Open http://localhost:8080
2. Complete the 5-minute WordPress installation
3. Log in to http://localhost:8080/wp-admin

### 2. Activate Plugins

1. Go to **Plugins** → **Installed Plugins**
2. Activate **BuddyBoss Platform** first
3. Activate **BuddyBoss CRM**

### 3. Access CRM Dashboard

Go to **CRM** → **Dashboard** in the admin menu

You should see:
- Stats cards (0 tags, 0 categories, etc.)
- Quick actions
- System status
- Getting started guide

### 4. Test Tag Creation (API)

Open WordPress admin and use **Tools** → **Theme File Editor** or SSH into container:

```php
// Create a test tag
$tag_id = bb_crm_create_tag(array(
    'name' => 'VIP Member',
    'slug' => 'vip-member',
    'color' => '#d4af37',
    'visibility' => 'public',
    'priority' => 10,
    'description' => 'Premium community members'
));

// Get the tag
$tag = bb_crm_get_tag($tag_id);

// Assign to a user
$result = bb_crm_add_user_tag(1, $tag_id); // User ID 1 (admin)

// Get user's tags
$user_tags = bb_crm_get_user_tags(1);
```

### 5. Verify Database Tables

Go to **phpMyAdmin** (http://localhost:8081):
- Server: `db`
- Username: `root`
- Password: `rootpassword`

Check these tables exist:
- `wp_bp_tags`
- `wp_bp_user_tags`
- `wp_bp_tag_categories`
- `wp_bp_user_lists`
- (and 6 more...)

## Next Development Steps

### Phase 2: Tag UI & CRUD (Next Priority)

1. **Tag Create/Edit Form**
   - Form to create/edit tags
   - Color picker integration
   - Icon selector
   - Category dropdown
   - Visibility settings
   - Expiry configuration

2. **Tag Actions**
   - Delete tags
   - Bulk operations
   - Tag preview

3. **Category Management**
   - Create/edit/delete categories
   - Assign tags to categories
   - Category filtering

### Phase 3: User Management

1. **User Tag Assignment**
   - Search users
   - Bulk tag assignment
   - Individual user tag management
   - Tag filtering in user list

2. **User Lists**
   - Create lists with tag criteria
   - ANY vs ALL matching
   - List preview
   - Export users from lists

### Phase 4: Automation System (v2.0)

Based on your `Automations System Architecture Scope.md`:
1. Trigger system
2. Condition evaluator
3. Action executor
4. Queue processor
5. Automation builder UI

### Phase 5: Broadcast System (v2.0)

1. Broadcast templates
2. User segmentation
3. Delivery system
4. Analytics

## Development Workflow

### Making Changes

```bash
# Edit files in buddyboss-crm/
# Changes are immediately reflected in WordPress

# Test in browser
# No restart needed!

# Commit changes
cd buddyboss-crm
git add .
git commit -m "Add feature"
git push
```

### Using WP-CLI

```bash
# From project root
docker exec buddyboss-wordpress wp plugin list --allow-root

# Activate plugin
docker exec buddyboss-wordpress wp plugin activate buddyboss-crm --allow-root

# Run custom commands
docker exec buddyboss-wordpress wp eval "echo bb_crm_get_tags();" --allow-root
```

### Database Queries

```bash
# Access MySQL
docker exec -it buddyboss-db mysql -u wordpress -pwordpress wordpress

# Example query
SELECT * FROM wp_bp_tags;
```

## API Examples

### Create Tag with All Options

```php
$tag_id = bb_crm_create_tag(array(
    'name'         => 'Gold Subscriber',
    'slug'         => 'gold-subscriber',
    'color'        => '#FFD700',
    'icon'         => 'dashicons-star-filled',
    'visibility'   => 'members-only',
    'priority'     => 5,
    'expires_days' => 365, // Auto-expire after 1 year
    'description'  => 'Annual gold subscription members',
    'category_id'  => 0,
));
```

### Bulk Tag Users

```php
$user_ids = array(1, 2, 3, 4, 5);
$tag_id = 1;

foreach ($user_ids as $user_id) {
    bb_crm_add_user_tag($user_id, $tag_id, array(
        'source' => 'bulk_import',
        'applied_by' => get_current_user_id(),
    ));
}
```

### Query Tags by Category

```php
$tags = bb_crm_get_tags(array(
    'category_id' => 2,
    'visibility' => 'public',
    'orderby' => 'name',
    'order' => 'ASC',
));
```

## Troubleshooting

### Plugin Not Showing

```bash
# Check if mounted
docker exec buddyboss-wordpress ls -la /var/www/html/wp-content/plugins/buddyboss-crm

# Restart WordPress container
docker-compose restart wordpress
```

### Database Tables Not Created

```php
// Manually trigger installation
require_once WP_PLUGIN_DIR . '/buddyboss-crm/includes/class-bb-crm-install.php';
BB_CRM_Install::install();
```

### CSS Not Loading

- Hard refresh: Cmd+Shift+R (Mac) or Ctrl+F5 (Windows)
- Check browser console for 404 errors
- Verify file exists: `buddyboss-crm/assets/css/bb-crm-admin.css`

## Resources

- **Architecture**: `/Resources/Tagging System Architecture Scope.md`
- **Automations**: `/Resources/Automations System Architecture Scope.md`
- **Prototypes**: `/prototypes/tagging-admin-dashboard.html`
- **BuddyBoss Docs**: https://www.buddyboss.com/resources/docs/

## Git Workflow

```bash
# Initialize repo (if not done)
cd buddyboss-crm
git init
git add .
git commit -m "Initial commit: Phase 1 complete"

# Add remote
git remote add origin YOUR_REPO_URL
git push -u origin main

# Feature branches
git checkout -b feature/tag-ui
# ...make changes...
git commit -m "Add tag create/edit UI"
git push origin feature/tag-ui
```

---

**Status**: Phase 1 Complete ✅
**Next**: Tag UI & CRUD Operations

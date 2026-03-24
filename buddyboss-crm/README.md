# BuddyBoss CRM Plugin

Comprehensive CRM system for BuddyBoss Platform with user tagging, automation workflows, and broadcast messaging.

## Features

### User Tagging System
- **Tags**: Visual labels with custom colors, icons, and visibility settings
- **Categories**: Hierarchical organization of tags
- **Lists**: Dynamic user groups based on tag combinations
- **Auto-Expiry**: Time-based tag expiration
- **Bulk Operations**: Efficient batch assignment
- **Import/Export**: CSV and JSON data portability

### Automation Workflows
- **Triggers**: Tag added/removed events
- **Conditions**: Complex rule evaluation
- **Actions**: Assign tags, send emails, webhooks
- **Queue**: Async processing for performance
- **Audit Trail**: Complete execution history

### Broadcast Messaging
- **Segmentation**: Target users by tags/lists
- **Templates**: Reusable message templates
- **Tracking**: Delivery and engagement analytics

## Installation

1. Ensure BuddyBoss Platform is installed
2. Copy this plugin to `wp-content/plugins/buddyboss-crm/`
3. Activate via WordPress admin

## Development

### Directory Structure

```
buddyboss-crm/
├── buddyboss-crm.php          # Main plugin file
├── readme.txt                 # WordPress.org readme
├── README.md                  # This file
│
├── includes/                  # Core functionality
│   ├── class-bb-crm-install.php
│   ├── class-bb-crm-tags.php
│   ├── class-bb-crm-automations.php
│   └── class-bb-crm-broadcasts.php
│
├── admin/                     # Admin interface
│   ├── class-bb-crm-admin.php
│   ├── views/
│   └── assets/
│
├── assets/                    # Frontend assets
│   ├── css/
│   ├── js/
│   └── images/
│
├── languages/                 # Translation files
│
└── tests/                     # Unit tests
```

### Architecture

See the complete architecture documentation in:
- `/Resources/Tagging System Architecture Scope.md`
- `/Resources/Automations System Architecture Scope.md`

### Database Schema

The plugin creates these custom tables:
- `wp_bp_tags` - Tag definitions
- `wp_bp_user_tags` - User-tag assignments
- `wp_bp_tag_categories` - Category definitions
- `wp_bp_user_lists` - Dynamic user lists
- `wp_bp_user_list_tags` - List-tag relationships
- `wp_bp_automation_rules` - Automation definitions
- `wp_bp_automation_queue` - Action queue
- `wp_bp_automation_log` - Execution history

## Requirements

- WordPress 6.0+
- PHP 7.4+
- BuddyBoss Platform
- MySQL 5.7+ or MariaDB 10.3+

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

## License

GPL v2 or later

## Author

Tom Jutla

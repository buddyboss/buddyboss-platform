Ground Level Insights
======================

The Ground Level Insights package is a service module that provides Net Promoter Score (NPS) functionality and integration with the [Mothership REST API](https://licenses.caseproof.com/help/api-reference) for collecting product insights and user feedback.

---

## Installation

```bash
composer config repositories.caseproof composer https://pkgs.cspf.co
composer require caseproof/ground-level-insights
```

## Usage

Create a container, register required services and parameters, and register the service provider:

```php
<?php

use GroundLevel\Container\Container;
use GroundLevel\Mothership\AbstractPluginConnection;
use GroundLevel\InProductNotifications\IPNServiceProvider;
use GroundLevel\Insights\InsightsServiceProvider;

$container = (new Container())
    ->singleton(AbstractPluginConnection::class, static fn() => new MyPluginConnection())
    ->parameters([
        InsightsServiceProvider::PARAM_PRODUCT_NAME => 'My Product Name',
        IPNServiceProvider::PARAM_PRODUCT_SLUG      => 'product-slug',
        IPNServiceProvider::PARAM_RENDER_HOOK       => 'myproduct_admin_header_actions',
    ])
    ->provider(InsightsServiceProvider::class)
    ->boot();
```

## Net Promoter Score (NPS)

The Insights package provides a complete NPS implementation that:

- Automatically displays NPS notifications to users after a grace period (default: 14 days)
- Re-displays notifications at recurring intervals (default: 90 days)
- Collects NPS scores (0-10) through an interactive interface
- Collects optional feedback and follow-up preferences
- Submits data to the Mothership API via REST endpoints

### Notification Display Logic

The NPS notification will be displayed when:

1. **Initial Display**: After the grace period (default: 14 days) from plugin installation
2. **Recurring Display**: Every recurrence interval (default: 90 days) after the last NPS event

An NPS event is recorded when the notification is added (shown) and refreshed when a score is submitted. Dismissing or ignoring the survey therefore still applies the recurrence interval.

The notification is displayed as an in-product notification using the IPN (In-Product Notifications) system.

### NPS Submission Flow

1. User selects a score (0-10) from the notification
2. User clicks "Submit" to submit the initial score
3. A modal appears asking for feedback based on the score:
   - **Promoters (9-10)**: "What is the main reason for your score?"
   - **Detractors (0-8)**: "What could we do to improve your score?"
4. User can optionally provide feedback and indicate if they want a follow-up
5. Data is submitted to the Mothership API

## Customization

### Container Parameters

The Insights service can be customized through container parameters:

| Parameter | Description | Required | Default |
| --------- | ----------- | -------- | ------- |
| `PRODUCT_NAME` | The name of the product displayed in NPS questions. | Yes | N/A |
| `PREFIX` | The prefix applied to various strings and IDs used by the service. | No | `grdlvl_insights_` |
| `GRACE_PERIOD_DAYS` | Days after installation before showing the first NPS notification. | No | `14` |
| `RECURRENCE_DAYS` | Days between recurring NPS notifications after the last event. | No | `90` |
| `REST_NAMESPACE` | The REST API namespace for NPS endpoints. | No | `grdlvl/insights` |

### WordPress Hooks

#### {$prefix}should_show_nps_notification

Filters whether the NPS notification should be displayed.

The dynamic portion of this hook, `{$prefix}`, is the PREFIX parameter set in the container. The default value is `grdlvl_insights_`.

**Parameters:**
- `bool $shouldShow` - Whether the notification should be shown based on timing logic.

**Return:**
- `bool` - Whether to display the notification.

**Example:**

```php
add_filter('grdlvl_insights_should_show_nps_notification', function($shouldShow) {
    // Only show NPS for users with specific capability
    if (!current_user_can('manage_options')) {
        return false;
    }
    return $shouldShow;
});
```

## REST API Endpoints

The Insights service automatically registers REST API endpoints for NPS submissions.

### POST /wp-json/{namespace}/nps/submit

Submits NPS data (score, feedback, follow-up preference).

**Authentication:** Requires WordPress REST API authentication and user capability check.

**Parameters:**
- `score` (integer, required): NPS score from 0-10
- `feedback` (string, optional): User feedback text (max 3072 characters)
- `follow_up` (boolean, optional): Whether user wants a follow-up

**Response:**
- `200`: Success
- `401`: User not authenticated or invalid
- `403`: Permission denied
- `422`: Invalid score

**Example:**

```javascript
fetch('/wp-json/grdlvl/insights/nps/submit', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': wpApiSettings.nonce
    },
    body: JSON.stringify({
        score: 9,
        feedback: 'Great product!',
        follow_up: true
    })
});
```

## Components

### Services

- **`Insights`**: Main service class that configures and loads the Insights system
- **`NetPromoterScore`**: Handles NPS notification display logic, scheduling, and data management
- **`RestApi`**: Manages REST API endpoints and JavaScript enqueuing

### Dependencies

The Insights package requires:
- `caseproof/ground-level-container`: ^2.0.0
- `caseproof/ground-level-in-product-notifications`: ^2.0.0
- `caseproof/ground-level-mothership`: ^2.0.0
- `caseproof/ground-level-support`: ^2.0.0

## Data Storage

NPS data is stored in WordPress options using the option key `{$prefix}nps_data` (default: `grdlvl_insights_nps_data`).

The stored data includes:
- `installTime`: Timestamp of when the plugin was first installed
- `lastEventTime`: Timestamp of the last NPS event (notification shown or score submitted)

## Scheduling

The Insights service automatically registers a WordPress CRON event that runs daily to check if the NPS notification should be displayed. The hook name is `{$prefix}nps_check` (default: `grdlvl_insights_nps_check`).

## Integration with Mothership API

NPS data is submitted to the Mothership API using the `ProductInsights::nps()` method, which sends data to:

```
POST /api/v1/products/{product_slug}/insights/nps
```

The submitted data includes:
- `email`: User's email address
- `score`: NPS score (0-10)
- `feedback`: Optional feedback text
- `follow_up`: Optional follow-up preference

---

## Development

### Testing

The Insights package includes comprehensive test coverage for all services and functionality. Run tests using:

```bash
composer test
```

### Force Display (Development)

During development, you can force display the NPS notification by adding a query string parameter to any WordPress admin URL:

```
?grdlvlInsightsForceDisplayNpsNotification=1
```

This will bypass timing checks and immediately display the notification.

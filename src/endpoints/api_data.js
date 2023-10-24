define({ "api": [
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/account-settings",
    "title": "Account Settings",
    "name": "GetBBAccountSettings",
    "group": "Account_Settings",
    "description": "<p>Retrieve account settings tabs.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "filename": "src/bp-settings/classes/class-bp-rest-account-settings-endpoint.php",
    "groupTitle": "Account_Settings"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/account-settings/:nav",
    "title": "Get Settings Options",
    "name": "GetBBAccountSettingsOptions",
    "group": "Account_Settings",
    "description": "<p>Retrieve account setting options based on navigation tab.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "general",
              "notifications",
              "profile",
              "invites",
              "export",
              "delete-account"
            ],
            "optional": false,
            "field": "nav",
            "description": "<p>Navigation item slug.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-settings/classes/class-bp-rest-account-settings-options-endpoint.php",
    "groupTitle": "Account_Settings"
  },
  {
    "type": "PATCH",
    "url": "/wp-json/buddyboss/v1/account-settings/:nav",
    "title": "Update Settings Options",
    "name": "UpdateBBAccountSettingsOptions",
    "group": "Account_Settings",
    "description": "<p>Update account setting options based on navigation tab.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "general",
              "notifications",
              "profile",
              "invites",
              "export",
              "delete-account"
            ],
            "optional": false,
            "field": "nav",
            "description": "<p>Navigation item slug.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": false,
            "field": "fields",
            "description": "<p>The list of fields to update with name and value of the field.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-settings/classes/class-bp-rest-account-settings-options-endpoint.php",
    "groupTitle": "Account_Settings"
  },
  {
    "type": "POST",
    "url": "/wp-json/buddyboss/v1/activity/:id/comment",
    "title": "Create activity comment",
    "name": "CreateActivityComment",
    "group": "Activity",
    "description": "<p>Create comment under activity.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>A unique numeric ID for the activity.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "parent_id",
            "description": "<p>ID of the parent activity/comment item.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": false,
            "field": "content",
            "description": "<p>The content of the comment.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "threaded",
              "stream",
              "false"
            ],
            "optional": true,
            "field": "display_comments",
            "defaultValue": "threaded",
            "description": "<p>Comments by default, stream for within stream display, threaded for below each activity item.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-activity/classes/class-bp-rest-activity-comment-endpoint.php",
    "groupTitle": "Activity"
  },
  {
    "type": "POST",
    "url": "/wp-json/buddyboss/v1/activity",
    "title": "Create activity",
    "name": "CreateBBActivity",
    "group": "Activity",
    "description": "<p>Create activity</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "primary_item_id",
            "description": "<p>The ID of some other object primarily associated with this one.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "secondary_item_id",
            "description": "<p>The ID of some other object also associated with this one.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "user_id",
            "description": "<p>The ID for the author of the activity.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": false,
            "field": "link",
            "description": "<p>The permalink to this activity on the site.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "settings",
              "notifications",
              "groups",
              "forums",
              "activity",
              "media",
              "messages",
              "friends",
              "invites",
              "search",
              "members",
              "xprofile",
              "blogs"
            ],
            "optional": false,
            "field": "component",
            "description": "<p>The active component the activity relates to.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "new_member",
              "new_avatar",
              "updated_profile",
              "activity_update",
              "created_group",
              "joined_group",
              "group_details_updated",
              "bbp_topic_create",
              "bbp_reply_create",
              "activity_comment",
              "friendship_accepted",
              "friendship_created",
              "new_blog_post",
              "new_blog_comment"
            ],
            "optional": false,
            "field": "type",
            "description": "<p>The activity type of the activity.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": false,
            "field": "content",
            "description": "<p>Allowed HTML content for the activity.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": false,
            "field": "date",
            "description": "<p>The date the activity was published, in the site's timezone.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "allowedValues": [
              "true",
              "false"
            ],
            "optional": false,
            "field": "hidden",
            "description": "<p>Whether the activity object should be sitewide hidden or not.</p>"
          },
          {
            "group": "Parameter",
            "type": "string",
            "allowedValues": [
              "public",
              "loggedin",
              "onlyme",
              "friends",
              "media"
            ],
            "optional": true,
            "field": "privacy",
            "description": "<p>Privacy of the activity.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "bp_media_ids",
            "description": "<p>Media specific IDs when Media component is enable.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "media_gif",
            "description": "<p>Save gif data into activity when Media component is enable. param(url,mp4)</p>"
          }
        ]
      }
    },
    "filename": "src/bp-activity/classes/class-bp-rest-activity-endpoint.php",
    "groupTitle": "Activity"
  },
  {
    "type": "DELETE",
    "url": "/wp-json/buddyboss/v1/activity/:id",
    "title": "Delete activity",
    "name": "DeleteBBActivity",
    "group": "Activity",
    "description": "<p>Delete single activity</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>A unique numeric ID for the activity.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-activity/classes/class-bp-rest-activity-endpoint.php",
    "groupTitle": "Activity"
  },
  {
    "type": "DELETE",
    "url": "/wp-json/buddyboss/v1/activity/:id/comment/:comment_id",
    "title": "Delete activity comment",
    "name": "DeleteBBActivityComment",
    "group": "Activity",
    "description": "<p>Delete single activity comment</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>A unique numeric ID for the activity.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "comment_id",
            "description": "<p>A unique numeric ID for the activity comment.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-activity/classes/class-bp-rest-activity-comment-endpoint.php",
    "groupTitle": "Activity"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/activity/:id/comment",
    "title": "Get activity comments",
    "name": "GetActivityComment",
    "group": "Activity",
    "description": "<p>Get all comments for an activity.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser if the site is in Private Network."
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>A unique numeric ID for the activity.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "threaded",
              "stream",
              "false"
            ],
            "optional": true,
            "field": "display_comments",
            "defaultValue": "threaded",
            "description": "<p>Comments by default, stream for within stream display, threaded for below each activity item.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-activity/classes/class-bp-rest-activity-comment-endpoint.php",
    "groupTitle": "Activity"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/activity",
    "title": "Get Activities",
    "name": "GetBBActivities",
    "group": "Activity",
    "description": "<p>Retrieve activities</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser if the site is in Private Network."
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "page",
            "description": "<p>Current page of the collection.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "per_page",
            "defaultValue": "10",
            "description": "<p>Maximum number of items to be returned in result set.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": true,
            "field": "search",
            "description": "<p>Limit results to those matching a string.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "exclude",
            "description": "<p>Ensure result set excludes specific IDs.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "include",
            "description": "<p>Ensure result set includes specific IDs.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "allowedValues": [
              "asc",
              "desc"
            ],
            "optional": true,
            "field": "order",
            "defaultValue": "desc",
            "description": "<p>Ensure result set includes specific IDs.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": true,
            "field": "after",
            "description": "<p>Limit result set to items published after a given ISO8601 compliant date.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "user_id",
            "description": "<p>Limit result set to items created by a specific user (ID).</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "ham_only",
              "spam_only",
              "all"
            ],
            "optional": true,
            "field": "status",
            "defaultValue": "ham_only",
            "description": "<p>Limit result set to items with a specific status.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "just-me",
              "friends",
              "groups",
              "favorites",
              "mentions",
              "following"
            ],
            "optional": true,
            "field": "scope",
            "description": "<p>Limit result set to items with a specific scope.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "group_id",
            "description": "<p>Limit result set to items created by a specific group.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "site_id",
            "description": "<p>Limit result set to items created by a specific site.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "primary_id",
            "description": "<p>Limit result set to items with a specific prime association ID.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "secondary_id",
            "description": "<p>Limit result set to items with a specific secondary association ID.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": true,
            "field": "component",
            "description": "<p>Limit result set to items with a specific active component.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": true,
            "field": "type",
            "description": "<p>Limit result set to items with a specific activity type.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "stream",
              "threaded",
              "false"
            ],
            "optional": true,
            "field": "display_comments",
            "defaultValue": "false",
            "description": "<p>No comments by default, stream for within stream display, threaded for below each activity item.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "allowedValues": [
              "public",
              "loggedin",
              "onlyme",
              "friends",
              "media"
            ],
            "optional": true,
            "field": "privacy",
            "description": "<p>Privacy of the activity.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-activity/classes/class-bp-rest-activity-endpoint.php",
    "groupTitle": "Activity"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/activity/details",
    "title": "Activity details",
    "name": "GetBBActivitiesDetails",
    "group": "Activity",
    "description": "<p>Retrieve activity details (includes nav, filters and post_in)</p>",
    "version": "1.0.0",
    "filename": "src/bp-activity/classes/class-bp-rest-activity-details-endpoint.php",
    "groupTitle": "Activity"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/activity/:id",
    "title": "Get Activity",
    "name": "GetBBActivity",
    "group": "Activity",
    "description": "<p>Retrieve single activity</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>A unique numeric ID for the activity.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "stream",
              "threaded",
              "false"
            ],
            "optional": true,
            "field": "display_comments",
            "defaultValue": "false",
            "description": "<p>No comments by default, stream for within stream display, threaded for below each activity item.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-activity/classes/class-bp-rest-activity-endpoint.php",
    "groupTitle": "Activity"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/activity/:id/comment/:comment_id",
    "title": "Get Activity Comment",
    "name": "GetBBActivityComment",
    "group": "Activity",
    "description": "<p>Retrieve single activity comment</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>A unique numeric ID for the activity.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "comment_id",
            "description": "<p>A unique numeric ID for the activity comment.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "stream",
              "threaded",
              "false"
            ],
            "optional": true,
            "field": "display_comments",
            "defaultValue": "false",
            "description": "<p>No comments by default, stream for within stream display, threaded for below each activity item.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-activity/classes/class-bp-rest-activity-comment-endpoint.php",
    "groupTitle": "Activity"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/activity/link-preview",
    "title": "Link Preview",
    "name": "GetBBActivityLinkPreview",
    "group": "Activity",
    "description": "<p>Retrieve link preview Activity.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser if the site is in Private Network."
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "String",
            "optional": false,
            "field": "url",
            "description": "<p>URL for the generate link preview.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-activity/classes/class-bp-rest-activity-link-preview-endpoint.php",
    "groupTitle": "Activity"
  },
  {
    "type": "PATCH",
    "url": "/wp-json/buddyboss/v1/activity/:id",
    "title": "Update activity",
    "name": "UpdateBBActivity",
    "group": "Activity",
    "description": "<p>Update single activity</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>A unique numeric ID for the activity.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "primary_item_id",
            "description": "<p>The ID of some other object primarily associated with this one.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "secondary_item_id",
            "description": "<p>The ID of some other object also associated with this one.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "user_id",
            "description": "<p>The ID for the author of the activity.</p>"
          },
          {
            "group": "Parameter",
            "type": "string",
            "optional": true,
            "field": "link",
            "description": "<p>The permalink to this activity on the site.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "settings",
              "notifications",
              "groups",
              "forums",
              "activity",
              "media",
              "messages",
              "friends",
              "invites",
              "search",
              "members",
              "xprofile",
              "blogs"
            ],
            "optional": true,
            "field": "component",
            "description": "<p>The active component the activity relates to.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "new_member",
              "new_avatar",
              "updated_profile",
              "activity_update",
              "created_group",
              "joined_group",
              "group_details_updated",
              "bbp_topic_create",
              "bbp_reply_create",
              "activity_comment",
              "friendship_accepted",
              "friendship_created",
              "new_blog_post",
              "new_blog_comment"
            ],
            "optional": true,
            "field": "type",
            "description": "<p>The activity type of the activity.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": true,
            "field": "content",
            "description": "<p>Allowed HTML content for the activity.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": true,
            "field": "date",
            "description": "<p>The date the activity was published, in the site's timezone.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "allowedValues": [
              "true",
              "false"
            ],
            "optional": true,
            "field": "hidden",
            "description": "<p>Whether the activity object should be sitewide hidden or not.</p>"
          },
          {
            "group": "Parameter",
            "type": "string",
            "allowedValues": [
              "public",
              "loggedin",
              "onlyme",
              "friends",
              "media"
            ],
            "optional": true,
            "field": "privacy",
            "description": "<p>Privacy of the activity.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "bp_media_ids",
            "description": "<p>Media specific IDs when Media component is enable.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "media_gif",
            "description": "<p>Save gif data into activity when Media component is enable. param(url,mp4)</p>"
          }
        ]
      }
    },
    "filename": "src/bp-activity/classes/class-bp-rest-activity-endpoint.php",
    "groupTitle": "Activity"
  },
  {
    "type": "PATCH",
    "url": "/wp-json/buddyboss/v1/activity/:id/comment/:comment_id",
    "title": "Update activity comment",
    "name": "UpdateBBActivityComment",
    "group": "Activity",
    "description": "<p>Update single activity comment</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>A unique numeric ID for the activity.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "comment_id",
            "description": "<p>A unique numeric ID for the activity comment.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "parent_id",
            "description": "<p>The ID of some other object activity associated with this one.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "user_id",
            "description": "<p>The ID for the author of the activity.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": true,
            "field": "content",
            "description": "<p>Allowed HTML content for the activity.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "bp_media_ids",
            "description": "<p>Media specific IDs when Media component is enable.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "bp_videos",
            "description": "<p>Video specific IDs when Media component is enable.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "bp_documents",
            "description": "<p>Document specific IDs when Media component is enable.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "media_gif",
            "description": "<p>Save gif data into activity when Media component is enable. param(url,mp4)</p>"
          }
        ]
      }
    },
    "filename": "src/bp-activity/classes/class-bp-rest-activity-comment-endpoint.php",
    "groupTitle": "Activity"
  },
  {
    "type": "PATCH",
    "url": "/wp-json/buddyboss/v1/activity/:id/favorite",
    "title": "Activity favorite",
    "name": "UpdateBBActivityFavorite",
    "group": "Activity",
    "description": "<p>Make activity favorite/unfavorite</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>A unique numeric ID for the activity</p>"
          }
        ]
      }
    },
    "filename": "src/bp-activity/classes/class-bp-rest-activity-endpoint.php",
    "groupTitle": "Activity"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/blogs/:id",
    "title": "Blog",
    "name": "GetBBBlog",
    "group": "Blogs",
    "description": "<p>Retrieve blog</p>",
    "version": "1.0.0",
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>A unique numeric ID for the Blog.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-blogs/classes/class-bp-rest-blogs-endpoint.php",
    "groupTitle": "Blogs"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/blogs",
    "title": "Blogs",
    "name": "GetBBBlogs",
    "group": "Blogs",
    "description": "<p>Retrieve blogs</p>",
    "version": "1.0.0",
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "page",
            "description": "<p>Current page of the collection.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "per_page",
            "defaultValue": "10",
            "description": "<p>Maximum number of items to be returned in result set.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": true,
            "field": "search",
            "description": "<p>Limit results to those matching a string.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "user_id",
            "description": "<p>ID of the user whose blogs user can post to.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "include",
            "description": "<p>Ensure result set includes specific IDs.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "active",
              "alphabetical",
              "newest",
              "random"
            ],
            "optional": true,
            "field": "type",
            "defaultValue": "active",
            "description": "<p>Ensure result set includes specific IDs.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-blogs/classes/class-bp-rest-blogs-endpoint.php",
    "groupTitle": "Blogs"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/components",
    "title": "Components",
    "name": "GetBBComponents",
    "group": "Components",
    "description": "<p>Retrieve components</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "page",
            "description": "<p>Current page of the collection.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "per_page",
            "defaultValue": "10",
            "description": "<p>Maximum number of records to be returned in result set.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "all",
              "active",
              "inactive"
            ],
            "optional": true,
            "field": "status",
            "description": "<p>Limit result set to items with a specific status.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "all",
              "optional",
              "retired",
              "required"
            ],
            "optional": true,
            "field": "type",
            "description": "<p>Limit result set to items with a specific type.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-core/classes/class-bp-rest-components-endpoint.php",
    "groupTitle": "Components"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/mention",
    "title": "Mention Member",
    "name": "GetBBMention",
    "group": "Components",
    "description": "<p>Retrieve member which you want to mention in Activity OR Forum topic and reply.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "String",
            "optional": false,
            "field": "term",
            "description": "<p>Members @name suggestions.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "only_friends",
            "description": "<p>Limit result set to Friends only.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "group_id",
            "description": "<p>A unique numeric ID for the Group. Limit result set to the group.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-core/classes/class-bp-rest-mention-endpoint.php",
    "groupTitle": "Components"
  },
  {
    "type": "PATCH",
    "url": "/wp-json/buddyboss/v1/components",
    "title": "Update component",
    "name": "UpdateBBComponent",
    "group": "Components",
    "description": "<p>Update component</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "String",
            "optional": false,
            "field": "name",
            "description": "<p>Name of component which needs to be activated/deactivated. Eg: activity, notifications, settings and further...</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "activate",
              "deactivate"
            ],
            "optional": false,
            "field": "action",
            "description": "<p>Action to be performed</p>"
          }
        ]
      }
    },
    "filename": "src/bp-core/classes/class-bp-rest-components-endpoint.php",
    "groupTitle": "Components"
  },
  {
    "type": "POST",
    "url": "/wp-json/buddyboss/v1/friends/",
    "title": "Create Friendship",
    "name": "CreateBBFriendship",
    "group": "Connections",
    "description": "<p>Create friendship</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "initiator_id",
            "description": "<p>User ID of the friendship initiator.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "friend_id",
            "description": "<p>User ID of the <code>friend</code> - the one invited to the friendship.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "force",
            "description": "<p>Whether to force friendship acceptance.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-friends/classes/class-bp-rest-friends-endpoint.php",
    "groupTitle": "Connections"
  },
  {
    "type": "DELETE",
    "url": "/wp-json/buddyboss/v1/friends/:id",
    "title": "Delete Friendship",
    "name": "DeleteBBFriendship",
    "group": "Connections",
    "description": "<p>Delete friendship</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>Identifier for the friendship.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-friends/classes/class-bp-rest-friends-endpoint.php",
    "groupTitle": "Connections"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/friends/:id",
    "title": "Friendship",
    "name": "GetBBFriendship",
    "group": "Connections",
    "description": "<p>Retrieve single friendship</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>Identifier for the friendship.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-friends/classes/class-bp-rest-friends-endpoint.php",
    "groupTitle": "Connections"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/friends",
    "title": "Friendships",
    "name": "GetBBFriendships",
    "group": "Connections",
    "description": "<p>Retrieve Friendships</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "page",
            "defaultValue": "1",
            "description": "<p>Current page of the collection.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "per_page",
            "defaultValue": "10",
            "description": "<p>Maximum number of items to be returned in result set.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "user_id",
            "description": "<p>ID of the user whose friends are being retrieved.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "is_confirmed",
            "defaultValue": "0",
            "description": "<p>Wether the friendship has been accepted.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "id",
            "description": "<p>ID of a specific friendship to retrieve.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "initiator_id",
            "description": "<p>ID of the friendship initiator.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "friend_id",
            "description": "<p>ID of a specific friendship to retrieve.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "date_created",
              "initiator_user_id",
              "friend_user_id",
              "id"
            ],
            "optional": true,
            "field": "order_by",
            "defaultValue": "date_created",
            "description": "<p>Column name to order the results by.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "asc",
              "desc"
            ],
            "optional": true,
            "field": "order",
            "defaultValue": "desc",
            "description": "<p>Order results ascending or descending.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-friends/classes/class-bp-rest-friends-endpoint.php",
    "groupTitle": "Connections"
  },
  {
    "type": "DELETE",
    "url": "/wp-json/buddyboss/v1/friends/",
    "title": "Unfriend a friendship",
    "name": "UnfriendBBFriendship",
    "group": "Connections",
    "description": "<p>Unfriend friendship</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "friend_id",
            "description": "<p>ID of the Friend member.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-friends/classes/class-bp-rest-friends-endpoint.php",
    "groupTitle": "Connections"
  },
  {
    "type": "PATCH",
    "url": "/wp-json/buddyboss/v1/friends/:id",
    "title": "Update Friendship",
    "name": "UpdateBBFriendship",
    "group": "Connections",
    "description": "<p>Update friendship</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>Identifier for the friendship.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-friends/classes/class-bp-rest-friends-endpoint.php",
    "groupTitle": "Connections"
  },
  {
    "type": "POST",
    "url": "/wp-json/buddyboss/v1/document",
    "title": "Create Document",
    "name": "CreateBBDocument",
    "group": "Document",
    "description": "<p>Create Document.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Array",
            "optional": false,
            "field": "document_ids",
            "description": "<p>Document specific IDs.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "activity_id",
            "description": "<p>A unique numeric ID for the activity.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "group_id",
            "description": "<p>A unique numeric ID for the Group.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "folder_id",
            "description": "<p>A unique numeric ID for the Document Folder.</p>"
          },
          {
            "group": "Parameter",
            "type": "string",
            "optional": true,
            "field": "content",
            "description": "<p>Document Content.</p>"
          },
          {
            "group": "Parameter",
            "type": "string",
            "allowedValues": [
              "public",
              "loggedin",
              "friends",
              "onlyme",
              "grouponly"
            ],
            "optional": true,
            "field": "privacy",
            "defaultValue": "public",
            "description": "<p>Privacy of the Document.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-document/classes/class-bp-rest-document-endpoint.php",
    "groupTitle": "Document"
  },
  {
    "type": "POST",
    "url": "/wp-json/buddyboss/v1/document/folder",
    "title": "Create Folder",
    "name": "CreateBBFolder",
    "group": "Document",
    "description": "<p>Create Document Folder.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "string",
            "optional": false,
            "field": "title",
            "description": "<p>Folder Title.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "group_id",
            "description": "<p>A unique numeric ID for the Group.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "parent",
            "description": "<p>A unique numeric ID for the Parent Folder.</p>"
          },
          {
            "group": "Parameter",
            "type": "string",
            "allowedValues": [
              "public",
              "loggedin",
              "friends",
              "onlyme",
              "grouponly"
            ],
            "optional": true,
            "field": "privacy",
            "defaultValue": "public",
            "description": "<p>Privacy of the Folder.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-document/classes/class-bp-rest-document-folder-endpoint.php",
    "groupTitle": "Document"
  },
  {
    "type": "DELETE",
    "url": "/wp-json/buddyboss/v1/document/:id",
    "title": "Delete Document",
    "name": "DeleteBBDocument",
    "group": "Document",
    "description": "<p>Delete a single Document.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>A unique numeric ID for the document.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-document/classes/class-bp-rest-document-endpoint.php",
    "groupTitle": "Document"
  },
  {
    "type": "DELETE",
    "url": "/wp-json/buddyboss/v1/document/folder/:id",
    "title": "Delete Folder",
    "name": "DeleteBBFolder",
    "group": "Document",
    "description": "<p>Delete a single Folder.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>A unique numeric ID for the folder.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-document/classes/class-bp-rest-document-folder-endpoint.php",
    "groupTitle": "Document"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/document/:id",
    "title": "Get Document",
    "name": "GetBBDocument",
    "group": "Document",
    "description": "<p>Retrieve a single document.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser if the site is in Private Network."
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>A unique numeric ID for the document.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-document/classes/class-bp-rest-document-endpoint.php",
    "groupTitle": "Document"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/document/details",
    "title": "Document Details",
    "name": "GetBBDocumentDetails",
    "group": "Document",
    "description": "<p>Retrieve Document details(includes tabs and privacy options)</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser if the site is in Private Network."
      }
    ],
    "filename": "src/bp-document/classes/class-bp-rest-document-details-endpoint.php",
    "groupTitle": "Document"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/document",
    "title": "Get Documents",
    "name": "GetBBDocuments",
    "group": "Document",
    "description": "<p>Retrieve Documents.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser if the site is in Private Network."
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "page",
            "description": "<p>Current page of the collection.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "per_page",
            "defaultValue": "10",
            "description": "<p>Maximum number of items to be returned in result set.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": true,
            "field": "search",
            "description": "<p>Limit results to those matching a string.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "asc",
              "desc"
            ],
            "optional": true,
            "field": "order",
            "defaultValue": "asc",
            "description": "<p>Order sort attribute ascending or descending.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "title",
              "date_created",
              "date_modified",
              "group_id",
              "privacy",
              "id",
              "include"
            ],
            "optional": true,
            "field": "orderby",
            "defaultValue": "title",
            "description": "<p>Order by a specific parameter.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "user_id",
            "description": "<p>Limit result set to items created by a specific user (ID).</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "max",
            "description": "<p>Maximum number of results to return.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "folder_id",
            "description": "<p>A unique numeric ID for the Folder.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "group_id",
            "description": "<p>A unique numeric ID for the Group.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "activity_id",
            "description": "<p>A unique numeric ID for the Document's Activity.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "allowedValues": [
              "public",
              "loggedin",
              "friends",
              "onlyme",
              "grouponly"
            ],
            "optional": true,
            "field": "privacy",
            "defaultValue": "public",
            "description": "<p>Privacy of the Document.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "allowedValues": [
              "public",
              "friends",
              "groups",
              "personal"
            ],
            "optional": true,
            "field": "scope",
            "description": "<p>Scope of the Document.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "exclude",
            "description": "<p>Ensure result set excludes specific IDs.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "include",
            "description": "<p>Ensure result set includes specific IDs.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "both",
              "document",
              "folder"
            ],
            "optional": true,
            "field": "type",
            "defaultValue": "both",
            "description": "<p>Ensure result set includes specific document type.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "count_total",
            "defaultValue": "true",
            "description": "<p>Show total count or not.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-document/classes/class-bp-rest-document-endpoint.php",
    "groupTitle": "Document"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/document/folder/:id",
    "title": "Get Folder",
    "name": "GetBBFolder",
    "group": "Document",
    "description": "<p>Retrieve a single folder.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser if the site is in Private Network."
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>A unique numeric ID for the folder.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-document/classes/class-bp-rest-document-folder-endpoint.php",
    "groupTitle": "Document"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/document/folder",
    "title": "Get Folders",
    "name": "GetBBFolders",
    "group": "Document",
    "description": "<p>Retrieve Folders.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser if the site is in Private Network."
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "page",
            "description": "<p>Current page of the collection.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "per_page",
            "defaultValue": "10",
            "description": "<p>Maximum number of items to be returned in result set.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": true,
            "field": "search",
            "description": "<p>Limit results to those matching a string.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "asc",
              "desc"
            ],
            "optional": true,
            "field": "order",
            "defaultValue": "desc",
            "description": "<p>Order sort attribute ascending or descending.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "id",
              "title",
              "date_created",
              "user_id",
              "group_id",
              "privacy"
            ],
            "optional": true,
            "field": "orderby",
            "defaultValue": "date_created",
            "description": "<p>Order by a specific parameter.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "max",
            "description": "<p>Maximum number of results to return.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "user_id",
            "description": "<p>Limit result set to items created by a specific user (ID).</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "parent",
            "description": "<p>A unique numeric ID for the Folder.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "group_id",
            "description": "<p>A unique numeric ID for the Group.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "allowedValues": [
              "public",
              "loggedin",
              "friends",
              "onlyme",
              "grouponly"
            ],
            "optional": true,
            "field": "privacy",
            "defaultValue": "public",
            "description": "<p>Privacy of the Folder.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "exclude",
            "description": "<p>Ensure result set excludes specific IDs.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "include",
            "description": "<p>Ensure result set includes specific IDs.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "count_total",
            "defaultValue": "true",
            "description": "<p>Show total count or not.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-document/classes/class-bp-rest-document-folder-endpoint.php",
    "groupTitle": "Document"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/document/folder/tree",
    "title": "Folder tree",
    "name": "GetBBFoldersTree",
    "group": "Document",
    "description": "<p>Retrieve Folder tree</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "group_id",
            "description": "<p>A unique numeric ID for the Group.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-document/classes/class-bp-rest-document-folder-endpoint.php",
    "groupTitle": "Document"
  },
  {
    "type": "PATCH",
    "url": "/wp-json/buddyboss/v1/document/:id",
    "title": "Update Document",
    "name": "UpdateBBDocument",
    "group": "Document",
    "description": "<p>Update a single Document.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>A unique numeric ID for the document.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "folder_id",
            "description": "<p>A unique numeric ID for the folder.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "group_id",
            "description": "<p>A unique numeric ID for the Group.</p>"
          },
          {
            "group": "Parameter",
            "type": "string",
            "optional": true,
            "field": "title",
            "description": "<p>Document title.</p>"
          },
          {
            "group": "Parameter",
            "type": "string",
            "optional": true,
            "field": "content",
            "description": "<p>Document Content.</p>"
          },
          {
            "group": "Parameter",
            "type": "string",
            "allowedValues": [
              "public",
              "loggedin",
              "onlyme",
              "friends",
              "grouponly"
            ],
            "optional": true,
            "field": "privacy",
            "description": "<p>Privacy of the document.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-document/classes/class-bp-rest-document-endpoint.php",
    "groupTitle": "Document"
  },
  {
    "type": "PATCH",
    "url": "/wp-json/buddyboss/v1/document/folder/:id",
    "title": "Update Folder",
    "name": "UpdateBBFolder",
    "group": "Document",
    "description": "<p>Update a folder.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>A unique numeric ID for the folder</p>"
          },
          {
            "group": "Parameter",
            "type": "string",
            "optional": true,
            "field": "title",
            "description": "<p>Folder title.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "parent",
            "description": "<p>A unique numeric ID for the parent folder.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "group_id",
            "description": "<p>A unique numeric ID for the Group.</p>"
          },
          {
            "group": "Parameter",
            "type": "string",
            "allowedValues": [
              "public",
              "loggedin",
              "onlyme",
              "friends",
              "grouponly"
            ],
            "optional": true,
            "field": "privacy",
            "description": "<p>Privacy of the folder.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-document/classes/class-bp-rest-document-folder-endpoint.php",
    "groupTitle": "Document"
  },
  {
    "type": "POST",
    "url": "/wp-json/buddyboss/v1/document/upload",
    "title": "Upload Document",
    "name": "UploadBBDocument",
    "group": "Document",
    "description": "<p>Upload Document. This endpoint requires request to be sent in &quot;multipart/form-data&quot; format.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "String",
            "optional": false,
            "field": "file",
            "description": "<p>File object which is going to upload.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-document/classes/class-bp-rest-document-endpoint.php",
    "groupTitle": "Document"
  },
  {
    "type": "POST",
    "url": "/wp-json/buddyboss/v1/invites",
    "title": "Send Invites",
    "name": "CreateBBInvites",
    "group": "Email_Invites",
    "description": "<p>Create an Invites/Send Invites.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Array",
            "optional": false,
            "field": "fields",
            "description": "<p>Fields array with name, email_id and profile_type to create an invites.</p>"
          },
          {
            "group": "Parameter",
            "type": "string",
            "optional": true,
            "field": "email_subject",
            "description": "<p>Subject for invite a member.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": true,
            "field": "email_content",
            "description": "<p>Content for invite a member.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-invites/classes/class-bp-rest-invites-endpoint.php",
    "groupTitle": "Email_Invites"
  },
  {
    "type": "DELETE",
    "url": "/wp-json/buddyboss/v1/invites/:id",
    "title": "Revoke Invite",
    "name": "DeleteBBInvites",
    "group": "Email_Invites",
    "description": "<p>Remoke Invites.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>A unique numeric ID for the member invitation.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-invites/classes/class-bp-rest-invites-endpoint.php",
    "groupTitle": "Email_Invites"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/invites",
    "title": "Sent Invites",
    "name": "GetBBInvites",
    "group": "Email_Invites",
    "description": "<p>Retrieve Sent Invites.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "page",
            "defaultValue": "1",
            "description": "<p>Current page of the collection.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "per_page",
            "defaultValue": "10",
            "description": "<p>Maximum number of items to be returned in result set.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "asc",
              "desc"
            ],
            "optional": true,
            "field": "order",
            "defaultValue": "desc",
            "description": "<p>Designates ascending or descending order of invites.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "date",
              "ID",
              "rand"
            ],
            "optional": true,
            "field": "orderby",
            "defaultValue": "date",
            "description": "<p>Sort retrieved invites by parameter.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-invites/classes/class-bp-rest-invites-endpoint.php",
    "groupTitle": "Email_Invites"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/invites/profile-type",
    "title": "Invites Profile Type",
    "name": "GetBBInvitesProfileType",
    "group": "Email_Invites",
    "description": "<p>Retrieve Sent Invites Profile Type.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "filename": "src/bp-invites/classes/class-bp-rest-invites-endpoint.php",
    "groupTitle": "Email_Invites"
  },
  {
    "type": "POST",
    "url": "/wp-json/buddyboss/v1/reply/action/:id",
    "title": "Reply Actions",
    "name": "ActionBBPReply",
    "group": "Forum_Replies",
    "description": "<p>Actions on Reply</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>A unique numeric ID for the reply.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "spam",
              "trash"
            ],
            "optional": false,
            "field": "action",
            "description": "<p>Action name to perform on the reply.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": false,
            "field": "value",
            "description": "<p>Value for the action on reply.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-forums/classes/class-bp-rest-reply-actions-endpoint.php",
    "groupTitle": "Forum_Replies"
  },
  {
    "type": "POST",
    "url": "/wp-json/buddyboss/v1/reply",
    "title": "Create Reply",
    "name": "CreateBBPReply",
    "group": "Forum_Replies",
    "description": "<p>Create a reply.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "String",
            "optional": true,
            "field": "title",
            "description": "<p>The title of the reply.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": false,
            "field": "content",
            "description": "<p>The content of the reply.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "topic_id",
            "description": "<p>ID of the topic to perform the reply on it.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "reply_to",
            "description": "<p>Parent Reply ID for reply.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "forum_id",
            "description": "<p>Forum ID to reply on.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": true,
            "field": "tags",
            "description": "<p>Tags to add into the topic with comma separated.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "subscribe",
            "description": "<p>Whether user subscribe topic or not.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "bbp_media",
            "description": "<p>Media specific IDs when Media component is enable.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "bbp_media_gif",
            "description": "<p>Save gif data into reply when Media component is enable. param(url,mp4)</p>"
          }
        ]
      }
    },
    "filename": "src/bp-forums/classes/class-bp-rest-reply-endpoint.php",
    "groupTitle": "Forum_Replies"
  },
  {
    "type": "DELETE",
    "url": "/wp-json/buddyboss/v1/reply/:id",
    "title": "Trash/Delete Reply",
    "name": "DeleteBBPReply",
    "group": "Forum_Replies",
    "description": "<p>Trash OR Delete a Reply.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>A unique numeric ID for the reply.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-forums/classes/class-bp-rest-reply-endpoint.php",
    "groupTitle": "Forum_Replies"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/reply",
    "title": "Replies",
    "name": "GetBBPReplies",
    "group": "Forum_Replies",
    "description": "<p>Retrieve Replies</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser if the site is in Private Network."
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "page",
            "defaultValue": "1",
            "description": "<p>Current page of the collection.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "per_page",
            "defaultValue": "10",
            "description": "<p>Maximum number of items to be returned in result set.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": true,
            "field": "search",
            "description": "<p>Limit results to those matching a string.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": true,
            "field": "author",
            "description": "<p>Author ID, or comma-separated list of IDs.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "author_exclude",
            "description": "<p>An array of author IDs not to query from.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "exclude",
            "description": "<p>An array of topic IDs not to retrieve.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "include",
            "description": "<p>An array of topic IDs to retrieve.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "offset",
            "description": "<p>The number of topics to offset before retrieval.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "asc",
              "desc"
            ],
            "optional": true,
            "field": "order",
            "defaultValue": "asc",
            "description": "<p>Designates ascending or descending order of replies.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "allowedValues": [
              "meta_value",
              "date",
              "ID",
              "author",
              "title",
              "modified",
              "parent",
              "rand"
            ],
            "optional": true,
            "field": "orderby",
            "description": "<p>Sort retrieved replies by parameter.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "parent",
            "description": "<p>Topic ID or Reply ID to retrieve all the child replies.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "thread_replies",
            "description": "<p>Calculated value and the thread replies depth.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "all"
            ],
            "optional": true,
            "field": "view",
            "description": "<p>If current user can and is viewing all replies.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-forums/classes/class-bp-rest-reply-endpoint.php",
    "groupTitle": "Forum_Replies"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/reply/:id",
    "title": "Reply",
    "name": "GetBBPReply",
    "group": "Forum_Replies",
    "description": "<p>Retrieve a single reply.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser if the site is in Private Network."
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>A unique numeric ID for the reply.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-forums/classes/class-bp-rest-reply-endpoint.php",
    "groupTitle": "Forum_Replies"
  },
  {
    "type": "POST",
    "url": "/wp-json/buddyboss/v1/reply/move/:id",
    "title": "Move Reply",
    "name": "MoveBBPReply",
    "group": "Forum_Replies",
    "description": "<p>Move a Reply</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>A unique numeric ID for the reply.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "topic",
              "existing"
            ],
            "optional": false,
            "field": "move_option",
            "description": "<p>Options for Move the reply.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "destination_topic_id",
            "description": "<p>Destination Topic ID.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": true,
            "field": "destination_topic_title",
            "description": "<p>New Topic Title.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-forums/classes/class-bp-rest-reply-actions-endpoint.php",
    "groupTitle": "Forum_Replies"
  },
  {
    "type": "PATCH",
    "url": "/wp-json/buddyboss/v1/reply/:id",
    "title": "Update Reply",
    "name": "UpdateBBPReply",
    "group": "Forum_Replies",
    "description": "<p>Update a reply.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>A unique numeric ID for the reply.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": true,
            "field": "title",
            "description": "<p>The title of the reply.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": false,
            "field": "content",
            "description": "<p>The content of the reply.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "topic_id",
            "description": "<p>ID of the topic to perform the reply on it.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "reply_to",
            "description": "<p>Parent Reply ID for reply.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "forum_id",
            "description": "<p>Forum ID to reply on.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": true,
            "field": "tags",
            "description": "<p>Tags to add into the topic with comma separated.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "subscribe",
            "description": "<p>Whether user subscribe topic or not.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": true,
            "field": "reason",
            "description": "<p>Reason for editing a reply.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "log",
            "description": "<p>Keep a log of reply edit.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "bbp_media",
            "description": "<p>Media specific IDs when Media component is enable.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "bbp_media_gif",
            "description": "<p>Save gif data into reply when Media component is enable. param(url,mp4)</p>"
          }
        ]
      }
    },
    "filename": "src/bp-forums/classes/class-bp-rest-reply-endpoint.php",
    "groupTitle": "Forum_Replies"
  },
  {
    "type": "POST",
    "url": "/wp-json/buddyboss/v1/topics/action/:id",
    "title": "Topic Actions",
    "name": "ActionBBPTopic",
    "group": "Forum_Topics",
    "description": "<p>Actions on Topic</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>A unique numeric ID for the topic.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "favorite",
              "subscribe",
              "close",
              "sticky",
              "super_sticky",
              "spam",
              "trash"
            ],
            "optional": false,
            "field": "action",
            "description": "<p>Action name to perform on the topic.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": false,
            "field": "value",
            "description": "<p>Value for the action on topic.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-forums/classes/class-bp-rest-topics-actions-endpoint.php",
    "groupTitle": "Forum_Topics"
  },
  {
    "type": "POST",
    "url": "/wp-json/buddyboss/v1/topics",
    "title": "Create Topic",
    "name": "CreateBBPTopic",
    "group": "Forum_Topics",
    "description": "<p>Create a topic.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "String",
            "optional": false,
            "field": "title",
            "description": "<p>The title of the topic.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": false,
            "field": "content",
            "description": "<p>The content of the topic.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "parent",
            "description": "<p>ID of the parent Forum.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "publish",
              "closed",
              "spam",
              "trash",
              "pending"
            ],
            "optional": true,
            "field": "status",
            "defaultValue": "publish",
            "description": "<p>The current status of the topic.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "stick",
              "super",
              "unstick"
            ],
            "optional": true,
            "field": "sticky",
            "defaultValue": "unstick",
            "description": "<p>Whether the topic is sticky or not.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "group",
            "description": "<p>ID of the forum's group.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": true,
            "field": "topic_tags",
            "description": "<p>Topic's tags with comma separated.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "bbp_media",
            "description": "<p>Media specific IDs when Media component is enable.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "bbp_media_gif",
            "description": "<p>Save gif data into topic when Media component is enable. param(url,mp4)</p>"
          }
        ]
      }
    },
    "filename": "src/bp-forums/classes/class-bp-rest-topics-endpoint.php",
    "groupTitle": "Forum_Topics"
  },
  {
    "type": "DELETE",
    "url": "/wp-json/buddyboss/v1/topics/:id",
    "title": "Trash/Delete Topic",
    "name": "DeleteBBPTopic",
    "group": "Forum_Topics",
    "description": "<p>Trash OR Delete a topic.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>A unique numeric ID for the topic.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-forums/classes/class-bp-rest-topics-endpoint.php",
    "groupTitle": "Forum_Topics"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/topics/dropdown/:id",
    "title": "Topic Actions",
    "name": "DropdownBBPTopic",
    "group": "Forum_Topics",
    "description": "<p>Siblings of the topic.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>A unique numeric ID for the topic.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-forums/classes/class-bp-rest-topics-actions-endpoint.php",
    "groupTitle": "Forum_Topics"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/topics/:id",
    "title": "Topic",
    "name": "GetBBPTopic",
    "group": "Forum_Topics",
    "description": "<p>Retrieve a single topic.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser if the site is in Private Network."
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>A unique numeric ID for the topic.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-forums/classes/class-bp-rest-topics-endpoint.php",
    "groupTitle": "Forum_Topics"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/topics",
    "title": "Topics",
    "name": "GetBBPTopics",
    "group": "Forum_Topics",
    "description": "<p>Retrieve topics</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser if the site is in Private Network."
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "page",
            "defaultValue": "1",
            "description": "<p>Current page of the collection.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "per_page",
            "defaultValue": "10",
            "description": "<p>Maximum number of items to be returned in result set.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": true,
            "field": "search",
            "description": "<p>Limit results to those matching a string.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": true,
            "field": "author",
            "description": "<p>Author ID, or comma-separated list of IDs.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "author_exclude",
            "description": "<p>An array of author IDs not to query from.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "exclude",
            "description": "<p>An array of topic IDs not to retrieve.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "include",
            "description": "<p>An array of topic IDs to retrieve.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "offset",
            "description": "<p>The number of topics to offset before retrieval.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "asc",
              "desc"
            ],
            "optional": true,
            "field": "order",
            "defaultValue": "asc",
            "description": "<p>Designates ascending or descending order of topics.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "allowedValues": [
              "meta_value",
              "date",
              "ID",
              "author",
              "title",
              "modified",
              "parent",
              "rand",
              "popular",
              "activity",
              "include"
            ],
            "optional": true,
            "field": "orderby",
            "description": "<p>Sort retrieved topics by parameter.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "allowedValues": [
              "publish",
              "private",
              "hidden"
            ],
            "optional": true,
            "field": "status",
            "defaultValue": "publish",
            "description": "<p>private] Limit result set to topic assigned a specific status.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "parent",
            "description": "<p>Forum ID to retrieve all the topics.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "subscriptions",
            "description": "<p>Retrieve subscribed topics by user.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "favorites",
            "description": "<p>Retrieve favorite topics by the current user.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": true,
            "field": "tag",
            "description": "<p>Search topic with specific tag.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "all"
            ],
            "optional": true,
            "field": "view",
            "description": "<p>If current user can and is viewing all topics.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-forums/classes/class-bp-rest-topics-endpoint.php",
    "groupTitle": "Forum_Topics"
  },
  {
    "type": "POST",
    "url": "/wp-json/buddyboss/v1/topics/merge/:id",
    "title": "Merge Topic",
    "name": "MergeBBPTopic",
    "group": "Forum_Topics",
    "description": "<p>Merge Topic</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>A unique numeric ID for the topic.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "destination_id",
            "description": "<p>A unique numeric ID for the destination topic.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "subscribers",
            "defaultValue": "true",
            "description": "<p>Whether to migrate subscriptions or not.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "favorites",
            "defaultValue": "true",
            "description": "<p>Whether to migrate favorites or not.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "tags",
            "defaultValue": "true",
            "description": "<p>Whether to migrate tags or not.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-forums/classes/class-bp-rest-topics-actions-endpoint.php",
    "groupTitle": "Forum_Topics"
  },
  {
    "type": "POST",
    "url": "/wp-json/buddyboss/v1/topics/split/:id",
    "title": "Split Topic",
    "name": "SplitBBPTopic",
    "group": "Forum_Topics",
    "description": "<p>Split Topic</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>A unique numeric ID for the topic.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "reply_id",
            "description": "<p>A unique numeric ID for the topic's reply.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "reply",
              "existing"
            ],
            "optional": false,
            "field": "split_option",
            "description": "<p>Choose a valid split option.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": true,
            "field": "new_destination_title",
            "description": "<p>New Topic title for the split with option reply.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "destination_id",
            "description": "<p>A unique numeric ID for the destination topic.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "subscribers",
            "defaultValue": "true",
            "description": "<p>Whether to migrate subscriptions or not.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "favorites",
            "defaultValue": "true",
            "description": "<p>Whether to migrate favorites or not.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "tags",
            "defaultValue": "true",
            "description": "<p>Whether to migrate tags or not.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-forums/classes/class-bp-rest-topics-actions-endpoint.php",
    "groupTitle": "Forum_Topics"
  },
  {
    "type": "PATCH",
    "url": "/wp-json/buddyboss/v1/topics/:id",
    "title": "Update Topic",
    "name": "UpdateBBPTopic",
    "group": "Forum_Topics",
    "description": "<p>Update a topic.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>A unique numeric ID for the topic.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": false,
            "field": "title",
            "description": "<p>The title of the topic.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": false,
            "field": "content",
            "description": "<p>The content of the topic.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "parent",
            "description": "<p>ID of the parent Forum.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "publish",
              "closed",
              "spam",
              "trash",
              "pending"
            ],
            "optional": true,
            "field": "status",
            "defaultValue": "publish",
            "description": "<p>The current status of the topic.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "stick",
              "super",
              "unstick"
            ],
            "optional": true,
            "field": "sticky",
            "defaultValue": "unstick",
            "description": "<p>Whether the topic is sticky or not.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "group",
            "description": "<p>ID of the forum's group.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": true,
            "field": "topic_tags",
            "description": "<p>Topic's tags with comma separated.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": true,
            "field": "reason_editing",
            "description": "<p>Reason for editing a topic.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "log",
            "description": "<p>Keep a log of topic edit.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "bbp_media",
            "description": "<p>Media specific IDs when Media component is enable.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "bbp_media_gif",
            "description": "<p>Save gif data into topic when Media component is enable. param(url,mp4)</p>"
          }
        ]
      }
    },
    "filename": "src/bp-forums/classes/class-bp-rest-topics-endpoint.php",
    "groupTitle": "Forum_Topics"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/forums/link-preview",
    "title": "Link Preview",
    "name": "GetBBForumsLinkPreview",
    "group": "Forums",
    "description": "<p>Retrieve link preview Forums.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser if the site is in Private Network."
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "String",
            "optional": false,
            "field": "url",
            "description": "<p>URL for the generate link preview.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-forums/classes/class-bb-rest-forums-link-preview-endpoint.php",
    "groupTitle": "Forums"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/forums/:id",
    "title": "Forum",
    "name": "GetBBPForum",
    "group": "Forums",
    "description": "<p>Retrieve a single forum</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser if the site is in Private Network."
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>A unique numeric ID for the forum.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-forums/classes/class-bp-rest-forums-endpoint.php",
    "groupTitle": "Forums"
  },
  {
    "type": "POST",
    "url": "/wp-json/buddyboss/v1/forums/subscribe/:id",
    "title": "Subscribe/Unsubscribe Forum",
    "name": "GetBBPForumSubscribe",
    "group": "Forums",
    "description": "<p>Subscribe/Unsubscribe forum for the user.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>A unique numeric ID for the forum.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-forums/classes/class-bp-rest-forums-endpoint.php",
    "groupTitle": "Forums"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/forums",
    "title": "Forums",
    "name": "GetBBPForums",
    "group": "Forums",
    "description": "<p>Retrieve forums</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser if the site is in Private Network."
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "page",
            "defaultValue": "1",
            "description": "<p>Current page of the collection.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "per_page",
            "defaultValue": "10",
            "description": "<p>Maximum number of items to be returned in result set.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": true,
            "field": "search",
            "description": "<p>Limit results to those matching a string.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": true,
            "field": "author",
            "description": "<p>Author ID, or comma-separated list of IDs.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "author_exclude",
            "description": "<p>An array of author IDs not to query from.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "exclude",
            "description": "<p>An array of forums IDs not to retrieve.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "include",
            "description": "<p>An array of forums IDs to retrieve.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "offset",
            "description": "<p>The number of forums to offset before retrieval.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "asc",
              "desc"
            ],
            "optional": true,
            "field": "order",
            "defaultValue": "asc",
            "description": "<p>Designates ascending or descending order of forums.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "allowedValues": [
              "date",
              "ID",
              "author",
              "title",
              "name",
              "modified",
              "parent",
              "rand",
              "menu_order",
              "relevance",
              "popular",
              "activity",
              "include"
            ],
            "optional": true,
            "field": "orderby",
            "description": "<p>Sort retrieved forums by parameter..</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "allowedValues": [
              "publish",
              "private",
              "hidden"
            ],
            "optional": true,
            "field": "status",
            "defaultValue": "publish",
            "description": "<p>private] Limit result set to forums assigned a specific status.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "parent",
            "description": "<p>Forum ID to retrieve child pages for. Use 0 to only retrieve top-level forums.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "subscriptions",
            "description": "<p>Retrieve subscribed forums by user.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-forums/classes/class-bp-rest-forums-endpoint.php",
    "groupTitle": "Forums"
  },
  {
    "type": "POST",
    "url": "/wp-json/buddyboss/v1/groups/:group_id/members",
    "title": "Add Group Member",
    "name": "AddBBGroupsMembers",
    "group": "Groups",
    "description": "<p>Add Member to a group.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "group_id",
            "description": "<p>A unique numeric ID for the Group.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "admin",
              "mod",
              "member"
            ],
            "optional": true,
            "field": "role",
            "defaultValue": "member",
            "description": "<p>Group role to assign the user to.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "user_id",
            "description": "<p>A unique numeric ID for the Member to add to the Group.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-groups/classes/class-bp-rest-group-membership-endpoint.php",
    "groupTitle": "Groups"
  },
  {
    "type": "POST",
    "url": "/wp-json/buddyboss/v1/groups/:group_id/avatar",
    "title": "Create Group Avatar",
    "name": "CreateBBGroupAvatar",
    "group": "Groups",
    "description": "<p>Create group avatar. This endpoint requires request to be sent in &quot;multipart/form-data&quot; format.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "group_id",
            "description": "<p>A unique numeric ID for the Group.</p>"
          },
          {
            "group": "Parameter",
            "type": "string",
            "allowedValues": [
              "bp_avatar_upload"
            ],
            "optional": false,
            "field": "action",
            "description": "<p>Action name for upload the group avatar.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-groups/classes/class-bp-rest-attachments-group-avatar-endpoint.php",
    "groupTitle": "Groups"
  },
  {
    "type": "POST",
    "url": "/wp-json/buddyboss/v1/groups/:group_id/cover",
    "title": "Create Group Cover",
    "name": "CreateBBGroupCover",
    "group": "Groups",
    "description": "<p>Create group cover. This endpoint requires request to be sent in &quot;multipart/form-data&quot; format.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "group_id",
            "description": "<p>A unique numeric ID for the Group.</p>"
          },
          {
            "group": "Parameter",
            "type": "string",
            "allowedValues": [
              "bp_cover_image_upload"
            ],
            "optional": false,
            "field": "action",
            "description": "<p>Action name for upload the group cover image.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-groups/classes/class-bp-rest-attachments-group-cover-endpoint.php",
    "groupTitle": "Groups"
  },
  {
    "type": "POST",
    "url": "/wp-json/buddyboss/v1/groups",
    "title": "Create Group",
    "name": "CreateBBGroups",
    "group": "Groups",
    "description": "<p>Create groups</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "creator_id",
            "defaultValue": "1",
            "description": "<p>The ID of the user who created the Group.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": false,
            "field": "name",
            "description": "<p>The name of the Group.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": true,
            "field": "slug",
            "description": "<p>The URL-friendly slug for the Group.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": false,
            "field": "description",
            "description": "<p>The description of the Group.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "public",
              "private",
              "hidden"
            ],
            "optional": true,
            "field": "status",
            "defaultValue": "public",
            "description": "<p>The status of the Group.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "enable_forum",
            "description": "<p>Whether the Group has a forum enabled or not.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "parent_id",
            "description": "<p>ID of the parent Group.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "types",
            "description": "<p>Set type(s) for a group.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-groups/classes/class-bp-rest-groups-endpoint.php",
    "groupTitle": "Groups"
  },
  {
    "type": "POST",
    "url": "/wp-json/buddyboss/v1/groups/invites",
    "title": "Create Group Invite",
    "name": "CreateBBGroupsInvites",
    "group": "Groups",
    "description": "<p>Create group invitation.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "user_id",
            "description": "<p>The ID of the user who is invited to join the Group.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "inviter_id",
            "defaultValue": "1",
            "description": "<p>The ID of the user who made the invite.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "group_id",
            "description": "<p>The ID of the group to which the user has been invited.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": true,
            "field": "message",
            "description": "<p>The optional message to send to the invited user.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "send_invite",
            "defaultValue": "true",
            "description": "<p>Whether the invite should be sent to the invitee.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-groups/classes/class-bp-rest-group-invites-endpoint.php",
    "groupTitle": "Groups"
  },
  {
    "type": "POST",
    "url": "/wp-json/buddyboss/v1/groups/membership-requests",
    "title": "Create Group Membership Request",
    "name": "CreateBBGroupsMembershipsRequest",
    "group": "Groups",
    "description": "<p>Create group membership request</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "user_id",
            "description": "<p>The ID of the user who requested a Group membership.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "group_id",
            "description": "<p>The ID of the group the user requested a membership for.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": true,
            "field": "message",
            "description": "<p>The optional message to send to the invited user.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-groups/classes/class-bp-rest-group-membership-request-endpoint.php",
    "groupTitle": "Groups"
  },
  {
    "type": "POST",
    "url": "/wp-json/buddyboss/v1/groups/invites/multiple",
    "title": "Create Group Invite",
    "name": "CreateBBGroupsMultipleInvites",
    "group": "Groups",
    "description": "<p>Create Multiple group invitation.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Array",
            "optional": false,
            "field": "user_id",
            "description": "<p>The ID of the users who is invited to join the Group.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "inviter_id",
            "description": "<p>The ID of the user who made the invite.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "group_id",
            "description": "<p>The ID of the group to which the user has been invited.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": true,
            "field": "message",
            "description": "<p>The optional message to send to the invited user.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "send_invite",
            "defaultValue": "true",
            "description": "<p>Whether the invite should be sent to the invitee.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-groups/classes/class-bp-rest-group-invites-endpoint.php",
    "groupTitle": "Groups"
  },
  {
    "type": "DELETE",
    "url": "/wp-json/buddyboss/v1/groups/:id",
    "title": "Delete Group",
    "name": "DeleteBBGroup",
    "group": "Groups",
    "description": "<p>Delete a group.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>A unique numeric ID for the Group.</p>"
          },
          {
            "group": "Parameter",
            "type": "boolean",
            "optional": false,
            "field": "delete_group_forum",
            "description": "<p>Delete the Group forum if exist.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-groups/classes/class-bp-rest-groups-endpoint.php",
    "groupTitle": "Groups"
  },
  {
    "type": "DELETE",
    "url": "/wp-json/buddyboss/v1/groups/:group_id/avatar",
    "title": "Delete Group Avatar",
    "name": "DeleteBBGroupAvatar",
    "group": "Groups",
    "description": "<p>Delete group avatar</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "group_id",
            "description": "<p>A unique numeric ID for the Group.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-groups/classes/class-bp-rest-attachments-group-avatar-endpoint.php",
    "groupTitle": "Groups"
  },
  {
    "type": "DELETE",
    "url": "/wp-json/buddyboss/v1/groups/:group_id/cover",
    "title": "Delete Group Cover",
    "name": "DeleteBBGroupCover",
    "group": "Groups",
    "description": "<p>Delete group cover</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "group_id",
            "description": "<p>A unique numeric ID for the Group.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-groups/classes/class-bp-rest-attachments-group-cover-endpoint.php",
    "groupTitle": "Groups"
  },
  {
    "type": "DELETE",
    "url": "/wp-json/buddyboss/v1/groups/invites/:invite_id",
    "title": "Delete Group Invite",
    "name": "DeleteBBGroupsInvite",
    "group": "Groups",
    "description": "<p>Delete group invitation.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "invite_id",
            "description": "<p>A unique numeric ID for the group invitation.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-groups/classes/class-bp-rest-group-invites-endpoint.php",
    "groupTitle": "Groups"
  },
  {
    "type": "DELETE",
    "url": "/wp-json/buddyboss/v1/groups/:group_id/members/:user_id",
    "title": "Delete Group Member",
    "name": "DeleteBBGroupsMembers",
    "group": "Groups",
    "description": "<p>Delete group membership</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "group_id",
            "description": "<p>A unique numeric ID for the Group.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "user_id",
            "description": "<p>A unique numeric ID for the Group Member.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-groups/classes/class-bp-rest-group-membership-endpoint.php",
    "groupTitle": "Groups"
  },
  {
    "type": "DELETE",
    "url": "/wp-json/buddyboss/v1/groups/membership-requests/:request_id",
    "title": "Delete Group Membership Request",
    "name": "DeleteBBGroupsMembershipsRequest",
    "group": "Groups",
    "description": "<p>Delete group membership request</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "request_id",
            "description": "<p>A unique numeric ID for the group membership request.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-groups/classes/class-bp-rest-group-membership-request-endpoint.php",
    "groupTitle": "Groups"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/groups/:id",
    "title": "Get Group",
    "name": "GetBBGroup",
    "group": "Groups",
    "description": "<p>Retrieve single group</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser if the site is in Private Network."
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>A unique numeric ID for the Group.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-groups/classes/class-bp-rest-groups-endpoint.php",
    "groupTitle": "Groups"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/groups/:group_id/avatar",
    "title": "Group Avatar",
    "name": "GetBBGroupAvatar",
    "group": "Groups",
    "description": "<p>Retrieve group avatar</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser if the site is in Private Network."
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "group_id",
            "description": "<p>A unique numeric ID for the Group.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "html",
            "defaultValue": "false",
            "description": "<p>Whether to return an <img> HTML element, vs a raw URL to a group avatar.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": true,
            "field": "alt",
            "description": "<p>The alt attribute for the <img> element.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-groups/classes/class-bp-rest-attachments-group-avatar-endpoint.php",
    "groupTitle": "Groups"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/groups/:group_id/cover",
    "title": "Group Cover",
    "name": "GetBBGroupCover",
    "group": "Groups",
    "description": "<p>Retrieve group cover</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser if the site is in Private Network."
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "group_id",
            "description": "<p>A unique numeric ID for the Group.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-groups/classes/class-bp-rest-attachments-group-cover-endpoint.php",
    "groupTitle": "Groups"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/groups",
    "title": "Get Groups",
    "name": "GetBBGroups",
    "group": "Groups",
    "description": "<p>Retrieve groups</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser if the site is in Private Network."
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "page",
            "defaultValue": "1",
            "description": "<p>Current page of the collection.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "per_page",
            "defaultValue": "10",
            "description": "<p>Maximum number of items to be returned in result set.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": true,
            "field": "search",
            "description": "<p>Limit results to those matching a string.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "active",
              "newest",
              "alphabetical",
              "random",
              "popular",
              "include"
            ],
            "optional": true,
            "field": "type",
            "defaultValue": "active",
            "description": "<p>Shorthand for certain orderby/order combinations.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "asc",
              "desc"
            ],
            "optional": true,
            "field": "order",
            "defaultValue": "desc",
            "description": "<p>Order sort attribute ascending or descending.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "date_created",
              "last_activity",
              "total_member_count",
              "name",
              "random"
            ],
            "optional": true,
            "field": "orderby",
            "defaultValue": "date_created",
            "description": "<p>Order Groups by which attribute.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "allowedValues": [
              "public",
              "private",
              "hidden"
            ],
            "optional": true,
            "field": "status",
            "description": "<p>Group statuses to limit results to.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "user_id",
            "description": "<p>Pass a user_id to limit to only Groups that this user is a member of.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "parent_id",
            "description": "<p>Get Groups that are children of the specified Group(s) IDs.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "meta",
            "description": "<p>Get Groups based on their meta data information.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "include",
            "description": "<p>Ensure result set includes Groups with specific IDs.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "exclude",
            "description": "<p>Ensure result set excludes Groups with specific IDs.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": true,
            "field": "group_type",
            "description": "<p>Limit results set to a certain Group type.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "enable_forum",
            "description": "<p>Whether the Group has a forum enabled or not.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "show_hidden",
            "description": "<p>Whether results should include hidden Groups.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "all",
              "personal"
            ],
            "optional": true,
            "field": "scope",
            "defaultValue": "all",
            "description": "<p>Limit result set to items with a specific scope.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "can_post",
            "description": "<p>Fetch current users groups which can post activity in it.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-groups/classes/class-bp-rest-groups-endpoint.php",
    "groupTitle": "Groups"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/groups/:id/detail",
    "title": "Group Detail",
    "name": "GetBBGroupsDetail",
    "group": "Groups",
    "description": "<p>Retrieve groups detail tabs.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser if the site is in Private Network."
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>A unique numeric ID for the Group.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-groups/classes/class-bp-rest-groups-details-endpoint.php",
    "groupTitle": "Groups"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/groups/details",
    "title": "Groups Details",
    "name": "GetBBGroupsDetails",
    "group": "Groups",
    "description": "<p>Retrieve groups details(includes tabs and order_options)</p>",
    "version": "1.0.0",
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "active",
              "popular",
              "newest",
              "alphabetical"
            ],
            "optional": true,
            "field": "type",
            "description": "<p>Reorder group by type.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-groups/classes/class-bp-rest-groups-details-endpoint.php",
    "groupTitle": "Groups"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/groups/:invite_id",
    "title": "Invite",
    "name": "GetBBGroupsInvite",
    "group": "Groups",
    "description": "<p>Retrieve single invitation.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "invite_id",
            "description": "<p>A unique numeric ID for the group invitation.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-groups/classes/class-bp-rest-group-invites-endpoint.php",
    "groupTitle": "Groups"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/groups/invites",
    "title": "Invites",
    "name": "GetBBGroupsInvites",
    "group": "Groups",
    "description": "<p>Retrieve invites for group</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "page",
            "description": "<p>Current page of the collection.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "per_page",
            "defaultValue": "10",
            "description": "<p>Maximum number of items to be returned in result set.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": true,
            "field": "group_id",
            "description": "<p>ID of the group to limit results to.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": true,
            "field": "user_id",
            "description": "<p>Return only invitations extended to this user.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "inviter_id",
            "description": "<p>Return only invitations extended by this user.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "draft",
              "sent",
              "all"
            ],
            "optional": true,
            "field": "invite_sent",
            "defaultValue": "sent",
            "description": "<p>Limit result set to invites that have been sent, not sent, or include all.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "id",
              "include"
            ],
            "optional": true,
            "field": "orderby",
            "defaultValue": "id",
            "description": "<p>Order invites by which attribute.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "asc",
              "desc"
            ],
            "optional": true,
            "field": "sort_order",
            "defaultValue": "desc",
            "description": "<p>Order sort attribute ascending or descending.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-groups/classes/class-bp-rest-group-invites-endpoint.php",
    "groupTitle": "Groups"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/groups/:group_id/members/",
    "title": "Get Group Members",
    "name": "GetBBGroupsMembers",
    "group": "Groups",
    "description": "<p>Retrieve group Members.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser if the site is in Private Network."
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "group_id",
            "description": "<p>A unique numeric ID for the Group.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "page",
            "defaultValue": "1",
            "description": "<p>Current page of the collection.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "per_page",
            "defaultValue": "10",
            "description": "<p>Maximum number of items to be returned in result set.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": true,
            "field": "search",
            "description": "<p>Limit results to those matching a string.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "last_joined",
              "first_joined",
              "alphabetical",
              "group_activity",
              "group_role"
            ],
            "optional": true,
            "field": "status",
            "defaultValue": "last_joined",
            "description": "<p>Sort the order of results by the status of the group members.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "allowedValues": [
              "admin",
              "mod",
              "member",
              "banned"
            ],
            "optional": true,
            "field": "roles",
            "description": "<p>Ensure result set includes specific group roles.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "exclude",
            "description": "<p>Ensure result set excludes specific member IDs.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "exclude_admins",
            "defaultValue": "true",
            "description": "<p>Whether results should exclude group admins and mods.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "exclude_banned",
            "defaultValue": "true",
            "description": "<p>Whether results should exclude banned group members.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "invite",
              "invite-friends",
              "invited",
              "message"
            ],
            "optional": true,
            "field": "scope",
            "description": "<p>Limit result set to items with a specific scope.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-groups/classes/class-bp-rest-group-membership-endpoint.php",
    "groupTitle": "Groups"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/groups/membership-requests",
    "title": "Group Membership Requests",
    "name": "GetBBGroupsMembershipsRequest",
    "group": "Groups",
    "description": "<p>Retrieve group membership requests</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "page",
            "defaultValue": "1",
            "description": "<p>Current page of the collection.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "per_page",
            "defaultValue": "10",
            "description": "<p>Maximum number of items to be returned in result set.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "group_id",
            "defaultValue": "0",
            "description": "<p>The ID of the group the user requested a membership for.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "user_id",
            "defaultValue": "0",
            "description": "<p>Return only Membership requests made by a specific user.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-groups/classes/class-bp-rest-group-membership-request-endpoint.php",
    "groupTitle": "Groups"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/groups/membership-requests/:request_id",
    "title": "Get Membership Request",
    "name": "GetBBGroupsMembershipsRequest",
    "group": "Groups",
    "description": "<p>Retrieve group membership request by ID.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "request_id",
            "description": "<p>A unique numeric ID for the group membership request.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-groups/classes/class-bp-rest-group-membership-request-endpoint.php",
    "groupTitle": "Groups"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/groups/:id/settings",
    "title": "Group Settings",
    "name": "GetBBGroupsSettings",
    "group": "Groups",
    "description": "<p>Retrieve groups settings.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>A unique numeric ID for the Group.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "edit-details",
              "group-settings",
              "forum",
              "courses"
            ],
            "optional": false,
            "field": "nav",
            "description": "<p>Navigation item slug.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-groups/classes/class-bp-rest-group-settings-endpoint.php",
    "groupTitle": "Groups"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/groups/types",
    "title": "Groups Types",
    "name": "GetBBGroupsTypes",
    "group": "Groups",
    "description": "<p>Retrieve Groups Types.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser if the site is in Private Network."
      }
    ],
    "filename": "src/bp-groups/classes/class-bp-rest-groups-types-endpoint.php",
    "groupTitle": "Groups"
  },
  {
    "type": "PATCH",
    "url": "/wp-json/buddyboss/v1/groups/:id",
    "title": "Update Group",
    "name": "UpdateBBGroup",
    "group": "Groups",
    "description": "<p>Update a group</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>A unique numeric ID for the Group.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "creator_id",
            "description": "<p>The ID of the user who created the Group.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": true,
            "field": "name",
            "description": "<p>The name of the Group.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": true,
            "field": "description",
            "description": "<p>The description of the Group.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "public",
              "private",
              "hidden"
            ],
            "optional": true,
            "field": "status",
            "description": "<p>The status of the Group.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "enable_forum",
            "description": "<p>Whether the Group has a forum enabled or not.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "parent_id",
            "description": "<p>ID of the parent Group.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "types",
            "description": "<p>Set type(s) for a group.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "append_types",
            "description": "<p>Append type(s) for a group.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "remove_types",
            "description": "<p>Remove type(s) for a group.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-groups/classes/class-bp-rest-groups-endpoint.php",
    "groupTitle": "Groups"
  },
  {
    "type": "PATCH",
    "url": "/wp-json/buddyboss/v1/groups/invites/:invite_id",
    "title": "Update Group Invite",
    "name": "UpdateBBGroupsInvite",
    "group": "Groups",
    "description": "<p>Update group invitation.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "invite_id",
            "description": "<p>A unique numeric ID for the group invitation.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-groups/classes/class-bp-rest-group-invites-endpoint.php",
    "groupTitle": "Groups"
  },
  {
    "type": "PATCH",
    "url": "/wp-json/buddyboss/v1/groups/:group_id/members/:user_id",
    "title": "Update Group Member",
    "name": "UpdateBBGroupsMembers",
    "group": "Groups",
    "description": "<p>Update user status on a group (add, remove, promote, demote or ban).</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "group_id",
            "description": "<p>A unique numeric ID for the Group.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "user_id",
            "description": "<p>A unique numeric ID for the Group Member.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "admin",
              "mod",
              "member"
            ],
            "optional": true,
            "field": "role",
            "defaultValue": "member",
            "description": "<p>Group role to assign the user to.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "promote",
              "demote",
              "ban",
              "unban"
            ],
            "optional": true,
            "field": "action",
            "defaultValue": "promote",
            "description": "<p>Group role to assign the user to.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-groups/classes/class-bp-rest-group-membership-endpoint.php",
    "groupTitle": "Groups"
  },
  {
    "type": "PATCH",
    "url": "/wp-json/buddyboss/v1/groups/membership-requests/:request_id",
    "title": "",
    "name": "UpdateBBGroupsMembershipsRequest",
    "group": "Groups",
    "description": "<p>Update group membership request</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "request_id",
            "description": "<p>A unique numeric ID for the group membership request.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-groups/classes/class-bp-rest-group-membership-request-endpoint.php",
    "groupTitle": "Groups"
  },
  {
    "type": "PATCH",
    "url": "/wp-json/buddyboss/v1/groups/:id/settings",
    "title": "Update Group Settings",
    "name": "UpdateBBGroupsSettings",
    "group": "Groups",
    "description": "<p>Update Group settings.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>A unique numeric ID for the Group.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": false,
            "field": "fields",
            "description": "<p>The list of fields to update with name and value of the field.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-groups/classes/class-bp-rest-group-settings-endpoint.php",
    "groupTitle": "Groups"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/learndash/courses",
    "title": "LearnDash Courses",
    "name": "GetBBLearndashCourses",
    "group": "Learndash",
    "description": "<p>Retrieve courses.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "page",
            "defaultValue": "1",
            "description": "<p>Current page of the collection.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "per_page",
            "defaultValue": "10",
            "description": "<p>Maximum number of items to be returned in result set.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": true,
            "field": "search",
            "description": "<p>Limit results to those matching a string.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "author",
            "description": "<p>Limit result set to posts assigned to specific authors.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "author_exclude",
            "description": "<p>Ensure result set excludes posts assigned to specific authors.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": true,
            "field": "after",
            "description": "<p>Limit response to resources published after a given ISO8601 compliant date.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": true,
            "field": "before",
            "description": "<p>Limit response to resources published before a given ISO8601 compliant date.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "exclude",
            "description": "<p>Ensure result set excludes specific IDs.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "include",
            "description": "<p>Limit result set to specific IDs.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "offset",
            "description": "<p>Offset the result set by a specific number of items.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "asc",
              "desc"
            ],
            "optional": true,
            "field": "order",
            "defaultValue": "asc",
            "description": "<p>Order sort attribute ascending or descending.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "author",
              "date",
              "id",
              "include",
              "modified",
              "parent",
              "relevance",
              "slug",
              "title",
              "menu_order"
            ],
            "optional": true,
            "field": "orderby",
            "defaultValue": "date",
            "description": "<p>Sort collection by object attribute.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "allowedValues": [
              "publish",
              "future",
              "draft",
              "pending",
              "private",
              "trash",
              "auto-draft",
              "inherit",
              "request-pending",
              "request-confirmed",
              "request-failed",
              "request-completed",
              "closed",
              "spam",
              "orphan",
              "hidden",
              "graded",
              "not_graded",
              "any"
            ],
            "optional": true,
            "field": "status",
            "defaultValue": "date",
            "description": "<p>Sort collection by object attribute.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "course_category",
            "description": "<p>Limit result set to all items that have the specified term assigned in the ld_course_category taxonomy.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "course_category_exclude",
            "description": "<p>Limit result set to all items except those that have the specified term assigned in the ld_course_category taxonomy.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "course_tag",
            "description": "<p>Limit result set to all items that have the specified term assigned in the ld_course_tag taxonomy.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "course_tag_exclude",
            "description": "<p>Limit result set to all items except those that have the specified term assigned in the ld_course_tag taxonomy.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "group_id",
            "description": "<p>Limit response to specific buddypress group.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-integrations/learndash/classes/class-bp-rest-learndash-courses-endpoint.php",
    "groupTitle": "Learndash"
  },
  {
    "type": "POST",
    "url": "/wp-json/buddyboss/v1/media/albums",
    "title": "Create Album",
    "name": "CreateBBAlbum",
    "group": "Media",
    "description": "<p>Create an Album.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "String",
            "optional": false,
            "field": "title",
            "description": "<p>New Album Title.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "public",
              "loggedin",
              "friends",
              "onlyme",
              "grouponly"
            ],
            "optional": true,
            "field": "privacy",
            "defaultValue": "public",
            "description": "<p>The privacy of album.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "group_id",
            "description": "<p>A unique numeric ID for the Group.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "user_id",
            "description": "<p>The ID for the author of the Album.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "upload_ids",
            "description": "<p>Media specific IDs.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-media/classes/class-bp-rest-media-albums-endpoint.php",
    "groupTitle": "Media"
  },
  {
    "type": "POST",
    "url": "/wp-json/buddyboss/v1/media",
    "title": "Create Photos",
    "name": "CreateBBPhotos",
    "group": "Media",
    "description": "<p>Create Media Photos. This endpoint requires request to be sent in &quot;multipart/form-data&quot; format.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Array",
            "optional": false,
            "field": "upload_ids",
            "description": "<p>Media specific IDs.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "activity_id",
            "description": "<p>A unique numeric ID for the activity.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "group_id",
            "description": "<p>A unique numeric ID for the Group.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "album_id",
            "description": "<p>A unique numeric ID for the Media Album.</p>"
          },
          {
            "group": "Parameter",
            "type": "string",
            "optional": true,
            "field": "content",
            "description": "<p>Media Content.</p>"
          },
          {
            "group": "Parameter",
            "type": "string",
            "allowedValues": [
              "public",
              "loggedin",
              "onlyme",
              "friends",
              "grouponly"
            ],
            "optional": true,
            "field": "privacy",
            "defaultValue": "public",
            "description": "<p>Privacy of the media.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-media/classes/class-bp-rest-media-endpoint.php",
    "groupTitle": "Media"
  },
  {
    "type": "DELETE",
    "url": "/wp-json/buddyboss/v1/media/albums/:id",
    "title": "Delete Album",
    "name": "DeleteBBAlbum",
    "group": "Media",
    "description": "<p>Delete a single Album.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>A unique numeric ID for the Album.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-media/classes/class-bp-rest-media-albums-endpoint.php",
    "groupTitle": "Media"
  },
  {
    "type": "DELETE",
    "url": "/wp-json/buddyboss/v1/media/:id",
    "title": "Delete Photo",
    "name": "DeleteBBPhoto",
    "group": "Media",
    "description": "<p>Delete a single Photo.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>A unique numeric ID for the media photo.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-media/classes/class-bp-rest-media-endpoint.php",
    "groupTitle": "Media"
  },
  {
    "type": "DELETE",
    "url": "/wp-json/buddyboss/v1/media/",
    "title": "Delete Medias",
    "name": "DeleteBBPhotos",
    "group": "Media",
    "description": "<p>Delete Multiple Photos/Videos.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Array",
            "optional": false,
            "field": "media_ids",
            "description": "<p>A unique numeric IDs for the media photo/video.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-media/classes/class-bp-rest-media-endpoint.php",
    "groupTitle": "Media"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/media/albums/:id",
    "title": "Get Album",
    "name": "GetBBAlbum",
    "group": "Media",
    "description": "<p>Retrieve a single Album.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser if the site is in Private Network."
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>A unique numeric ID for the Album.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "media_page",
            "defaultValue": "1",
            "description": "<p>Current page of Album Medias.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "media_per_page",
            "defaultValue": "10",
            "description": "<p>A unique numeric ID for the Album.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-media/classes/class-bp-rest-media-albums-endpoint.php",
    "groupTitle": "Media"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/media/albums",
    "title": "Get Albums",
    "name": "GetBBAlbums",
    "group": "Media",
    "description": "<p>Retrieve Albums.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser if the site is in Private Network."
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "page",
            "description": "<p>Current page of the collection.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "per_page",
            "defaultValue": "10",
            "description": "<p>Maximum number of items to be returned in result set.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": true,
            "field": "search",
            "description": "<p>Limit results to those matching a string.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "asc",
              "desc"
            ],
            "optional": true,
            "field": "order",
            "defaultValue": "desc",
            "description": "<p>Order sort attribute ascending or descending.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "date_created",
              "menu_order"
            ],
            "optional": true,
            "field": "orderby",
            "defaultValue": "date_created",
            "description": "<p>Order albums by which attribute.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "max",
            "description": "<p>Maximum number of results to return.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "user_id",
            "description": "<p>Limit result set to items created by a specific user (ID).</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "group_id",
            "description": "<p>A unique numeric ID for the Group.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "public",
              "loggedin",
              "friends",
              "onlyme",
              "grouponly"
            ],
            "optional": true,
            "field": "privacy",
            "defaultValue": "public",
            "description": "<p>The privacy of album.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "exclude",
            "description": "<p>Ensure result set excludes specific IDs.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "include",
            "description": "<p>Ensure result set includes specific IDs.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "count_total",
            "defaultValue": "true",
            "description": "<p>Show total count or not.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-media/classes/class-bp-rest-media-albums-endpoint.php",
    "groupTitle": "Media"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/media/details",
    "title": "Media Details",
    "name": "GetBBMediaDetails",
    "group": "Media",
    "description": "<p>Retrieve Media details(includes tabs and privacy options)</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser if the site is in Private Network."
      }
    ],
    "filename": "src/bp-media/classes/class-bp-rest-media-details-endpoint.php",
    "groupTitle": "Media"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/media/:id",
    "title": "Get Photo",
    "name": "GetBBPhoto",
    "group": "Media",
    "description": "<p>Retrieve a single photo.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser if the site is in Private Network."
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>A unique numeric ID for the media photo.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-media/classes/class-bp-rest-media-endpoint.php",
    "groupTitle": "Media"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/media",
    "title": "Get Photos",
    "name": "GetBBPhotos",
    "group": "Media",
    "description": "<p>Retrieve photos.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser if the site is in Private Network."
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "page",
            "description": "<p>Current page of the collection.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "per_page",
            "defaultValue": "10",
            "description": "<p>Maximum number of items to be returned in result set.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": true,
            "field": "search",
            "description": "<p>Limit results to those matching a string.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "asc",
              "desc"
            ],
            "optional": true,
            "field": "order",
            "defaultValue": "desc",
            "description": "<p>Order sort attribute ascending or descending.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "date_created",
              "menu_order",
              "id",
              "include"
            ],
            "optional": true,
            "field": "orderby",
            "defaultValue": "date_created",
            "description": "<p>Order by a specific parameter.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "user_id",
            "description": "<p>Limit result set to items created by a specific user (ID).</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "max",
            "description": "<p>Maximum number of results to return.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "album_id",
            "description": "<p>A unique numeric ID for the Album.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "group_id",
            "description": "<p>A unique numeric ID for the Group.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "activity_id",
            "description": "<p>A unique numeric ID for the Media's Activity.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "allowedValues": [
              "public",
              "loggedin",
              "onlyme",
              "friends",
              "grouponly"
            ],
            "optional": true,
            "field": "privacy",
            "defaultValue": "public",
            "description": "<p>Privacy of the media.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "allowedValues": [
              "friends",
              "groups",
              "personal"
            ],
            "optional": true,
            "field": "scope",
            "description": "<p>Scope of the media.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "exclude",
            "description": "<p>Ensure result set excludes specific IDs.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "include",
            "description": "<p>Ensure result set includes specific IDs.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "count_total",
            "defaultValue": "true",
            "description": "<p>Show total count or not.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-media/classes/class-bp-rest-media-endpoint.php",
    "groupTitle": "Media"
  },
  {
    "type": "POST",
    "url": "/wp-json/buddyboss/v1/media/move",
    "title": "Move Medias",
    "name": "MoveBBPhotos",
    "group": "Media",
    "description": "<p>Move Medias into the albums.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Array",
            "optional": false,
            "field": "media_ids",
            "description": "<p>Media specific IDs.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "album_id",
            "description": "<p>A unique numeric ID for the Media Album.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "group_id",
            "description": "<p>A unique numeric ID for the Group.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-media/classes/class-bp-rest-media-details-endpoint.php",
    "groupTitle": "Media"
  },
  {
    "type": "PATCH",
    "url": "/wp-json/buddyboss/v1/media/albums/:id",
    "title": "Update Album",
    "name": "UpdateBBAlbum",
    "group": "Media",
    "description": "<p>Update a single Album.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>A unique numeric ID for the Album.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": true,
            "field": "title",
            "description": "<p>New Album Title.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "public",
              "loggedin",
              "friends",
              "onlyme",
              "grouponly"
            ],
            "optional": true,
            "field": "privacy",
            "description": "<p>The privacy of album.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-media/classes/class-bp-rest-media-albums-endpoint.php",
    "groupTitle": "Media"
  },
  {
    "type": "PATCH",
    "url": "/wp-json/buddyboss/v1/media/:id",
    "title": "Update Photo",
    "name": "UpdateBBPhoto",
    "group": "Media",
    "description": "<p>Update a single Photo.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>A unique numeric ID for the media photo.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "album_id",
            "description": "<p>A unique numeric ID for the Album.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "group_id",
            "description": "<p>A unique numeric ID for the Group.</p>"
          },
          {
            "group": "Parameter",
            "type": "string",
            "optional": true,
            "field": "content",
            "description": "<p>Media Content.</p>"
          },
          {
            "group": "Parameter",
            "type": "string",
            "allowedValues": [
              "public",
              "loggedin",
              "onlyme",
              "friends",
              "grouponly"
            ],
            "optional": true,
            "field": "privacy",
            "description": "<p>Privacy of the media.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-media/classes/class-bp-rest-media-endpoint.php",
    "groupTitle": "Media"
  },
  {
    "type": "POST",
    "url": "/wp-json/buddyboss/v1/media/upload",
    "title": "Upload Media",
    "name": "UploadBBMedia",
    "group": "Media",
    "description": "<p>Upload Media. This endpoint requires request to be sent in &quot;multipart/form-data&quot; format.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "String",
            "optional": false,
            "field": "file",
            "description": "<p>File object which is going to upload.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-media/classes/class-bp-rest-media-endpoint.php",
    "groupTitle": "Media"
  },
  {
    "type": "POST",
    "url": "/wp-json/buddyboss/v1/members/:user_id/avatar",
    "title": "Create Member Avatar",
    "name": "CreateBBMemberAvatar",
    "group": "Members",
    "description": "<p>Create member avatar. This endpoint requires request to be sent in &quot;multipart/form-data&quot; format.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "user_id",
            "description": "<p>A unique numeric ID for the Member.</p>"
          },
          {
            "group": "Parameter",
            "type": "string",
            "allowedValues": [
              "bp_avatar_upload"
            ],
            "optional": false,
            "field": "action",
            "description": "<p>Action name for upload the Member avatar.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-members/classes/class-bp-rest-attachments-member-avatar-endpoint.php",
    "groupTitle": "Members"
  },
  {
    "type": "POST",
    "url": "/wp-json/buddyboss/v1/members/:user_id/cover",
    "title": "Create Member Cover",
    "name": "CreateBBMemberCover",
    "group": "Members",
    "description": "<p>Create member cover. This endpoint requires request to be sent in &quot;multipart/form-data&quot; format.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "user_id",
            "description": "<p>A unique numeric ID for the User.</p>"
          },
          {
            "group": "Parameter",
            "type": "string",
            "allowedValues": [
              "bp_cover_image_upload"
            ],
            "optional": false,
            "field": "action",
            "description": "<p>Action name for upload the Member cover image.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-members/classes/class-bp-rest-attachments-member-cover-endpoint.php",
    "groupTitle": "Members"
  },
  {
    "type": "DELETE",
    "url": "/wp-json/buddyboss/v1/members/:user_id/cover",
    "title": "Delete Member Avatar",
    "name": "DeleteBBMemberAvatar",
    "group": "Members",
    "description": "<p>Delete member avatar</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "user_id",
            "description": "<p>A unique numeric ID for the Member.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-members/classes/class-bp-rest-attachments-member-avatar-endpoint.php",
    "groupTitle": "Members"
  },
  {
    "type": "DELETE",
    "url": "/wp-json/buddyboss/v1/members/:user_id/cover",
    "title": "Delete Member Cover",
    "name": "DeleteBBMemberCover",
    "group": "Members",
    "description": "<p>Delete member cover</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "user_id",
            "description": "<p>A unique numeric ID for the User.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-members/classes/class-bp-rest-attachments-member-cover-endpoint.php",
    "groupTitle": "Members"
  },
  {
    "type": "DELETE",
    "url": "/wp-json/buddyboss/v1/members/:id",
    "title": "Delete Member",
    "name": "DeleteBBMembers",
    "group": "Members",
    "description": "<p>Delete a member.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>A unique numeric ID for the Member.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-members/classes/class-bp-rest-members-endpoint.php",
    "groupTitle": "Members"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/members/:user_id/avatar",
    "title": "Member Avatar",
    "name": "GetBBMemberAvatar",
    "group": "Members",
    "description": "<p>Retrieve member avatar</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser if the site is in Private Network."
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "user_id",
            "description": "<p>A unique numeric ID for the Member.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "html",
            "defaultValue": "false",
            "description": "<p>Whether to return an <img> HTML element, vs a raw URL to an avatar.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": true,
            "field": "alt",
            "description": "<p>The alt attribute for the <img> element.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "no_grav",
            "defaultValue": "false",
            "description": "<p>Whether to disable the default Gravatar fallback.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-members/classes/class-bp-rest-attachments-member-avatar-endpoint.php",
    "groupTitle": "Members"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/members/:user_id/cover",
    "title": "Member Cover",
    "name": "GetBBMemberCover",
    "group": "Members",
    "description": "<p>Retrieve member cover</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser if the site is in Private Network."
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "user_id",
            "description": "<p>A unique numeric ID for the User.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-members/classes/class-bp-rest-attachments-member-cover-endpoint.php",
    "groupTitle": "Members"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/members/me/permissions",
    "title": "Member Permissions",
    "name": "GetBBMemberPermissions",
    "group": "Members",
    "description": "<p>Retrieve Member Permissions</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "filename": "src/bp-members/classes/class-bp-rest-members-permissions-endpoint.php",
    "groupTitle": "Members"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/members",
    "title": "Get Members",
    "name": "GetBBMembers",
    "group": "Members",
    "description": "<p>Retrieve Members</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser if the site is in Private Network."
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "page",
            "defaultValue": "1",
            "description": "<p>Current page of the collection.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "per_page",
            "defaultValue": "10",
            "description": "<p>Maximum number of items to be returned in result set.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": true,
            "field": "search",
            "description": "<p>Limit results to those matching a string.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "active",
              "newest",
              "alphabetical",
              "random",
              "online",
              "popular",
              "include"
            ],
            "optional": true,
            "field": "type",
            "defaultValue": "newest",
            "description": "<p>Shorthand for certain orderby/order combinations.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "user_id",
            "description": "<p>Limit results to friends of a user.</p>"
          },
          {
            "group": "Parameter",
            "type": "Arrays",
            "optional": true,
            "field": "user_ids",
            "description": "<p>Pass IDs of users to limit result set.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "include",
            "description": "<p>Ensure result set includes specific IDs.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "exclude",
            "description": "<p>Ensure result set excludes specific IDs.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": true,
            "field": "member_type",
            "description": "<p>Limit results set to certain type(s).</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": true,
            "field": "xprofile",
            "description": "<p>Limit results set to a certain xProfile field.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "bp_ps_search",
            "description": "<p>Profile Search form field data(s).</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "all",
              "personal",
              "following",
              "followers"
            ],
            "optional": true,
            "field": "scope",
            "defaultValue": "all",
            "description": "<p>Limit result set to items with a specific scope.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-members/classes/class-bp-rest-members-endpoint.php",
    "groupTitle": "Members"
  },
  {
    "type": "POST",
    "url": "/wp-json/buddyboss/v1/members/presence",
    "title": "Member Presence State",
    "name": "GetBBMembers-MembersPresence",
    "group": "Members",
    "description": "<p>Members Presence.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Array",
            "optional": false,
            "field": "ids",
            "description": "<p>A unique numeric ID for the members</p>"
          }
        ]
      }
    },
    "filename": "src/bp-members/classes/class-bp-rest-members-actions-endpoint.php",
    "groupTitle": "Members"
  },
  {
    "type": "POST",
    "url": "/wp-json/buddyboss/v1/members/action/:user_id",
    "title": "Member Action",
    "name": "GetBBMembers-UpdateMembersAction",
    "group": "Members",
    "description": "<p>Update members action</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "user_id",
            "description": "<p>A unique numeric ID for the member.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "follow",
              "unfollow"
            ],
            "optional": false,
            "field": "action",
            "description": "<p>Action name which you want to perform for the member.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-members/classes/class-bp-rest-members-actions-endpoint.php",
    "groupTitle": "Members"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/members/:id/detail",
    "title": "Members Detail",
    "name": "GetBBMembersDetail",
    "group": "Members",
    "description": "<p>Retrieve Member detail tabs.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser if the site is in Private Network."
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>A unique numeric ID for the member.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-members/classes/class-bp-rest-members-details-endpoint.php",
    "groupTitle": "Members"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/members/details",
    "title": "Members Details",
    "name": "GetBBMembersDetails",
    "group": "Members",
    "description": "<p>Retrieve Members details(includes tabs and order_options)</p>",
    "version": "1.0.0",
    "filename": "src/bp-members/classes/class-bp-rest-members-details-endpoint.php",
    "groupTitle": "Members"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/members/profile-dropdown",
    "title": "Profile Dropdown",
    "name": "GetBBMembersProfileDropdown",
    "group": "Members",
    "description": "<p>Retrieve Member Profile Dropdown.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "filename": "src/bp-members/classes/class-bp-rest-members-details-endpoint.php",
    "groupTitle": "Members"
  },
  {
    "type": "POST",
    "url": "/wp-json/buddyboss/v1/messages/group",
    "title": "Create Group Thread",
    "name": "CreateBBGroupThread",
    "group": "Messages",
    "description": "<p>Create Group thread</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "String",
            "optional": false,
            "field": "message",
            "description": "<p>Content of the Message to add to the Thread.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "group_id",
            "description": "<p>A unique numeric ID for the Group.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "user_id",
            "description": "<p>Limit result to messages created by a specific user.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "open",
              "private"
            ],
            "optional": false,
            "field": "type",
            "defaultValue": "open",
            "description": "<p>Type of message, Group thread or private reply.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "all",
              "individual"
            ],
            "optional": false,
            "field": "users",
            "defaultValue": "all",
            "description": "<p>Group thread users individual or all.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "users_list",
            "description": "<p>Limit result to messages created by a specific user.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-messages/classes/class-bp-rest-group-messages-endpoint.php",
    "groupTitle": "Messages"
  },
  {
    "type": "POST",
    "url": "/wp-json/buddyboss/v1/messages",
    "title": "Create Thread",
    "name": "CreateBBThread",
    "group": "Messages",
    "description": "<p>Create thread</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "id",
            "description": "<p>ID of the Messages Thread. Required when replying to an existing Thread.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": true,
            "field": "subject",
            "description": "<p>Subject of the Message initializing the Thread.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": false,
            "field": "message",
            "description": "<p>Content of the Message to add to the Thread.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "recipients",
            "description": "<p>The list of the recipients user IDs of the Message.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "sender_id",
            "description": "<p>The user ID of the Message sender.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-messages/classes/class-bp-rest-messages-endpoint.php",
    "groupTitle": "Messages"
  },
  {
    "type": "DELETE",
    "url": "/wp-json/buddyboss/v1/messages/:id",
    "title": "Delete Thread",
    "name": "DeleteBBThread",
    "group": "Messages",
    "description": "<p>Delete thread</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>ID of the Messages Thread.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "user_id",
            "description": "<p>The user ID to remove from the thread.</p>"
          },
          {
            "group": "Parameter",
            "type": "Date",
            "optional": true,
            "field": "before",
            "description": "<p>Messages to get before a specific date.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "recipients_pagination",
            "description": "<p>Load recipients in a paginated manner.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "recipients_page",
            "defaultValue": "1",
            "description": "<p>Current page of the recipients.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-messages/classes/class-bp-rest-messages-endpoint.php",
    "groupTitle": "Messages"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/messages/:id",
    "title": "Thread",
    "name": "GetBBThread",
    "group": "Messages",
    "description": "<p>Retrieve single thread</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>ID of the Messages Thread.</p>"
          },
          {
            "group": "Parameter",
            "type": "Date",
            "optional": true,
            "field": "before",
            "description": "<p>Messages to get before a specific date.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "recipients_pagination",
            "description": "<p>Load recipients in a paginated manner.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "recipients_page",
            "defaultValue": "1",
            "description": "<p>Current page of the recipients.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-messages/classes/class-bp-rest-messages-endpoint.php",
    "groupTitle": "Messages"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/messages",
    "title": "Threads",
    "name": "GetBBThreads",
    "group": "Messages",
    "description": "<p>Retrieve threads</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "page",
            "defaultValue": "1",
            "description": "<p>Current page of the collection.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "per_page",
            "defaultValue": "10",
            "description": "<p>Maximum number of items to be returned in result set.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": true,
            "field": "search",
            "description": "<p>Limit results to those matching a string.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "sentbox",
              "inbox",
              "starred"
            ],
            "optional": true,
            "field": "box",
            "defaultValue": "inbox",
            "description": "<p>Filter the result by box.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "all",
              "read",
              "unread"
            ],
            "optional": true,
            "field": "type",
            "defaultValue": "all",
            "description": "<p>Filter the result by thread status.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "user_id",
            "description": "<p>Limit result to messages created by a specific user.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": false,
            "field": "is_hidden",
            "description": "<p>List the archived threads.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-messages/classes/class-bp-rest-messages-endpoint.php",
    "groupTitle": "Messages"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/messages/action/:id",
    "title": "Thread Action",
    "name": "GetBBThreadsAction",
    "group": "Messages",
    "description": "<p>Perform Action on the Message Thread.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>ID of the Messages Thread.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "delete_messages",
              "hide_thread",
              "unread"
            ],
            "optional": false,
            "field": "action",
            "description": "<p>Action name to perform on the message thread.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": false,
            "field": "value",
            "description": "<p>Value for the action on message thread.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-messages/classes/class-bp-rest-messages-actions-endpoint.php",
    "groupTitle": "Messages"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/messages/search-recipients",
    "title": "Search Recipients",
    "name": "SearchBBRecipients",
    "group": "Messages",
    "description": "<p>Search Recipients</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "string",
            "optional": false,
            "field": "term",
            "description": "<p>Text for search recipients.</p>"
          },
          {
            "group": "Parameter",
            "type": "number",
            "optional": false,
            "field": "group_id",
            "description": "<p>Group id to search members.</p>"
          },
          {
            "group": "Parameter",
            "type": "number",
            "optional": true,
            "field": "exclude",
            "description": "<p>Ensure result set excludes specific member IDs.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "page",
            "description": "<p>Current page of the collection.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "per_page",
            "defaultValue": "10",
            "description": "<p>Maximum number of items to be returned in result set.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-messages/classes/class-bp-rest-messages-endpoint.php",
    "groupTitle": "Messages"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/messages/search-thread",
    "title": "Search Thread",
    "name": "SearchBBThread",
    "group": "Messages",
    "description": "<p>Search Existing thread by user and recipient for the message.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "number",
            "optional": false,
            "field": "user_id",
            "description": "<p>Sender users ID.</p>"
          },
          {
            "group": "Parameter",
            "type": "number",
            "optional": false,
            "field": "recipient_id",
            "description": "<p>Thread recipient ID.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": false,
            "field": "include_group_thread",
            "description": "<p>Include group thread or not.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-messages/classes/class-bp-rest-messages-endpoint.php",
    "groupTitle": "Messages"
  },
  {
    "type": "PATCH",
    "url": "/wp-json/buddyboss/v1/messages/:id",
    "title": "Update Thread",
    "name": "UpdateBBThread",
    "group": "Messages",
    "description": "<p>Update thread</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>ID of the Messages Thread.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "message_id",
            "description": "<p>By default the latest message of the thread will be updated. Specify this message ID to edit another message of the thread.</p>"
          },
          {
            "group": "Parameter",
            "type": "Date",
            "optional": true,
            "field": "before",
            "description": "<p>Messages to get before a specific date.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "recipients_pagination",
            "description": "<p>Load recipients in a paginated manner.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "recipients_page",
            "defaultValue": "1",
            "description": "<p>Current page of the recipients.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-messages/classes/class-bp-rest-messages-endpoint.php",
    "groupTitle": "Messages"
  },
  {
    "type": "PATCH",
    "url": "/wp-json/buddyboss/v1/messages/starred/:id",
    "title": "Update Starred Thread",
    "name": "UpdateBBThreadStarred",
    "group": "Messages",
    "description": "<p>Update starred thread</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>ID of one of the message of the Thread.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-messages/classes/class-bp-rest-messages-endpoint.php",
    "groupTitle": "Messages"
  },
  {
    "type": "POST",
    "url": "/wp-json/buddyboss/v1/moderation",
    "title": "Block a Member",
    "name": "CreateBBReportMember",
    "group": "Moderation",
    "description": "<p>Block a Member.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "item_id",
            "description": "<p>User ID which needs to be blocked.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-moderation/classes/class-bp-rest-moderation-endpoint.php",
    "groupTitle": "Moderation"
  },
  {
    "type": "DELETE",
    "url": "/wp-json/buddyboss/v1/moderation:id",
    "title": "Unblock Member",
    "name": "DeleteBBReportMember",
    "group": "Moderation",
    "description": "<p>Unblock Member.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>A unique numeric ID for the moderation.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-moderation/classes/class-bp-rest-moderation-endpoint.php",
    "groupTitle": "Moderation"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/moderation/report",
    "title": "Get Report Form",
    "name": "GetBBReportForm",
    "group": "Moderation",
    "description": "<p>Retrieve Report Form</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "filename": "src/bp-moderation/classes/class-bp-rest-moderation-report-endpoint.php",
    "groupTitle": "Moderation"
  },
  {
    "type": "POST",
    "url": "/wp-json/buddyboss/v1/moderation/report",
    "title": "Report a item",
    "name": "GetBBReportItem",
    "group": "Moderation",
    "description": "<p>Report a Item from components.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "item_id",
            "description": "<p>Unique identifier for the content to report.</p>"
          },
          {
            "group": "Parameter",
            "type": "string",
            "allowedValues": [
              "activity",
              "activity_comment",
              "groups",
              "forum",
              "forum_topic",
              "forum_reply",
              "document",
              "media"
            ],
            "optional": false,
            "field": "item_type",
            "description": "<p>Component type to report.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": false,
            "field": "report_category",
            "description": "<p>Reasoned category for report.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": true,
            "field": "note",
            "description": "<p>User Notes for the other type of report.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-moderation/classes/class-bp-rest-moderation-report-endpoint.php",
    "groupTitle": "Moderation"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/moderation/:id",
    "title": "Get Reported Member",
    "name": "GetBBReportedMember",
    "group": "Moderation",
    "description": "<p>Retrieve Reported Member</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>A unique numeric ID for the Moderation.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-moderation/classes/class-bp-rest-moderation-endpoint.php",
    "groupTitle": "Moderation"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/moderation",
    "title": "Get Reported Members",
    "name": "GetBBReportedMembers",
    "group": "Moderation",
    "description": "<p>Retrieve Reported Members</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "page",
            "description": "<p>Current page of the collection.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "per_page",
            "defaultValue": "10",
            "description": "<p>Maximum number of items to be returned in result set.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "item_id",
            "description": "<p>Get the result by reported item.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "asc",
              "desc"
            ],
            "optional": true,
            "field": "order",
            "defaultValue": "desc",
            "description": "<p>Order sort attribute ascending or descending.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "id",
              "item_type",
              "item_id",
              "last_updated",
              "hide_sitewide"
            ],
            "optional": true,
            "field": "order_by",
            "defaultValue": "last_updated",
            "description": "<p>Column name to order the results by.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "exclude",
            "description": "<p>Ensure result set excludes specific IDs.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "include",
            "description": "<p>Ensure result set includes specific IDs.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "reporters",
            "defaultValue": "false",
            "description": "<p>Whether to show the reporter or not.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "status",
            "description": "<p>Whether to show the blocked or suspended. 0-Blocked, 1-Suspended</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "blog_id",
            "description": "<p>Limit result set to items created by a specific site.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-moderation/classes/class-bp-rest-moderation-endpoint.php",
    "groupTitle": "Moderation"
  },
  {
    "type": "POST",
    "url": "/wp-json/buddyboss/v1/notifications",
    "title": "Create Notification",
    "name": "CreateBBNotifications",
    "group": "Notifications",
    "description": "<p>Create a notifications</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "user_id",
            "description": "<p>A unique numeric ID for the notification.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "item_id",
            "description": "<p>The ID of the item associated with the notification.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "secondary_item_id",
            "description": "<p>The ID of the secondary item associated with the notification.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": true,
            "field": "component",
            "description": "<p>The name of the component associated with the notification.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": true,
            "field": "action",
            "description": "<p>The name of the component action associated with the notification.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": true,
            "field": "date",
            "description": "<p>The date the notification was sent/created.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "is_new",
            "description": "<p>Whether the notification is new or not.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-notifications/classes/class-bp-rest-notifications-endpoint.php",
    "groupTitle": "Notifications"
  },
  {
    "type": "DELETE",
    "url": "/wp-json/buddyboss/v1/notifications/:id",
    "title": "Delete Notification",
    "name": "DeleteBBNotification",
    "group": "Notifications",
    "description": "<p>Delete notification</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>A unique numeric ID for the notification.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-notifications/classes/class-bp-rest-notifications-endpoint.php",
    "groupTitle": "Notifications"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/notifications/:id",
    "title": "Notification",
    "name": "GetBBNotification",
    "group": "Notifications",
    "description": "<p>Retrieve a notification</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>A unique numeric ID for the notification.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-notifications/classes/class-bp-rest-notifications-endpoint.php",
    "groupTitle": "Notifications"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/notifications",
    "title": "Notifications",
    "name": "GetBBNotifications",
    "group": "Notifications",
    "description": "<p>Retrieve notifications</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "page",
            "defaultValue": "1",
            "description": "<p>Current page of the collection.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "per_page",
            "defaultValue": "10",
            "description": "<p>Maximum number of items to be returned in result set.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "id",
              "date_notified",
              "item_id",
              "secondary_item_id",
              "component_name",
              "component_action",
              "include"
            ],
            "optional": true,
            "field": "order_by",
            "defaultValue": "id",
            "description": "<p>Name of the field to order according to.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "ASC",
              "DESC"
            ],
            "optional": true,
            "field": "sort_order",
            "defaultValue": "ASC",
            "description": "<p>Order sort attribute ascending or descending.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": true,
            "field": "component_name",
            "description": "<p>Limit result set to notifications associated with a specific component.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": true,
            "field": "component_action",
            "description": "<p>Limit result set to notifications associated with a specific component's action name.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "user_id",
            "description": "<p>Limit result set to notifications addressed to a specific user.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "item_id",
            "description": "<p>Limit result set to notifications associated with a specific item ID.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "secondary_item_id",
            "description": "<p>Limit result set to notifications associated with a specific secondary item ID.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "is_new",
            "defaultValue": "true",
            "description": "<p>Limit result set to items from specific states.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-notifications/classes/class-bp-rest-notifications-endpoint.php",
    "groupTitle": "Notifications"
  },
  {
    "type": "PATCH",
    "url": "/wp-json/buddyboss/v1/notifications/:id",
    "title": "Update Notification",
    "name": "UpdateBBNotification",
    "group": "Notifications",
    "description": "<p>Update notification</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>A unique numeric ID for the notification.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "is_new",
            "defaultValue": "0",
            "description": "<p>Whether it's a new notification or not.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-notifications/classes/class-bp-rest-notifications-endpoint.php",
    "groupTitle": "Notifications"
  },
  {
    "type": "PATCH",
    "url": "/wp-json/buddyboss/v1/notifications/bulk/read",
    "title": "Notification read in bulk",
    "name": "UpdateBBNotificationRead",
    "group": "Notifications",
    "description": "<p>Mark as read bulk notifications</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "filename": "src/bp-notifications/classes/class-bp-rest-notifications-endpoint.php",
    "groupTitle": "Notifications"
  },
  {
    "type": "POST",
    "url": "/wp-json/buddyboss/v1/xprofile/fields",
    "title": "Create xProfile Field",
    "name": "CreateBBxProfileField",
    "group": "Profile_Fields",
    "description": "<p>Create xProfile Field.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "group_id",
            "description": "<p>The ID of the group the field is part of.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "parent_id",
            "description": "<p>The ID of the parent field.</p>"
          },
          {
            "group": "Parameter",
            "type": "string",
            "optional": false,
            "field": "type",
            "description": "<p>The type for the profile field.</p>"
          },
          {
            "group": "Parameter",
            "type": "string",
            "optional": false,
            "field": "name",
            "description": "<p>The name of the profile field.</p>"
          },
          {
            "group": "Parameter",
            "type": "string",
            "optional": true,
            "field": "alternate_name",
            "description": "<p>The alternate name of the profile field.</p>"
          },
          {
            "group": "Parameter",
            "type": "string",
            "optional": true,
            "field": "description",
            "description": "<p>The description of the profile field.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "is_required",
            "description": "<p>Whether the profile field must have a value.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "allowedValues": [
              "true",
              "false"
            ],
            "optional": true,
            "field": "can_delete",
            "defaultValue": "true",
            "description": "<p>Whether the profile field can be deleted or not.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "field_order",
            "description": "<p>The order of the profile field into the group of fields.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "option_order",
            "description": "<p>The order of the option into the profile field list of options.</p>"
          },
          {
            "group": "Parameter",
            "type": "string",
            "allowedValues": [
              "asc",
              "desc"
            ],
            "optional": true,
            "field": "order_by",
            "defaultValue": "asc",
            "description": "<p>The way profile field's options are ordered.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "is_default_option",
            "description": "<p>Whether the option is the default one for the profile field.</p>"
          },
          {
            "group": "Parameter",
            "type": "string",
            "allowedValues": [
              "public",
              "adminsonly",
              "loggedin",
              "friends"
            ],
            "optional": true,
            "field": "default_visibility",
            "defaultValue": "public",
            "description": "<p>Default visibility for the profile field.</p>"
          },
          {
            "group": "Parameter",
            "type": "string",
            "allowedValues": [
              "allowed",
              "disabled"
            ],
            "optional": true,
            "field": "allow_custom_visibility",
            "defaultValue": "allowed",
            "description": "<p>Whether to allow members to set the visibility for the profile field data or not.</p>"
          },
          {
            "group": "Parameter",
            "type": "string",
            "allowedValues": [
              "on",
              "off"
            ],
            "optional": true,
            "field": "do_autolink",
            "defaultValue": "off",
            "description": "<p>Autolink status for this profile field.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-xprofile/classes/class-bp-rest-xprofile-fields-endpoint.php",
    "groupTitle": "Profile_Fields"
  },
  {
    "type": "POST",
    "url": "/wp-json/buddyboss/v1/xprofile/groups",
    "title": "Create xProfile Group",
    "name": "CreateBBxProfileGroup",
    "group": "Profile_Fields",
    "description": "<p>Create a Group</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "String",
            "optional": false,
            "field": "name",
            "description": "<p>The name of group of profile fields.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": true,
            "field": "description",
            "description": "<p>The description of the group of profile fields.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "can_delete",
            "defaultValue": "true",
            "description": "<p>Whether the group of profile fields can be deleted or not.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "repeater_enabled",
            "defaultValue": "false",
            "description": "<p>The description of the profile field.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-xprofile/classes/class-bp-rest-xprofile-field-groups-endpoint.php",
    "groupTitle": "Profile_Fields"
  },
  {
    "type": "POST",
    "url": "/wp-json/buddyboss/v1/xprofile/repeater/:id",
    "title": "Create xProfile Repeater",
    "name": "CreateBBxProfileRepeaterFields",
    "group": "Profile_Fields",
    "description": "<p>Create a new Repeater Fields Set in Group.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>A unique numeric ID for the group of profile fields.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "fetch_fields",
            "defaultValue": "true",
            "description": "<p>Whether to fetch the fields for each group.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "fetch_field_data",
            "defaultValue": "true",
            "description": "<p>Whether to fetch data for each field. Requires a $user_id.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "fetch_visibility_level",
            "defaultValue": "true",
            "description": "<p>Whether to fetch the visibility level for each field.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-xprofile/classes/class-bp-rest-xprofile-repeater-endpoint.php",
    "groupTitle": "Profile_Fields"
  },
  {
    "type": "DELETE",
    "url": "/wp-json/buddyboss/v1/xprofile/:field_id/data/:user_id",
    "title": "Delete xProfile Field Data",
    "name": "DeleteBBxProfileData",
    "group": "Profile_Fields",
    "description": "<p>Delete user's xProfile data.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "field_id",
            "description": "<p>The ID of the field the data is from.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "user_id",
            "description": "<p>The ID of user the field data is from.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-xprofile/classes/class-bp-rest-xprofile-data-endpoint.php",
    "groupTitle": "Profile_Fields"
  },
  {
    "type": "DELETE",
    "url": "/wp-json/buddyboss/v1/xprofile/fields/:field_id",
    "title": "Delete xProfile Field",
    "name": "DeleteBBxProfileField",
    "group": "Profile_Fields",
    "description": "<p>Delete xProfile Field.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>A unique numeric ID for the profile field.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "delete_data",
            "defaultValue": "false",
            "description": "<p>Required if you want to delete users data for the field.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-xprofile/classes/class-bp-rest-xprofile-fields-endpoint.php",
    "groupTitle": "Profile_Fields"
  },
  {
    "type": "DELETE",
    "url": "/wp-json/buddyboss/v1/xprofile/groups/:id",
    "title": "Delete xProfile Group",
    "name": "DeleteBBxProfileGroup",
    "group": "Profile_Fields",
    "description": "<p>Delete xProfile Group.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>A unique numeric ID for the group of profile fields.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-xprofile/classes/class-bp-rest-xprofile-field-groups-endpoint.php",
    "groupTitle": "Profile_Fields"
  },
  {
    "type": "DELETE",
    "url": "/wp-json/buddyboss/v1/xprofile/repeater/:id",
    "title": "Delete xProfile Repeater",
    "name": "DeleteBBxProfileRepeaterFields",
    "group": "Profile_Fields",
    "description": "<p>Delete a Repeater Fields Set in Group.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>A unique numeric ID for the group of profile fields.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "fetch_fields",
            "defaultValue": "true",
            "description": "<p>Whether to fetch the fields for each group.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "fetch_field_data",
            "defaultValue": "true",
            "description": "<p>Whether to fetch data for each field. Requires a $user_id.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "fetch_visibility_level",
            "defaultValue": "true",
            "description": "<p>Whether to fetch the visibility level for each field.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": false,
            "field": "fields",
            "description": "<p>Field IDs which you want to delete it.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-xprofile/classes/class-bp-rest-xprofile-repeater-endpoint.php",
    "groupTitle": "Profile_Fields"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/xprofile/types",
    "title": "Profile Types",
    "name": "GetBBProfileTypes",
    "group": "Profile_Fields",
    "description": "<p>Retrieve Profile Types.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser if the site is in Private Network."
      }
    ],
    "filename": "src/bp-xprofile/classes/class-bp-rest-xprofile-types-endpoint.php",
    "groupTitle": "Profile_Fields"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/xprofile/groups/:id",
    "title": "Get xProfile Group",
    "name": "GetBBxProfilGroup",
    "group": "Profile_Fields",
    "description": "<p>Retrieve Single xProfile Group</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser if the site is in Private Network."
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>A unique numeric ID for the group of profile fields.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "user_id",
            "defaultValue": "1",
            "description": "<p>Required if you want to load a specific user's data.</p>"
          },
          {
            "group": "Parameter",
            "type": "string",
            "optional": true,
            "field": "member_type",
            "description": "<p>Limit fields by those restricted to a given member type, or array of member types.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "hide_empty_fields",
            "defaultValue": "false",
            "description": "<p>Whether to hide profile groups of fields that do not have any profile fields or not.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "fetch_fields",
            "defaultValue": "false",
            "description": "<p>Whether to fetch the fields for each group.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "fetch_field_data",
            "defaultValue": "false",
            "description": "<p>Whether to fetch data for each field. Requires a $user_id.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "fetch_visibility_level",
            "defaultValue": "false",
            "description": "<p>Whether to fetch the visibility level for each field.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "exclude_fields",
            "description": "<p>Ensure result set excludes specific profile fields.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "update_meta_cache",
            "defaultValue": "true",
            "description": "<p>Whether to pre-fetch xprofilemeta for all retrieved groups, fields, and data.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-xprofile/classes/class-bp-rest-xprofile-field-groups-endpoint.php",
    "groupTitle": "Profile_Fields"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/xprofile/fields/:field_id",
    "title": "Get xProfile Field",
    "name": "GetBBxProfileField",
    "group": "Profile_Fields",
    "description": "<p>Retrieve xProfile single Field</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser if the site is in Private Network."
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>A unique numeric ID for the profile field.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "user_id",
            "defaultValue": "0",
            "description": "<p>Required if you want to load a specific user's data.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "fetch_field_data",
            "description": "<p>Whether to fetch data for the field. Requires a $user_id.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-xprofile/classes/class-bp-rest-xprofile-fields-endpoint.php",
    "groupTitle": "Profile_Fields"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/xprofile/:field_id/data/:user_id",
    "title": "Get xProfile Field Data",
    "name": "GetBBxProfileFieldData",
    "group": "Profile_Fields",
    "description": "<p>Retrieve xProfile Field data for the user.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser if the site is in Private Network."
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "field_id",
            "description": "<p>The ID of the field the data is from.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "user_id",
            "description": "<p>The ID of user the field data is from.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-xprofile/classes/class-bp-rest-xprofile-data-endpoint.php",
    "groupTitle": "Profile_Fields"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/xprofile/fields",
    "title": "Get xProfile Fields",
    "name": "GetBBxProfileFields",
    "group": "Profile_Fields",
    "description": "<p>Retrieve Multiple xProfile Fields</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser if the site is in Private Network."
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "profile_group_id",
            "description": "<p>ID of the profile group of fields that have profile fields</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "hide_empty_groups",
            "defaultValue": "false",
            "description": "<p>Whether to hide profile groups of fields that do not have any profile fields or not.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "user_id",
            "defaultValue": "1",
            "description": "<p>Required if you want to load a specific user's data.</p>"
          },
          {
            "group": "Parameter",
            "type": "string",
            "optional": true,
            "field": "member_type",
            "description": "<p>Limit fields by those restricted to a given member type, or array of member types.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "hide_empty_fields",
            "defaultValue": "false",
            "description": "<p>Whether to hide profile fields where the user has not provided data or not.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "fetch_field_data",
            "defaultValue": "false",
            "description": "<p>Whether to fetch data for each field. Requires a $user_id.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "fetch_visibility_level",
            "defaultValue": "false",
            "description": "<p>Whether to fetch the visibility level for each field.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "exclude_groups",
            "description": "<p>Ensure result set excludes specific profile field groups.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "exclude_fields",
            "description": "<p>Ensure result set excludes specific profile fields.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "update_meta_cache",
            "defaultValue": "true",
            "description": "<p>Whether to pre-fetch xprofilemeta for all retrieved groups, fields, and data.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-xprofile/classes/class-bp-rest-xprofile-fields-endpoint.php",
    "groupTitle": "Profile_Fields"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/xprofile/groups",
    "title": "Get xProfile Groups",
    "name": "GetBBxProfileGroups",
    "group": "Profile_Fields",
    "description": "<p>Retrieve xProfile Groups</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser if the site is in Private Network."
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "profile_group_id",
            "description": "<p>ID of the field group that have fields.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "hide_empty_groups",
            "defaultValue": "false",
            "description": "<p>Whether to hide profile groups of fields that do not have any profile fields or not.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "user_id",
            "defaultValue": "1",
            "description": "<p>Required if you want to load a specific user's data.</p>"
          },
          {
            "group": "Parameter",
            "type": "string",
            "optional": true,
            "field": "member_type",
            "description": "<p>Limit fields by those restricted to a given member type, or array of member types.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "hide_empty_fields",
            "defaultValue": "false",
            "description": "<p>Whether to hide profile groups of fields that do not have any profile fields or not.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "fetch_fields",
            "defaultValue": "false",
            "description": "<p>Whether to fetch the fields for each group.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "fetch_field_data",
            "defaultValue": "false",
            "description": "<p>Whether to fetch data for each field. Requires a $user_id.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "fetch_visibility_level",
            "defaultValue": "false",
            "description": "<p>Whether to fetch the visibility level for each field.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "exclude_groups",
            "description": "<p>Ensure result set excludes specific profile field groups.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "exclude_fields",
            "description": "<p>Ensure result set excludes specific profile fields.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "update_meta_cache",
            "defaultValue": "true",
            "description": "<p>Whether to pre-fetch xprofilemeta for all retrieved groups, fields, and data.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-xprofile/classes/class-bp-rest-xprofile-field-groups-endpoint.php",
    "groupTitle": "Profile_Fields"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/xprofile/search",
    "title": "Get Search Form",
    "name": "GetBBxProfileSearchForm",
    "group": "Profile_Fields",
    "description": "<p>Retrieve Advanced Search Form fields for Members Directory.</p>",
    "version": "1.0.0",
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "form_id",
            "description": "<p>ID of the profile search form.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-xprofile/classes/class-bp-rest-xprofile-search-form-fields-endpoint.php",
    "groupTitle": "Profile_Fields"
  },
  {
    "type": "POST",
    "url": "/wp-json/buddyboss/v1/xprofile/update",
    "title": "Update xProfile",
    "name": "UpdateBBxProfile",
    "group": "Profile_Fields",
    "description": "<p>Update xProfile for user.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Array",
            "optional": false,
            "field": "fields",
            "description": "<p>Fields array with field_id, group_id, type, value and visibility_level to update the data.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "profile_group_id",
            "description": "<p>ID of the field group that have fields.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "hide_empty_groups",
            "defaultValue": "false",
            "description": "<p>Whether to hide profile groups of fields that do not have any profile fields or not.</p>"
          },
          {
            "group": "Parameter",
            "type": "string",
            "optional": true,
            "field": "member_type",
            "description": "<p>Limit fields by those restricted to a given member type, or array of member types.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "hide_empty_fields",
            "defaultValue": "false",
            "description": "<p>Whether to hide profile fields where the user has not provided data or not.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "fetch_fields",
            "defaultValue": "true",
            "description": "<p>Whether to fetch the fields for each group.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "fetch_field_data",
            "defaultValue": "true",
            "description": "<p>Whether to fetch data for each field. Requires a $user_id.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "fetch_visibility_level",
            "defaultValue": "true",
            "description": "<p>Whether to fetch the visibility level for each field.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "exclude_groups",
            "description": "<p>Ensure result set excludes specific profile field groups.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "exclude_fields",
            "description": "<p>Ensure result set excludes specific profile fields.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "update_meta_cache",
            "defaultValue": "true",
            "description": "<p>Whether to pre-fetch xprofilemeta for all retrieved groups, fields, and data.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-xprofile/classes/class-bp-rest-xprofile-update-endpoint.php",
    "groupTitle": "Profile_Fields"
  },
  {
    "type": "PATCH",
    "url": "/wp-json/buddyboss/v1/xprofile/:field_id/data/:user_id",
    "title": "Update xProfile Field Data",
    "name": "UpdateBBxProfileData",
    "group": "Profile_Fields",
    "description": "<p>Update xProfile field data for the user.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "field_id",
            "description": "<p>The ID of the field the data is from.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "user_id",
            "description": "<p>The ID of user the field data is from.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "value",
            "description": "<p>The list of values for the field data.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-xprofile/classes/class-bp-rest-xprofile-data-endpoint.php",
    "groupTitle": "Profile_Fields"
  },
  {
    "type": "PATCH",
    "url": "/wp-json/buddyboss/v1/xprofile/fields/:field_id",
    "title": "Update xProfile Field",
    "name": "UpdateBBxProfileField",
    "group": "Profile_Fields",
    "description": "<p>Update xProfile Field.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>A unique numeric ID for the profile field.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "group_id",
            "description": "<p>The ID of the group the field is part of.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "parent_id",
            "description": "<p>The ID of the parent field.</p>"
          },
          {
            "group": "Parameter",
            "type": "string",
            "optional": true,
            "field": "type",
            "description": "<p>The type for the profile field.</p>"
          },
          {
            "group": "Parameter",
            "type": "string",
            "optional": true,
            "field": "name",
            "description": "<p>The name of the profile field.</p>"
          },
          {
            "group": "Parameter",
            "type": "string",
            "optional": true,
            "field": "alternate_name",
            "description": "<p>The alternate name of the profile field.</p>"
          },
          {
            "group": "Parameter",
            "type": "string",
            "optional": true,
            "field": "description",
            "description": "<p>The description of the profile field.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "is_required",
            "description": "<p>Whether the profile field must have a value.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "allowedValues": [
              "true",
              "false"
            ],
            "optional": true,
            "field": "can_delete",
            "defaultValue": "true",
            "description": "<p>Whether the profile field can be deleted or not.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "field_order",
            "description": "<p>The order of the profile field into the group of fields.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "option_order",
            "description": "<p>The order of the option into the profile field list of options.</p>"
          },
          {
            "group": "Parameter",
            "type": "string",
            "allowedValues": [
              "asc",
              "desc"
            ],
            "optional": true,
            "field": "order_by",
            "defaultValue": "asc",
            "description": "<p>The way profile field's options are ordered.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "is_default_option",
            "description": "<p>Whether the option is the default one for the profile field.</p>"
          },
          {
            "group": "Parameter",
            "type": "string",
            "allowedValues": [
              "public",
              "adminsonly",
              "loggedin",
              "friends"
            ],
            "optional": true,
            "field": "default_visibility",
            "defaultValue": "public",
            "description": "<p>Default visibility for the profile field.</p>"
          },
          {
            "group": "Parameter",
            "type": "string",
            "allowedValues": [
              "allowed",
              "disabled"
            ],
            "optional": true,
            "field": "allow_custom_visibility",
            "defaultValue": "allowed",
            "description": "<p>Whether to allow members to set the visibility for the profile field data or not.</p>"
          },
          {
            "group": "Parameter",
            "type": "string",
            "allowedValues": [
              "on",
              "off"
            ],
            "optional": true,
            "field": "do_autolink",
            "defaultValue": "off",
            "description": "<p>Autolink status for this profile field.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-xprofile/classes/class-bp-rest-xprofile-fields-endpoint.php",
    "groupTitle": "Profile_Fields"
  },
  {
    "type": "PATCH",
    "url": "/wp-json/buddyboss/v1/xprofile/groups/:id",
    "title": "Update xProfile Group",
    "name": "UpdateBBxProfileGroup",
    "group": "Profile_Fields",
    "description": "<p>Update a Group</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>A unique numeric ID for the group of profile fields.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": true,
            "field": "name",
            "description": "<p>The name of group of profile fields.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": true,
            "field": "description",
            "description": "<p>The description of the group of profile fields.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "group_order",
            "description": "<p>The order of the group of profile fields.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "can_delete",
            "description": "<p>Whether the group of profile fields can be deleted or not.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "repeater_enabled",
            "description": "<p>The description of the profile field.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-xprofile/classes/class-bp-rest-xprofile-field-groups-endpoint.php",
    "groupTitle": "Profile_Fields"
  },
  {
    "type": "PATCH",
    "url": "/wp-json/buddyboss/v1/xprofile/repeater/order/:id",
    "title": "Reorder xProfile Repeater",
    "name": "UpdateBBxProfileRepeaterFields",
    "group": "Profile_Fields",
    "description": "<p>Reorder the order of the repeater.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>A unique numeric ID for the group of profile fields.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": false,
            "field": "fields",
            "description": "<p>Fields array with order of field set with field ID and value to reorder.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "fetch_fields",
            "defaultValue": "true",
            "description": "<p>Whether to fetch the fields for each group.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "fetch_field_data",
            "defaultValue": "true",
            "description": "<p>Whether to fetch data for each field. Requires a $user_id.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "fetch_visibility_level",
            "defaultValue": "true",
            "description": "<p>Whether to fetch the visibility level for each field.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-xprofile/classes/class-bp-rest-xprofile-repeater-endpoint.php",
    "groupTitle": "Profile_Fields"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/reactions",
    "title": "Get Reactions",
    "name": "GetBBReactions",
    "group": "Reactions",
    "description": "<p>Retrieve supported reactions</p>",
    "version": "1.0.0",
    "filename": "src/bp-core/classes/class-bb-rest-reactions-endpoint.php",
    "groupTitle": "Reactions"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/settings",
    "title": "Settings",
    "name": "GetBBSettings",
    "group": "Settings",
    "description": "<p>Retrieve settings</p>",
    "version": "1.0.0",
    "filename": "src/bp-core/classes/class-bp-rest-settings-endpoint.php",
    "groupTitle": "Settings"
  },
  {
    "type": "PATCH",
    "url": "/wp-json/buddyboss/v1/signup/activate/:activation_key",
    "title": "Activate a signup",
    "name": "ActivateBBSignups",
    "group": "Signups",
    "description": "<p>Activate a signup.</p>",
    "version": "1.0.0",
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "String",
            "optional": false,
            "field": "activation_key",
            "description": "<p>Identifier for the signup.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-members/classes/class-bp-rest-signup-endpoint.php",
    "groupTitle": "Signups"
  },
  {
    "type": "POST",
    "url": "/wp-json/buddyboss/v1/signup",
    "title": "Create signup",
    "name": "CreateBBSignups",
    "group": "Signups",
    "description": "<p>Create signup</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "WithoutLoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "String",
            "optional": false,
            "field": "signup_email",
            "description": "<p>New user email address.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": true,
            "field": "signup_email_confirm",
            "description": "<p>New user confirm email address.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": false,
            "field": "signup_password",
            "description": "<p>New user account password.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": true,
            "field": "signup_password_confirm",
            "description": "<p>New user confirm account password.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-members/classes/class-bp-rest-signup-endpoint.php",
    "groupTitle": "Signups"
  },
  {
    "type": "DELETE",
    "url": "/wp-json/buddyboss/v1/signup/:id",
    "title": "Delete signup",
    "name": "DeleteBBSignups",
    "group": "Signups",
    "description": "<p>Delete signup</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "String",
            "optional": false,
            "field": "id",
            "description": "<p>Identifier for the signup. Can be a signup ID, an email address, or a user_login.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-members/classes/class-bp-rest-signup-endpoint.php",
    "groupTitle": "Signups"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/signup/form",
    "title": "Signup Form",
    "name": "GetBBSignupFormFields",
    "group": "Signups",
    "description": "<p>Retrieve Signup Form Fields.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "WithoutLoggedInUser"
      }
    ],
    "filename": "src/bp-members/classes/class-bp-rest-signup-endpoint.php",
    "groupTitle": "Signups"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/signup",
    "title": "Signups",
    "name": "GetBBSignups",
    "group": "Signups",
    "description": "<p>Retrieve signups</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "include",
            "description": "<p>Ensure result set includes specific IDs.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "asc",
              "desc"
            ],
            "optional": true,
            "field": "order",
            "description": "<p>Order sort attribute ascending or descending.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": true,
            "field": "orderby",
            "defaultValue": "signup_id",
            "description": "<p>Order by a specific parameter.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": true,
            "field": "user_login",
            "description": "<p>Specific user login to return.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "number",
            "description": "<p>Total number of signups to return.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "offset",
            "description": "<p>'Offset the result set by a specific number of items.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-members/classes/class-bp-rest-signup-endpoint.php",
    "groupTitle": "Signups"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/signup/:id",
    "title": "Signup",
    "name": "GetBBSignups",
    "group": "Signups",
    "description": "<p>Retrieve signup</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "String",
            "optional": false,
            "field": "id",
            "description": "<p>Identifier for the signup. Can be a signup ID, an email address, or a user_login.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-members/classes/class-bp-rest-signup-endpoint.php",
    "groupTitle": "Signups"
  },
  {
    "type": "POST",
    "url": "/wp-json/buddyboss/v1/subscription",
    "title": "Create Subscription",
    "name": "CreateBBSubscription",
    "group": "Subscription",
    "description": "<p>Create subscription</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "forum",
              "topic"
            ],
            "optional": false,
            "field": "type",
            "description": "<p>The type subscription.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "item_id",
            "description": "<p>The ID of forum/topic.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "secondary_item_id",
            "description": "<p>ID of the parent forum/topic.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "user_id",
            "description": "<p>The ID of the user who created the Subscription. default logged-in user id.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "blog_id",
            "description": "<p>The ID of site. default current site id.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-core/classes/class-bb-rest-subscriptions-endpoint.php",
    "groupTitle": "Subscription"
  },
  {
    "type": "DELETE",
    "url": "/wp-json/buddyboss/v1/subscription/:id",
    "title": "Delete Subscription",
    "name": "DeleteBBSubscription",
    "group": "Subscriptions",
    "description": "<p>Delete a subscription.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>A unique numeric ID for the Subscription.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-core/classes/class-bb-rest-subscriptions-endpoint.php",
    "groupTitle": "Subscriptions"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/subscription/:id",
    "title": "Get Subscription",
    "name": "GetBBSubscription",
    "group": "Subscriptions",
    "description": "<p>Retrieve single subscription</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser if the site is in Private Network."
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>A unique numeric ID for the Subscription.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-core/classes/class-bb-rest-subscriptions-endpoint.php",
    "groupTitle": "Subscriptions"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/subscription-types",
    "title": "Get Subscription types",
    "name": "GetBBSubscriptionTypes",
    "group": "Subscriptions",
    "description": "<p>Retrieve subscription Types</p>",
    "version": "1.0.0",
    "filename": "src/bp-core/classes/class-bb-rest-subscriptions-endpoint.php",
    "groupTitle": "Subscriptions"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/subscription",
    "title": "Get Subscriptions",
    "name": "GetBBSubscriptions",
    "group": "Subscriptions",
    "description": "<p>Retrieve subscriptions</p>",
    "version": "1.0.0",
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "page",
            "defaultValue": "1",
            "description": "<p>Current page of the collection.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "per_page",
            "defaultValue": "10",
            "description": "<p>Maximum number of items to be returned in result set.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": true,
            "field": "search",
            "description": "<p>Limit results to those matching a string.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "forum",
              "topic"
            ],
            "optional": true,
            "field": "type",
            "description": "<p>Limit results based on subscription type.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "asc",
              "desc"
            ],
            "optional": true,
            "field": "order",
            "defaultValue": "desc",
            "description": "<p>Order sort attribute ascending or descending.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "id",
              "type",
              "item_id",
              "date_recorded"
            ],
            "optional": true,
            "field": "orderby",
            "defaultValue": "date_recorded",
            "description": "<p>Order Subscriptions by which attribute.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "blog_id",
            "description": "<p>Get subscription site wise. Default current site ID.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "item_id",
            "description": "<p>Get Subscriptions that are user subscribed items.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "secondary_item_id",
            "description": "<p>Get Subscriptions that are children of the subscribed items.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "status",
            "defaultValue": "1",
            "description": "<p>Active Subscriptions. 1 = Active, 0 = Inactive.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "include",
            "description": "<p>Ensure result set includes Subscriptions with specific IDs.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "exclude",
            "description": "<p>Ensure result set excludes Subscriptions with specific IDs.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-core/classes/class-bb-rest-subscriptions-endpoint.php",
    "groupTitle": "Subscriptions"
  },
  {
    "type": "DELETE",
    "url": "/wp-json/buddyboss/v1/user-reactions/:id",
    "title": "Delete User Reaction",
    "name": "DeleteUserReaction",
    "group": "User_Reaction",
    "description": "<p>Delete a single user reaction.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>A unique numeric ID for the user reaction.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-core/classes/class-bb-rest-reactions-endpoint.php",
    "groupTitle": "User_Reaction"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/user-reactions",
    "title": "Get Reactions",
    "name": "GetBBUserReactions",
    "group": "User_Reactions",
    "description": "<p>Retrieve user reactions</p>",
    "version": "1.0.0",
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "page",
            "description": "<p>Current page of the collection.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "per_page",
            "defaultValue": "10",
            "description": "<p>Maximum number of items to be returned in result set.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "reaction_id",
            "description": "<p>Limit result set to items with a specific Reaction ID.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": true,
            "field": "item_type",
            "description": "<p>Limit result set to items with a specific item type.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "item_id",
            "description": "<p>Limit result set to items with a specific item ID.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "user_id",
            "description": "<p>Limit result set to items with a specific user ID.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "allowedValues": [
              "asc",
              "desc"
            ],
            "optional": true,
            "field": "order",
            "defaultValue": "desc",
            "description": "<p>Order sort attribute ascending or descending.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "allowedValues": [
              "id",
              "date_created"
            ],
            "optional": true,
            "field": "order_by",
            "defaultValue": "id",
            "description": "<p>Order by a specific parameter.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-core/classes/class-bb-rest-reactions-endpoint.php",
    "groupTitle": "User_Reactions"
  },
  {
    "type": "POST",
    "url": "/wp-json/buddyboss/v1/user-reactions",
    "title": "Create user reactions",
    "name": "CreateUserReaction",
    "group": "User_reaction",
    "description": "<p>Create user reactions</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "reaction_id",
            "description": "<p>The ID of reaction.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": true,
            "field": "item_type",
            "description": "<p>Type of item.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "item_id",
            "description": "<p>The ID of item.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "user_id",
            "description": "<p>The ID for the author of the reaction.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-core/classes/class-bb-rest-reactions-endpoint.php",
    "groupTitle": "User_reaction"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/user-reactions/:id",
    "title": "Get user reaction",
    "name": "GetBBUserReaction",
    "group": "User_reaction",
    "description": "<p>Retrieve single user reaction</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser if the site is in Private Network."
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>A unique numeric ID for the user reaction.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-core/classes/class-bb-rest-reactions-endpoint.php",
    "groupTitle": "User_reaction"
  },
  {
    "type": "POST",
    "url": "/wp-json/buddyboss/v1/video",
    "title": "Create Videos",
    "name": "CreateBBVideos",
    "group": "Video",
    "description": "<p>Create Video.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Array",
            "optional": false,
            "field": "upload_ids",
            "description": "<p>Video specific IDs.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "activity_id",
            "description": "<p>A unique numeric ID for the activity.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "group_id",
            "description": "<p>A unique numeric ID for the Group.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "album_id",
            "description": "<p>A unique numeric ID for the Video Album.</p>"
          },
          {
            "group": "Parameter",
            "type": "string",
            "optional": true,
            "field": "content",
            "description": "<p>Video Content.</p>"
          },
          {
            "group": "Parameter",
            "type": "string",
            "allowedValues": [
              "public",
              "loggedin",
              "onlyme",
              "friends",
              "grouponly"
            ],
            "optional": true,
            "field": "privacy",
            "defaultValue": "public",
            "description": "<p>Privacy of the video.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-video/classes/class-bp-rest-video-endpoint.php",
    "groupTitle": "Video"
  },
  {
    "type": "DELETE",
    "url": "/wp-json/buddyboss/v1/video/:id",
    "title": "Delete Video",
    "name": "DeleteBBVideo",
    "group": "Video",
    "description": "<p>Delete a single Video.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>A unique numeric ID for the video video.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-video/classes/class-bp-rest-video-endpoint.php",
    "groupTitle": "Video"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/video/:id/poster",
    "title": "Delete Poster",
    "name": "DeleteBBVideoPoster",
    "group": "Video",
    "description": "<p>Delete Video Poster</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>A unique numeric ID for the video video.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "attachment_id",
            "description": "<p>A Unique numeric ID for the video poster.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-video/classes/class-bp-rest-video-poster-endpoint.php",
    "groupTitle": "Video"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/video/:id",
    "title": "Get Video",
    "name": "GetBBVideo",
    "group": "Video",
    "description": "<p>Retrieve a single video.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser if the site is in Private Network."
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>A unique numeric ID for the video video.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-video/classes/class-bp-rest-video-endpoint.php",
    "groupTitle": "Video"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/video/details",
    "title": "Video Details",
    "name": "GetBBVideoDetails",
    "group": "Video",
    "description": "<p>Retrieve Video details(includes tabs and privacy options)</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser if the site is in Private Network."
      }
    ],
    "filename": "src/bp-video/classes/class-bp-rest-video-details-endpoint.php",
    "groupTitle": "Video"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/video/:id/poster",
    "title": "Get Posters",
    "name": "GetBBVideoPosters",
    "group": "Video",
    "description": "<p>Retrieve Video posters.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>A unique numeric ID for the video video.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-video/classes/class-bp-rest-video-poster-endpoint.php",
    "groupTitle": "Video"
  },
  {
    "type": "GET",
    "url": "/wp-json/buddyboss/v1/video",
    "title": "Get Videos",
    "name": "GetBBVideos",
    "group": "Video",
    "description": "<p>Retrieve videos.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser if the site is in Private Network."
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "page",
            "description": "<p>Current page of the collection.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "per_page",
            "defaultValue": "10",
            "description": "<p>Maximum number of items to be returned in result set.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "optional": true,
            "field": "search",
            "description": "<p>Limit results to those matching a string.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "asc",
              "desc"
            ],
            "optional": true,
            "field": "order",
            "defaultValue": "desc",
            "description": "<p>Order sort attribute ascending or descending.</p>"
          },
          {
            "group": "Parameter",
            "type": "String",
            "allowedValues": [
              "date_created",
              "menu_order",
              "id",
              "include"
            ],
            "optional": true,
            "field": "orderby",
            "defaultValue": "date_created",
            "description": "<p>Order by a specific parameter.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "user_id",
            "description": "<p>Limit result set to items created by a specific user (ID).</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "max",
            "description": "<p>Maximum number of results to return.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "album_id",
            "description": "<p>A unique numeric ID for the Album.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "group_id",
            "description": "<p>A unique numeric ID for the Group.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "activity_id",
            "description": "<p>A unique numeric ID for the Video's Activity.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "allowedValues": [
              "public",
              "loggedin",
              "onlyme",
              "friends",
              "grouponly"
            ],
            "optional": true,
            "field": "privacy",
            "defaultValue": "public",
            "description": "<p>Privacy of the video.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "allowedValues": [
              "friends",
              "groups",
              "personal"
            ],
            "optional": true,
            "field": "scope",
            "description": "<p>Scope of the video.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "exclude",
            "description": "<p>Ensure result set excludes specific IDs.</p>"
          },
          {
            "group": "Parameter",
            "type": "Array",
            "optional": true,
            "field": "include",
            "description": "<p>Ensure result set includes specific IDs.</p>"
          },
          {
            "group": "Parameter",
            "type": "Boolean",
            "optional": true,
            "field": "count_total",
            "defaultValue": "true",
            "description": "<p>Show total count or not.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-video/classes/class-bp-rest-video-endpoint.php",
    "groupTitle": "Video"
  },
  {
    "type": "PATCH",
    "url": "/wp-json/buddyboss/v1/video/:id",
    "title": "Update Video",
    "name": "UpdateBBVideo",
    "group": "Video",
    "description": "<p>Update a single Video.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>A unique numeric ID for the video video.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "album_id",
            "description": "<p>A unique numeric ID for the Album.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": true,
            "field": "group_id",
            "description": "<p>A unique numeric ID for the Group.</p>"
          },
          {
            "group": "Parameter",
            "type": "string",
            "optional": true,
            "field": "content",
            "description": "<p>Video Content.</p>"
          },
          {
            "group": "Parameter",
            "type": "string",
            "allowedValues": [
              "public",
              "loggedin",
              "onlyme",
              "friends",
              "grouponly"
            ],
            "optional": true,
            "field": "privacy",
            "description": "<p>Privacy of the video.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-video/classes/class-bp-rest-video-endpoint.php",
    "groupTitle": "Video"
  },
  {
    "type": "POST/PUT",
    "url": "/wp-json/buddyboss/v1/video/:id/poster",
    "title": "Add Video Poster",
    "name": "UpdateBBVideoPoster",
    "group": "Video",
    "description": "<p>Add Video Poster</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>A unique numeric ID for the video video.</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "attachment_id",
            "description": "<p>A Unique numeric ID for the video poster.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-video/classes/class-bp-rest-video-poster-endpoint.php",
    "groupTitle": "Video"
  },
  {
    "type": "POST",
    "url": "/wp-json/buddyboss/v1/video/upload",
    "title": "Upload Video",
    "name": "UploadBBVideo",
    "group": "Video",
    "description": "<p>Upload Video.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "String",
            "optional": false,
            "field": "file",
            "description": "<p>File object which is going to upload.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-video/classes/class-bp-rest-video-endpoint.php",
    "groupTitle": "Video"
  },
  {
    "type": "POST",
    "url": "/wp-json/buddyboss/v1/video/:id/upload_poster",
    "title": "Upload Video Poster",
    "name": "UploadBBVideoPoster",
    "group": "Video",
    "description": "<p>Upload Video Poster.</p>",
    "version": "1.0.0",
    "permission": [
      {
        "name": "LoggedInUser"
      }
    ],
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "String",
            "optional": false,
            "field": "file",
            "description": "<p>File object which is going to upload.</p>"
          }
        ]
      }
    },
    "filename": "src/bp-video/classes/class-bp-rest-video-poster-endpoint.php",
    "groupTitle": "Video"
  }
] });

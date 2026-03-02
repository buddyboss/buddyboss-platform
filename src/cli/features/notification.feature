Feature: Manage BuddyPress Notifications

  Scenario: Notifications CRUD Operations
    Given a BP install

    When I run `wp user create testuser2 testuser2@example.com --first_name=test --last_name=user --role=subscriber --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {MEMBER_ID}

    When I run `wp bp notification create --component=activity --action=comment_reply --user-id={MEMBER_ID} --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {NOTIFICATION_ID}

    When I run `wp bp notification get {NOTIFICATION_ID} --fields=user_id,component_name,component_action`
    Then STDOUT should be a table containing rows:
      | Field            | Value                 |
      | user_id          | {MEMBER_ID}           |
      | component_name   | activity              |
      | component_action | comment_reply         |

    When I run `wp bp notification list --fields=id,user_id`
    Then STDOUT should be a table containing rows:
      | id                | user_id     |
      | {NOTIFICATION_ID} | {MEMBER_ID} |

    When I run `wp bp notification get {NOTIFICATION_ID}`
    Then STDOUT should be a table containing rows:
      | Field            | Value                 |
      | user_id          | {MEMBER_ID}           |
      | component_name   | activity              |
      | component_action | comment_reply         |

    When I run `wp bp notification delete {NOTIFICATION_ID} --yes`
    Then STDOUT should contain:
      """
      Success: Deleted notification {NOTIFICATION_ID}.
      """

  Scenario: Notification list
    Given a BP install

    When I run `wp user create testuser1 testuser1@example.com --first_name=test --last_name=user --role=subscriber --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {MEMBER_ONE_ID}

    When I run `wp user create testuser2 testuser2@example.com --first_name=test --last_name=user --role=subscriber --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {MEMBER_TWO_ID}

    When I run `wp bp notification create --component=groups --user-id={MEMBER_ONE_ID} --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {NOTIFICATION_ONE_ID}

    When I run `wp bp notification create --component=activity --user-id={MEMBER_TWO_ID} --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {NOTIFICATION_TWO_ID}

    When I run `wp bp notification list --fields=id,user_id`
    Then STDOUT should be a table containing rows:
      | id                    | user_id         |
      | {NOTIFICATION_ONE_ID} | {MEMBER_ONE_ID} |
      | {NOTIFICATION_TWO_ID} | {MEMBER_TWO_ID} |

    When I run `wp bp notification delete {NOTIFICATION_ONE_ID} {NOTIFICATION_TWO_ID} --yes`
    Then STDOUT should contain:
      """
      Success: Deleted notification {NOTIFICATION_ONE_ID}.
      Success: Deleted notification {NOTIFICATION_TWO_ID}.
      """

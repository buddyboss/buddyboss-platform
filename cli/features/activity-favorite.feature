Feature: Manage BuddyPress Activity Favorites

  Scenario: Activity Favorite CRUD Operations
    Given a BP install

    When I run `wp user create testuser2 testuser2@example.com --porcelain`
    And save STDOUT as {MEMBER_ID}

    When I run `wp bp activity create --component=groups --user-id={MEMBER_ID} --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {ACTIVITY_ID}

    When I run `wp bp activity list --fields=id,user_id,component`
    Then STDOUT should be a table containing rows:
      | id            | user_id      | component |
      | {ACTIVITY_ID} | {MEMBER_ID}  | groups    |

    When I run `wp user create testuser3 testuser3@example.com --porcelain`
    And save STDOUT as {SEC_MEMBER_ID}

    When I run `wp bp activity favorite create {ACTIVITY_ID} {SEC_MEMBER_ID}`
    Then STDOUT should contain:
      """
      Success: Activity item added as a favorite for the user.
      """

    When I run `wp bp activity favorite list {SEC_MEMBER_ID} --fields=id`
    Then STDOUT should be a table containing rows:
      | id            |
      | {ACTIVITY_ID} |

    When I run `wp bp activity favorite remove {ACTIVITY_ID} {SEC_MEMBER_ID} --yes`
    Then STDOUT should contain:
      """
      Success: Activity item removed as a favorite for the user.
      """

    When I try `wp bp activity favorite list {SEC_MEMBER_ID} --fields=id`
    Then the return code should be 1

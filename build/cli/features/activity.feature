Feature: Manage BuddyPress Activities

  Scenario: Activity CRUD Operations
    Given a BP install

    When I run `wp user create testuser2 testuser2@example.com --first_name=test --last_name=user --role=subscriber --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {MEMBER_ID}

    When I run `wp bp activity create --component=groups --user-id={MEMBER_ID} --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {ACTIVITY_ID}

    When I run `wp bp activity get {ACTIVITY_ID} --fields=id,user_id,component`
    Then STDOUT should be a table containing rows:
      | Field     | Value         |
      | id        | {ACTIVITY_ID} |
      | user_id   | {MEMBER_ID}   |
      | component | groups        |

    When I run `wp bp activity list --fields=id,user_id,component`
    Then STDOUT should be a table containing rows:
      | id            | user_id      | component |
      | {ACTIVITY_ID} | {MEMBER_ID}  | groups    |

    When I run `wp bp activity spam {ACTIVITY_ID}`
    Then STDOUT should contain:
      """
      Success: Activity marked as spam.
      """

    When I run `wp bp activity get {ACTIVITY_ID} --fields=id,is_spam`
    Then STDOUT should be a table containing rows:
      | Field     | Value         |
      | id        | {ACTIVITY_ID} |
      | is_spam   | 1             |

    When I run `wp bp activity ham {ACTIVITY_ID}`
    Then STDOUT should contain:
      """
      Success: Activity marked as ham.
      """

    When I run `wp bp activity get {ACTIVITY_ID} --fields=id,is_spam`
    Then STDOUT should be a table containing rows:
      | Field     | Value         |
      | id        | {ACTIVITY_ID} |
      | is_spam   | 0             |

    When I run `wp bp activity delete {ACTIVITY_ID} --yes`
    Then STDOUT should contain:
      """
      Success: Activity deleted.
      """

    When I try `wp bp activity get {ACTIVITY_ID}`
    Then the return code should be 1

  Scenario: Activity Comment Operations
    Given a BP install

    When I run `wp user create testuser2 testuser2@example.com --first_name=test --last_name=user --role=subscriber --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {MEMBER_ID}

    When I run `wp bp activity post-update --user-id={MEMBER_ID} --content="Random Content" --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {ACTIVITY_ID}

    When I run `wp bp activity list --fields=id,user_id,component`
    Then STDOUT should be a table containing rows:
      | id            | user_id       | component   |
      | {ACTIVITY_ID} | {MEMBER_ID}   | activity    |

    When I run `wp bp activity comment {ACTIVITY_ID} --user-id={MEMBER_ID} --content="Activity Comment" --skip-notification --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {COMMENT_ID}

    When I run `wp bp activity get {COMMENT_ID} --fields=id,type`
    Then STDOUT should be a table containing rows:
      | Field | Value            |
      | id    | {COMMENT_ID}     |
      | type  | activity_comment |

    When I run `wp bp activity delete_comment {ACTIVITY_ID} --comment-id={COMMENT_ID} --yes`
    Then STDOUT should contain:
      """
      Success: Activity comment deleted.
      """

    When I try `wp bp activity get {COMMENT_ID} --fields=id,type`
    Then the return code should be 1

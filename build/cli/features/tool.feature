Feature: Manage BuddyPress Tools

 Scenario: BuddyPress repair
    Given a BP install

    When I run `wp bp tool repair friend-count`
    Then STDOUT should contain:
      """
      Complete!
      """

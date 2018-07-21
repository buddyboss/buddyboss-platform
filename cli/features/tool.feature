Feature: Manage BuddyBoss Tools

 Scenario: BuddyBoss repair
    Given a BP install

    When I run `wp bp tool repair friend-count`
    Then STDOUT should contain:
      """
      Complete!
      """

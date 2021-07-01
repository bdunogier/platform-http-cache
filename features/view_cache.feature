Feature: Views are cached by the reverse proxy, and purged by the repository.

  Background:
    Given that Symfony's reverse proxy is enabled
      And View cache is enabled
      And Purge type is set to local

  Scenario: Content view is cached by the reverse proxy
    Given that a content exists
      And I am allowed to view it
     When I view this Content
     Then the response was cached
     When I view this Content again
     Then I get the cached response

  Scenario: Publishing a new version of a Content expires its Content view cache
    Given that a Content view is cached
      And this Content gets updated
      And I view this Content
     Then I get the updated Content view

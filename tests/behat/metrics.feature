@local @local_prometheus
Feature: A metrics.php endpoint is available for Prometheus scrapers

  Scenario: Endpoint authentication fails if no token provided
    Given the following config values are set as admin:
      | token | authtoken | local_prometheus |
    When I visit "/local/prometheus/metrics.php"
    Then I should see "BEHAT: No token specified"

  Scenario: Endpoint authentication succeeds if the correct token is provided
    Given the following config values are set as admin:
      | token | authtoken | local_prometheus |
    When I visit "/local/prometheus/metrics.php?token=authtoken"
    Then I should see "moodle_users_online" on the page

  Scenario: Metrics endpoint can be set to public
    Given I unset "token" in "local_prometheus"
    When I visit "/local/prometheus/metrics.php"
    Then I should see "moodle_users_online" on the page

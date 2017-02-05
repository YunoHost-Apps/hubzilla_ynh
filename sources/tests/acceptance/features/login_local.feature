Feature: Local login
  In order to login locally
  As a member
  I need to successfully authenticate

  Scenario: Provide wrong credentials
    Given I am on "/login"
    When I fill in "id_username" with "foo"
    And I fill in "id_password" with "bar"
    And I press "submit"
    Then I should be on "/login" 

  Scenario: Provide correct credentials
    Given I am on "/login"
    When I fill in "id_username" with "behat"
    And I fill in "id_password" with "behat"
    And I press "submit"
    Then I should be on "/apps"

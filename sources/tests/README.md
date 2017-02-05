The folder tests/ contains resources for automated testing tools.

Here you will find PHPUnit, Behat, etc. files to test the functionaly
of Hubzilla. Right now it only contains some basic tests to see if feasable
this can help improve the project.

# Contents

* unit/           PHPUnit tests
These are unit tests to check the smallest parts, like single functions.
It uses the tool PHPUnit https://phpunit.de/

* acceptance/     functional/acceptance testing
These are behavioral or so called functional/acceptance testing. They
are used to test business logic. They are written in Gherkin and use
the tool Behat http://behat.org/

# How to use?
You need the dev tools which are defined in the composer.json in the
require-dev configuration.
Run ```composer install``` without --no-dev to install these tools.

To run unit tests run ```vendor/bin/phpunit tests/unit/```

To run acceptance tests run ```vendor/bin/behat --config tests/acceptance/behat.yml```

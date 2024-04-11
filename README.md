# WPGraphQL TestCase

![continuous_integration](https://github.com/wp-graphql/wp-graphql-testcase/workflows/continuous_integration/badge.svg)
[![Coverage Status](https://coveralls.io/repos/github/wp-graphql/wp-graphql-testcase/badge.svg)](https://coveralls.io/github/wp-graphql/wp-graphql-testcase)

Is a library of tools for testing WPGraphQL APIs, designed for both WPGraphQL
and WPGraphQL extension development. Currently the library only consisted of a
Codeception Testcase built on top wp-browser's WPTestCase class.

## Installing

1. Run `composer require wp-graphql/wp-graphql-testcase` from your project
   directory in the terminal.

## Codeception Only

1. If your didn't already have codeception installed in the project, run
   `vendor/bin/codecept init wpbrowser`.
2. To make a test case generate a with
   `vendor/bin/codecept generate:wpunit wpunit TestName`. Then just change the
   extending class to `\Tests\WPGraphQL\TestCase\WPGraphQLTestCase`
   :man_shrugging:

## Going Forward

There are plans to add more to this library, and contribution are greatly
appreciated :pray:.

## Contributing

To contribute, fork this repository and open a PR with your requested changes
back into the main repository.

### Local Development

To develop locally, you need to have Docker and Composer installed.

#### Composer Setup

To ensure you have the necessary local dependencies, first run
`composer install`.

#### Docker Setup

This project currently uses a `docker-compose.yml` v2 file. To spin this up, run
`docker-compose up -d`.

#### Local Tests

To run the local tests, use `composer run-phpunit` or
`composer run-codeception`. You should see the tests pass with output generated
in the terminal.

#### Test Coverage

The CI process uses [coveralls.io](https://coveralls.io/) to store coverage
reports. This is available for free for open-source projects, and is required to
run the CI process. Sign up for free and add your `COVERALLS_REPO_TOKEN` value
to GitHub Actions secrets.

## Contributors

<p float="left">
<a href="https://github.com/kidunot89"><img src="https://github.com/kidunot89.png?size=80" title="kidunot89" width="80" height="80"></a>
<a href="https://github.com/missionmike"><img src="https://github.com/missionmike.png?size=80" title="missionmike" width="80" height="80"></a>
</p>

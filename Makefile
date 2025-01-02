BOX_BIN := build/box.phar
COMPOSER_BIN := composer

COMPOSER_DEPENDENCY_ANALYSER_BIN := ./tools/dependency-analyser/bin/composer-dependency-analyser
PHP_CS_FIXER_BIN := ./tools/php-cs-fixer/bin/php-cs-fixer
PHPSTAN_BIN	:= ./tools/phpstan/bin/phpstan
PSALM_BIN	:= ./tools/psalm/bin/psalm
PHPUNIT_BIN	:= ./tools/phpunit/bin/phpunit
INFECTION_BIN	:= ./tools/infection/bin/roave-infection-static-analysis-plugin

.PHONY: help build tests deptrac gpg
help: ## Displays list of available targets with their descriptions
	@grep -E '^[a-zA-Z0-9_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}'


build: tests ## Runs tests and creates the phar-binary
	$(BOX_BIN) compile

install: vendor ## Installs dependencies
vendor: composer.json composer.lock
	$(COMPOSER_BIN) install --no-interaction --no-progress --ansi

composer-dependency-analyser: ## Performs static code analysis using composer-dependency-analyser
	$(COMPOSER_DEPENDENCY_ANALYSER_BIN)

deptrac: vendor ## Analyses own architecture using the default config confile
	bin/deptrac analyse -c deptrac.config.php --cache-file=./.cache/deptrac.cache --no-progress --ansi

#generate-changelog: ## Generates a changelog file based on changes compared to remote origin
#	gem install github_changelog_generator
#	github_changelog_generator -u qossmic -p deptrac --no-issues --future-release <version>

gpg: ## Signs release with local key
	gpg --detach-sign --armor --local-user ${USER} --output deptrac.phar.asc deptrac.phar
	gpg --verify deptrac.phar.asc deptrac.phar

infection: vendor ## Runs mutation tests
	$(INFECTION_BIN) --threads=$(shell nproc || sysctl -n hw.ncpu || 1) --test-framework-options='--testsuite=Tests' --only-covered --min-msi=85 --psalm-config=psalm.xml

php-cs-check: vendor ## Checks for code style violation
	$(PHP_CS_FIXER_BIN) fix --allow-risky=yes --diff --using-cache=no --verbose --dry-run

cs: vendor ## Fixes any found code style violation
	$(PHP_CS_FIXER_BIN) fix --allow-risky=yes

phpstan: vendor ## Performs static code analysis using phpstan
	$(PHPSTAN_BIN) analyse 

psalm: vendor ## Performs static code analysis using psalm
	$(PSALM_BIN)

tests-coverage: vendor ## Runs tests and generate an html coverage report
	XDEBUG_MODE=coverage $(PHPUNIT_BIN) -c . --coverage-html coverage

tests: vendor ## Runs tests followed by a very basic e2e-test
	$(PHPUNIT_BIN) -c .
	bin/deptrac analyse --config-file=docs/examples/Fixture.depfile.yaml --no-cache

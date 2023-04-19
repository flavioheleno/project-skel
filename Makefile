.DEFAULT_GOAL := help

web: ## Open the site url using the default browser
	@xdg-open http://127.0.0.1:8080/
.PHONY: web

rmq: ## Open the RabbitMQ admin url using the default browser
	@xdg-open http://127.0.0.1:15672/
.PHONY: rmq

psql: ## Open the Postgres Command Line utility
	@sudo docker exec -ti sso-kahu-postgres-dev sh -c 'psql -U $${POSTGRES_USER} $${POSTGRES_DB}'
.PHONY: psql

dev: ## Start local development server
	@php -S 127.0.0.1:8080 -t public/ dev.php
.PHONY: dev

composer.lock:
	@composer validate --strict
	@composer update

install: composer.json composer.lock  ## Install PHP dependencies

update: composer.json ## Update PHP dependencies
	@composer update --with-all-dependencies

db-diff: install ## Generate migration based on database diff
	@./bin/migrations.php migrations:diff --from-empty-schema

db-migrate: install ## Execute database migration
	@./bin/migrations.php migrations:migrate next

db-rollback: install ## Rollback database migration
	@./bin/migrations.php migrations:migrate prev

lint: install ## Run php code linter
	@./vendor/bin/parallel-lint -j $(shell nproc) --exclude ./vendor .

phpcs: install ruleset.xml ## Run phpcs coding standards check
	@./vendor/bin/phpcs --standard=./ruleset.xml ./src ./tests

phpcbf: install ruleset.xml ## Run phpcbf coding standards fixer
	@./vendor/bin/phpcbf --standard=./ruleset.xml ./src ./tests

phpstan: install ## Run phpstan static code analysis
	@./vendor/bin/phpstan analyse --level=max --autoload-file=./vendor/autoload.php ./src

phpunit: install ## Run phpunit test suite
	@./vendor/bin/phpunit ./tests --disallow-test-output --process-isolation

psalm: install ## Run psalm taint analysis
	@./vendor/bin/psalm --taint-analysis

help: ## Show this help
	@printf "\033[37mUsage:\033[0m\n"
	@printf "  \033[37mmake [target]\033[0m\n\n"
	@printf "\033[34mAvailable targets:\033[0m\n"
	@grep -E '^[0-9a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[0;36m%-12s\033[m %s\n", $$1, $$2}'
	@printf "\n"
.PHONY: help

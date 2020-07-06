CURRENT_USER := $(shell id -u)
CURRENT_GROUP := $(shell id -g)
DOCKER_COMPOSE_FILE := .docker/docker-compose.yml
COMPOSER := docker-compose -f $(DOCKER_COMPOSE_FILE) run -u $(CURRENT_USER):$(CURRENT_GROUP) --rm composer
APP := docker-compose -f $(DOCKER_COMPOSE_FILE) run -u $(CURRENT_USER):$(CURRENT_GROUP) --rm app

.DEFAULT_GOAL = build

build: docker-build composer-install

docker-build:
	docker-compose -f $(DOCKER_COMPOSE_FILE) build

composer-install:
	$(COMPOSER) install

composer-update:
	$(COMPOSER) update

composer-require:
	$(COMPOSER) require

composer-require-dev:
	$(COMPOSER) require --dev

composer-validate:
	$(COMPOSER) validate composer.json

composer:
	$(COMPOSER) $(args)

phpunit:
	$(APP) ./vendor/bin/phpunit $(args)

phpunit-coverage-html:
	$(APP) ./vendor/bin/phpunit --coverage-html ./.coverage_html

phpunit-coverage-clover:
	$(APP) ./vendor/bin/phpunit --coverage-clover=coverage.xml

phpcs:
	$(APP) ./vendor/bin/phpcs

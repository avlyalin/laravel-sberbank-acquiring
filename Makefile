CURRENT_USER := $(shell id -u)
CURRENT_GROUP := $(shell id -g)
DOCKER_COMPOSE_FILE := docker/docker-compose.yml
COMPOSER := docker-compose -f $(DOCKER_COMPOSE_FILE) run -u $(CURRENT_USER):$(CURRENT_GROUP) --rm composer
APP := docker-compose -f $(DOCKER_COMPOSE_FILE) run -u $(CURRENT_USER):$(CURRENT_GROUP) --rm app

.DEFAULT_GOAL = build

lint: composer-validate phpcs

build: docker-build composer-install

test: phpunit

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
	$(COMPOSER) validate

composer:
	$(COMPOSER) $(args)

phpunit:
	$(APP) ./vendor/bin/phpunit --colors

phpcs:
	$(APP) ./vendor/bin/phpcs

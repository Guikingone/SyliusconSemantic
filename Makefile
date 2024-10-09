include Makefile.common.mk
include Makefile.search.mk

#################################
Docker:

#################################

.PHONY: up down clean build

## Build the containers
build: .cloud/docker compose.yml
	$(DOCKER_COMPOSE) build

## Up the containers
up: .cloud/docker compose.yml
	$(DOCKER_COMPOSE) up -d --force-recreate --remove-orphans

## Down the containers
down: .cloud/docker compose.yml
	$(DOCKER_COMPOSE) down --remove-orphans

## Down the containers with associated volumes
clean: .cloud/docker compose.yml
	$(DOCKER_COMPOSE) down --remove-orphans --volumes

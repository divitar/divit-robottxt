# =============================================================================
# Divit RobotTXT — Developer convenience commands
#
# Usage:  make <target>
# =============================================================================

.PHONY: up down restart setup logs shell db-shell wpcli reset help

# Detect docker compose v2 (plugin) vs v1 (standalone binary).
DOCKER_COMPOSE := $(shell docker compose version > /dev/null 2>&1 && echo "docker compose" || echo "docker-compose")

## up: Start all containers in the background.
up:
	$(DOCKER_COMPOSE) up -d

## down: Stop and remove containers (volumes are preserved).
down:
	$(DOCKER_COMPOSE) down

## restart: Restart all containers.
restart:
	$(DOCKER_COMPOSE) restart

## setup: Install WordPress and activate the plugin (run once after "make up").
setup:
	bash bin/setup.sh

## logs: Tail WordPress container logs.
logs:
	$(DOCKER_COMPOSE) logs -f wordpress

## shell: Open a bash shell inside the WordPress container.
shell:
	$(DOCKER_COMPOSE) exec wordpress bash

## db-shell: Open a MySQL shell.
db-shell:
	$(DOCKER_COMPOSE) exec db mysql -u wordpress -pwordpress wordpress

## wpcli: Run a WP-CLI command. Usage: make wpcli CMD="plugin list"
wpcli:
	$(DOCKER_COMPOSE) run --rm wpcli wp $(CMD)

## reset: Destroy all containers AND volumes (full clean slate).
reset:
	@echo "This will delete all data. Press Ctrl-C to cancel, Enter to continue."
	@read _confirm
	$(DOCKER_COMPOSE) down -v --remove-orphans

## help: Show this help message.
help:
	@grep -E '^## ' Makefile | sed 's/^## /  /'

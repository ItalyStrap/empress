DOCKER_FOLDER = .docker
DOCKER_DIR = cd $(DOCKER_FOLDER) &&
HOST_OWNER = $(shell id -u):$(shell id -g)
FILES_OWNERSHIP = sudo chown -R $(HOST_OWNER) .

TEMP_DIR := ../config-temp
EXCLUDE := --exclude=vendor --exclude=node_modules --exclude=.git
RECTOR_CONFIG=rector-74.php

default: help

# https://marmelab.com/blog/2016/02/29/auto-documented-makefile.html
# https://gist.github.com/prwhite/8168133?permalink_comment_id=4266839#gistcomment-4266839
.PHONY: help
help: ## Display this help screen
	@grep -hP '^\w.*?:.*##.*$$' $(MAKEFILE_LIST) | sort -u | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

.PHONY: up
up: ## Start the container
	docker-compose -f .docker/docker-compose.yml up -d

.PHONY: down
down: ## Stop the container
	docker-compose -f .docker/docker-compose.yml down --remove-orphans --volumes

.PHONY: build
build: ## Build the container
	docker-compose -f .docker/docker-compose.yml build

.PHONY: bash
bash: ## Run bash in the container
	docker-compose -f .docker/docker-compose.yml run --rm php bash

.PHONY: bash83
bash84: ## Run bash in the container
	docker-compose -f .docker/docker-compose.yml run --rm php83 bash

.PHONY: test
test: ## Run the unit tests in the container
	docker-compose -f .docker/docker-compose.yml run --rm php composer unit

.PHONY: copy
copy: ## Copy the configuration files to the temporary folder
	@echo "Copying the configuration files to the temporary folder"
	mkdir -p $(TEMP_DIR)
	rsync -av $(EXCLUDE) ./ $(TEMP_DIR)/
	@echo "Updating PHP version in composer.json"
	sed -i 's/"php" *: *"[^"]*"/"php": ">=8.4"/' $(TEMP_DIR)/composer.json

.PHONY: reset
reset: ## Reset the configuration files
	@echo "Resetting the configuration files"
	rm -rf $(TEMP_DIR)

.PHONY: rm/vendor
rm/vendor: ## Remove the vendor folder
	@echo "Removing the vendor folder"
	rm -rf vendor

.PHONY: downgrade
downgrade:
	@echo "Downgrade with Rector..."
	vendor/bin/rector process --config=$(RECTOR_CONFIG)

.PHONY: rector
rector: ## Run Rector in dry-run mode
	@echo "Rector..."
	vendor/bin/rector process --dry-run

.PHONY: rector/fix
rector/fix: ## Run Rector to fix issues
	@echo "Rector fix..."
	vendor/bin/rector process

.PHONY: rector/final
rector/final: ## Run Rector final clean-up
	@echo "Rector final clean-up..."
	vendor/bin/swiss-knife finalize-classes app tests --dry-run
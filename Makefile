.PHONY: help start stop logs shell test sheets-setup clear-cache dev build

help:
	@echo "Audio/Video Schedule Generator"
	@echo ""
	@echo "Usage: make <target>"
	@echo ""
	@echo "Targets:"
	@echo "  start         Start containers (docker compose up -d)"
	@echo "  stop          Stop containers (docker compose down)"
	@echo "  logs          Follow container logs"
	@echo "  shell         Open a bash shell in the app container"
	@echo "  test          Run the test suite"
	@echo "  sheets-setup  Initialize Google Sheets tabs and headers"
	@echo "  clear-cache   Clear Laravel caches"
	@echo "  dev           Run Vite dev server with HMR"
	@echo "  build         Build production frontend assets"

start:
	docker compose up -d

stop:
	docker compose down

logs:
	docker compose logs

shell:
	docker compose exec app bash

test:
	docker compose exec app php artisan test

sheets-setup:
	docker compose exec app php artisan sheets:setup

clear-cache:
	docker compose exec app php artisan optimize:clear

dev:
	docker compose exec app npm run dev

build:
	docker compose exec app npm run build

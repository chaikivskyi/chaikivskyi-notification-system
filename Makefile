shell:
	@docker compose exec app sh

run-tests:
	@docker compose exec app ./vendor/bin/phpunit

shell:
	@docker compose exec app sh

run-tests:
	@docker compose exec app ./vendor/bin/phpunit

pint:
	@docker compose exec app composer pint

phpstan:
	@docker compose exec app composer phpstan

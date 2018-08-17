.PHONY : build test

# Сборка проекта (Default)
build: vendor/composer/installed.json
	composer validate --no-check-all --strict
	composer dump

vendor/composer/installed.json: composer.json
	composer update


# Тесты
test: vendor/composer/installed.json
	@echo
	-./vendor/bin/phpunit

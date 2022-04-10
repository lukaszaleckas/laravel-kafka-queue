DOCKER_COMPOSE = docker-compose
EXEC_PHP_CONTAINER = ${DOCKER_COMPOSE} exec php

start:
	${DOCKER_COMPOSE} up -d

stop:
	${DOCKER_COMPOSE} stop

phpcs-test:
	php -d memory_limit=1024M vendor/bin/phpcs --standard=phpcs.xml -p

phpstan-test:
	php -d memory_limit=1024M vendor/bin/phpstan analyse

unit-test:
	php -dxdebug.mode=coverage vendor/bin/phpunit --configuration phpunit.xml --coverage-text

test:
	make unit-test && make phpcs-test && make phpstan-test

fix-cs:
	vendor/bin/phpcbf

d-unit-test:
	${EXEC_PHP_CONTAINER} make unit-test

d-phpcs-test:
	${EXEC_PHP_CONTAINER} make phpcs-test

d-phpstan-test:
	${EXEC_PHP_CONTAINER} make phpstan-test

d-test:
	${EXEC_PHP_CONTAINER} make test


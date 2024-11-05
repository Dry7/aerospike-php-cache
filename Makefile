install:
	cp docker-compose.override.yml.dist docker-compose.override.yml
	cp .env.example .env
	git clone https://github.com/aerospike/aerospike-rest-gateway.git aerospike-rest-gateway

run-all:
	docker-compose up --force-recreate --build -d

down-all:
	docker-compose down

migrate:
	docker-compose run --rm php-fpm php artisan migrate

test:
	docker-compose run --rm phpunit && open ./storage/coverage/index.html

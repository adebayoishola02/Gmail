## Create ENV File

``cp src/.env.example src/.env``



## To Start up docker container


``docker compose build --no-cache``

``docker compose up -d``

### Or on a Mac

``DOCKER_DEFAULT_PLATFORM=linux/amd64 docker-compose up --build``

### install composer dependencies

``docker-compose run --rm app composer install``



## To access APP

``docker-compose exec app sh``


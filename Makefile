#!/bin/sh -l

# Define variables
IMAGE_PROD=izdrail/phpteacher.com:latest
DOCKERFILE=Dockerfile
DOCKER_COMPOSE_FILE=docker-compose.yaml


build:
	docker image rm -f $(IMAGE_PROD) || true
	docker buildx build \
		--platform linux/amd64 \
		-t $(IMAGE_PROD) \
		--progress=plain \
		--build-arg CACHEBUST=$$(date +%s) \
		-f $(DOCKERFILE) \
		.  # <-- Build Context Docker file is located at root
dev:
	docker-compose -f $(DOCKER_COMPOSE_FILE) up --remove-orphans


prod:
	docker-compose -f $(DOCKER_COMPOSE_FILE) up --remove-orphans

down:
	docker-compose -f $(DOCKER_COMPOSE_FILE) down

ssh:
	docker exec -it phpteacher.com /bin/bash

publish:
	docker push $(IMAGE_PROD)


# Additional functionality
test:
	docker exec phpteacher.com php artisan test

migrate:
	docker exec phpteacher.com php artisan migrate --force

seed:
	docker exec phpteacher.com php artisan db:seed --force

clean-queue:
	docker exec phpteacher.com php artisan horizon:clear

lint:
	docker exec phpteacher.com ./vendor/bin/phpcs --standard=PSR12 app/

fix-lint:
	docker exec phpteacher.com ./vendor/bin/phpcbf --standard=PSR12 app/

prune:
	docker system prune -f --volumes

logs:
	docker logs -f phpteacher.com

restart:
	docker-compose -f $(DOCKER_COMPOSE_FILE) down
	docker-compose -f $(DOCKER_COMPOSE_FILE) up --remove-orphans -d

# Cleanup target
clean:
	-docker-compose -f $(DOCKER_COMPOSE_FILE) down --rmi all --volumes --remove-orphans
	-docker system prune -f --volumes

#!/bin/sh -e

docker login --username "$DOCKER_USERNAME" --password "$DOCKER_PASSWORD"

docker buildx create --use --name wp --driver remote tcp://192.168.69.1:1234

if ping -c 1 192.168.69.206; then
	docker buildx create --use --name wp --driver remote tcp://192.168.69.206:1234
fi

if ping -c 1 192.168.69.236; then
	docker buildx create --use --name wp --driver remote tcp://192.168.69.236:1234
fi

for f in debian alpine; do
	docker buildx build -f tests/dockerfiles/Dockerfile.$f  --platform linux/arm64,linux/amd64 . -t danog/madelineproto:next-$f --cache-from danog/madelineproto:next-$f --cache-to type=inline
	#IMG=danog/php:8.2-fpm-$f docker buildx build -f tests/dockerfiles/Dockerfile.$f  --platform linux/riscv64 . -t danog/madelineproto:next-$f --cache-from danog/madelineproto:next-$f --cache-to type=inline
	docker push danog/php:next-$f

	docker tag danog/php:next-$f danog/php:next-$f-$CI_COMMIT_HASH
	docker push danog/php:next-$f-$CI_COMMIT_HASH

	if [ "$CI_COMMIT_TAG" != "" ]; then
		docker tag danog/php:next-$f danog/php:$f
		docker push danog/php:$f
	fi
done

docker tag danog/php:next-debian danog/php:next
docker push danog/php:next

if [ "$CI_COMMIT_TAG" != "" ]; then
	docker tag danog/php:next danog/php:latest
	docker push danog/php:latest
fi

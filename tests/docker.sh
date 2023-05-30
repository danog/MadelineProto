#!/bin/sh -e

docker login --username "$DOCKER_USERNAME" --password "$DOCKER_PASSWORD"

docker buildx create --use --name wp --driver remote tcp://192.168.69.1:1234

has_riscv=0
if ping -c 1 192.168.69.206; then
	docker buildx create --append --name wp --driver remote tcp://192.168.69.206:1234
	has_riscv=1
fi

has_x86=0
if ping -c 1 192.168.69.236; then
	docker buildx create --append --name wp --driver remote tcp://192.168.69.236:1234
	has_x86=1
fi

arch="linux/arm64"
if [ $has_x86 -eq 1 ]; then
	arch="$arch,linux/amd64"
fi

for f in debian alpine; do
	docker buildx build -f tests/dockerfiles/Dockerfile.$f  --platform $arch . -t danog/madelineproto:next-$f --cache-from danog/madelineproto:next-$f --cache-to type=inline
	if [ $has_riscv -eq 1 ]; then
		IMG=danog/madelineproto:8.2-fpm-$f docker buildx build -f tests/dockerfiles/Dockerfile.$f  --platform linux/riscv64 . -t danog/madelineproto:next-$f --cache-from danog/madelineproto:next-$f --cache-to type=inline
	fi
	docker push danog/madelineproto:next-$f

	docker tag danog/madelineproto:next-$f danog/madelineproto:next-$f-$CI_COMMIT_SHA
	docker push danog/madelineproto:next-$f-$CI_COMMIT_SHA

	if [ "$CI_COMMIT_TAG" != "" ]; then
		docker tag danog/madelineproto:next-$f danog/madelineproto:$f
		docker push danog/madelineproto:$f
	fi
done

docker tag danog/madelineproto:next-debian danog/madelineproto:next
docker push danog/madelineproto:next

if [ "$CI_COMMIT_TAG" != "" ]; then
	docker tag danog/madelineproto:next danog/madelineproto:latest
	docker push danog/madelineproto:latest
fi

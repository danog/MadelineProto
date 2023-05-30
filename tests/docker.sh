#!/bin/sh -e

docker login --username "$DOCKER_USERNAME" --password "$DOCKER_PASSWORD"

docker buildx create --use --name wp --driver remote tcp://192.168.69.1:1234

has_riscv=0
if ping -c 1 192.168.69.206; then
	docker buildx create --append --name wp --driver remote tcp://192.168.69.206:1234
	has_riscv=1
fi

has_x86=0
for f in 192.168.69.236 192.168.69.207; do
	if ping -c 1 $f; then
		docker buildx create --append --name wp --driver remote tcp://$f:1234
		has_x86=1
		break
	fi
done

arch="linux/arm64"
if [ $has_x86 -eq 1 ]; then
	arch="$arch,linux/amd64"
fi

if [ $has_riscv -eq 1 ]; then
	arch="$arch,linux/riscv64"
fi

echo "Building for $arch"

for f in alpine debian; do
	content="$(cat tests/dockerfiles/Dockerfile.$f)"
	content="$content$(cat tests/dockerfiles/Dockerfile.$f | sed "s/FROM .*/FROM danog/php:8.2-fpm-$f/")"

	docker buildx build -f tests/dockerfiles/Dockerfile.$f  --platform $arch . -t danog/madelineproto:next-$f --cache-from danog/madelineproto:next-$f --cache-to type=inline
	docker push danog/madelineproto:next-$f

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

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

arches="arm64"
if [ $has_x86 -eq 1 ]; then
	arches="$arches amd64"
fi

if [ $has_riscv -eq 1 ]; then
	arches="$arches riscv64"
fi

echo "Building for $arches"

for f in alpine debian; do
	manifest=""
	for arch in $arches; do
		cp tests/dockerfiles/Dockerfile.$f Dockerfile.$arch
		if [ "$arch" == "riscv64" ]; then
			if [ "$f" == "debian" ]; then continue; fi
			sed "s|FROM .*|FROM danog/php:8.2-fpm-$f|" -i Dockerfile.$arch
		fi
		docker buildx build --platform linux/$arch . \
			-f Dockerfile.$arch \
			-t danog/madelineproto:next-$f-$arch \
			--cache-from danog/madelineproto:next-$f \
			--cache-to type=inline \
			--push &
		
		manifest="danog/madelineproto:next-$f-$arch $manifest"
	done
	wait

	docker pull $manifest
	docker manifest create danog/madelineproto:next-$f $manifest
	docker manifest push danog/madelineproto:next-$f

	if [ "$CI_COMMIT_TAG" != "" ]; then
		docker tag danog/madelineproto:next-$f danog/madelineproto:$f
		docker push danog/madelineproto:$f
	fi
done

docker tag danog/madelineproto:next-alpine danog/madelineproto:next
docker push danog/madelineproto:next

if [ "$CI_COMMIT_TAG" != "" ]; then
	docker tag danog/madelineproto:next danog/madelineproto:latest
	docker push danog/madelineproto:latest
fi

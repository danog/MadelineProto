#!/bin/bash -e

docker login --username "$DOCKER_USERNAME" --password "$DOCKER_PASSWORD"

docker buildx create --use --name wp --driver remote tcp://127.0.0.1:1234

has_riscv=0
if ping -c 1 192.168.69.206; then
	docker buildx create --append --name wp --driver remote tcp://192.168.69.206:1234
	has_riscv=1
fi

has_x86=0
for f in 192.168.1.30 192.168.69.236 192.168.69.233 192.168.69.207 192.168.69.130; do
	if ping -c 1 $f; then
		docker buildx create --append --name wp --driver remote tcp://$f:1234
		has_x86=1
		break
	fi
done

arches=""
arches="arm64"
if [ $has_x86 -eq 1 ]; then
	arches="$arches amd64"
fi

if [ $has_riscv -eq 1 ]; then
	arches="$arches riscv64"
fi

echo "Building for $arches"

join_images() {
	if [ "$1" == "debian" ]; then
		docker buildx imagetools create -t danog/madelineproto:$2 danog/madelineproto:next-$1-{arm,amd}64
	else
		docker buildx imagetools create -t danog/madelineproto:$2 danog/madelineproto:next-$1-{arm,amd,riscv}64
	fi
}

for f in alpine; do
	for arch in $arches; do
		cp tests/dockerfiles/Dockerfile.$f Dockerfile.$arch
		if [ "$arch" == "riscv64" ]; then
			if [ "$f" == "debian" ]; then continue; fi
			sed "s|FROM .*|FROM danog/php:8.2-fpm-$f|" -i Dockerfile.$arch
		fi
		docker buildx build --platform linux/$arch . \
			-f Dockerfile.$arch \
			-t danog/madelineproto:next-$f-$arch \
			--cache-from danog/madelineproto:next-$f-$arch \
			--cache-to type=inline \
			--push &
	done
	wait

	join_images $f next-$f

	if [ "$1" == "deploy" ]; then
		join_images $f $f
	fi
done

join_images alpine next

if [ "$1" == "deploy" ]; then
	join_images alpine latest
fi

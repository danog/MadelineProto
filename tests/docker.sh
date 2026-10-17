#!/bin/bash -ex

wget https://github.com/docker-library/php/raw/refs/heads/master/8.4/alpine3.21/fpm/Dockerfile

sed 's/-O2/-O3 -g/g;s/strip --strip-all/echo/g' -i Dockerfile

docker login --username "$DOCKER_USERNAME" --password "$DOCKER_PASSWORD"

has_arm=0
for f in 192.168.1.2 192.168.69.4; do
	if ping -c 1 $f; then
		docker buildx create --use --name wp --driver remote tcp://$f:1234
		has_arm=1
		break
	fi
done

has_riscv=0
if ping -c 1 192.168.69.206; then
	docker buildx create --append --name wp --driver remote tcp://192.168.69.206:1234
	has_riscv=1
fi

has_x86=0
for f in 192.168.69.236; do
	if ping -c 1 $f; then
		docker buildx create --append --name wp --driver remote tcp://$f:1234
		has_x86=1
		break
	fi
done

arches="amd64 arm64 riscv64"
arches="amd64 arm64"

echo "Building for $arches"

join_images() {
	docker buildx imagetools create -t danog/madelineproto:$2 danog/madelineproto:next-$1-{arm,amd,riscv}64
}

for f in alpine; do
	pids=""
	for arch in $arches; do
		cp tests/dockerfiles/Dockerfile.$f Dockerfile.$arch
		cp tests/dockerfiles/docker-php* .

		sed "s|FROM .*||" -i Dockerfile.$arch
		cat Dockerfile Dockerfile.$arch > Dockerfile.final.$arch

		docker buildx build --platform linux/$arch . \
			-f Dockerfile.final.$arch \
			-t danog/madelineproto:next-$f-$arch \
			--cache-from danog/madelineproto:next-$f-$arch \
			--cache-to type=inline \
			--push &
		pids="$pids $!"
	done
	for pid in $pids; do wait $pid; done

	join_images $f next-$f

	if [ "$1" == "deploy" ]; then
		join_images $f $f
	fi
done

join_images alpine next

if [ "$1" == "deploy" ]; then
	join_images alpine latest
fi

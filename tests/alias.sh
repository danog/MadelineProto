#!/bin/sh -e

docker login -p "$docker_password" -u "$docker_user"
docker pull danog/madelineproto:next-debian
docker tag danog/madelineproto:next-debian danog/madelineproto:next
docker push danog/madelineproto:next

#!/bin/sh -e

docker login -p "$dpass" -u "$duser"
docker pull danog/madelineproto:next-debian
docker tag danog/madelineproto:next-debian danog/madelineproto:next
docker push danog/madelineproto:next

#!/bin/bash -e

rm -rf vendor/danog/madelineproto/docs

sed -re 's/\?(\w*) (\$\w+)/\2 = NULL/g;s/= null = null/=null/ig;s/: self//g' -i vendor/league/uri-interfaces/src/Contracts/UriInterface.php
php $(dirname $0)/prepare-5.php

sed -i 's/handleConnectionWindowIncrement[(]\$windowSize[)]/handleConnectionWindowIncrement(int $windowSize)/g' vendor/amphp/http-client/src/Connection/Internal/Http2ConnectionProcessor.php

sed -ri 's/^\{/{ use \\MyCallableMaker;/g;s/\\Closure::fromCallable[(]\[(.+), (.+)\][)]/\1->callableFromInstanceMethod(\2)/g' vendor/amphp/http-client/src/Connection/Internal/Http2ConnectionProcessor.php vendor/amphp/http-client/src/Connection/Http{2,1}Connection.php

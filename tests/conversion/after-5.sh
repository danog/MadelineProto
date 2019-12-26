#!/bin/bash -e

sed 's/^Loop::set.*;//g' -i vendor/amphp/amp/lib/Loop.php
echo 'Loop::set((new DriverFactory())->create());' >> vendor/amphp/amp/lib/Loop.php
cp $(dirname $0)/../random.php vendor/paragonie/random_compat/lib/random.php
cp vendor/danog/madelineproto/src/danog/MadelineProto/Coroutine.php vendor/amphp/amp/lib/Coroutine.php
sed 's/namespace danog\\MadelineProto;/namespace Amp;/g' -i vendor/amphp/amp/lib/Coroutine.php
sed 's/public static function echo/public static function echo_/g' -i vendor/danog/madelineproto/src/danog/MadelineProto/Tools.php

sed 's/use Kelunik\\Certificate\\Certificate/use Kelunik\Certificate\Certificate as _Certificate/g;s/new Certificate/new _Certificate/g;s/empty[(]\$metadata[)] [?] null : //g' -i vendor/amphp/socket/src/TlsInfo.php

echo "<?php

namespace League\Uri\Contracts;

interface UriException
{
}" > vendor/league/uri-interfaces/src/Contracts/UriException.php

find vendor/amphp -type f -name '*.php' -exec sed "s/extension_loaded[(]'zlib'[)]/false/g;s/new[(]/new_(/g;s/clone[(]/clone_(/g" -i {} +

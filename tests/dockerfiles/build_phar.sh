#!/bin/bash -e
#
# Builds madeline.phar out of the MadelineProto sources in $1, and installs it in the docker image as
# /usr/local/lib/madelineproto/madeline.phar, with the madeline-server command (standalone IPC server).
#
# Usage: tests/dockerfiles/build_phar.sh /path/to/MadelineProto

src="$(realpath "$1")"
dest=/usr/local/lib/madelineproto

version="$(php -r 'preg_match("/const RELEASE = \x27([^\x27]+)\x27/", file_get_contents($argv[1]), $m); echo $m[1];' "$src/src/API.php")"
echo "Building madeline.phar $version"

build="$(mktemp -d)"
cd "$build"

# Like tests/makephar.sh: MadelineProto is installed as a package, the phar stub loads vendor/autoload.php.
cat > composer.json <<JSON
{
    "require": {
        "danog/madelineproto": "$version"
    },
    "config": {
        "allow-plugins": {
            "symfony/thanks": true
        }
    },
    "repositories": [
        {
            "type": "path",
            "url": "$src",
            "options": {"symlink": false, "versions": {"danog/madelineproto": "$version"}}
        }
    ]
}
JSON
composer update --no-cache --no-interaction --no-progress
composer dumpautoload --optimize --no-interaction
rm -rf vendor/danog/madelineproto/docs vendor/danog/madelineproto/vendor-bin
mkdir -p vendor/danog/madelineproto/src/danog/MadelineProto/Ipc/Runner
cp vendor/danog/madelineproto/src/Ipc/Runner/entry.php vendor/danog/madelineproto/src/danog/MadelineProto/Ipc/Runner

# tools/makephar.php writes the phar relative to the root of the sources.
php -d phar.readonly=0 "$src/tools/makephar.php" "$build" "madeline.phar" "$version"

mkdir -p "$dest"
mv "$src/madeline.phar" "$dest/madeline.phar"
cd /
rm -rf "$build"

cp "$src/tests/dockerfiles/madeline-server" /usr/local/bin/madeline-server
chmod +x /usr/local/bin/madeline-server

php -r 'require "/usr/local/lib/madelineproto/madeline.phar"; echo "Installed MadelineProto ", danog\MadelineProto\API::RELEASE, PHP_EOL;'

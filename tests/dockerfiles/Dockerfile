# syntax=docker/dockerfile:1

# Not alpine, due to possible performance issues of MUSL malloc.
#
# In theory this should not be relevant because PHP uses its own allocator,
# but some one-time initialization logic inside PHP bypasses it,
# which means system malloc *is* used more often especially in cases like these.
#
# Based on docker-library's image of PHP (https://github.com/docker-library/php/tree/master/8.6/trixie/fpm), the fpm
# variant: it has the CLI too, and the image also works as a php-fpm server (docs/docs/DOCKER.md). Its PHP 8.6 is
# thread-safe (docker-library builds 8.6 as ZTS only), as are the pre-built binaries of danog/mongo-php-async-driver
# for 8.6. jemalloc is used instead of the system malloc (see LD_PRELOAD below).
# The versions pinned here and in Dockerfile.test are updated by the hourly build of the CI (see
# tests/update_docker.sh): PHP's patch releases automatically, the others (a new minor version of PHP, PIE, the
# extensions, MadelineCrypto, Rust, mtg) by a pull request a person reviews.

# danog/MadelineCrypto, pinned: built in crypto-build, checked in the final image.
ARG MADELINE_CRYPTO_VERSION=0.6.3

FROM php:8.6.0-fpm-trixie AS php-runtime

# The extensions of PHP the image adds to docker-library's (mbstring included: docker-library's 8.6 has none), with
# the development packages they're built with removed afterwards, as docker-library does.
RUN set -eux; \
	savedAptMark="$(apt-mark showmanual)"; \
	apt-get update; \
	apt-get install -y --no-install-recommends \
		libffi-dev libgmp-dev libicu-dev libpng-dev libpq-dev libtidy-dev libzip-dev \
	; \
	docker-php-ext-configure mbstring --disable-mbregex; \
	docker-php-ext-install -j "$(nproc)" ffi gd gmp intl mbstring pcntl pdo_mysql pgsql tidy zip; \
	apt-mark auto '.*' > /dev/null; \
	[ -z "$savedAptMark" ] || apt-mark manual $savedAptMark; \
	find /usr/local -type f -executable -exec ldd '{}' ';' \
		| awk '/=>/ { so = $(NF-1); if (index(so, "/usr/local/") == 1) { next }; gsub("^/(usr/)?", "", so); printf "*%s\n", so }' \
		| sort -u \
		| xargs -rt dpkg-query --search \
		| awk 'sub(":$", "", $1) { print $1 }' \
		| sort -u \
		| xargs -r apt-mark manual \
	; \
	apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false; \
	apt-get dist-clean; \
	php -m

RUN apt-get update && apt-get -y --no-install-recommends install $PHPIZE_DEPS ssh git unzip procps libjemalloc2 ffmpeg nghttp2 && apt-get dist-clean

# The extensions are installed with PIE (https://github.com/php/pie), from their packages on Packagist (built from
# the sources of the tagged release on GitHub), pinned: not with pecl, which made the build fail whenever
# pecl.php.net was down. PIE stays in the image, to install other extensions (as root, with the build tools).
ENV PIE_VERSION=1.6.0
ENV PIE_SHA256=81041b276f4e23dfdfa6dc941e5745f9adec824a1bcf6141e226181ef43f519e
RUN set -eux; \
	curl -fsSL -o /usr/local/bin/pie "https://github.com/php/pie/releases/download/$PIE_VERSION/pie.phar"; \
	echo "$PIE_SHA256  /usr/local/bin/pie" | sha256sum -c -; \
	chmod +x /usr/local/bin/pie

# igbinary is pinned to a commit: its latest release (3.2.17RC1) doesn't build on PHP 8.6. No memprof (for the memprof
# metrics): it doesn't build on thread-safe PHP ("ZTS build not supported (yet)").
# The sources are downloaded from the GitHub API, whose anonymous rate limit (60 requests per hour per IP) the CI's
# runners share: the CI passes its token as the github_token secret (optional).
# Enabled by tests/dockerfiles/php.ini, not by PIE, except mongodb (see below).
#
# danog/mongo-php-async-driver is the mongodb extension (for the MongoDB database backend, with async queries), with
# revolt-rs built in: it registers Revolt's classes itself, so everything in the image runs on its native event loop
# (Revolt\EventLoop\Driver\NativeDriver), instead of one of revolt/event-loop's drivers (hence no ext-uv). PIE installs
# its pre-built binary, never its sources (the image has no Rust): PIE looks it up with one request to the GitHub API,
# and when that request fails it silently builds the sources instead (seen in the CI, failing for want of cargo), so
# that fallback is suppressed, the lookup retried, and its failures logged (-v). Enabled in its own file of conf.d,
# which tests/test.sh phpunit-light leaves out to test revolt/event-loop's drivers.
RUN --mount=type=secret,id=github_token,env=GITHUB_TOKEN \
	set -eux; \
	for package in igbinary/igbinary:dev-master#2b1f5d62e3037b07c2a46499ac32091cdb41fb40; do \
		pie install --no-interaction --skip-enable-extension "$package"; \
	done; \
	for package in danog/mongo-php-async-driver:0.2.2; do \
		for attempt in 1 2 3 4 5; do \
			if pie install -v --no-interaction --skip-enable-extension --suppress-download-url-method composer-default \
				"$package"; then \
				break; \
			fi; \
			[ "$attempt" != 5 ]; \
			sleep $((attempt * 10)); \
		done; \
	done; \
	echo 'extension=mongodb' > "$PHP_INI_DIR/conf.d/mongodb.ini"; \
	rm -rf /root/.composer/cache

RUN apt-get -y remove $PHPIZE_DEPS

ADD tests/dockerfiles/php.ini /usr/local/etc/php/php.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN ln -s "$(dpkg -L libjemalloc2 | grep libjemalloc.so | head -1)" /usr/lib/libjemalloc.so

# The unprivileged user the image runs as by default (see USER below), with a fixed UID and GID (1000, the first
# user of most distributions, so that the files it writes to bind mounts belong to them), owning its home (and so
# its own composer home, ~/.composer) and /app. Run with --user root (or user: root in compose) for the rest.
# Nothing it can write may be in the PATH, which root uses too (/composer/vendor/bin, in it, can only be created by
# root): so it doesn't share root's composer home, and the binaries it installs globally aren't in the PATH.
RUN set -eux; \
	groupadd --gid 1000 madeline; \
	useradd --uid 1000 --gid madeline --create-home --home-dir /home/madeline --shell /bin/bash madeline; \
	mkdir -p /app; \
	chown madeline:madeline /app

# Build against the exact PHP headers and ABI shipped in the runtime (danog/MadelineCrypto, pinned).
FROM php-runtime AS crypto-build
USER root
ARG MADELINE_CRYPTO_VERSION
RUN apt-get update \
    && apt-get install -y --no-install-recommends build-essential libclang-dev libssl-dev pkg-config curl ca-certificates git \
    && rm -rf /var/lib/apt/lists/*
RUN git clone --depth 1 --branch "$MADELINE_CRYPTO_VERSION" https://github.com/danog/MadelineCrypto.git /usr/src/madeline-crypto
WORKDIR /usr/src/madeline-crypto
RUN curl --proto '=https' --tlsv1.2 -sSf https://sh.rustup.rs | sh -s -- -y --profile minimal --default-toolchain 1.100.0 \
    && /root/.cargo/bin/cargo test --release --locked --no-default-features --features const-widths \
    && /root/.cargo/bin/cargo build --release --locked \
    && install -D target/release/libmadeline_crypto.so "/artifacts$(php-config --extension-dir)/madeline_crypto.so" \
    && mkdir -p "/artifacts$PHP_INI_DIR/conf.d" \
    && echo 'extension=madeline_crypto.so' > "/artifacts$PHP_INI_DIR/conf.d/madeline-crypto.ini"

FROM scratch
ARG MADELINE_CRYPTO_VERSION

COPY --from=php-runtime / /
COPY --from=crypto-build /artifacts/ /

LABEL "repository"="http://github.com/danog/MadelineProto"
LABEL "homepage"="http://docs.madelineproto.xyz"
LABEL "maintainer"="Daniil Gentili <daniil@daniil.it>"

LABEL "org.opencontainers.image.source"="http://github.com/danog/MadelineProto"
LABEL "org.opencontainers.image.description"=" Async PHP client API for the telegram MTProto protocol"
LABEL "org.opencontainers.image.licenses"=AGPL-3.0-only

ENV PHP_INI_DIR /usr/local/etc/php
ENV PATH=/composer/vendor/bin:${PATH}

ENV USE_ZEND_ALLOC=0
ENV LD_PRELOAD=/usr/lib/libjemalloc.so

RUN php -r 'if ((new ReflectionClass(Revolt\EventLoop::class))->getExtensionName() !== "mongodb" || get_class(Revolt\EventLoop::getDriver()) !== "Revolt\\EventLoop\\Driver\\NativeDriver") { throw new RuntimeException("revolt-rs of danog/mongo-php-async-driver unavailable"); }'
RUN php -v && php -r 'if (!extension_loaded("madeline_crypto") || version_compare(\danog\MadelineProto\Native\Crypto::version(), "0.6.0", "<") || version_compare(phpversion("madeline_crypto"), getenv("MADELINE_CRYPTO_VERSION"), "<")) { throw new RuntimeException("Native crypto extension unavailable"); }'


USER madeline:madeline

STOPSIGNAL SIGTERM

WORKDIR /app

CMD ["php", "/app/bot.php"]

# The image of the light tests with the event loop drivers of Revolt whose extensions the docker image of
# MadelineProto (tests/dockerfiles/Dockerfile) doesn't have: it only has ext-uv, Revolt's default. This one adds ext-ev
# (EvDriver) and ext-event (EventDriver), which Revolt prefers to PHP's own stream_select driver but not to ext-uv's:
# tests/test.sh phpunit-light picks the driver with MADELINE_EVENT_LOOP. Built by tests/test_drivers.sh, from the
# image of the main branch, in the jobs that use it: never pushed, and outside tests/dockerfiles/ since it doesn't
# ship (see tests/release_version.sh).
ARG BASE_IMAGE=danog/madelineproto:next
FROM $BASE_IMAGE

USER root

# ext-event is built with its sockets support (the default of its configure, as distributions build it), so it needs
# ext-sockets (bundled with PHP, so of its version), which the image doesn't have, loaded before it: hence the order
# of the extension lines.
# The sources are downloaded from the GitHub API, whose anonymous rate limit (60 requests per hour per IP) the CI's
# runners share: the CI passes its token as the github_token secret (optional).
RUN --mount=type=secret,id=github_token,env=GITHUB_TOKEN \
	set -eux; \
	apt-get update; \
	apt-get install -y --no-install-recommends autoconf dpkg-dev file g++ gcc libc-dev make pkg-config re2c \
		libevent-dev libssl-dev; \
	for package in php/sockets osmanov/pecl-ev:1.2.4 osmanov/pecl-event:3.2.0; do \
		pie install --no-interaction --skip-enable-extension "$package"; \
	done; \
	printf 'extension=sockets\nextension=ev\nextension=event\n' > "$PHP_INI_DIR/conf.d/event-loop-drivers.ini"; \
	rm -rf /root/.composer/cache; \
	apt-get dist-clean; \
	php -r 'foreach (["sockets", "ev", "event"] as $ext) { if (!extension_loaded($ext)) { exit(1); } }'

USER madeline:madeline

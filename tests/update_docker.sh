#!/bin/bash -e

# Update the version of PHP of the docker image (tests/dockerfiles/Dockerfile) to the latest release of its
# major version (new minor versions too), submitting the change as a pull request: once merged, the CI rebuilds the
# image, before running in it.
#
# Needs GITHUB_TOKEN, able to push branches and to open pull requests (not to push to the main branch), and
# RUN_URL, linked by the pull request.

source tests/submit_pr.sh
export GH_TOKEN="$GITHUB_TOKEN"
dockerfile=tests/dockerfiles/Dockerfile

current=$(grep -o 'php-[0-9]\+\.[0-9]\+\.[0-9]\+' "$dockerfile" | head -1 | cut -d- -f2)
major=${current%%.*}
latest=$(curl -fsS "https://www.php.net/releases/?json&version=$major" | jq -r .version)
if ! [[ "$latest" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
    echo "Unexpected latest version of PHP $major: $latest"
    exit 1
fi
if [ "$latest" == "$current" ]; then
    echo "PHP $current is the latest $major release"
    exit 0
fi

git config --global user.name "${GIT_NAME:-MadelineProto autobuild}"
git config --global user.email "${GIT_EMAIL:-autobuild@madelineproto.xyz}"
gh auth setup-git

perl -pi -e "s/php-\Q$current\E\b/php-$latest/g" "$dockerfile"
git add "$dockerfile"
submit_pr . "${GH_REPO:-danog/MadelineProto}" docker-php "Update PHP to $latest in the docker image" "PHP $latest was released: https://www.php.net/ChangeLog-${latest%%.*}.php#$latest" release:internal

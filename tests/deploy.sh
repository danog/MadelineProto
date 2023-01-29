#!/bin/bash -e

echo "$TAG" | grep -q '\.9999' && exit 0 || true
echo "$TAG" | grep -q '\.9998' && exit 0 || true

# Configure
COMMIT="$(git log -1 --pretty=%H)"
BRANCH=$(git rev-parse --abbrev-ref HEAD)
COMMIT_MESSAGE="$(git log -1 --pretty=%B HEAD)"

echo "Branch: $BRANCH"
echo "Commit: $COMMIT"
echo "Latest tag: $TAG"

git remote add hub https://github.com/danog/MadelineProto

gh release edit --prerelease=false "$TAG"

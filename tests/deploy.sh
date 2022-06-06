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

if [ "$DEPLOY_KEY" != "" ]; then
    eval "$(ssh-agent -s)"
    echo -e "$DEPLOY_KEY" > madeline_rsa
    chmod 600 madeline_rsa
    ssh-add madeline_rsa
fi

git config --global user.email "41898282+github-actions[bot]@users.noreply.github.com"
git config --global user.name "Github Actions"

input=$PWD

cd /tmp
git clone git@github.com:danog/MadelineProtoPhar.git
cd MadelineProtoPhar

cp "$input/tools/phar.php" .
for php in 71 72 73 74 80 81; do
    echo -n "$COMMIT-$php" > release$php
done

git add -A
git commit -am "Release $BRANCH - $COMMIT_MESSAGE"
while :; do
    git push origin master && break || {
        git fetch
        git rebase origin/master
    }
done

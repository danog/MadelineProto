#!/bin/bash -e

# Configure
COMMIT="$(git log -1 --pretty=%H)"
BRANCH=$(git rev-parse --abbrev-ref HEAD)
COMMIT_MESSAGE="$(git log -1 --pretty=%B HEAD)"

echo "Branch: $BRANCH"
echo "Commit: $COMMIT"
echo "Latest tag: $TAG"

# Clean up
madelinePath=$PWD

if [ "$SSH_KEY" != "" ]; then
    eval "$(ssh-agent -s)"
    echo -e "$SSH_KEY" > madeline_rsa
    chmod 600 madeline_rsa
    ssh-add madeline_rsa
fi

git config --global user.email "41898282+github-actions[bot]@users.noreply.github.com"
git config --global user.name "Github Actions"

input=$PWD

cd ~/MadelineProtoPhar
git pull

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

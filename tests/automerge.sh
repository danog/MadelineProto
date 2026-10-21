#!/bin/bash -e

# Merge the pull request whose checks just passed on HEAD_SHA, if it's one merged automatically: of Dependabot, or an
# update of PHP of the docker image (tests/update_docker.sh, on the docker-php branch of the app of the autobuild).
# Run by automerge.yml. Only if it's still at that commit (not pushed again since), and all of its checks passed (the
# release label and version too).
#
# Needs GH_TOKEN, able to merge pull requests that update workflows (not the token of a job), and HEAD_SHA.

repository=${GH_REPO:-danog/MadelineProto}
export GH_REPO="$repository"

pr=$(gh pr list --state open --json number,headRefOid,headRefName,author --jq ".[] | select(.headRefOid == \"$HEAD_SHA\") | select(.author.login == \"app/dependabot\" or (.author.login == \"app/madelineproto-autobuild\" and .headRefName == \"docker-php\")) | .number")
if [ -z "$pr" ]; then
    echo "No open pull request to merge automatically at $HEAD_SHA (pushed again or closed since, or not one of those)"
    exit 0
fi

# An update of PHP is merged with the token of an admin: only if the app made all of its commits, and it only changes
# what tests/update_docker.sh changes.
if [ "$(gh pr view "$pr" --json headRefName --jq .headRefName)" == docker-php ]; then
    others=$(gh pr view "$pr" --json commits --jq '[.commits[].authors[].login | select(. != "madelineproto-autobuild[bot]")] | unique | join(", ")')
    files=$(gh pr view "$pr" --json files --jq '[.files[].path | select(. != "tests/dockerfiles/Dockerfile" and . != "src/API.php")] | join(", ")')
    if [ -n "$others" ] || [ -n "$files" ]; then
        echo "Not merging #$pr: an update of PHP with commits of others (${others:-none}) or other files (${files:-none})"
        exit 0
    fi
fi

pending=$(gh pr checks "$pr" --json name,bucket --jq '[.[] | select(.bucket != "pass" and .bucket != "skipping") | .name] | join(", ")')
if [ -n "$pending" ]; then
    echo "Not merging #$pr: some of its checks didn't pass ($pending)"
    exit 0
fi

gh pr merge "$pr" --merge --match-head-commit "$HEAD_SHA"
echo "Merged #$pr"

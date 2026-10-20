#!/bin/bash -e

# Merge the pull request of Dependabot whose checks (pull-request.yml) just passed on HEAD_SHA: run by
# dependabot-automerge.yml. Only if it's still at that commit (not pushed again since), and all of its checks
# passed (the release label too).
#
# Needs GH_TOKEN, able to merge pull requests that update workflows (not the token of a job), and HEAD_SHA.

repository=${GH_REPO:-danog/MadelineProto}
export GH_REPO="$repository"

pr=$(gh pr list --author app/dependabot --state open --json number,headRefOid --jq ".[] | select(.headRefOid == \"$HEAD_SHA\") | .number")
if [ -z "$pr" ]; then
    echo "No open pull request of Dependabot at $HEAD_SHA (pushed again or closed since)"
    exit 0
fi

pending=$(gh pr checks "$pr" --json name,bucket --jq '[.[] | select(.bucket != "pass" and .bucket != "skipping") | .name] | join(", ")')
if [ -n "$pending" ]; then
    echo "Not merging #$pr: some of its checks didn't pass ($pending)"
    exit 0
fi

gh pr merge "$pr" --merge --match-head-commit "$HEAD_SHA"
echo "Merged #$pr"

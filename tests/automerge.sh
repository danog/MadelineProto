#!/bin/bash -e

# Merge the pull request whose checks just passed on HEAD_SHA, if it's one merged automatically: of Dependabot, or an
# update of PHP of the docker image (tests/update_docker.sh, on the docker-php branch of the app of the autobuild).
# Run by automerge.yml. Only if it's still at that commit (not pushed again since), and all of its checks passed (the
# release label and version too).
#
# Needs GH_TOKEN, able to merge pull requests that update workflows (not the token of a job), and HEAD_SHA.

set -o pipefail

repository=${GH_REPO:-danog/MadelineProto}
export GH_REPO="$repository"

# Without the versions of PHP and the RELEASE constant.
normalize_versions()
{
    sed -E "s/php-[0-9]+\.[0-9]+\.[0-9]+\b/php-VERSION/g; s/^(    public const RELEASE = )'[0-9]+\.[0-9]+\.[0-9]+';\$/\1'VERSION';/"
}

pr=$(gh pr list --state open --json number,headRefOid,headRefName,author --jq ".[] | select(.headRefOid == \"$HEAD_SHA\") | select(.author.login == \"app/dependabot\" or (.author.login == \"app/madelineproto-autobuild\" and .headRefName == \"docker-php\")) | .number")
if [ -z "$pr" ]; then
    echo "No open pull request to merge automatically at $HEAD_SHA (pushed again or closed since, or not one of those)"
    exit 0
fi

# An update of PHP is merged with the token of an admin, but the token of the app is also in the jobs of the autobuild
# that run the code of the dependencies: only if the app made all of its commits, and it only changes what
# tests/update_docker.sh changes, the versions of PHP of the Dockerfile and the RELEASE constant (the files are the
# same as in the main branch without them).
if [ "$(gh pr view "$pr" --json headRefName --jq .headRefName)" == docker-php ]; then
    others=$(gh pr view "$pr" --json commits --jq '[.commits[].authors[].login | select(. != "madelineproto-autobuild[bot]")] | unique | join(", ")')
    if [ -n "$others" ]; then
        echo "Not merging #$pr: an update of PHP with commits of others ($others)"
        exit 0
    fi
    git fetch -q origin "+refs/heads/v8:refs/remotes/origin/v8" "$HEAD_SHA"
    base=$(git merge-base origin/v8 "$HEAD_SHA")
    files=$(git diff --no-renames --name-only "$base" "$HEAD_SHA")
    for file in $files; do
        case $file in
            tests/dockerfiles/Dockerfile|src/API.php) ;;
            *)
                echo "Not merging #$pr: an update of PHP that changes $file"
                exit 0
                ;;
        esac
        old=$(git show "$base:$file" | normalize_versions)
        new=$(git show "$HEAD_SHA:$file" | normalize_versions)
        if [ "$old" != "$new" ]; then
            echo "Not merging #$pr: an update of PHP that changes more than the versions of $file"
            exit 0
        fi
    done
fi

pending=$(gh pr checks "$pr" --json name,bucket --jq '[.[] | select(.bucket != "pass" and .bucket != "skipping") | .name] | join(", ")')
if [ -n "$pending" ]; then
    echo "Not merging #$pr: some of its checks didn't pass ($pending)"
    exit 0
fi

gh pr merge "$pr" --merge --match-head-commit "$HEAD_SHA"
echo "Merged #$pr"

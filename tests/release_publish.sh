#!/bin/bash -e

# Publish the draft of the next release (kept up to date by tests/release_draft.sh) as a pre-release, which
# tags the main branch: the CI of the tag builds and tests the phar, and tests/release_phar.sh makes the release
# final once it passed. Run periodically by release-publish.yml.
#
# Only publishes a draft that already exists (none is created), and only once the main branch is ready to be
# released as it is: its RELEASE constant is the version of the draft (tests/makephar.sh checks it against the
# tag), the CI of its last push succeeded, and the tag doesn't exist yet.
#
# Needs GITHUB_TOKEN, a token whose events run workflows (not the one of a job: the tag push must run the CI),
# able to edit the releases. With DRY_RUN=1, prints what it would do instead.

repository=${GH_REPO:-danog/MadelineProto}
branch=v8
export GH_TOKEN="$GITHUB_TOKEN"

head=$(gh api "repos/$repository/commits/$branch" --jq .sha)
release=$(gh api "repos/$repository/contents/src/API.php?ref=$head" --jq .content | base64 -d | grep -o "RELEASE = '[^']*'" | grep -o "[0-9][^']*")

draft=$(gh api --paginate "repos/$repository/releases?per_page=100" | jq -s -c --arg major "${release%%.*}." \
    'add | [.[] | select(.draft and (.tag_name | startswith($major)))] | sort_by(.created_at) | last // empty')
if [ "$draft" == "" ]; then
    echo "No draft of the next release: nothing to publish"
    exit 0
fi
version=$(jq -r .tag_name <<< "$draft")

if [ "$release" != "$version" ]; then
    echo "The RELEASE constant of $branch is $release, not $version yet: the draft is published once release-draft.yml updated it"
    exit 0
fi
if gh api "repos/$repository/git/ref/tags/$version" > /dev/null 2>&1; then
    echo "::warning::The tag $version already exists: publishing the draft would release it, not $branch. Not publishing"
    exit 0
fi
# The CI of the last push of the main branch, which release-draft.yml waits for too.
conclusion=$(gh run list --repo "$repository" --workflow ci.yml --branch "$branch" --event push --commit "$head" --limit 1 --json conclusion --jq '.[0].conclusion // empty')
if [ "$conclusion" != "success" ]; then
    echo "The CI of $head on $branch didn't succeed (${conclusion:-not finished}): not publishing $version"
    exit 0
fi

if [ -n "$DRY_RUN" ]; then
    echo "Would publish $version ($(jq -r .name <<< "$draft")) as a pre-release, tagging $head"
    exit 0
fi
# Tagging the commit checked above, not whatever the branch points to by then.
gh api -X PATCH "repos/$repository/releases/$(jq -r .id <<< "$draft")" -f tag_name="$version" -f target_commitish="$head" \
    -F draft=false -F prerelease=true -f make_latest=false > /dev/null
echo "Published $version as a pre-release, tagging $head: the CI of the tag releases it once it passed"

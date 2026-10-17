#!/bin/bash -e

# Run release.yml for a tag (its notes and the announcement of the changelog) and wait for it, failing if it
# fails: the CI releases the tags with the token of its job, whose events don't run workflows.
#
# Needs GH_TOKEN, able to run workflows and to read their runs.

tag=$1
repository=${GH_REPO:-danog/MadelineProto}

# A minute early, against a skew of the clock of the runner.
since=$(date -u -d '-1 minute' +%Y-%m-%dT%H:%M:%SZ)
gh workflow run release.yml --repo "$repository" --ref "$tag" -f tag="$tag"

id=""
for i in $(seq 60); do
    id=$(gh run list --repo "$repository" --workflow release.yml --event workflow_dispatch --branch "$tag" \
        --json databaseId,createdAt --jq "[.[] | select(.createdAt >= \"$since\")] | last | .databaseId // empty")
    if [ -n "$id" ]; then
        break
    fi
    sleep 5
done
if [ -z "$id" ]; then
    echo "The run of release.yml for $tag didn't start!"
    exit 1
fi
echo "Waiting for https://github.com/$repository/actions/runs/$id"
gh run watch "$id" --repo "$repository" --exit-status

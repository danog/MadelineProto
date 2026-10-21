#!/bin/bash
# Sourced by the scripts of the CI that submit pull requests: tests/autobuild.sh, tests/update_docker.sh.

# The body of a pull request: pr_body <body>.
pr_body()
{
    echo "$1

Built by $RUN_URL"
}

# Commit the staged changes of a repository to a branch, opening a pull request if there's none (updating it
# otherwise), whose URL is put in PR_URL: submit_pr <dir> <repository> <branch> <title> <body> [labels],
# the labels separated by commas: the release labels of an existing pull request that aren't among them are removed.
# PR_URL is empty if the default branch already has the changes: then the repository is left at the tip of
# the default branch, so a submodule doesn't point to a commit that was never pushed.
# Not called in a condition: there, errors would not stop the script.
submit_pr()
{
    local dir=$1 repository=$2 branch=$3 title=$4 body=$5 labels=${6:-}
    local base stale
    git -C "$dir" status --short
    git -C "$dir" checkout -q -B "$branch"
    git -C "$dir" commit -q -m "$title"
    # The default branch may have moved since the checkout: the pull request of the previous build may have
    # been merged meanwhile, with the same changes, which would be submitted again.
    base=$(gh repo view "$repository" --json defaultBranchRef --jq .defaultBranchRef.name)
    git -C "$dir" fetch -q origin "$base"
    if git -C "$dir" diff --name-only -z HEAD~1 HEAD | xargs -0 git -C "$dir" diff --quiet FETCH_HEAD HEAD --; then
        echo "$base of $repository already has the changes"
        git -C "$dir" checkout -q --detach FETCH_HEAD
        PR_URL=""
        return
    fi
    git -C "$dir" push -q --force origin "$branch"
    PR_URL=$(gh pr list --repo "$repository" --head "$branch" --state open --json url --jq '.[0].url')
    if [ "$PR_URL" == "" ]; then
        PR_URL=$(gh pr create --repo "$repository" --base "$base" --head "$branch" --title "$title" --body "$(pr_body "$body")" ${labels:+--label "$labels"})
    else
        stale=""
        if [ -n "$labels" ]; then
            stale=$(gh pr view "$PR_URL" --json labels --jq "[.labels[].name | select(startswith(\"release:\"))] - (\"$labels\" | split(\",\")) | join(\",\")")
        fi
        gh pr edit "$PR_URL" --title "$title" --body "$(pr_body "$body")" ${labels:+--add-label "$labels"} ${stale:+--remove-label "$stale"}
        echo "Updated the pull request of $repository"
    fi
    echo "Pull request of $repository: $PR_URL"
}

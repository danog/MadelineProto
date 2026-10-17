#!/bin/bash
# Sourced by the scripts of the CI that submit pull requests: tests/autobuild.sh, tests/update_docker.sh.

# The body of a pull request: pr_body <body>.
pr_body()
{
    echo "$1

Built by $RUN_URL"
}

# Commit the staged changes of a repository to a branch, opening a pull request if there's none (updating it
# otherwise), whose URL is put in PR_URL: submit_pr <dir> <repository> <branch> <title> <body> [label].
# Not called in a condition: there, errors would not stop the script.
submit_pr()
{
    local dir=$1 repository=$2 branch=$3 title=$4 body=$5 label=${6:-}
    git -C "$dir" status --short
    git -C "$dir" checkout -q -B "$branch"
    git -C "$dir" commit -q -m "$title"
    git -C "$dir" push -q --force origin "$branch"
    PR_URL=$(gh pr list --repo "$repository" --head "$branch" --state open --json url --jq '.[0].url')
    if [ "$PR_URL" == "" ]; then
        local base
        base=$(gh repo view "$repository" --json defaultBranchRef --jq .defaultBranchRef.name)
        PR_URL=$(gh pr create --repo "$repository" --base "$base" --head "$branch" --title "$title" --body "$(pr_body "$body")" ${label:+--label "$label"})
    else
        gh pr edit "$PR_URL" --title "$title" --body "$(pr_body "$body")" ${label:+--add-label "$label"}
        echo "Updated the pull request of $repository"
    fi
    echo "Pull request of $repository: $PR_URL"
}

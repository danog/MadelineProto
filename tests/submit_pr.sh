#!/bin/bash
# Sourced by the scripts of the CI that submit pull requests: tests/autobuild.sh, tests/update_docker.sh.

# Commit the staged changes of a repository to a branch, opening a pull request if there's none (updating it
# otherwise): submit_pr <dir> <repository> <branch> <title> <body> [label].
# Not called in a condition: there, errors would not stop the script.
submit_pr()
{
    local dir=$1 repository=$2 branch=$3 title=$4 body=$5 label=${6:-}
    git -C "$dir" status --short
    git -C "$dir" checkout -q -B "$branch"
    git -C "$dir" commit -q -m "$title"
    git -C "$dir" push -q --force origin "$branch"
    local number
    number=$(gh pr list --repo "$repository" --head "$branch" --state open --json number --jq '.[0].number')
    if [ "$number" == "" ]; then
        local base
        base=$(gh repo view "$repository" --json defaultBranchRef --jq .defaultBranchRef.name)
        gh pr create --repo "$repository" --base "$base" --head "$branch" --title "$title" --body "$body

Built by $RUN_URL" ${label:+--label "$label"}
    else
        gh pr edit "$number" --repo "$repository" --title "$title" ${label:+--add-label "$label"}
        echo "Updated the pull request of $repository"
    fi
}

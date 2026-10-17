#!/bin/bash -e

# Send the phar of a release to the @madelineprotophar channel, with a link to its changelog, once it's fully
# released (marked in the body of the release, so that running it again doesn't send it twice).
#
# Usage: tests/send_phar.sh <tag>. Needs GH_TOKEN (able to edit the releases) and PHAR_BOT_TOKEN.

tag=$1
repository=${GH_REPO:-danog/MadelineProto}
marker='<!-- phar sent -->'

release=$(gh api "repos/$repository/releases/tags/$tag")
if [ "$(jq -r '.draft or .prerelease' <<< "$release")" == "true" ]; then
    echo "$tag is not released yet"
    exit 0
fi
if [[ "$(jq -r '.body // ""' <<< "$release")" == *"$marker"* ]]; then
    echo "The phar of $tag was already sent"
    exit 0
fi
if [ "$PHAR_BOT_TOKEN" == "" ]; then
    echo "PHAR_BOT_TOKEN is not set!"
    exit 1
fi

dir=$(mktemp -d)
gh release download "$tag" --repo "$repository" --pattern madeline81.phar --dir "$dir"
# --form-string: with -F, a value starting with @ is the name of a file to upload ("@madelineprotophar").
curl --fail-with-body --silent --show-error \
    --form-string chat_id=@madelineprotophar \
    -F "document=@$dir/madeline81.phar;filename=madeline-$tag.phar" \
    --form-string caption="MadelineProto $tag

Changelog: https://github.com/$repository/releases/tag/$tag" \
    "https://api.telegram.org/bot$PHAR_BOT_TOKEN/sendDocument" > "$dir/response" \
    || { cat "$dir/response"; echo; echo "Could not send the phar of $tag to @madelineprotophar!"; exit 1; }
rm -rf "$dir"

# The body again: the other steps of release.yml edit it too.
body=$(gh api "repos/$repository/releases/tags/$tag" --jq '.body // ""')
gh api -X PATCH "repos/$repository/releases/$(jq -r .id <<< "$release")" -f body="${body%"${body##*[![:space:]]}"}
$marker
" > /dev/null
echo "Sent the phar of $tag to @madelineprotophar"

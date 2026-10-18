#!/bin/bash -e

# Publish the phar built and tested by tests/makephar.sh (the build job) to the release of a tag, and make the release
# final: run by the release job of the CI once every test passed, so that a release is never published untested.
#
# Usage: tests/release_phar.sh <tag> <directory with the files of the release>. Needs GH_TOKEN (able to edit the
# releases) and GH_REPO.

tag=$1
dir=$2
if [ "$tag" == "" ] || [ ! -d "$dir" ]; then
    echo "Usage: $0 <tag> <directory>"
    exit 1
fi
grep -qx "$tag" <(cat "$dir/release"; echo) || {
    echo "The files in $dir are not the ones of $tag!"
    exit 1
}

for file in madeline.phar madeline81.phar release madeline.php; do
    gh release upload "$tag" "$dir/$file"
done

gh release edit --prerelease=false "$tag"
gh release edit --latest=true "$tag"

# Sent to @madelineprotophar by release.yml (tests/send_phar.sh), dispatched next.

#!/bin/bash -e
# Tests and builds the web UI (webui/), and checks that the committed bundle
# (src/WebUI/templates/assets/tweb/) is the build of the committed sources: Composer and the phar ship the
# bundle, without running npm.
#
# With --update (the daily build, .github/workflows/autobuild.yml), the bundle is only rebuilt, to be
# committed: run it without secrets, it runs the code of the npm dependencies.

cd "$(dirname "$0")/../webui"

npm ci
npm run typecheck
npm test
npm run build

if [ "$1" != "--update" ] && [ "$(git status --porcelain -- ../src/WebUI/templates/assets)" != "" ]; then
    git diff --stat -- ../src/WebUI/templates/assets
    echo "The web UI bundle is outdated: run npm run build in webui/ and commit src/WebUI/templates/assets/tweb/!"
    exit 1
fi

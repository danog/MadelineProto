#!/bin/bash -e

find docs/ -name '*.md' -exec sed 's/\.md/\.html/g' -i {} +
sed 's:(danog:(/PHP/danog:g' -i docs/docs/PHP/index.md

sed 's/Array of \[long|string/Array of [long\\|string/g' docs/docs/API_docs/methods/* -i

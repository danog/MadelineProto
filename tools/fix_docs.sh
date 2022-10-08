#!/bin/bash -e

find docs/ -name '*.md' -exec sed 's/\.md/\.html/g' -i {} +
sed 's:(danog:(/PHP/danog:g' -i docs/docs/PHP/index.md

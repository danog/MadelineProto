#!/bin/bash

# Run the tests of the session group (using the accounts of the CI): one class at a time, each in its own process,
# printing every test as it starts, with a timeout, so that one hanging doesn't prevent seeing the others.

failed=0
for test in $(grep -l '@group session' tests/danog/MadelineProto/*Test.php); do
    echo "Running $test"
    timeout -k 30 900 php -dzend.assertions=1 vendor/bin/phpunit --debug "$test"
    status=$?
    if [ $status -eq 124 ]; then
        echo "$test timed out!"
    fi
    if [ $status -ne 0 ]; then
        failed=1
    fi
done
exit $failed

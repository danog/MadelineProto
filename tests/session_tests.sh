#!/bin/bash

# Run the tests of the session group (using the accounts of the CI): one class at a time, each in its own process,
# printing every test as it starts, with a timeout, so that one hanging doesn't prevent seeing the others.

failed=0
for test in $(grep -l '@group session' tests/danog/MadelineProto/*Test.php); do
    echo "Running $test"
    timeout -k 30 900 php -dzend.assertions=1 vendor/bin/phpunit --debug "$test"
    status=$?
    # 137 if it had to be killed.
    if [ $status -eq 124 ] || [ $status -eq 137 ]; then
        echo "$test timed out!"
    fi
    # Stop the IPC workers of the sessions, which outlive the tests: the next class would get a stuck one.
    if pkill -f "MadelineProto worker $PWD/"; then
        for i in $(seq 30); do pgrep -f "MadelineProto worker $PWD/" >/dev/null || break; sleep 1; done
        pkill -9 -f "MadelineProto worker $PWD/"
    fi
    if [ $status -ne 0 ]; then
        failed=1
    fi
done
exit $failed

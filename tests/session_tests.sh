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
    # Stop the IPC worker of the session of MadelineTestCase, which outlives the tests: the next class would get a
    # stuck one. Only that one: locally, the other sessions in the repository may be in use.
    # With SIGTERM, which they handle saving the session.
    worker="MadelineProto worker $PWD/testing.madeline"
    if pkill -f "$worker"; then
        for i in $(seq 60); do pgrep -f "$worker" >/dev/null || break; sleep 1; done
        if pgrep -f "$worker" >/dev/null; then
            echo "The worker of testing.madeline didn't stop after $test:"
            pgrep -af "$worker"
            status=1
        fi
    fi
    if [ $status -ne 0 ]; then
        failed=1
    fi
done
exit $failed

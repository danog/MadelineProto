#!/bin/bash

# Run the tests of the session group (using the accounts of the CI): each class in its own process, printing every test
# as it starts, with a timeout, so that one hanging doesn't prevent seeing the others.
#
# The classes run in lanes, at once: in the CI, each lane has its own sessions of the bot, from its own authorizations
# in BOT_SESSION_AUTH (see tests/ci_sessions.php): copies of a session share its auth key, and the responses go to the
# wrong process. The classes of a lane run one after the other. Without BOT_SESSION_AUTH (locally), the lanes run one
# after the other, with testing.madeline.

# Each lane: the classes, and the authorizations of its main session (MadelineTestCase) and of the template of the
# sessions of its child processes (RESUME_CYCLE_SESSION), or - without sessions of the bot. Authorization 0 is the one of
# the phar build, 11 and 12 are the ones of the lane of the classes that aren't in one.
lanes=(
    "ResumableStreamTest:1:2"
    "FileIdTest EventHandlerStartTest:3:4"
    "ResumeCycleTest:5:6"
    "ResumeCycleUploadTest:7:8"
    "ResumeCycleEncryptedUploadTest:9:10"
    # The two user accounts, without the bot.
    "CallResumeTest SecretChatResumeTest:-:-"
)

dir="$PWD/ci-lanes"
rm -rf "$dir"
mkdir -p "$dir"

# Any class of the session group that isn't in a lane runs in a lane of its own, after the others.
assigned=" ${lanes[*]} "
extra=""
for test in $(grep -l '@group session' tests/danog/MadelineProto/*Test.php); do
    class=$(basename "$test" .php)
    if [[ "$assigned" != *[\ \"]"$class"[\ :]* ]]; then
        extra="$extra $class"
    fi
done

run_class()
{
    local class=$1 session=$2 status
    echo "Running $class"
    timeout -k 30 900 php -dzend.assertions=1 vendor/bin/phpunit --debug "tests/danog/MadelineProto/$class.php"
    status=$?
    # 137 if it had to be killed.
    if [ $status -eq 124 ] || [ $status -eq 137 ]; then
        echo "$class timed out!"
    fi
    # Stop the IPC worker of the session of MadelineTestCase, which outlives the tests: the next class would get a stuck
    # one. Only that one: locally, the other sessions in the repository may be in use. With SIGTERM, which they handle
    # saving the session.
    local worker="MadelineProto worker $session"
    if pkill -f "$worker"; then
        for i in $(seq 60); do pgrep -f "$worker" >/dev/null || break; sleep 1; done
        if pgrep -f "$worker" >/dev/null; then
            echo "The worker of $session didn't stop after $class:"
            pgrep -af "$worker"
            status=1
        fi
    fi
    return $status
}

run_lane()
{
    local n=$1 classes=$2 main=$3 template=$4 failed=0
    if [ "$main" = - ]; then
        local session="$dir/$n/none"
    elif [ -n "$BOT_SESSION_AUTH" ]; then
        local session="$dir/$n/testing.madeline"
        mkdir -p "$dir/$n"
        php tests/ci_sessions.php bot "$session" BOT_SESSION_AUTH "$main" || return 1
        php tests/ci_sessions.php bot "$dir/$n/template.madeline" BOT_SESSION_AUTH "$template" || return 1
        export TEST_SESSION="$session" RESUME_CYCLE_SESSION="$dir/$n/template.madeline"
    else
        local session="$PWD/testing.madeline"
    fi
    for class in $classes; do
        run_class "$class" "$session" || failed=1
    done
    return $failed
}

if [ -n "$extra" ]; then
    lanes+=("$extra:11:12")
fi

failed=0
pids=()
for n in "${!lanes[@]}"; do
    IFS=: read -r classes main template <<< "${lanes[$n]}"
    if [ -n "$BOT_SESSION_AUTH" ]; then
        run_lane "$n" "$classes" "$main" "$template" > "$dir/$n.log" 2>&1 &
        pids[$n]=$!
    else
        run_lane "$n" "$classes" "$main" "$template" || failed=1
    fi
done
for n in "${!pids[@]}"; do
    wait "${pids[$n]}" || failed=1
    echo "=== Lane $n: ${lanes[$n]%%:*}"
    cat "$dir/$n.log"
done
exit $failed

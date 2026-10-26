---
name: debug-ipc-hang
description: Diagnose a MadelineProto script that hangs or ignores SIGTERM — reproduce IPC worker failures in-process, and get PHP stack traces out of fibers stuck in tight loops.
---

# Debugging hangs

## A script hangs (IPC client)

A plain script like `new API('x.madeline'); $API->method()` (no startAndLoop) runs as an IPC *client*: the
work happens in a spawned `MadelineProto worker <session>` process, whose log goes neither to the client's
terminal nor to ./MadelineProto.log. A worker-side exception looks like a silent hang of the client (it
only ever shows `ClientAbstract->__call` awaiting).

1. Kill the session's worker: `pgrep -fl "MadelineProto worker <resolved session path>"` (on macOS the
   path is `/private/tmp/...`; never kill a bare "MadelineProto worker").
2. Rerun the same calls in-process, with a big file log:
   ```php
   Magic::start(light: false);
   Magic::$isIpcWorker = true; // forces forceFull (Settings\Ipc::getSlow has no setter)
   $s = new Settings;
   $s->getLogger()->setType(Logger::FILE_LOGGER)->setExtra('/tmp/x.log')->setLevel(Logger::ULTRA_VERBOSE)->setMaxSize(100 * 1024 * 1024);
   $API = new API('fuzz_user.madeline', $s);
   ```
3. Grep the log for `Error during deserializing message`, `Invalid constructor given`, "Reporting:
   Surfaced", "Unhandled future".

Past mechanism: a throw while deserializing a response (an unknown constructor, e.g. tdesktop's abandoned
layer 231 `userFull#7fe738c4`, or a TL after-deserialization callback like PeerDatabase addChat ->
getIdInternal) killed the DC read loop; the request was resent after reconnecting, failed again forever,
and the caller never returned. Since 201ebf9ac `ReadLoop::deserialize` fails the request with the TL
exception instead (`UndeserializableResultTest`), so such bugs surface as exceptions: a new hang of this
shape means that path regressed.

A script that seems to hang at `start()` may simply be at an interactive prompt (app credentials, login):
with stdin closed it fails within a second instead.

## A loop ignores SIGTERM (tight loop)

A fiber that never suspends keeps MadelineProto's signal handlers (`EventLoop::onSignal`) from running:
`timeout`/`pkill` do nothing, and `sample <pid>` on macOS only shows native frames. Don't SIGKILL the
session's worker (it saves the session on SIGTERM); reproduce in a throwaway script on a session *copy*
instead, and get the PHP stack with async signals there, run under `timeout -s KILL`:

```php
pcntl_async_signals(true);
pcntl_signal(SIGALRM, static fn () => fwrite(STDERR, (new Exception)->getTraceAsString()));
pcntl_alarm(40);
```

(This found the handleUpdate replay loop: postponed updates re-enqueued into the IT_MODE_DELETE SplQueue
being drained when internalStart() failed.)

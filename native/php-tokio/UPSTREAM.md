Based on danog/php-tokio bcbc4a4861b41f2050007b5b09e07abaad29f8f9 (Apache-2.0).
Only the Revolt bridge is included; the borrowing macros are deliberately unused.

Local changes: one explicitly configured Tokio scheduler thread, no blocking
pool use, runtime owned by the PHP thread and shut down per request; PHP Zvals
stay on their owning thread; asynchronous futures own all their Rust data;
notifications share one write descriptor and tolerate a full pipe; results are handed over
through a per-call slot, not a JoinHandle, so callers can resume after shutdown; an exception
thrown by suspend() (left pending by ext-php-rs) unregisters the caller; wakeup resumes
every ready caller even if one fails; shutdown can resume the completed callers first (drain) and
releases the rest on the PHP thread; reject use after fork.
No cryptographic work runs on Tokio's scheduler.

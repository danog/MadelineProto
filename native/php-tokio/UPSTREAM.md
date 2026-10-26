Based on danog/php-tokio bcbc4a4861b41f2050007b5b09e07abaad29f8f9 (Apache-2.0).
Only the Revolt bridge is included; the borrowing macros are deliberately unused.

Local changes: one explicitly configured Tokio scheduler thread, no blocking
pool use, runtime owned by the PHP thread and shut down per request; PHP Zvals
stay on their owning thread; asynchronous futures own all their Rust data;
notifications share one write descriptor and tolerate a full pipe; drain before closing; reject use after fork.
No cryptographic work runs on Tokio's scheduler.

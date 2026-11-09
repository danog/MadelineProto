<?php declare(strict_types=1);

/**
 * Prepended to every request of the webserver of WebContextTest (auto_prepend_file): records the start and the end of
 * the request in MADELINE_TEST_REQUEST_LOG, one locked append each.
 *
 * The log of the webserver can't tell: its workers write it at once, and a line may be written in several pieces, with
 * the line of another worker in between.
 */

$requestLog = getenv('MADELINE_TEST_REQUEST_LOG');
if (is_string($requestLog) && $requestLog !== '') {
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    file_put_contents($requestLog, "start $requestUri\n", FILE_APPEND | LOCK_EX);
    // After the shutdown functions of the request, registered after this one
    register_shutdown_function(static function () use ($requestLog, $requestUri): void {
        register_shutdown_function(static function () use ($requestLog, $requestUri): void {
            file_put_contents($requestLog, "end $requestUri\n", FILE_APPEND | LOCK_EX);
        });
    });
    unset($requestUri);
}
unset($requestLog);

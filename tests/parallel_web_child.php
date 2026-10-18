<?php

declare(strict_types=1);

// Requested by WebContextTest through the built-in webserver: runs amphp/parallel contexts and workers, which
// WebContextFactory starts with requests to the webserver, and prints what they returned as JSON.

use Amp\Parallel\Context\ContextException;
use Amp\Parallel\Worker\TaskFailureThrowable;
use danog\MadelineProto\Magic;
use danog\MadelineProto\Parallel\WebContextFactory;
use danog\MadelineProto\Test\WebContextTask;

use function Amp\Future\await;
use function Amp\Parallel\Context\contextFactory;
use function Amp\Parallel\Context\startContext;
use function Amp\Parallel\Worker\submit;
use function Amp\Parallel\Worker\workerPool;

require __DIR__.'/../vendor/autoload.php';

header('Content-Type: application/json');
// Without a session, MadelineProto logs to the output
ob_start();

$result = ['pid' => getmypid()];
try {
    Magic::start(light: true);
    $result['installed'] = contextFactory() instanceof WebContextFactory;

    $context = startContext(__DIR__.'/parallel_web_context.php');
    foreach ([1, null, 'two', [3]] as $value) {
        $context->send($value);
        $result['echoed'][] = $context->receive();
    }
    $context->send('exit');
    $result['joined'] = $context->join();

    try {
        startContext([__DIR__.'/parallel_web_context.php', 'throw'])->join();
    } catch (ContextException $e) {
        $result['failure'] = $e->getPrevious()?->getMessage();
    }

    $executions = [];
    foreach ([1, 2, 3] as $value) {
        $executions[] = submit(new WebContextTask($value));
    }
    foreach ($executions as $execution) {
        $channel = $execution->getChannel();
        $channel->send($channel->receive() * 10);
    }
    $result['tasks'] = await(array_map(static fn ($execution) => $execution->getFuture(), $executions));

    try {
        submit(new WebContextTask(4, throw: true))->await();
    } catch (TaskFailureThrowable $e) {
        $result['taskFailure'] = $e->getOriginalMessage();
    }

    workerPool()->shutdown();
    $result['shutdown'] = true;
} catch (Throwable $e) {
    $result['error'] = (string) $e;
}
ob_end_clean();
echo json_encode($result);

<?php

declare(strict_types=1);

// Requested by WebContextTest through the built-in webserver, with ?mode=:
// - contexts: runs amphp/parallel contexts and workers, which WebContextFactory starts with requests to the webserver
//   where processes can't be started (proc_open disabled), and prints what they returned as JSON;
// - process: starts a context where processes can be started;
// - userset: sets the context factory and the worker pool of amphp/parallel before MadelineProto starts;
// - selfstart: sends a request to the webserver with WebRunner::selfStart(), to ?mode=ping.

use Amp\Cancellation;
use Amp\Parallel\Context\Context;
use Amp\Parallel\Context\ContextException;
use Amp\Parallel\Context\ContextFactory;
use Amp\Parallel\Worker\ContextWorkerPool;
use Amp\Parallel\Worker\TaskFailureThrowable;
use danog\MadelineProto\Ipc\Runner\WebRunner;
use danog\MadelineProto\Magic;
use danog\MadelineProto\Parallel\Internal\FallbackContextFactory;
use danog\MadelineProto\Parallel\Internal\OnDemandWorkerFactory;
use danog\MadelineProto\Parallel\WebContextFactory;
use danog\MadelineProto\Test\WebContextTask;

use function Amp\delay;
use function Amp\Future\await;
use function Amp\Parallel\Context\contextFactory;
use function Amp\Parallel\Context\startContext;
use function Amp\Parallel\Worker\submit;
use function Amp\Parallel\Worker\workerPool;

require __DIR__.'/../vendor/autoload.php';

$mode = $_GET['mode'] ?? 'contexts';
if ($mode === 'ping') {
    echo 'pong';
    return;
}

/** The factory of the workers of a ContextWorkerPool */
$getWorkerFactory = static fn (ContextWorkerPool $pool): ?object => (new ReflectionProperty(ContextWorkerPool::class, 'factory'))->getValue($pool);

/** The requests of web contexts the webserver logged as done */
$countDoneContexts = static fn (): int => substr_count(
    file_get_contents(sys_get_temp_dir().'/server.log'),
    ']: GET /src/Ipc/Runner/entry.php?argv%5B0%5D=madeline-parallel&',
);

$modes = [
    'selfstart' => static function (array &$result): void {
        Magic::start(light: true);
        $start = microtime(true);
        WebRunner::selfStart('/tests/parallel_web_child.php?mode=ping');
        $result['selfStart'] = microtime(true) - $start;
    },
    'process' => static function (array &$result): void {
        Magic::start(light: true);
        $context = startContext(__DIR__.'/parallel_web_context.php');
        $context->send('exit');
        $result['joined'] = $context->join();
        $result['web'] = contextFactory()->usesWeb();
    },
    'userset' => static function (array &$result): void {
        $factory = contextFactory(new WebContextFactory());
        $pool = workerPool(new ContextWorkerPool(2));
        Magic::start(light: true);
        $result['factoryKept'] = contextFactory() === $factory;
        $result['poolKept'] = workerPool() === $pool;
    },
    'contexts' => static function (array &$result) use ($getWorkerFactory, $countDoneContexts): void {
        Magic::start(light: true);
        $pool = workerPool();
        $result['installed'] = contextFactory() instanceof FallbackContextFactory
            && $pool instanceof ContextWorkerPool
            && $pool->getWorkerLimit() === WebContextFactory::DEFAULT_WORKER_LIMIT
            && $getWorkerFactory($pool) instanceof OnDemandWorkerFactory;

        $context = startContext(__DIR__.'/parallel_web_context.php');
        $result['web'] = contextFactory()->usesWeb();
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

        // The pool installed by MadelineProto, with workers idle for half a second stopped, and a factory counting
        // the contexts started
        $factory = new class(contextFactory()) implements ContextFactory {
            public int $started = 2;
            public function __construct(private readonly ContextFactory $factory)
            {
            }
            public function start(string|array $script, ?Cancellation $cancellation = null): Context
            {
                $this->started++;
                return $this->factory->start($script, $cancellation);
            }
        };
        contextFactory($factory);
        workerPool(new ContextWorkerPool(WebContextFactory::DEFAULT_WORKER_LIMIT, new OnDemandWorkerFactory(0.5)));
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
        // The idle workers stop, ending the requests of their contexts: the next task starts a new one
        $result['started'] = $factory->started;
        $deadline = microtime(true) + 10;
        do {
            delay(0.1);
            $result['done'] = $countDoneContexts();
        } while ($result['done'] < $factory->started && microtime(true) < $deadline);
        $result['afterIdle'] = submit(new WebContextTask(5, talk: false))->await();

        workerPool()->shutdown();
        $result['shutdown'] = true;
    },
];

header('Content-Type: application/json');
// Without a session, MadelineProto logs to the output
ob_start();

$result = ['pid' => getmypid()];
try {
    $modes[$mode]($result);
} catch (Throwable $e) {
    $result['error'] = (string) $e;
}
ob_end_clean();
echo json_encode($result);

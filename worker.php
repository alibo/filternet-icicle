<?php

require __DIR__ . '/vendor/autoload.php';

use Filternet\Icicle\Logger;
use Filternet\Icicle\Results\Dns;
use Filternet\Icicle\Results\Http;
use Filternet\Icicle\Results\Result;
use Filternet\Icicle\Results\Sni;
use Filternet\Icicle\Site;
use Filternet\Icicle\Stats;
use Filternet\Icicle\SyncLogger;
use Filternet\Icicle\Tasks\DnsTask;
use Filternet\Icicle\Tasks\HttpTask;
use Filternet\Icicle\Tasks\SniTask;
use Icicle\Awaitable;
use Icicle\Concurrent\Worker\DefaultPool;
use Icicle\Concurrent\Worker\Worker;
use Icicle\Concurrent\Worker\WorkerFactory;
use Icicle\Concurrent\Worker\WorkerFork;
use Icicle\Coroutine;
use Icicle\Loop;
use League\CLImate\CLImate;
use League\CLImate\TerminalObject\Dynamic\Progress;
use League\Csv\Reader;

$csv = Reader::createFromPath(__DIR__ . DIRECTORY_SEPARATOR . 'top-1m.csv');

$limit = $argv[1] ?? 10;
$sites = $csv->setLimit($limit)->fetchAll();

$climate = new CLImate;

Coroutine\create(function () use ($sites, $climate) {
    $pool = new DefaultPool(min(count($sites), 10), 40, new class implements WorkerFactory
    {
        public function create(): Worker
        {
            return new WorkerFork();
        }
    });

    $pool->start();
    $coroutines = [];
    $stats = new Stats;
    $logger = new SyncLogger(__DIR__ . '/logs');

    /** @var Progress $progress */
    $progress = $climate->progress()->total(count($sites) * 2);

    foreach ($sites as $record) {
        list($rank, $domain) = $record;
        $site = new Site($domain, (int)$rank);

        $coroutines[] = Coroutine\create(function () use ($pool, $site, $stats, $progress, $logger) {

            $task = new DnsTask($site);
            $task->logger($logger);

            /** @var Dns $dnsResult */
            $dnsResult = yield from $pool->enqueue($task);

            $stats->addDns($dnsResult);

            $progress->advance(1, colorize($dnsResult, "Done: (DNS) {$site->domain()} [{$site->rank()}]"));
        });

        $coroutines[] = Coroutine\create(function () use ($pool, $site, $stats, $progress, $logger) {

            $task = new HttpTask($site);
            $task->logger($logger);

            /** @var Http $httpResult */
            $httpResult = yield from $pool->enqueue($task);

            $stats->addHttp($httpResult);

            $progress->advance(1, colorize($httpResult, "Done: (HTTP) {$site->domain()} [{$site->rank()}]"));
        });

//        $coroutines[] = Coroutine\create(function () use ($pool, $site, $stats, $progress, $logger) {
//
//            $task = new SniTask($site, 'bing.com');
//            $task->logger($logger);
//
//            /** @var Sni $sniResult */
//            $sniResult = yield from $pool->enqueue($task);
//
//            $stats->addSni($sniResult);
//            $progress->advance(1, colorize($sniResult, "Done: (SNI) {$site->domain()} [{$site->rank()}]"));
//        });

    }

    $stats->startWatch();
    yield Awaitable\all($coroutines);

    $climate->clear();
    $climate->table([
        $stats->getHttp(),
        $stats->getDns(),
        $stats->getSni()
    ]);
    $climate->info('elapsed time: ' . $stats->elapsedTime());

    return yield from $pool->shutdown();
})->done();

Loop\run();

function colorize(Result $result, $text)
{
    $text .= ' - time: ' . $result->getElapsedTime();
    if ($result->isUnknown()) {
        return "<yellow>$text</yellow>";
    }

    if ($result->isBlocked()) {
        return "<red>$text</red>";
    }

    return "<green>$text</green>";
}
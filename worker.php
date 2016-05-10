<?php

require __DIR__ . '/vendor/autoload.php';

use Filternet\Icicle\Site;
use Filternet\Icicle\Tasks\DnsTask;
use Filternet\Icicle\Tasks\HttpTask;
use Filternet\Icicle\Tasks\SniTask;
use Icicle\Awaitable;
use Icicle\Concurrent\Worker\DefaultPool;
use Icicle\Coroutine;
use Icicle\Loop;

$sites = [
    new Site('google.com', 1),
    new Site('youtube.com', 2)
];

Coroutine\create(function () use ($sites) {
    $pool = new DefaultPool();
    $pool->start();
    $coroutines = [];

    foreach ($sites as $site) {
        $coroutines[] = Coroutine\create(function () use ($pool, $site) {
            echo json_encode([
                yield from $pool->enqueue(new DnsTask($site)),
                yield from $pool->enqueue(new SniTask($site)),
                yield from $pool->enqueue(new HttpTask($site))
            ], JSON_PRETTY_PRINT);
        });
    }

    yield Awaitable\all($coroutines);

    return yield from $pool->shutdown();
})->done();

Loop\periodic(0.1, function () {
    printf(".\n");
})->unreference();

Loop\run();

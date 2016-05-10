<?php

require __DIR__ . '/vendor/autoload.php';
use Icicle\Coroutine;
use Icicle\Dns;
use Icicle\Loop;

$domain = $argv[1] ?? 'google.com';

/** @var \Icicle\Coroutine\Coroutine|\Icicle\Awaitable\Awaitable $coroutine */
$coroutine = Coroutine\create(function () use ($domain) {
    echo "Connecting to google.com...\n";
    /** @var \Icicle\Socket\Socket $socket */
    $socket = yield from Dns\connect('google.com', 443, ['name' => $domain]);
    yield from $socket->enableCrypto(STREAM_CRYPTO_METHOD_TLS_CLIENT);

    echo "\nDone.\n";
});

$coroutine
    ->capture(function (Exception $e) {
        echo "Exception: {$e->getMessage()}\n";
    }
    )->done();
Loop\run();
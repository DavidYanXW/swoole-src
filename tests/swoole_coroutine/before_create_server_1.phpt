--TEST--
swoole_http_server: http redirect
--SKIPIF--
<?php require __DIR__ . '/../include/skipif.inc'; ?>
--FILE--
<?php
require __DIR__ . '/../include/bootstrap.php';

define('SECRET', RandStr::getBytes(rand(1024, 8192)));

$pm = new ProcessManager;
$pm->parentFunc = function ($pid) use ($pm) {
    $data = curlGet("http://127.0.0.1:{$pm->getFreePort()}/");
    assert(!empty($data));
    assert($data == SECRET);
    $pm->kill();
};

$pm->childFunc = function () use ($pm) {
    go(function () use ($pm) {
        co::sleep(0.1);

        $http = new swoole_http_server('127.0.0.1', $pm->getFreePort());

        $http->set([
            'log_file' => '/dev/null',
            "worker_num" => 1,
        ]);

        $http->on("WorkerStart", function ($serv, $wid) use ($pm) {
            $pm->wakeup();
        });

        $http->on("request", function ($request, swoole_http_response $response) {
            $response->end(SECRET);
        });

        $http->start();
    });

    swoole_event_wait();
};

$pm->childFirst();
$pm->run();



?>
--EXPECT--

<?php
require __DIR__ . '/vendor/autoload.php';
$pusher_config = [
    'app_id' => '1927783',
    'key' => '561b69476711bf54f56f',
    'secret' => '10b81fe10e9b7efc75ff',
    'cluster' => 'ap1',
    'useTLS' => true
];

$pusher = new Pusher\Pusher(
    $pusher_config['key'],
    $pusher_config['secret'],
    $pusher_config['app_id'],
    [
        'cluster' => $pusher_config['cluster'],
        'useTLS' => $pusher_config['useTLS']
    ]
);

?>
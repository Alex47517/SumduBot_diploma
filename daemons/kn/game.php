<?php
set_time_limit(0); //Знімаємо обмеження по часу на виконання скрипту
require __DIR__.'/../../config/start.php';
require __DIR__.'/../../config/loader.php';
require __DIR__.'/kn.php';
use api\{chat as chat, Bot as Bot, stats as stats};
pcntl_fork();
$bot = new Bot($bot_token);
$game = R::load('kn', $argv[1]);
$chat = new chat($game['chat_id']);

$lock_file_name = '/tmp/kn_game_'.$game['id'].'.lock';
$lock_file = fopen($lock_file_name, 'c+');

if (!$game['storage']) {
    $game->storage = json_encode([
        //0
        [
            ['value' => '.', 'date' => date('U')],
            ['value' => '.', 'date' => date('U')],
            ['value' => '.', 'date' => date('U')]
        ],
        //1
        [
            ['value' => '.', 'date' => date('U')],
            ['value' => '.', 'date' => date('U')],
            ['value' => '.', 'date' => date('U')]
        ],
        //2
        [
            ['value' => '.', 'date' => date('U')],
            ['value' => '.', 'date' => date('U')],
            ['value' => '.', 'date' => date('U')]
        ]
    ]);
    R::store($game);
    $kn = new kn();
    $kn->load($game['id']);
    $kn::reloadTable();
}

//timer
$last_update = $game['last_update'];
$time = 30;
while ($time) {
    if ($time % 5 == 0) {
        $game = R::getAll( 'SELECT * FROM kn WHERE id = :id', [ ':id' => $game['id'] ] )[0];
        if ($game['last_update'] != $last_update or $game['status'] != 'playing') die();
    }
    sleep(1);
    $time--;
}
$game = R::getAll( 'SELECT * FROM kn WHERE id = :id', [ ':id' => $game['id'] ] )[0];
if ($game['last_update'] != $last_update or $game['status'] != 'playing') die();
else {
    $kn = new kn();
    $kn->load($game['id']);
    if ($game->moves%2==0 or $game->moves==0) $val = '0'; else $val = 'X';
    //$kn->win($val, 'Технічний програш за AFK');
    die();
}

flock($lock_file, LOCK_UN);
fclose($lock_file);
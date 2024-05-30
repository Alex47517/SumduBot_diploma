<?php
set_time_limit(0); //Знімаємо обмеження по часу на виконання скрипту
require __DIR__.'/../../config/start.php';
require __DIR__.'/../../config/loader.php';
use api\{chat as chat, Bot as Bot, stats as stats};
pcntl_fork();
$bot = new Bot($bot_token);
function updateTimer() {
    global $time, $chat, $game;
    $keyboard[0][0]['text'] = 'Приєднатися';
    $keyboard[0][0]['callback_data'] = 'kn_connect_'.$game['id'];
    $chat->editMessageText('<b>🤞 Хрестики - нолики 👌</b>

Ставка: <b>'.$game['bet'].'💰</b>
Залишилось: <b>'.$time.' сек. ⏳</b>

<em>Очікування супротивника</em>', ['inline_keyboard' => $keyboard], $game['msg_id']);
}
$game = R::load('kn', $argv[1]);

$lock_file_name = '/tmp/kn_loader_'.$game['id'].'.lock';
$lock_file = fopen($lock_file_name, 'c+');

// Пытаемся установить эксклюзивную блокировку
if (!flock($lock_file, LOCK_EX | LOCK_NB)) {
    echo "Скрипт уже выполняется.";
    fclose($lock_file);
    exit;
}

$chat = new chat($game['chat_id']);
$player1 = new User();
$player1->loadByID($game->player1);
$player2 = new User();
$player2->loadByID($game->player2);
$time = 60;
updateTimer();
while ($time) {
    sleep(1);
    $time--;
    echo $time.PHP_EOL;
    if ($time % 10 == 0 or $time == 0) {
        $game = R::getAll( 'SELECT * FROM kn WHERE id = :id', [ ':id' => $game['id'] ] )[0];
        if ($game['player2']) {
            flock($lock_file, LOCK_UN);
            fclose($lock_file);
            die();
        }
        updateTimer();
    }
}
R::trash($game);
$chat->editMessageText('<b>🤞 Хрестики - нолики 👌</b>

Час вичерпано 😒', null, $game['msg_id']);
$player1->addBal($game['bet']);
$player1->update('display');
flock($lock_file, LOCK_UN);
fclose($lock_file);
die();
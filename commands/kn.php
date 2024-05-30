<?php
//
// Command: Хрестики-нолики #
// Text: !хн !кн /tictactoe #
// Callback: kn #
// Display: kn #
// Info: Запускає гру "Хрестики-нолики" (Можна ставити лише 3 своїх знаки) #
// Syntax: !хн [ставка*] #
// Args: 0 #
// Rank: USER #
//
require __DIR__.'/../lib/Process.php';
require __DIR__.'/../daemons/kn/kn.php';
use api\{update as update, Log as Log, AutoClean as AutoClean};
$text = ['!кн', '!хн', '/kn'];
function checkBal($sum) {
    global $user;
    if ($user->user['balance'] < $sum) {
        custom_error('Ти не можеш приймати участь у цій грі', 'Необхідно: <b>'.$sum.'💰</b>
У тебе: <b>'.$user->user['balance'].'💰</b>');
    } else {
        $user->addBal($sum*-1);
        return true;
    }
}
if (in_array($cmd[0], $text)) {
    if ($user->user['display'] == 'kn') {
        custom_error('Ви вже у грі', 'Ви автоматично програєте, якщо припините гру', true);
    }
    if (!$cmd[1]) $cmd[1] = 0;
    checkBal(round($cmd[1]));
    $game = R::dispense('kn');
    $game->status = 'waiting';
    $game->bet = round($cmd[1]);
    $game->player1 = $user->user['id'];
    $game->last_update = date('U');
    $game->start_time = date('U');
    $game->chat_id = update::$chat['id'];
    R::store($game);
    //$user->update('display', 'kn');
    $response = $chat->sendMessage('<b>🤞 Хрестики - нолики 👌</b>

Ставка: <b>'.$game['bet'].'💰</b>

<em>Завантаження...</em>');
    $id = $response->result->message_id;
    $command = 'php -f ' . __DIR__ . '/../daemons/kn/loader.php ' . $game['id'] . '';
    //$chat->sendMessage($command); die();
    $process = new Process($command);
    $processId = $process->getPid();
    $game->msg_id = $id;
    $game->pid = $processId;
    R::store($game);
}
if ($ex_callback[1] == 'connect') {
    $game = R::load('kn', $ex_callback[2]);
    if ($game['status'] == 'waiting') {
        if ($game['player1'] == $user->user['id']) {
            $chat->answerCallbackQuery('💢 Ви не можете грати самі проти себе', true); die();
        }
        checkBal($game['bet']);
        $player1 = new User();
        $player1->LoadById($game['player1']);
        //$user->update('display', 'kn');
        $chat->editMessageText('<b>🤞 Хрестики - нолики 👌</b>

❌ <code>'.$player1->user['nick'].'</code> VS <code>'.$user->user['nick'].'</code> ⭕

<em>Завантаження...</em>', null, $game['msg_id']);
        $game->status = 'playing';
        $command = 'php -f ' . __DIR__ . '/../daemons/kn/game.php ' . $game['id'];
        //$chat->sendMessage($command);
        $process = new Process($command);
        $processId = $process->getPid();
        $game->player2 = $user->user['id'];
        $game->pid = $processId;
        R::store($game);
        die();
    }
}
if ($ex_callback[1] == 'move') {
    //kn_move_{gameID}_{line}_{col}_{blocked}
    if ($ex_callback[5] == 1) {
        $chat->answerCallbackQuery('💢 Ця клітинка вже зайнята', true); die();
    }
    $game = R::load('kn', $ex_callback[2]);
    $kn = new kn();
    $kn::load($game['id']);
    if ($game['player1'] == $user->user['id'] or $game['player2'] == $user->user['id']) {
        if ((($game->moves%2==0 or $game->moves==0) && $game['player1'] == $user->user['id']) or ($game->moves%2!=0 && $game['player2'] == $user->user['id'])) {
            $kn::move($ex_callback[3], $ex_callback[4]);
            //$chat->sendMessage('php -f ' . __DIR__ . '/../daemons/kn/game.php ' . $game['id']);
            //$process = new Process('php -f ' . __DIR__ . '/../daemons/kn/game.php ' . $game['id']);
        } else {
            $chat->answerCallbackQuery('💢 Зараз не твій хід', true); die();
        }
    } else {
        $chat->answerCallbackQuery('💢 Ви не приймаєте участь у цій грі', true); die();
    }
}
if ($ex_callback[1] == 'ended') {
    $chat->answerCallbackQuery('💢 Ця гра вже завершена', true); die();
}
<?php
//
// Command: Баланс #
// Text: !баланс /balance #
// Info: Виводить інформацію про баланс #
// Syntax: !баланс #
// Args: 0 #
// Rank: USER #
//
if ($cmd[1]) {
Permissions::Admin($user->user);
Permissions::Owner($user->user);
$s_user = new User();
if ($cmd[1] == '$') {
    $nick = $cmd[2];
    $sum = $cmd[3];
} else {
    $nick = $cmd[1];
    $sum = round($cmd[2]);
}
if ($s_user->loadByNick($nick)) {
if ($cmd[1] == '$') {
    $s_user->update('balance_usd', ($s_user->user['balance_usd']+$sum));
    $chat->sendMessage('✅ Користувачу <a href="tg://user?id='.$s_user->user['tg_id'].'">'.$s_user->user['nick'].'</a> видано <b>'.$sum.'💵</b>');
}
else {
    $s_user->addBal($sum);
    $chat->sendMessage('✅ Користувачу <a href="tg://user?id='.$s_user->user['tg_id'].'">'.$s_user->user['nick'].'</a> видано <b>'.$sum.'💰</b>');
}
} else custom_error('Помилка 404', 'Користувач не знайдений');
} else {
    if ($user->user['balance_usd'] > 0) {
        $usd = '
<b>'.$user->user['balance_usd'].' 💵</b>';
    } else $usd = null;
    $achievements_count = R::count('userachievements', '`user_id` = ?', [$user->user['id']]);
    if ($achievements_count) {
        $text = '

Досягнень: <b>'.$achievements_count.'🏆 </b>';
    }
$chat->sendMessage('🕯 <b>Твій баланс:</b>
<b>'.$user->user['balance'].'💰</b>
<b>'.$user->user['diamonds'].'💎</b>'.$usd.''.$text);
}
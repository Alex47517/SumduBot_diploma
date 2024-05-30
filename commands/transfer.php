<?php
//
// Command: Переказ #
// Text: !переказ !перевод /transfer /pay #
// Callback: transfer #
// Info: Переказ коштів між користувачами, коміссія - 20% #
// Syntax: !переказ [нік/відповідь на повідомлення*] [сума] #
// Args: 1 #
// Rank: USER #
//
use api\{update as update, chat as chat};
if ($ex_callback[0] == 'transfer') {
    $transfer = R::load('transfers', $ex_callback[2]);
    if ($transfer['id']) {
        if ($user->user['id'] == $transfer['from_user']) {
            if ($ex_callback[1] == 'confirm') {
                $s_user = R::load('users', $transfer['to_user']);
                if ($s_user['id']) {
                    if ($transfer['type'] == 'usd') {
                        if ($user->user['balance_usd'] < ($transfer['sum'])) {
                            $chat->editMessageText('❌ Недостатньо коштів!

Потрібна сума: '.$transfer['sum'].' 💵
Ваш баланс: '.$user->user['balance_usd'].' 💵', null, update::$btn_id);
                            die();
                        } else {
                            $transfer->confirmed = 1;
                            R::store($transfer);
                            $s_user->balance_usd += $transfer['sum'];
                            R::store($s_user);
                            $user->update('balance_usd', ($user->user['balance_usd']-($transfer['sum'])));
                            $chat->editMessageText('✅ Переказ успішно підтверджено!
                            
Ви переказали <b>'.$transfer['sum'].' 💵</b> користувачу <a href="tg://user?id='.$s_user['tg_id'].'">'.$s_user['nick'].'</a>', null, update::$btn_id);
                            $pm = new chat($s_user['user_id']);
                            $pm->sendMessage('🎁 Користувач <a href="tg://user?id=' . $user->user['tg_id'] . '">' . $user->user['nick'] . '</a> переказав вам <b>'.$transfer['sum'].' 💵</b>

<em>Це донатна валюта, яку можна витратити на платні функції, наприклад !gpt4 або /image</em>');
                            die();
                        }
                    }
                } else {
                    $chat->editMessageText('❌ Користувач не знайдений', null, update::$btn_id); die();
                }
            } else {
                R::trash($transfer);
                $chat->deleteMessage(update::$btn_id);
                die();
            }
        } else {
            $chat->answerCallbackQuery('💢 Це переказ іншого користувача, ви не можете виконувати ніякі дії з ним', true); die();
        }
    } else { $chat->deleteMessage(update::$btn_id); custom_error('Помилка 404', 'Переказ не знайдено'); }
}
if ($cmd[1] == '$') {
    //=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
    // Переказ $
    //=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
    if (update::$reply_user_id) {
        $col = 'tg_id';
        $find = update::$reply_user_id;
        $sum = $cmd[2];
    } else {
        $col = 'nick';
        $find = $cmd[2];
        $sum = $cmd[3];
    }
    if (!is_numeric($sum)) custom_error('Помилка', 'Сума має бути числом');
    $sum = round($sum, 3);
    if ($sum < 0.1) custom_error('Помилка', 'Мінімальна сума переказу: 0.1 💵');
    $s_user = R::findOne('users', $col.' = ?', [$find]);
    if ($s_user['id']) {
         if ($user->user['balance_usd'] < ($sum)) custom_error('Недостатньо коштів', 'Потрібна сума: '.$sum.' 💵
Ваш баланс: '.$user->user['balance_usd'].' 💵');
        if ($user->user['id'] == $s_user['id']) {
            $user->addBal(-100);
            $chat->sendMessage('✅ Користувач <a href="tg://user?id=' . $s_user['tg_id'] . '">' . $s_user['nick'] . '</a> переказав <b>' . $sum . ' 💵</b> сам собі :/

Коміссія склала: <b>100 💰</b>');
            die();
        } else {
            $transfer = R::dispense('transfers');
            $transfer->from_user = $user->user['id'];
            $transfer->to_user = $s_user['id'];
            $transfer->sum = $sum;
            $transfer->type = 'usd';
            $transfer->confirmed = 0;
            $transfer->date = date('U');
            R::store($transfer);
            $keyboard[0][0]['text'] = '❌ Ні';
            $keyboard[0][0]['callback_data'] = 'transfer_remove_' . $transfer['id'];
            $keyboard[0][1]['text'] = '✅ Так';
            $keyboard[0][1]['callback_data'] = 'transfer_confirm_' . $transfer['id'];
            $chat->sendMessage('<b>‼ УВАГА</b>

Ви дійсно бажаєте переказати <b>' . $sum . ' 💵</b> користувачу <code>' . $s_user['nick'] . '</code>?', null, ['inline_keyboard' => $keyboard]);
        }
    } else custom_error('Помилка 404', 'Користувач не знайдений');
} else {
    //=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
    // Переказ монет
    //=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
if (update::$reply_user_id) {
    $col = 'tg_id';
    $find = update::$reply_user_id;
    $sum = $cmd[1];
} else {
    $col = 'nick';
    $find = $cmd[1];
    $sum = $cmd[2];
}
if (!is_numeric($sum)) custom_error('Помилка', 'Сума має бути числом');
$sum = floor($sum);
$comission = floor($sum*0.2);
$s_user = R::findOne('users', $col.' = ?', [$find]);
if ($s_user) {
    if ($sum < 20) custom_error('Помилка', 'Мінімальна сума переказу: 20💰');
    if ($user->user['balance'] < ($sum+$comission)) custom_error('Недостатньо коштів', 'Потрібна сума: '.$sum.'+'.$comission.'💰 (комісія)
Ваш баланс: '.$user->user['balance'].'💰');
    $s_user->balance += $sum;
    R::store($s_user);
    $user->update('balance', ($user->user['balance']-($sum+$comission)));
    Bank::add($comission);
    if ($user->user['id'] == $s_user['id']) {
        $chat->sendMessage('✅ Користувач <a href="tg://user?id='.$s_user['tg_id'].'">'.$s_user['nick'].'</a> переказав <b>'.$sum.'💰</b> сам собі :/

Коміссія склала: <b>'.($comission).'💰</b>'); die();
    }
    if ($s_user['nick'] == 'Sumdu_bot') {
        if ($sum >= 5000 && !R::findOne('userachievements', 'user_id = ? AND achievement_id = ?', [$user->user['id'], 15])['id']) {
            $user->getAchievement(15);
            $chat->sendMessage('💵 Вітаємо <a href="tg://user?id='.$user->user['tg_id'].'">'.$user->user['nick'].'</a> з отриманням нового досягнення!');
        }
        $chat->sendMessage('✅ Ви переказали <b>'.$sum.'💰</b> користувачу <a href="tg://user?id='.$s_user['tg_id'].'">'.$s_user['nick'].'</a>

Коміссія склала: <b>'.($comission).'💰</b>
Коментар: <b>На благодійність та підтримку економіки</b>'); die();
    }
    $chat->sendMessage('✅ Ви переказали <b>'.$sum.'💰</b> користувачу <a href="tg://user?id='.$s_user['tg_id'].'">'.$s_user['nick'].'</a>

Коміссія склала: <b>'.($comission).'💰</b>');
} else custom_error('Помилка 404', 'Користувач не знайдений');
}
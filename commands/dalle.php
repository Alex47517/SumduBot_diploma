<?php
//
// Command: DALL-E #
// Text: !зображення !картинка !dall_e /dall_e /image #
// Callback: dalle #
// Display: dalle #
// Info: Генерує зображення #
// Syntax: !зображення [Опис] #
// Args: 1 #
// Rank: USER #
//
require __DIR__.'/../lib/Process.php';

use api\{update as update, Log as Log, AutoClean as AutoClean};
$starter = ['!зображення', '!dall_e', '/dall_e', '/image', '!картинка'];
if (in_array($cmd[0], $starter)) {
    AutoClean::save();
    if ($cmd[1]) {
        $message = str_replace($cmd[0].' ', '', $msg);
    }
    if ($user->user['balance_usd'] < 0.04) custom_error('Недостатньо коштів', 'Необхідно: 0.04 💵
У тебе: '.$user->user['balance_usd'].' 💵');
    $user->LocalStorageClear();
    $user->LocalStorageSet('promt', $message);
    $user->LocalStorageSet('chat', $chat->chat_id);
    $user->update('balance_usd', ($user->user['balance_usd']-0.04));
    $process = new Process('php -f ' . __DIR__ . '/../daemons/dalle.php ' . $user->user['id']);
    die();
}
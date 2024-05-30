<?php
//
// Command: ChatGPT #
// Text: !gpt4 /gpt4 #
// Callback: gpt4 #
// Display: gpt4 #
// Info: ChatGPT 4 #
// Syntax: !gpt4 [Повідомлення] #
// Args: 1 #
// Rank: USER #
//
require __DIR__.'/../lib/Process.php';

use Orhanerday\OpenAi\OpenAi;
use api\{update as update, Log as Log, AutoClean as AutoClean};
$starter = ['!gpt4', '/gpt4'];
function roundUp($number, $precision = 3) {
    $factor = pow(10, $precision);
    return ceil($number * $factor) / $factor;
}
$chat->sendMessage('CMD[0]: '.$cmd[0]);
if (in_array($cmd[0], $starter)) {
    AutoClean::save();
    if ($cmd[1]) {
        $message = str_replace($cmd[0].' ', '', $msg);
    }
    if ($user->user['balance_usd'] < 0.04) custom_error('Недостатньо коштів', 'Необхідно: 0.01 💵
У тебе: '.$user->user['balance_usd'].' 💵');
    $fp = fopen('/tmp/lockfile1', 'w+');
    if (flock($fp, LOCK_EX | LOCK_NB)) {
    $result = $chat->sendMessage('⏳ <b>ChatGPT</b> - Генерація відповіді...');
    $edit = $result->result->message_id;
//$command = 'echo "' . $nodeJS_password . '" | su -l ' . $nodeJS_username . ' -c "node ' . __DIR__ . '/../NodeJS/gpt.mjs \"'.str_replace('"', '\'', $message).'\" '.update::$chat['id'].' '.update::$message_id.' '.$edit.'"';
//$process = new Process($command);

    require_once '/home/alex/websites/bot.sumdubot.pp.ua/test/vendor/autoload.php';
    $openai = new OpenAI(OPEN_AI_API_KEY);
    $complete = $openai->chat([
        'model' => 'gpt-4o',
        'messages' => [
            [
                "role" => "system",
                "content" => "Ти ChatGPT 4, працюєш у SumduBot (Бот Сумского Державного Університету) Тобі пише людина з ніком ".update::$from['username']."."
            ],
            [
                "role" => "user",
                "content" => $message,
            ]
        ],
        'temperature' => 1.0,
        'max_tokens' => 2000,
        'frequency_penalty' => 0,
        'presence_penalty' => 0,
    ]);
    //$text = end($complete)['text'];
    //$chat->sendMessage(var_export(json_decode($complete, true), true)); die();
    $prompt_tokens = json_decode($complete, true)['usage']['prompt_tokens'];
    $completion_tokens = json_decode($complete, true)['usage']['completion_tokens'];
    $result_price = roundUp(($prompt_tokens*0.000005)+($completion_tokens*0.000015), 3);
    $user->update('balance_usd', ($user->user['balance_usd']-$result_price));
    $chat->sendMessage('<b>❗ З балансу <a href="tg://user?id='.$user->user['tg_id'].'">'.$user->user['nick'].'</a> списано '.$result_price.' 💵</b>

Input: <b>'.$prompt_tokens.' tokens</b>
Output: <b>'.$completion_tokens.' tokens</b>

'.$prompt_tokens.' * 0.000005 + '.$completion_tokens.' * 0.000015 = '.$result_price);
    $text = json_decode($complete, true)['choices'][0]['message']['content'];
    if (!$text) {
        $file = fopen('log.txt', 'w+');
        fwrite($file, var_export($openai->getCURLInfo(),true));
        fclose($file);
        $result = $chat->editMessageText('♨ Виникла помилка при генерації
<code>'.var_export(json_decode($complete, true),true).'</code>', null, $edit);
    }
    $chat_gpt_user = R::dispense('chatgpt');
    $chat_gpt_user->user = $user->user['id'];
    $chat_gpt_user->message_id = update::$message_id;
    $chat_gpt_user->message_text = $message;
    $chat_gpt_user->role = 'user';
    $chat_gpt_user->date = date('U');
//    if (update::$reply['message_id']) {
//        $id = R::findOne('chatgpt', 'message_id = ?', [update::$reply['message_id']])['id'];
//        if ($id) {
//            $chat_gpt_user->father_id = $id;
//        }
//    }
    R::store($chat_gpt_user);
    $chat_gpt = R::dispense('chatgpt');
    $chat_gpt->user = $user->user['id'];
    $chat_gpt->message_id = $edit;
    $chat_gpt->message_text = $text;
    $chat_gpt->role = 'assistant';
    $chat_gpt->date = date('U');
    $chat_gpt->father_id = $chat_gpt_user['id'];
    R::store($chat_gpt);
    //filters
    $text = str_replace('Путин', 'Хуйло', $text);
    $text = str_replace('Путін', 'Хуйло', $text);
    $text = str_replace('Росія', 'параша (росія)', $text);
    $text = str_replace('Россия', 'параша (россия)', $text);
    $text = str_replace('России', 'параши (россии)', $text);
    $text = str_replace('Росії', 'параши (росії)', $text);
    $text = str_replace('Російська Федерація', 'блядушня (я про росію)', $text);
    $text = str_replace('Российская Федерация', 'блядушня (я про россию)', $text);
//    $text = str_replace('<', '&lt;', $text);
//    $text = str_replace('>', '&gt;', $text);
        $text .= '
---
_Для продовження бесіди відповідайте на це повідомлення_';
    $result = $chat->editMessageText($text, null, $edit, 'Markdown');
    //echo $text;
//$processId = $process->getPid();
//Log::admin('ChatGPT', $command);
    } else {
        echo 'Скрипт уже запущен';
    }

    fclose($fp);
}
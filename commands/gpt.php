<?php
//
// Command: ChatGPT #
// Text: !gpt /gpt #
// Callback: gpt #
// Display: gpt #
// Info: Запускає гру "UNO" #
// Syntax: !gpt [Повідомлення] #
// Args: 0 #
// Rank: USER #
//
require __DIR__.'/../lib/Process.php';

use Orhanerday\OpenAi\OpenAi;
use api\{update as update, Log as Log, AutoClean as AutoClean};
$starter = ['!gpt', '/gpt'];
if (in_array($cmd[0], $starter)) {
AutoClean::save();
if ($cmd[1]) {
    $message = str_replace($cmd[0].' ', '', $msg);
}
$result = $chat->sendMessage('⏳ <b>ChatGPT</b> - Генерація відповіді...');
$edit = $result->result->message_id;;
require_once '/home/alex/websites/bot.sumdubot.pp.ua/test/vendor/autoload.php';
    $openai = new OpenAI(OPENAI_API_KEY);

    $complete = $openai->chat([
        'model' => 'gpt-3.5-turbo',
        'messages' => [
            [
                "role" => "system",
                "content" => "Ты chatGPT, работаешь внутри SumduBot (Бот Сумского государственного университета) Тебе пишет человек с именем ".update::$from['username']."."
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
    $text = json_decode($complete, true)['choices'][0]['message']['content'];
    if (!$text) {
        $file = fopen('log.txt', 'w+');
        fwrite($file, var_export($openai->getCURLInfo(),true));
        fclose($file);
        $result = $chat->editMessageText('♨ Виникла помилка при генерації
<code>'.var_export(json_decode($complete, true),true).'</code>', null, $edit);
    }
    //filters
    $text = str_replace('Путин', 'Хуйло', $text);
    $text = str_replace('Путін', 'Хуйло', $text);
    $text = str_replace('Росія', 'параша (росія)', $text);
    $text = str_replace('Россия', 'параша (россия)', $text);
    $text = str_replace('России', 'параши (россии)', $text);
    $text = str_replace('Росії', 'параши (росії)', $text);
    $text = str_replace('Російська Федерація', 'блядушня (я про росію)', $text);
    $text = str_replace('Российская Федерация', 'блядушня (я про россию)', $text);
    $result = $chat->editMessageText($text, null, $edit);
}

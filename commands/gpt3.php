<?php
//
// Command: ChatGPT #
// Text: !gpt3 /gpt3 #
// Callback: gpt3 #
// Display: gpt3 #
// Info: Виводить відвовідь від ChatGPT 3ї версії #
// Syntax: !gpt3 [Повідомлення] #
// Args: 0 #
// Rank: USER #
//
require __DIR__.'/../lib/Process.php';

use Orhanerday\OpenAi\OpenAi;
use api\{update as update, Log as Log, AutoClean as AutoClean};
$starter = ['!gpt3', '/gpt3'];
if (in_array($cmd[0], $starter)) {
    AutoClean::save();
    if ($cmd[1]) {
        $message = str_replace($cmd[0] . ' ', '', $msg);
    }
    $result = $chat->sendMessage('⏳ <b>ChatGPT</b> - Запуск NodeJS...');
    $edit = $result->result->message_id;
    $command = 'echo "' . $nodeJS_password . '" | su -l ' . $nodeJS_username . ' -c "node ' . __DIR__ . '/../NodeJS/gpt.mjs \"' . str_replace('"', '\'', $message) . '\" ' . update::$chat['id'] . ' ' . update::$message_id . ' ' . $edit . '"';
    $process = new Process($command);
}
<?php
set_time_limit(0); //Знімаємо обмеження по часу на виконання скрипту
require __DIR__.'/../config/start.php';
require __DIR__.'/../config/loader.php';
require_once '/home/alex/websites/bot.sumdubot.pp.ua/test/vendor/autoload.php';
use api\{chat as chat, Bot as Bot, stats as stats};
use Orhanerday\OpenAi\OpenAi;

//pcntl_fork();
$fp = fopen('/tmp/lockfile', 'w+');
if (flock($fp, LOCK_EX | LOCK_NB)) {
$user = new User();
if(!$user->loadByID($argv[1])) die('[DAEMON/AVIATOR] User not found');
$bot = new Bot($bot_token);
$chat = new chat($user->LocalStorageGet('chat'));
if ($user->LocalStorageGet('generation_started')) die();
$user->LocalStorageSet('generation_started', 1);
$chat->sendMessage('<b>❗ З балансу <a href="tg://user?id='.$user->user['tg_id'].'">'.$user->user['nick'].'</a> списано 0.04 💵</b>');
$result = $chat->sendMessage('⏳ <b>DALL-E 3</b> - Генерація зображення...');
$edit = $result->result->message_id;
$user->LocalStorageSet('edit', $edit);
$openai = new OpenAI(OPEN_AI_API_KEY);
$complete = $openai->image([
    "prompt" => $user->LocalStorageGet('promt'),
    "model" => "dall-e-3",
    "n" => 1,
    "size" => "1024x1024",
    "response_format" => "url",
]);
$complete = json_decode($complete, true);
$image_url = $complete['data'][0]['url'];
$description = $complete['data'][0]['revised_prompt'];
if (!$image_url) {
    $chat->editMessageText('<b>💢 Виникла помилка</b>

Відповідь DALL-E:
<em>'.var_export($complete, true).'</em>', null, $user->LocalStorageGet('edit'));
    $user->LocalStorageClear();
    die();
}
$chat->deleteMessage($user->LocalStorageGet('edit'));
$user->LocalStorageClear();
$chat->sendPhoto($image_url, $description);
die();
    flock($fp, LOCK_UN); // снять блокировку
} else {
    echo 'Скрипт уже запущен';
}

fclose($fp);
<?php
//
// Command: Очищення_чату #
// Text: !cleanALL #
// Info: Видаляє УСІ повідомлення у чаті (потрібен UserBot) #
// Syntax: !cleanALL #
// Args: 0 #
// Rank: ADMIN #
//
use api\{Bot as Bot, chat as chat, ChatMember as ChatMember, Log as Log, update as update};
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$me = $MadelineProto->getSelf();

$MadelineProto->logger($me);

if (!$me['bot']) {
    $chatId = 'CHAT_ID_OR_USERNAME';  // ID или @username чата
    $lastMessage = $MadelineProto->messages->getHistory(['peer' => $chatId, 'limit' => 1]);
    if (empty($lastMessage['messages'])) {
        die("No messages found.");
    }
    $userIdFromLastMessage = $lastMessage['messages'][0]['from_id']['user_id'];
    // Получение информации о пользователе
    $userInfo = $MadelineProto->getInfo($userIdFromLastMessage);
    $MadelineProto->channels->deleteUserHistory([
        'channel' => $chatId,
        'user_id' => $userInfo
    ]);

    echo "Messages from user ID $userIdFromLastMessage deleted successfully from $chatId!\n";
}
$MadelineProto->echo('OK, done!');
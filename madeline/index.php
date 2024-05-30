<?php

if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$chatId = -1001195752130;  // ID или @username чата

// Получить последнее сообщение из чата
$lastMessage = $MadelineProto->messages->getHistory(['peer' => $chatId, 'limit' => 1]);

if (isset($lastMessage['messages'][0]['id'])) {
    $messageId = $lastMessage['messages'][0]['id'];
    var_dump($lastMessage['messages'][0]);
    // Удалить это сообщение
    $result = $MadelineProto->messages->deleteMessages(['revoke' => true, 'id' => [$messageId]]);
    echo '=========';
    var_dump($result);
    echo "Last message (ID: $messageId) deleted successfully from $chatId!\n";
} else {
    echo "Couldn't fetch the last message from $chatId.\n";
}

echo 'OK, done!';

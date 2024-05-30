<?php
use api\update as update;
if (($cmd[0] == '!gpt4' or $cmd[0] == '/gpt4') && update::$photo_id) {
    $msg = str_replace('!gpt4', '!gpt4v', $msg);
    $msg = str_replace('/gpt4', '/gpt4v', $msg);
    $cmd = explode(' ', $msg);
    $chat->sendMessage('ℹ Ваш запит передано до <code>gpt-4o</code>');
}
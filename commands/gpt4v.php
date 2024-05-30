<?php
//
// Command: ChatGPT #
// Text: !gpt4 /gpt4 #
// Callback: gpt4 #
// Display: gpt4 #
// Info: ChatGPT 4 vision #
// Syntax: !gpt4 [Повідомлення з фото] #
// Args: 1 #
// Rank: USER #
//
require __DIR__ . '/../lib/Process.php';

use Orhanerday\OpenAi\OpenAi;
use api\{update as update, Log as Log, AutoClean as AutoClean, Bot as Bot};

$starter = ['!gpt4', '/gpt4'];
function roundUp($number, $precision = 3)
{
    $factor = pow(10, $precision);
    return ceil($number * $factor) / $factor;
}
if (in_array($cmd[0], $starter)) {
    AutoClean::save();
    if ($cmd[1]) {
        $message = str_replace($cmd[0] . ' ', '', $msg);
    }
    if ($user->user['balance_usd'] < 0.04) custom_error('Недостатньо коштів', 'Необхідно: 0.01 💵
У тебе: ' . $user->user['balance_usd'] . ' 💵');
    $fp = fopen('/tmp/lockfile_gpt', 'w+');
    if (flock($fp, LOCK_EX | LOCK_NB)) {
        if (!R::findOne('userachievements', 'user_id = ? AND achievement_id = ?', [$user->user['id'], 16])['id']) {
            $user->getAchievement(16);
            $chat->sendMessage('🌟 Вітаємо <a href="tg://user?id='.$user->user['tg_id'].'">'.$user->user['nick'].'</a> з отриманням нового досягнення!');
        }
        $result = $chat->sendMessage('⏳ <b>ChatGPT</b> - Генерація відповіді...');
        $edit = $result->result->message_id;
        if (update::$photo_id) {
        $tg_file_response = Bot::getFile(update::$photo_id);
        $path = $tg_file_response['result']['file_path'];
        $store_path = __DIR__.'/../../../sumdubot.pp.ua/img/tmp';
        $filename = Bot::storeFile($path, $store_path, date('U').'_'.mt_rand(99, 999));
        }
        if ($user->LocalStorageGet('gpt_promt')) {
            $system_promt = $user->LocalStorageGet('gpt_promt');
        } else {
            $system_promt = "Ти ChatGPT 4, працюєш у SumduBot (Бот Сумского Державного Університету) Тобі пише людина з ніком " . update::$from['username'] . ".";
        }
        require_once '/home/alex/websites/bot.sumdubot.pp.ua/test/vendor/autoload.php';
        $openai = new OpenAI(OPEN_AI_API_KEY);
        if (update::$reply['message_id']) {
            $chatGPT_message = R::findOne('chatgpt', 'message_id = ?', [update::$reply['message_id']]);
            if ($chatGPT_message['id']) {
                $chatGPT_conversation = R::load('chatgptconversations', $chatGPT_message['conversation_id']);
                if ($chatGPT_conversation['promt']) {
                    $system_promt = $chatGPT_conversation['promt'];
                }
                $messages = [];
                while ($chatGPT_message['id']) {
                    array_unshift($messages, [
                        "role" => $chatGPT_message->role,
                        "content" => [[
                            "type" => "text",
                            "text" => $chatGPT_message->message_text
                        ]]
                    ]);
                    $chatGPT_message = R::load('chatgpt', $chatGPT_message->father_id);
                }
                if ($filename) {
                    $content = [
                        [
                            "type" => "image_url",
                            "image_url" => "https://sumdubot.pp.ua/img/tmp/".$filename
                        ],
                        [
                            "type" => "text",
                            "text" => $message
                        ]
                    ];
                } else {
                    $content = [
                        [
                            "type" => "text",
                            "text" => $message
                        ]
                    ];
                }
                $messages[] = [
                    "role" => "user",
                    "content" => $content,
                ];
                array_unshift($messages, [
                    "role" => "system",
                    "content" => [[
                        "type" => "text",
                        "text" => $system_promt
                    ]]
                ]);
            }
            if (!$chatGPT_message['id']) {
//                $chat->sendMessage(var_export($messages, true)); die();
                $complete = $openai->chat([
                    'model' => 'gpt-4o',
                    'messages' => $messages,
                    'user' => $user->user['id'],
                    'max_tokens' => 2000,
                ]);
            }
        } else {
            if ($filename) {
                $content = [
                    [
                        "type" => "image_url",
                        "image_url" => "https://sumdubot.pp.ua/img/tmp/".$filename
                    ],
                    [
                        "type" => "text",
                        "text" => $message
                    ]
                ];
            } else {
                $content = [
                    [
                        "type" => "text",
                        "text" => $message
                    ]
                ];
            }
            //GPT-4o
            $complete = $openai->chat([
                'model' => 'gpt-4o',
                'messages' => [
                    [
                        "role" => "system",
                        "content" => [[
                            "type" => "text",
                            "text" => $system_promt
                        ]]
                    ],
                    [
                        "role" => "user",
                        "content" => $content,
                    ],
                ],
//                'tools' => [
//                    [
//                        'type' => 'function',
//                        'function' => [
//                            'name' => 'execute_python_code',
//                            'description' => 'Executes a given Python code snippet and returns its output. The output is captured from print statements in the code.',
//                            'parameters' => [
//                                'type' => 'object',
//                                'properties' => [
//                                    'code' => [
//                                        'type' => 'string',
//                                        'description' => 'The Python code to execute. The code should be a string containing valid Python syntax. Use print statements to output results.'
//                                    ]
//                                ],
//                                'required' => ['code']
//                            ]
//                        ]
//                    ]
//                ],
//                'tool_choice' => 'auto',
                'user' => $user->user['id'],
                'max_tokens' => 2000,
            ]);
        }
//        $chat->sendMessage('ok!');
//        $chat->sendMessage(var_export(json_decode($complete, true), true));
//        die();
        $prompt_tokens = json_decode($complete, true)['usage']['prompt_tokens'];
        $completion_tokens = json_decode($complete, true)['usage']['completion_tokens'];
        $result_price = roundUp(($prompt_tokens * 0.000005) + ($completion_tokens * 0.000015), 3);
        $user->update('balance_usd', ($user->user['balance_usd'] - $result_price));
        $chat->sendMessage('<b>❗ З балансу <a href="tg://user?id=' . $user->user['tg_id'] . '">' . $user->user['nick'] . '</a> списано ' . $result_price . ' 💵</b>

Input: <b>' . $prompt_tokens . ' tokens</b>
Output: <b>' . $completion_tokens . ' tokens</b>

' . $prompt_tokens . ' * 0.000005 + ' . $completion_tokens . ' * 0.000015 = ' . $result_price);
        $text = json_decode($complete, true)['choices'][0]['message']['content'];
        if (!$text) {
            $file = fopen('log.txt', 'w+');
            fwrite($file, var_export($openai->getCURLInfo(), true));
            fclose($file);
            $result = $chat->editMessageText('♨ Виникла помилка при генерації
<code>' . var_export(json_decode($complete, true), true) . '</code>', null, $edit);
        }
        if (!$chatGPT_conversation['id']) {
        $chatGPT_conversation = R::dispense('chatgptconversations');
        $chatGPT_conversation->user = $user->user['id'];
        $chatGPT_conversation->promt = $user->LocalStorageGet('gpt_promt');
        $chatGPT_conversation->maxTokens = $user->LocalStorageGet('gpt_maxTokens');
        $chatGPT_conversation->temperature = $user->LocalStorageGet('gpt_temperature');
        $chatGPT_conversation->frequencyPenalty = $user->LocalStorageGet('gpt_frequencyPenalty');
        $chatGPT_conversation->presencePenalty = $user->LocalStorageGet('gpt_presencePenalty');
        R::store($chatGPT_conversation);
        $user->LocalStorageClear();
        }
        $chat_gpt_user = R::dispense('chatgpt');
        $chat_gpt_user->conversation_id = $chatGPT_conversation['id'];
        $chat_gpt_user->message_id = update::$message_id;
        $chat_gpt_user->message_text = $message;
        $chat_gpt_user->role = 'user';
        $chat_gpt_user->date = date('U');
        if (update::$reply['message_id']) $chat_gpt_user->father_id = R::findOne('chatgpt', 'message_id = ?', [update::$reply['message_id']])['id'];
        if ($filename) $chat_gpt_user->image = $filename;
        R::store($chat_gpt_user);
        $chat_gpt = R::dispense('chatgpt');
        $chat_gpt->conversation_id = $chatGPT_conversation['id'];
        $chat_gpt->message_id = $edit;
        $chat_gpt->message_text = $text;
        $chat_gpt->role = 'assistant';
        $chat_gpt->date = date('U');
        $chat_gpt->father_id = $chat_gpt_user['id'];
        R::store($chat_gpt);
        if ($ex_display[0] != 'pm') {
            $text .= '
---
_Для продовження діалогу відповідайте на це повідомлення командою !gpt4 [повідомлення]_';
        } else {
            if ($ex_display[3] == 'new') {
                $user->update('display', 'pm_chatgpt_conversation_'.$chatGPT_conversation['id']);
            }
        }
        $result = $chat->editMessageText($text, null, $edit, 'Markdown');
        //unlink($store_path.'/'.$filename);
        //echo $text;
//$processId = $process->getPid();
//Log::admin('ChatGPT', $command);
    } else {
        echo 'Скрипт уже запущен';
    }

    fclose($fp);
}
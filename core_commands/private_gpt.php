<?php
use api\update as update;
$descriptions = [
    'promt' => [
        'name' => '📡 SYSTEM повідомлення (PROMT)',
        'description' => 'Поясніть чатуGPT навіщо він у цьому чаті та як йому необхідно себе поводити. Зауважте, цей текст тарифікується за тарифами Input',
        'type' => 'text',
    ],
    'maxTokens' => [
        'name' => '🎚 Макс. число токенів (max_tokens)',
        'description' => 'вказує максимальну кількість токенів, які може генерувати модель за один запит. Токен може бути словом, частиною слова або навіть пунктуацією. Зазвичай, одне слово вважається за декілька токенів, залежно від мови та складності слова. Коли ви встановлюєте maxTokens, ви обмежуєте довжину відповіді, яку модель зможе згенерувати.

<b>Максимално можлива кількість токенів: 4095</b>',
        'type' => 'number',
        'limit' => 4095,
        'round' => 0,
    ],
    'temperature' => [
        'name' => '🌡 Температура (temperature)',
        'description' => 'Температура в ChatGPT — це параметр, що регулює випадковість відповідей: нижча температура забезпечує більш передбачуваний текст, тоді як вища сприяє креативності та непередбачуваності, але може знизити точність.

<b>Максимално можлива температура: 2, допустимо число з 2ма цифрами після крапки, від 0 до 2</b>',
        'type' => 'number',
        'limit' => 2,
        'round' => 2,
    ],
    'frequencyPenalty' => [
        'name' => '👺 Штраф за частоту (frequency_penalty)',
        'description' => 'Штраф за частоту (frequency_penalty) — наскільки штрафувати нові токени залежно від їх наявної частоти в тексті. Зменшує ймовірність того, що модель дослівно повторить той самий рядок.

<b>Максимално можливий штраф: 2, допустимо число з 2ма цифрами після крапки, від 0 до 2</b>',
        'type' => 'number',
        'limit' => 2,
        'round' => 2,
    ],
    'presencePenalty' => [
        'name' => '👺 Штраф за присутність (presence_penalty)',
        'description' => 'Штраф за присутність (presence_penalty) — наскільки штрафувати нові маркери залежно від того, чи з’явилися вони в тексті до цього часу. Збільшує ймовірність того, що модель буде говорити на нові теми.

<b>Максимално можливий штраф: 2, допустимо число з 2ма цифрами після крапки, від 0 до 2</b>',
        'type' => 'number',
        'limit' => 2,
        'round' => 2,
    ],
];
if ($chat->chat['tg_id'] == $user->user['tg_id'] && ($msg != '/start' && $msg != '🔙 Повернутися 🔙')) {
    if ($ex_display[0] == 'pm' && $ex_display[1] == 'chatgpt' && $ex_display[2] == 'settings') {
        if ($ex_display[3] == 'set') {
            if ($descriptions[$ex_display[4]]['name']) {
                if ($descriptions[$ex_display[4]]['type'] == 'text') {
                    $user->LocalStorageSet('gpt_'.$ex_display[4], $msg);
                } elseif ($descriptions[$ex_display[4]]['type'] == 'number') {
                    if (is_numeric($msg) && $msg <= $descriptions[$ex_display[4]]['limit'] && $msg >= 0) {
                        $user->LocalStorageSet('gpt_'.$ex_display[4], round($msg, $descriptions[$ex_display[4]]['round']));
                    } else {
                        custom_error('Помилка', 'Введіть число від 0 до '.$descriptions[$ex_display[4]]['limit']);
                    }
                }
                $user->update('display', 'pm_chatgpt_conversation_new');
                $keyboard[0][0]['text'] = 'Ok';
                $keyboard[0][0]['callback_data'] = 'pm_chatgpt_settings';
                $chat->sendMessage('<b>✅ Встановлено!</b>', null, ['inline_keyboard' => $keyboard]);
                die();
            } else {
                custom_error('Помилка', 'Напишіть /start');
            }
        }
    }
    if ($ex_callback[0] == 'pm' && $ex_callback[1] == 'chatgpt') {
//        if ($user->user['id'] != 1) {
//            $chat->sendMessage('<b>🩺 Технічні роботи</b>
//
//Ой! Схоже, що ChatGPT 4 зараз поринув у медитацію щодо оновлення свого космічного інтелекту. Ми виявили незвичайний потік даних, що виходить із сузір\'я Андромеди, і зараз активно вбираємо космологічні знання, щоб ти міг ставити ще глибші та неординарні питання. Поки що пропонуємо тобі попити чаю, загадати бажання, дивлячись на зірки, або просто помріяти. Повертайся через короткий проміжок часу, і ти побачиш, як ChatGPT 4 став ще розумнішим, швидшим і загадковішим!');
//            die();
//        }
        if ($ex_callback[2] == 'page') {
            $page = $ex_callback[3];
            $offset = ($page - 1) * 5;
            show_chatGPT_chats($offset, update::$btn_id);
        } elseif ($ex_callback[2] == 'conversation') {
            if ($ex_callback[3]) {
                $conversation = R::load('chatgptconversations', $ex_callback[3]);
                if ($conversation['id']) {
                    $user->update('display', 'pm_chatgpt_conversation_'.$conversation['id']);
                } else {
                    $user->update('display', 'pm_chatgpt_conversation_new');
                }
                if (!$conversation['id']) {
                    $user->LocalStorageClear();
                    $keyboard[0][0]['text'] = '⚙ Налаштування';
                    $keyboard[0][0]['callback_data'] = 'pm_chatgpt_settings';
                    $chat->editMessageText('<b>💬 Діалог створено - усі ваші наступні повідомлення будуть передані ChatGPT</b>

<em>Ви можете індивідуально налаштувати цей чат, або просто одразу писати свій запит зі стандартними налаштуваннями</em>', ['inline_keyboard' => $keyboard], update::$btn_id);
                    die();
                } else {
                    $chat->editMessageText('<b>💬 Ви у діалозі #'.$conversation['id'].' - усі ваші наступні повідомлення будуть передані ChatGPT</b>

Увага! З кожним новим повідомленням ціна генерації буде збільшуватися у прогресії (старі токени будуть передаватися по тарифу Input)

<em>Якщо ви будете використовувати бот у інших чатах - ви можете вийти з діалогу і chatGPT перестане вам відповідати. У такому випадку поверніться до головного меню, та знов зайдіть у цей чат</em>', null, update::$btn_id);
                    die();
                }
            }
        } elseif ($ex_callback[2] == 'settings') {
            $user->update('display', 'pm_chatgpt_conversation_new');
            if ($ex_callback[3] == 'set') {
                $param = $ex_callback[4];
                $user->update('display', 'pm_chatgpt_settings_set_'.$param);
                $keyboard[0][0]['text'] = '🔙 Повернутися 🔙';
                $keyboard[0][0]['callback_data'] = 'pm_chatgpt_settings';
                $chat->editMessageText('<b>✏ Введіть нове значення для: '.$descriptions[$param]['name'].'</b>

'.$descriptions[$param]['description'], ['inline_keyboard' => $keyboard], update::$btn_id);
                die();
            }
            if ($user->LocalStorageGet('gpt_promt')) $system = '[кастомне]'; else $system = '[стандартно]';
            if ($user->LocalStorageGet('gpt_maxTokens')) $maxTokens = $user->LocalStorageGet('gpt_maxTokens'); else $maxTokens = '[стандартно, 2000]';
            if ($user->LocalStorageGet('gpt_temperature')) $temperature = $user->LocalStorageGet('gpt_temperature'); else $temperature = '[стандартно, 1]';
            if ($user->LocalStorageGet('gpt_frequencyPenalty')) $frequencyPenalty = $user->LocalStorageGet('gpt_frequencyPenalty'); else $frequencyPenalty = '[стандартно, 0]';
            if ($user->LocalStorageGet('gpt_presencePenalty')) $presencePenalty = $user->LocalStorageGet('gpt_presencePenalty'); else $presencePenalty = '[стандартно, 0]';
            $keyboard[0][0]['text'] = '📡 SYSTEM повідомлення (PROMT)';
            $keyboard[0][0]['callback_data'] = 'pm_chatgpt_settings_set_promt';
            $keyboard[1][0]['text'] = '🎚 Макс. число токенів (max_tokens)';
            $keyboard[1][0]['callback_data'] = 'pm_chatgpt_settings_set_maxTokens';
            $keyboard[2][0]['text'] = '🌡 Температура (temperature)';
            $keyboard[2][0]['callback_data'] = 'pm_chatgpt_settings_set_temperature';
            $keyboard[3][0]['text'] = '👺 Штраф за частоту (frequency_penalty)';
            $keyboard[3][0]['callback_data'] = 'pm_chatgpt_settings_set_frequencyPenalty';
            $keyboard[4][0]['text'] = '👺 Штраф за присутність (presence_penalty)';
            $keyboard[4][0]['callback_data'] = 'pm_chatgpt_settings_set_presencePenalty';
            $chat->editMessageText('<b>💬 Налаштування діалогу</b>

=====
<b>📡 SYSTEM повідомлення: </b>'.$system.'
<b>🎚 Макс. число токенів: </b>'.$maxTokens.'
<b>🌡 Температура: </b>'.$temperature.'
<b>👺 Штраф за частоту: </b>'.$frequencyPenalty.'
<b>👺 Штраф за присутність: </b>'.$presencePenalty.'
=====

<em>Починайте писати свій запит для збереження налаштувань</em>', ['inline_keyboard' => $keyboard], update::$btn_id);
            die();
        }
    }
    if ($ex_display[0] == 'pm' && $ex_display[1] == 'chatgpt') {
        if ($ex_display[2] == 'conversation') {
            if ($ex_display[3] != 'new') {
                $conversation = R::load('chatgptconversations', $ex_display[3]);
                if ($conversation['id']) {
                    $messages = R::find('chatgpt', 'conversation_id = ? AND role = ? ORDER BY id DESC LIMIT 1', [$conversation['id'], 'assistant']);
                    $last_message = reset($messages);
                    update::$reply['message_id'] = $last_message['message_id'];
                }
            }
            $msg = '!gpt4 '.$msg;
            $cmd = explode(' ', $msg);
        }
    }
}
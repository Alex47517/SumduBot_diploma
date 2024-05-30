<?php
use api\update as update;
if ($chat->chat['tg_id'] == $user->user['tg_id']) {
    if ($msg == '/start' or $msg == '🔙 Повернутися 🔙') {
        $user->update('display');
        $text = '👋 <b>Вас вітає СумДУ бот!</b>';
        $i = 0;
//        if (!$user->user['grp']) {
//            $keyboard[$i][0] = '🎓 Я тільки вступив';
//            $i++;
//        }
        $keyboard[$i][0] = '🔐 Авторизація на порталі';
        $i++;
        $keyboard[$i][0] = '💻 ChatGPT 4';
        $i++;
        $keyboard[$i][0] = '⚔ Combats';
        $i++;
        $chat->sendMessage($text, null, ['keyboard' => $keyboard, 'resize_keyboard' => true]);
    } elseif ($msg == '🔐 Авторизація на порталі') {
        $code = gen_password();
        $auth = R::dispense('auth');
        $auth->user_id = $user->user['id'];
        $auth->code = $code;
        $auth->date = date('U');
        R::store($auth);
        $text = '🔐 <b>Авторизація на порталі</b>
Використоввуйте це посилання, щоб увійти на портал

⚠ <em>Воно одноразове та діє 5 хвилин</em>

<code>https://'.DOMAIN.'/auth/'.$code.'</code>';
        $keyboard[0][0]['text'] = '🔗 Увійти';
        $keyboard[0][0]['url'] = 'https://'.DOMAIN.'/auth/'.$code;
        $chat->sendMessage($text, null, ['inline_keyboard' => $keyboard, 'resize_keyboard' => true]);
    } elseif ($msg == '💻 ChatGPT 4') {
        $keyboard[0][0]['text'] = '🔙 Повернутися 🔙';
        $chat->sendMessage('💻 <b>ChatGPT 4</b>

<b>Валюта</b>
Ви можете використовувати останню версію ChatGPT у цьому боті за віртуальну валюту - 💵. Вона є еквівалентом долару США, отримати її можна за допомогою команди /donate

<b>Ціни:</b>
<a href="https://t.me/alex47517">Розробник бота</a> не заробляє на донатах. Скільки ви задонатили - стільки і витрачаєте (за виключенням комісії платіжної системи). Ціни за генерацію по API ви можете переглянути на <a href="https://openai.com/pricing">офіційному сайті OpenAI</a> для моделі <b>GPT-4 Turbo</b> (з підтримкою розпізнавання зображень)
', null, ['keyboard' => $keyboard, 'resize_keyboard' => true]);
        $result = $chat->sendMessage('⏳ <b>Завантаження діалогів...</b>');
        $edit_id = $result->result->message_id;
        show_chatGPT_chats(0, $edit_id);
    }
}
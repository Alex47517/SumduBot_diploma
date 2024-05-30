<?php
//
// Command: Donate #
// Text: !донат /donate #
// Callback: donate #
// Display: donate #
// Info: Підтримати розробника #
// Syntax: !донат #
// Args: 0 #
// Rank: USER #
//
use api\{update as update, Log as Log, AutoClean as AutoClean};
if ($chat->chat_id != $user->user['tg_id']) {
    $chat->sendMessage('<b>💢 Цю команду можна використовувати лише у приватних повідомленнях з ботом</b>'); die();
}
if ($msg) {
    $keyboard[0][0]['text'] = '🔗 Задонатити';
    $keyboard[0][0]['url'] = 'https://www.buymeacoffee.com/alex47517/e/186612';
    $keyboard[1][0]['text'] = '✅ Я задонатив';
    $keyboard[1][0]['callback_data'] = 'donate_check';
    $chat->sendPhoto('https://telegra.ph/file/8c762d40368d1a12d3e06.png', '<b>💵 Підтримка проекту</b>

Якщо ви бажаєте підтримати цей проект і маєте таку можливість, ваша допомога буде високо цінуватися.

Усі внески використовуються для оплати сервісів, що використоввуються у проекті, наприклад, послуги OpenAI.

❗ <b>УВАГА</b> ❗
Будь ласка, вкажіть свій нік: <code>'.$user->user['nick'].'</code>, у поле, яке показане на зображенні (@ ставити НЕ ТРЕБА), щоб ми могли подякувати вам особливим подарунком за підтримку!

Детальнішу інформацію про правила внесків та використання бота можна знайти, написаши команду: /rules
', null, ['inline_keyboard' => $keyboard]);
}
if ($ex_callback[1] == 'check') {
    if ($user->user['donate_check'] > date('U')) {
        $chat->answerCallbackQuery('⏳ Не так часто! Спробуйте знову через '.($user->user['donate_check']-date('U')).' сек.', true);
        die();
    }
    $user->update('donate_check', (date('U')+60));
    $url = "https://developers.buymeacoffee.com/api/v1/extras";
    $ch = curl_init($url);
    $headers = [
        "Authorization: Bearer ".$buyMeACoffe_api_key
    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $json_response = curl_exec($ch);
    if (curl_errno($ch)) {
        $chat->deleteMessage(update::$btn_id);
        $chat->sendMessage('<b>🥺 От халепа!</b>

Якщо ви дійсно зробили донат - зв\'яжіться з @alex47517

<em>Помилка запиту: ' . curl_error($ch).'</em>');
        curl_close($ch);
        die();
    } else {
        curl_close($ch);
        $response = json_decode($json_response, true);
        $data = $response['data'];
        foreach ($data as $purchase) {
            if ($purchase['payer_name'] == $user->user['nick']) {
                if (R::findOne('donate', 'purchase_id = ?', [$purchase['purchase_id']])['id']) {
                    continue;
                } else {
                    $donate = R::dispense('donate');
                    $donate->purchase_id = $purchase['purchase_id'];
                    R::store($donate);
                    $user->update('balance_usd', ($user->user['balance_usd']+$purchase['purchase_amount']));
                    $chat->deleteMessage(update::$btn_id);
                    $chat->sendMessage('<b>✅ Дякую за підтримку!</b>

<em>У знак подяки вам нараховано '.$purchase['purchase_amount'].' 💵</em>');
                    die();
                }
            }
        }
        $chat->answerCallbackQuery('⏳ Поки що не знайшли інформацію про ваш донат. Спробуйте знову через '.($user->user['donate_check']-date('U')).' сек.', true);
        die();
    }
}
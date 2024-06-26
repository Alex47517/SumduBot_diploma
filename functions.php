<?php

use api\{chat, update};

class Time {
    //переобразовывает текст вида "2d4m5s" в секунды
    public static function toTimestamp($str){
        if ($str == 0) return 0;
        $month = self::getDatePart($str, 'M');
        $week = self::getDatePart($str, 'w');
        $day = self::getDatePart($str, 'd');
        $hour = self::getDatePart($str, 'h');
        $minut = self::getDatePart($str, 'm');
        $sec = self::getDatePart($str, 's');

        return $month * 60 * 60 * 24 * 30 + $week * 60 * 60 * 24 * 7 +
            $day * 60 * 60 * 24 + $hour * 60 * 60 + $minut * 60 + $sec;
    }
    //ищет в тексте "2d4m5s" (пример) указатель времени, "4m" (пример)
    private static function getDatePart($str, $part){
        $substr = substr($str, 0, strpos($str, $part));
        if(!$substr) return 0;
        $substr = strrev($substr);
        $res = '';
        for($i = 0; $i < strlen($substr); $i++){
            if(is_numeric($substr[$i])){
                $res .= $substr[$i];
            } else{
                break;
            }
        }
        return (int)strrev($res);
    }
    //формирует массив из количества дней, часов, минут, секунд. Получая на вход количество секунд
    private static function seconds2times($seconds) {
        $times = array();
        // считать нули в значениях
        $count_zero = false;
        $periods = array(60, 3600, 86400, 31536000);

        for ($i = 3; $i >= 0; $i--)
        {
            $period = floor($seconds/$periods[$i]);
            if (($period > 0) || ($period == 0 && $count_zero)) {
                $times[$i+1] = $period;
                $seconds -= $period * $periods[$i];
                $count_zero = true;
            }
        }
        $times[0] = $seconds;
        return $times;
    }
    //переобразовывает секунды в текстовое представление времени, пример: 2 дня, 6 часов, 7 мин. 9 сек.
    public static function sec2time_txt($w_time) {
        if ($w_time == 'e') return 'вічність';
        $t = self::seconds2times($w_time);
        $time = '';
        if ($t[3] && $t[3] != 0) {
            $u = self::days($t[3]);
            $time .= $t[3].' '.$u.' ';
        }
        if ($t[2] && $t[2] != 0) {
            $u = self::hours($t[2]);
            $time .= $t[2].' '.$u.' ';
        }
        if ($t[1] && $t[1] != 0) {
            $time .= $t[1].' хв. ';
        }
        if ($t[0] && $t[0] != 0) {
            $time .= $t[0].' сек. ';
        }
        return $time;
    }
    //возвращает правильный вариант слова "день" для входного числа
    private static function days($days) {
        $a=substr($days,strlen($days)-1,1);
        if($a==1) $str="доба";
        if($a==2 || $a==3 || $a==4) $str="доби";
        if($a==5 || $a==6 || $a==7 || $a==8 || $a==9 || $a==0) $str="діб";
        if ($days==11 or $days==12 or $days==13 or $days==14) $str="діб";
        return $str;
    }
    //возвращает правильный вариант слова "час" для входного числа
    private static function hours($hour) {
        $a=substr($hour,strlen($hour)-1,1);
        if($a==1) $str="година";
        if($a==2 || $a==3 || $a==4) $str="години";
        if($a==5 || $a==6 || $a==7 || $a==8 || $a==9 || $a==0) $str="годин";
        if ($hour==11 or $hour==12 or $hour==13 or $hour==14) $str="годин";
        return $str;
    }
}
function menu() {
    global $user;
    global $chat;
    $user->update('display');
    $chat->sendMessage('🔅 <b>Ви завершили усі активні сессії</b>', update::$message_id, ['remove_keyboard' => true, 'selective' => true]); die();
}
function args_error($action) {
    global $chat;
    $file = R::load('commandfiles', $action['file_id']);
    $chat->sendMessage('♨ <b>Недостатньо аргументів</b>

<b>Команда: </b>'.$file['name'].'
<b>Синтаксис: </b>'.$file['syntax'].'

<em>'.$file['info'].'</em>'); die();
}
function custom_error($title, $description, $exit = false) {
    global $chat;
    if ($exit) $exit_text = '<em>Для виходу - /start</em>'; else $exit_text = null;
    $chat->sendMessage('♨ <b>'.$title.'</b>

'.$description.'

'.$exit_text); die();
}
function translit_sef($value) {
    $converter = array(
        'а' => 'a',    'б' => 'b',    'в' => 'v',    'г' => 'g',    'д' => 'd',
        'е' => 'e',    'ё' => 'e',    'ж' => 'zh',   'з' => 'z',    'и' => 'i',
        'й' => 'y',    'к' => 'k',    'л' => 'l',    'м' => 'm',    'н' => 'n',
        'о' => 'o',    'п' => 'p',    'р' => 'r',    'с' => 's',    'т' => 't',
        'у' => 'u',    'ф' => 'f',    'х' => 'h',    'ц' => 'c',    'ч' => 'ch',
        'ш' => 'sh',   'щ' => 'sch',  'ь' => '',     'ы' => 'y',    'ъ' => '',
        'э' => 'e',    'ю' => 'yu',   'я' => 'ya',   'і' => 'i',    'ї' => 'iy',

        'А' => 'A',    'Б' => 'B',    'В' => 'V',    'Г' => 'G',    'Д' => 'D',
        'Е' => 'E',    'Ё' => 'E',    'Ж' => 'Zh',   'З' => 'Z',    'И' => 'I',
        'Й' => 'Y',    'К' => 'K',    'Л' => 'L',    'М' => 'M',    'Н' => 'N',
        'О' => 'O',    'П' => 'P',    'Р' => 'R',    'С' => 'S',    'Т' => 'T',
        'У' => 'U',    'Ф' => 'F',    'Х' => 'H',    'Ц' => 'C',    'Ч' => 'Ch',
        'Ш' => 'Sh',   'Щ' => 'Sch',  'Ь' => '',     'Ы' => 'Y',    'Ъ' => '',
        'Э' => 'E',    'Ю' => 'Yu',   'Я' => 'Ya',   'І' => 'I',    'Ї' => 'Iy',
    );

    $value = mb_strtolower($value);
    $value = strtr($value, $converter);
    $value = mb_ereg_replace('[^-0-9a-z]', '-', $value);
    $value = mb_ereg_replace('[-]+', '-', $value);
    $value = trim($value, '-');

    return $value;
}
function translit_file($filename) {
    $converter = array(
        'а' => 'a',    'б' => 'b',    'в' => 'v',    'г' => 'g',    'д' => 'd',
        'е' => 'e',    'ё' => 'e',    'ж' => 'zh',   'з' => 'z',    'и' => 'i',
        'й' => 'y',    'к' => 'k',    'л' => 'l',    'м' => 'm',    'н' => 'n',
        'о' => 'o',    'п' => 'p',    'р' => 'r',    'с' => 's',    'т' => 't',
        'у' => 'u',    'ф' => 'f',    'х' => 'h',    'ц' => 'c',    'ч' => 'ch',
        'ш' => 'sh',   'щ' => 'sch',  'ь' => '',     'ы' => 'y',    'ъ' => '',
        'э' => 'e',    'ю' => 'yu',   'я' => 'ya',   'і' => 'i',    'ї' => 'iy',

        'А' => 'A',    'Б' => 'B',    'В' => 'V',    'Г' => 'G',    'Д' => 'D',
        'Е' => 'E',    'Ё' => 'E',    'Ж' => 'Zh',   'З' => 'Z',    'И' => 'I',
        'Й' => 'Y',    'К' => 'K',    'Л' => 'L',    'М' => 'M',    'Н' => 'N',
        'О' => 'O',    'П' => 'P',    'Р' => 'R',    'С' => 'S',    'Т' => 'T',
        'У' => 'U',    'Ф' => 'F',    'Х' => 'H',    'Ц' => 'C',    'Ч' => 'Ch',
        'Ш' => 'Sh',   'Щ' => 'Sch',  'Ь' => '',     'Ы' => 'Y',    'Ъ' => '',
        'Э' => 'E',    'Ю' => 'Yu',   'Я' => 'Ya',   'І' => 'I',    'Ї' => 'Iy',
    );
    $new = '';
    $file = pathinfo(trim($filename));
    if (!empty($file['dirname']) && @$file['dirname'] != '.') {
        $new .= rtrim($file['dirname'], '/') . '/';
    }
    if (!empty($file['filename'])) {
        $file['filename'] = str_replace(array(' ', ','), '-', $file['filename']);
        $file['filename'] = strtr($file['filename'], $converter);
        $file['filename'] = mb_ereg_replace('[-]+', '-', $file['filename']);
        $file['filename'] = trim($file['filename'], '-');
        $new .= $file['filename'];
    }
    if (!empty($file['extension'])) {
        $new .= '.' . $file['extension'];
    }
    return $new;
}
function new_command($command) {
    if ($command['text']) $add .= '
// Text: '.$command['text'].' #';
    if ($command['display']) $add .= '
// Display: '.$command['display'].' #';
    if ($command['callback']) $add .= '
// Callback: '.$command['callback'].' #';
    $filename = translit_sef($command['name']).'_custom.php';
    $file_data = '<?php
//
// Command: '.$command['name'].' #'.$add.'
// Info: '.$command['info'].' #
// Syntax: '.$command['syntax'].' #
// Args: '.$command['args'].' #
// Rank: '.$command['rank'].' #
//
'.$command['code'];
    $new_command_file = fopen(__DIR__.'/commands/'.$filename, 'w+');
    fwrite($new_command_file, $file_data);
    fclose($new_command_file);
    return true;
}
function mute($user_id, $time, $reason, $by, $send_msg = true, $custom_chat = null) {
    if ($custom_chat) {
        $chat = new chat($custom_chat);
    } else {
        global $chat;
    }
    if (!$reason) $reason = '[Не вказана]';
    if ($time == 0) {
        $until_date = date('U')+1;
        $str_time = 'назавжди';
    } else {
        $until_date = date('U')+$time;
        $str_time = Time::sec2time_txt($time);
    }
    $s_user = R::load('users', $user_id);
    if ($s_user) {
        if ($send_msg) {
            $chat->sendMessage('👺 <b>Користувачу <a href="tg://user?id=' . $s_user['tg_id'] . '">' . $s_user['nick'] . '</a> видано мут</b>

<b>Адміністратор: </b>' . $by . '
<b>Причина: </b>' . $reason . '
<b>Срок: </b>' . $str_time . '');
        }
        $permissions = [
            'can_send_messages' => false,
            'can_send_media_messages' => false,
            'can_send_polls' => false,
            'can_send_other_messages' => false,
            'can_change_info' => false,
            'can_add_web_page_previews' => false,
            'can_invite_users' => false,
            'can_pin_messages' => false,
        ];
        return $chat->restrictChatMember($s_user['tg_id'], $permissions, $until_date);
    } else {
        return false;
    }
}
function unmute($user_id, $by, $send_msg = true) {
    global $chat;
    $s_user = R::load('users', $user_id);
    if ($s_user) {
        if ($send_msg) {
            $chat->sendMessage('✅ <b>Користувача <a href="tg://user?id=' . $s_user['tg_id'] . '">' . $s_user['nick'] . '</a> розмучено</b>

<b>Адміністратор: </b>' . $by . '');
        }
        $permissions = [
            'can_send_messages'         => true,
            'can_send_media_messages'   => true,
            'can_send_polls'            => true,
            'can_send_other_messages'   => true,
            'can_add_web_page_previews' => true,
        ];
        $until_date = 0;
        return $chat->restrictChatMember($s_user['tg_id'], $permissions, $until_date);
    } else {
        return false;
    }
}
function ban($user_id, $time, $reason, $by) {
    if (!$reason) $reason = '[Не вказана]';
    if ($time == 0) {
        $until_date = date('U')+1;
        $str_time = 'назавжди';
    } else {
        $until_date = date('U')+$time;
        $str_time = Time::sec2time_txt($time);
    }
    global $chat;
    $s_user = R::load('users', $user_id);
    if ($s_user) {
        $chat->sendMessage('📛 <b>Користувача <a href="tg://user?id='.$s_user['tg_id'].'">'.$s_user['nick'].'</a> забанено у чаті</b>

<b>Адміністратор: </b>'.$by.'
<b>Причина: </b>'.$reason.'
<b>Срок: </b>'.$str_time.'');
        $result = $chat->banChatMember($s_user['tg_id'], $until_date);
        $pm = new Chat($s_user['tg_id']);
        $keyboard[0][0]['text'] = 'Повернутися у чат';
        $keyboard[0][0]['url'] = 'https://t.me/+_pjx_wfyvjdlMzli';
        $pm->sendMessage('📛 <b>Ви були заблоковані у чаті "'.$chat->chat['name'].'"</b>

<b>Причина: </b>'.$reason.'
<b>Срок: </b>'.$str_time.'

<em>Після закінчення терміну блокування Ви можете повернутися натиснувши кнопку нижче</em>', null, ['inline_keyboard' => $keyboard]);
        return $result;
    } else {
        return false;
    }
}
function unban($user_id, $by) {
    global $chat;
    $s_user = R::load('users', $user_id);
    if ($s_user) {
        $chat->sendMessage('✅ <b>Користувач <a href="tg://user?id='.$s_user['tg_id'].'">'.$s_user['nick'].'</a> розбанений</b>

<b>Адміністратор: </b>'.$by.'');
        $result = $chat->unbanChatMember($s_user['tg_id']);
        $pm = new Chat($s_user['tg_id']);
        $keyboard[0][0]['text'] = 'Повернутися у чат';
        $keyboard[0][0]['url'] = 'https://t.me/+_pjx_wfyvjdlMzli';
        $pm->sendMessage('✅ <b>Адміністратор чату "'.$chat->chat['name'].'" розблокував Вас</b>

Ви можете повернутися натиснувши кнопку нижче', null, ['inline_keyboard' => $keyboard]);
        return $result;
    } else {
        return false;
    }
}
function gen_password($length = 6) {
    $password = '';
    $arr = array(
        'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm',
        'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z',
        'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M',
        'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
        '1', '2', '3', '4', '5', '6', '7', '8', '9', '0'
    );

    for ($i = 0; $i < $length; $i++) {
        $password .= $arr[random_int(0, count($arr) - 1)];
    }
    return $password;
}
function replace_custom_info($custom_info, $user) {
    $achievements_count = R::count('userachievements', '`user_id` = ?', [$user['id']]);
    $custom_info = str_replace('%id', $user['id'], $custom_info);
    $custom_info = str_replace('%nick', $user['nick'], $custom_info);
    $custom_info = str_replace('%rank', $user['rank'], $custom_info);
    $custom_info = str_replace('%balance', $user['balance'], $custom_info);
    $custom_info = str_replace('%group', $user['grp'], $custom_info);
    $custom_info = str_replace('%tg', '<a href="tg://user?id='.$user['tg_id'].'">'.$user['tg_id'].'</a>', $custom_info);
    $custom_info = str_replace('%date', $user['reg_date'], $custom_info);
    $custom_info = str_replace('%achievements', $achievements_count, $custom_info);
    return $custom_info;
}
function getEmojiNum($num) {
    if ($num >= 0 && $num < 10) {
        return ''.$num.'⃣';
    } else {
        return $num.'.';
    }
}
function remove_emoji($text) {
    return preg_replace('/[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{1F700}-\x{1F77F}\x{1F780}-\x{1F7FF}\x{1F800}-\x{1F8FF}\x{1F900}-\x{1F9FF}\x{1FA00}-\x{1FA6F}\x{1FA70}-\x{1FAFF}\x{2300}-\x{23FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}\x{2900}-\x{297F}\x{2B50}-\x{2BFF}\x{3200}-\x{32FF}\x{1F000}-\x{1F02F}]/u', '', $text);
}
function show_chatGPT_chats($offset, $edit_id) {
    global $chat, $user;
    $conversations = R::getAll('SELECT * FROM chatgptconversations WHERE `user` = ? ORDER BY `id` DESC LIMIT 6 OFFSET ?', [$user->user['id'], $offset]);
    $page = $offset/5+1;
    if ($page == 1) {
        $keyboard[0][0]['text'] = '➕ Новий діалог';
        $keyboard[0][0]['callback_data'] = 'pm_chatgpt_conversation_new';
        $button_id = 1;
    } else $button_id = 0;
    foreach ($conversations as $key => $conversation) {
        if ($key >= 5) {
            $next_index = true;
        } else $next_index = false;
        $messages = R::find('chatgpt', 'conversation_id = ? AND role = ? ORDER BY id DESC LIMIT 1', [$conversation['id'], 'user']);
        $last_message = reset($messages);
        if (!$last_message['id']) {
            $last_message['message_text'] = $conversation['id'].' | *Порожній діалог*';
        }
        $last_message['message_text'] = str_replace(PHP_EOL, ' ', $last_message['message_text']);
        if (mb_strlen($last_message['message_text']) > 40) {
            $short_msg = mb_substr($last_message['message_text'], 0, 40) . "...";
        } else $short_msg = $last_message['message_text'];
        if ($conversation['promt'] or $conversation['maxTokens'] or $conversation['temperature'] or $conversation['frequencyPenalty'] or $conversation['presencePenalty']) {
            $extra = '⚙ ';
        } else $extra = null;
        $keyboard[$button_id][0]['text'] =  $extra.$conversation['id'].' | '.$short_msg;
        $keyboard[$button_id][0]['callback_data'] = 'pm_chatgpt_conversation_'.$conversation['id'];
        $button_id++;
    }
    if ($page > 1) {
        $keyboard[$button_id][0]['text'] = '⬅';
        $keyboard[$button_id][0]['callback_data'] = 'pm_chatgpt_page_'.($page-1);
        $this_page_index = 1;
        $next_page_index = 2;
    } else {
        $this_page_index = 0;
        $next_page_index = 1;
    }
    if ($next_index or $this_page_index == 2 or $page > 1) {
        $keyboard[$button_id][$this_page_index]['text'] = '[ ' . $page . ' ]';
        $keyboard[$button_id][$this_page_index]['callback_data'] = 'pm_chatgpt_page_' . $page;
    }
    if ($next_index) {
        $keyboard[$button_id][$next_page_index]['text'] = '➡';
        $keyboard[$button_id][$next_page_index]['callback_data'] = 'pm_chatgpt_page_' . ($page + 1);
    }
    $chat->editMessageText('<b><a href="https://'.DOMAIN.'/empty?id='.mt_rand(99, 99999).'">📚</a> Список діалогів:</b>', ['inline_keyboard' => $keyboard], $edit_id);
    die();
}
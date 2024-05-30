<?php
require __DIR__.'/../config/start.php';
require __DIR__.'/../config/loader.php';
require_once __DIR__.'/../vendor/autoload.php';
set_time_limit(0); //Знімаємо обмеження по часу на виконання скрипту
use api\{chat as chat, Bot as Bot, Log as Log};
use Orhanerday\OpenAi\OpenAi;
$user = new User();
$bot = new Bot($bot_token);
if(!$user->loadByID($argv[1])) die('[DAEMON/AVIATOR] User not found');
$chat = new chat($user->user['tg_id']);
$userId = $argv[1]; // ID користувача
$theme = $argv[2]; // Тема вікторини
$question = $argv[3]; // Номер питання
$themes = [
    1 => 'програмування',
    2 => 'моделювання',
    3 => 'телеком',
    4 => 'математика',
    5 => 'електроніка',
];
$letters = ['А', 'Б', 'В', 'Г'];
echo '[1:'.$user->user['nick'].'] ok!';
// Отримання ID вікторин, які користувач ще не проходив по цій темі
$sql = "SELECT q.id 
        FROM quiz q 
        LEFT JOIN quizresults qr ON q.id = qr.question AND qr.user = ?
        WHERE qr.id IS NULL AND q.theme = ?";
$quizIds = R::getCol($sql, [$userId, $theme]);
if (count($quizIds) > 0) {
    $randomKey = array_rand($quizIds);
    $randomQuizId = $quizIds[$randomKey];

    $quiz = R::load('quiz', $randomQuizId); // Завантаження об'єкту вікторини
    $answers = R::getAll('SELECT * FROM `quizanswers` WHERE `quiz` = ? ORDER BY `id` ASC', [$quiz['id']]);
} else {
    $chat->editMessageText('<b>🙀 Ого! Ти відповів на усі питання з бази. Але не засмучуйся, зараз я придумаю щось новеньке :)</b>', null, $user->LocalStorageGet('msg_id'));
    echo '[I] GPT STARTED!';
    $openai = new OpenAI(OPEN_AI_API_KEY);
    $complete = $openai->chat([
        'model' => 'gpt-4o',
        'messages' => [
            [
                "role" => "system",
                "content" => "Твоє завдання - створити теоретичне питання по темі: '.$themes[$theme].' для студентів університету з рівнем складності 8/10. Після цього передай питання користувачу через функцію"
            ],
            [
                "role" => "user",
                "content" => 'Сгенеруй питання та передай користувачу Тема: '.$themes[$theme].'.',
            ]
        ],
        'tools' => [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'generate_quiz',
                    'description' => 'Passes question to the user',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'question' => [
                                'type' => 'string',
                                'description' => 'Вигадане питання на українській мові. має вимагати глибоких знань у заданій користувачем області для відповіді. Подбай про те, щоб воно не містило фактичних помилок і було логічно коректним.'
                            ],
                            'incorrect_answer_option_1' => [
                                'type' => 'string',
                                'description' => 'Передай неправильну відповідь 1. Вона повинна бути доволі логічною, щоб студенту довелося подумати'
                            ],
                            'incorrect_answer_option_2' => [
                                'type' => 'string',
                                'description' => 'Передай неправильну відповідь 2. Вона повинна бути доволі логічною, щоб студенту довелося подумати'
                            ],
                            'incorrect_answer_option_3' => [
                                'type' => 'string',
                                'description' => 'Передай неправильну відповідь 3. Вона повинна бути доволі логічною, щоб студенту довелося подумати'
                            ],
                            'сorrect_answer' => [
                                'type' => 'string',
                                'description' => 'Передай правильну відповідь на питання у question'
                            ],
                        ],
                        'required' => ['question', 'incorrect_answer_option_1', 'incorrect_answer_option_2', 'incorrect_answer_option_3', 'сorrect_answer']
                    ]
                ]
            ]
        ],
        'tool_choice' => 'auto',
        'temperature' => 1.0,
        'max_tokens' => 1000,
        'frequency_penalty' => 0,
        'presence_penalty' => 0,
    ]);
    $complete = json_decode($complete, true);
    if (!$complete['choices'][0]['message']['tool_calls'][0]['function']['name']) {
        echo "[LOG] Помилка: не вдалося розібрати текст за вказаним форматом.\n";
        $keyboard[0][0]['text'] = '🔜 Далі 🔜';
        $keyboard[0][0]['callback_data'] = 'quiz_next_'.$question;
        $chat->editMessageText('<b>💢 Схоже, що в нас виникла проблема. Давай домовимося так: Я дам тобі 100💰, але ти надішлеш @alex47517 цей текст:</b>

<code>[ERR] Failed to parse ChatGPT response</code>', ['inline_keyboard' => $keyboard], $user->LocalStorageGet('msg_id')); die();
    } else {
        $function_request = json_decode($complete['choices'][0]['message']['tool_calls'][0]['function']['arguments'], true);
        $quiz = R::dispense('quiz');
        $quiz->question = $function_request['question'];
        $quiz->theme = $theme;
        R::store($quiz);
        Log::admin('QUIZ/SUCCESS-GPT', ' | <a href="tg://user?id='.$user->user['tg_id'].'">'.$user->user['nick'].'</a>: <code>'.var_export($function_request, true).'</code>');
        $is_correct = false;
        $answers = [];
        $correctAnswerIndex = rand(0, 3); //Випадково обираємо літеру від (А до Г) для підставки в неї правильної літери
        $incorrectIndex = 1;
        for ($i=0;$i<4;$i++) {
            if ($i == $correctAnswerIndex) {
                $answers[] = $function_request['сorrect_answer'];
            } else {
                $answers[] = $function_request['incorrect_answer_option_'.$incorrectIndex];
                $incorrectIndex++;
            }
        }
        foreach ($answers as $key => $ans) {
            $answer = R::dispense('quizanswers');
            $answer->quiz = $quiz['id'];
            $answer->answer = $ans;
            if ($key == $correctAnswerIndex) { $answer->correct = 1; $is_correct = true; }
            else $answer->correct = 0;
            R::store($answer);
        }
        $answers = R::getAll('SELECT * FROM `quizanswers` WHERE `quiz` = ? ORDER BY `id` ASC', [$quiz['id']]);
        if (!$answers[3]['answer'] or !$is_correct) {
            R::trash($quiz);
            $keyboard[0][0]['text'] = '🔜 Далі 🔜';
            $keyboard[0][0]['callback_data'] = 'quiz_next_'.$question;
            $chat->editMessageText('<b>💢 Схоже, що в нас виникла проблема. Давай домовимося так: Я зарахую це питання, але ти спробуєш ще раз:</b>

<code>[ERR] Failed to parse ChatGPT response</code>
<code>'.$text.'</code>', ['inline_keyboard' => $keyboard], $user->LocalStorageGet('msg_id')); die();
        }
    }
}
$answers_text = '';
var_dump($answers);
foreach ($answers as $key => $answer) {
    $answers_text .= '<b>'.$letters[$key].')</b> '.$answer['answer'].'
';
    if ($answer['correct']) {
        $correct_answer = $letters[$key].') '.$answer['answer'];
    }
    $keyboard[0][$key]['text'] = $letters[$key];
    $keyboard[0][$key]['callback_data'] = 'quiz_answer_'.$quiz['id'].'_'.$key.'_'.($question+1);
}
$chat->editMessageText('<b>❓ '.$question.'/10 ></b> '.mb_convert_case($themes[$theme], MB_CASE_TITLE, "UTF-8").' [⏰ 45 сек.]

'.$quiz['question'].'
<em>= Обери 1 відповідь: =</em>
'.$answers_text, ['inline_keyboard' => $keyboard], $user->LocalStorageGet('msg_id'));
sleep(45);
$keyboard = null;
$keyboard[0][0]['text'] = '🔜 Далі 🔜';
$keyboard[0][0]['callback_data'] = 'quiz_answer_'.$quiz['id'].'_-1_'.($question+1);
$chat->editMessageText('<b>❓ '.$question.'/10 ></b> '.mb_convert_case($themes[$theme], MB_CASE_TITLE, "UTF-8").'

>> <b>ЧАС ВИЧЕРПАНО!</b>

Правильна відповідь:
<em>'.$correct_answer.'</em>', ['inline_keyboard' => $keyboard], $user->LocalStorageGet('msg_id'));
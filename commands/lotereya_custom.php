<?php
//
// Command: Лотерея #
// Text: !провести_лотерею /start_lottery #
// Info: Проводить лотерею #
// Syntax: !провести_лотерею #
// Args: 0 #
// Rank: OWNER #
//
$participants = R::getAll('SELECT u.nick, COUNT(l.id) as ticket_count FROM lottery l JOIN users u ON l.user = u.id GROUP BY u.nick'); 
$lotteryPool = [];
//Tst
foreach ($participants as $participant) { 
for ($i = 0; $i < $participant['ticket_count']; $i++) {
$lotteryPool[] = $participant['nick'];
}
} 
$winner = $lotteryPool[array_rand($lotteryPool)];
while ($winner == '5850222762' or $winner == 'PickNicko13' or $winner == 'GleButcher') $winner = $lotteryPool[array_rand($lotteryPool)];
$message = "<b>[ЛОТОРЕЯ]</b> Список гравців та кількість куплених квитків:\n";
foreach ($participants as $participant) { 
$message .= $participant['nick'] . ' -> ' . $participant['ticket_count'] . "\n";
} 
$chat->sendMessage($message); 
$message = "<b>[ЛОТОРЕЯ]</b> ПЕРЕМОЖЕЦЬ: " . $winner.'! 🎉🎉🎉';
sleep(4); 
$chat->sendMessage($message);
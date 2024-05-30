<?php
class Kn {
    private static $game;
    private static $player1;
    private static $player2;

    public static function load($id) {
        self::$game = R::load('kn', $id);
        self::$player1 = R::load('users', self::$game->player1);
        self::$player2 = R::load('users', self::$game->player2);
    }
    public static function reloadTable() {
        global $chat;
        if (self::$game->moves%2==0 or self::$game->moves==0) {
            $move = '<a href="tg://user?id='.self::$player1['tg_id'].'">'.self::$player1['nick'].'</a> ❌';
        } else {
            $move = '<a href="tg://user?id='.self::$player2['tg_id'].'">'.self::$player2['nick'].'</a> ⭕';
        }
        $keyboard = self::getTable();
        $chat->editMessageText('<b>🤞 Хрестики - нолики 👌</b>

Ставка: <b>'.self::$game['bet'].'💰</b>
❌ <code>'.self::$player1['nick'].'</code> VS <code>'.self::$player2['nick'].'</code> ⭕

===
Очікуємо хід '.$move.'
⏳ 30 сек.', ['inline_keyboard' => $keyboard], self::$game['msg_id']);
    }
    public static function storageSet($line, $col, $value) {
        $tmp = json_decode(self::$game->storage, true);
        $tmp[$line][$col] = $value;
        self::$game->storage = json_encode($tmp);
        R::store(self::$game);
        return true;
    }
    public static function storageGet($line, $col) {
        $tmp = json_decode(self::$game->storage, true);
        return $tmp[$line][$col];
    }
    public static function move($line, $col) {
        global $chat;
        if (self::$game->moves%2==0 or self::$game->moves==0) $val = 'x'; else $val = '0';
        if (self::getCount($val) > 2) self::removeOldest($val);
        self::storageSet($line, $col, ['value' => $val, 'date' => date('U')]);
        self::$game->moves++;
        self::$game->last_updated = date('U');
        R::store(self::$game);
        if (self::checkWin($val)) {
            self::win($val);
        }
        self::reloadTable();
    }
    public static function getTable($ended = false) {
        //x
        $xCount = self::getCount('x');
        if ($xCount > 2) {
            $xOldest = self::getOldest('x');
        }
        //0
        $oCount = self::getCount('0');
        if ($oCount > 2) {
            $oOldest = self::getOldest('0');
        }
        for ($i = 0; $i < 3; $i++) {
            for ($j = 0; $j < 3; $j++) {
                if (self::storageGet($i, $j)['value'] != '.') {
                    $blocked = 1;
                    if ($xOldest[0] == $i && $xOldest[1] == $j && $xCount > 2 && $xOldest) {
                        $text = '✖';
                    } elseif ($oOldest[0] == $i && $oOldest[1] == $j && $oCount > 2 && $oOldest) {
                        $text = '🔘';
                    } elseif (self::storageGet($i, $j)['value'] == 'x') {
                        $text = '❌';
                    } elseif (self::storageGet($i, $j)['value'] == '0') {
                        $text = '⭕';
                    } else {
                        $text = '?';
                    }
                } else {
                    $blocked = 0;
                    $text = '.';
                }
                $keyboard[$i][$j]['text'] = $text;
                if ($ended) $keyboard[$i][$j]['callback_data'] = 'kn_ended_'.mt_rand(99, 999999);
                else $keyboard[$i][$j]['callback_data'] = 'kn_move_'.self::$game['id'].'_'.$i.'_'.$j.'_'.$blocked;
            }
        }
        return $keyboard;
    }
    private static function getCount($type) {
        $count = 0;
        $text  = '';
        for ($i = 0; $i < 3; $i++) {
            for ($j = 0; $j < 3; $j++) {
                $value = self::storageGet($i, $j)['value'];
                $isEqual = $value === $type;
                if ($isEqual) {
                    $count++;
                }
            }
        }
        return $count;
    }
    private static function getOldest($type) {
        $oldest = date('U');
        $oldest_arr = [];
        $text = '';
        for ($i = 0; $i < 3; $i++) {
            for ($j = 0; $j < 3; $j++) {
                if (self::storageGet($i, $j)['value'] == $type && self::storageGet($i, $j)['date'] < $oldest) {
                    $oldest = self::storageGet($i, $j)['date'];
                    $oldest_arr = [$i, $j];
                }
            }
        }
        return $oldest_arr;
    }
    private static function removeOldest($type) {
        $oldest = self::getOldest($type);
        self::storageSet($oldest[0], $oldest[1], ['value' => '.', 'date' => date('U')]);
        return true;
    }
    public static function checkWin($type) {
    // check rows
        $debug = ''; // Collect debug messages in this string

        for ($i = 0; $i < 3; $i++) {
            $isEqual = self::storageGet($i, 0)['value'] === $type &&
                self::storageGet($i, 1)['value'] === $type &&
                self::storageGet($i, 2)['value'] === $type;
            $debug .= "Row $i: " . ($isEqual ? 'true' : 'false') . "\n";
            if ($isEqual) {
                return $debug . "Return: $type";
            }
        }

// check columns
        for ($j = 0; $j < 3; $j++) {
            $isEqual = self::storageGet(0, $j)['value'] === $type &&
                self::storageGet(1, $j)['value'] === $type &&
                self::storageGet(2, $j)['value'] === $type;
            $debug .= "Column $j: " . ($isEqual ? 'true' : 'false') . "\n";
            if ($isEqual) {
                return $debug . "Return: $type";
            }
        }

// check diagonals
        $isEqual = self::storageGet(0, 0)['value'] === $type &&
            self::storageGet(1, 1)['value'] === $type &&
            self::storageGet(2, 2)['value'] === $type;
        $debug .= "Main diagonal: " . ($isEqual ? 'true' : 'false') . "\n";
        if ($isEqual) {
            return $debug . "Return: $type";
        }

        $isEqual = self::storageGet(0, 2)['value'] === $type &&
            self::storageGet(1, 1)['value'] === $type &&
            self::storageGet(2, 0)['value'] === $type;
        $debug .= "Secondary diagonal: " . ($isEqual ? 'true' : 'false') . "\n";
        global $chat;
        if ($isEqual) {
            //$chat->sendMessage(var_export($debug . "Return: $type", true));
            return true;
        }
        //$chat->sendMessage(var_export($debug . "Return: false", true));
        //$chat->sendMessage(json_decode(self::$game['storage'], true));
        return false;
}
    public static function win($type, $extra = null) {
        global $chat;
        if ($extra) {
            $extra = 'Додаткова інформація:
<em>'.$extra.'</em>';
        }
        $player1 = new User();
        $player1->loadByID(self::$game['player1']);
        $player2 = new User();
        $player2->loadByID(self::$game['player2']);
        if ($type == 'x') {
            $winner = '<a href="tg://user?id='.self::$player1['tg_id'].'">'.self::$player1['nick'].'</a> ❌';
            $player1->addBal(self::$game['bet']*2);
        } else {
            $winner = '<a href="tg://user?id='.self::$player2['tg_id'].'">'.self::$player2['nick'].'</a> ⭕';
            $player1->addBal(self::$game['bet']*2);
        }
        $keyboard = self::getTable(true);
        $chat->editMessageText('<b>🤞 Хрестики - нолики 👌</b>

🎉 Виграв гравець <b>'.$winner.'</b>

Він отримує <b>'.(self::$game['bet']*2).'💰</b>

'.$extra, ['inline_keyboard' => $keyboard], self::$game['msg_id']);
        $player1->update('display');
        $player2->update('display');
        self::$game->storage = null;
        self::$game->status = 'ended';
        R::store(self::$game);
        die();
    }
}
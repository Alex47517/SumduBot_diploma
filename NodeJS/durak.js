const mysql = require('mysql');
const express = require('express');
const TelegramBot = require('node-telegram-bot-api');
const token = 'TOKEN';
const port = 7001;
const bot = new TelegramBot(token);
const chatId = process.argv[2];
const msgId = process.argv[3];
let bet = process.argv[4];
let disable_start_timer = false;
let deck = ['6 ♠', '7 ♠', '8 ♠', '9 ♠', '10 ♠', 'В ♠', 'Д ♠', 'К ♠', 'Т ♠',
             '6 ♣', '7 ♣', '8 ♣', '9 ♣', '10 ♣', 'В ♣', 'Д ♣', 'К ♣', 'Т ♣',
             '6 ♥', '7 ♥', '8 ♥', '9 ♥', '10 ♥', 'В ♥', 'Д ♥', 'К ♥', 'Т ♥',
             '6 ♦', '7 ♦', '8 ♦', '9 ♦', '10 ♦', 'В ♦', 'Д ♦', 'К ♦', 'Т ♦'];
var sql = mysql.createConnection({
    host     : 'HOST',
    user     : 'USER',
    password : 'PASSWORD',
    database : 'elitglobal'
});
sql.connect();
const app = express();
// -- CONFIG --
let start_time = 60;


let players = {};
players.db = [];
players.gameinfo = [];

//=========================
// ООП
//=========================

async function startTimer(start_time) {
    start_time -= 5;
    let opt = {
        chat_id: chatId,
        message_id: msgId,
        parse_mode: 'html',
        reply_markup: JSON.stringify({
            inline_keyboard: [[
                {text: '🎮 Приєднатися до гри', url: 'https://t.me/Sumdu_bot?start=durak'}
            ],
                [
                    {text: '📜 Правила гри', url: 'https://telegra.ph/Pravila-dlya-gri-UNO-10-19'}
                ]]
        })
    }
    let players_text = '\n';
    players.db.forEach(el => {
        players_text += el.nick+'\n';
    });
    if (start_time <= 0) {
        let opt = {
            chat_id: chatId,
            message_id: msgId,
            parse_mode: 'html',
        }
        if (players.db.length < 1) {
            await bot.editMessageText('⚠ <b>[DURAK] Не вдалося почати гру!</b> \n' +
                '\n' +
                '<em>Недостатньо гравців</em>', {chat_id: chatId, message_id: msgId, parse_mode: 'html',});
            setTimeout(function () {
                process.exit(-1);
            }, 1000);
        }
        if (players.db.length < 2) {
            console.log('ID: '+players.db[0].id);
            sql.query("SELECT * FROM users WHERE id = ?", [players.db[0].id], async function (error, results, fields) {
                let pl = results[0];
                let new_balance = Number(pl.balance)+Number(bet);
                sql.query("UPDATE users SET balance = ? WHERE id = " + players.db[0].id, [new_balance], async function (error, results, fields) {
                    await bot.editMessageText('⚠ <b>[UNO] Не вдалося почати гру!</b> \n' +
                        '\n' +
                        '<em>Недостатньо гравців</em>', {chat_id: chatId, message_id: msgId, parse_mode: 'html',});
                    setTimeout(function () {
                        process.exit(-1);
                    }, 1000);
                });
            });
        } else {
            await bot.editMessageText('🧪 <b>[DURAK] Гра почалася!</b> \n' +
                '<b>Гравці:</b>'+players_text+'' +
                '\n' +
                '♦ Ставка: <b>'+bet+'💰</b>\n' +
                '🎁 Нагорода за перемогу: <b>'+bank+'💰</b>\n', {chat_id: chatId, message_id: msgId, parse_mode: 'html',});
            //роздача карток
            players.db.forEach(function callback(player, i, array) {
                players.gameinfo[i].cards = [];
                for (let l = 0; l < 6; l++) {
                    players.gameinfo[i].cards[l] = getRandomCard();
                }
                console.log('START CARDS '+players.db[i].nick+' [MSG: '+players.gameinfo[i].message+']: '+players.gameinfo[i].cards+'\n');
            });
            active_card = getRandomCard(true);
            console.log('active_card: '+active_card);
            newAction('Почнемо гру!');
            newAction('Усім гравцям видано по 6 карток');
            newAction('Початкова карта: '+active_card);
            restartTimer(move, false);
            await updateGame();
            disable_start_timer = true;
        }
    }
    if (!disable_start_timer) {
        bot.editMessageText('⏳ <b>[DURAK] Гра запущена!</b> \n' +
            '♦ Ставка: <b>'+bet+'💰</b>\n' +
            players_text + '\n' +
            '<em>Очікування гравців - ' + start_time + ' сек.</em>', opt);
        setTimeout(startTimer, 5000, start_time);
    }
}



class game {
    constructor(game_id, players, ) {
        sql.query("SELECT * FROM users WHERE tg_id = ?", [user_id], async function (error, results, fields) {

        });
    }
}

app.post(`/bot${token}`, (req, res) => {
    bot.processUpdate(req.body);
    res.sendStatus(200);
});
app.listen(port, () => {
    console.log(`Express server is listening on ${port}`);
    let opt = {
        chat_id: chatId,
        message_id: msgId,
        parse_mode: 'html',
        reply_markup: JSON.stringify({
            inline_keyboard: [[
                {text: '🎮 Приєднатися до гри', url: 'https://t.me/Sumdu_bot?start=durak'}
            ],
                [
                    {text: '📜 Правила гри', url: 'https://telegra.ph/Pravila-dlya-gri-UNO-10-19'}
                ]]
        })
    }
    bot.editMessageText('⏳ <b>[UNO] Гра запущена!</b> \n' +
        '\n' +
        '♦ Ставка: <b>'+bet+'💰</b>\n' +
        '\n' +
        '<em>Очікування гравців - '+start_time+' сек.</em>', opt);
    setTimeout(startTimer, 5000, start_time);
});

bot.on('message', async msg => {
    console.log('TEST!!!!');
    let die = false;
    let cmd = msg.text.split(' ');
    if (cmd[0] === '/start' && cmd[1] === 'durak') {
        players.db.forEach(function callback(player, i, array) {
            if (players.db[i].tg_id === msg.chat.id) {
                die = true;
            }
        });
        if (!die) {
            sql.query("SELECT * FROM users WHERE tg_id = ?", [msg.from.id], async function (error, results, fields) {
                if (error) throw error;
                let player = results[0];
                if (Number(player.balance) >= Number(bet)) {
                    sql.query("UPDATE users SET balance = ? WHERE id = "+player.id, [(player.balance-bet)], async function (error, results, fields) {
                        bank += Number(bet);
                        if (error) throw error;
                        players.db.push(player);
                        let result = await bot.sendMessage(msg.chat.id, '⏳ Ви приєдналися до гри в "дурак". Очікуйте поки почнеться гра. \n\n Додаткова інформація в чаті звідки запустили гру');
                        players.gameinfo.push({message: result.message_id, cards: [], uno: false});
                    });
                } else {
                    bot.sendMessage(msg.chat.id, '💢 У тебе недостатньо коштів щоб оплатити ставку у цій грі\n\nНеобхідно: '+bet+'💰\nБаланс: '+player.balance+'💰');
                }
            });
        } else {
            await bot.sendMessage(msg.chat.id, '💢 Ви вже приєднані до гри в uno.');
        }
    }
});
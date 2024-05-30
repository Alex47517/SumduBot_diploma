const puppeteer = require('puppeteer');
const axios = require('axios');
let login = 'Логін від особистого кабінета сумду';
let password = 'Пароль від особистого кабінета сумду';
let bot_token = 'TOKEN';

(async () => {
    let date_from = process.argv[2];
    let date_to = process.argv[3];
    let group_code = process.argv[4];
    let chat_id = process.argv[5];
    let reply_to_message = process.argv[6];
    let message_id_to_edit = process.argv[7];
    async function getRandomIntInclusive(min, max) {
        min = Math.ceil(min);
        max = Math.floor(max);
        return Math.floor(Math.random() * (max - min + 1)) + min; //Максимум и минимум включаются
    }
    async function sendPhoto(photo) {
        axios({
            method: 'get',
            url: 'https://api.telegram.org/bot'+bot_token+'/sendPhoto?chat_id='+chat_id+'&photo='+photo+'&caption='+encodeURIComponent('<b>Розклад на '+date_from+'</b>')+'&reply_to_message_id='+reply_to_message+'&parse_mode=html',
            responseType: 'stream'
        }).then(function (response) {
            return response;
        });
    }

    function editMessage(new_text) {
        let url_str = 'https://api.telegram.org/bot'+bot_token+'/editMessageText?message_id='+message_id_to_edit+'&chat_id='+chat_id+'&text='+encodeURIComponent(new_text)+'&parse_mode=html';
        console.log(url_str);
        axios({
            method: 'get',
            url: url_str,
            responseType: 'stream'
        }).then(function (response) {
            return response;
        });
    }
    editMessage('⏳ <b>Розклад</b> - Завантаження Chromium...');
    const browser = await puppeteer.launch({
        defaultViewport: {
            width: 1920,
            height: 1080,
            isLandscape: true
        }
    });
    const page = await browser.newPage();
    editMessage('⏳ <b>Розклад</b> - Авторизація...');
    await page.goto('https://sh.cabinet.sumdu.edu.ua/');
    await page.evaluate('$(\'#email\').val(\''+login+'\'); $(\'#password\').val(\''+password+'\'); $(\'[type="submit"]\').click();');
    await page.waitForNavigation().catch(() => console.log("catched"));
    await page.evaluate('$(\'small:contains("Розклад занять")\').click();');
    await page.waitForNavigation().catch(() => console.log("catched sh"));
    await page.evaluate('$(\'#date-from\').val(\''+date_from+'\');');
    await page.evaluate('$(\'#date-to\').val(\''+date_to+'\');');
    await page.evaluate('$(\'[class="kod-group"]\').val(\''+group_code+'\');');
    await page.evaluate('$(\'[value="Запит"]\').click();');
    editMessage('⏳ <b>Розклад</b> - Отримання результату...');
    await page.waitForNavigation().catch(() => console.log("catched sh"));
    const element = await page.$('#result');
    editMessage('⏳ <b>Розклад</b> - Створення зображення...');
    let name = await getRandomIntInclusive(99, 999999);
    await element.screenshot({path: '/home/alex/websites/bot.sumdu.fun/test/NodeJS/images/'+name+'.png'});
    editMessage('⏳ <b>Розклад</b> - Зображення створено, надсилаємо ...');
    await sendPhoto('https://bot.sumdu.fun/test/NodeJS/images/'+name+'.png');
    editMessage('✅ <b>Розклад</b> - Готово!');
    await browser.close();
})();
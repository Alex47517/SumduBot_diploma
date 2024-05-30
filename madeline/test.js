const { TelegramClient } = require("telegram");
const { StringSession } = require("telegram/sessions");
const input = require("input");

const apiId = APIID;
const apiHash = "APIHASH";
const stringSession = new StringSession(""); // fill this later with the value from session.save()

(async () => {
  console.log("Loading interactive example...");
  const client = new TelegramClient(stringSession, apiId, apiHash, {
    connectionRetries: 5,
  });
  await client.start({
    phoneNumber: async () => await input.text("Please enter your number: "),
    password: async () => await input.text("Please enter your password: "),
    phoneCode: async () => await input.text("Please enter the code you received: "),
    onError: (err) => console.log(err),
  });
  console.log("You should now be connected.");

  const chatId = -1001195752130; // Targeting this specific chat
  
  // Fetch the last message in the chat
  const messages = await client.getMessages(chatId, 1);
  
  if (messages.length > 0) {
    const lastMessage = messages[0];

    // Delete the last message
    await client.deleteMessages(chatId, [lastMessage.id]);
    console.log("Last message deleted successfully in chat:", chatId);
  } else {
    console.log("No messages found in the chat:", chatId);
  }

  console.log(client.session.save()); // Save this string to avoid logging in again
})();

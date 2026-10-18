/*
 * https://github.com/morethanwords/tweb
 * Copyright (C) 2019-2026 Eduard Kuzmenko
 * https://github.com/morethanwords/tweb/blob/master/LICENSE
 *
 * The English strings of the auth flow, out of tweb's `lang.ts` and `langSign.ts` (the same keys, so the
 * cards read them unchanged), with MadelineProto's own: the bot login, and the errors of the server.
 */

const lang = {
  'Login.Title': 'Sign in to MadelineProto',
  'Login.PhoneLabelInvalid': 'Phone Number Invalid',
  'Login.StartText': 'Please confirm your country code\nand enter your phone number.',
  'Login.Code.SentSms': 'We have sent you an SMS\nwith the code.',
  'Login.Code.SentInApp': 'We have sent you a message in Telegram\nwith the code.',
  'Login.Code.SentCall': 'We will call you and dictate\nthe code.',
  'Login.Code.SentEmail': 'Please check your email **%s** (don\'t forget the spam folder) and enter the code we just sent you.',
  'Login.Code.SentUnknown': 'Please check everything\nfor a code (type: %s)',
  'Login.Code.SentMissedCall': 'Within the next few seconds you should receive\na short call from **%s**. Don\'t answer it —\njust enter the last digits of that number.',
  'Login.Code.SentSmsWord': 'We\'ve sent an SMS with a secret word\nto your phone **%s**.',
  'Login.Code.SentSmsWordBeginning': 'We\'ve sent an SMS with a secret word that starts\nwith "**%2$s**" to your phone **%1$s**.',
  'Login.Code.SentSmsPhrase': 'We\'ve sent an SMS with a secret phrase\nto your phone **%s**.',
  'Login.Code.SentSmsPhraseBeginning': 'We\'ve sent an SMS with a secret phrase that starts\nwith "**%2$s**" to your phone **%1$s**.',
  'Login.Code.SecretWord': 'Secret word',
  'Login.Code.SecretPhrase': 'Secret phrase',
  'Login.Code.WordInvalid': 'Incorrect, please try again',
  'Login.Code.WordBeginningInvalid': 'Incorrect word',
  'Login.Password.Title': 'Enter Your Password',
  'Login.Password.Subtitle': 'Your account is protected with\nan additional password',
  'Login.Register.Subtitle': 'Enter your name\nto create your account',
  'PleaseWait': 'Please wait...',
  'Code': 'Code',
  'LoginPassword': 'Password',
  'YourName': 'Your Name',
  'FirstName': 'First name (required)',
  'LastName': 'Last name (optional)',
  'StartMessaging': 'Start Messaging',
  'Country': 'Country',
  'Contacts.PhoneNumber.Placeholder': 'Phone Number',
  'Login.Next': 'Next',
  'Login.QR.Title': 'Log in by QR Code',
  'Login.QR.Help1': 'Open Telegram on your phone',
  'Login.QR.Help2': 'Go to **Settings** > **Devices** > **Add Device**',
  'Login.QR.Help3': 'Point your phone at this screen to confirm login',
  'Login.QR.Cancel': 'Log in by phone number >',
  'Login.QR.Login': 'Log in by QR Code >',
  'Login.QR.Subtitle': 'Scan with Telegram app on your phone',
  'PhoneNumber.Code.Fragment.Info': 'Get the code in the Numbers section on [Fragment]()',
  'PHONE_CODE_INVALID': 'Invalid code',
  'PHONE_CODE_EXPIRED': 'Code expired',
  'PASSWORD_HASH_INVALID': 'Incorrect password',
  'DarkMode': 'Dark Mode',
  'Error.AnError': 'An error occurred. Please try again later.',
  'AccDescr.LoginCode': 'Login code',

  // * MadelineProto
  'Close': 'Close',
  'LiquidGlass': 'Liquid Glass',
  'Login.Bot': 'Log in as a bot >',
  'Login.Bot.Title': 'Log in as a Bot',
  'Login.Bot.Subtitle': 'Enter the token @BotFather gave you\nwhen you created the bot.',
  'Login.Bot.Token': 'Bot Token',
  'Login.Bot.Cancel': 'Log in as a user >',
  'Optional': 'optional',
  'PHONE_NUMBER_INVALID': 'Phone Number Invalid',
  'PHONE_NUMBER_BANNED': 'This phone number is banned',
  'PHONE_NUMBER_FLOOD': 'Too many attempts, try again later',
  'PHONE_CODE_EMPTY': 'Invalid code',
  'PHONE_PASSWORD_FLOOD': 'Too many attempts, try again later',
  'ACCESS_TOKEN_INVALID': 'Invalid bot token',
  'ACCESS_TOKEN_EXPIRED': 'This bot token was revoked',
  'FIRSTNAME_INVALID': 'Invalid first name',
  'LASTNAME_INVALID': 'Invalid last name'
};

export type LangPackKey = keyof typeof lang;

export default lang;

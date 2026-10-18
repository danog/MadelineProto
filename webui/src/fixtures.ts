import type { Page, State } from "./types";

/** Sample states, for the tests and for `npm run dev`. */
export const steps: State["steps"] = [
    { id: "app", label: "API ID and hash" },
    { id: "account", label: "Account" },
    { id: "verify", label: "Verification" },
    { id: "done", label: "Running" },
];

export const strings: State["strings"] = {
    webDocs: "Documentation",
    webProgress: "Setup progress",
    webOptional: "optional",
    webShowPassword: "Show",
    webHidePassword: "Hide",
    webQrTitle: "Log in with a QR code",
    webQrWaiting: "Waiting for the scan…",
    loginQrCodeSuccessful: "QR code login successful!",
    loginWebQr1: "Open Telegram on your phone",
    loginWebQr2: "Go to Settings > Devices > Link Desktop Device",
    loginWebQr3: "Point your phone at this screen to confirm login",
};

const base: Omit<Page, "title"> = {
    view: "setup",
    lead: "",
    step: null,
    fields: [],
    submit: null,
    alerts: [],
    actions: [],
    instructions: [],
    qrEndpoint: null,
    data: {},
};

export const loginPage: Page = {
    ...base,
    title: "Log in to Telegram",
    lead: "Choose the account or the bot this MadelineProto instance will run as.",
    step: "account",
    fields: [
        {
            name: "type",
            label: "Log in as",
            type: "choice",
            value: "phone",
            placeholder: "",
            hint: "",
            required: true,
            autocomplete: null,
            options: { phone: "User", bot: "Bot" },
            showWhen: null,
            autofocus: false,
            maxLength: null,
        },
        {
            name: "phone_number",
            label: "Phone number",
            type: "tel",
            value: "",
            placeholder: "+39 123 456 7890",
            hint: "With the country code, like +39 123 456 7890.",
            required: true,
            autocomplete: null,
            options: {},
            showWhen: ["type", "phone"],
            autofocus: true,
            maxLength: null,
        },
        {
            name: "token",
            label: "Bot token",
            type: "text",
            value: "",
            placeholder: "123456789:AAE…",
            hint: "The token @BotFather gave you when you created the bot.",
            required: true,
            autocomplete: null,
            options: {},
            showWhen: ["type", "bot"],
            autofocus: false,
            maxLength: null,
        },
    ],
    submit: "Continue",
    alerts: [{ level: "warning", message: "Warning: the gmp extension is not installed, please install it to speed up MadelineProto!", title: null, code: null }],
    qrEndpoint: "/qr",
};

export const passwordPage: Page = {
    ...base,
    title: "Two-step verification",
    lead: "This account is protected by a password.",
    step: "verify",
    fields: [
        {
            name: "password",
            label: "Password",
            type: "password",
            value: "",
            placeholder: "",
            hint: "Hint: <pony>",
            required: true,
            autocomplete: null,
            options: {},
            showWhen: null,
            autofocus: true,
            maxLength: null,
        },
    ],
    submit: "Log in",
};

export const manualApiPage: Page = {
    ...base,
    title: "Enter your API ID and API hash",
    lead: { html: 'Note that you can also provide the API ID/hash directly in the code using the <a target="_blank" href="https://docs.madelineproto.xyz/docs/SETTINGS.html">settings</a>.' },
    step: "app",
    fields: [
        { ...passwordPage.fields[0], name: "api_id", label: "API ID", type: "text", hint: "", autofocus: true },
        { ...passwordPage.fields[0], name: "api_hash", label: "API hash", type: "text", hint: "", maxLength: 32 },
    ],
    submit: "Save and continue",
    alerts: [
        {
            level: "error",
            title: "my.telegram.org didn't create the app",
            message: {
                html: 'ERROR. Telegram doesn\'t let VPS, VPN and other datacenter IP ranges create new apps on my.telegram.org. Create the app manually at <a href="https://my.telegram.org" target="_blank" rel="noopener noreferrer">https://my.telegram.org</a>.',
            },
            code: null,
        },
    ],
    actions: [{ label: "Get them automatically", name: "api_setup", value: "reset" }],
    instructions: [{ html: 'Login to <a href="https://my.telegram.org" target="_blank" rel="noopener noreferrer">https://my.telegram.org</a>' }, "Go to API development tools", "Click on create application"],
};

export const donePage: Page = {
    ...base,
    title: "The bot was started!",
    lead: "MadelineProto keeps running on the server: you can close this page.",
    step: "done",
    alerts: [
        {
            level: "error",
            title: null,
            message: "Warning: no report peers are set, please add the following method to your event handler",
            code: "public function getReportPeers() { return '@yourtelegramusername'; }",
        },
    ],
};

export function state(page: Page): State {
    return { page, steps, strings, lang: "en", dir: "ltr" };
}

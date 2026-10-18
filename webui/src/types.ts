// The JSON serialization of the view models of src/WebUI (danog\MadelineProto\WebUI\*).

/** Plain text, or trusted HTML built by MadelineProto (SafeHtml). */
export type Text = string | { html: string };

export type FieldType = "text" | "tel" | "password" | "code" | "url" | "textarea" | "hidden" | "choice";

export interface Field {
    name: string;
    label: string;
    type: FieldType;
    value: string;
    placeholder: string;
    hint: Text;
    required: boolean;
    autocomplete: string | null;
    /** Value => label, for choices. */
    options: Record<string, string>;
    /** Only shown (and submitted) when the choice with the given name has the given value. */
    showWhen: [string, string] | null;
    autofocus: boolean;
    maxLength: number | null;
}

export type AlertLevel = "info" | "success" | "warning" | "error";

export interface Alert {
    level: AlertLevel;
    message: Text;
    title: Text | null;
    code: Text | null;
}

export interface Action {
    label: string;
    name: string;
    value: string;
}

export type StepId = "app" | "account" | "verify" | "done";

export interface Page {
    view: string;
    title: string;
    lead: Text;
    step: StepId | null;
    fields: Field[];
    /** Label of the submit button, or null if the page has no form. */
    submit: string | null;
    alerts: Alert[];
    actions: Action[];
    instructions: Text[];
    /** URL of the QR code login endpoint, if the page offers QR code login. */
    qrEndpoint: string | null;
    data: Record<string, unknown>;
}

export interface Step {
    id: StepId;
    label: string;
}

export interface State {
    page: Page;
    steps: Step[];
    strings: Record<string, string>;
    lang: string;
    dir: "ltr" | "rtl";
}

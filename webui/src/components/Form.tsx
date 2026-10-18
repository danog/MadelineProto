import { useEffect, useRef, useState, type FormEvent, type InputHTMLAttributes } from "react";
import { useStrings } from "../strings";
import type { Action, Field } from "../types";
import { isEmpty, RichText } from "./RichText";

function fieldId(field: Field): string {
    return "f-" + field.name.replace(/[^a-z0-9_-]/gi, "-");
}

/** The attributes of an input, matching Field::getInputAttributes() on the server. */
function inputAttributes(field: Field, hintId: string | undefined): InputHTMLAttributes<HTMLInputElement> {
    return {
        id: fieldId(field),
        name: field.name,
        placeholder: field.placeholder || undefined,
        required: field.required,
        autoFocus: field.autofocus,
        maxLength: field.maxLength ?? undefined,
        "aria-describedby": hintId,
        spellCheck: false,
        autoComplete:
            field.autocomplete ??
            ({ code: "one-time-code", tel: "tel", password: "current-password" } as Record<string, string>)[field.type] ??
            "off",
        inputMode: field.type === "code" ? "numeric" : field.type === "tel" ? "tel" : undefined,
        autoCapitalize: field.type === "text" || field.type === "code" ? "off" : undefined,
    };
}

function PasswordInput({ field, hintId }: { field: Field; hintId: string | undefined }) {
    const t = useStrings();
    const [revealed, setRevealed] = useState(false);
    return (
        <div className="reveal">
            <input {...inputAttributes(field, hintId)} type={revealed ? "text" : "password"} defaultValue={field.value} />
            <button type="button" className="reveal-toggle" aria-pressed={revealed} onClick={() => setRevealed(!revealed)}>
                {t(revealed ? "webHidePassword" : "webShowPassword")}
            </button>
        </div>
    );
}

function FieldInput({ field }: { field: Field }) {
    const t = useStrings();
    const id = fieldId(field);
    const hintId = isEmpty(field.hint) ? undefined : `${id}-hint`;
    let input;
    switch (field.type) {
        case "textarea":
            input = (
                <textarea
                    id={id}
                    name={field.name}
                    rows={3}
                    placeholder={field.placeholder || undefined}
                    required={field.required}
                    autoFocus={field.autofocus}
                    maxLength={field.maxLength ?? undefined}
                    aria-describedby={hintId}
                    defaultValue={field.value}
                />
            );
            break;
        case "password":
            input = <PasswordInput field={field} hintId={hintId} />;
            break;
        case "code":
            // Codes are often pasted with spaces or dashes
            input = (
                <input
                    {...inputAttributes(field, hintId)}
                    type="text"
                    defaultValue={field.value}
                    onInput={(event) => {
                        const input = event.currentTarget;
                        const clean = input.value.replace(/[\s-]+/g, "");
                        if (clean !== input.value) {
                            input.value = clean;
                        }
                    }}
                />
            );
            break;
        default:
            input = <input {...inputAttributes(field, hintId)} type={field.type} defaultValue={field.value} />;
    }
    return (
        <div className={`field is-${field.type}`}>
            <label htmlFor={id}>
                {field.label}
                {!field.required && <span className="optional">{t("webOptional")}</span>}
            </label>
            {input}
            {hintId !== undefined && <RichText as="p" className="hint" text={field.hint} />}
        </div>
    );
}

function ChoiceField({ field, value, onChange }: { field: Field; value: string; onChange: (value: string) => void }) {
    return (
        <fieldset className="field choice">
            <legend>{field.label}</legend>
            <div className="segmented">
                {Object.entries(field.options).map(([option, label]) => (
                    <label key={option} className="segment">
                        <input
                            type="radio"
                            name={field.name}
                            value={option}
                            checked={option === value}
                            required={field.required}
                            onChange={() => onChange(option)}
                        />
                        <span>{label}</span>
                    </label>
                ))}
            </div>
            {!isEmpty(field.hint) && <RichText as="p" className="hint" text={field.hint} />}
        </fieldset>
    );
}

/**
 * A form of a page: submitted natively with a POST to the current URL, as the server-rendered one is, so the
 * script handles both the same way.
 */
export function Form({ fields, submit, actions }: { fields: Field[]; submit: string; actions: Action[] }) {
    const [choices, setChoices] = useState<Record<string, string>>(() =>
        Object.fromEntries(fields.filter((field) => field.type === "choice").map((field) => [field.name, field.value])),
    );
    const [busy, setBusy] = useState<string | null>(null);
    // The state is only updated on the next render: a second click right after the first must see the submission
    const submitted = useRef(false);
    const [focusChoice, setFocusChoice] = useState<string | null>(null);

    // Coming back with the back button restores the page from the cache, still busy
    useEffect(() => {
        const reset = (event: PageTransitionEvent) => {
            if (event.persisted) {
                submitted.current = false;
                setBusy(null);
            }
        };
        window.addEventListener("pageshow", reset);
        return () => window.removeEventListener("pageshow", reset);
    }, []);

    // After switching choice, focus the first field it shows
    useEffect(() => {
        if (focusChoice === null) {
            return;
        }
        const field = fields.find((field) => field.showWhen?.[0] === focusChoice && field.showWhen[1] === choices[focusChoice]);
        if (field) {
            document.getElementById(fieldId(field))?.focus();
        }
        setFocusChoice(null);
    }, [focusChoice, choices, fields]);

    // Each submission can send a login code or create an app: never submit twice
    const onSubmit = (event: FormEvent<HTMLFormElement>) => {
        if (submitted.current) {
            event.preventDefault();
            return;
        }
        submitted.current = true;
        const submitter = (event.nativeEvent as SubmitEvent).submitter;
        setBusy(submitter?.getAttribute("data-key") ?? "submit");
    };

    const visible = fields.filter((field) => field.showWhen === null || choices[field.showWhen[0]] === field.showWhen[1]);
    return (
        <form method="post" className="form" onSubmit={onSubmit}>
            {visible.map((field) =>
                field.type === "hidden" ? (
                    <input key={field.name} type="hidden" name={field.name} value={field.value} />
                ) : field.type === "choice" ? (
                    <ChoiceField
                        key={field.name}
                        field={field}
                        value={choices[field.name] ?? ""}
                        onChange={(value) => {
                            setChoices({ ...choices, [field.name]: value });
                            setFocusChoice(field.name);
                        }}
                    />
                ) : (
                    <FieldInput key={field.name} field={field} />
                ),
            )}
            <div className="form-actions">
                <button type="submit" className="btn btn-primary" data-key="submit" aria-busy={busy === "submit" || undefined}>
                    {submit}
                </button>
                {actions.map((action, i) => (
                    <button
                        key={i}
                        type="submit"
                        className="btn btn-quiet"
                        formNoValidate
                        name={action.name}
                        value={action.value}
                        data-key={`action-${i}`}
                        aria-busy={busy === `action-${i}` || undefined}
                    >
                        {action.label}
                    </button>
                ))}
            </div>
        </form>
    );
}

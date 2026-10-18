import { useEffect, useRef, useState, type FormEvent, type InputHTMLAttributes } from "react";
import { useStrings } from "../strings";
import type { Action, Field } from "../types";
import { isEmpty, RichText } from "./RichText";

function fieldId(field: Field): string {
    return "f-" + field.name.replace(/[^a-z0-9_-]/gi, "-");
}

/** What the browser would complain about, in the words of the page: the form doesn't use its validation UI. */
function validate(input: HTMLInputElement | HTMLTextAreaElement, t: (key: string) => string): string | null {
    const value = input.value.trim();
    if (input.required && value === "") {
        return t("webFieldRequired");
    }
    if (input.type === "tel" && value !== "" && !/^\+?[\d\s().-]{5,}$/.test(value)) {
        return t("webPhoneInvalid");
    }
    return null;
}

interface Described {
    /** Ids of the error and of the hint, for aria-describedby. */
    describedBy: string | undefined;
    invalid: boolean;
}

/** The attributes of an input, matching Field::getInputAttributes() on the server. */
function inputAttributes(field: Field, { describedBy, invalid }: Described): InputHTMLAttributes<HTMLInputElement> {
    return {
        id: fieldId(field),
        name: field.name,
        placeholder: field.placeholder || undefined,
        required: field.required,
        autoFocus: field.autofocus,
        maxLength: field.maxLength ?? undefined,
        "aria-describedby": describedBy,
        "aria-invalid": invalid || undefined,
        spellCheck: false,
        autoComplete:
            field.autocomplete ??
            ({ code: "one-time-code", tel: "tel", password: "current-password" } as Record<string, string>)[field.type] ??
            "off",
        inputMode: field.type === "code" ? "numeric" : field.type === "tel" ? "tel" : undefined,
        autoCapitalize: field.type === "text" || field.type === "code" ? "off" : undefined,
    };
}

function PasswordInput({ field, described }: { field: Field; described: Described }) {
    const t = useStrings();
    const [revealed, setRevealed] = useState(false);
    return (
        <div className="reveal">
            <input {...inputAttributes(field, described)} type={revealed ? "text" : "password"} defaultValue={field.value} />
            <button type="button" className="reveal-toggle" aria-pressed={revealed} onClick={() => setRevealed(!revealed)}>
                {t(revealed ? "webHidePassword" : "webShowPassword")}
            </button>
        </div>
    );
}

function FieldInput({ field, error }: { field: Field; error: string | null }) {
    const t = useStrings();
    const id = fieldId(field);
    const hintId = isEmpty(field.hint) ? undefined : `${id}-hint`;
    const errorId = error === null ? undefined : `${id}-error`;
    const described: Described = {
        describedBy: [errorId, hintId].filter((id) => id !== undefined).join(" ") || undefined,
        invalid: error !== null,
    };
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
                    aria-describedby={described.describedBy}
                    aria-invalid={described.invalid || undefined}
                    defaultValue={field.value}
                />
            );
            break;
        case "password":
            input = <PasswordInput field={field} described={described} />;
            break;
        case "code":
            // Hidden like the other secrets typed in the login; codes are often pasted with spaces or dashes
            input = (
                <input
                    {...inputAttributes(field, described)}
                    type="password"
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
            input = <input {...inputAttributes(field, described)} type={field.type} defaultValue={field.value} />;
    }
    return (
        <div className={`field is-${field.type}${error === null ? "" : " is-invalid"}`}>
            <label htmlFor={id}>
                {field.label}
                {!field.required && <span className="optional">{t("webOptional")}</span>}
            </label>
            {input}
            {errorId !== undefined && (
                <p className="field-error" id={errorId}>
                    {error}
                </p>
            )}
            {hintId !== undefined && <RichText as="p" className="hint" text={field.hint} id={hintId} />}
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
export function Form({
    fields,
    submit,
    actions,
    onSubmitted,
}: {
    fields: Field[];
    submit: string;
    actions: Action[];
    /** Called when the form is submitted (once). */
    onSubmitted?: () => void;
}) {
    const [choices, setChoices] = useState<Record<string, string>>(() =>
        Object.fromEntries(fields.filter((field) => field.type === "choice").map((field) => [field.name, field.value])),
    );
    const t = useStrings();
    const [busy, setBusy] = useState<string | null>(null);
    const [errors, setErrors] = useState<Record<string, string>>({});
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
        const submitter = (event.nativeEvent as SubmitEvent).submitter as HTMLButtonElement | null | undefined;
        // Secondary actions (start over, enter manually...) skip the validation, like formnovalidate does
        if (!submitter?.formNoValidate) {
            const found: Record<string, string> = {};
            let first: HTMLInputElement | HTMLTextAreaElement | null = null;
            for (const element of Array.from(event.currentTarget.elements)) {
                if (
                    (element instanceof HTMLInputElement || element instanceof HTMLTextAreaElement) &&
                    !element.disabled &&
                    element.type !== "hidden" &&
                    element.type !== "radio"
                ) {
                    const error = validate(element, t);
                    if (error !== null) {
                        found[element.name] = error;
                        first ??= element;
                    }
                }
            }
            setErrors(found);
            if (first !== null) {
                event.preventDefault();
                first.focus();
                return;
            }
        }
        submitted.current = true;
        onSubmitted?.();
        setBusy(submitter?.getAttribute("data-key") ?? "submit");
    };

    const visible = fields.filter((field) => field.showWhen === null || choices[field.showWhen[0]] === field.showWhen[1]);
    return (
        <form
            method="post"
            className="form"
            noValidate
            onSubmit={onSubmit}
            onInput={(event) => {
                // Editing a field clears its error
                const name = (event.target as HTMLInputElement).name;
                if (name in errors) {
                    const rest = { ...errors };
                    delete rest[name];
                    setErrors(rest);
                }
            }}
        >
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
                    <FieldInput key={field.name} field={field} error={errors[field.name] ?? null} />
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

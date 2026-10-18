import { createContext, useContext } from "react";

/** The localized strings the app itself uses (WebUI::APP_STRINGS), the pages carry their own. */
export const StringsContext = createContext<Record<string, string>>({});

/** Get a translator: falls back to the key, so a missing string is visible instead of empty. */
export function useStrings(): (key: string) => string {
    const strings = useContext(StringsContext);
    return (key) => strings[key] ?? key;
}

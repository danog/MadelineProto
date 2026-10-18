import type { ElementType } from "react";
import type { Text } from "../types";

/**
 * Renders a text of the view models: plain text is escaped by React, `{html}` is trusted HTML built by
 * MadelineProto (SafeHtml: links in translations, never user input).
 */
export function RichText({ text, as: Tag = "span", className }: { text: Text; as?: ElementType; className?: string }) {
    if (typeof text === "string") {
        return <Tag className={className}>{text}</Tag>;
    }
    return <Tag className={className} dangerouslySetInnerHTML={{ __html: text.html }} />;
}

export function isEmpty(text: Text | null): boolean {
    return text === null || (typeof text === "string" ? text === "" : text.html === "");
}

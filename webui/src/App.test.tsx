import { act, cleanup, render, screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { afterEach, describe, expect, it, vi } from "vitest";
import { App } from "./App";
import { donePage, loginPage, manualApiPage, passwordPage, state } from "./fixtures";
import { registerView } from "./views/registry";

afterEach(() => {
    cleanup();
    vi.unstubAllGlobals();
});

/** A fetch that never answers: the QR panel stays hidden. */
function pendingFetch() {
    vi.stubGlobal("fetch", vi.fn(() => new Promise(() => {})));
}

describe("App", () => {
    it("renders the steps, with the current one marked", () => {
        render(<App state={state(passwordPage)} />);
        const current = screen.getByText("Verification").closest("li")!;
        expect(current.getAttribute("aria-current")).toBe("step");
        expect(screen.getByText("Account").closest("li")!.className).toContain("is-done");
        expect(screen.getByText("Running").closest("li")!.className).toContain("is-todo");
    });

    it("shows only the fields of the selected choice, so hidden ones are not submitted", async () => {
        pendingFetch();
        render(<App state={state(loginPage)} />);
        expect(screen.getByLabelText("Phone number")).toBeTruthy();
        expect(screen.queryByLabelText("Bot token")).toBeNull();

        await userEvent.click(screen.getByLabelText("Bot"));
        expect(screen.queryByLabelText("Phone number")).toBeNull();
        const token = screen.getByLabelText("Bot token");
        expect(document.activeElement).toBe(token);

        const data = new FormData(token.closest("form")!);
        expect([...data.keys()]).toEqual(["type", "token"]);
        expect(data.get("type")).toBe("bot");
    });

    it("escapes plain text, and renders trusted HTML", () => {
        render(<App state={state(manualApiPage)} />);
        // Plain text
        expect(screen.getByText("Go to API development tools").tagName).toBe("LI");
        // Trusted HTML
        expect(screen.getByRole("link", { name: "settings" }).getAttribute("href")).toBe("https://docs.madelineproto.xyz/docs/SETTINGS.html");
        expect(screen.getByRole("alert").textContent).toContain("VPS, VPN");
    });

    it("never renders HTML from plain text", () => {
        render(<App state={state(passwordPage)} />);
        expect(screen.getByText("Hint: <pony>")).toBeTruthy();
        expect(document.querySelector("pony")).toBeNull();
    });

    it("reveals the password", async () => {
        render(<App state={state(passwordPage)} />);
        const input = screen.getByLabelText("Password") as HTMLInputElement;
        expect(input.type).toBe("password");
        await userEvent.click(screen.getByRole("button", { name: "Show" }));
        expect(input.type).toBe("text");
        expect(screen.getByRole("button", { name: "Hide" }).getAttribute("aria-pressed")).toBe("true");
    });

    it("submits once", async () => {
        render(<App state={state(passwordPage)} />);
        await userEvent.type(screen.getByLabelText("Password"), "hunter2");
        const form = screen.getByLabelText("Password").closest("form")!;
        const first = new Event("submit", { bubbles: true, cancelable: true });
        const second = new Event("submit", { bubbles: true, cancelable: true });
        act(() => {
            form.dispatchEvent(first);
            form.dispatchEvent(second);
        });
        expect(first.defaultPrevented).toBe(false);
        expect(second.defaultPrevented).toBe(true);
        expect(screen.getByRole("button", { name: "Log in" }).getAttribute("aria-busy")).toBe("true");
    });

    it("shows its own validation errors, inline", async () => {
        pendingFetch();
        render(<App state={state(loginPage)} />);
        const phone = screen.getByLabelText("Phone number") as HTMLInputElement;
        const form = phone.closest("form")!;
        expect(form.noValidate).toBe(true);

        const empty = new Event("submit", { bubbles: true, cancelable: true });
        act(() => {
            form.dispatchEvent(empty);
        });
        expect(empty.defaultPrevented).toBe(true);
        expect(screen.getByText("Fill in this field.").id).toBe("f-phone_number-error");
        expect(phone.getAttribute("aria-invalid")).toBe("true");
        expect(phone.getAttribute("aria-describedby")).toBe("f-phone_number-error f-phone_number-hint");
        expect(document.activeElement).toBe(phone);
        // Not busy: the form can be submitted again
        expect(screen.getByRole("button", { name: "Continue" }).getAttribute("aria-busy")).toBeNull();

        await userEvent.type(phone, "abc");
        expect(screen.queryByText("Fill in this field.")).toBeNull();
        expect(phone.getAttribute("aria-invalid")).toBeNull();

        act(() => {
            form.dispatchEvent(new Event("submit", { bubbles: true, cancelable: true }));
        });
        expect(screen.getByText(/Enter the phone number with its country code/)).toBeTruthy();

        await userEvent.clear(phone);
        await userEvent.type(phone, "+39 123 456 7890");
        const valid = new Event("submit", { bubbles: true, cancelable: true });
        act(() => {
            form.dispatchEvent(valid);
        });
        expect(valid.defaultPrevented).toBe(false);
    });

    it("doesn't validate the fields of the other choice", async () => {
        pendingFetch();
        render(<App state={state(loginPage)} />);
        await userEvent.click(screen.getByLabelText("Bot"));
        await userEvent.type(screen.getByLabelText("Bot token"), "123:abc");
        const submit = new Event("submit", { bubbles: true, cancelable: true });
        act(() => {
            screen.getByLabelText("Bot token").closest("form")!.dispatchEvent(submit);
        });
        expect(submit.defaultPrevented).toBe(false);
    });

    it("renders secondary actions that skip validation", () => {
        render(<App state={state(manualApiPage)} />);
        const action = screen.getByRole("button", { name: "Get them automatically" }) as HTMLButtonElement;
        expect(action.formNoValidate).toBe(true);
        expect(action.name).toBe("api_setup");
        expect(action.value).toBe("reset");
    });

    it("renders status pages without a form", () => {
        render(<App state={state(donePage)} />);
        expect(document.querySelector("form")).toBeNull();
        expect(screen.getByText(/getReportPeers/).tagName).toBe("CODE");
        // The last step is done
        expect(screen.getByText("Running").closest("li")!.querySelector("svg")).toBeTruthy();
    });

    it("renders registered views", () => {
        registerView("custom", ({ page }) => <p>custom view: {page.title}</p>);
        render(<App state={state({ ...donePage, view: "custom" })} />);
        expect(screen.getByText("custom view: The bot was started!")).toBeTruthy();
    });
});

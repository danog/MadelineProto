import { act, cleanup, render, screen } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";
import { App } from "../App";
import { loginPage, state } from "../fixtures";

const reloadPage = vi.hoisted(() => vi.fn());
vi.mock("../components/QrPanel", async (importOriginal) => ({
    ...(await importOriginal<typeof import("../components/QrPanel")>()),
    reloadPage,
}));

afterEach(() => {
    cleanup();
    vi.unstubAllGlobals();
    reloadPage.mockReset();
});

/** A QR endpoint whose long poll answers when told to. */
function stubQr() {
    let answer: (body: unknown) => void = () => {};
    vi.stubGlobal(
        "fetch",
        vi.fn((url: string) =>
            url.endsWith("?getQrCode")
                ? Promise.resolve(new Response(JSON.stringify({ logged_in: false, svg: "<svg></svg>" })))
                : new Promise<Response>((resolve) => {
                      answer = (body) => resolve(new Response(JSON.stringify(body)));
                  }),
        ),
    );
    return (body: unknown) => answer(body);
}

describe("SetupView", () => {
    it("reloads when the QR code is scanned", async () => {
        const answer = stubQr();
        render(<App state={state(loginPage)} />);
        await screen.findByText("Log in with a QR code");
        await act(async () => answer({ logged_in: true }));
        expect(reloadPage).toHaveBeenCalledOnce();
    });

    it("doesn't reload after the form was submitted", async () => {
        // A bot login changes the authorization: the pending long poll answers logged_in, but the reload
        // would run the script a second time
        const answer = stubQr();
        render(<App state={state(loginPage)} />);
        await screen.findByText("Log in with a QR code");
        const form = screen.getByLabelText("Phone number").closest("form")!;
        act(() => {
            form.dispatchEvent(new Event("submit", { bubbles: true, cancelable: true }));
        });
        await act(async () => answer({ logged_in: true }));
        expect(reloadPage).not.toHaveBeenCalled();
    });
});

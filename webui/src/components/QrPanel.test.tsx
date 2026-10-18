import { act, cleanup, render, screen, waitFor } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";
import { useQrLogin } from "./QrPanel";

afterEach(() => {
    cleanup();
    vi.unstubAllGlobals();
    vi.useRealTimers();
});

function json(body: unknown, status = 200) {
    return Promise.resolve(new Response(JSON.stringify(body), { status, headers: { "Content-Type": "application/json" } }));
}

function Probe({ onLoggedIn, retryDelay }: { onLoggedIn: () => void; retryDelay?: number }) {
    const state = useQrLogin("/login.php", onLoggedIn, retryDelay);
    return <p data-testid="state">{state.status + ("svg" in state ? ":" + state.svg : "")}</p>;
}

describe("useQrLogin", () => {
    it("gets the code, then long-polls until the login", async () => {
        const responses = [
            json({ logged_in: false, svg: "<svg>1</svg>" }),
            json({ logged_in: false, svg: "<svg>2</svg>" }),
            json({ logged_in: true }),
        ];
        const fetch = vi.fn(() => responses.shift()!);
        vi.stubGlobal("fetch", fetch);
        const onLoggedIn = vi.fn();
        render(<Probe onLoggedIn={onLoggedIn} />);

        await waitFor(() => expect(onLoggedIn).toHaveBeenCalledOnce());
        expect(screen.getByTestId("state").textContent).toBe("done:<svg>2</svg>");
        expect(fetch.mock.calls.map((call) => (call as unknown[])[0])).toEqual([
            "/login.php?getQrCode",
            "/login.php?waitQrCodeOrLogin",
            "/login.php?waitQrCodeOrLogin",
        ]);
    });

    it("backs off on errors, then asks for a fresh code", async () => {
        const responses = [json({}, 502), json({}, 502), json({ logged_in: false, svg: "<svg>3</svg>" })];
        const fetch = vi.fn(() => responses.shift() ?? new Promise<Response>(() => {}));
        vi.stubGlobal("fetch", fetch);
        render(<Probe onLoggedIn={() => {}} retryDelay={1} />);

        await waitFor(() => expect(screen.getByTestId("state").textContent).toBe("waiting:<svg>3</svg>"));
        expect(fetch.mock.calls.map((call) => (call as unknown[])[0])).toEqual([
            "/login.php?getQrCode",
            "/login.php?waitQrCodeOrLogin",
            "/login.php?getQrCode",
            "/login.php?waitQrCodeOrLogin",
        ]);
    });

    it("stops polling when unmounted", async () => {
        let signal: AbortSignal | undefined;
        vi.stubGlobal(
            "fetch",
            vi.fn((_url: string, init: RequestInit) => {
                signal = init.signal ?? undefined;
                return new Promise<Response>(() => {});
            }),
        );
        const { unmount } = render(<Probe onLoggedIn={() => {}} />);
        await act(async () => {});
        unmount();
        expect(signal?.aborted).toBe(true);
    });
});

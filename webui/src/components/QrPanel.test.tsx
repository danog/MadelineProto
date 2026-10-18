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
            `${window.location.origin}/login.php?getQrCode`,
            `${window.location.origin}/login.php?waitQrCodeOrLogin`,
            `${window.location.origin}/login.php?waitQrCodeOrLogin`,
        ]);
    });

    it("backs off on errors, then asks for a fresh code", async () => {
        const responses = [json({}, 502), json({}, 502), json({ logged_in: false, svg: "<svg>3</svg>" })];
        const fetch = vi.fn(() => responses.shift() ?? new Promise<Response>(() => {}));
        vi.stubGlobal("fetch", fetch);
        render(<Probe onLoggedIn={() => {}} retryDelay={1} />);

        await waitFor(() => expect(screen.getByTestId("state").textContent).toBe("waiting:<svg>3</svg>"));
        expect(fetch.mock.calls.map((call) => (call as unknown[])[0])).toEqual([
            `${window.location.origin}/login.php?getQrCode`,
            `${window.location.origin}/login.php?waitQrCodeOrLogin`,
            `${window.location.origin}/login.php?getQrCode`,
            `${window.location.origin}/login.php?waitQrCodeOrLogin`,
        ]);
    });

    it("never polls another origin", async () => {
        const fetch = vi.fn(() => json({ logged_in: false, svg: "<svg>evil</svg>" }));
        vi.stubGlobal("fetch", fetch);
        function Evil() {
            const state = useQrLogin("//evil.example/login.php", () => {});
            return <p data-testid="state">{state.status}</p>;
        }
        render(<Evil />);
        await act(async () => {});
        expect(fetch).not.toHaveBeenCalled();
        expect(screen.getByTestId("state").textContent).toBe("loading");
    });

    it("never turns a path starting with // into another origin", async () => {
        const fetch = vi.fn(() => new Promise<Response>(() => {}));
        vi.stubGlobal("fetch", fetch);
        function Probe2() {
            useQrLogin(`${window.location.origin}//evil.example/login.php`, () => {});
            return null;
        }
        render(<Probe2 />);
        await act(async () => {});
        expect(fetch.mock.calls.map((call) => (call as unknown[])[0])).toEqual([`${window.location.origin}//evil.example/login.php?getQrCode`]);
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

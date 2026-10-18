import { useCallback, useRef, type ReactNode } from "react";
import { Alerts } from "../components/Alerts";
import { Form } from "../components/Form";
import { QrPanel, reloadPage, useQrLogin } from "../components/QrPanel";
import { isEmpty, RichText } from "../components/RichText";
import type { Page } from "../types";
import type { ViewProps } from "./registry";

function Body({ page, qr, onSubmitted }: { page: Page; qr: ReactNode; onSubmitted?: () => void }) {
    return (
        <div className={`pane-body${qr ? " has-qr" : ""}`}>
            <div className="pane-main">
                <header className="pane-head">
                    <h1>{page.title}</h1>
                    {!isEmpty(page.lead) && <RichText as="p" className="lead" text={page.lead} />}
                </header>
                <Alerts alerts={page.alerts} />
                {page.instructions.length > 0 && (
                    <ol className="instructions">
                        {page.instructions.map((instruction, i) => (
                            <RichText key={i} as="li" text={instruction} />
                        ))}
                    </ol>
                )}
                {page.submit !== null && <Form fields={page.fields} submit={page.submit} actions={page.actions} onSubmitted={onSubmitted} />}
            </div>
            {qr}
        </div>
    );
}

function BodyWithQr({ page, endpoint }: { page: Page; endpoint: string }) {
    // Once the form is submitted, the QR code login must not reload the page: the reload would cancel the
    // submission in the browser, but not on the server, where the script continues after a bot login (the
    // poll answers logged_in as soon as the authorization changes), and would then run again for the reload
    const submitted = useRef(false);
    const onLoggedIn = useCallback(() => {
        if (!submitted.current) {
            reloadPage();
        }
    }, []);
    const state = useQrLogin(endpoint, onLoggedIn);
    // Until the first QR code arrives, or if the endpoint is unreachable, there is just the form
    return (
        <Body
            page={page}
            qr={state.status === "loading" ? null : <QrPanel state={state} />}
            onSubmitted={() => {
                submitted.current = true;
            }}
        />
    );
}

/** The pages of the setup flow (API ID, login) and the status pages. */
export function SetupView({ page }: ViewProps) {
    return page.qrEndpoint === null ? <Body page={page} qr={null} /> : <BodyWithQr page={page} endpoint={page.qrEndpoint} />;
}

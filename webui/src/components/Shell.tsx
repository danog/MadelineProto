import type { ReactNode } from "react";
import { useStrings } from "../strings";
import type { Step, StepId } from "../types";

const markSvg = (
    <svg viewBox="0 0 32 32" aria-hidden="true">
        <rect width="32" height="32" rx="9" fill="#5546D6" />
        <path d="M9 22.5V10l7 7.5 7-7.5v12.5" fill="none" stroke="#fff" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round" />
    </svg>
);

const checkSvg = (
    <svg viewBox="0 0 16 16">
        <path d="M3.5 8.5l3 3 6-7" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
    </svg>
);

/** The page frame: the rail with the brand and the progress, and the working pane. */
export function Shell({ steps, current, children }: { steps: Step[]; current: StepId | null; children: ReactNode }) {
    const t = useStrings();
    return (
        <div className="shell">
            <aside className="rail">
                <div className="brand">
                    <span className="brand-mark">{markSvg}</span>
                    <span className="brand-name">MadelineProto</span>
                </div>
                {current !== null && <Steps steps={steps} current={current} />}
                <p className="rail-foot">
                    <a href="https://docs.madelineproto.xyz" target="_blank" rel="noopener noreferrer">
                        {t("webDocs")}
                    </a>
                </p>
            </aside>
            <main className="pane">{children}</main>
        </div>
    );
}

export function Steps({ steps, current }: { steps: Step[]; current: StepId }) {
    const t = useStrings();
    const currentIndex = steps.findIndex((step) => step.id === current);
    const last = steps.length - 1;
    return (
        <ol className="steps" aria-label={t("webProgress")}>
            {steps.map((step, i) => {
                const state = i < currentIndex ? "done" : i === currentIndex ? "current" : "todo";
                const checked = state === "done" || (state === "current" && i === last);
                return (
                    <li key={step.id} className={`step is-${state}`} aria-current={state === "current" ? "step" : undefined}>
                        <span className="step-dot" aria-hidden="true">
                            {checked ? checkSvg : i + 1}
                        </span>
                        <span className="step-label">{step.label}</span>
                    </li>
                );
            })}
        </ol>
    );
}

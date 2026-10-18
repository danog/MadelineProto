import type { Alert } from "../types";
import { isEmpty, RichText } from "./RichText";

export function Alerts({ alerts }: { alerts: Alert[] }) {
    return (
        <>
            {alerts.map((alert, i) => (
                <div key={i} className={`alert is-${alert.level}`} role={alert.level === "error" ? "alert" : "status"}>
                    {alert.title !== null && !isEmpty(alert.title) && <RichText as="p" className="alert-title" text={alert.title} />}
                    <RichText as="p" text={alert.message} />
                    {alert.code !== null && (
                        <pre>
                            <RichText as="code" text={alert.code} />
                        </pre>
                    )}
                </div>
            ))}
        </>
    );
}

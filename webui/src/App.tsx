import { Shell } from "./components/Shell";
import { StringsContext } from "./strings";
import type { State } from "./types";
import { getView, registerView } from "./views/registry";
import { SetupView } from "./views/SetupView";

registerView("setup", SetupView);

export function App({ state }: { state: State }) {
    const View = getView(state.page.view) ?? SetupView;
    return (
        <StringsContext.Provider value={state.strings}>
            <Shell steps={state.steps} current={state.page.step}>
                <View page={state.page} state={state} />
            </Shell>
        </StringsContext.Provider>
    );
}

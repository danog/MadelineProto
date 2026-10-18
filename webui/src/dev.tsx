/// <reference types="vite/client" />
import "../../src/WebUI/templates/assets/madeline.css";
import { createRoot } from "react-dom/client";
import { App } from "./App";
import { donePage, loginPage, manualApiPage, passwordPage, state } from "./fixtures";

// Development entry point (npm run dev): the sample pages, without a MadelineProto backend
const pages = { login: loginPage, password: passwordPage, manual: manualApiPage, done: donePage };
const name = (new URLSearchParams(location.search).get("page") ?? "login") as keyof typeof pages;
createRoot(document.getElementById("madeline-app")!).render(<App state={state({ ...(pages[name] ?? loginPage), qrEndpoint: null })} />);

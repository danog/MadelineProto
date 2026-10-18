(function () {
    "use strict";

    // Fields shown only for a given value of a choice: hidden fields are disabled, so they're neither validated nor submitted
    function syncConditionalFields(form) {
        form.querySelectorAll("[data-show-when]").forEach(function (field) {
            var rule = field.getAttribute("data-show-when").split("=");
            var checked = form.querySelector('input[type="radio"][name="' + rule[0] + '"]:checked');
            var visible = !checked || checked.value === rule[1];
            field.hidden = !visible;
            field.querySelectorAll("input, textarea, select").forEach(function (input) {
                input.disabled = !visible;
            });
        });
    }

    document.querySelectorAll("form").forEach(function (form) {
        syncConditionalFields(form);
        form.addEventListener("change", function (event) {
            if (event.target.type === "radio") {
                syncConditionalFields(form);
                var shown = form.querySelector("[data-show-when]:not([hidden]) input");
                if (shown) {
                    shown.focus();
                }
            }
        });
        // Each submission can send a login code or create an app: don't submit twice
        form.addEventListener("submit", function (event) {
            if (form.getAttribute("data-submitted")) {
                event.preventDefault();
                return;
            }
            form.setAttribute("data-submitted", "1");
            var button = event.submitter || form.querySelector('button[type="submit"]');
            if (button) {
                button.setAttribute("aria-busy", "true");
            }
        });
    });
    // Coming back with the back button restores the page from the cache, with the form still marked as submitted
    window.addEventListener("pageshow", function (event) {
        if (event.persisted) {
            document.querySelectorAll("form[data-submitted]").forEach(function (form) {
                form.removeAttribute("data-submitted");
                form.querySelectorAll('[aria-busy="true"]').forEach(function (button) {
                    button.removeAttribute("aria-busy");
                });
            });
        }
    });

    document.querySelectorAll("[data-reveal]").forEach(function (toggle) {
        var input = document.getElementById(toggle.getAttribute("data-reveal"));
        if (!input) {
            return;
        }
        toggle.hidden = false;
        toggle.addEventListener("click", function () {
            var reveal = input.type === "password";
            input.type = reveal ? "text" : "password";
            toggle.textContent = toggle.getAttribute(reveal ? "data-hide-label" : "data-show-label");
            toggle.setAttribute("aria-pressed", reveal ? "true" : "false");
            input.focus();
        });
    });

    // QR code login: fetch the QR code, then long-poll until it's scanned (reload) or it expires (new code)
    var qr = document.querySelector("[data-qr-endpoint]");
    if (qr && window.fetch) {
        var endpoint = qr.getAttribute("data-qr-endpoint");
        // The response is injected as markup: only ever ask the script that served the page
        var link = document.createElement("a");
        link.href = endpoint;
        if (link.protocol + "//" + link.host !== window.location.protocol + "//" + window.location.host) {
            return;
        }
        // Absolute: a path starting with // (https://host//evil.example/) would be a protocol-relative URL
        endpoint = link.protocol + "//" + link.host + link.pathname;
        var code = qr.querySelector("[data-qr-code]");
        var statusText = qr.querySelector("[data-qr-status-text]");
        var failures = 0;
        var poll = function (query) {
            fetch(endpoint + "?" + query, { credentials: "same-origin", cache: "no-store", redirect: "error" })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error("HTTP " + response.status);
                    }
                    return response.json();
                })
                .then(function (result) {
                    failures = 0;
                    if (result.logged_in) {
                        // After a submission, a reload would cancel it in the browser but not on the server,
                        // where the script continues (bot login), and would run the script again
                        if (document.querySelector("form[data-submitted]")) {
                            return;
                        }
                        qr.classList.add("is-done");
                        statusText.textContent = statusText.getAttribute("data-done-label");
                        window.location.href = window.location.href.split("#")[0];
                        return;
                    }
                    qr.hidden = false;
                    code.innerHTML = result.svg;
                    poll("waitQrCodeOrLogin");
                })
                .catch(function () {
                    failures++;
                    setTimeout(function () {
                        poll(failures > 1 ? "getQrCode" : "waitQrCodeOrLogin");
                    }, Math.min(1000 * failures, 10000));
                });
        };
        poll("getQrCode");
    }
})();

/*
 * https://github.com/morethanwords/tweb
 * Copyright (C) 2019-2026 Eduard Kuzmenko
 * https://github.com/morethanwords/tweb/blob/master/LICENSE
 *
 * tweb's auth-flow host: the shell, the theme toggle, the scrollable container and the current card,
 * with its card transitions. MadelineProto has no other account to go back to (the back button), and
 * the theme is a day/night switch rather than tweb's themeController.
 */

import {JSX, Match, Show, Switch, children, createMemo, onMount} from 'solid-js';
import {Transition} from 'solid-transition-group';

import IS_TOUCH_SUPPORTED from '@environment/touchSupport';
import {IS_MOBILE_SAFARI} from '@environment/userAgent';
import classNames from '@helpers/string/classNames';
import liteMode from '@helpers/liteMode';
import {toggleNight} from '@helpers/theme';
import I18n from '@lib/langPack';

import {
  AuthFlowContext,
  AuthFlowContextValue,
  currentCard,
  currentState,
  matchCard,
  navigateAuth,
  submitAuth,
  toImAuth
} from '@/pages/authFlow';
import styles from '@/pages/authFlow.module.scss';
import Button from '@components/buttonTsx';
import LiquidGlass from '@components/liquidGlass';

import SignInCard from '@/pages/cards/SignInCard';
import SignBotCard from '@/pages/cards/SignBotCard';
import AuthCodeCard from '@/pages/cards/AuthCodeCard';
import PasswordCard from '@/pages/cards/PasswordCard';
import SignUpCard from '@/pages/cards/SignUpCard';
import SignQRCard from '@/pages/cards/SignQRCard';
import ApiFormCard from '@/pages/cards/ApiFormCard';
import StatusCard from '@/pages/cards/StatusCard';
import SessionSelect from '@/pages/SessionSelect';

/* ------------------------------------------------------------------ */
/* Host                                                               */
/* ------------------------------------------------------------------ */

/**
 * Top-level component for the auth flow. Renders the host shell, the theme
 * button, the scrollable container, and the current card.
 *
 * Cards mount/unmount on every navigation — `<Transition mode="outin">` waits
 * for the leaving card's exit animation before the new card enters. There's
 * no keep-alive: every card runs `onMount` / `onCleanup` on each visit.
 */
export default function AuthCardsHost(): JSX.Element {
  let hostEl!: HTMLElement;

  /* ---------- context ---------- */

  const ctx: AuthFlowContextValue = {
    state: currentState,
    current: currentCard,
    navigate: navigateAuth,
    submit: submitAuth,
    toIm: toImAuth
  };

  /* ---------- theme toggle (mirrors sidebarLeft "Dark mode" menu item) ---------- */

  function toggleTheme() {
    toggleNight();
  }

  /* ---------- enter: once the fonts are ready (was in src/index.ts) ---------- */

  onMount(() => {
    if(!currentCard()) {
      console.warn(
        '[AuthCardsHost] mounted without an initial card — call navigateAuth({...}) before render'
      );
    }

    const fonts = (document as any).fonts?.ready as Promise<unknown> | undefined;
    (fonts ?? Promise.resolve()).then(() => {
      hostEl.style.opacity = '';
    });
  });

  /* ---------- render ---------- */

  return (
    <AuthFlowContext.Provider value={ctx}>
      <LiquidGlass />
      <main ref={hostEl} style={{opacity: 0}} class={classNames('whole', styles.host)} id="auth-pages">
        <Button.Icon icon="darkmode_filled" class={styles.themeButton} onClick={toggleTheme} aria-label={I18n.format('DarkMode', true)} />
        <div
          class={classNames(
            'scrollable',
            'scrollable-y',
            styles.scrollable,
            (!IS_TOUCH_SUPPORTED || IS_MOBILE_SAFARI) && 'no-scrollbar'
          )}
        >
          <div class={classNames(styles.placeholder, styles.placeholderTop)} />
          <div class={styles.cardsContainer}>
            <SessionSelect sessions={currentState()?.page.sessions ?? null} />
            <CardsTransition />
          </div>
          <div class={styles.placeholder} />
        </div>
      </main>
    </AuthFlowContext.Provider>
  );
}

/**
 * The Switch is lifted out of `<Transition>`'s direct children and resolved via
 * `children()` so we can observe the matched card from outside the transition
 * primitive: `<Transition>` is only mounted once the first card is resolved, so
 * its initial child is the card itself — `appear={false}` then correctly
 * suppresses the initial animation.
 */
function CardsTransition(): JSX.Element {
  const cardChild = children(() => (
    <Switch>
      <Match when={matchCard('signIn')} keyed>
        {(spec) => <SignInCard spec={spec} />}
      </Match>
      <Match when={matchCard('signBot')} keyed>
        {(spec) => <SignBotCard spec={spec} />}
      </Match>
      <Match when={matchCard('authCode')} keyed>
        {(spec) => <AuthCodeCard spec={spec} />}
      </Match>
      <Match when={matchCard('password')} keyed>
        {(spec) => <PasswordCard spec={spec} />}
      </Match>
      <Match when={matchCard('signUp')} keyed>
        {(spec) => <SignUpCard spec={spec} />}
      </Match>
      <Match when={matchCard('signQR')} keyed>
        {(spec) => <SignQRCard spec={spec} />}
      </Match>
      <Match when={matchCard('apiForm')} keyed>
        {(spec) => <ApiFormCard spec={spec} />}
      </Match>
      <Match when={matchCard('status')} keyed>
        {(spec) => <StatusCard spec={spec} />}
      </Match>
    </Switch>
  ));

  const stableCard = createMemo<JSX.Element>((prev) => {
    const c = cardChild();
    return c || prev;
  });

  return (
    <Show when={stableCard()}>
      {/* the cards slide only at the animation levels that move things (tweb's liteMode): with less motion
          asked for, the next card replaces the last one at once */}
      <Show when={liteMode.isAvailable('animations')} fallback={stableCard()}>
      <Transition
        mode="outin"
        enterActiveClass={styles.cardEnterActive}
        exitActiveClass={styles.cardExitActive}
        enterClass={styles.cardEnter}
        enterToClass={styles.cardEnterTo}
        exitClass={styles.cardExit}
        exitToClass={styles.cardExitTo}
        appear={false}
      >
        {stableCard()}
      </Transition>
      </Show>
    </Show>
  );
}

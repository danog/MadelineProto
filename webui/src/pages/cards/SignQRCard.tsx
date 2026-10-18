/*
 * https://github.com/morethanwords/tweb
 * Copyright (C) 2019-2026 Eduard Kuzmenko
 * https://github.com/morethanwords/tweb/blob/master/LICENSE
 *
 * Adapted for MadelineProto: the login token comes from the script's long poll (`page.qrEndpoint`, see
 * PROTOCOL.md; tweb calls `auth.exportLoginToken` in a loop), which also tells when the code was scanned,
 * or when the login moved on to the password: the page then reloads. The logo in the middle of the code
 * is MadelineProto's mark.
 */

import {onCleanup, onMount} from 'solid-js';
import QRCodeStyling from 'qr-code-styling';

import Button from '@components/buttonTsx';
import {putPreloader} from '@components/putPreloader';
import MediaHeader from '@components/mediaHeader';
import mediaHeaderStyles from '@components/mediaHeader.module.scss';
import pause from '@helpers/schedulers/pause';
import {paintQrCode} from '@helpers/qrCode/paintQrCode';
import {LangPackKey, i18n} from '@lib/langPack';
import {hasSubmitted, pollQr} from '@lib/transport';

import AuthCard from '@/pages/AuthCard';
import {CardSpec, useAuthFlow} from '@/pages/authFlow';
import {PageNotices, withoutErrors} from '@/pages/pageContent';
import styles from '@/pages/authFlow.module.scss';

const QR_SIZE = 240;
/** The longest wait after failed requests, in ms. */
const MAX_BACKOFF = 10e3;

/**
 * Card variant of `pageSignQR`: the QR code in the sticker slot, painted with the theme's colours, and
 * repainted when the token rotates or the theme changes. The loop ends when the card unmounts.
 */
export default function SignQRCard(props: {spec: CardSpec, retryDelay?: number}) {
  const {navigate, toIm} = useAuthFlow();

  let stickerHost: HTMLDivElement;
  let preloader: HTMLElement | undefined;
  let stopped = false;
  const controller = new AbortController();

  let lastDrawnLink: string | undefined;

  /* ---------- description list ---------- */

  const helpKeys: LangPackKey[] = ['Login.QR.Help1', 'Login.QR.Help2', 'Login.QR.Help3'];
  const helpList = (
    <ol class={styles.qrDescription}>
      {helpKeys.map((key, idx) => (
        <li class={styles.qrDescriptionItem}>
          <span class={styles.qrDescriptionMarker}>{idx + 1}</span>
          {i18n(key)}
        </li>
      ))}
    </ol>
  );

  /* ---------- paint QR ---------- */

  function revealCanvas(canvas: HTMLElement) {
    // ! costyl, but the library doesn't expose any events
    if(preloader) {
      preloader.style.animation = 'hide-icon .4s forwards';

      canvas.style.display = 'none';
      canvas.style.animation = 'grow-icon .4s forwards';
      setTimeout(() => {
        canvas.style.display = '';
      }, 150);
      setTimeout(() => {
        canvas.style.animation = '';
      }, 500);
      preloader = undefined;
    } else {
      Array.from(stickerHost.children).slice(0, -1).forEach((el) => el.remove());
    }
  }

  // Builds the QR canvas (and its embedded logo) with the theme's CSS variables: on a new token, and
  // when the theme changes
  async function paintQR(link: string) {
    const style = window.getComputedStyle(document.documentElement);
    const surfaceColor = style.getPropertyValue('--light-filled-primary-color').trim();
    const textColor = style.getPropertyValue('--primary-text-color').trim();
    const primaryColor = style.getPropertyValue('--primary-color').trim();

    const {canvas} = await paintQrCode({
      data: link,
      size: QR_SIZE,
      host: stickerHost,
      background: surfaceColor,
      foreground: textColor,
      logoColor: primaryColor,
      canvasClass: styles.qrCanvas,
      QRCodeStylingCtor: QRCodeStyling
    });

    if(stopped) return;
    revealCanvas(canvas);
    lastDrawnLink = link;
  }

  /** A server without the link (an older MadelineProto) still sends the SVG: drawn as an image, never as markup. */
  function paintSvg(svg: string) {
    const img = document.createElement('img');
    img.classList.add(styles.qrCanvas);
    img.alt = '';
    img.src = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(svg);
    stickerHost.append(img);
    revealCanvas(img);
  }

  const onThemeChanged = () => {
    if(stopped || !lastDrawnLink) return;
    paintQR(lastDrawnLink);
  };
  window.addEventListener('madeline-theme-change', onThemeChanged);

  /* ---------- the long poll ---------- */

  async function loop(endpoint: string) {
    let failures = 0;
    let query: 'getQrCode' | 'waitQrCodeOrLogin' = 'getQrCode';
    while(!stopped) {
      try {
        const result = await pollQr(endpoint, query, controller.signal);
        if(stopped) return;
        failures = 0;
        query = 'waitQrCodeOrLogin';

        if(result.logged_in === true) {
          // scanned (or the login moved on to the password): the page reloads, unless a form was submitted
          // meanwhile, whose answer must not be cancelled (PROTOCOL.md)
          stopped = true;
          if(!hasSubmitted()) toIm();
          return;
        }

        if(result.link) {
          if(result.link !== lastDrawnLink) await paintQR(result.link);
        } else if(result.svg) {
          paintSvg(result.svg);
        }
      } catch(err) {
        if(stopped) return;
        console.error('SignQRCard:', err);
        // the server or the connection is down: back off, and get a fresh code after repeated failures
        failures++;
        if(failures > 1) query = 'getQrCode';
        await pause(Math.min((props.retryDelay ?? 1000) * failures, MAX_BACKOFF));
      }
    }
  }

  /* ---------- lifecycle ---------- */

  onMount(() => {
    preloader = putPreloader(stickerHost, true);
    if(props.spec.page.qrEndpoint) {
      loop(props.spec.page.qrEndpoint);
    }
  });

  onCleanup(() => {
    stopped = true;
    controller.abort();
    window.removeEventListener('madeline-theme-change', onThemeChanged);
  });

  /* ---------- render ---------- */

  return (
    <AuthCard
      class={styles.pageSignQR}
      inputWrapper={false}
      header={
        <MediaHeader>
          <MediaHeader.Sticker ref={stickerHost} class={styles.qrContainer} size={QR_SIZE}/>
          <h1
            class={`${mediaHeaderStyles.title} text-center text-overflow-wrap`}
            style={{'margin-top': 0}}
          >
            {i18n('Login.QR.Title')}
          </h1>
          <MediaHeader.Subtitle class="secondary">{i18n('Login.QR.Subtitle')}</MediaHeader.Subtitle>
          <PageNotices alerts={props.spec.page.alerts} />
        </MediaHeader>
      }
    >
      {helpList}
      <Button
        class="btn-primary btn-secondary btn-primary-transparent primary"
        onClick={() => {
          stopped = true;
          navigate({name: 'signIn', page: withoutErrors(props.spec.page)});
        }}
        text="Login.QR.Cancel"
      />
    </AuthCard>
  );
}

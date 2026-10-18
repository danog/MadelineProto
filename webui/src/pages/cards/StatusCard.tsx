/*
 * MadelineProto's status page in tweb's style (`status`, see PROTOCOL.md): the bot is running, or already
 * was. No form: the server's title and text, and its notices, like the issues of the event handler with the
 * code that fixes them.
 *
 * Built from the pieces of tweb (https://github.com/morethanwords/tweb, GPL-3.0): see SignInCard.tsx.
 */

import {Show} from 'solid-js';

import Logo from '@components/logo';
import MediaHeader from '@components/mediaHeader';

import AuthCard from '@/pages/AuthCard';
import type {CardSpec} from '@/pages/authFlow';
import {PageNotices, RichText, isEmptyText} from '@/pages/pageContent';
import styles from '@/pages/authFlow.module.scss';

export default function StatusCard(props: {spec: CardSpec}) {
  const page = props.spec.page;
  return (
    <AuthCard
      inputWrapper={false}
      header={
        <MediaHeader>
          <MediaHeader.Sticker class={styles.logoContainer} size={120} element={<Logo class={styles.logo} />} />
          <MediaHeader.Title tag="h1">{page.title}</MediaHeader.Title>
          <Show when={!isEmptyText(page.lead)}>
            <MediaHeader.Subtitle class="secondary"><RichText text={page.lead} /></MediaHeader.Subtitle>
          </Show>
          <PageNotices alerts={page.alerts} errors />
        </MediaHeader>
      }
    />
  );
}

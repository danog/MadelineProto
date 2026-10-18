import {cleanup, fireEvent, render, screen} from '@solidjs/testing-library';
import {afterEach, describe, expect, it} from 'vitest';

import I18n from '@lib/langPack';
import {PageNotices} from '@/pages/pageContent';
import type {Alert} from '@/types';

afterEach(cleanup);

describe('localization', () => {
  it('uses the strings of the page over the English ones', () => {
    expect(I18n.format('Login.Next', true)).toBe('Next');
    I18n.applyStrings({'Login.Next': 'Avanti', webDocs: 'Documentazione', notAString: 3});
    expect(I18n.format('Login.Next', true)).toBe('Avanti');
    I18n.applyStrings({'Login.Next': 'Next'});
  });

  it('names the countries in the language of the page', () => {
    const italy = I18n.countriesList.find((country) => country.iso2 === 'DE')!;
    const anonymous = I18n.countriesList.find((country) => country.iso2 === 'FT')!;
    I18n.applyStrings({}, 'it');
    expect(italy.name).toBe('Germania');
    expect(italy.default_name).toBe('Germany');
    // no region for the virtual ones: their English name
    expect(anonymous.name || anonymous.default_name).toBe('Anonymous Numbers');
  });
});

describe('notices', () => {
  const warning: Alert = {level: 'warning', message: 'gmp is missing', title: null, code: null};
  const info: Alert = {level: 'info', message: 'uv is missing', title: null, code: null};
  const translation: Alert = {level: 'info', message: 'translate MadelineProto', title: null, code: null};
  const error: Alert = {level: 'error', message: 'PHONE_CODE_INVALID', title: 'Error', code: null};

  it('merges the warnings into one notice, one at a time', () => {
    render(() => <PageNotices alerts={[warning, info, translation, error]} errors />);
    // the error on its own, the rest in one notice
    expect(screen.getByRole('alert').textContent).toContain('PHONE_CODE_INVALID');
    expect(screen.getAllByRole('status')).toHaveLength(1);
    expect(screen.getByRole('status').textContent).toContain('gmp is missing');

    const pager = screen.getByRole('button');
    expect(pager.textContent).toBe('1/3');
    fireEvent.click(pager);
    expect(screen.getByRole('status').textContent).toContain('uv is missing');
    expect(screen.getByRole('button').textContent).toBe('2/3');
    fireEvent.click(screen.getByRole('button'));
    fireEvent.click(screen.getByRole('button'));
    expect(screen.getByRole('status').textContent).toContain('gmp is missing');
  });

  it('shows a single notice without the pager', () => {
    render(() => <PageNotices alerts={[warning]} />);
    expect(screen.getByRole('status').textContent).toContain('gmp is missing');
    expect(screen.queryByRole('button')).toBeNull();
  });
});

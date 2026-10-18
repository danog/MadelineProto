/*
 * https://github.com/morethanwords/tweb
 * Copyright (C) 2019-2026 Eduard Kuzmenko
 * https://github.com/morethanwords/tweb/blob/master/LICENSE
 *
 * A slim version of tweb's `components/inputField.ts`: the same DOM and API, always on an `<input>`
 * (tweb's `plainText` mode). The contenteditable mode, with its rich text, custom emoji and paste
 * handling, has nothing to do on a login form.
 */

import {i18n, LangPackKey} from '@lib/langPack';

export enum InputState {
  Neutral = 0,
  Valid = 1,
  Error = 2
};

export type InputFieldOptions = {
  placeholder?: LangPackKey,
  label?: LangPackKey,
  labelOptions?: any[],
  /** A label that isn't a lang key: one the server sent. */
  labelText?: string,
  name?: string,
  maxLength?: number,
  showLengthOn?: number,
  plainText?: true,
  required?: boolean,
  inputMode?: 'tel' | 'numeric',
  autocomplete?: string,
  allowStartingSpace?: boolean,
  onRawInput?: (value: string) => void
};

let idSeed = 0;
const uniqueId = () => 'input-field-' + (++idSeed);

export default class InputField {
  public container: HTMLElement;
  public input: HTMLInputElement;
  public label: HTMLLabelElement;
  public placeholder: HTMLElement;

  public required: boolean;
  public allowStartingSpace: boolean;

  constructor(public options: InputFieldOptions = {}) {
    this.container = document.createElement('div');
    this.container.classList.add('input-field');

    this.required = options.required;
    this.allowStartingSpace = options.allowStartingSpace;

    if(options.maxLength !== undefined && options.showLengthOn === undefined) {
      options.showLengthOn = Math.min(40, Math.round(options.maxLength / 3));
    }

    const {placeholder, maxLength, showLengthOn, name, autocomplete} = options;
    const label = options.label || options.labelText;

    const input = this.input = document.createElement('input');
    input.type = 'text';
    if(name) input.name = name;
    input.autocomplete = (autocomplete ?? 'off') as AutoFill;
    if(label) input.required = true;
    input.classList.add('input-field-input');
    input.dir = 'auto';
    this.container.append(input);

    if(options.inputMode) {
      input.inputMode = options.inputMode;
    }

    if(placeholder) {
      this.placeholder = document.createElement('span');
      this.placeholder.id = uniqueId();
      if(!label) input.setAttribute('aria-labelledby', this.placeholder.id);
      this.placeholder.classList.add('input-field-placeholder');
      this.placeholder.append(i18n(placeholder));
      this.container.append(this.placeholder);
    }

    if(label || placeholder) {
      const border = document.createElement('div');
      border.classList.add('input-field-border');
      this.container.append(border);
    }

    if(label != null || maxLength) {
      this.label = document.createElement('label');
      this.label.id = uniqueId();
      input.id ||= uniqueId();
      this.label.htmlFor = input.id;
      this.setLabel();
      this.container.append(this.label);
    }

    const onInputCallbacks: Array<() => void> = [() => this.setEmpty()];

    if(maxLength) {
      const labelEl = this.label;
      let showingLength = false;
      onInputCallbacks.push(() => {
        const wasError = input.classList.contains('error');
        const diff = maxLength - input.value.length;
        const isError = diff < 0;
        input.classList.toggle('error', isError);
        labelEl.classList.toggle('error', isError);

        if(isError || diff <= showLengthOn) {
          this.setLabel();
          labelEl.append(` (${maxLength - input.value.length})`);
          labelEl.style.visibility = 'visible';
          showingLength = true;
        } else if((wasError && !isError) || showingLength) {
          this.setLabel();
          showingLength = false;
        }
      });
    }

    if(options.onRawInput) {
      onInputCallbacks.push(() => options.onRawInput(this.value));
    }

    input.addEventListener('input', () => {
      onInputCallbacks.forEach((callback) => callback());
    });

    this.setEmpty();
  }

  public select() {
    if(!this.value) return; // * avoid selecting whole empty field on iOS devices
    this.input.select();
  }

  public setLabel() {
    if(!this.label) return;
    this.label.textContent = '';
    if(this.options.labelText) {
      this.label.textContent = this.options.labelText;
    } else if(this.options.label) {
      this.label.append(i18n(this.options.label, this.options.labelOptions));
    }
    this.label.style.visibility = this.label.textContent ? 'visible' : 'hidden';
  }

  get value(): string {
    return this.input.value;
  }

  set value(value: string | Node) {
    this.setValueSilently(value);
    this.simulateInputEvent();
  }

  public simulateInputEvent() {
    this.input.dispatchEvent(new Event('input', {bubbles: true, cancelable: true}));
  }

  public setValueSilently(value: string | Node) {
    this.input.value = typeof(value) === 'string' ? value : (value?.textContent ?? '');
    this.setEmpty();
  }

  private setEmpty() {
    const empty = this.isEmpty();
    [this.input, this.placeholder].filter(Boolean).forEach((el) => {
      el.classList.toggle('is-empty', empty);
    });
  }

  public isEmpty() {
    const value = this.allowStartingSpace ? this.input.value : this.input.value.trim();
    return !value;
  }

  public isValid() {
    return !this.input.classList.contains('error') && (!this.required || !this.isEmpty());
  }

  public setState(state: InputState, label?: LangPackKey | string, labelOptions?: any[]) {
    const isError = !!(state & InputState.Error);

    if(label) {
      this.label.textContent = '';
      this.label.append(i18n(label, labelOptions ?? this.options.labelOptions));
      this.label.style.visibility = 'visible';
    } else {
      this.setLabel();
    }

    if(isError) {
      this.input.setAttribute('aria-invalid', 'true');
    } else {
      this.input.removeAttribute('aria-invalid');
    }
    this.input.classList.toggle('error', isError);
    this.input.classList.toggle('valid', !!(state & InputState.Valid));
  }

  public setError(label?: LangPackKey | string, labelOptions?: any[]) {
    this.setState(InputState.Error, label, labelOptions);
  }
}

/*
 * https://github.com/morethanwords/tweb
 * Copyright (C) 2019-2026 Eduard Kuzmenko
 * https://github.com/morethanwords/tweb/blob/master/LICENSE
 *
 * Adapted for MadelineProto.
 */

import lottieLoader, {LottieLoader, LottiePlayer} from '@lib/lottie/lottieLoader';
import PasswordInputField from '@components/passwordInputField';

export default class PasswordMonkey {
  public container: HTMLElement;
  public animation: LottiePlayer;
  public needFrame = 0;
  protected loadPromise: ReturnType<LottieLoader['waitForFirstFrame']>;

  constructor(protected passwordInputField: PasswordInputField, protected size: number) {
    this.container = document.createElement('div');
    this.container.classList.add('media-sticker-wrapper');
  }

  public load() {
    if(this.loadPromise) return this.loadPromise;
    return this.loadPromise = lottieLoader.loadAnimationAsAsset({
      container: this.container,
      loop: false,
      autoplay: false,
      width: this.size,
      height: this.size,
      noCache: true
    // }, 'assets/img/TwoFactorSetupMonkeyClose.tgs').then((_animation) => {
    }, 'TwoFactorSetupMonkeyPeek').then((_animation) => {
      // return;
      this.animation = _animation;
      this.animation.addEventListener('enterFrame', (currentFrame) => {
        // console.log('enterFrame', currentFrame, this.needFrame);

        if((this.animation.direction === 1 && currentFrame >= this.needFrame) ||
          (this.animation.direction === -1 && currentFrame <= this.needFrame)) {
          this.animation.setSpeed(1);
          this.animation.pause();
        }
      });

      this.passwordInputField.helpers.onVisibilityClickAdditional = () => {
        if(this.passwordInputField.helpers.passwordVisible) {
          this.animation.setDirection(1);
          this.animation.curFrame = 0;
          this.needFrame = 16;
          this.animation.play();
        } else {
          this.animation.setDirection(-1);
          this.animation.curFrame = 16;
          this.needFrame = 0;
          this.animation.play();
        }
      };

      return lottieLoader.waitForFirstFrame(_animation);
    });
  }

  public remove() {
    if(this.animation) {
      this.animation.remove();
    }
  }
}

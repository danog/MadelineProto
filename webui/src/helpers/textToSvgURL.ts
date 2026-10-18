/*
 * https://github.com/morethanwords/tweb
 * Copyright (C) 2019-2026 Eduard Kuzmenko
 * https://github.com/morethanwords/tweb/blob/master/LICENSE
 */

export default function textToSvgURL(text: string) {
  const blob = new Blob([text], {type: 'image/svg+xml;charset=utf-8'});

  // * because iOS Safari doesn't want to eat objectURL
  return new Promise<string>((resolve) => {
    const reader = new FileReader();
    reader.onload = (e) => {
      resolve(e.target.result as string);
    };
    reader.readAsDataURL(blob);
  });
  // return URL.createObjectURL(blob);
}

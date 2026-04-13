'use strict';

document.addEventListener('DOMContentLoaded', () => {
  const categoryItems = [...document.querySelectorAll('.image-item')];
  const lightbox = document.getElementById('lightbox');
  if (!lightbox || categoryItems.length === 0) return;

  const canvas = document.getElementById('lightbox-canvas');
  const ctx = canvas.getContext('2d');
  const counter = lightbox.querySelector('[data-lightbox-counter]');
  const thumbLoaders = new Map();
  const thumbnailObserver = 'IntersectionObserver' in window
    ? new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        thumbnailObserver.unobserve(entry.target);
        const loadThumb = thumbLoaders.get(entry.target);
        if (loadThumb) loadThumb();
      });
    }, { rootMargin: '200px' })
    : null;
  let current = 0;
  let touchStartX = 0;
  lightbox.hidden = true;

  const paintThumbnail = (localCtx, canvasEl, source) => {
    const imgW = source.naturalWidth;
    const imgH = source.naturalHeight;
    if (!imgW || !imgH) return;

    const cW = canvasEl.width;
    const cH = canvasEl.height;
    const canvasRatio = cW / cH;
    const imageRatio = imgW / imgH;
    let sx = 0;
    let sy = 0;
    let sw = imgW;
    let sh = imgH;

    if (imageRatio > canvasRatio) {
      sw = imgH * canvasRatio;
      sx = (imgW - sw) / 2;
    } else {
      sh = imgW / canvasRatio;
      sy = (imgH - sh) / 2;
    }

    localCtx.clearRect(0, 0, cW, cH);
    localCtx.drawImage(source, sx, sy, sw, sh, 0, 0, cW, cH);
  };

  const draw = (index) => {
    current = index;
    const src = categoryItems[current].dataset.displaySrc;
    const img = new Image();
    img.onload = () => {
      const ratio = Math.min(canvas.width / img.width, canvas.height / img.height);
      const w = img.width * ratio;
      const h = img.height * ratio;
      const x = (canvas.width - w) / 2;
      const y = (canvas.height - h) / 2;
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      ctx.drawImage(img, x, y, w, h);
      counter.textContent = `${current + 1} / ${categoryItems.length}`;
    };
    img.src = src;
  };

  const open = (index) => {
    lightbox.hidden = false;
    document.body.classList.add('lb-open');
    draw(index);
  };

  const close = () => {
    lightbox.hidden = true;
    document.body.classList.remove('lb-open');
  };

  const next = () => draw((current + 1) % categoryItems.length);
  const prev = () => draw((current - 1 + categoryItems.length) % categoryItems.length);

  categoryItems.forEach((item, idx) => {
    item.addEventListener('click', () => open(idx));
    const canvasEl = item.querySelector('canvas');
    const source = item.querySelector('.source-image');
    if (!canvasEl || !source) return;
    const localCtx = canvasEl.getContext('2d');
    const thumbSrc = source.currentSrc || source.getAttribute('src');
    if (!thumbSrc) return;
    let loaded = false;
    const loadThumb = () => {
      if (loaded) return;
      loaded = true;
      const thumb = new Image();
      thumb.decoding = 'async';
      thumb.onload = () => paintThumbnail(localCtx, canvasEl, thumb);
      thumb.src = thumbSrc;
    };
    if (thumbnailObserver) {
      thumbLoaders.set(item, loadThumb);
      thumbnailObserver.observe(item);
    } else {
      loadThumb();
    }
  });

  lightbox.querySelector('[data-lightbox-close]').addEventListener('click', close);
  lightbox.querySelector('[data-lightbox-next]').addEventListener('click', next);
  lightbox.querySelector('[data-lightbox-prev]').addEventListener('click', prev);
  lightbox.addEventListener('click', (e) => { if (e.target === lightbox || e.target === canvas) close(); });
  document.addEventListener('keydown', (e) => {
    if (lightbox.hidden) return;
    if (e.key === 'Escape') close();
    if (e.key === 'ArrowRight') next();
    if (e.key === 'ArrowLeft') prev();
  });

  lightbox.addEventListener('touchstart', (e) => { touchStartX = e.changedTouches[0].screenX; });
  lightbox.addEventListener('touchend', (e) => {
    const delta = e.changedTouches[0].screenX - touchStartX;
    if (delta > 60) prev();
    if (delta < -60) next();
  });
});

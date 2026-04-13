<?php declare(strict_types=1); ?>
<script>
(() => {
  const galleries = document.querySelectorAll('.protected-gallery');
  const message = <?= json_encode(__('protection.message')) ?>;
  galleries.forEach((gallery) => {
    gallery.addEventListener('contextmenu', (e) => { e.preventDefault(); alert(message); });
    gallery.addEventListener('dragstart', (e) => e.preventDefault());
    gallery.querySelectorAll('img').forEach((img) => { img.draggable = false; img.style.pointerEvents = 'none'; });
  });
  document.addEventListener('keydown', (e) => {
    if ((e.ctrlKey && e.key.toLowerCase() === 's') || (e.ctrlKey && e.shiftKey && e.key.toLowerCase() === 'i')) {
      e.preventDefault();
    }
  });
})();
</script>

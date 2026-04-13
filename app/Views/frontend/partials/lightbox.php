<?php declare(strict_types=1); ?>
<div class="lightbox" id="lightbox" hidden>
    <button type="button" class="lightbox-close" data-lightbox-close aria-label="<?= e(__('lightbox.close')) ?>">×</button>
    <button type="button" class="lightbox-prev" data-lightbox-prev aria-label="<?= e(__('lightbox.prev')) ?>">←</button>
    <canvas id="lightbox-canvas" width="1400" height="900"></canvas>
    <button type="button" class="lightbox-next" data-lightbox-next aria-label="<?= e(__('lightbox.next')) ?>">→</button>
    <div class="lightbox-counter" data-lightbox-counter>1 / 1</div>
</div>

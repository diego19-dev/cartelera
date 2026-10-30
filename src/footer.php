<?php
/**
 * Cierre del layout del panel: toasts, diálogo confirmar, admin.js / api.js.
 */
if ($currentUser ?? null): ?>
    </main>
  </div>
</div>
<div id="toast-host" class="fixed top-4 right-4 z-50 space-y-2"></div>
<div id="confirm-dialog" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
  <div class="card w-full max-w-md">
    <h3 class="text-lg font-semibold" id="confirm-title">Confirmar</h3>
    <p class="mt-2 text-sm text-surface-muted" id="confirm-message">¿Está seguro?</p>
    <div class="mt-5 flex justify-end gap-2">
      <button class="btn btn-outline" type="button" id="confirm-cancel">Cancelar</button>
      <button class="btn btn-danger" type="button" id="confirm-ok">Confirmar</button>
    </div>
  </div>
</div>
<script src="<?= e(cset_url('js/api.js')) ?>"></script>
<script src="<?= e(cset_url('js/admin.js')) ?>?v=4"></script>
<?php else: ?>
</main>
<?php endif; ?>
</body>
</html>

<?php
/**
 * Guard de autenticación para páginas del panel.
 * Incluir después de bootstrap + helpers.
 */
Auth::requireAuth(APP_BASE . '/login.php');
Auth::syncSessionFromDb();
$currentUser = Auth::userPayload();

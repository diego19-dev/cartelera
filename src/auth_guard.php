<?php
/**
 * Guard de sesión del panel: requireAuth + syncSessionFromDb.
 *
 * Incluir después de bootstrap y helpers. Define $currentUser (payload sin hash).
 */
Auth::requireAuth(APP_BASE . '/login.php');
Auth::syncSessionFromDb();
$currentUser = Auth::userPayload();

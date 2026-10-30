<?php

/**
 * Front controller REST: /tv/api/* → src/api.php → este dispatcher.
 *
 * Parsea PATH_INFO / REQUEST_URI y enruta por primer segmento:
 *
 * - auth: login, logout, me, csrf
 * - meta, branding (públicos)
 * - tv, tv/ping (públicos; feed de la cartelera)
 * - dashboard (sesión)
 * - academic, general, users, media, config, welcome (sesión; mutaciones + CSRF)
 * - center, gallery: 403 si están en PANEL_DISABLED_MODULES (la TV no pasa por aquí)
 *
 * CRUD típico: GET lista/show, POST create, PUT/PATCH/POST id update,
 * DELETE destroy, PATCH id/toggle.
 */
class ApiDispatcher {

    /** Enruta el request y termina con JSON (Security::jsonResponse). */
    public function dispatch() {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = $_SERVER['PATH_INFO'] ?? '';
        if ($uri === '' || $uri === '/') {
            $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '';
        }
        if (preg_match('#/api(?:/index\.php)?(.*)$#', $uri, $m)) {
            $path = trim($m[1], '/');
        } else {
            $path = trim($uri, '/');
        }

        $segments = $path === '' ? [] : explode('/', $path);
        $resource = $segments[0] ?? '';
        $id = isset($segments[1]) && ctype_digit($segments[1]) ? (int) $segments[1] : null;
        $action = $segments[2] ?? ($segments[1] ?? null);
        if ($id !== null && isset($segments[2])) {
            $action = $segments[2];
        } elseif ($id !== null && !isset($segments[2])) {
            $action = null;
        }

        // Auth routes
        if ($resource === 'auth') {
            $auth = new AuthController();
            $sub = $segments[1] ?? '';
            return match ($sub) {
                'login'  => $auth->login(),
                'logout' => $auth->logout(),
                'me'     => $auth->me(),
                'csrf'   => $auth->csrf(),
                default  => Security::jsonResponse(['ok' => false, 'error' => 'Not Found'], 404),
            };
        }

        if ($resource === 'meta') {
            return (new MetaController())->index();
        }

        if ($resource === 'branding') {
            return (new BrandingController())->index();
        }

        if ($resource === 'tv') {
            $c = new TvController();
            if (($segments[1] ?? '') === 'ping') {
                return $c->ping();
            }
            return $c->index();
        }

        if ($resource === 'dashboard') {
            return (new DashboardController())->index();
        }

        if ($resource === 'academic') {
            $c = new AcademicController();
            if ($method === 'GET' && $id === null) return $c->index();
            if ($method === 'GET' && $id) return $c->show($id);
            if ($method === 'POST' && $id === null) return $c->store();
            if (($method === 'PUT' || $method === 'PATCH' || $method === 'POST') && $id && $action !== 'toggle') return $c->update($id);
            if ($method === 'DELETE' && $id) return $c->destroy($id);
            if ($method === 'PATCH' && $id && $action === 'toggle') return $c->toggle($id);
        }

        if ($resource === 'center') {
            if (in_array('center', PANEL_DISABLED_MODULES, true)) {
                Security::jsonResponse(['ok' => false, 'error' => 'Módulo no disponible'], 403);
            }
            $c = new CenterController();
            if ($method === 'GET' && $id === null) return $c->index();
            if ($method === 'GET' && $id) return $c->show($id);
            if ($method === 'POST' && $id === null) return $c->store();
            if (($method === 'PUT' || $method === 'PATCH' || $method === 'POST') && $id && $action !== 'toggle') return $c->update($id);
            if ($method === 'DELETE' && $id) return $c->destroy($id);
            if ($method === 'PATCH' && $id && $action === 'toggle') return $c->toggle($id);
        }

        if ($resource === 'general') {
            $c = new GeneralController();
            if (($segments[1] ?? '') === 'indicators') {
                return $c->indicators();
            }
            if ($method === 'GET' && $id === null) return $c->index();
            if ($method === 'GET' && $id) return $c->show($id);
            if ($method === 'POST' && $id === null) return $c->store();
            if (($method === 'PUT' || $method === 'PATCH' || $method === 'POST') && $id && $action !== 'toggle') return $c->update($id);
            if ($method === 'DELETE' && $id) return $c->destroy($id);
            if ($method === 'PATCH' && $id && $action === 'toggle') return $c->toggle($id);
        }

        if ($resource === 'gallery') {
            if (in_array('gallery', PANEL_DISABLED_MODULES, true)) {
                Security::jsonResponse(['ok' => false, 'error' => 'Módulo no disponible'], 403);
            }
            $c = new GalleryController();
            if ($method === 'GET' && $id === null) return $c->index();
            if ($method === 'GET' && $id) return $c->show($id);
            if ($method === 'POST' && $id === null) return $c->store();
            if (($method === 'PUT' || $method === 'PATCH' || $method === 'POST') && $id && $action !== 'toggle') return $c->update($id);
            if ($method === 'DELETE' && $id) return $c->destroy($id);
            if ($method === 'PATCH' && $id && $action === 'toggle') return $c->toggle($id);
        }

        if ($resource === 'media') {
            $c = new MediaLibraryController();
            if ($method === 'GET') return $c->index();
            if ($method === 'POST') return $c->store();
            if ($method === 'DELETE' && $id) return $c->destroy($id);
        }

        if ($resource === 'users') {
            $c = new UsersController();
            if ($method === 'GET' && $id === null) return $c->index();
            if ($method === 'GET' && $id) return $c->show($id);
            if ($method === 'POST' && $id === null) return $c->store();
            if (($method === 'PUT' || $method === 'PATCH' || $method === 'POST') && $id && $action !== 'toggle') return $c->update($id);
            if ($method === 'PATCH' && $id && $action === 'toggle') return $c->toggle($id);
            if ($method === 'DELETE' && $id) return $c->destroy($id);
        }

        if ($resource === 'config') {
            $c = new ConfigController();
            $sub = $segments[1] ?? '';
            if ($sub === 'logo') {
                if ($method === 'POST') return $c->uploadLogo();
                if ($method === 'DELETE') return $c->deleteLogo();
            }
            if ($method === 'GET') return $c->index();
            if ($method === 'PUT' || $method === 'PATCH') return $c->update();
        }

        if ($resource === 'welcome') {
            $c = new WelcomeController();
            $sub = $segments[1] ?? '';
            if ($sub === 'background') {
                if ($method === 'POST') return $c->uploadBackground();
                if ($method === 'DELETE') return $c->deleteBackground();
            }
            if ($method === 'GET') return $c->index();
            if ($method === 'PUT' || $method === 'PATCH') return $c->update();
        }

        Security::jsonResponse(['ok' => false, 'error' => 'Not Found', 'path' => $path], 404);
    }
}

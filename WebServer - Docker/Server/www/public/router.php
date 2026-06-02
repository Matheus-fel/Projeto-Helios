<?php

require_once __DIR__ . '/controllers/EmpresaController.php';
require_once __DIR__ . '/controllers/UsuarioController.php';
require_once __DIR__ . '/controllers/UsinaController.php';
require_once __DIR__ . '/controllers/HistoricoChatController.php';

// ── CORS (ajuste a origem conforme necessário) ─────────────────────────────
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// ── Helpers ────────────────────────────────────────────────────────────────
function jsonError(string $msg, int $code = 404): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

// ── Parse da URI ───────────────────────────────────────────────────────────
$requestUri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$scriptDir     = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$path          = '/' . ltrim(substr($requestUri, strlen($scriptDir)), '/');
$method        = strtoupper($_SERVER['REQUEST_METHOD']);

$segments      = array_values(array_filter(explode('/', trim($path, '/'))));
$resource      = $segments[0] ?? '';
$id            = isset($segments[1]) && is_numeric($segments[1]) ? (int) $segments[1] : null;
$sub           = $segments[2] ?? '';           // sub-resource
$subAction     = $segments[3] ?? '';           // ação dentro da sub-resource

// ── Roteamento ─────────────────────────────────────────────────────────────
try {
    switch ($resource) {

        // ── /empresas ──────────────────────────────────────────────────────
        case 'empresas':
            $ctrl = new EmpresaController();
            if ($id === null) {
                match ($method) {
                    'GET'  => $ctrl->index(),
                    'POST' => $ctrl->store(),
                    default => jsonError('Método não permitido', 405),
                };
            } elseif ($sub === 'usinas') {
                $ctrl->usinas($id);
            } elseif ($sub === 'usuarios') {
                $ctrl->usuarios($id);
            } else {
                match ($method) {
                    'GET'    => $ctrl->show($id),
                    'PUT'    => $ctrl->update($id),
                    'DELETE' => $ctrl->destroy($id),
                    default  => jsonError('Método não permitido', 405),
                };
            }
            break;

        // ── /usuarios ──────────────────────────────────────────────────────
        case 'usuarios':
            $ctrl = new UsuarioController();
            if ($id === null && $sub === '') {
                if ($method === 'POST' && ($segments[1] ?? '') === 'login') {
                    $ctrl->login(); // POST /usuarios/login
                }
                match ($method) {
                    'GET'  => $ctrl->index(),
                    'POST' => $ctrl->store(),
                    default => jsonError('Método não permitido', 405),
                };
            } elseif ($id !== null && $sub === 'historico') {
                $ctrl->historico($id);
            } elseif ($id === null && ($segments[1] ?? '') === 'login') {
                if ($method === 'POST') $ctrl->login();
                else jsonError('Método não permitido', 405);
            } else {
                match ($method) {
                    'GET'    => $ctrl->show($id),
                    'PUT'    => $ctrl->update($id),
                    'DELETE' => $ctrl->destroy($id),
                    default  => jsonError('Método não permitido', 405),
                };
            }
            break;

        // ── /usinas ────────────────────────────────────────────────────────
        case 'usinas':
            $ctrl = new UsinaController();
            if ($id === null) {
                match ($method) {
                    'GET'  => $ctrl->index(),
                    'POST' => $ctrl->store(),
                    default => jsonError('Método não permitido', 405),
                };
            } elseif ($sub === 'telemetria') {
                if ($subAction === 'filtro')     $ctrl->telemetriaFiltrada($id);
                elseif ($subAction === 'media')  $ctrl->mediaHoraria($id);
                else                             $ctrl->telemetria($id);
            } else {
                match ($method) {
                    'GET'    => $ctrl->show($id),
                    'PUT'    => $ctrl->update($id),
                    'DELETE' => $ctrl->destroy($id),
                    default  => jsonError('Método não permitido', 405),
                };
            }
            break;

        // ── /historico ─────────────────────────────────────────────────────
        case 'historico':
            $ctrl = new HistoricoChatController();
            if ($id === null) {
                match ($method) {
                    'GET'  => $ctrl->index(),
                    'POST' => $ctrl->store(),
                    default => jsonError('Método não permitido', 405),
                };
            } else {
                match ($method) {
                    'GET'    => $ctrl->show($id),
                    'DELETE' => $ctrl->destroy($id),
                    default  => jsonError('Método não permitido', 405),
                };
            }
            break;

        // ── Rota não encontrada ────────────────────────────────────────────
        default:
            jsonError("Rota não encontrada: /$resource", 404);
    }
} catch (PDOException $e) {
    jsonError('Erro de banco de dados: ' . $e->getMessage(), 500);
} catch (Throwable $e) {
    jsonError('Erro interno: ' . $e->getMessage(), 500);
}

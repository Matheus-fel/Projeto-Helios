<?php
ob_start();
 
require_once __DIR__ . '/controllers/EmpresaController.php';
require_once __DIR__ . '/controllers/UsuarioController.php';
require_once __DIR__ . '/controllers/HistoricoChatController.php';
require_once __DIR__ . '/controllers/ChatController.php';
require_once __DIR__ . '/controllers/ConversaController.php';
 
// ── CORS ───────────────────────────────────────────────────────────────────
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

// ── Conexão com o banco (usada pelo ChatController e ConversaController) ───
function getPdo(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=mysql;port=3306;dbname=db_helios;charset=utf8mb4',
            'root',
            'root',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }
    return $pdo;
}
 
// ── Parse da URI ───────────────────────────────────────────────────────────
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$scriptDir  = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$path       = '/' . ltrim(substr($requestUri, strlen($scriptDir)), '/');
$method     = strtoupper($_SERVER['REQUEST_METHOD']);
 
$segments  = array_values(array_filter(explode('/', trim($path, '/'))));
$resource  = $segments[0] ?? '';
$id        = isset($segments[1]) && is_numeric($segments[1]) ? (int) $segments[1] : null;
$sub       = $segments[1] ?? '';
$subNum    = $segments[2] ?? '';
$subAction = $segments[3] ?? '';
 
// ── Roteamento ─────────────────────────────────────────────────────────────
try {
    switch ($resource) {
 
        case 'chat':
            if ($method !== 'POST') jsonError('Método não permitido', 405);
            (new ChatController(getPdo()))->chat();
            break;

        case 'conversas':
            $ctrl = new ConversaController(getPdo());

            if ($id === null) {
                match ($method) {
                    'GET'   => $ctrl->index(),
                    default => jsonError('Método não permitido', 405),
                };
            } elseif ($subNum === 'mensagens') {
                match ($method) {
                    'GET'   => $ctrl->mensagens($id),
                    default => jsonError('Método não permitido', 405),
                };
            } else {
                match ($method) {
                    'DELETE' => $ctrl->destroy($id),
                    default  => jsonError('Método não permitido', 405),
                };
            }
            break;
 
        case 'empresas':
            $ctrl = new EmpresaController();
            if ($id === null) {
                match ($method) {
                    'GET'  => $ctrl->index(),
                    'POST' => $ctrl->store(),
                    default => jsonError('Método não permitido', 405),
                };
            } elseif ($subNum === 'usuarios') {
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
 
        case 'usuarios':
            $ctrl = new UsuarioController();
 
            if ($method === 'POST' && $sub === 'login' && $id === null) {
                $ctrl->login();
                break;
            }
 
            if ($id === null) {
                match ($method) {
                    'GET'    => $ctrl->index(),
                    'POST'   => $ctrl->store(),
                    'PUT'    => $ctrl->update(),   // ◄ Aceita PUT vindo do Perfil (ID no JSON)
                    'DELETE' => $ctrl->destroy(),  // ◄ Aceita DELETE vindo do Perfil (ID no JSON)
                    default  => jsonError('Método não permitido', 405),
                };
                break;
            }
 
            if ($subNum === 'historico') {
                $ctrl->historico($id);
                break;
            }
 
            match ($method) {
                'GET'    => $ctrl->show($id),
                'PUT'    => $ctrl->update($id),
                'DELETE' => $ctrl->destroy($id),
                default  => jsonError('Método não permitido', 405),
            };
            break;
 
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
 
        default:
            jsonError("Rota não encontrada: /$resource", 404);
    }
} catch (PDOException $e) {
    jsonError('Erro de banco de dados: ' . $e->getMessage(), 500);
} catch (Throwable $e) {
    jsonError('Erro interno: ' . $e->getMessage(), 500);
}
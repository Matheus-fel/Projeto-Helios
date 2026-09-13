<?php
 
abstract class Controller {
 
    protected function json(mixed $data, int $status = 200): void {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
 
    protected function success(mixed $data, string $message = 'OK', int $status = 200): void {
        $this->json(['success' => true, 'message' => $message, 'data' => $data], $status);
    }
 
    protected function error(string $message, int $status = 400): void {
        $this->json(['success' => false, 'message' => $message], $status);
    }
 
    protected function body(): array {
        $raw = file_get_contents('php://input');
        return $raw ? (json_decode($raw, true) ?? []) : [];
    }
 
    protected function param(string $key, mixed $default = null): mixed {
        return isset($_GET[$key]) ? htmlspecialchars(trim($_GET[$key])) : $default;
    }

    // Validação de Segurança
    protected function requireRole(array $allowedRoles): void {
        $body = $this->body();
        $userRole = $_SERVER['HTTP_X_USER_ROLE'] ?? $body['solicitante_role'] ?? $_GET['solicitante_role'] ?? null;

        if (!$userRole || !in_array($userRole, $allowedRoles, true)) {
            $this->error('Acesso negado. Você não possui permissão para executar esta ação.', 403);
        }
    }
}
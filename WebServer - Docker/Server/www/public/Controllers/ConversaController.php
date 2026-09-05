<?php

class ConversaController {

    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * GET /conversas?usuario_id=X
     * Lista as conversas do usuário, mais recente primeiro (igual à barra lateral do Claude).
     */
    public function index(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $usuario_id = $_GET['usuario_id'] ?? null;

        if (!$usuario_id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'usuario_id é obrigatório.']);
            return;
        }

        $sql = "SELECT id, titulo, criado_em 
                FROM conversas 
                WHERE usuario_id = :usuario_id 
                ORDER BY criado_em DESC 
                LIMIT 50";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':usuario_id' => $usuario_id]);
        $conversas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'data' => $conversas]);
    }

    /**
     * GET /conversas/{id}/mensagens
     * Retorna todas as mensagens (pergunta + resposta) daquela conversa, em ordem.
     */
    public function mensagens(int $conversa_id): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $sql = "SELECT pergunta_tecnica, resposta_ia, data_interacao 
                FROM historico_chat 
                WHERE conversa_id = :conversa_id 
                ORDER BY data_interacao ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':conversa_id' => $conversa_id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'data' => $rows]);
    }

    /**
     * DELETE /conversas/{id}
     * Apaga a conversa e (via ON DELETE CASCADE) suas mensagens.
     */
    public function destroy(int $conversa_id): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $stmt = $this->pdo->prepare("DELETE FROM conversas WHERE id = :id");
        $stmt->execute([':id' => $conversa_id]);

        echo json_encode(['success' => true]);
    }
}

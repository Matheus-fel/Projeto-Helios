<?php

class ConversaController {

    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    
    public function index(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $usuario_id = $_GET['usuario_id'] ?? null;

        if (!$usuario_id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'usuario_id é obrigatório.']);
            return;
        }

        $q = mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 100);

        $sql    = "SELECT c.id, c.titulo, c.criado_em
                   FROM conversas c
                   WHERE c.usuario_id = :usuario_id";
        $params = [':usuario_id' => $usuario_id];

        if ($q !== '') {
            // escapa os curingas do LIKE para o usuário buscar "50%" ou "a_b" literalmente
            $like = '%' . addcslashes($q, '%_\\') . '%';

            $sql .= " AND (c.titulo LIKE :q_titulo
                       OR EXISTS (SELECT 1 FROM historico_chat h
                                  WHERE h.conversa_id = c.id
                                    AND (h.pergunta_tecnica LIKE :q_pergunta
                                      OR h.resposta_ia      LIKE :q_resposta)))";
            $params[':q_titulo']   = $like;
            $params[':q_pergunta'] = $like;
            $params[':q_resposta'] = $like;
        }

        $sql .= " ORDER BY c.criado_em DESC LIMIT 50";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $conversas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('[ConversaController::index] ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao listar conversas.']);
            return;
        }

        // "q" é devolvido para o front saber que o backend aplicou o filtro
        echo json_encode(['success' => true, 'q' => $q, 'data' => $conversas]);
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
     * 404 se a conversa não existe; 500 com mensagem amigável se o banco falhar.
     */
    public function destroy(int $conversa_id): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $stmt = $this->pdo->prepare("DELETE FROM conversas WHERE id = :id");
            $stmt->execute([':id' => $conversa_id]);

            if ($stmt->rowCount() === 0) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Conversa não encontrada.']);
                return;
            }

            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            error_log('[ConversaController::destroy] ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Não foi possível excluir a conversa. Tente novamente.']);
        }
    }
}
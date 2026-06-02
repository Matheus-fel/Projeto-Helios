<?php

require_once __DIR__ . '/Model.php';

class HistoricoChatModel extends Model {
    protected string $table = 'historico_chat';

    /** Registra uma interação de chat */
    public function registrar(int $usuarioId, string $pergunta, string $resposta, string $normas = ''): int {
        return $this->create([
            'usuario_id'        => $usuarioId,
            'pergunta_tecnica'  => $pergunta,
            'resposta_ia'       => $resposta,
            'normas_relacionadas' => $normas,
            'data_interacao'    => date('Y-m-d H:i:s'),
        ]);
    }

    /** Últimas N interações com nome do usuário */
    public function recentes(int $limit = 10): array {
        $stmt = $this->db->prepare(
            "SELECT h.*, u.nome AS nome_usuario
             FROM historico_chat h
             JOIN usuarios u ON u.id = h.usuario_id
             ORDER BY h.data_interacao DESC
             LIMIT ?"
        );
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
}

<?php

require_once __DIR__ . '/Model.php';

class EmpresaModel extends Model {
    protected string $table = 'empresas';

    /** Retorna todas as usinas de uma empresa */
    public function usinas(int $empresaId): array {
        $stmt = $this->db->prepare("SELECT * FROM usinas WHERE empresa_id = ?");
        $stmt->execute([$empresaId]);
        return $stmt->fetchAll();
    }

    /** Retorna todos os usuários de uma empresa */
    public function usuarios(int $empresaId): array {
        $stmt = $this->db->prepare("SELECT id, nome, email, nivel_acesso FROM usuarios WHERE empresa_id = ?");
        $stmt->execute([$empresaId]);
        return $stmt->fetchAll();
    }

    /** Busca uma empresa pelo seu código de acesso gerado */
    public function findByCodigo(string $codigo): array|false {
        $stmt = $this->db->prepare("SELECT id FROM {$this->table} WHERE codigo_acesso = ? LIMIT 1");
        $stmt->execute([$codigo]);
        return $stmt->fetch();
    }
}

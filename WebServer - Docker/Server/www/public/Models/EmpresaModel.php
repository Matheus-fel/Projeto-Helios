<?php

require_once __DIR__ . '/Model.php';

class EmpresaModel extends Model {
    protected string $table = 'empresas';

    /** Colunas que podem ser gravadas via API (whitelist) */
    private const COLUNAS = [
        'nome_comercial', 'razao_social', 'cnpj', 'telefone',
        'email_contato', 'cidade', 'uf', 'codigo_acesso', 'data_adesao',
    ];

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

    /** Busca uma empresa pelo CNPJ, ignorando pontuação (formatado ou só dígitos) */
    public function findByCnpj(string $cnpj): array|false {
        $digitos = preg_replace('/\D+/', '', $cnpj);
        $stmt = $this->db->prepare(
            "SELECT id FROM {$this->table}
             WHERE REGEXP_REPLACE(cnpj, '[^0-9]', '') = ? LIMIT 1"
        );
        $stmt->execute([$digitos]);
        return $stmt->fetch();
    }

    /** Insere a empresa gravando apenas as colunas permitidas */
    public function create(array $data): int {
        $dados = array_intersect_key($data, array_flip(self::COLUNAS));
        $cols  = array_keys($dados);
        $sql   = sprintf(
            "INSERT INTO {$this->table} (%s) VALUES (%s)",
            implode(', ', array_map(fn($c) => "`$c`", $cols)),
            implode(', ', array_fill(0, count($cols), '?'))
        );
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($dados));
        return (int) $this->db->lastInsertId();
    }

    /** Atualiza a empresa gravando apenas as colunas permitidas */
    public function update(int $id, array $data): bool {
        $dados = array_intersect_key($data, array_flip(self::COLUNAS));
        if (!$this->find($id)) {
            return false;
        }
        if (!$dados) {
            return true;
        }
        $set  = implode(', ', array_map(fn($c) => "`$c` = ?", array_keys($dados)));
        $stmt = $this->db->prepare("UPDATE {$this->table} SET $set WHERE id = ?");
        $stmt->execute([...array_values($dados), $id]);
        return true;
    }
}
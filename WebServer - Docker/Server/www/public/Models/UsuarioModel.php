<?php

require_once __DIR__ . '/Model.php';

class UsuarioModel extends Model {
    protected string $table = 'usuarios';

    /** Sobrescreve a busca geral para incluir dados da empresa vinculada */
    public function all(): array {
        $sql = "SELECT u.*, 
                       e.nome_comercial AS empresa_nome, 
                       e.cnpj AS empresa_cnpj, 
                       e.codigo_acesso AS empresa_codigo_acesso
                FROM usuarios u
                LEFT JOIN empresas e ON u.empresa_id = e.id
                ORDER BY u.id DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /** Sobrescreve a busca por ID para incluir dados da empresa vinculada */
    public function find(int $id): array|false {
        $sql = "SELECT u.*, 
                       e.nome_comercial AS empresa_nome, 
                       e.cnpj AS empresa_cnpj, 
                       e.codigo_acesso AS empresa_codigo_acesso
                FROM usuarios u
                LEFT JOIN empresas e ON u.empresa_id = e.id
                WHERE u.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /** Busca usuário por e-mail com dados da empresa vinculada */
    public function findByEmail(string $email): array|false {
        $sql = "SELECT u.*, 
                       e.nome_comercial AS empresa_nome, 
                       e.cnpj AS empresa_cnpj, 
                       e.codigo_acesso AS empresa_codigo_acesso
                FROM usuarios u
                LEFT JOIN empresas e ON u.empresa_id = e.id
                WHERE u.email = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    /** Histórico de chats do usuário */
    public function historico(int $usuarioId, int $limit = 20): array {
        $stmt = $this->db->prepare(
            "SELECT * FROM historico_chat WHERE usuario_id = ? ORDER BY data_interacao DESC LIMIT ?"
        );
        $stmt->execute([$usuarioId, $limit]);
        return $stmt->fetchAll();
    }

    /** Cria usuário com senha já em hash */
    public function criar(array $dados): int {
        if (isset($dados['senha'])) {
            $dados['senha_hash'] = password_hash($dados['senha'], PASSWORD_BCRYPT);
            unset($dados['senha']);
        }
        return $this->create($dados);
    }

    /** Valida credenciais e retorna o usuário (sem hash) ou false */
    public function autenticar(string $email, string $senha): array|false {
        $usuario = $this->findByEmail($email);
        if ($usuario && password_verify($senha, $usuario['senha_hash'])) {
            unset($usuario['senha_hash']);
            return $usuario;
        }
        return false;
    }
}

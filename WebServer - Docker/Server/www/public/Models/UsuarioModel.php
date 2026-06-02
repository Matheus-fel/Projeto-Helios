<?php

require_once __DIR__ . '/Model.php';

class UsuarioModel extends Model {
    protected string $table = 'usuarios';

    public function findByEmail(string $email): array|false {
        $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE email = ?");
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

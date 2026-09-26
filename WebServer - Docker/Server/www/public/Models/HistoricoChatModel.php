<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/MongoConnection.php';

class HistoricoChatModel {

    private PDO $pdo;
    private \MongoDB\Collection $mongo;

    public function __construct() {
        $this->pdo   = Database::getInstance()->getConnection();
        $this->mongo = MongoConnection::getInstance()->collection('conversas');
    }

    /**
     * Grava a interação nos DOIS bancos, na mesma chamada:
     * 1) MySQL — fonte oficial, se isso falhar a operação inteira falha.
     * 2) MongoDB — espelho pra treinar a IA depois; se falhar, só loga .
     */
    public function registrar(int $usuario_id, string $pergunta, string $resposta, string $normas = '', ?int $conversa_id = null): int {
        $sql = "INSERT INTO historico_chat (usuario_id, conversa_id, pergunta_tecnica, resposta_ia, normas_relacionadas) 
                VALUES (:usuario_id, :conversa_id, :pergunta, :resposta, :normas)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':usuario_id'  => $usuario_id,
            ':conversa_id' => $conversa_id,
            ':pergunta'    => $pergunta,
            ':resposta'    => $resposta,
            ':normas'      => $normas,
        ]);

        $id = (int) $this->pdo->lastInsertId();

        try {
            $this->mongo->insertOne([
                'historico_id'        => $id,
                'usuario_id'          => $usuario_id,
                'conversa_id'         => $conversa_id,
                'pergunta_tecnica'    => $pergunta,
                'resposta_ia'         => $resposta,
                'normas_relacionadas' => $normas,
                'data_interacao'      => new \MongoDB\BSON\UTCDateTime(),
            ]);
        } catch (\Throwable $e) {
            error_log('Falha ao gravar no MongoDB (historico_id=' . $id . '): ' . $e->getMessage());
        }

        return $id;
    }

    /**
     * Lê os registros mais recentes — continua vindo do MySQL (fonte oficial e rápida pra listagem).
     */
    public function recentes(int $limit = 10): array {
        $sql = "SELECT id, usuario_id, conversa_id, pergunta_tecnica, resposta_ia, normas_relacionadas, data_interacao 
                FROM historico_chat 
                ORDER BY data_interacao DESC 
                LIMIT :limit";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca um registro pelo id — vem do MySQL.
     */
    public function find(int $id): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM historico_chat WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Apaga dos DOIS bancos. Se o MySQL não tinha o registro, retorna false sem mexer no Mongo.
     */
    public function delete(int $id): bool {
        $stmt = $this->pdo->prepare("DELETE FROM historico_chat WHERE id = :id");
        $stmt->execute([':id' => $id]);

        $apagouMysql = $stmt->rowCount() > 0;

        if ($apagouMysql) {
            try {
                $this->mongo->deleteMany(['historico_id' => $id]);
            } catch (\Throwable $e) {
                error_log('Falha ao apagar do MongoDB (historico_id=' . $id . '): ' . $e->getMessage());
            }
        }

        return $apagouMysql;
    }
}
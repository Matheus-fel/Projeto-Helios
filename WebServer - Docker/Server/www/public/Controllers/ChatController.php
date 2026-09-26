<?php

require_once __DIR__ . '/../Models/HistoricoChatModel.php';

class ChatController {

    private string $ollamaUrl = 'http://host.docker.internal:11434/api/chat';
    private PDO $pdo;
    private HistoricoChatModel $historicoModel;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->historicoModel = new HistoricoChatModel();
    }

    public function chat(): void {
        header('Content-Type: application/json; charset=utf-8');

        $body = json_decode(file_get_contents('php://input'), true);
        $pergunta    = trim($body['pergunta'] ?? '');
        $usuario_id  = $body['usuario_id'] ?? null;
        $conversa_id = $body['conversa_id'] ?? null;

        if (empty($pergunta)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Campo pergunta é obrigatório.']);
            return;
        }

        $conversaNova = false;
        if (!$conversa_id && $usuario_id) {
            $conversa_id  = $this->criarNovaConversa($usuario_id, $pergunta);
            $conversaNova = true;
        }

        $nome = $this->buscarNomeUsuario($usuario_id);
        $messages = [];

        if ($nome) {
            $messages[] = [
                'role'    => 'system',
                'content' => "Você está conversando com {$nome}. Trate-o pelo nome quando fizer sentido, de forma natural, sem exagerar."
            ];
        }

        $messages = array_merge($messages, $this->buscarHistorico($usuario_id, $conversa_id));
        $messages[] = ['role' => 'user', 'content' => $pergunta];

        $resposta = $this->chamarOllama($messages, 4096, 0.7);

        if ($resposta === null) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Não foi possível conectar ao Ollama. Verifique se ele está rodando.'
            ]);
            return;
        }

        // Grava no MySQL + Mongo ao mesmo tempo, via HistoricoChatModel
        $this->historicoModel->registrar(
            (int) $usuario_id,
            $pergunta,
            $resposta,
            '',
            (int) $conversa_id
        );

        if ($conversaNova) {
            $this->atualizarTituloConversa($conversa_id, $pergunta, $resposta);
        }

        echo json_encode([
            'success'     => true,
            'resposta'    => $resposta,
            'conversa_id' => $conversa_id
        ]);
    }

    private function chamarOllama(array $messages, int $numCtx = 4096, float $temperature = 0.7): ?string
    {
        $payload = json_encode([
            'model'       => 'helios',
            'messages'    => $messages,
            'stream'      => false,
            'temperature' => $temperature,
            'options'     => [
                'num_ctx' => $numCtx
            ]
        ]);

        $ch = curl_init($this->ollamaUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 90,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError || $httpCode !== 200) {
            return null;
        }

        $data = json_decode($response, true);
        return $data['message']['content'] ?? null;
    }

    private function atualizarTituloConversa(int $conversa_id, string $pergunta, string $resposta): void
    {
        $resumoResposta = mb_substr($resposta, 0, 400);

        $promptTitulo = [
            [
                'role'    => 'system',
                'content' => 'Gere um título curto (3 a 6 palavras) para esta conversa, resumindo o assunto principal. '
                           . 'Responda APENAS com o título, sem aspas, sem pontuação final, sem explicações.'
            ],
            [
                'role'    => 'user',
                'content' => "Pergunta: {$pergunta}\n\nResposta: {$resumoResposta}"
            ]
        ];

        $titulo = $this->chamarOllama($promptTitulo, 512, 0.3);

        if (!$titulo) {
            return;
        }

        $titulo = trim($titulo, " \t\n\r\0\x0B\"'“”");
        $titulo = preg_replace('/\s+/', ' ', $titulo);
        $titulo = mb_substr($titulo, 0, 80);

        if (empty($titulo)) {
            return;
        }

        $stmt = $this->pdo->prepare("UPDATE conversas SET titulo = :titulo WHERE id = :id");
        $stmt->execute([':titulo' => $titulo, ':id' => $conversa_id]);
    }

    private function buscarNomeUsuario($usuario_id): ?string
    {
        if (!$usuario_id) return null;

        $sql = "SELECT nome FROM usuarios WHERE id = :usuario_id LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':usuario_id' => $usuario_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row['nome'] ?? null;
    }

    private function criarNovaConversa($usuario_id, string $pergunta): int
    {
        $tituloProvisorio = mb_substr($pergunta, 0, 60);

        $sql = "INSERT INTO conversas (usuario_id, titulo, criado_em) VALUES (:usuario_id, :titulo, NOW())";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':usuario_id' => $usuario_id,
            ':titulo'     => $tituloProvisorio
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    private function buscarHistorico($usuario_id, $conversa_id): array
    {
        if (!$usuario_id || !$conversa_id) return [];

        $sql = "SELECT pergunta_tecnica, resposta_ia 
                FROM historico_chat 
                WHERE usuario_id = :usuario_id 
                  AND conversa_id = :conversa_id
                ORDER BY data_interacao ASC 
                LIMIT 10";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':usuario_id'  => $usuario_id,
            ':conversa_id' => $conversa_id
        ]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $messages = [];
        foreach ($rows as $row) {
            $messages[] = ['role' => 'user', 'content' => $row['pergunta_tecnica']];
            $messages[] = ['role' => 'assistant', 'content' => $row['resposta_ia']];
        }

        return $messages;
    }
}
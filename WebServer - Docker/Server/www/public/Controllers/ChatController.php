<?php

class ChatController {

    private string $ollamaUrl = 'http://host.docker.internal:11434/api/chat';
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
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

        // 1. Se não veio conversa_id, é uma conversa nova -> cria uma (com título provisório)
        $conversaNova = false;
        if (!$conversa_id && $usuario_id) {
            $conversa_id  = $this->criarNovaConversa($usuario_id, $pergunta);
            $conversaNova = true;
        }

        // 2. Monta o contexto: nome do cliente (system) + histórico da conversa + pergunta atual
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

        // 3. Chama o Ollama para gerar a resposta
        $resposta = $this->chamarOllama($messages, 4096, 0.7);

        if ($resposta === null) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Não foi possível conectar ao Ollama. Verifique se ele está rodando.'
            ]);
            return;
        }


        $this->salvarMensagem($usuario_id, $conversa_id, $pergunta, $resposta);

        // 5. 
        if ($conversaNova) {
            $this->atualizarTituloConversa($conversa_id, $pergunta, $resposta);
        }

        echo json_encode([
            'success'     => true,
            'resposta'    => $resposta,
            'conversa_id' => $conversa_id
        ]);
    }

    /**
     * Faz uma chamada genérica ao Ollama e devolve o texto da resposta,
     * ou null se der erro de conexão/HTTP.
     */
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

    /**
     * Gera um título curto e descritivo (igual o Claude gera pra suas conversas)
     * a partir da primeira pergunta + resposta, e atualiza a conversa no banco.
     * Se der qualquer problema, simplesmente mantém o título provisório — não quebra o fluxo.
     */
    private function atualizarTituloConversa(int $conversa_id, string $pergunta, string $resposta): void
    {
        $resumoResposta = mb_substr($resposta, 0, 400); // não precisa mandar a resposta inteira pra gerar o título

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
            return; // Ollama falhou nessa chamada extra: mantém o título provisório, sem problema
        }

        // Limpeza básica: tira aspas, quebras de linha e limita tamanho
        $titulo = trim($titulo, " \t\n\r\0\x0B\"'“”");
        $titulo = preg_replace('/\s+/', ' ', $titulo);
        $titulo = mb_substr($titulo, 0, 80);

        if (empty($titulo)) {
            return;
        }

        $stmt = $this->pdo->prepare("UPDATE conversas SET titulo = :titulo WHERE id = :id");
        $stmt->execute([':titulo' => $titulo, ':id' => $conversa_id]);
    }

    /**
     * Busca o nome do usuário para ser injetado no contexto da IA,
     * assim como um sistema de IA já "sabe" quem está falando com ele.
     */
    private function buscarNomeUsuario($usuario_id): ?string
    {
        if (!$usuario_id) return null;

        $sql = "SELECT nome FROM usuarios WHERE id = :usuario_id LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':usuario_id' => $usuario_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row['nome'] ?? null;
    }

    /**
     * Cria uma nova conversa quando o front não manda conversa_id
     * (equivalente a abrir um "chat novo"). Usa um título provisório
     * (início da pergunta) até o título de verdade ser gerado pela IA.
     */
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

    /**
     * Busca o histórico apenas DESSA conversa (não mistura com outras).
     */
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

    private function salvarMensagem($usuario_id, $conversa_id, string $pergunta, string $resposta): void
    {
        if (!$usuario_id) return;

        $sql = "INSERT INTO historico_chat (usuario_id, conversa_id, pergunta_tecnica, resposta_ia) 
                VALUES (:usuario_id, :conversa_id, :pergunta, :resposta)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':usuario_id'  => $usuario_id,
            ':conversa_id' => $conversa_id,
            ':pergunta'    => $pergunta,
            ':resposta'    => $resposta
        ]);
    }
}
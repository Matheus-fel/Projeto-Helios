<?php

class ChatController {

    // No Windows com Docker Desktop, host.docker.internal funciona muito bem
    private string $ollamaUrl = 'http://host.docker.internal:11434/api/chat';

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

        $messages = $this->buscarHistorico($usuario_id, $conversa_id);
        $messages[] = ['role' => 'user', 'content' => $pergunta];

        $payload = json_encode([
            'model'      => 'Helios-AI',
            'messages'   => $messages,
            'stream'     => false,
            'temperature'=> 0.75,
            'max_tokens' => 1500,
            'options'    => [
                'num_ctx' => 8192
            ]
        ]);

        $ch = curl_init($this->ollamaUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 90,      // Ollama local pode ser lento
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'Não foi possível conectar ao Ollama. Verifique se ele está rodando.'
            ]);
            return;
        }

        if ($httpCode !== 200) {
            http_response_code(502);
            echo json_encode(['success' => false, 'message' => 'Erro ao comunicar com Ollama']);
            return;
        }

        $data = json_decode($response, true);
        $resposta = $data['message']['content'] ?? 'Helios não conseguiu responder no momento.';

        // Salvar histórico
        $this->salvarMensagem($usuario_id, $conversa_id, $pergunta, 'user');
        $this->salvarMensagem($usuario_id, $conversa_id, $resposta, 'assistant');

        echo json_encode([
            'success'     => true,
            'resposta'    => $resposta,
            'conversa_id' => $conversa_id
        ]);
    }

    private function buscarHistorico($usuario_id, $conversa_id): array
    {
        // TODO: implementar depois
        return [];
    }

    private function salvarMensagem($usuario_id, $conversa_id, string $conteudo, string $role): void
    {
        // TODO: implementar depois usando HistoricoChatController
    }
}
<?php

class ChatController {

    private string $apiKey;
    private string $apiUrl = 'https://api.anthropic.com/v1/messages';

    public function __construct() {
        // Coloque sua chave aqui ou use variável de ambiente
        $this->apiKey = getenv('ANTHROPIC_API_KEY') ?: 'SUA_CHAVE_AQUI';
    }

    public function chat(): void {
        header('Content-Type: application/json; charset=utf-8');

        $body = json_decode(file_get_contents('php://input'), true);
        $pergunta = trim($body['pergunta'] ?? '');

        if (!$pergunta) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Campo pergunta é obrigatório.']);
            return;
        }

        $payload = json_encode([
            'model'      => 'claude-sonnet-4-20250514',
            'max_tokens' => 1000,
            'system'     => 'Você é a Helios IA, assistente técnica especializada em energia solar, usinas fotovoltaicas, eólicas e hídricas, telemetria, normas ABNT e gestão de usinas. Responda em português do Brasil, de forma clara, técnica e prática.',
            'messages'   => [
                ['role' => 'user', 'content' => $pergunta]
            ]
        ]);

        $ch = curl_init($this->apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-api-key: ' . $this->apiKey,
                'anthropic-version: 2023-06-01',
            ],
            CURLOPT_TIMEOUT        => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro de conexão com a IA: ' . $curlError]);
            return;
        }

        $data = json_decode($response, true);

        if ($httpCode !== 200) {
            http_response_code(502);
            echo json_encode(['success' => false, 'message' => $data['error']['message'] ?? 'Erro na API da IA.']);
            return;
        }

        $resposta = $data['content'][0]['text'] ?? 'Sem resposta.';

        echo json_encode([
            'success'  => true,
            'resposta' => $resposta,
        ]);
    }
}

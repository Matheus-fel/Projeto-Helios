<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtHandler {
    private static function getSecretKey(): string {
        return $_ENV['JWT_SECRET'] ?? 'helios_secret_key_2026_super_segura_jwt_token';
    }

    /**
     * Gera um token JWT assinado com os dados do usuário.
     */
    public static function gerarToken(array $usuario): string {
        $agora = time();
        $expiracao = $agora + (8 * 3600); // Token válido por 8 horas

        $payload = [
            'iat' => $agora,                 // Emitido em
            'exp' => $expiracao,             // Expira em
            'sub' => $usuario['id'],          // ID do usuário
            'email' => $usuario['email'],
            'nivel_acesso' => $usuario['nivel_acesso']
        ];

        return JWT::encode($payload, self::getSecretKey(), 'HS256');
    }

    /**
     * Decodifica e valida o token JWT.
     * Retorna o payload como objeto se for válido, ou null se for inválido/expirado.
     */
    public static function validarToken(string $token): ?object {
        try {
            return JWT::decode($token, new Key(self::getSecretKey(), 'HS256'));
        } catch (\Exception $e) {
            return null; // Token expirado, alterado ou assinatura inválida
        }
    }
}
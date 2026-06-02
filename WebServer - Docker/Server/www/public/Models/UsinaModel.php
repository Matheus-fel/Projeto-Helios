<?php

require_once __DIR__ . '/Model.php';

class UsinaModel extends Model {
    protected string $table = 'usinas';

    /** Últimas leituras de telemetria de uma usina */
    public function telemetria(int $usinaId, int $limit = 50): array {
        $stmt = $this->db->prepare(
            "SELECT * FROM telemetria WHERE usina_id = ? ORDER BY data_hora DESC LIMIT ?"
        );
        $stmt->execute([$usinaId, $limit]);
        return $stmt->fetchAll();
    }

    /** Leituras de um parâmetro específico em intervalo de datas */
    public function telemetriaFiltrada(int $usinaId, string $parametro, string $de, string $ate): array {
        $stmt = $this->db->prepare(
            "SELECT * FROM telemetria
             WHERE usina_id = ? AND parametro = ? AND data_hora BETWEEN ? AND ?
             ORDER BY data_hora ASC"
        );
        $stmt->execute([$usinaId, $parametro, $de, $ate]);
        return $stmt->fetchAll();
    }

    /** Média de um parâmetro agrupada por hora */
    public function mediaHoraria(int $usinaId, string $parametro): array {
        $stmt = $this->db->prepare(
            "SELECT DATE_FORMAT(data_hora, '%Y-%m-%d %H:00:00') AS hora,
                    AVG(valor_leitura) AS media
             FROM telemetria
             WHERE usina_id = ? AND parametro = ?
             GROUP BY hora
             ORDER BY hora DESC
             LIMIT 24"
        );
        $stmt->execute([$usinaId, $parametro]);
        return $stmt->fetchAll();
    }
}

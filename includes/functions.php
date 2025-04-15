<?php
function performCheck($clientId) {
    global $pdo;
    
    // Obter dados do cliente
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
    $stmt->execute([$clientId]);
    $client = $stmt->fetch();
    
    if (!$client) {
        return ['success' => false, 'message' => 'Cliente não encontrado'];
    }
    
    // Verificar se temos dados de conexão
    if (empty($client['db_host']) || empty($client['db_user']) || empty($client['db_password']) || empty($client['db_name'])) {
        return ['success' => false, 'message' => 'Dados de conexão incompletos'];
    }
    
    try {
        // Conectar ao banco do cliente
        $dsn = "{$client['db_type']}:host={$client['db_host']};dbname={$client['db_name']}";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ];
        
        $clientDb = new PDO($dsn, $client['db_user'], $client['db_password'], $options);
        
        // Executar query baseada no tipo de banco e versão de infra
        $query = "";
        $result = [];
        
        if ($client['db_type'] === 'mysql' && $client['infra_version'] === 'atual') {
            $query = "SELECT
                a.aet,
                a.ae_desc,
                ser.station_name,
                ser.modality,
                envios.ip,
                envios.status,
                CASE WHEN envios.ae_title IS NULL THEN 'SEM ENVIOS'
                ELSE 'COM ENVIOS' END SITUACAO
                FROM pacsdb.ae a
                LEFT JOIN (
                    SELECT
                        asi.ae_title,
                        asi.ip,
                        CASE
                            WHEN counts.total_ips > 1 THEN 'Verificar IP'
                            ELSE ''
                        END AS status
                    FROM (
                        SELECT ae_title, ip
                        FROM pacsdb.animati_store_info
                        WHERE datetime > DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
                        GROUP BY ae_title, ip
                    ) asi
                    LEFT JOIN (
                        SELECT ae_title, COUNT(DISTINCT ip) AS total_ips
                        FROM pacsdb.animati_store_info
                        WHERE datetime > DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
                        GROUP BY ae_title
                    ) counts ON counts.ae_title = asi.ae_title
                ) AS envios ON envios.ae_title = a.aet
                JOIN pacsdb.series ser on ser.src_aet = envios.ae_title
                GROUP BY
                    a.aet,
                    a.ae_desc,
                    ser.station_name,
                    ser.modality,
                    envios.ip,
                    envios.status,
                    envios.ae_title
                ORDER BY SITUACAO, a.aet";
            
            $stmt = $clientDb->query($query);
            $result = $stmt->fetchAll();
        }
        // Adicionar outros casos para postgres, legado, etc...
        
        // Processar resultados
        $processed = [
            'check_date' => date('Y-m-d H:i:s'),
            'total_equipment' => count($result),
            'modalities' => [],
            'details' => $result
        ];
        
        // Agrupar por modalidade
        $modalityCount = [];
        foreach ($result as $row) {
            $modality = $row['modality'] ?? 'UNKNOWN';
            if (!isset($modalityCount[$modality])) {
                $modalityCount[$modality] = 0;
            }
            $modalityCount[$modality]++;
        }
        
        foreach ($modalityCount as $code => $count) {
            $processed['modalities'][] = [
                'code' => $code,
                'count' => $count
            ];
        }
        
        // Salvar no banco de dados
        $jsonResult = json_encode($processed);
        $filePath = "check_" . date('Ymd_His') . ".txt";
        $clientDir = "../../client_data/{$client['id']}";
        
        // Criar diretório se não existir
        if (!file_exists($clientDir)) {
            mkdir($clientDir, 0777, true);
        }
        
        // Salvar arquivo
        file_put_contents("$clientDir/$filePath", print_r($result, true));
        
        // Inserir no banco
        $stmt = $pdo->prepare("INSERT INTO checks 
                              (client_id, result_json, file_path, performed_by) 
                              VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $clientId,
            $jsonResult,
            $filePath,
            $_SESSION['user_id']
        ]);
        
        return ['success' => true, 'message' => 'Checagem realizada com sucesso'];
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Erro na conexão: ' . $e->getMessage()];
    }
}

function testConnection($clientId) {
    global $pdo;
    
    // Obter dados do cliente
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
    $stmt->execute([$clientId]);
    $client = $stmt->fetch();
    
    if (!$client) {
        return ['success' => false, 'message' => 'Cliente não encontrado'];
    }
    
    if (empty($client['db_host'])) {
        return ['success' => false, 'message' => 'Endereço do banco não configurado'];
    }
    
    // Testar conexão simples com ping
    $ping = exec("ping -c 1 " . escapeshellarg($client['db_host']));
    
    if (strpos($ping, '1 received')) {
        return ['success' => true, 'message' => 'Conexão bem-sucedida'];
    } else {
        return ['success' => false, 'message' => 'Falha no ping para ' . $client['db_host']];
    }
}

function podeEditarTipo($tipoAlvo) {
  global $tipoLogado;
  if ($tipoLogado === 'master') return true;
  if ($tipoLogado === 'infra') return in_array($tipoAlvo, ['infra', 'financeiro']);
  if ($tipoLogado === 'financeiro') return $tipoAlvo === 'financeiro';
  return false;
}


?>
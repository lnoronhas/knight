<?php
while (ob_get_level())
    ob_end_clean();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/checagem_errors.log');

// Verificar se a saída está limpa
if (headers_sent()) {
    error_log("Headers já enviados antes do script");
    exit;
}

// Definir cabeçalho JSON primeiro
header('Content-Type: application/json');

// Incluir arquivos necessários
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/defs.php';
require_once __DIR__ . '/../includes/functions.php';

// Verificar se o request é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['success' => false, 'message' => 'Método não permitido']));
}

// Iniciar sessão e verificar autenticação
session_start();
if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Não autorizado']));
}

// Validar entrada
$clienteId = filter_input(INPUT_POST, 'cliente_id', FILTER_VALIDATE_INT);
if (!$clienteId || $clienteId < 1) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'ID do cliente inválido']));
}

try {
    // 1. Buscar informações do cliente
    $stmt = $pdo->prepare("SELECT c.*, cx.* FROM clientes c 
                          LEFT JOIN conexoes cx ON cx.cliente_id = c.id 
                          WHERE c.id = ? LIMIT 1");
    $stmt->execute([$clienteId]);
    $cliente = $stmt->fetch();

    if (!$cliente) {
        throw new Exception("Cliente não encontrado");
    }

    $tipoChecagem = filter_input(INPUT_POST, 'tipo_checagem', FILTER_SANITIZE_STRING) ?: 'status';
    if (!in_array($tipoChecagem, ['status', 'detalhes', 'ambos'])) {
        http_response_code(400);
        die(json_encode([
            'success' => false,
            'message' => 'Tipo de checagem inválido'
        ]));
    }

    // 2. Executar checagem conforme o tipo de conexão
    if ($cliente['tipo_banco'] === 'mysql' && $cliente['versao_infra'] === 'atual') {
        $resultado = checarMySQLAtual($cliente, $tipoChecagem);
    } elseif ($cliente['tipo_banco'] === 'postgres' && $cliente['versao_infra'] === 'atual') {
        $resultado = checarPostgresAtual($cliente, $tipoChecagem);
    } elseif ($cliente['tipo_banco'] === 'mysql' && $cliente['versao_infra'] === 'legado') {
        $resultado = checarMySQLLegado($cliente, $tipoChecagem);
    } elseif ($cliente['tipo_banco'] === 'postgres' && $cliente['versao_infra'] === 'legado') {
        $resultado = checarPostgresLegado($cliente, $tipoChecagem);
    } else {
        throw new Exception("Tipo de banco/versão não suportado");
    }

    // 3. Registrar a checagem no banco de dados
    $tipoRegistro = $tipoChecagem === 'status' ? 'cadastrados' : 'completa';
    $resumo = $tipoChecagem === 'status' ? ($resultado['resumo_status'] ?? $resultado['resumo']) :
        ($resultado['resumo_detalhes'] ?? $resultado['resumo']);

    $stmt = $pdo->prepare("INSERT INTO checagens 
                  (cliente_id, data, resultado_json, tipo_checagem, status, resumo) 
                  VALUES (?, NOW(), ?, ?, ?, ?)");

    $detalhesJson = json_encode($resultado['detalhes']);

    $stmt->execute([
        $clienteId,
        $detalhesJson,
        $tipoRegistro,
        $resultado['status'],
        $resumo
    ]);

    // Checagem cadastrados
    $stmt->execute([
        $clienteId,
        json_encode(['status_aparelhos' => $resultado['detalhes']['status_aparelhos']]),
        'cadastrados',
        $resultado['status'],
        $resultado['resumo_cadastrados'] ?? $resultado['resumo']
    ]);

    // Checagem completa
    $stmt->execute([
        $clienteId,
        json_encode(['detalhes_aparelhos' => $resultado['detalhes']['detalhes_aparelhos']]),
        'completa',
        $resultado['status'],
        $resultado['resumo_completa'] ?? $resultado['resumo']
    ]);

    // 4. Retornar resposta de sucesso
    echo json_encode([
        'success' => true,
        'message' => 'Checagem concluída com sucesso',
        'resultado' => $resultado
    ]);

} catch (Exception $e) {
    // Registrar erro no log
    error_log('Erro na checagem: ' . $e->getMessage());

    // Retornar erro
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro na checagem: ' . $e->getMessage()
    ]);
}

// Funções específicas para cada tipo de checagem
function checarMySQLAtual($cliente, $tipoChecagem = 'status')
{
    try {
        $pdo = conectarBancoCliente($cliente);
        $resultado = [];

        // Simplificar a lógica de checagem baseada no tipo
        switch ($tipoChecagem) {
            case 'status':
                $queryStatus = "SELECT
                    a.aet,
                    CASE
                        WHEN COUNT(asi.study_uid) > 1 THEN 'ENVIANDO'
                        ELSE 'SEM ENVIOS'
                    END AS situacao
                    FROM pacsdb.ae a
                    LEFT JOIN pacsdb.animati_store_info asi
                        ON asi.ae_title = a.aet
                        AND asi.datetime > DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
                    GROUP BY a.aet";

                $status = $pdo->query($queryStatus)->fetchAll(PDO::FETCH_ASSOC);
                $comEnvios = count(array_filter($status, fn($item) => $item['situacao'] === 'ENVIANDO'));
                $semEnvios = count($status) - $comEnvios;

                $resultado['status_aparelhos'] = $status;
                $resumo = "$comEnvios aparelhos enviando, $semEnvios sem envios";
                break;

            case 'detalhes':
                $queryAparelhos = "SELECT
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
                    WHEN COUNT(DISTINCT asi.ip) > 1 THEN 'Verificar IP'
                    ELSE ''
                END AS status
            FROM pacsdb.animati_store_info asi
            GROUP BY asi.ae_title, asi.ip
        ) AS envios ON envios.ae_title = a.aet
        LEFT JOIN pacsdb.series ser on ser.src_aet = a.aet
        WHERE a.aet not in ('JBOSS','ANIMATI_PACS')
        GROUP BY a.aet, a.ae_desc, ser.station_name, ser.modality, envios.ip, envios.status";

                $aparelhos = $pdo->query($queryAparelhos)->fetchAll(PDO::FETCH_ASSOC);
                $comEnviosDetalhes = count(array_filter($aparelhos, fn($item) => $item['SITUACAO'] === 'COM ENVIOS'));

                $resultado['detalhes_aparelhos'] = $aparelhos;
                $resumo = "$comEnviosDetalhes aparelhos com envios detalhados";
                break;

            case 'ambos':
                // Executar ambas as checagens
                $resultadoStatus = checarMySQLAtual($cliente, 'status');
                $resultadoDetalhes = checarMySQLAtual($cliente, 'detalhes');

                $resultado = array_merge(
                    $resultadoStatus['detalhes'],
                    $resultadoDetalhes['detalhes']
                );
                $resumo = "Checagem completa concluída";
                break;

            default:
                throw new Exception("Tipo de checagem inválido");
        }

        return [
            'status' => 'sucesso',
            'message' => 'Checagem concluída com sucesso',
            'resumo' => $resumo,
            'detalhes' => $resultado
        ];

    } catch (PDOException $e) {
        throw new Exception('Erro ao executar checagem: ' . $e->getMessage());
    }
}

function checarPostgresAtual($cliente)
{
    // Similar ao MySQL mas com adaptações para PostgreSQL
    // Implementação similar à função anterior com ajustes de sintaxe
    // ...
}

function checarMySQLLegado($cliente)
{
    return [
        'status' => 'aviso',
        'resumo' => 'Checagem para MySQL legado não implementada',
        'detalhes' => []
    ];
}

function checarPostgresLegado($cliente)
{
    return [
        'status' => 'aviso',
        'resumo' => 'Checagem para PostgreSQL legado não implementada',
        'detalhes' => []
    ];
}

function conectarBancoCliente($cliente)
{
    try {
        $port = $cliente['tipo_banco'] === 'mysql' ? 3306 : 5432;

        // Formatar o IPv6 corretamente
        $ipv6 = $cliente['ipv6'];
        if (strpos($ipv6, ':') !== false && !preg_match('/^\[.+\]$/', $ipv6)) {
            $ipv6 = "[$ipv6]"; // Adiciona colchetes se necessário
        }

        $dbname = $cliente['dbname'] ?? 'pacsdb';

        $dsn = "{$cliente['tipo_banco']}:host={$ipv6};port={$port};dbname={$dbname}";

        error_log("Tentando conectar com DSN: $dsn");

        $pdo = new PDO($dsn, $cliente['usuario'], $cliente['senha'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 10,
            PDO::ATTR_PERSISTENT => false
        ]);

        error_log("Conexão estabelecida com sucesso");
        return $pdo;

    } catch (PDOException $e) {
        error_log("Erro de conexão completo: " . $e->getMessage());
        throw new Exception("Não foi possível conectar ao banco: " . $e->getMessage());
    }
}
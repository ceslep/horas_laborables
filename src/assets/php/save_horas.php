<?php
/**
 * save_horas.php
 * 
 * Recibe los datos de horas laborables y los guarda en Google Sheets.
 */

require __DIR__ . '/vendor/autoload.php';

use Google\Client;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;

// Configuración de archivos
const SERVICE_ACCOUNT_KEY_FILE = __DIR__ . '/assets/serviceaccount.json';

// CORS y Cabeceras
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido. Use POST.');
    }

    // Leer datos
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON inválido.');
    }

    // El frontend enviará un objeto con spreadsheetId, worksheetTitle y values (34 valores: email, nombre, mes, y 31 días)
    if (!isset($data['values']) || !is_array($data['values'])) {
        throw new Exception('Datos incompletos. Se espera el campo "values".');
    }

    // Obtener parámetros dinámicos del payload
    $spreadsheetId = $data['spreadsheetId'] ?? '1UW_dbtJEFJeOjCg323HJPaacqPIztw_9bGI5Rw6HRxQ';
    $worksheetTitle = $data['worksheetTitle'] ?? date('Y');
    $range = $worksheetTitle . '!A1:AI1000'; // Forzar lectura de más filas

    // Inicializar Google Client
    $client = new Client();
    $client->setApplicationName('Horas laborables');
    $client->setScopes([Sheets::SPREADSHEETS]);
    
    if (!file_exists(SERVICE_ACCOUNT_KEY_FILE)) {
        throw new Exception('Archivo de credenciales no encontrado en el servidor.');
    }
    
    $client->setAuthConfig(SERVICE_ACCOUNT_KEY_FILE);

    $service = new Sheets($client);

// Preparar los datos finales sin timestamp
$newData = $data['values']; // [timestamp, email, nombre, mes, d1, d2, ..., d31]

    // Debug: Log los datos recibidos
    error_log("DEBUG: newData[0]=" . $newData[0] . " [timestamp]");
    error_log("DEBUG: newData[1]=" . $newData[1] . " [email]");
    error_log("DEBUG: newData[2]=" . $newData[2] . " [nombre]");
    error_log("DEBUG: newData[3]=" . $newData[3] . " [mes]");

// Leer todos los valores para búsqueda completa
    $response = $service->spreadsheets_values->get($spreadsheetId, $range);
    $allValues = $response->getValues();
    
$foundRowIndex = -1;

// TEMPORAL: Forzar siempre append para probar
    $foundRowIndex = -1; // Siempre -1 para forzar append
    error_log("DEBUG: MODO FORZADO - siempre hará append");

    // Preparar el cuerpo para la operación
    $body = new ValueRange([
        'values' => [$newData]
    ]);
    $params = ['valueInputOption' => 'RAW'];

$returnedRowIndex = -1; // Variable para almacenar el rowIndex

    error_log("DEBUG: foundRowIndex=$foundRowIndex");

    if ($foundRowIndex !== -1) {
        // Actualizar fila existente - preservando datos históricos
        // Solo actualizamos si la combinación C:D coincide exactamente
        // Calculamos el rango exacto para la fila encontrada: A{index}:AI{index}
        error_log("DEBUG: Ejecutando UPDATE en fila $foundRowIndex - coincidencia para Nombre='" . $newData[2] . "' Mes='" . $newData[3] . "'");
        $updateRange = $worksheetTitle . "!A$foundRowIndex:AI$foundRowIndex";
        $result = $service->spreadsheets_values->update($spreadsheetId, $updateRange, $body, $params);
        $message = 'Registro actualizado exitosamente (misma combinación Nombre-Mes).';
        $returnedRowIndex = $foundRowIndex; // Ya tenemos el índice
} else {
        // Crear nueva fila (Append) - nueva combinación Nombre-Mes
        // Preserva todos los datos históricos existentes
        error_log("DEBUG: Ejecutando APPEND FORZADO - Nombre='" . $newData[2] . "' Mes='" . $newData[3] . "'");
        
        // Usar un rango específico para append
        $appendRange = $worksheetTitle . '!A1';
        $result = $service->spreadsheets_values->append($spreadsheetId, $appendRange, $body, $params);
        
        // Log del resultado para depuración
        if (isset($result->updates->updatedRange)) {
            error_log("DEBUG: Append result updatedRange=" . $result->updates->updatedRange);
        }
        if (isset($result->updates->updatedRows)) {
            error_log("DEBUG: Append result updatedRows=" . $result->updates->updatedRows);
        }
        
        $message = 'Nuevo registro creado exitosamente (combinación Nombre-Mes única).';
        
        // Extraer el rowIndex de la respuesta de append
        if (isset($result->updates->updatedRange)) {
            preg_match('/A(\d+):/', $result->updates->updatedRange, $matches);
            if (isset($matches[1])) {
                $returnedRowIndex = (int)$matches[1];
            }
        }
    }

    echo json_encode([
        'success' => true,
        'message' => $message,
        'updated' => $foundRowIndex !== -1,
        'rowIndex' => $returnedRowIndex // Devolver el rowIndex
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

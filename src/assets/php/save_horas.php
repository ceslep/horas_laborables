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
    $range = $worksheetTitle . '!A:AI';

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
    $newData = $data['values']; // [email, nombre, mes, d1, d2, ..., d31]

    // Obtener valores actuales para buscar duplicados
    $response = $service->spreadsheets_values->get($spreadsheetId, $range);
    $allValues = $response->getValues();
    
    $foundRowIndex = -1;

    if ($allValues) {
        foreach ($allValues as $idx => $row) {
// Verificar si coinciden: Email(0), Docente(1), Mes(2)
            if (isset($row[0], $row[1], $row[2]) &&
                $row[0] == $newData[0] &&
                $row[1] == $newData[1] &&
                $row[2] == $newData[2]) {
                $foundRowIndex = $idx + 1; // Las filas en Sheets son base 1
                break;
            }
        }
    }

    // Preparar el cuerpo para la operación
    $body = new ValueRange([
        'values' => [$newData]
    ]);
    $params = ['valueInputOption' => 'RAW'];

    $returnedRowIndex = -1; // Variable para almacenar el rowIndex

    if ($foundRowIndex !== -1) {
        // Actualizar fila existente
        // Calculamos el rango exacto para la fila encontrada: A{index}:AI{index}
        $updateRange = $worksheetTitle . "!A$foundRowIndex:AI$foundRowIndex";
        $result = $service->spreadsheets_values->update($spreadsheetId, $updateRange, $body, $params);
        $message = 'Registro actualizado exitosamente.';
        $returnedRowIndex = $foundRowIndex; // Ya tenemos el índice
    } else {
        // Crear nueva fila (Append)
        $result = $service->spreadsheets_values->append($spreadsheetId, $range, $body, $params);
        $message = 'Registro guardado exitosamente.';
        
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

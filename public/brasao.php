<?php
/**
 * Servir brasões diretamente sem passar pelo Laravel
 * Para evitar problemas com middleware e autenticação
 */

// Obter o filename do query string
$filename = $_GET['f'] ?? '';

if (empty($filename)) {
    http_response_code(400);
    die('Filename not provided');
}

// Sanitizar filename (evitar path traversal)
$filename = basename($filename);

// Caminho completo do arquivo
$filePath = __DIR__ . '/storage/brasoes/' . $filename;

// Verificar se o arquivo existe
if (!file_exists($filePath)) {
    http_response_code(404);
    die('File not found');
}

// Definir headers
$mimeType = mime_content_type($filePath);
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: public, max-age=31536000');

// Servir o arquivo
readfile($filePath);
exit;

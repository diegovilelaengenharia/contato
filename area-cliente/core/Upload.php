<?php
class Upload {
    private static $allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'zip', 'xls', 'xlsx', 'dwg', 'dxf', 'webp', 'gif'];
    private static $allowed_mimes = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/zip',
        'application/x-zip-compressed',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'image/vnd.dwg',
        'image/x-dwg',
        'application/acad',
        'application/x-acad',
        'application/autocad_dwg',
        'application/dwg',
        'application/x-dwg',
        'drawing/dwg',
        'image/vnd.dxf',
        'image/x-dxf',
        'application/dxf',
        'application/x-dxf',
        'application/autocad_dxf',
        'drawing/dxf',
        'application/octet-stream' // Muitas vezes arquivos CAD binários são lidos como octet-stream
    ];
    private static $max_size = 10485760; // 10MB

    /**
     * Valida e processa um upload de arquivo.
     * @param array $file O array do $_FILES['campo']
     * @param string $destination_dir Diretório de destino
     * @param string $prefix Prefixo para o nome do arquivo
     * @return array [success => bool, message => string, file_path => string|null]
     */
    public static function process($file, $destination_dir, $prefix = '') {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Erro no upload: ' . self::getUploadErrorMessage($file['error'])];
        }

        if ($file['size'] > self::$max_size) {
            return ['success' => false, 'message' => 'O arquivo excede o limite de 10MB.'];
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, self::$allowed_extensions)) {
            return ['success' => false, 'message' => 'Extensão de arquivo não permitida.'];
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime_type = $finfo->file($file['tmp_name']);
        if (!in_array($mime_type, self::$allowed_mimes)) {
            return ['success' => false, 'message' => 'Tipo de conteúdo inválido detectado.'];
        }

        // Sanitização de nome: prefixo_timestamp_aleatorio.ext
        $new_name = ($prefix ? $prefix . '_' : '') . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
        $final_path = rtrim($destination_dir, '/') . '/' . $new_name;

        if (!is_dir($destination_dir)) {
            mkdir($destination_dir, 0755, true);
        }

        if (move_uploaded_file($file['tmp_name'], $final_path)) {
            return ['success' => true, 'message' => 'Upload realizado com sucesso.', 'file_path' => $final_path];
        }

        return ['success' => false, 'message' => 'Falha ao mover arquivo para o destino.'];
    }

    private static function getUploadErrorMessage($err_code) {
        switch ($err_code) {
            case UPLOAD_ERR_INI_SIZE: return 'O arquivo excede o limite definido no servidor.';
            case UPLOAD_ERR_FORM_SIZE: return 'O arquivo excede o limite definido no formulário.';
            case UPLOAD_ERR_PARTIAL: return 'O upload foi feito apenas parcialmente.';
            case UPLOAD_ERR_NO_FILE: return 'Nenhum arquivo foi enviado.';
            default: return 'Erro desconhecido no upload.';
        }
    }
}

<?php
/**
 * Classe Google Drive API - Integrazione diretta con Google Drive API v3
 * Bypassa Use-your-Drive Client per evitare problemi di cache e permessi
 */

defined('ABSPATH') || exit;

class MEP_Google_Drive_API {
    
    /**
     * @var string Access token ottenuto da Use-your-Drive
     */
    private static $access_token = null;
    
    /**
     * @var string Base URL Google Drive API v3
     */
    const API_BASE_URL = 'https://www.googleapis.com/drive/v3';
    
    /**
     * Ottieni access token OAuth (dalla nostra classe OAuth)
     */
    public static function get_access_token() {
        if (self::$access_token !== null) {
            return self::$access_token;
        }
        
        $token = MEP_Google_OAuth::get_access_token();
        
        if (is_wp_error($token)) {
            MEP_Helpers::log_error("❌ Errore ottenimento token OAuth", $token->get_error_message());
            return $token;
        }
        
        self::$access_token = $token;
        MEP_Helpers::log_info("✅ Token OAuth ottenuto");
        
        return $token;
    }
    
    /**
     * Lista cartelle e file in una cartella Google Drive (per browser navigabile)
     */
    public static function list_folders_and_files($folder_id = 'root', $include_files = true, $mime_type_filter = 'image/') {
        if (empty($folder_id)) {
            $folder_id = 'root';
        }
        
        $token = self::get_access_token();
        if (is_wp_error($token)) {
            return $token;
        }
        
        MEP_Helpers::log_info("📂 Lista cartelle/file nella cartella: {$folder_id}");
        
        try {
            $result = [
                'folders'   => [],
                'files'     => [],
                'folder_id' => $folder_id
            ];
            
            // 1. Lista CARTELLE
            $folders_query = "'" . $folder_id . "' in parents and mimeType='application/vnd.google-apps.folder' and trashed=false";
            
            $folders_params = [
                'q'       => $folders_query,
                'fields'  => 'files(id,name,mimeType,modifiedTime,iconLink)',
                'pageSize'=> 1000,
                'orderBy' => 'name'
            ];
            
            $folders_url = self::API_BASE_URL . '/files?' . http_build_query($folders_params);
            
            $folders_response = wp_remote_get($folders_url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Accept'        => 'application/json'
                ],
                'timeout' => 30
            ]);
            
            if (!is_wp_error($folders_response) && wp_remote_retrieve_response_code($folders_response) === 200) {
                $folders_data = json_decode(wp_remote_retrieve_body($folders_response), true);
                if (isset($folders_data['files'])) {
                    $result['folders'] = $folders_data['files'];
                }
            }
            
            // 2. Lista FILE (solo se richiesto)
            if ($include_files) {
                $files_query = "'" . $folder_id . "' in parents and trashed=false";
                
                if (!empty($mime_type_filter)) {
                    $files_query .= " and mimeType contains '" . $mime_type_filter . "'";
                }
                
                $files_params = [
                    'q'       => $files_query,
                    'fields'  => 'files(id,name,mimeType,size,thumbnailLink,webContentLink,iconLink,modifiedTime)',
                    'pageSize'=> 1000,
                    'orderBy' => 'name'
                ];
                
                $files_url = self::API_BASE_URL . '/files?' . http_build_query($files_params);
                
                $files_response = wp_remote_get($files_url, [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $token,
                        'Accept'        => 'application/json'
                    ],
                    'timeout' => 30
                ]);
                
                if (!is_wp_error($files_response) && wp_remote_retrieve_response_code($files_response) === 200) {
                    $files_data = json_decode(wp_remote_retrieve_body($files_response), true);
                    if (isset($files_data['files'])) {
                        $result['files'] = $files_data['files'];
                    }
                }
            }
            
            MEP_Helpers::log_info("✅ Trovate " . count($result['folders']) . " cartelle e " . count($result['files']) . " file");
            
            return $result;
            
        } catch (Throwable $e) {
            MEP_Helpers::log_error("Eccezione list_folders_and_files", [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString()
            ]);
            return new WP_Error('exception', $e->getMessage());
        }
    }
    
    /**
     * Lista file in una cartella Google Drive
     */
    public static function list_files_in_folder($folder_id, $mime_type_filter = 'image/') {
        if (empty($folder_id)) {
            return new WP_Error('empty_folder_id', __('ID cartella vuoto', 'my-event-plugin'));
        }
        
        $token = self::get_access_token();
        if (is_wp_error($token)) {
            return $token;
        }
        
        MEP_Helpers::log_info("Lista file nella cartella: {$folder_id}");
        
        try {
            $query = "'" . $folder_id . "' in parents and trashed=false";
            
            if (!empty($mime_type_filter)) {
                $query .= " and mimeType contains '" . $mime_type_filter . "'";
            }
            
            $params = [
                'q'        => $query,
                'fields'   => 'files(id,name,mimeType,size,thumbnailLink,webContentLink,iconLink)',
                'pageSize' => 1000,
                'orderBy'  => 'name'
            ];
            
            $url = self::API_BASE_URL . '/files?' . http_build_query($params);
            
            $response = wp_remote_get($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Accept'        => 'application/json'
                ],
                'timeout' => 30
            ]);
            
            if (is_wp_error($response)) {
                MEP_Helpers::log_error("Errore HTTP Google Drive API", $response->get_error_message());
                return new WP_Error('http_error', $response->get_error_message());
            }
            
            $status_code = wp_remote_retrieve_response_code($response);
            $body        = wp_remote_retrieve_body($response);
            
            if ($status_code !== 200) {
                MEP_Helpers::log_error("Google Drive API errore {$status_code}", $body);
                return new WP_Error('api_error', sprintf(
                    __('Google Drive API errore %d: %s', 'my-event-plugin'),
                    $status_code,
                    $body
                ));
            }
            
            $data = json_decode($body, true);
            
            if (!isset($data['files'])) {
                MEP_Helpers::log_error("Risposta API invalida", $data);
                return new WP_Error('invalid_response', __('Risposta API non valida', 'my-event-plugin'));
            }
            
            $files = $data['files'];
            
            MEP_Helpers::log_info("Trovati " . count($files) . " file nella cartella");
            
            return $files;
            
        } catch (Throwable $e) {
            MEP_Helpers::log_error("Eccezione list_files_in_folder", [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString()
            ]);
            return new WP_Error('exception', $e->getMessage());
        }
    }
    
    /**
     * Ottieni URL thumbnail per un file
     */
    public static function get_thumbnail_url($file_id, $size = 400) {
        if (empty($file_id)) {
            return false;
        }
        
        $token = self::get_access_token();
        if (is_wp_error($token)) {
            return false;
        }
        
        $thumbnail_url = "https://drive.google.com/thumbnail?id={$file_id}&sz=w{$size}";
        
        return $thumbnail_url;
    }
    
    /**
     * Scarica un file da Google Drive e importalo in WordPress Media Library
     */
    public static function download_and_import_file($file_id, $file_name) {
        if (empty($file_id)) {
            MEP_Helpers::log_error("❌ Import: ID file vuoto");
            return new WP_Error('empty_file_id', __('ID file vuoto', 'my-event-plugin'));
        }

        $token = self::get_access_token();
        if (is_wp_error($token)) {
            MEP_Helpers::log_error("❌ Import: Token non valido", $token->get_error_message());
            return $token;
        }

        // Deduplication: if this Google Drive file was already imported, return existing attachment.
        $existing = get_posts([
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'meta_key'       => '_gdrive_file_id',
            'meta_value'     => $file_id,
            'numberposts'    => 1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
        ]);

        if (!empty($existing)) {
            MEP_Helpers::log_info("⚡ File già importato (attachment ID: {$existing[0]}), salto download: {$file_name}");
            return $existing[0];
        }

        MEP_Helpers::log_info("📥 Download file: {$file_name} ({$file_id})");

        try {
            $url = self::API_BASE_URL . '/files/' . $file_id . '?alt=media';

            MEP_Helpers::log_info("🌐 Download da: " . $url);

            $response = wp_remote_get($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token
                ],
                'timeout'   => 120,
                'sslverify' => false
            ]);

            if (is_wp_error($response)) {
                MEP_Helpers::log_error("❌ Errore download HTTP", $response->get_error_message());
                return $response;
            }

            $status_code = wp_remote_retrieve_response_code($response);

            if ($status_code !== 200) {
                $body = wp_remote_retrieve_body($response);
                MEP_Helpers::log_error("❌ Errore download file", [
                    'status'  => $status_code,
                    'body'    => substr($body, 0, 200),
                    'file_id' => $file_id
                ]);
                return new WP_Error('download_failed', sprintf(
                    __('Download fallito (HTTP %d): %s', 'my-event-plugin'),
                    $status_code,
                    substr($body, 0, 100)
                ));
            }

            $file_content = wp_remote_retrieve_body($response);

            if (empty($file_content)) {
                MEP_Helpers::log_error("❌ File scaricato vuoto", ['file_id' => $file_id]);
                return new WP_Error('empty_file', __('File vuoto', 'my-event-plugin'));
            }

            MEP_Helpers::log_info("✅ File scaricato: " . strlen($file_content) . " bytes");

            $upload_dir = wp_upload_dir();

            if (!empty($upload_dir['error'])) {
                MEP_Helpers::log_error("❌ Errore upload directory", $upload_dir['error']);
                return new WP_Error('upload_dir_error', $upload_dir['error']);
            }

            $sanitized_name  = sanitize_file_name($file_name);
            $unique_filename = wp_unique_filename($upload_dir['path'], $sanitized_name);
            $file_path       = $upload_dir['path'] . '/' . $unique_filename;

            if ($unique_filename !== $sanitized_name) {
                MEP_Helpers::log_info("⚠️ File rinominato: {$sanitized_name} -> {$unique_filename}");
            }

            MEP_Helpers::log_info("💾 Salvataggio in: " . $file_path);

            $saved = file_put_contents($file_path, $file_content);

            if ($saved === false) {
                MEP_Helpers::log_error("❌ Impossibile salvare file su disco", ['path' => $file_path]);
                return new WP_Error('save_failed', __('Impossibile salvare il file su disco', 'my-event-plugin'));
            }

            MEP_Helpers::log_info("✅ File salvato su disco: " . $saved . " bytes");

            $file_type = wp_check_filetype($file_name);

            $attachment = [
                'post_mime_type' => $file_type['type'],
                'post_title'     => sanitize_file_name(pathinfo($file_name, PATHINFO_FILENAME)),
                'post_content'   => '',
                'post_status'    => 'inherit'
            ];

            MEP_Helpers::log_info("🎨 Creazione attachment WordPress");

            $attachment_id = wp_insert_attachment($attachment, $file_path);

            if (is_wp_error($attachment_id)) {
                MEP_Helpers::log_error("❌ Errore creazione attachment", $attachment_id->get_error_message());
                @unlink($file_path);
                return $attachment_id;
            }

            MEP_Helpers::log_info("✅ Attachment creato ID: {$attachment_id}");

            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $attach_data = wp_generate_attachment_metadata($attachment_id, $file_path);
            wp_update_attachment_metadata($attachment_id, $attach_data);

            MEP_Helpers::log_info("✅ Metadata generati per attachment {$attachment_id}");

            update_post_meta($attachment_id, '_imported_from_gdrive', true);
            update_post_meta($attachment_id, '_gdrive_file_id', $file_id);
            update_post_meta($attachment_id, '_import_date', current_time('mysql'));

            MEP_Helpers::log_info("🎉 File importato con successo! Attachment ID: {$attachment_id}");

            return $attachment_id;

        } catch (Throwable $e) {
            MEP_Helpers::log_error("💥 Eccezione download_and_import_file", [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString()
            ]);
            return new WP_Error('exception', sprintf(
                __('Errore: %s (file: %s linea: %d)', 'my-event-plugin'),
                $e->getMessage(),
                basename($e->getFile()),
                $e->getLine()
            ));
        }
    }
    
    /**
     * Importa file specifici da Google Drive
     */
    public static function import_files($file_ids, $file_names) {
        if (empty($file_ids) || !is_array($file_ids)) {
            return new WP_Error('empty_files', __('Nessun file da importare', 'my-event-plugin'));
        }
        
        MEP_Helpers::log_info("Inizio import di " . count($file_ids) . " file da Google Drive");
        
        $attachment_ids = [];
        $errors         = [];
        
        foreach ($file_ids as $index => $file_id) {
            $file_name = isset($file_names[$index]) ? $file_names[$index] : "file-{$index}.jpg";
            
            $attachment_id = self::download_and_import_file($file_id, $file_name);
            
            if (is_wp_error($attachment_id)) {
                $errors[] = $file_name . ': ' . $attachment_id->get_error_message();
                MEP_Helpers::log_error("Errore import file {$file_name}", $attachment_id->get_error_message());
                continue;
            }
            
            $attachment_ids[] = $attachment_id;
        }
        
        if (empty($attachment_ids)) {
            return new WP_Error(
                'import_failed',
                __('Impossibile importare alcun file. Errori: ', 'my-event-plugin') . implode(', ', $errors)
            );
        }
        
        if (!empty($errors)) {
            MEP_Helpers::log_error("Alcuni errori durante l'import", $errors);
        }
        
        MEP_Helpers::log_info("Import completato: " . count($attachment_ids) . " file importati");
        
        return $attachment_ids;
    }
    
    /**
     * Ottieni informazioni dettagliate su una cartella (per breadcrumb)
     */
    public static function get_folder_info($folder_id) {
        if (empty($folder_id)) {
            return new WP_Error('empty_folder_id', __('ID cartella vuoto', 'my-event-plugin'));
        }
        
        $token = self::get_access_token();
        if (is_wp_error($token)) {
            return $token;
        }
        
        try {
            $url = self::API_BASE_URL . '/files/' . $folder_id . '?fields=id,name,mimeType,parents';
            
            $response = wp_remote_get($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Accept'        => 'application/json'
                ],
                'timeout' => 15
            ]);
            
            if (is_wp_error($response)) {
                return $response;
            }
            
            $status_code = wp_remote_retrieve_response_code($response);
            
            if ($status_code !== 200) {
                return new WP_Error('api_error', sprintf(__('Errore API: %d', 'my-event-plugin'), $status_code));
            }
            
            $data = json_decode(wp_remote_retrieve_body($response), true);
            
            return $data;
            
        } catch (Throwable $e) {
            MEP_Helpers::log_error("Eccezione get_folder_info", $e->getMessage());
            return new WP_Error('exception', $e->getMessage());
        }
    }
    
    /**
     * Verifica che un folder ID sia accessibile
     */
    public static function verify_folder_access($folder_id) {
        if (empty($folder_id)) {
            return new WP_Error('empty_folder_id', __('ID cartella vuoto', 'my-event-plugin'));
        }
        
        $token = self::get_access_token();
        if (is_wp_error($token)) {
            return $token;
        }
        
        try {
            $url = self::API_BASE_URL . '/files/' . $folder_id . '?fields=id,name,mimeType';
            
            $response = wp_remote_get($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Accept'        => 'application/json'
                ],
                'timeout' => 15
            ]);
            
            if (is_wp_error($response)) {
                return $response;
            }
            
            $status_code = wp_remote_retrieve_response_code($response);
            
            if ($status_code === 404) {
                return new WP_Error('folder_not_found', __('Cartella non trovata', 'my-event-plugin'));
            }
            
            if ($status_code === 403) {
                return new WP_Error('access_denied', __('Accesso negato alla cartella', 'my-event-plugin'));
            }
            
            if ($status_code !== 200) {
                return new WP_Error('api_error', sprintf(__('Errore API: %d', 'my-event-plugin'), $status_code));
            }
            
            $data = json_decode(wp_remote_retrieve_body($response), true);
            
            if (isset($data['mimeType']) && $data['mimeType'] !== 'application/vnd.google-apps.folder') {
                return new WP_Error('not_folder', __('L\'ID fornito non è una cartella', 'my-event-plugin'));
            }
            
            MEP_Helpers::log_info("Cartella {$folder_id} accessibile: " . ($data['name'] ?? 'N/A'));
            
            return true;
            
        } catch (Throwable $e) {
            MEP_Helpers::log_error("Eccezione verify_folder_access", $e->getMessage());
            return new WP_Error('exception', $e->getMessage());
        }
    }
}
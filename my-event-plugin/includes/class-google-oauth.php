<?php
/**
 * Classe Google OAuth - Gestione autenticazione OAuth 2.0 per Google Drive
 */

defined('ABSPATH') || exit;

class MEP_Google_OAuth {
    
    const AUTH_URL  = 'https://accounts.google.com/o/oauth2/v2/auth';
    const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    const SCOPE     = 'https://www.googleapis.com/auth/drive.readonly';
    
    public static function get_auth_url() {
        $client_id    = get_option('mep_google_client_id');
        $redirect_uri = self::get_redirect_uri();
        
        if (empty($client_id)) {
            return '';
        }
        
        $params = [
            'client_id'     => $client_id,
            'redirect_uri'  => $redirect_uri,
            'response_type' => 'code',
            'scope'         => self::SCOPE,
            'access_type'   => 'offline',
            'prompt'        => 'consent',
            'state'         => wp_create_nonce('mep_google_oauth')
        ];
        
        return self::AUTH_URL . '?' . http_build_query($params);
    }
    
    public static function get_redirect_uri() {
        return admin_url('admin.php?page=my-event-settings&google_auth=callback');
    }
    
    public static function exchange_code_for_token($code) {
        $client_id     = get_option('mep_google_client_id');
        $client_secret = get_option('mep_google_client_secret');
        $redirect_uri  = self::get_redirect_uri();
        
        if (empty($client_id) || empty($client_secret)) {
            return new WP_Error('missing_credentials', __('Client ID o Secret mancanti', 'my-event-plugin'));
        }
        
        MEP_Helpers::log_info("🔄 Scambio authorization code con token");
        
        $response = wp_remote_post(self::TOKEN_URL, [
            'body' => [
                'code'          => $code,
                'client_id'     => $client_id,
                'client_secret' => $client_secret,
                'redirect_uri'  => $redirect_uri,
                'grant_type'    => 'authorization_code'
            ],
            'timeout' => 30
        ]);
        
        if (is_wp_error($response)) {
            MEP_Helpers::log_error("Errore scambio token", $response->get_error_message());
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['error'])) {
            MEP_Helpers::log_error("Errore OAuth", $data['error_description'] ?? $data['error']);
            return new WP_Error('oauth_error', $data['error_description'] ?? $data['error']);
        }
        
        if (!isset($data['access_token'])) {
            MEP_Helpers::log_error("Token mancante nella risposta", $data);
            return new WP_Error('no_token', __('Token non ricevuto', 'my-event-plugin'));
        }
        
        self::save_token_data($data);
        
        MEP_Helpers::log_info("✅ Token salvato con successo");
        
        return $data;
    }
    
    private static function save_token_data($token_data) {
        update_option('mep_google_access_token',    $token_data['access_token']);
        update_option('mep_google_token_type',      $token_data['token_type'] ?? 'Bearer');
        update_option('mep_google_expires_in',      $token_data['expires_in'] ?? 3600);
        update_option('mep_google_token_created_at', time());
        
        if (isset($token_data['refresh_token'])) {
            update_option('mep_google_refresh_token', $token_data['refresh_token']);
        }
    }
    
    public static function get_access_token() {
        $token      = get_option('mep_google_access_token');
        $created_at = get_option('mep_google_token_created_at');
        $expires_in = get_option('mep_google_expires_in', 3600);
        
        if (empty($token)) {
            return new WP_Error('no_token', __('Nessun token salvato. Autorizza l\'applicazione.', 'my-event-plugin'));
        }
        
        // Verifica se scaduto (con margine di 5 minuti)
        $expires_at = $created_at + $expires_in - 300;
        
        if (time() >= $expires_at) {
            MEP_Helpers::log_info("🔄 Token scaduto, refresh in corso");
            
            $result = self::refresh_access_token();
            
            if (is_wp_error($result)) {
                return $result;
            }
            
            $token = get_option('mep_google_access_token');
        }
        
        return $token;
    }
    
    public static function refresh_access_token() {
        $refresh_token = get_option('mep_google_refresh_token');
        $client_id     = get_option('mep_google_client_id');
        $client_secret = get_option('mep_google_client_secret');
        
        if (empty($refresh_token)) {
            return new WP_Error('no_refresh_token', __('Refresh token mancante. Ri-autorizza l\'applicazione.', 'my-event-plugin'));
        }
        
        if (empty($client_id) || empty($client_secret)) {
            return new WP_Error('missing_credentials', __('Credenziali OAuth mancanti', 'my-event-plugin'));
        }
        
        MEP_Helpers::log_info("🔄 Refresh access token");
        
        $response = wp_remote_post(self::TOKEN_URL, [
            'body' => [
                'refresh_token' => $refresh_token,
                'client_id'     => $client_id,
                'client_secret' => $client_secret,
                'grant_type'    => 'refresh_token'
            ],
            'timeout' => 30
        ]);
        
        if (is_wp_error($response)) {
            MEP_Helpers::log_error("Errore refresh token", $response->get_error_message());
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['error'])) {
            MEP_Helpers::log_error("Errore OAuth refresh", $data['error_description'] ?? $data['error']);
            return new WP_Error('oauth_error', $data['error_description'] ?? $data['error']);
        }
        
        if (!isset($data['access_token'])) {
            MEP_Helpers::log_error("Token mancante nella risposta refresh", $data);
            return new WP_Error('no_token', __('Token non ricevuto', 'my-event-plugin'));
        }
        
        self::save_token_data($data);
        
        MEP_Helpers::log_info("✅ Token refreshato con successo");
        
        return $data;
    }
    
    public static function is_authorized() {
        $token         = get_option('mep_google_access_token');
        $refresh_token = get_option('mep_google_refresh_token');
        
        return !empty($token) || !empty($refresh_token);
    }
    
    public static function revoke_authorization() {
        delete_option('mep_google_access_token');
        delete_option('mep_google_refresh_token');
        delete_option('mep_google_token_type');
        delete_option('mep_google_expires_in');
        delete_option('mep_google_token_created_at');
        
        MEP_Helpers::log_info("🗑️ Autorizzazione Google revocata");
    }
    
    public static function get_token_info() {
        $created_at = get_option('mep_google_token_created_at');
        $expires_in = get_option('mep_google_expires_in', 3600);
        
        $expires_at = $created_at ? $created_at + $expires_in : 0;
        $is_expired = time() >= $expires_at;
        
        return [
            'has_access_token'  => !empty(get_option('mep_google_access_token')),
            'has_refresh_token' => !empty(get_option('mep_google_refresh_token')),
            'created_at'        => $created_at ? date('Y-m-d H:i:s', $created_at) : 'N/A',
            'expires_at'        => $expires_at  ? date('Y-m-d H:i:s', $expires_at) : 'N/A',
            'is_expired'        => $is_expired,
            'time_to_expiry'    => $expires_at  ? max(0, $expires_at - time()) : 0
        ];
    }
}
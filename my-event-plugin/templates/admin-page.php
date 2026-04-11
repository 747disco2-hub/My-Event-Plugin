<?php
/**
 * Template: Pagina Admin Principale - Creazione Evento
 * Versione 1.2.0 - Browser Google Drive Integrato
 */

defined('ABSPATH') || exit;
?>

<div class="wrap mep-admin-wrap">
    <h1 class="mep-page-title">
        <span class="dashicons dashicons-calendar-alt"></span>
        <?php _e('Crea Nuovo Evento', 'my-event-plugin'); ?>
    </h1>
    
    <div class="mep-admin-container">
        
        <!-- Sidebar Info -->
        <div class="mep-sidebar">
            <div class="mep-info-box">
                <h3><?php _e('📋 Come Funziona', 'my-event-plugin'); ?></h3>
                <ol>
                    <li><?php _e('Naviga nel tuo Google Drive e seleziona la cartella con le foto', 'my-event-plugin'); ?></li>
                    <li><?php _e('Seleziona le foto e importale nella Media Library', 'my-event-plugin'); ?></li>
                    <li><?php _e('Scegli la foto di copertina (esclusa dal prompt ChatGPT)', 'my-event-plugin'); ?></li>
                    <li><?php _e('Inserisci il titolo dell\'evento e scegli la categoria', 'my-event-plugin'); ?></li>
                    <li><?php _e('Genera il prompt ChatGPT e incolla la risposta per auto-compilare i campi', 'my-event-plugin'); ?></li>
                    <li><?php _e('Verifica i campi compilati (slug, contenuto, SEO) e clicca "Crea Evento"', 'my-event-plugin'); ?></li>
                </ol>
            </div>
            
            <div class="mep-info-box mep-tips">
                <h3><?php _e('💡 Suggerimenti', 'my-event-plugin'); ?></h3>
                <ul>
                    <li><?php _e('La cartella deve contenere almeno 4 foto', 'my-event-plugin'); ?></li>
                    <li><?php _e('Formati supportati: JPG, PNG, GIF, WebP', 'my-event-plugin'); ?></li>
                    <li><?php _e('Le foto verranno importate nella Media Library', 'my-event-plugin'); ?></li>
                    <li><?php _e('L\'articolo verrà creato come bozza', 'my-event-plugin'); ?></li>
                    <li><?php _e('🤖 Usa "Genera Prompt ChatGPT" per creare automaticamente titolo SEO, meta description e contenuto HTML', 'my-event-plugin'); ?></li>
                </ul>
            </div>
        </div>
        
        <!-- Form Principale -->
        <div class="mep-main-content">
            <form id="mep-event-form" class="mep-form" method="post">
                <?php wp_nonce_field('mep_nonce', 'mep_nonce_field'); ?>
                
                <!-- 📁 PASSO 1: Browser Google Drive -->
                <div class="mep-section mep-gdrive-section">
                    <div class="mep-step-banner mep-step-banner--gdrive">
                        <h2>
                            <span class="dashicons dashicons-cloud" style="font-size: 32px; width: 32px; height: 32px;"></span>
                            <?php _e('📁 Passo 1: Naviga nel tuo Google Drive', 'my-event-plugin'); ?>
                        </h2>
                        <p>
                            <?php _e('Naviga nelle cartelle del tuo Google Drive e seleziona quella che contiene le foto dell\'evento.', 'my-event-plugin'); ?><br>
                            <?php _e('✨ Clicca su una cartella per aprirla, oppure clicca "Seleziona questa cartella" per caricare le foto!', 'my-event-plugin'); ?>
                        </p>
                    </div>
                    
                    <!-- Browser Google Drive -->
                    <div id="mep-gdrive-browser" class="mep-gdrive-browser">
                        <!-- Breadcrumb -->
                        <div id="mep-gdrive-breadcrumb" class="mep-gdrive-breadcrumb">
                            <span class="dashicons dashicons-admin-home" style="color: #2271b1;"></span>
                            <span style="color: #646970;">My Drive</span>
                        </div>
                        
                        <!-- Lista Cartelle -->
                        <div id="mep-gdrive-folders-list" style="min-height: 200px;">
                            <div style="text-align: center; padding: 40px; color: #646970;">
                                <span class="mep-spinner"></span>
                                <p style="margin: 10px 0 0 0;"><?php _e('Caricamento cartelle...', 'my-event-plugin'); ?></p>
                            </div>
                        </div>
                        
                        <!-- Pulsante Seleziona Cartella Corrente -->
                        <div id="mep-current-folder-actions" class="mep-current-folder-actions" style="display: none;">
                            <button type="button" 
                                    id="mep-select-current-folder" 
                                    class="button button-primary button-large mep-select-folder-btn">
                                <span class="dashicons dashicons-yes" style="margin-top: 4px;"></span>
                                <?php _e('✓ Seleziona Questa Cartella e Carica Foto', 'my-event-plugin'); ?>
                            </button>
                        </div>
                    </div>
                    
                    <div id="mep-folder-validation-message" class="mep-validation-message" style="display:none;"></div>
                    
                    <!-- Campi nascosti -->
                    <input type="hidden" name="event_folder_id" id="event_folder_id">
                    <input type="hidden" name="event_folder_name" id="event_folder_name">
                </div>
                
                <!-- 📸 PASSO 2: Griglia Selezione Foto -->
                <div id="mep-photo-selector-wrapper" style="display:none;">
                    <hr style="margin: 30px 0; border: 0; border-top: 2px solid #2271b1;">
                    
                    <div class="mep-section">
                        <div class="mep-step-banner mep-step-banner--photos">
                            <h3>
                                <span class="dashicons dashicons-images-alt2" style="font-size: 24px; width: 24px; height: 24px;"></span>
                                <?php _e('Passo 2: Seleziona le Foto da Importare', 'my-event-plugin'); ?>
                            </h3>
                            <p>
                                <?php _e('📸 Clicca sulle miniature per selezionare 4 foto che verranno scaricate e importate nella galleria WordPress.', 'my-event-plugin'); ?>
                            </p>
                        </div>
                        
                        <div id="mep-selection-info" class="mep-selection-info">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <span class="dashicons dashicons-format-gallery" style="color: #2271b1; font-size: 20px;"></span>
                                <span class="mep-selection-count" style="font-size: 15px;">
                                    <?php _e('Foto selezionate:', 'my-event-plugin'); ?> 
                                    <strong style="color: #2271b1; font-size: 18px;">0</strong>
                                </span>
                            </div>
                            <div id="mep-selection-help" style="font-size: 13px; color: #646970;">
                                <?php _e('Clicca sui pulsanti "Seleziona" sotto ogni foto per aggiungerle alla selezione', 'my-event-plugin'); ?>
                            </div>
                        </div>
                        
                        <!-- Griglia Foto -->
                        <div id="mep-photo-grid" class="mep-photo-grid">
                            <div class="mep-loading-grid">
                                <span class="mep-spinner"></span>
                                <p><?php _e('Caricamento foto dalla cartella...', 'my-event-plugin'); ?></p>
                            </div>
                        </div>
                        
                        <!-- Foto Selezionate -->
                        <div id="mep-selected-photos" class="mep-selected-photos" style="display:none;">
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px;">
                                <h3 style="margin: 0;"><?php _e('✓ Foto Selezionate', 'my-event-plugin'); ?></h3>
                                <button type="button" id="mep-clear-selection" class="button button-secondary button-small">
                                    <?php _e('Cancella Selezione', 'my-event-plugin'); ?>
                                </button>
                            </div>
                            <p style="margin: 0 0 15px 0; color: #646970; font-size: 13px;">
                                <?php _e('Queste foto verranno scaricate da Google Drive e aggiunte alla Media Library di WordPress. Clicca sulla X per rimuoverne una.', 'my-event-plugin'); ?>
                            </p>
                            <div id="mep-selected-photos-list" class="mep-selected-photos-list"></div>
                            
                            <!-- Pulsante Importa Foto -->
                            <div class="mep-import-photos-action">
                                <button type="button" id="mep-import-photos-btn" class="mep-import-photos-btn">
                                    <span class="dashicons dashicons-download" style="font-size: 20px; margin-top: 2px;"></span>
                                    <?php _e('Importa Foto in WordPress', 'my-event-plugin'); ?>
                                </button>
                                <p style="margin: 10px 0 0 0; color: white; font-size: 13px; opacity: 0.95;">
                                    <?php _e('Le foto verranno scaricate e salvate nella Media Library', 'my-event-plugin'); ?>
                                </p>
                            </div>
                            
                            <!-- Container per i link delle foto importate -->
                            <div id="mep-imported-links-container" class="mep-imported-links" style="display:none;"></div>
                            
                        </div>
                        
                        <!-- Campo nascosto con gli ID delle foto selezionate -->
                        <input type="hidden" name="selected_photo_ids" id="selected_photo_ids">
                    </div>
                    
                    <hr style="margin: 30px 0; border: 0; border-top: 2px solid #2271b1;">
                </div>
                
                <!-- 🌟 PASSO 3: Scelta Copertina -->
                <div id="mep-featured-image-section" class="mep-section" style="display:none;">
                    <h2 class="mep-section-title">
                        <span class="dashicons dashicons-format-image"></span>
                        <?php _e('Passo 3: Scegli la Foto di Copertina', 'my-event-plugin'); ?>
                    </h2>
                    <p style="margin: 0 0 15px 0; color: #646970;">
                        <?php _e('Seleziona quale foto usare come immagine in evidenza dell\'articolo (quella che appare nelle anteprime). La foto di copertina sarà esclusa dalla galleria nel prompt ChatGPT.', 'my-event-plugin'); ?>
                    </p>
                    <select id="mep-featured-image-select" name="featured_image_index" class="mep-select" style="max-width: 100%;">
                        <option value=""><?php _e('-- Seleziona immagine di copertina --', 'my-event-plugin'); ?></option>
                    </select>
                </div>
                
                <!-- 📝 PASSO 4: Titolo e Categoria -->
                <div class="mep-section">
                    <h2 class="mep-section-title">
                        <span class="dashicons dashicons-edit"></span>
                        <?php _e('Passo 4: Titolo e Categoria', 'my-event-plugin'); ?>
                    </h2>

                    <!-- Titolo Evento -->
                    <div class="mep-form-row">
                        <label for="event_title" class="mep-label required">
                            <?php _e('Titolo Evento', 'my-event-plugin'); ?>
                        </label>
                        <input type="text"
                               id="event_title"
                               name="event_title"
                               class="mep-input large"
                               required
                               placeholder="<?php esc_attr_e('Es: Serata Live Music - Sabato 15 Marzo', 'my-event-plugin'); ?>">
                        <p class="mep-description">
                            <?php _e('Il titolo principale che apparirà nell\'articolo', 'my-event-plugin'); ?>
                        </p>
                    </div>

                    <!-- Categoria -->
                    <div class="mep-form-row">
                        <label for="event_category" class="mep-label required">
                            <?php _e('Categoria Articolo', 'my-event-plugin'); ?>
                        </label>
                        <?php
                        wp_dropdown_categories([
                            'name'             => 'event_category',
                            'id'               => 'event_category',
                            'class'            => 'mep-select',
                            'hide_empty'       => false,
                            'required'         => true,
                            'show_option_none' => __('-- Seleziona Categoria --', 'my-event-plugin'),
                            'option_none_value'=> ''
                        ]);
                        ?>
                    </div>
                </div>

                <!-- 🤖 PASSO 5: ChatGPT -->
                <div class="mep-section">
                    <h2 class="mep-section-title">
                        <span class="dashicons dashicons-format-chat"></span>
                        <?php _e('Passo 5: Genera Contenuto con ChatGPT', 'my-event-plugin'); ?>
                    </h2>
                    <p class="mep-description" style="margin-bottom: 15px;">
                        <?php _e('Clicca il pulsante per generare il prompt, poi incollalo in ChatGPT. Infine, incolla la risposta nel campo sottostante per compilare automaticamente tutti i campi.', 'my-event-plugin'); ?>
                    </p>

                    <!-- Pulsante Genera Prompt -->
                    <div class="mep-form-actions" style="margin-bottom: 20px; border-top: none; padding-top: 0; margin-top: 0;">
                        <button type="button"
                                class="button button-secondary button-hero"
                                id="mep-generate-prompt-btn"
                                style="background: #e7f5ff; border-color: #0073aa; color: #0073aa;">
                            <span class="dashicons dashicons-format-chat"></span>
                            <?php _e('Genera Prompt ChatGPT', 'my-event-plugin'); ?>
                        </button>
                    </div>

                    <!-- Container per il prompt generato -->
                    <div id="mep-chatgpt-prompt-container" style="display: none; margin-bottom: 20px;"></div>

                    <!-- Sezione Incolla Risposta ChatGPT -->
                    <div id="mep-chatgpt-response-section" style="display: none;">
                        <div class="mep-step-banner mep-step-banner--chatgpt" style="margin-bottom: 15px;">
                            <h3>
                                <span class="dashicons dashicons-welcome-write-blog" style="font-size: 24px; width: 24px; height: 24px;"></span>
                                <?php _e('📋 Incolla la Risposta di ChatGPT', 'my-event-plugin'); ?>
                            </h3>
                            <p>
                                <?php _e('Incolla qui la risposta completa di ChatGPT. I campi verranno compilati automaticamente!', 'my-event-plugin'); ?>
                            </p>
                        </div>

                        <div class="mep-form-row">
                            <textarea id="mep-chatgpt-response-input"
                                      class="mep-textarea code"
                                      rows="12"
                                      placeholder="<?php esc_attr_e('Incolla qui tutta la risposta di ChatGPT...', 'my-event-plugin'); ?>"></textarea>
                        </div>

                        <div class="mep-chatgpt-actions">
                            <button type="button"
                                    id="mep-parse-response-btn"
                                    class="button button-primary button-large"
                                    style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); border: none; color: white; font-weight: 600; padding: 10px 25px;">
                                <span class="dashicons dashicons-controls-repeat" style="margin-top: 4px;"></span>
                                <?php _e('🔄 Analizza e Compila Campi', 'my-event-plugin'); ?>
                            </button>
                            <span id="mep-parse-result-message" style="display: none; font-weight: 600;"></span>
                        </div>
                    </div>
                </div>

                <!-- ✏️ PASSO 6: Campi compilati da ChatGPT -->
                <div class="mep-section">
                    <h2 class="mep-section-title">
                        <span class="dashicons dashicons-edit"></span>
                        <?php _e('Passo 6: Dettagli Compilati (da ChatGPT o manualmente)', 'my-event-plugin'); ?>
                    </h2>

                    <!-- Permalink (Slug) -->
                    <div class="mep-form-row">
                        <label for="event_slug" class="mep-label">
                            <?php _e('Permalink (Slug)', 'my-event-plugin'); ?>
                        </label>
                        <input type="text"
                               id="event_slug"
                               name="event_slug"
                               class="mep-input large"
                               placeholder="<?php esc_attr_e('es: serata-live-music-sabato-15-marzo', 'my-event-plugin'); ?>">
                        <p class="mep-description">
                            <?php _e('URL-friendly dell\'articolo. Lascia vuoto per generarlo automaticamente dal titolo. Si compila automaticamente con ChatGPT.', 'my-event-plugin'); ?>
                        </p>
                    </div>

                    <!-- Contenuto HTML -->
                    <div class="mep-form-row">
                        <label for="event_content" class="mep-label">
                            <?php _e('Contenuto Evento (HTML)', 'my-event-plugin'); ?>
                        </label>
                        <textarea id="event_content"
                                  name="event_content"
                                  class="mep-textarea code"
                                  rows="8"
                                  placeholder="<?php esc_attr_e('Si compila automaticamente con ChatGPT, oppure incolla qui il codice HTML del contenuto dell\'evento...', 'my-event-plugin'); ?>"></textarea>
                        <p class="mep-description">
                            <?php _e('Puoi incollare HTML. Lascia vuoto per usare il contenuto del template.', 'my-event-plugin'); ?>
                        </p>
                    </div>

                    <!-- SEO Section -->
                    <h3 class="mep-section-title" style="margin-top: 20px;">
                        <span class="dashicons dashicons-chart-line"></span>
                        <?php _e('SEO - Ottimizzazione Motori di Ricerca', 'my-event-plugin'); ?>
                    </h3>

                    <?php if (MEP_Helpers::is_rankmath_active()): ?>
                        <p class="mep-notice mep-notice-success">
                            ✓ <?php _e('Rank Math è attivo. I metadati SEO verranno salvati automaticamente.', 'my-event-plugin'); ?>
                        </p>
                    <?php else: ?>
                        <p class="mep-notice mep-notice-info">
                            <?php _e('Installa Rank Math per gestire al meglio la SEO dei tuoi eventi.', 'my-event-plugin'); ?>
                        </p>
                    <?php endif; ?>

                    <div class="mep-form-row">
                        <label for="seo_focus_keyword" class="mep-label">
                            <?php _e('Focus Keyword', 'my-event-plugin'); ?>
                        </label>
                        <input type="text"
                               id="seo_focus_keyword"
                               name="seo_focus_keyword"
                               class="mep-input"
                               placeholder="<?php esc_attr_e('Es: live music roma', 'my-event-plugin'); ?>">
                        <p class="mep-description">
                            <?php _e('Parola chiave principale per cui vuoi posizionare l\'articolo', 'my-event-plugin'); ?>
                        </p>
                    </div>

                    <div class="mep-form-row">
                        <label for="seo_title" class="mep-label">
                            <?php _e('Titolo SEO', 'my-event-plugin'); ?>
                        </label>
                        <input type="text"
                               id="seo_title"
                               name="seo_title"
                               class="mep-input large"
                               maxlength="60"
                               placeholder="<?php esc_attr_e('Lascia vuoto per usare il titolo evento', 'my-event-plugin'); ?>">
                        <p class="mep-description">
                            <span class="seo-counter">0/60</span> caratteri •
                            <?php _e('Questo titolo apparirà nei risultati di Google', 'my-event-plugin'); ?>
                        </p>
                    </div>

                    <div class="mep-form-row">
                        <label for="seo_description" class="mep-label">
                            <?php _e('Meta Description', 'my-event-plugin'); ?>
                        </label>
                        <textarea id="seo_description"
                                  name="seo_description"
                                  class="mep-textarea"
                                  rows="3"
                                  maxlength="160"
                                  placeholder="<?php esc_attr_e('Descrizione breve che apparirà su Google...', 'my-event-plugin'); ?>"></textarea>
                        <p class="mep-description">
                            <span class="desc-counter">0/160</span> caratteri •
                            <?php _e('Descrizione che apparirà sotto il titolo nei risultati di ricerca', 'my-event-plugin'); ?>
                        </p>
                    </div>
                </div>
                
                <!-- Submit Button -->
                <div class="mep-form-actions">
                    <button type="submit" 
                            class="button button-primary button-hero mep-submit-btn" 
                            id="mep-submit-btn">
                        <span class="dashicons dashicons-yes"></span>
                        <?php _e('Crea Evento', 'my-event-plugin'); ?>
                    </button>
                    
                    <div id="mep-status-message" class="mep-status-message"></div>
                </div>
                
            </form>
        </div>
        
    </div>
</div>
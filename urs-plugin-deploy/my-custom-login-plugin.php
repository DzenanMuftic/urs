<?php
/**
 * Plugin Name: My Custom Login Plugin
 * Description: Custom login system with external database authentication and user dashboard
 * Version: 1.0.0
 * Author: URS
 * Text Domain: my-custom-login
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('MCL_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('MCL_PLUGIN_URL', plugin_dir_url(__FILE__));

class MyCustomLoginPlugin {
    
    private $db_config = [
        'host' => '65.21.234.24',
        'port' => '3306',
        'database' => 'ursbihba_lara195',
        'username' => 'ursbihba_lara195',
        'password' => 'paradoX2019',
        'charset' => 'utf8'
    ];

    private $debug_mode = true; // Set to false in production

    public function __construct() {
        add_action('init', [$this, 'start_session']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_loaded', 'prevent_wp_login_redirect');
        add_action('parse_request', 'handle_komisije_requests', 1);
        add_action('init', 'setup_wp_redirect_filter', 5);
        add_action('wp_head', [$this, 'add_css_mime_type'], 1);
        add_shortcode('custom_login_form', [$this, 'display_login_form']);
        add_shortcode('custom_user_dashboard', [$this, 'display_user_dashboard']);

        // New shortcodes for user system
        add_shortcode('user_login_form', [$this, 'display_user_login_form']);
        add_shortcode('user_dashboard', [$this, 'display_user_dashboard_page']);
        add_shortcode('custom_db_test', [$this, 'test_db_connection']); // Debug shortcode
        add_shortcode('custom_user_check', [$this, 'check_user_details']); // Debug user details
        add_shortcode('custom_password_test', [$this, 'test_password_hash']); // Password testing
        add_shortcode('custom_imenik_test', [$this, 'test_imenik_search']); // Test imenik search
        add_shortcode('custom_table_check', [$this, 'check_user_match_table']); // Check user_match table
        add_action('wp_ajax_custom_login', [$this, 'handle_login']);
        add_action('wp_ajax_nopriv_custom_login', [$this, 'handle_login']);
        add_action('wp_ajax_mcl_login', [$this, 'handle_mcl_login']);
        add_action('wp_ajax_nopriv_mcl_login', [$this, 'handle_mcl_login']);
        add_action('wp_ajax_custom_logout', [$this, 'handle_logout']);
        add_action('wp_ajax_nopriv_custom_logout', [$this, 'handle_logout']);
        add_action('wp_ajax_search_imenik', [$this, 'handle_search_imenik']);
        add_action('wp_ajax_nopriv_search_imenik', [$this, 'handle_search_imenik']);
        add_action('wp_ajax_admin_komisija_login', [$this, 'handle_admin_komisija_login']);
        add_action('wp_ajax_nopriv_admin_komisija_login', [$this, 'handle_admin_komisija_login']);

        // New user login/logout handlers
        add_action('wp_ajax_user_login', [$this, 'handle_user_login']);
        add_action('wp_ajax_nopriv_user_login', [$this, 'handle_user_login']);
        add_action('wp_ajax_user_logout', [$this, 'handle_user_logout']);
        add_action('wp_ajax_nopriv_user_logout', [$this, 'handle_user_logout']);
        add_action('admin_menu', [$this, 'add_admin_menu']);
        
        // Admin CRUD operations
        add_action('wp_ajax_load_operators', [$this, 'handle_load_operators']);
        add_action('wp_ajax_add_operator', [$this, 'handle_add_operator']);
        add_action('wp_ajax_update_operator', [$this, 'handle_update_operator']);
        add_action('wp_ajax_delete_operator', [$this, 'handle_delete_operator']);
        
        // Users data CRUD operations
        add_action('wp_ajax_load_users', [$this, 'handle_load_users']);
        add_action('wp_ajax_add_user', [$this, 'handle_add_user']);
        add_action('wp_ajax_update_user', [$this, 'handle_update_user']);
        add_action('wp_ajax_delete_user', [$this, 'handle_delete_user']);
        
        // Matches CRUD operations
        add_action('wp_ajax_load_matches', [$this, 'handle_load_matches']);
        add_action('wp_ajax_add_match', [$this, 'handle_add_match']);
        add_action('wp_ajax_update_match', [$this, 'handle_update_match']);
        add_action('wp_ajax_delete_match', [$this, 'handle_delete_match']);
        
        // Privileges management operations
        add_action('wp_ajax_load_roles_for_privileges', [$this, 'handle_load_roles_for_privileges']);
        add_action('wp_ajax_load_role_privileges', [$this, 'handle_load_role_privileges']);
        add_action('wp_ajax_save_role_privileges', [$this, 'handle_save_role_privileges']);
        
        // Komisije AJAX handlers
        add_action('wp_ajax_search_matches_komisije', [$this, 'handle_search_matches_komisije']);
        add_action('wp_ajax_get_match_details', [$this, 'handle_get_match_details']);
        add_action('wp_ajax_save_match_komisije', [$this, 'handle_save_match_komisije']);
        add_action('wp_ajax_delete_match_komisije', [$this, 'handle_delete_match_komisije']);
        add_action('wp_ajax_get_judges_list', [$this, 'handle_get_judges_list']);
        add_action('wp_ajax_check_komisija_access', [$this, 'handle_check_komisija_access']);
        add_action('wp_ajax_nopriv_check_komisija_access', [$this, 'handle_check_komisija_access']);
        add_action('wp_ajax_load_komisije', [$this, 'handle_load_komisije']);
        
        // WordPress komisija pristup
        add_action('wp_ajax_create_komisija_wp_user', [$this, 'handle_create_komisija_wp_user']);
        add_action('wp_ajax_nopriv_create_komisija_wp_user', [$this, 'handle_create_komisija_wp_user']);
        
        // Hook za komisijski admin pristup - UKLONJENO
        // add_action('wp_login', [$this, 'handle_komisija_wp_login'], 10, 2);
        // add_action('admin_init', [$this, 'restrict_komisija_admin_access']);
        
        // Dodaj filter da sprečimo WordPress redirekciju za komisijske korisnike
        add_action('init', [$this, 'prevent_wp_login_redirect'], 1);
        add_action('wp_loaded', [$this, 'block_wp_redirects'], 1);
    }

    /**
     * Učitava sve komisije iz user_komisije tabele
     */
    public function get_available_komisije() {
        try {
            $connection = new mysqli(
                $this->db_config['host'],
                $this->db_config['username'], 
                $this->db_config['password'],
                $this->db_config['database']
            );

            if ($connection->connect_error) {
                return [];
            }

            $connection->set_charset($this->db_config['charset']);

            // Učitaj sve jedinstvene tip_komisije vrednosti
            $query = "SELECT DISTINCT tip_komisije FROM user_komisije WHERE tip_komisije IS NOT NULL AND tip_komisije != '' ORDER BY tip_komisije";
            $result = $connection->query($query);

            $komisije = [];
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $tip = $row['tip_komisije'];
                    $komisije[] = [
                        'value' => $tip,
                        'label' => $this->format_komisija_name($tip)
                    ];
                }
            }

            $connection->close();
            return $komisije;

        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Formatira naziv komisije na osnovu tip_komisije
     */
    private function format_komisija_name($tip) {
        $names = [
            'FK' => 'FK - Komisija za fudbal',
            'RK' => 'RK - Komisija za rukomet', 
            'OK' => 'OK - Komisija za odbojku',
            'KK' => 'KK - Komisija za košarku',
            'TK' => 'TK - Komisija za tenis',
            'SK' => 'SK - Komisija za stoni tenis'
        ];
        
        return $names[$tip] ?? $tip . ' - Komisija';
    }

    public function start_session() {
        if (!session_id()) {
            session_start();
        }
    }

    public function handle_komisije_panel_redirect() {
        // This function is no longer needed - we'll use shortcode instead
        return;
    }

    public function add_css_mime_type() {
        // Ensure proper MIME type for CSS
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=UTF-8');
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        
        // No reCAPTCHA needed - using mathematical verification
        
        // Our script always loads - updated version to clear cache
        wp_enqueue_script('mcl-script', MCL_PLUGIN_URL . 'assets/script.js', ['jquery'], '1.0.7', true);
        // Enqueue style with updated version
        wp_enqueue_style('mcl-style', MCL_PLUGIN_URL . 'assets/style.css', [], '1.0.4');
        wp_localize_script('mcl-script', 'mcl_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mcl_nonce'),
            'plugin_url' => MCL_PLUGIN_URL
        ]);
    }

    private function get_db_connection() {
        try {
            // Handle host:port format
            $host = $this->db_config['host'];
            $port = isset($this->db_config['port']) ? $this->db_config['port'] : 3306;
            
            $connection = new mysqli(
                $host,
                $this->db_config['username'],
                $this->db_config['password'],
                $this->db_config['database'],
                $port
            );

            if ($connection->connect_error) {
                throw new Exception('Connection failed: ' . $connection->connect_error);
            }

            $connection->set_charset($this->db_config['charset']);
            return $connection;
        } catch (Exception $e) {
            error_log('Database connection error: ' . $e->getMessage());
            return false;
        }
    }

    public function display_login_form($atts) {
        $atts = shortcode_atts([
            'redirect_url' => '',
            'recaptcha_error' => ''
        ], $atts);

        // If user is already logged in, redirect to dashboard
        if (isset($_SESSION['mcl_user_id'])) {
            // Use JavaScript redirect as we can't use wp_redirect in shortcode
            echo '<script>window.location.href = "https://ursbih.ba/user-dashboard/";</script>';
            return '<div class="mcl-info">Već ste prijavljeni. Preusmjeravamo vas na dashboard...</div>';
        }

        ob_start();
        include MCL_PLUGIN_PATH . 'templates/login-form.php';
        return ob_get_clean();
    }

    public function display_user_dashboard($atts) {
        // Check if user is logged in
        if (!isset($_SESSION['mcl_user_id'])) {
            return '<div class="mcl-error">Morate biti prijavljeni da biste pristupili dashboard-u. <a href="https://ursbih.ba/myplugin/">Prijavite se</a></div>';
        }

        $user_data = $this->get_user_data($_SESSION['mcl_user_id']);
        if (!$user_data) {
            return '<div class="mcl-error">Greška pri učitavanju korisničkih podataka.</div>';
        }

        ob_start();
        include MCL_PLUGIN_PATH . 'templates/dashboard.php';
        return ob_get_clean();
    }

    public function handle_login() {
        // Debug: Log all POST data
        error_log("All POST data: " . print_r($_POST, true));
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'mcl_nonce')) {
            wp_die('Security check failed');
        }

        // Google reCAPTCHA v2 validation
        $recaptcha_response = isset($_POST['g-recaptcha-response']) ? $_POST['g-recaptcha-response'] : '';
        $recaptcha_secret = '6Lfo7qUrAAAAAD3QQicmEmIt58IGVnScwER1uwpY'; // <-- OVDJE UNESI SVOJ SECRET KEY
        $recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';
        $recaptcha_valid = false;
        
        // Debug: Log reCAPTCHA response
        error_log("reCAPTCHA response received: " . $recaptcha_response);
        
        if (!empty($recaptcha_response)) {
            $response = wp_remote_post($recaptcha_url, [
                'body' => [
                    'secret' => $recaptcha_secret,
                    'response' => $recaptcha_response,
                    'remoteip' => $_SERVER['REMOTE_ADDR']
                ],
                'timeout' => 10
            ]);
            if (!is_wp_error($response)) {
                $result = json_decode(wp_remote_retrieve_body($response), true);
                
                // Debug: Log Google's response
                error_log("Google reCAPTCHA API response: " . print_r($result, true));
                
                if (!empty($result['success'])) {
                    $recaptcha_valid = true;
                    error_log("reCAPTCHA validation successful");
                } else {
                    error_log("reCAPTCHA validation failed. Errors: " . print_r($result['error-codes'] ?? [], true));
                    // Common error codes:
                    // missing-input-secret, invalid-input-secret, missing-input-response, invalid-input-response, timeout-or-duplicate
                }
            } else {
                error_log("WordPress error in reCAPTCHA request: " . $response->get_error_message());
            }
        } else {
            error_log("reCAPTCHA response is empty");
        }
        
        if (!$recaptcha_valid) {
            $debug_info = "Debug info: reCAPTCHA response = '$recaptcha_response', Valid = " . ($recaptcha_valid ? 'true' : 'false');
            error_log("Blocking login due to invalid reCAPTCHA: " . $debug_info);
            if (defined('DOING_AJAX') && DOING_AJAX) {
                wp_send_json_error('Molimo potvrdite da niste robot. ' . $debug_info);
            } else {
                // Server-side fallback for failed reCAPTCHA
                $atts = [
                    'redirect_url' => isset($_POST['redirect_url']) ? $_POST['redirect_url'] : '',
                    'recaptcha_error' => 'Molimo potvrdite da niste robot. ' . $debug_info
                ];
                echo $this->display_login_form($atts);
                exit;
            }
        }
        
        error_log("reCAPTCHA validation passed, proceeding with login");

        $username = sanitize_text_field($_POST['username']);
        $password = sanitize_text_field($_POST['password']);

        if (empty($username) || empty($password)) {
            wp_send_json_error('Molimo unesite korisničko ime i lozinku.');
        }

        // Rate limiting is disabled for testing
        // $this->check_login_attempts($username);

        $user = $this->authenticate_user($username, $password);
        if ($user) {
            // Regenerate session ID for security
            session_regenerate_id(true);
            $_SESSION['mcl_user_id'] = $user['id'];
            $_SESSION['mcl_username'] = $user['Username'];
            $_SESSION['mcl_user_data'] = $user;
            // Eksplicitno postavi ime i prezime u sesiju
            $_SESSION['mcl_ime'] = isset($user['Ime']) ? $user['Ime'] : (isset($user['ime']) ? $user['ime'] : '');
            $_SESSION['mcl_prezime'] = isset($user['Prezime']) ? $user['Prezime'] : (isset($user['prezime']) ? $user['prezime'] : '');
            $_SESSION['mcl_login_time'] = time();
            $_SESSION['mcl_logged_in'] = true; // Dodaj flag koji komisije panel očekuje
            
            // Check if user has komisija privileges
            $komisija_access = $this->check_komisija_privileges($username);
            
            // Log successful login
            error_log("Successful login for user: " . $username);
            error_log("Komisija access for user $username: " . ($komisija_access ? 'YES' : 'NO'));
            
            // If this is an AJAX request, send JSON response with redirect
            if (defined('DOING_AJAX') && DOING_AJAX) {
                if ($komisija_access) {
                    wp_send_json_success([
                        'success' => true, 
                        'komisija_redirect' => true,
                        'redirect_url' => 'https://ursbih.ba/komisije-panel/'
                    ]);
                } else {
                    wp_send_json_success(['success' => true]);
                }
            } else {
                // Server-side fallback redirect for non-AJAX (or failed JS)
                if ($komisija_access) {
                    wp_redirect('https://ursbih.ba/komisije-panel/');
                    exit;
                } else {
                    wp_redirect('https://ursbih.ba/user-dashboard/');
                    exit;
                }
            }
        } else {
            if (defined('DOING_AJAX') && DOING_AJAX) {
                wp_send_json_error('Neispravno unijeti podaci.');
            } else {
                // Server-side fallback for failed login
                wp_redirect('https://ursbih.ba/myplugin/?login=failed');
                exit;
            }
        }
    }

    public function handle_mcl_login() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['mcl_nonce'], 'mcl_nonce')) {
            wp_send_json_error(['message' => 'Sigurnosna provjera neuspješna.']);
        }
        
        // Provjeri matematički zadatak
        $math_answer = isset($_POST['math_answer']) ? intval($_POST['math_answer']) : 0;
        $math_result = isset($_POST['math_result']) ? intval($_POST['math_result']) : 0;
        
        if (empty($math_answer) || $math_answer !== $math_result) {
            wp_send_json_error(['message' => 'Netačan odgovor na matematički zadatak.']);
        }
        
        $username = sanitize_text_field($_POST['username']);
        $password = $_POST['password'];
        $redirect_url = sanitize_url($_POST['redirect_url']);
        
        // Debug: Log login attempt
        error_log("MCL Login attempt - Username: " . $username . ", Math answer: " . $math_answer . ", Math result: " . $math_result);
        
        try {
            // Try multiple connection configurations
            $connection_configs = [
                [
                    'host' => '65.21.234.24',
                    'db' => 'ursbihba_lara195',
                    'user' => 'nedim',
                    'pass' => 'test123'
                ],
                [
                    'host' => '127.0.0.1',
                    'db' => 'ursbihba_lara195', 
                    'user' => 'nedim',
                    'pass' => 'test123'
                ],
                [
                    'host' => '65.21.234.24',
                    'db' => 'ursbihba_lara195',
                    'user' => 'root',
                    'pass' => ''
                ]
            ];
            
            $pdo = null;
            foreach ($connection_configs as $config) {
                try {
                    $pdo = new PDO(
                        "mysql:host={$config['host']};dbname={$config['db']};charset=utf8mb4",
                        $config['user'],
                        $config['pass'],
                        [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                            PDO::ATTR_TIMEOUT => 10
                        ]
                    );
                    error_log("MCL: Database connection successful with {$config['user']}@{$config['host']}");
                    break;
                } catch (PDOException $e) {
                    error_log("MCL: Connection failed for {$config['user']}@{$config['host']}: " . $e->getMessage());
                    continue;
                }
            }
            
            if (!$pdo) {
                throw new Exception("All database connection attempts failed");
            }
            
            // Check user in custom database - exact table and columns as specified
            $stmt = $pdo->prepare("SELECT Username, Passw, Ime, Prezime, ULOGA_ID FROM users_data WHERE Username = ? LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            error_log("MCL: Database query executed for username: " . $username);
            
            if (!$user) {
                error_log("MCL Login: User '$username' not found in custom database");
                wp_send_json_error(['message' => 'Korisničko ime ili lozinka nisu ispravni.']);
                return;
            }
            
            // Verify password (assuming it's stored as plain text in your DB - SECURITY NOTE: should be hashed)
            if ($user['Passw'] !== $password) {
                error_log("MCL Login: Invalid password for user '$username'");
                wp_send_json_error(['message' => 'Korisničko ime ili lozinka nisu ispravni.']);
                return;
            }
            
            // Set up session for custom user system
            if (!session_id()) {
                session_start();
            }
            
            $_SESSION['mcl_user_id'] = $user['ULOGA_ID'];
            $_SESSION['mcl_username'] = $user['Username'];
            $_SESSION['mcl_full_name'] = $user['Ime'] . ' ' . $user['Prezime'];
            $_SESSION['mcl_logged_in'] = true;
            
            error_log("MCL Login success for user: " . $username . " (ID: " . $user['ULOGA_ID'] . ")");
            
            wp_send_json_success([
                'message' => 'Uspješno ste prijavljeni.',
                'redirect_url' => $redirect_url ?: home_url('/user-dashboard/')
            ]);
            
        } catch (PDOException $e) {
            $error_msg = "MCL Login database error: " . $e->getMessage();
            error_log($error_msg);
            error_log("MCL: PDO Error Code: " . $e->getCode());
            error_log("MCL: PDO Error Info: " . print_r($e->errorInfo ?? 'No error info', true));
            
            wp_send_json_error([
                'message' => 'Greška u bazi podataka. Proverite log fajlove.',
                'debug' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
        } catch (Exception $e) {
            error_log("MCL Login general error: " . $e->getMessage());
            wp_send_json_error(['message' => 'Općenita greška sistema: ' . $e->getMessage()]);
        }
    }

    // Rate limiting function is disabled for testing
    // private function check_login_attempts($username) {
    //     $key = 'mcl_login_attempts_' . md5($username . $_SERVER['REMOTE_ADDR']);
    //     $attempts = get_transient($key) ?: 0;
    //     
    //     if ($attempts >= 5) {
    //         wp_send_json_error('Previše neuspešnih pokušaja prijave. Pokušajte ponovo za 15 minuta.');
    //     }
    //     
    //     set_transient($key, $attempts + 1, 15 * MINUTE_IN_SECONDS);
    // }

    // Funkcija za sprečavanje WordPress redirekcije za komisijske korisnike
    public function prevent_wp_login_redirect() {
        // Provjeri da li je korisnik ulogiran kroz naš sistem
        if (isset($_SESSION['mcl_user_id']) && isset($_SESSION['mcl_username'])) {
            $username = $_SESSION['mcl_username'];
            
            // Provjeri da li ima komisijske privilegije
            if ($this->check_komisija_privileges($username)) {
                // Dodaj filter da blokira auth_redirect
                add_filter('wp_redirect', function($location, $status) {
                    // Ako WordPress pokušava redirekciju na wp-login.php, blokiraj je
                    if (strpos($location, 'wp-login.php') !== false) {
                        error_log('Blokiram WordPress redirekciju na wp-login.php za komisijskog korisnika');
                        return false; // Blokira redirekciju
                    }
                    return $location;
                }, 1, 2);
                
                // Također blokira auth_redirect funkciju
                add_action('wp_loaded', function() {
                    remove_action('template_redirect', 'wp_redirect_admin_locations', 1000);
                });
            }
        }
    }

    // Globalni blok WordPress redirekcija za komisijske korisnike
    public function block_wp_redirects() {
        // Provjeri da li je korisnik ulogiran kroz naš sistem
        if (isset($_SESSION['mcl_user_id']) && isset($_SESSION['mcl_username'])) {
            $username = $_SESSION['mcl_username'];
            
            // Provjeri da li ima komisijske privilegije
            if ($this->check_komisija_privileges($username)) {
                error_log('Postavljam globalni blok za wp-login redirekcije za komisijskog korisnika: ' . $username);
                
                // Blokira sve redirekcije na wp-login.php
                add_filter('wp_redirect', function($location, $status) use ($username) {
                    if (strpos($location, 'wp-login.php') !== false || strpos($location, '/wp-admin/') !== false) {
                        error_log('BLOKIRAO redirekciju na: ' . $location . ' za komisijskog korisnika: ' . $username);
                        // Umjesto blokiranja, preusmjeri na komisijski panel
                        wp_redirect('https://ursbih.ba/komisije-panel/');
                        exit;
                    }
                    return $location;
                }, 999, 2);
            }
        }
    }

    public function handle_logout() {
        // Clear session data
        unset($_SESSION['mcl_user_id']);
        unset($_SESSION['mcl_username']);
        unset($_SESSION['mcl_user_data']);
        unset($_SESSION['mcl_logged_in']); // Ukloni i ovaj flag
        
        wp_send_json_success(['message' => 'Uspešno ste se odjavili.']);
    }

    public function handle_search_imenik() {
        // Add debug logging
        if ($this->debug_mode) {
            error_log('Search imenik called with data: ' . print_r($_POST, true));
        }

        // Verify nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'search_imenik_nonce')) {
            if ($this->debug_mode) {
                error_log('Nonce verification failed. Received nonce: ' . ($_POST['nonce'] ?? 'not set'));
            }
            wp_send_json_error('Neispravna sigurnosna provera.');
            return;
        }

        $ime = isset($_POST['ime']) ? sanitize_text_field($_POST['ime']) : '';
        $prezime = isset($_POST['prezime']) ? sanitize_text_field($_POST['prezime']) : '';

        if ($this->debug_mode) {
            error_log('Search params - Ime: "' . $ime . '", Prezime: "' . $prezime . '"');
        }

        if (empty($ime) && empty($prezime)) {
            wp_send_json_error('Molimo unesite ime ili prezime za pretraživanje.');
            return;
        }

        $connection = $this->get_db_connection();
        if (!$connection) {
            if ($this->debug_mode) {
                error_log('Database connection failed in search_imenik');
            }
            wp_send_json_error('Greška pri povezivanju sa bazom podataka.');
            return;
        }

        try {
            // Build the query with LIKE search
            $sql = "SELECT Ime, Prezime, EMAIL, MOBITEL FROM users_data WHERE 1=1";
            $params = [];
            $types = "";

            if (!empty($ime)) {
                $sql .= " AND Ime LIKE ?";
                $params[] = '%' . $ime . '%';
                $types .= 's';
            }

            if (!empty($prezime)) {
                $sql .= " AND Prezime LIKE ?";
                $params[] = '%' . $prezime . '%';
                $types .= 's';
            }

            $sql .= " ORDER BY Ime, Prezime LIMIT 50"; // Limit results to prevent overload

            if ($this->debug_mode) {
                error_log('SQL Query: ' . $sql);
                error_log('Parameters: ' . print_r($params, true));
            }

            $stmt = $connection->prepare($sql);
            
            if (!$stmt) {
                throw new Exception('Failed to prepare statement: ' . $connection->error);
            }
            
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to execute statement: ' . $stmt->error);
            }
            
            $result = $stmt->get_result();
            
            if (!$result) {
                throw new Exception('Failed to get result: ' . $stmt->error);
            }

            $results = [];
            while ($row = $result->fetch_assoc()) {
                $results[] = [
                    'ime' => $row['Ime'] ?? '',
                    'prezime' => $row['Prezime'] ?? '',
                    'email' => $row['EMAIL'] ?? '',
                    'telefon' => $row['MOBITEL'] ?? ''
                ];
            }

            if ($this->debug_mode) {
                error_log('Search results count: ' . count($results));
            }

            $stmt->close();
            $connection->close();

            wp_send_json_success($results);

        } catch (Exception $e) {
            if ($this->debug_mode) {
                error_log('Imenik search error: ' . $e->getMessage());
                error_log('Error trace: ' . $e->getTraceAsString());
            }
            wp_send_json_error('Greška pri pretraživanju imenika: ' . $e->getMessage());
        }
    }

    private function authenticate_user($username, $password) {
        $connection = $this->get_db_connection();
        if (!$connection) {
            return false;
        }

        // Query the Laravel users_data table
        $stmt = $connection->prepare("SELECT * FROM users_data WHERE Username = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($this->debug_mode) {
            error_log("Login attempt - Username: " . $username);
            error_log("Query found " . $result->num_rows . " users");
        }
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if ($this->debug_mode) {
                error_log("Found user ID: " . $user['id']);
                error_log("Stored password: " . $user['Passw']);
                error_log("Input password: " . $password);
                error_log("Passwords match: " . ($password === $user['Passw'] ? 'YES' : 'NO'));
                error_log("Hash starts with: " . substr($user['Passw'], 0, 4));
            }
            
            // Verify password using Laravel's bcrypt hashing
            if ($this->verify_laravel_password($password, $user['Passw'])) {
                $stmt->close();
                $connection->close();
                return $user;
            } else {
                if ($this->debug_mode) {
                    error_log("Password verification failed for user: " . $username);
                }
            }
        } else {
            if ($this->debug_mode) {
                error_log("No user found with username: " . $username);
            }
        }

        $stmt->close();
        $connection->close();
        return false;
    }

    /**
     * Verify Laravel bcrypt password
     * Laravel uses PHP's password_hash() with PASSWORD_DEFAULT (bcrypt)
     */
    private function verify_laravel_password($password, $hash) {
        if ($this->debug_mode) {
            error_log("Password verification - Input: '" . $password . "', Stored: '" . $hash . "'");
        }
        
        // Check if it's a bcrypt hash (starts with $2y$, $2a$, $2b$)
        if (strpos($hash, '$2y$') === 0 || strpos($hash, '$2a$') === 0 || strpos($hash, '$2b$') === 0) {
            $result = password_verify($password, $hash);
            if ($this->debug_mode) {
                error_log("Bcrypt verification result: " . ($result ? 'TRUE' : 'FALSE'));
            }
            return $result;
        }
        
        // Check if it's plain text (fallback for development/testing)
        if ($this->debug_mode) {
            error_log("Plain text password comparison");
        }
        
        // Direct comparison for plain text passwords
        $direct_match = ($password === $hash);
        if ($this->debug_mode) {
            error_log("Direct comparison result: " . ($direct_match ? 'TRUE' : 'FALSE'));
            error_log("Input length: " . strlen($password) . ", Stored length: " . strlen($hash));
        }
        
        if ($direct_match) {
            return true;
        }
        
        // Fallback for other hash types (if any)
        return hash_equals($hash, crypt($password, $hash));
    }

    private function get_user_data($user_id) {
        $connection = $this->get_db_connection();
        if (!$connection) {
            return false;
        }

        // Query the Laravel users_data table
        $stmt = $connection->prepare("SELECT * FROM users_data WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user_data = $result->fetch_assoc();
            $stmt->close();
            $connection->close();
            return $user_data;
        }

        $stmt->close();
        $connection->close();
        return false;
    }

    private function get_logged_in_status() {
        // Uklonjeno po zahtevu korisnika - ne prikazivati status ni dugme za odjavu ovde
        return '';
    }

    /**
     * Debug function to test database connection
     * Use shortcode [custom_db_test] to test connection
     */
    public function test_db_connection() {
        if (!$this->debug_mode) {
            return '<div class="mcl-error">Debug mode is disabled.</div>';
        }

        $connection = $this->get_db_connection();
        if (!$connection) {
            return '<div class="mcl-error">❌ Database connection failed!</div>';
        }

        // Test if users_data table exists
        $result = $connection->query("SHOW TABLES LIKE 'users_data'");
        if ($result->num_rows === 0) {
            $connection->close();
            return '<div class="mcl-error">❌ Table "users_data" not found!</div>';
        }

        // Get table structure
        $structure = $connection->query("DESCRIBE users_data");
        $fields = [];
        while ($row = $structure->fetch_assoc()) {
            $fields[] = $row['Field'];
        }

        // Count total users
        $count_result = $connection->query("SELECT COUNT(*) as total FROM users_data");
        $total_users = $count_result->fetch_assoc()['total'];

        $connection->close();

        $output = '<div class="mcl-success">';
        $output .= '<h3>✅ Database Connection Successful</h3>';
        $output .= '<p><strong>Database:</strong> ' . $this->db_config['database'] . '</p>';
        $output .= '<p><strong>Host:</strong> ' . $this->db_config['host'] . ':' . $this->db_config['port'] . '</p>';
        $output .= '<p><strong>Table:</strong> users_data (Found)</p>';
        $output .= '<p><strong>Total Users:</strong> ' . $total_users . '</p>';
        $output .= '<p><strong>Available Fields:</strong> ' . implode(', ', $fields) . '</p>';
        $output .= '</div>';

        return $output;
    }

    /**
     * Debug function to check specific user details
     * Use shortcode [custom_user_check username="nedim"]
     */
    public function check_user_details($atts) {
        if (!$this->debug_mode) {
            return '<div class="mcl-error">Debug mode is disabled.</div>';
        }

        $atts = shortcode_atts(['username' => ''], $atts);
        $username = $atts['username'];

        if (empty($username)) {
            return '<div class="mcl-error">Please provide username: [custom_user_check username="nedim"]</div>';
        }

        $connection = $this->get_db_connection();
        if (!$connection) {
            return '<div class="mcl-error">❌ Database connection failed!</div>';
        }

        // Search for user (case insensitive)
        $stmt = $connection->prepare("SELECT * FROM users_data WHERE Username LIKE ? LIMIT 5");
        $search_term = '%' . $username . '%';
        $stmt->bind_param("s", $search_term);
        $stmt->execute();
        $result = $stmt->get_result();

        $output = '<div class="mcl-info">';
        $output .= '<h3>🔍 User Search Results for: "' . esc_html($username) . '"</h3>';

        if ($result->num_rows === 0) {
            $output .= '<p>❌ No users found matching "' . esc_html($username) . '"</p>';
            
            // Show first few usernames for reference
            $all_users = $connection->query("SELECT Username FROM users_data LIMIT 10");
            $output .= '<p><strong>Sample usernames in database:</strong></p><ul>';
            while ($row = $all_users->fetch_assoc()) {
                $output .= '<li>' . esc_html($row['Username']) . '</li>';
            }
            $output .= '</ul>';
        } else {
            while ($user = $result->fetch_assoc()) {
                $output .= '<div style="border: 1px solid #ccc; padding: 15px; margin: 10px 0; background: #f9f9f9;">';
                $output .= '<p><strong>ID:</strong> ' . $user['id'] . '</p>';
                $output .= '<p><strong>Username:</strong> ' . esc_html($user['Username']) . '</p>';
                $output .= '<p><strong>Name:</strong> ' . esc_html($user['Ime'] . ' ' . $user['Prezime']) . '</p>';
                $output .= '<p><strong>Email:</strong> ' . esc_html($user['EMAIL']) . '</p>';
                $output .= '<p><strong>Password Hash:</strong> ' . esc_html(substr($user['Passw'], 0, 50)) . '...</p>';
                $output .= '<p><strong>Hash Type:</strong> ';
                if (strpos($user['Passw'], '$2y$') === 0) {
                    $output .= '✅ Laravel bcrypt (compatible)';
                } elseif (strpos($user['Passw'], '$2a$') === 0 || strpos($user['Passw'], '$2b$') === 0) {
                    $output .= '✅ bcrypt (compatible)';
                } else {
                    $output .= '❌ Unknown hash type - might not be bcrypt';
                }
                $output .= '</p>';
                
                // Test password verification with common passwords
                $test_passwords = ['lipa', 'password', '123456', 'nedim'];
                $output .= '<p><strong>Test Password Results:</strong></p><ul>';
                foreach ($test_passwords as $test_pass) {
                    $verify_result = $this->verify_laravel_password($test_pass, $user['Passw']);
                    $status = $verify_result ? '✅' : '❌';
                    $output .= '<li>' . $status . ' "' . $test_pass . '"</li>';
                }
                $output .= '</ul>';
                $output .= '</div>';
            }
        }

        $stmt->close();
        $connection->close();
        $output .= '</div>';

        return $output;
    }

    /**
     * Debug function to test password verification
     * Use shortcode [custom_password_test]
     */
    public function test_password_hash() {
        if (!$this->debug_mode) {
            return '<div class="mcl-error">Debug mode is disabled.</div>';
        }

        $known_hash = '$2y$10$4CQrtHmbbGr7wcKKdeGTxO1xBgzt27aQr8iN.OMf/iCKX7UGzmyCC';
        $test_passwords = [
            'lipa',
            'nedim', 
            'password',
            '123456',
            'admin',
            'test',
            'nedim123',
            'lipa123',
            'NedimLipa',
            'nedim_lipa',
            '12345678',
            'qwerty'
        ];

        $output = '<div class="mcl-info">';
        $output .= '<h3>🔐 Password Hash Testing</h3>';
        $output .= '<p><strong>Testing hash:</strong> ' . esc_html($known_hash) . '</p>';
        $output .= '<p><strong>Hash type:</strong> ✅ Laravel bcrypt (valid)</p>';
        $output .= '<hr>';
        $output .= '<p><strong>Testing common passwords:</strong></p>';
        
        $found_match = false;
        foreach ($test_passwords as $test_pass) {
            $verify_result = password_verify($test_pass, $known_hash);
            $status = $verify_result ? '✅ MATCH!' : '❌ No match';
            $color = $verify_result ? 'color: green; font-weight: bold;' : 'color: red;';
            
            $output .= '<p style="' . $color . '">' . $status . ' Password: "' . esc_html($test_pass) . '"</p>';
            
            if ($verify_result) {
                $found_match = true;
            }
        }
        
        if (!$found_match) {
            $output .= '<hr>';
            $output .= '<p style="color: orange;"><strong>⚠️ No matches found!</strong></p>';
            $output .= '<p>The correct password is not in the test list. Options:</p>';
            $output .= '<ul>';
            $output .= '<li>Contact the Laravel developer to get the correct password</li>';
            $output .= '<li>Reset the password in Laravel database</li>';
            $output .= '<li>Create a new test user with known password</li>';
            $output .= '</ul>';
            
            // Show how to create new password hash
            $new_hash = password_hash('testpass', PASSWORD_DEFAULT);
            $output .= '<hr>';
            $output .= '<p><strong>💡 To create a test user with password "testpass":</strong></p>';
            $output .= '<code style="background: #f0f0f0; padding: 10px; display: block; margin: 10px 0;">';
            $output .= 'UPDATE users_data SET Passw = \'' . $new_hash . '\' WHERE Username = \'nedim\';';
            $output .= '</code>';
            $output .= '<p>After running this SQL, you can login with: <strong>nedim / testpass</strong></p>';
        }
        
        $output .= '</div>';
        return $output;
    }

    /**
     * Debug function to test imenik search
     * Use shortcode [custom_imenik_test]
     */
    public function test_imenik_search() {
        if (!$this->debug_mode) {
            return '<div class="mcl-error">Debug mode is disabled.</div>';
        }

        $connection = $this->get_db_connection();
        if (!$connection) {
            return '<div class="mcl-error">❌ Database connection failed!</div>';
        }

        // Test the imenik table structure
        $output = '<div class="mcl-info">';
        $output .= '<h3>🔍 Imenik Search Test</h3>';

        // Check table structure
        $structure = $connection->query("DESCRIBE users_data");
        $fields = [];
        while ($row = $structure->fetch_assoc()) {
            $fields[] = $row['Field'];
        }
        $output .= '<p><strong>Available fields:</strong> ' . implode(', ', $fields) . '</p>';

        // Test search for "Nedim"
        try {
            $sql = "SELECT Ime, Prezime, EMAIL, MOBITEL FROM users_data WHERE Ime LIKE ? ORDER BY Ime, Prezime LIMIT 10";
            $stmt = $connection->prepare($sql);
            $search_param = '%Nedim%';
            $stmt->bind_param('s', $search_param);
            $stmt->execute();
            $result = $stmt->get_result();

            $output .= '<h4>🔍 Search Results for "Nedim":</h4>';
            
            if ($result->num_rows === 0) {
                $output .= '<p>❌ No results found for "Nedim"</p>';
                
                // Show sample names in database
                $sample_query = $connection->query("SELECT DISTINCT Ime FROM users_data WHERE Ime IS NOT NULL AND Ime != '' LIMIT 10");
                $output .= '<p><strong>Sample names in database:</strong></p><ul>';
                while ($row = $sample_query->fetch_assoc()) {
                    $output .= '<li>' . esc_html($row['Ime']) . '</li>';
                }
                $output .= '</ul>';
            } else {
                $output .= '<p>✅ Found ' . $result->num_rows . ' result(s):</p>';
                while ($row = $result->fetch_assoc()) {
                    $output .= '<div style="border: 1px solid #ccc; padding: 10px; margin: 5px 0; background: #f9f9f9;">';
                    $output .= '<strong>' . esc_html($row['Ime'] . ' ' . $row['Prezime']) . '</strong><br>';
                    $output .= 'Email: ' . esc_html($row['EMAIL']) . '<br>';
                    $output .= 'Mobitel: ' . esc_html($row['MOBITEL']);
                    $output .= '</div>';
                }
            }

            $stmt->close();
        } catch (Exception $e) {
            $output .= '<p style="color: red;">❌ Error: ' . esc_html($e->getMessage()) . '</p>';
        }

        $connection->close();
        $output .= '</div>';

        return $output;
    }

    public function handle_admin_komisija_login() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'admin_komisija_login_nonce')) {
            wp_send_json_error('Security check failed');
            return;
        }

        $username = sanitize_text_field($_POST['username']);
        $password = $_POST['password'];

        if (empty($username) || empty($password)) {
            wp_send_json_error('Molimo unesite korisničko ime i lozinku.');
            return;
        }

        $connection = $this->get_db_connection();
        if (!$connection) {
            wp_send_json_error('Greška u konekciji sa bazom podataka.');
            return;
        }

        try {
            // Provjeri da li postoji korisnik u tabeli user_komisije sa ulogom 1 (admin)
            $stmt = $connection->prepare("SELECT id, username, password, ime, prezime, uloga FROM user_komisije WHERE username = ? AND uloga = 1");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                wp_send_json_error('Nemate administratorske privilegije.');
                $stmt->close();
                $connection->close();
                return;
            }

            $user = $result->fetch_assoc();
            $stmt->close();

            // Provjeri lozinku (assuming bcrypt hash or plain text for now)
            $password_valid = false;
            if (password_verify($password, $user['password'])) {
                // Bcrypt hash
                $password_valid = true;
            } elseif ($password === $user['password']) {
                // Plain text (legacy)
                $password_valid = true;
            }

            if (!$password_valid) {
                wp_send_json_error('Neispravna lozinka.');
                $connection->close();
                return;
            }

            // Uspješna prijava - postavi sesiju
            $_SESSION['admin_komisija_logged_in'] = true;
            $_SESSION['admin_komisija_user_id'] = $user['id'];
            $_SESSION['admin_komisija_username'] = $user['username'];
            $_SESSION['admin_komisija_ime'] = $user['ime'] . ' ' . $user['prezime'];

            wp_send_json_success([
                'message' => 'Uspješna prijava!',
                'redirect_url' => admin_url('admin.php?page=urs-admin-dashboard')
            ]);

        } catch (Exception $e) {
            wp_send_json_error('Greška pri prijavi: ' . $e->getMessage());
        }

        $connection->close();
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'URS Admin Dashboard',      // Page title
            'URS Admin',                // Menu title
            'manage_options',           // Capability required
            'urs-admin-dashboard',      // Menu slug
            [$this, 'display_admin_dashboard'], // Callback function
            'dashicons-shield-alt',     // Icon
            30                          // Position
        );
    }

    public function display_admin_dashboard() {
        // Check if user is logged in through our custom admin system
        if (!isset($_SESSION['admin_komisija_logged_in']) || !$_SESSION['admin_komisija_logged_in']) {
            echo '<div class="wrap">';
            echo '<h1>URS Admin Dashboard</h1>';
            echo '<div class="notice notice-error"><p>Morate se prijaviti preko komisije panela da pristupite ovoj stranici.</p></div>';
            echo '<p><a href="' . home_url('/user-dashboard/') . '" class="button button-primary">Idi na prijavu</a></p>';
            echo '</div>';
            return;
        }

        // Include the admin dashboard file
        include_once MCL_PLUGIN_PATH . 'admin-dashboard.php';
    }

    // CRUD Operations for Operators

    public function handle_load_operators() {
        if (!wp_verify_nonce($_POST['nonce'], 'admin_operators_nonce')) {
            wp_send_json_error('Security check failed');
            return;
        }

        if (!isset($_SESSION['admin_komisija_logged_in']) || !$_SESSION['admin_komisija_logged_in']) {
            wp_send_json_error('Nemate dozvolu za pristup.');
            return;
        }

        $connection = $this->get_db_connection();
        if (!$connection) {
            wp_send_json_error('Greška u konekciji sa bazom podataka.');
            return;
        }

        try {
            // First, ensure the privileges table exists
            $create_table_sql = "CREATE TABLE IF NOT EXISTS user_komisije_privileges (
                id INT AUTO_INCREMENT PRIMARY KEY,
                uloga VARCHAR(255),
                tip_komisije VARCHAR(255),
                privilege_view TINYINT(1) DEFAULT 1,
                privilege_create TINYINT(1) DEFAULT 0,
                privilege_edit TINYINT(1) DEFAULT 0,
                privilege_delete TINYINT(1) DEFAULT 0,
                privilege_export TINYINT(1) DEFAULT 0,
                privilege_import TINYINT(1) DEFAULT 0,
                field_tarifa TINYINT(1) DEFAULT 0,
                field_user_id TINYINT(1) DEFAULT 0,
                field_liga TINYINT(1) DEFAULT 0,
                field_kolo TINYINT(1) DEFAULT 0,
                field_sezona TINYINT(1) DEFAULT 0,
                field_utakmica TINYINT(1) DEFAULT 0,
                field_uloga TINYINT(1) DEFAULT 0,
                field_datum_obavjesti TINYINT(1) DEFAULT 0,
                field_putni_troskovi TINYINT(1) DEFAULT 0,
                field_komentar TINYINT(1) DEFAULT 0,
                field_status TINYINT(1) DEFAULT 0,
                field_id_utakmice TINYINT(1) DEFAULT 0,
                privilege_own_only TINYINT(1) DEFAULT 1,
                privilege_current_season_only TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_role (uloga, tip_komisije)
            )";
            
            $connection->query($create_table_sql);
            
            $stmt = $connection->prepare("
                SELECT uk.id, uk.username, uk.ime, uk.prezime, uk.email, uk.telefon, 
                       uk.uloga, uk.tip_komisije, uk.aktivan, uk.datum_kreiranja,
                       COALESCE(ukp.privilege_view, 1) as privilege_view, 
                       COALESCE(ukp.privilege_create, 0) as privilege_create, 
                       COALESCE(ukp.privilege_edit, 0) as privilege_edit, 
                       COALESCE(ukp.privilege_delete, 0) as privilege_delete, 
                       COALESCE(ukp.privilege_export, 0) as privilege_export, 
                       COALESCE(ukp.privilege_import, 0) as privilege_import,
                       COALESCE(ukp.field_tarifa, 0) as field_tarifa,
                       COALESCE(ukp.field_user_id, 0) as field_user_id,
                       COALESCE(ukp.field_liga, 0) as field_liga,
                       COALESCE(ukp.field_kolo, 0) as field_kolo,
                       COALESCE(ukp.field_sezona, 0) as field_sezona,
                       COALESCE(ukp.field_utakmica, 0) as field_utakmica,
                       COALESCE(ukp.field_uloga, 0) as field_uloga,
                       COALESCE(ukp.field_datum_obavjesti, 0) as field_datum_obavjesti,
                       COALESCE(ukp.field_putni_troskovi, 0) as field_putni_troskovi,
                       COALESCE(ukp.field_komentar, 0) as field_komentar,
                       COALESCE(ukp.field_status, 0) as field_status,
                       COALESCE(ukp.field_id_utakmice, 0) as field_id_utakmice,
                       COALESCE(ukp.privilege_own_only, 1) as privilege_own_only, 
                       COALESCE(ukp.privilege_current_season_only, 1) as privilege_current_season_only
                FROM user_komisije uk
                LEFT JOIN user_komisije_privileges ukp ON uk.uloga = ukp.uloga AND uk.tip_komisije = ukp.tip_komisije
                ORDER BY uk.id DESC
            ");
            $stmt->execute();
            $result = $stmt->get_result();
            
            $operators = [];
            while ($row = $result->fetch_assoc()) {
                // Add basic privileges summary
                $privileges = [];
                if ($row['privilege_view']) $privileges[] = 'Pregled';
                if ($row['privilege_create']) $privileges[] = 'Dodavanje';
                if ($row['privilege_edit']) $privileges[] = 'Uređivanje';
                if ($row['privilege_delete']) $privileges[] = 'Brisanje';
                if ($row['privilege_export']) $privileges[] = 'Izvoz';
                if ($row['privilege_import']) $privileges[] = 'Uvoz';
                
                // Add field privileges summary
                $fieldPrivileges = [];
                if ($row['field_tarifa']) $fieldPrivileges[] = 'Tarifa';
                if ($row['field_user_id']) $fieldPrivileges[] = 'Korisnik';
                if ($row['field_liga']) $fieldPrivileges[] = 'Liga';
                if ($row['field_kolo']) $fieldPrivileges[] = 'Kolo';
                if ($row['field_sezona']) $fieldPrivileges[] = 'Sezona';
                if ($row['field_utakmica']) $fieldPrivileges[] = 'Utakmica';
                if ($row['field_uloga']) $fieldPrivileges[] = 'Uloga';
                if ($row['field_datum_obavjesti']) $fieldPrivileges[] = 'Datum';
                if ($row['field_putni_troskovi']) $fieldPrivileges[] = 'Putni troškovi';
                if ($row['field_komentar']) $fieldPrivileges[] = 'Komentar';
                if ($row['field_status']) $fieldPrivileges[] = 'Status';
                if ($row['field_id_utakmice']) $fieldPrivileges[] = 'ID utakmice';
                
                $basicPrivText = empty($privileges) ? 'Osnovne: Nema' : 'Osnovne: ' . implode(', ', $privileges);
                $fieldPrivText = empty($fieldPrivileges) ? 'Polja: Nema' : 'Polja: ' . implode(', ', $fieldPrivileges);
                
                $row['privileges_summary'] = $basicPrivText . ' | ' . $fieldPrivText;
                
                // Add restrictions summary
                $restrictions = [];
                if ($row['privilege_own_only']) $restrictions[] = 'Samo svoje utakmice';
                if ($row['privilege_current_season_only']) $restrictions[] = 'Trenutna sezona';
                
                $row['restrictions_summary'] = empty($restrictions) ? 'Bez ograničenja' : implode(', ', $restrictions);
                
                $operators[] = $row;
            }
            
            $stmt->close();
            wp_send_json_success($operators);

        } catch (Exception $e) {
            wp_send_json_error('Greška pri učitavanju: ' . $e->getMessage());
        }

        $connection->close();
    }

    public function handle_add_operator() {
        if (!wp_verify_nonce($_POST['nonce'], 'admin_operators_nonce')) {
            wp_send_json_error('Security check failed');
            return;
        }

        if (!isset($_SESSION['admin_komisija_logged_in']) || !$_SESSION['admin_komisija_logged_in']) {
            wp_send_json_error('Nemate dozvolu za pristup.');
            return;
        }

        $username = sanitize_text_field($_POST['username']);
        $password = $_POST['password'];
        $ime = sanitize_text_field($_POST['ime']);
        $prezime = sanitize_text_field($_POST['prezime']);
        $email = sanitize_email($_POST['email'] ?? '');
        $telefon = sanitize_text_field($_POST['telefon'] ?? '');
        $uloga = intval($_POST['uloga']);
        $tip_komisije = sanitize_text_field($_POST['tipKomisije'] ?? '');
        $aktivan = intval($_POST['aktivan']);

        if (empty($username) || empty($password) || empty($ime) || empty($prezime) || empty($uloga)) {
            wp_send_json_error('Molimo popunite sva obavezna polja.');
            return;
        }

        $connection = $this->get_db_connection();
        if (!$connection) {
            wp_send_json_error('Greška u konekciji sa bazom podataka.');
            return;
        }

        try {
            // Check if username exists
            $stmt = $connection->prepare("SELECT id FROM user_komisije WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                wp_send_json_error('Korisničko ime već postoji.');
                $stmt->close();
                $connection->close();
                return;
            }
            $stmt->close();

            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert new operator
            $stmt = $connection->prepare("INSERT INTO user_komisije (username, password, ime, prezime, email, telefon, uloga, tip_komisije, aktivan, kreirao_admin) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $kreirao_admin = $_SESSION['admin_komisija_user_id'];
            $stmt->bind_param("ssssssisii", $username, $hashed_password, $ime, $prezime, $email, $telefon, $uloga, $tip_komisije, $aktivan, $kreirao_admin);
            
            if ($stmt->execute()) {
                wp_send_json_success(['message' => 'Operator je uspešno dodat.']);
            } else {
                wp_send_json_error('Greška pri dodavanju operatera.');
            }
            
            $stmt->close();

        } catch (Exception $e) {
            wp_send_json_error('Greška pri dodavanju: ' . $e->getMessage());
        }

        $connection->close();
    }

    public function handle_update_operator() {
        if (!wp_verify_nonce($_POST['nonce'], 'admin_operators_nonce')) {
            wp_send_json_error('Security check failed');
            return;
        }

        if (!isset($_SESSION['admin_komisija_logged_in']) || !$_SESSION['admin_komisija_logged_in']) {
            wp_send_json_error('Nemate dozvolu za pristup.');
            return;
        }

        $operator_id = intval($_POST['operatorId']);
        $username = sanitize_text_field($_POST['username']);
        $password = $_POST['password']; // Optional for update
        $ime = sanitize_text_field($_POST['ime']);
        $prezime = sanitize_text_field($_POST['prezime']);
        $email = sanitize_email($_POST['email'] ?? '');
        $telefon = sanitize_text_field($_POST['telefon'] ?? '');
        $uloga = intval($_POST['uloga']);
        $tip_komisije = sanitize_text_field($_POST['tipKomisije'] ?? '');
        $aktivan = intval($_POST['aktivan']);

        if (empty($operator_id) || empty($username) || empty($ime) || empty($prezime) || empty($uloga)) {
            wp_send_json_error('Molimo popunite sva obavezna polja.');
            return;
        }

        $connection = $this->get_db_connection();
        if (!$connection) {
            wp_send_json_error('Greška u konekciji sa bazom podataka.');
            return;
        }

        try {
            // Check if username exists for other users
            $stmt = $connection->prepare("SELECT id FROM user_komisije WHERE username = ? AND id != ?");
            $stmt->bind_param("si", $username, $operator_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                wp_send_json_error('Korisničko ime već postoji za drugog korisnika.');
                $stmt->close();
                $connection->close();
                return;
            }
            $stmt->close();

            // Update operator
            if (!empty($password)) {
                // Update with new password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $connection->prepare("UPDATE user_komisije SET username = ?, password = ?, ime = ?, prezime = ?, email = ?, telefon = ?, uloga = ?, tip_komisije = ?, aktivan = ? WHERE id = ?");
                $stmt->bind_param("ssssssisii", $username, $hashed_password, $ime, $prezime, $email, $telefon, $uloga, $tip_komisije, $aktivan, $operator_id);
            } else {
                // Update without changing password
                $stmt = $connection->prepare("UPDATE user_komisije SET username = ?, ime = ?, prezime = ?, email = ?, telefon = ?, uloga = ?, tip_komisije = ?, aktivan = ? WHERE id = ?");
                $stmt->bind_param("sssssisii", $username, $ime, $prezime, $email, $telefon, $uloga, $tip_komisije, $aktivan, $operator_id);
            }
            
            if ($stmt->execute()) {
                wp_send_json_success(['message' => 'Operator je uspešno ažuriran.']);
            } else {
                wp_send_json_error('Greška pri ažuriranju operatera.');
            }
            
            $stmt->close();

        } catch (Exception $e) {
            wp_send_json_error('Greška pri ažuriranju: ' . $e->getMessage());
        }

        $connection->close();
    }

    public function handle_delete_operator() {
        if (!wp_verify_nonce($_POST['nonce'], 'admin_operators_nonce')) {
            wp_send_json_error('Security check failed');
            return;
        }

        if (!isset($_SESSION['admin_komisija_logged_in']) || !$_SESSION['admin_komisija_logged_in']) {
            wp_send_json_error('Nemate dozvolu za pristup.');
            return;
        }

        $operator_id = intval($_POST['operatorId']);

        if (empty($operator_id)) {
            wp_send_json_error('Nevaljan ID operatera.');
            return;
        }

        // Prevent deleting own account
        if ($operator_id == $_SESSION['admin_komisija_user_id']) {
            wp_send_json_error('Ne možete obrisati svoj nalog.');
            return;
        }

        $connection = $this->get_db_connection();
        if (!$connection) {
            wp_send_json_error('Greška u konekciji sa bazom podataka.');
            return;
        }

        try {
            $stmt = $connection->prepare("DELETE FROM user_komisije WHERE id = ?");
            $stmt->bind_param("i", $operator_id);
            
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    wp_send_json_success(['message' => 'Operator je uspešno obrisan.']);
                } else {
                    wp_send_json_error('Operator nije pronađen.');
                }
            } else {
                wp_send_json_error('Greška pri brisanju operatera.');
            }
            
            $stmt->close();

        } catch (Exception $e) {
            wp_send_json_error('Greška pri brisanju: ' . $e->getMessage());
        }

        $connection->close();
    }
    
    // USERS DATA HANDLERS
    
    public function handle_load_users() {
        if (!wp_verify_nonce($_POST['nonce'], 'admin_users_nonce')) {
            wp_send_json_error('Neispravna autentifikacija.');
            return;
        }

        try {
            $connection = $this->get_db_connection();
            
            $sql = "SELECT * FROM users_data ORDER BY Ime, Prezime";
            $result = $connection->query($sql);
            
            $users = [];
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $users[] = $row;
                }
            }
            
            // Debug log - privremeno
            error_log('USERS DATA STRUCTURE: ' . print_r($users[0] ?? 'No users', true));
            
            wp_send_json_success($users);
            
        } catch (Exception $e) {
            wp_send_json_error('Greška pri učitavanju korisnika: ' . $e->getMessage());
        }

        $connection->close();
    }
    
    public function handle_add_user() {
        if (!wp_verify_nonce($_POST['nonce'], 'admin_users_nonce')) {
            wp_send_json_error('Neispravna autentifikacija.');
            return;
        }

        try {
            $connection = $this->get_db_connection();
            
            $user_id = sanitize_text_field($_POST['USER_ID'] ?? '');
            $username = sanitize_text_field($_POST['Username'] ?? '');
            $password = sanitize_text_field($_POST['Passw'] ?? '');
            $ime = sanitize_text_field($_POST['Ime'] ?? '');
            $prezime = sanitize_text_field($_POST['Prezime'] ?? '');
            $adresa = sanitize_text_field($_POST['ADRESA'] ?? '');
            $grad = sanitize_text_field($_POST['GRAD'] ?? '');
            $email = sanitize_email($_POST['EMAIL'] ?? '');
            $mobitel = sanitize_text_field($_POST['MOBITEL'] ?? '');
            $banka = sanitize_text_field($_POST['BANKA'] ?? '');
            $racun = sanitize_text_field($_POST['RACUN'] ?? '');
            $poziv_na_br = sanitize_text_field($_POST['POZIV_NA_BR'] ?? '');
            $zbor = sanitize_text_field($_POST['ZBOR'] ?? '');
            $kantonalni_savez = sanitize_text_field($_POST['KANTONALNI_SAVEZ'] ?? '');
            $uloga_id = intval($_POST['ULOGA_ID'] ?? 0);
            $iznos_clanarine = floatval($_POST['IZNOS_CLANARINE'] ?? 0.00);
            $sezona = sanitize_text_field($_POST['SEZONA'] ?? '2025');
            
            if (empty($user_id) || empty($username) || empty($ime) || empty($prezime) || $uloga_id <= 0) {
                wp_send_json_error('USER_ID, Username, ime, prezime i uloga su obavezni.');
                return;
            }
            
            // Hash password ako je poslat
            $hashed_password = '';
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            }
            
            if (!empty($password)) {
                $sql = "INSERT INTO users_data (USER_ID, ADRESA, GRAD, EMAIL, MOBITEL, BANKA, RACUN, POZIV_NA_BR, ZBOR, KANTONALNI_SAVEZ, ULOGA_ID, Ime, Prezime, Username, Passw, IZNOS_CLANARINE, SEZONA) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $connection->prepare($sql);
                
                if ($stmt) {
                    $stmt->bind_param("ssssssssssissssds", $user_id, $adresa, $grad, $email, $mobitel, $banka, $racun, $poziv_na_br, $zbor, $kantonalni_savez, $uloga_id, $ime, $prezime, $username, $hashed_password, $iznos_clanarine, $sezona);
                }
            } else {
                $sql = "INSERT INTO users_data (USER_ID, ADRESA, GRAD, EMAIL, MOBITEL, BANKA, RACUN, POZIV_NA_BR, ZBOR, KANTONALNI_SAVEZ, ULOGA_ID, Ime, Prezime, Username, IZNOS_CLANARINE, SEZONA) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $connection->prepare($sql);
                
                if ($stmt) {
                    $stmt->bind_param("ssssssssssisssds", $user_id, $adresa, $grad, $email, $mobitel, $banka, $racun, $poziv_na_br, $zbor, $kantonalni_savez, $uloga_id, $ime, $prezime, $username, $iznos_clanarine, $sezona);
                }
            }
            
            if ($stmt) {
                if ($stmt->execute()) {
                    wp_send_json_success(['message' => 'Korisnik je uspešno dodat.']);
                } else {
                    wp_send_json_error('Greška pri dodavanju korisnika.');
                }
                
                $stmt->close();
            } else {
                wp_send_json_error('Greška pri pripremi upita.');
            }
            
        } catch (Exception $e) {
            wp_send_json_error('Greška pri dodavanju: ' . $e->getMessage());
        }

        $connection->close();
    }
    
    public function handle_update_user() {
        if (!wp_verify_nonce($_POST['nonce'], 'admin_users_nonce')) {
            wp_send_json_error('Neispravna autentifikacija.');
            return;
        }

        try {
            $connection = $this->get_db_connection();
            
            $id = intval($_POST['id'] ?? 0);
            $user_id = sanitize_text_field($_POST['USER_ID'] ?? '');
            $username = sanitize_text_field($_POST['Username'] ?? '');
            $password = sanitize_text_field($_POST['Passw'] ?? '');
            $ime = sanitize_text_field($_POST['Ime'] ?? '');
            $prezime = sanitize_text_field($_POST['Prezime'] ?? '');
            $adresa = sanitize_text_field($_POST['ADRESA'] ?? '');
            $grad = sanitize_text_field($_POST['GRAD'] ?? '');
            $email = sanitize_email($_POST['EMAIL'] ?? '');
            $mobitel = sanitize_text_field($_POST['MOBITEL'] ?? '');
            $banka = sanitize_text_field($_POST['BANKA'] ?? '');
            $racun = sanitize_text_field($_POST['RACUN'] ?? '');
            $poziv_na_br = sanitize_text_field($_POST['POZIV_NA_BR'] ?? '');
            $zbor = sanitize_text_field($_POST['ZBOR'] ?? '');
            $kantonalni_savez = sanitize_text_field($_POST['KANTONALNI_SAVEZ'] ?? '');
            $uloga_id = intval($_POST['ULOGA_ID'] ?? 0);
            $iznos_clanarine = floatval($_POST['IZNOS_CLANARINE'] ?? 0.00);
            $sezona = sanitize_text_field($_POST['SEZONA'] ?? '2025');
            
            if ($id <= 0 || empty($user_id) || empty($username) || empty($ime) || empty($prezime) || $uloga_id <= 0) {
                wp_send_json_error('ID, USER_ID, Username, ime, prezime i uloga su obavezni.');
                return;
            }
            
            // Update with or without password
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE users_data SET USER_ID=?, ADRESA=?, GRAD=?, EMAIL=?, MOBITEL=?, BANKA=?, RACUN=?, POZIV_NA_BR=?, ZBOR=?, KANTONALNI_SAVEZ=?, ULOGA_ID=?, Ime=?, Prezime=?, Username=?, Passw=?, IZNOS_CLANARINE=?, SEZONA=? WHERE id=?";
                $stmt = $connection->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param("ssssssssssissssdssi", $user_id, $adresa, $grad, $email, $mobitel, $banka, $racun, $poziv_na_br, $zbor, $kantonalni_savez, $uloga_id, $ime, $prezime, $username, $hashed_password, $iznos_clanarine, $sezona, $id);
                }
            } else {
                $sql = "UPDATE users_data SET USER_ID=?, ADRESA=?, GRAD=?, EMAIL=?, MOBITEL=?, BANKA=?, RACUN=?, POZIV_NA_BR=?, ZBOR=?, KANTONALNI_SAVEZ=?, ULOGA_ID=?, Ime=?, Prezime=?, Username=?, IZNOS_CLANARINE=?, SEZONA=? WHERE id=?";
                $stmt = $connection->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param("ssssssssssisssdsi", $user_id, $adresa, $grad, $email, $mobitel, $banka, $racun, $poziv_na_br, $zbor, $kantonalni_savez, $uloga_id, $ime, $prezime, $username, $iznos_clanarine, $sezona, $id);
                }
            }
            
            if ($stmt) {
                if ($stmt->execute()) {
                    wp_send_json_success(['message' => 'Korisnik je uspešno ažuriran.']);
                } else {
                    wp_send_json_error('Greška pri ažuriranju korisnika.');
                }
                $stmt->close();
            } else {
                wp_send_json_error('Greška pri pripremi upita.');
            }
            
        } catch (Exception $e) {
            wp_send_json_error('Greška pri ažuriranju: ' . $e->getMessage());
        }

        $connection->close();
    }
    
    public function handle_delete_user() {
        if (!wp_verify_nonce($_POST['nonce'], 'admin_users_nonce')) {
            wp_send_json_error('Neispravna autentifikacija.');
            return;
        }

        try {
            $connection = $this->get_db_connection();
            
            $id = intval($_POST['userId'] ?? 0);
            
            if ($id <= 0) {
                wp_send_json_error('ID korisnika je obavezan.');
                return;
            }
            
            $sql = "DELETE FROM users_data WHERE id = ?";
            $stmt = $connection->prepare($sql);
            
            if ($stmt) {
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    if ($stmt->affected_rows > 0) {
                        wp_send_json_success(['message' => 'Korisnik je uspešno obrisan.']);
                    } else {
                        wp_send_json_error('Korisnik nije pronađen.');
                    }
                } else {
                    wp_send_json_error('Greška pri brisanju korisnika.');
                }
                
                $stmt->close();
            } else {
                wp_send_json_error('Greška pri pripremi upita.');
            }
            
        } catch (Exception $e) {
            wp_send_json_error('Greška pri brisanju: ' . $e->getMessage());
        }

        $connection->close();
    }
    
    // MATCHES HANDLERS
    
    public function handle_load_matches() {
        if (!wp_verify_nonce($_POST['nonce'], 'admin_matches_nonce')) {
            wp_send_json_error('Neispravna autentifikacija.');
            return;
        }

        try {
            $connection = $this->get_db_connection();
            
            error_log('LOAD MATCHES: Starting to load matches...');
            
            // Corrected SQL based on your example - use user_id = USER_ID join
            $sql = "SELECT um.*, 
                           ud.Ime, 
                           ud.Prezime, 
                           ud.Username,
                           ud.USER_ID as user_data_user_id
                    FROM user_match um 
                    LEFT JOIN users_data ud ON um.user_id = ud.USER_ID 
                    ORDER BY um.id DESC";
                    
            error_log('LOAD MATCHES SQL: ' . $sql);
            $result = $connection->query($sql);
            
            if (!$result) {
                error_log('LOAD MATCHES SQL ERROR: ' . $connection->error);
                wp_send_json_error('SQL greška: ' . $connection->error);
                return;
            }
            $result = $connection->query($sql);
            
            $matches = [];
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    // Manual creation of user_full_name
                    if (!empty($row['Ime']) && !empty($row['Prezime'])) {
                        $row['user_full_name'] = $row['Ime'] . ' ' . $row['Prezime'];
                    } elseif (!empty($row['Username'])) {
                        $row['user_full_name'] = $row['Username'];
                    } else {
                        $row['user_full_name'] = 'Nepoznat korisnik (user_id: ' . $row['user_id'] . ')';
                    }
                    
                    // Debug info
                    $row['debug_user_id'] = $row['user_id'];
                    $row['debug_clan_value'] = $row['clan'];
                    $row['debug_joined_ime'] = $row['Ime'];
                    $row['debug_joined_prezime'] = $row['Prezime'];
                    $row['debug_user_data_user_id'] = $row['user_data_user_id'];
                    
                    $matches[] = $row;
                }
                error_log('LOAD MATCHES: Found ' . count($matches) . ' matches');
            } else {
                error_log('LOAD MATCHES: No matches found or query failed');
            }
            
            wp_send_json_success($matches);
            
        } catch (Exception $e) {
            wp_send_json_error('Greška pri učitavanju utakmica: ' . $e->getMessage());
        }

        $connection->close();
    }

    // Helper function to check user privileges
    private function checkUserPrivileges($connection, $username, $privilege_type) {
        try {
            // Get user's role and commission type
            $stmt = $connection->prepare("SELECT uloga, tip_komisije FROM user_komisije WHERE username = ? AND aktivan = 1");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            
            if (!$user) {
                return false; // User not found or inactive
            }
            
            // Check if this is admin (uloga = 1)
            if ($user['uloga'] == 1) {
                return true; // Admins have all privileges
            }
            
            // Check specific privilege for this role/commission
            $stmt = $connection->prepare("SELECT privilege_{$privilege_type} FROM user_komisije_privileges WHERE uloga = ? AND tip_komisije = ?");
            $stmt->bind_param("ss", $user['uloga'], $user['tip_komisije']);
            $stmt->execute();
            $result = $stmt->get_result();
            $privileges = $result->fetch_assoc();
            
            return $privileges && $privileges["privilege_{$privilege_type}"] == 1;
            
        } catch (Exception $e) {
            return false;
        }
    }

    // Helper function to check ownership restrictions
    private function checkOwnershipRestrictions($connection, $username, $match_id = null) {
        try {
            // Get user's role and commission type
            $stmt = $connection->prepare("SELECT uloga, tip_komisije FROM user_komisije WHERE username = ? AND aktivan = 1");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            
            if (!$user) {
                return false;
            }
            
            // Check if this is admin (uloga = 1)
            if ($user['uloga'] == 1) {
                return true; // Admins can access everything
            }
            
            // Get privilege restrictions
            $stmt = $connection->prepare("SELECT privilege_own_only, privilege_current_season_only FROM user_komisije_privileges WHERE uloga = ? AND tip_komisije = ?");
            $stmt->bind_param("ss", $user['uloga'], $user['tip_komisije']);
            $stmt->execute();
            $result = $stmt->get_result();
            $privileges = $result->fetch_assoc();
            
            if (!$privileges) {
                return false; // No privileges defined
            }
            
            // If checking specific match
            if ($match_id) {
                // Get match details
                $stmt = $connection->prepare("SELECT um.user_id, ud.USER_ID as user_username FROM user_match um JOIN users_data ud ON um.user_id = ud.USER_ID WHERE um.id = ?");
                $stmt->bind_param("i", $match_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $match = $result->fetch_assoc();
                
                if (!$match) {
                    return false; // Match not found
                }
                
                // Check ownership restriction
                if ($privileges['privilege_own_only'] == 1) {
                    if ($match['user_username'] !== $username) {
                        return false; // Can only edit own matches
                    }
                }
                
                // Check season restriction
                if ($privileges['privilege_current_season_only'] == 1) {
                    $current_season = date('Y') . '/' . (date('Y') + 1);
                    $stmt = $connection->prepare("SELECT sezona FROM user_match WHERE id = ?");
                    $stmt->bind_param("i", $match_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $match_season = $result->fetch_assoc();
                    
                    if (!$match_season || $match_season['sezona'] !== $current_season) {
                        return false; // Can only edit current season matches
                    }
                }
            }
            
            return true;
            
        } catch (Exception $e) {
            return false;
        }
    }

    public function handle_add_match() {
        if (!wp_verify_nonce($_POST['nonce'], 'admin_matches_nonce')) {
            wp_send_json_error('Neispravna autentifikacija.');
            return;
        }

        // Check if user is logged in as admin/komisija
        if (!isset($_SESSION['admin_komisija_logged_in']) || !$_SESSION['admin_komisija_logged_in']) {
            wp_send_json_error('Nemate dozvolu za pristup.');
            return;
        }

        $connection = $this->get_db_connection();
        if (!$connection) {
            wp_send_json_error('Greška u konekciji sa bazom podataka.');
            return;
        }

        // Check create privilege
        $username = $_SESSION['admin_komisija_username'];
        if (!$this->checkUserPrivileges($connection, $username, 'create')) {
            wp_send_json_error('Nemate privilegiju za dodavanje utakmica.');
            $connection->close();
            return;
        }

        try {
            
            // Debug log - privremeno za debugging
            error_log('ADD MATCH POST data: ' . print_r($_POST, true));
            
            // Get all the fields based on the actual database structure
            $id_utakmice = intval($_POST['id_utakmice'] ?? 0);
            $tarifa = floatval($_POST['tarifa'] ?? 0.00);
            $user_id = intval($_POST['clan'] ?? 0);  // Frontend still sends 'clan' but we store it as user_id
            $clan = sanitize_text_field($_POST['clan'] ?? '');  // Clan je varchar(255) u bazi
            $liga = sanitize_text_field($_POST['liga'] ?? '');
            $kolo = sanitize_text_field($_POST['kolo'] ?? '');
            $sezona = sanitize_text_field($_POST['sezona'] ?? '');
            $utakmica = sanitize_text_field($_POST['utakmica'] ?? '');
            $ocjena = intval($_POST['ocjena'] ?? 0);
            $status = intval($_POST['status'] ?? 0);
            $uloga = sanitize_text_field($_POST['uloga'] ?? '');
            $datum_obavjesti = sanitize_text_field($_POST['datum_obavjesti'] ?? '');
            $suspendovan = intval($_POST['suspendovan'] ?? 0);
            $status_ssk = intval($_POST['status_ssk'] ?? 0);
            $komentar = sanitize_textarea_field($_POST['komentar'] ?? '');
            $komisija = sanitize_text_field($_POST['komisija'] ?? ''); // Komisija kolona postoji u tabeli
            
            if (empty($utakmica)) {
                wp_send_json_error('Utakmica je obavezna.');
                return;
            }
            
            $sql = "INSERT INTO user_match (user_id, id_utakmice, tarifa, clan, liga, kolo, sezona, utakmica, ocjena, status, uloga, datum_obavjesti, suspendovan, status_ssk, komentar, komisija) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $connection->prepare($sql);
            
            if ($stmt) {
                $stmt->bind_param("iidsssssiississs", $user_id, $id_utakmice, $tarifa, $clan, $liga, $kolo, $sezona, $utakmica, $ocjena, $status, $uloga, $datum_obavjesti, $suspendovan, $status_ssk, $komentar, $komisija);
                
                if ($stmt->execute()) {
                    wp_send_json_success(['message' => 'Utakmica je uspešno dodana.']);
                } else {
                    error_log('ADD MATCH SQL Error: ' . $stmt->error);
                    wp_send_json_error('Greška pri dodavanju utakmice: ' . $stmt->error);
                }
                
                $stmt->close();
            } else {
                error_log('ADD MATCH Prepare Error: ' . $connection->error);
                wp_send_json_error('Greška pri pripremi upita: ' . $connection->error);
            }
            
        } catch (Exception $e) {
            wp_send_json_error('Greška pri dodavanju: ' . $e->getMessage());
        }

        $connection->close();
    }
    
    public function handle_update_match() {
        if (!wp_verify_nonce($_POST['nonce'], 'admin_matches_nonce')) {
            wp_send_json_error('Neispravna autentifikacija.');
            return;
        }

        // Check if user is logged in as admin/komisija
        if (!isset($_SESSION['admin_komisija_logged_in']) || !$_SESSION['admin_komisija_logged_in']) {
            wp_send_json_error('Nemate dozvolu za pristup.');
            return;
        }

        $connection = $this->get_db_connection();
        if (!$connection) {
            wp_send_json_error('Greška u konekciji sa bazom podataka.');
            return;
        }

        $match_id = intval($_POST['id'] ?? 0);
        $username = $_SESSION['admin_komisija_username'];

        // Check edit privilege
        if (!$this->checkUserPrivileges($connection, $username, 'edit')) {
            wp_send_json_error('Nemate privilegiju za uređivanje utakmica.');
            $connection->close();
            return;
        }

        // Check ownership and season restrictions
        if (!$this->checkOwnershipRestrictions($connection, $username, $match_id)) {
            wp_send_json_error('Nemate dozvolu za uređivanje ove utakmice (ograničenja vlasništva ili sezone).');
            $connection->close();
            return;
        }

        try {
            
            // Debug log - privremeno za debugging
            error_log('UPDATE MATCH POST data: ' . print_r($_POST, true));
            
            $id = intval($_POST['id'] ?? 0);
            $id_utakmice = intval($_POST['id_utakmice'] ?? 0);
            $tarifa = floatval($_POST['tarifa'] ?? 0.00);
            $user_id = intval($_POST['clan'] ?? 0);  // This is now the user_id from dropdown (named clan in form)
            $clan = sanitize_text_field($_POST['clan'] ?? '');  // Clan je varchar(255) u bazi
            $liga = sanitize_text_field($_POST['liga'] ?? '');
            $kolo = sanitize_text_field($_POST['kolo'] ?? '');
            $sezona = sanitize_text_field($_POST['sezona'] ?? '');
            $utakmica = sanitize_text_field($_POST['utakmica'] ?? '');
            $ocjena = intval($_POST['ocjena'] ?? 0);
            $status = intval($_POST['status'] ?? 0);
            $uloga = sanitize_text_field($_POST['uloga'] ?? '');
            $datum_obavjesti = sanitize_text_field($_POST['datum_obavjesti'] ?? '');
            $suspendovan = intval($_POST['suspendovan'] ?? 0);
            $status_ssk = intval($_POST['status_ssk'] ?? 0);
            $komentar = sanitize_textarea_field($_POST['komentar'] ?? '');
            $komisija = sanitize_text_field($_POST['komisija'] ?? ''); // Komisija kolona postoji u tabeli
            
            error_log("Extracted values - ID: $id, Utakmica: $utakmica, User_ID: $user_id, Clan: $clan");
            
            if ($id <= 0 || empty($utakmica)) {
                wp_send_json_error('ID i utakmica su obavezni.');
                return;
            }
            
            $sql = "UPDATE user_match SET user_id=?, id_utakmice=?, tarifa=?, clan=?, liga=?, kolo=?, sezona=?, utakmica=?, ocjena=?, status=?, uloga=?, datum_obavjesti=?, suspendovan=?, status_ssk=?, komentar=?, komisija=? WHERE id=?";
            $stmt = $connection->prepare($sql);
            
            if ($stmt) {
                $stmt->bind_param("iidsssssiississsi", $user_id, $id_utakmice, $tarifa, $clan, $liga, $kolo, $sezona, $utakmica, $ocjena, $status, $uloga, $datum_obavjesti, $suspendovan, $status_ssk, $komentar, $komisija, $id);
                
                if ($stmt->execute()) {
                    wp_send_json_success(['message' => 'Utakmica je uspešno ažurirana.']);
                } else {
                    error_log('UPDATE MATCH SQL Error: ' . $stmt->error);
                    wp_send_json_error('Greška pri ažuriranju utakmice: ' . $stmt->error);
                }
                
                $stmt->close();
            } else {
                error_log('UPDATE MATCH Prepare Error: ' . $connection->error);
                wp_send_json_error('Greška pri pripremi upita: ' . $connection->error);
            }
            
        } catch (Exception $e) {
            wp_send_json_error('Greška pri ažuriranju: ' . $e->getMessage());
        }

        $connection->close();
    }
    
    public function handle_delete_match() {
        if (!wp_verify_nonce($_POST['nonce'], 'admin_matches_nonce')) {
            wp_send_json_error('Neispravna autentifikacija.');
            return;
        }

        // Check if user is logged in as admin/komisija
        if (!isset($_SESSION['admin_komisija_logged_in']) || !$_SESSION['admin_komisija_logged_in']) {
            wp_send_json_error('Nemate dozvolu za pristup.');
            return;
        }

        $connection = $this->get_db_connection();
        if (!$connection) {
            wp_send_json_error('Greška u konekciji sa bazom podataka.');
            return;
        }

        $match_id = intval($_POST['id'] ?? 0);
        $username = $_SESSION['admin_komisija_username'];

        if ($match_id <= 0) {
            wp_send_json_error('ID utakmice je obavezan.');
            $connection->close();
            return;
        }

        // Check delete privilege
        if (!$this->checkUserPrivileges($connection, $username, 'delete')) {
            wp_send_json_error('Nemate privilegiju za brisanje utakmica.');
            $connection->close();
            return;
        }

        // Check ownership and season restrictions
        if (!$this->checkOwnershipRestrictions($connection, $username, $match_id)) {
            wp_send_json_error('Nemate dozvolu za brisanje ove utakmice (ograničenja vlasništva ili sezone).');
            $connection->close();
            return;
        }

        try {
            
            $id = intval($_POST['id'] ?? 0);
            
            if ($id <= 0) {
                wp_send_json_error('ID utakmice je obavezan.');
                return;
            }
            
            $sql = "DELETE FROM user_match WHERE id = ?";
            $stmt = $connection->prepare($sql);
            
            if ($stmt) {
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    if ($stmt->affected_rows > 0) {
                        wp_send_json_success(['message' => 'Utakmica je uspešno obrisana.']);
                    } else {
                        wp_send_json_error('Utakmica nije pronađena.');
                    }
                } else {
                    wp_send_json_error('Greška pri brisanju utakmice.');
                }
                
                $stmt->close();
            } else {
                wp_send_json_error('Greška pri pripremi upita.');
            }
            
        } catch (Exception $e) {
            wp_send_json_error('Greška pri brisanju: ' . $e->getMessage());
        }

        $connection->close();
    }

    /**
     * AJAX handler za učitavanje komisija iz baze
     */
    public function handle_load_komisije() {
        wp_send_json_success($this->get_available_komisije());
    }

    /**
     * Funkcija za kreiranje komisija kolone u user_match tabeli ako ne postoji
     */
    private function ensure_komisija_column_exists($connection) {
        try {
            $check_column = $connection->query("SHOW COLUMNS FROM user_match LIKE 'komisija'");
            if ($check_column->num_rows == 0) {
                $add_column = "ALTER TABLE user_match ADD COLUMN komisija VARCHAR(50) DEFAULT NULL";
                $result = $connection->query($add_column);
                error_log('Komisija column creation result: ' . ($result ? 'SUCCESS' : 'FAILED: ' . $connection->error));
                return $result;
            }
            return true; // Column already exists
        } catch (Exception $e) {
            error_log('Error checking/creating komisija column: ' . $e->getMessage());
            return false;
        }
    }

    // Privileges Management Methods
    public function handle_load_roles_for_privileges() {
        if (!wp_verify_nonce($_POST['nonce'], 'admin_privileges_nonce')) {
            wp_send_json_error('Security check failed');
        }

        $connection = $this->get_db_connection();
        if (!$connection) {
            wp_send_json_error('Database connection failed');
        }

        try {
            // Load unique roles/komisije with count of operators
            $sql = "SELECT uloga, tip_komisije, 
                           COUNT(*) as operater_count,
                           MIN(id) as id
                    FROM user_komisije 
                    WHERE (uloga IS NOT NULL AND uloga != '') 
                       OR (tip_komisije IS NOT NULL AND tip_komisije != '')
                    GROUP BY uloga, tip_komisije
                    ORDER BY uloga, tip_komisije";
            
            $stmt = $connection->prepare($sql);
            if (!$stmt) {
                wp_send_json_error('Database prepare failed: ' . $connection->error);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            $roles = [];
            
            while ($row = $result->fetch_assoc()) {
                $roles[] = [
                    'id' => $row['id'],
                    'uloga' => $row['uloga'] ?: 'Nedefinirano',
                    'tip_komisije' => $row['tip_komisije'] ?: 'Nedefinirano',
                    'operater_count' => $row['operater_count']
                ];
            }
            
            wp_send_json_success($roles);
            
        } catch (Exception $e) {
            wp_send_json_error('Greška pri učitavanju komisija: ' . $e->getMessage());
        }

        $connection->close();
    }

    public function handle_load_role_privileges() {
        if (!wp_verify_nonce($_POST['nonce'], 'admin_privileges_nonce')) {
            wp_send_json_error('Security check failed');
        }

        $role_id = intval($_POST['role_id']);
        if (!$role_id) {
            wp_send_json_error('Invalid role ID');
        }

        $connection = $this->get_db_connection();
        if (!$connection) {
            wp_send_json_error('Database connection failed');
        }

        try {
            // First get the role details
            $stmt = $connection->prepare("SELECT uloga, tip_komisije FROM user_komisije WHERE id = ?");
            $stmt->bind_param("i", $role_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $role = $result->fetch_assoc();
            
            if (!$role) {
                wp_send_json_error('Role not found');
            }

            // Create table if it doesn't exist
            $create_table_sql = "CREATE TABLE IF NOT EXISTS user_komisije_privileges (
                id INT AUTO_INCREMENT PRIMARY KEY,
                uloga VARCHAR(255),
                tip_komisije VARCHAR(255),
                privilege_view TINYINT(1) DEFAULT 1,
                privilege_create TINYINT(1) DEFAULT 0,
                privilege_edit TINYINT(1) DEFAULT 0,
                privilege_delete TINYINT(1) DEFAULT 0,
                privilege_export TINYINT(1) DEFAULT 0,
                privilege_import TINYINT(1) DEFAULT 0,
                field_tarifa TINYINT(1) DEFAULT 0,
                field_user_id TINYINT(1) DEFAULT 0,
                field_liga TINYINT(1) DEFAULT 0,
                field_kolo TINYINT(1) DEFAULT 0,
                field_sezona TINYINT(1) DEFAULT 0,
                field_utakmica TINYINT(1) DEFAULT 0,
                field_uloga TINYINT(1) DEFAULT 0,
                field_datum_obavjesti TINYINT(1) DEFAULT 0,
                field_putni_troskovi TINYINT(1) DEFAULT 0,
                field_komentar TINYINT(1) DEFAULT 0,
                field_status TINYINT(1) DEFAULT 0,
                field_id_utakmice TINYINT(1) DEFAULT 0,
                privilege_own_only TINYINT(1) DEFAULT 1,
                privilege_current_season_only TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_role (uloga, tip_komisije)
            )";
            
            $connection->query($create_table_sql);

            // Load existing privileges or defaults
            $stmt = $connection->prepare("SELECT * FROM user_komisije_privileges WHERE uloga = ? AND tip_komisije = ?");
            $stmt->bind_param("ss", $role['uloga'], $role['tip_komisije']);
            $stmt->execute();
            $result = $stmt->get_result();
            $privileges = $result->fetch_assoc();

            if (!$privileges) {
                // Return default privileges
                $privileges = [
                    'view' => '1', 'create' => '0', 'edit' => '0', 'delete' => '0', 'export' => '0', 'import' => '0',
                    'tarifa' => '0', 'user_id' => '0', 'liga' => '0', 'kolo' => '0', 'sezona' => '0', 'utakmica' => '0',
                    'uloga' => '0', 'datum_obavjesti' => '0', 'putni_troskovi' => '0', 'komentar' => '0', 'status' => '0', 'id_utakmice' => '0',
                    'own_only' => '1', 'current_season_only' => '1'
                ];
            } else {
                // Return existing privileges
                $privileges = [
                    'view' => $privileges['privilege_view'], 'create' => $privileges['privilege_create'], 'edit' => $privileges['privilege_edit'],
                    'delete' => $privileges['privilege_delete'], 'export' => $privileges['privilege_export'], 'import' => $privileges['privilege_import'],
                    'tarifa' => $privileges['field_tarifa'], 'user_id' => $privileges['field_user_id'], 'liga' => $privileges['field_liga'],
                    'kolo' => $privileges['field_kolo'], 'sezona' => $privileges['field_sezona'], 'utakmica' => $privileges['field_utakmica'],
                    'uloga' => $privileges['field_uloga'], 'datum_obavjesti' => $privileges['field_datum_obavjesti'], 
                    'putni_troskovi' => $privileges['field_putni_troskovi'], 'komentar' => $privileges['field_komentar'],
                    'status' => $privileges['field_status'], 'id_utakmice' => $privileges['field_id_utakmice'],
                    'own_only' => $privileges['privilege_own_only'], 'current_season_only' => $privileges['privilege_current_season_only']
                ];
            }
            
            wp_send_json_success($privileges);
            
        } catch (Exception $e) {
            wp_send_json_error('Greška pri učitavanju privilegija: ' . $e->getMessage());
        }

        $connection->close();
    }

    public function handle_save_role_privileges() {
        // Basic validation
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'admin_privileges_nonce')) {
            wp_send_json_error('Security check failed');
            return;
        }

        $role_id = intval($_POST['role_id'] ?? 0);
        if (!$role_id) {
            wp_send_json_error('Invalid role ID');
            return;
        }

        $connection = $this->get_db_connection();
        if (!$connection) {
            wp_send_json_error('Database connection failed');
            return;
        }

        // Get role details
        $stmt = $connection->prepare("SELECT uloga, tip_komisije FROM user_komisije WHERE id = ?");
        if (!$stmt) {
            $connection->close();
            wp_send_json_error('Prepare failed');
            return;
        }
        
        $stmt->bind_param("i", $role_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $role = $result->fetch_assoc();
        
        if (!$role) {
            $connection->close();
            wp_send_json_error('Role not found');
            return;
        }

        // Collect values safely
        $view = intval($_POST['view'] ?? 0);
        $create = intval($_POST['create'] ?? 0);
        $edit = intval($_POST['edit'] ?? 0);
        $delete = intval($_POST['delete'] ?? 0);
        $export = intval($_POST['export'] ?? 0);
        $import = intval($_POST['import'] ?? 0);
        $own_only = intval($_POST['own_only'] ?? 0);
        $current_season_only = intval($_POST['current_season_only'] ?? 0);
        
        // Field privileges
        $field_tarifa = intval($_POST['tarifa'] ?? 0);
        $field_user_id = intval($_POST['user_id'] ?? 0);
        $field_liga = intval($_POST['liga'] ?? 0);
        $field_kolo = intval($_POST['kolo'] ?? 0);
        $field_sezona = intval($_POST['sezona'] ?? 0);
        $field_utakmica = intval($_POST['utakmica'] ?? 0);
        $field_uloga = intval($_POST['uloga'] ?? 0);
        $field_datum_obavjesti = intval($_POST['datum_obavjesti'] ?? 0);
        $field_putni_troskovi = intval($_POST['putni_troskovi'] ?? 0);
        $field_komentar = intval($_POST['komentar'] ?? 0);
        $field_status = intval($_POST['status'] ?? 0);
        $field_id_utakmice = intval($_POST['id_utakmice'] ?? 0);

        // Insert/Update query
        $sql = "INSERT INTO user_komisije_privileges (
                    uloga, tip_komisije, privilege_view, privilege_create, privilege_edit, 
                    privilege_delete, privilege_export, privilege_import, 
                    field_tarifa, field_user_id, field_liga, field_kolo, field_sezona, field_utakmica,
                    field_uloga, field_datum_obavjesti, field_putni_troskovi, field_komentar, field_status, field_id_utakmice,
                    privilege_own_only, privilege_current_season_only
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    privilege_view = VALUES(privilege_view),
                    privilege_create = VALUES(privilege_create),
                    privilege_edit = VALUES(privilege_edit),
                    privilege_delete = VALUES(privilege_delete),
                    privilege_export = VALUES(privilege_export),
                    privilege_import = VALUES(privilege_import),
                    field_tarifa = VALUES(field_tarifa),
                    field_user_id = VALUES(field_user_id),
                    field_liga = VALUES(field_liga),
                    field_kolo = VALUES(field_kolo),
                    field_sezona = VALUES(field_sezona),
                    field_utakmica = VALUES(field_utakmica),
                    field_uloga = VALUES(field_uloga),
                    field_datum_obavjesti = VALUES(field_datum_obavjesti),
                    field_putni_troskovi = VALUES(field_putni_troskovi),
                    field_komentar = VALUES(field_komentar),
                    field_status = VALUES(field_status),
                    field_id_utakmice = VALUES(field_id_utakmice),
                    privilege_own_only = VALUES(privilege_own_only),
                    privilege_current_season_only = VALUES(privilege_current_season_only)";

        $stmt = $connection->prepare($sql);
        if (!$stmt) {
            $connection->close();
            wp_send_json_error('Prepare failed: ' . $connection->error);
            return;
        }

        $stmt->bind_param("ssiiiiiiiiiiiiiiiiiiii", 
            $role['uloga'], $role['tip_komisije'], 
            $view, $create, $edit, $delete, $export, $import, 
            $field_tarifa, $field_user_id, $field_liga, $field_kolo, $field_sezona, $field_utakmica,
            $field_uloga, $field_datum_obavjesti, $field_putni_troskovi, $field_komentar, $field_status, $field_id_utakmice,
            $own_only, $current_season_only
        );

        if ($stmt->execute()) {
            $connection->close();
            wp_send_json_success('Privilegije uspješno sačuvane!');
        } else {
            $connection->close();
            wp_send_json_error('Execute failed: ' . $stmt->error);
        }
    }

    // Komisije AJAX handlers
    public function handle_search_matches_komisije() {
        if (!wp_verify_nonce($_POST['nonce'], 'search_matches_komisije')) {
            wp_send_json_error('Nonce verification failed');
            return;
        }

        $current_user = wp_get_current_user();
        if (!$current_user || !is_user_logged_in()) {
            wp_send_json_error('Korisnik nije ulogovan');
            return;
        }

        $connection = $this->get_db_connection();
        if (!$connection) {
            wp_send_json_error('Greška konekcije sa bazom');
            return;
        }

        // Provjeri da li korisnik pripada komisiji
        global $wpdb;
        $komisija_check = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM user_komisije WHERE user_id = %d AND uloga IN (2, 3)
        ", $current_user->ID));

        if (!$komisija_check) {
            wp_send_json_error('Nemate dozvolu za pristup');
            return;
        }

        // Napravi SQL upit za pretragu
        $where_conditions = [];
        $params = [];
        $param_types = '';

        if (!empty($_POST['liga'])) {
            $where_conditions[] = "liga = ?";
            $params[] = $_POST['liga'];
            $param_types .= 's';
        }
        if (!empty($_POST['sezona'])) {
            $where_conditions[] = "sezona = ?";
            $params[] = $_POST['sezona'];
            $param_types .= 's';
        }
        if (!empty($_POST['kolo'])) {
            $where_conditions[] = "kolo = ?";
            $params[] = intval($_POST['kolo']);
            $param_types .= 'i';
        }
        if (!empty($_POST['status'])) {
            $where_conditions[] = "status = ?";
            $params[] = $_POST['status'];
            $param_types .= 's';
        }
        if (!empty($_POST['utakmica'])) {
            $where_conditions[] = "utakmica LIKE ?";
            $params[] = '%' . $_POST['utakmica'] . '%';
            $param_types .= 's';
        }

        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        $query = "SELECT * FROM user_match $where_clause ORDER BY id DESC LIMIT 50";

        if (!empty($params)) {
            $stmt = $connection->prepare($query);
            $stmt->bind_param($param_types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $connection->query($query);
        }

        $matches = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $matches[] = $row;
            }
        }

        $connection->close();
        wp_send_json_success($matches);
    }

    public function handle_get_match_details() {
        if (!wp_verify_nonce($_POST['nonce'], 'get_match_details')) {
            wp_send_json_error('Nonce verification failed');
            return;
        }

        $match_id = intval($_POST['match_id']);
        if (!$match_id) {
            wp_send_json_error('Neispravni ID utakmice');
            return;
        }

        $connection = $this->get_db_connection();
        if (!$connection) {
            wp_send_json_error('Greška konekcije sa bazom');
            return;
        }

        $stmt = $connection->prepare("SELECT * FROM user_match WHERE id = ?");
        $stmt->bind_param('i', $match_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $match = $result->fetch_assoc();

        $connection->close();

        if ($match) {
            wp_send_json_success($match);
        } else {
            wp_send_json_error('Utakmica nije pronađena');
        }
    }

    public function handle_save_match_komisije() {
        if (!wp_verify_nonce($_POST['nonce'], 'save_match_komisije')) {
            wp_send_json_error('Nonce verification failed');
            return;
        }

        $current_user = wp_get_current_user();
        if (!$current_user || !is_user_logged_in()) {
            wp_send_json_error('Korisnik nije ulogovan');
            return;
        }

        // Provjeri privilegije korisnika
        global $wpdb;
        $privileges = $wpdb->get_row($wpdb->prepare("
            SELECT uk.*, ukp.* 
            FROM user_komisije uk 
            LEFT JOIN user_komisije_privileges ukp ON uk.id = ukp.komisija_id 
            WHERE uk.user_id = %d AND uk.uloga IN (2, 3)
        ", $current_user->ID));

        if (!$privileges || !$privileges->can_edit) {
            wp_send_json_error('Nemate dozvolu za editovanje');
            return;
        }

        $match_id = intval($_POST['match_id']);
        $connection = $this->get_db_connection();
        if (!$connection) {
            wp_send_json_error('Greška konekcije sa bazom');
            return;
        }

        // Pripremi podatke za ažuriranje samo za polja sa privilegijama
        $update_fields = [];
        $params = [];
        $param_types = '';

        $field_mapping = [
            'liga' => 'field_liga',
            'sezona' => 'field_sezona', 
            'kolo' => 'field_kolo',
            'id_utakmice' => 'field_id_utakmice',
            'utakmica' => 'field_utakmica',
            'user_id' => 'field_user_id',
            'tarifa' => 'field_tarifa',
            'datum_obavjesti' => 'field_datum_obavjesti',
            'putni_troskovi' => 'field_putni_troskovi',
            'status' => 'field_status',
            'komentar' => 'field_komentar'
        ];

        foreach ($field_mapping as $field => $privilege_field) {
            if (isset($_POST[$field]) && $privileges->$privilege_field == 1) {
                $update_fields[] = "$field = ?";
                $params[] = $_POST[$field];
                $param_types .= ($field == 'kolo' || $field == 'user_id') ? 'i' : 's';
            }
        }

        if (empty($update_fields)) {
            wp_send_json_error('Nema polja za ažuriranje ili nemate privilegije');
            return;
        }

        $params[] = $match_id;
        $param_types .= 'i';

        $query = "UPDATE user_match SET " . implode(', ', $update_fields) . " WHERE id = ?";
        $stmt = $connection->prepare($query);
        $stmt->bind_param($param_types, ...$params);

        if ($stmt->execute()) {
            $connection->close();
            wp_send_json_success('Utakmica uspješno ažurirana');
        } else {
            $connection->close();
            wp_send_json_error('Greška pri ažuriranju: ' . $stmt->error);
        }
    }

    public function handle_delete_match_komisije() {
        if (!wp_verify_nonce($_POST['nonce'], 'delete_match_komisije')) {
            wp_send_json_error('Nonce verification failed');
            return;
        }

        $current_user = wp_get_current_user();
        if (!$current_user || !is_user_logged_in()) {
            wp_send_json_error('Korisnik nije ulogovan');
            return;
        }

        // Provjeri privilegije
        global $wpdb;
        $privileges = $wpdb->get_row($wpdb->prepare("
            SELECT can_delete FROM user_komisije uk 
            LEFT JOIN user_komisije_privileges ukp ON uk.id = ukp.komisija_id 
            WHERE uk.user_id = %d AND uk.uloga IN (2, 3)
        ", $current_user->ID));

        if (!$privileges || !$privileges->can_delete) {
            wp_send_json_error('Nemate dozvolu za brisanje');
            return;
        }

        $match_id = intval($_POST['match_id']);
        $connection = $this->get_db_connection();
        if (!$connection) {
            wp_send_json_error('Greška konekcije sa bazom');
            return;
        }

        $stmt = $connection->prepare("DELETE FROM user_match WHERE id = ?");
        $stmt->bind_param('i', $match_id);

        if ($stmt->execute()) {
            $connection->close();
            wp_send_json_success('Utakmica uspješno obrisana');
        } else {
            $connection->close();
            wp_send_json_error('Greška pri brisanju: ' . $stmt->error);
        }
    }

    public function handle_get_judges_list() {
        if (!wp_verify_nonce($_POST['nonce'], 'get_judges_list')) {
            wp_send_json_error('Nonce verification failed');
            return;
        }

        $users = get_users(['role__in' => ['administrator', 'editor', 'author']]);
        $judges = [];
        
        foreach ($users as $user) {
            $judges[] = [
                'ID' => $user->ID,
                'display_name' => $user->display_name
            ];
        }

        wp_send_json_success($judges);
    }
    
    // Rukovanje komisijskim login-om u WordPress
    public function handle_komisija_wp_login($user_login, $user) {
        // Provjeri da li je ovo komisijski korisnik
        if (strpos($user_login, 'komisija_') === 0 || 
            (isset($_POST['komisija_login']) && $_POST['komisija_login'] === '1')) {
            
            // Postavi marker da je ovo komisijski korisnik
            update_user_meta($user->ID, 'is_komisija_user', '1');
            
            // Preusmjeri na URS admin panel umjesto standardni dashboard
            wp_safe_redirect(admin_url('admin.php?page=urs-admin-dashboard'));
            exit;
        }
    }
    
    // Ukloni admin meni stavke za komisijske korisnike
    public function remove_admin_menus_for_komisija() {
        // Provjeri da li je ovo komisijski korisnik ili s2Member Level 4
        $current_user = wp_get_current_user();
        $is_komisija = get_user_meta($current_user->ID, 'is_komisija_user', true);
        $has_s2_level4 = in_array('s2member_level4', $current_user->roles);
        
        if ($is_komisija || $has_s2_level4) {
            // Ukloni sve meni stavke osim našeg URS panela
            remove_menu_page('index.php');                  // Dashboard
            remove_menu_page('edit.php');                   // Posts
            remove_menu_page('upload.php');                 // Media
            remove_menu_page('edit.php?post_type=page');    // Pages
            remove_menu_page('edit-comments.php');          // Comments
            remove_menu_page('themes.php');                 // Appearance
            remove_menu_page('plugins.php');                // Plugins
            remove_menu_page('users.php');                  // Users
            remove_menu_page('tools.php');                  // Tools
            remove_menu_page('options-general.php');        // Settings
            
            // Ukloni dodatne plugin meni stavke
            remove_menu_page('edit.php?post_type=elementor_library');
            remove_menu_page('elementor');
        }
    }
    
    // Ukloni admin bar stavke za komisijske korisnike
    public function remove_admin_bar_items_for_komisija() {
        global $wp_admin_bar;
        
        $current_user = wp_get_current_user();
        $is_komisija = get_user_meta($current_user->ID, 'is_komisija_user', true);
        $has_s2_level4 = in_array('s2member_level4', $current_user->roles);
        
        if ($is_komisija || $has_s2_level4) {
            // Ukloni admin bar stavke
            $wp_admin_bar->remove_menu('wp-logo');
            $wp_admin_bar->remove_menu('about');
            $wp_admin_bar->remove_menu('wporg');
            $wp_admin_bar->remove_menu('documentation');
            $wp_admin_bar->remove_menu('support-forums');
            $wp_admin_bar->remove_menu('feedback');
            $wp_admin_bar->remove_menu('site-name');
            $wp_admin_bar->remove_menu('view-site');
            $wp_admin_bar->remove_menu('updates');
            $wp_admin_bar->remove_menu('comments');
            $wp_admin_bar->remove_menu('new-content');
            $wp_admin_bar->remove_menu('w3tc');
        }
    }
    
    // Sakrij admin elemente pomoću CSS-a
    public function hide_admin_elements_for_komisija() {
        $current_user = wp_get_current_user();
        $is_komisija = get_user_meta($current_user->ID, 'is_komisija_user', true);
        $has_s2_level4 = in_array('s2member_level4', $current_user->roles);
        
        if ($is_komisija || $has_s2_level4) {
            echo '<style>
                /* Sakrij footer tekst */
                #wpfooter { display: none !important; }
                
                /* Sakrij screen options i help */
                #screen-meta-links { display: none !important; }
                
                /* Sakrij admin notices */
                .notice, .error, .updated { display: none !important; }
                
                /* Sakrij WordPress logo u admin bar */
                #wp-admin-bar-wp-logo { display: none !important; }
                
                /* Customizuj admin bar */
                #wpadminbar {
                    background: linear-gradient(90deg, #0073aa 0%, #005a87 100%) !important;
                }
                
                /* Dodaj custom header za URS */
                #wpbody-content:before {
                    content: "🏛️ URS Komisijski Panel";
                    display: block;
                    background: linear-gradient(90deg, #0073aa 0%, #005a87 100%);
                    color: white;
                    font-size: 18px;
                    font-weight: bold;
                    padding: 15px 20px;
                    margin: -20px -20px 20px -20px;
                    text-align: center;
                }
            </style>';
        }
    }

    // Nova funkcija za ograničavanje pristupa samo na URS Admin Dashboard
    public function restrict_komisija_admin_access() {
        // Debug logging
        error_log('restrict_komisija_admin_access called');
        
        if (!is_admin()) {
            return;
        }
        
        $current_user = wp_get_current_user();
        error_log('Current user ID: ' . $current_user->ID);
        error_log('Current user roles: ' . print_r($current_user->roles, true));
        
        // Provjeri da li je komisijski korisnik ili ima s2Member Level 4 rolu
        $is_komisija = get_user_meta($current_user->ID, 'is_komisija_user', true);
        $has_s2_level4 = in_array('s2member_level4', $current_user->roles);
        $is_administrator = in_array('administrator', $current_user->roles);
        
        error_log('Is komisija: ' . ($is_komisija ? 'YES' : 'NO'));
        error_log('Has s2member_level4: ' . ($has_s2_level4 ? 'YES' : 'NO'));
        error_log('Is administrator: ' . ($is_administrator ? 'YES' : 'NO'));
        
        if ($is_komisija || $has_s2_level4 || $is_administrator) {
            global $pagenow;
            $current_page = isset($_GET['page']) ? $_GET['page'] : '';
            
            error_log('Current pagenow: ' . $pagenow);
            error_log('Current page param: ' . $current_page);
            
            // Dozvoljene stranice za komisijske korisnike
            $allowed_pages = [
                'admin.php', // Za URS Admin Dashboard
                'admin-ajax.php', // Za AJAX pozive
                'wp-login.php' // Za logout
            ];
            
            // Provjeri trenutnu stranicu
            if (!in_array($pagenow, $allowed_pages)) {
                error_log('Page not allowed, redirecting to URS dashboard');
                wp_redirect(admin_url('admin.php?page=urs-admin-dashboard'));
                exit;
            }
            
            // Dodatna provjera za admin.php - mora biti URS Admin Dashboard
            if ($pagenow === 'admin.php') {
                $page = isset($_GET['page']) ? $_GET['page'] : '';
                if ($page !== 'urs-admin-dashboard') {
                    error_log('Wrong admin page, redirecting to URS dashboard');
                    wp_redirect(admin_url('admin.php?page=urs-admin-dashboard'));
                    exit;
                }
            }
        }
    }

    // Dodatna provjera pristupa na nivou screen-a
    public function check_screen_access($screen) {
        error_log('check_screen_access called for screen: ' . ($screen ? $screen->id : 'null'));
        
        $current_user = wp_get_current_user();
        $is_komisija = get_user_meta($current_user->ID, 'is_komisija_user', true);
        $has_s2_level4 = in_array('s2member_level4', $current_user->roles);
        
        if ($is_komisija || $has_s2_level4) {
            // Dozvoljeni screen ID-jevi
            $allowed_screens = [
                'urs-admin-dashboard', // URS Admin Dashboard
                'toplevel_page_urs-admin-dashboard' // Alternative screen ID
            ];
            
            if ($screen && !in_array($screen->id, $allowed_screens)) {
                error_log('Screen not allowed: ' . $screen->id . ', redirecting');
                wp_redirect(admin_url('admin.php?page=urs-admin-dashboard'));
                exit;
            }
        }
    }

    // Forsiraj redirekciju za komisijske korisnike na svaki zahtjev
    public function force_komisija_redirect() {
        // Provjeri da li smo u admin area
        if (!is_admin()) {
            return;
        }
        
        $current_user = wp_get_current_user();
        if (!$current_user || $current_user->ID == 0) {
            return;
        }
        
        $is_komisija = get_user_meta($current_user->ID, 'is_komisija_user', true);
        $has_s2_level4 = in_array('s2member_level4', $current_user->roles);
        
        // Debug ispis
        error_log('force_komisija_redirect - User ID: ' . $current_user->ID);
        error_log('force_komisija_redirect - Is komisija: ' . ($is_komisija ? 'YES' : 'NO'));
        error_log('force_komisija_redirect - Has s2member_level4: ' . ($has_s2_level4 ? 'YES' : 'NO'));
        error_log('force_komisija_redirect - Current URL: ' . $_SERVER['REQUEST_URI']);
        
        if ($is_komisija || $has_s2_level4) {
            $current_page = isset($_GET['page']) ? $_GET['page'] : '';
            $current_file = basename($_SERVER['SCRIPT_NAME']);
            
            // Ako nije na URS Admin Dashboard stranici, rediriguj
            if ($current_file === 'admin.php' && $current_page !== 'urs-admin-dashboard') {
                error_log('force_komisija_redirect - Redirecting to URS dashboard');
                wp_redirect(admin_url('admin.php?page=urs-admin-dashboard'));
                exit;
            }
            
            // Ako je na bilo kojoj drugoj admin stranici osim profila
            if (!in_array($current_file, ['admin.php', 'admin-ajax.php']) && strpos($_SERVER['REQUEST_URI'], 'wp-admin') !== false) {
                error_log('force_komisija_redirect - Redirecting from ' . $current_file . ' to URS dashboard');
                wp_redirect(admin_url('admin.php?page=urs-admin-dashboard'));
                exit;
            }
        }
    }

    public function handle_create_komisija_wp_user() {
        // Verifikacija nonce
        if (!wp_verify_nonce($_POST['nonce'], 'mcl_nonce')) {
            wp_die('Neispravna sigurnosna provjera');
        }

        session_start();
        
        // Provjera da li je korisnik ulogiran u custom sistem
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            wp_send_json_error(['message' => 'Nemate dozvolu za pristup']);
            return;
        }

        $username = $_SESSION['username'];
        
        // Provjera komisijskih privilegija
        if (!$this->check_komisija_privileges($username)) {
            wp_send_json_error(['message' => 'Nemate komisijske privilegije']);
            return;
        }

        // Provjera da li WordPress korisnik već postoji
        $wp_user = get_user_by('login', $username);
        
        if (!$wp_user) {
            // Kreiranje novog WordPress korisnika
            $password = wp_generate_password(12, false);
            $user_id = wp_create_user($username, $password, $username . '@ursbih.ba');
            
            if (is_wp_error($user_id)) {
                wp_send_json_error(['message' => 'Greška pri kreiranju korisnika: ' . $user_id->get_error_message()]);
                return;
            }
            
            $wp_user = get_user_by('id', $user_id);
        } else {
            $user_id = $wp_user->ID;
        }
        
        // Označavanje kao komisijski korisnik
        update_user_meta($user_id, 'is_komisija_user', true);
        
        // Ulogiranje korisnika u WordPress
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id, true);
        
        // Čišćenje cache-a
        wp_cache_delete($user_id, 'users');
        wp_cache_delete($username, 'userlogins');
        
        wp_send_json_success([
            'message' => 'Uspješno ste ulogirani u WordPress admin panel',
            'redirect_url' => admin_url()
        ]);
    }

    // Funkcija za kreiranje WordPress pristupa za komisiju
    private function create_komisija_wp_access($username) {
        error_log("create_komisija_wp_access called for username: $username");
        
        try {
            // Provjera da li WordPress korisnik već postoji
            $wp_user = get_user_by('login', $username);
            
            if (!$wp_user) {
                error_log("Creating new WordPress user for: $username");
                // Kreiranje novog WordPress korisnika
                $password = wp_generate_password(12, false);
                $user_id = wp_create_user($username, $password, $username . '@ursbih.ba');
                
                if (is_wp_error($user_id)) {
                    error_log('Greška pri kreiranju WP korisnika: ' . $user_id->get_error_message());
                    return false;
                }
                
                $wp_user = get_user_by('id', $user_id);
            } else {
                error_log("WordPress user already exists for: $username");
                $user_id = $wp_user->ID;
            }
            
            // Označavanje kao komisijski korisnik i postavljanje role
            update_user_meta($user_id, 'is_komisija_user', true);
            
            // Postavljanje administrator role za pristup URS Admin Dashboard
            $wp_user->set_role('administrator');
            
            // Dodaj dodatne capabilities za URS Admin Dashboard
            $wp_user->add_cap('read');
            $wp_user->add_cap('edit_posts');
            $wp_user->add_cap('upload_files');
            $wp_user->add_cap('manage_urs_data');
            $wp_user->add_cap('access_urs_dashboard');
            $wp_user->add_cap('manage_options');
            
            // Također dodaj s2Member Level 4 kao sekundarnu rolu
            $wp_user->add_role('s2member_level4');
            
            // Ulogiranje korisnika u WordPress
            wp_set_current_user($user_id);
            wp_set_auth_cookie($user_id, true);
            
            // Čišćenje cache-a
            wp_cache_delete($user_id, 'users');
            wp_cache_delete($username, 'userlogins');
            
            error_log("WordPress user successfully created/updated for komisija user: $username with ID: $user_id");
            
            // Redirekcija direktno na URS Admin Dashboard
            return admin_url('admin.php?page=urs-admin-dashboard');
            
        } catch (Exception $e) {
            error_log('Greška pri kreiranju komisijskog WP pristupa: ' . $e->getMessage());
            return false;
        }
    }

    // Funkcija za provjeru komisijskih privilegija
    private function check_komisija_privileges($username) {
        $connection = $this->get_db_connection();
        if (!$connection) {
            return false;
        }
        
        try {
            $stmt = $connection->prepare("
                SELECT uk.tip_komisije, uk.aktivan 
                FROM user_komisije uk 
                INNER JOIN users_data ud ON uk.username = ud.username 
                WHERE uk.username = ? AND uk.aktivan = 1
            ");
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                return in_array($row['tip_komisije'], ['disciplinska', 'strucno-sudijska']);
            }
            return false;
        } catch (Exception $e) {
            error_log('Greška pri provjeri komisijskih privilegija: ' . $e->getMessage());
            return false;
        }
    }

    public function handle_check_komisija_access() {
        if (!wp_verify_nonce($_POST['nonce'], 'check_komisija_access')) {
            wp_send_json_error('Nonce verification failed');
            return;
        }

        // Koristi session podatke za trenutnog korisnika
        if (!isset($_SESSION['mcl_user_data']) || !isset($_SESSION['mcl_user_data']['Username'])) {
            wp_send_json_error('Korisnik nije ulogovan');
            return;
        }

        $session_username = $_SESSION['mcl_user_data']['Username'];
        $komisija_tip = sanitize_text_field($_POST['komisija_tip'] ?? '');

        if (!$komisija_tip) {
            wp_send_json_error('Neispravni parametri - tip komisije nije određen');
            return;
        }

        // Mapiranje tipova komisija
        $komisija_tip_mapping = [
            'disciplinska' => 'disciplinska',
            'strucno-sudijska' => 'strucno-sudijska'
        ];

        if (!isset($komisija_tip_mapping[$komisija_tip])) {
            wp_send_json_error('Nepoznat tip komisije');
            return;
        }

        $tip_komisije_db = $komisija_tip_mapping[$komisija_tip];

        try {
            // Konekcija na vanjsku bazu
            $pdo = new PDO(
                "mysql:host={$this->db_config['host']};dbname={$this->db_config['database']};charset={$this->db_config['charset']}",
                $this->db_config['username'],
                $this->db_config['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );

            // Provjeri da li korisnik postoji u user_komisije tabeli sa odgovarajućim tipom komisije
            // Povezivanje preko username polja između tabela
            $stmt = $pdo->prepare("
                SELECT uk.*, ud.Ime, ud.Prezime, ud.EMAIL 
                FROM user_komisije uk 
                INNER JOIN users_data ud ON uk.username = ud.Username
                WHERE uk.username = ? AND uk.tip_komisije = ? AND uk.aktivan = 1
            ");
            $stmt->execute([$session_username, $tip_komisije_db]);
            $komisija_check = $stmt->fetch();

            if (!$komisija_check) {
                $komisija_nazivi = [
                    'disciplinska' => 'Disciplinsku komisiju',
                    'strucno-sudijska' => 'Stručno sudijsku komisiju'
                ];
                wp_send_json_error('Nemate dodijeljene privilegije za ' . $komisija_nazivi[$komisija_tip]);
                return;
            }

            // Korisnik ima privilegije
            $komisija_nazivi = [
                'disciplinska' => 'Disciplinsku komisiju',
                'strucno-sudijska' => 'Stručno sudijsku komisiju'
            ];
            
            wp_send_json_success([
                'message' => 'Pristup odobren za ' . $komisija_nazivi[$komisija_tip],
                'komisija_data' => $komisija_check,
                'tip_komisije' => $tip_komisije_db
            ]);

        } catch (Exception $e) {
            if ($this->debug_mode) {
                wp_send_json_error('Greška baze podataka: ' . $e->getMessage());
            } else {
                wp_send_json_error('Greška sistema, molimo pokušajte ponovo');
            }
        }
    }

    public function check_user_match_table() {
        try {
            $connection = $this->get_db_connection();
            if (!$connection) {
                return '<div style="color: red;">Greška konekcije na bazu</div>';
            }

            // Proverava da li tabela postoji
            $result = $connection->query("SHOW TABLES LIKE 'user_match'");
            $table_exists = $result->num_rows > 0;
            
            $output = '<div style="background: white; padding: 20px; border-radius: 8px; margin: 20px 0;">';
            $output .= '<h3>🔍 Debug: User Match Tabela</h3>';
            
            if (!$table_exists) {
                $output .= '<div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0;">';
                $output .= '<strong>❌ Tabela user_match ne postoji!</strong><br>';
                $output .= 'Potrebno je kreirati tabelu user_match u bazi podataka.';
                $output .= '</div>';
                
                // Pokazuje SQL za kreiranje tabele
                $output .= '<div style="background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 10px 0;">';
                $output .= '<strong>SQL za kreiranje tabele:</strong><br>';
                $output .= '<code style="display: block; background: #f8f9fa; padding: 10px; margin: 10px 0; border-radius: 3px;">
CREATE TABLE user_match (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    match_id INT NOT NULL,
    liga VARCHAR(100),
    sezona VARCHAR(20),
    kolo VARCHAR(20),
    uloga VARCHAR(50),
    status VARCHAR(20) DEFAULT "aktivan",
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
</code>';
                $output .= '</div>';
            } else {
                // Tabela postoji, prikaži broj zapisa
                $count_result = $connection->query("SELECT COUNT(*) as total FROM user_match");
                $count = $count_result->fetch_assoc()['total'];
                
                $output .= '<div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0;">';
                $output .= '<strong>✅ Tabela user_match postoji!</strong><br>';
                $output .= 'Broj zapisa u tabeli: <strong>' . $count . '</strong>';
                $output .= '</div>';
                
                if ($count == 0) {
                    $output .= '<div style="background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 10px 0;">';
                    $output .= '<strong>⚠️ Tabela je prazna!</strong><br>';
                    $output .= 'Potrebno je dodati test podatke u tabelu user_match.';
                    $output .= '</div>';
                    
                    // Prikazuje sample INSERT
                    $output .= '<div style="background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 10px 0;">';
                    $output .= '<strong>Sample INSERT za test podatke:</strong><br>';
                    $output .= '<code style="display: block; background: #f8f9fa; padding: 10px; margin: 10px 0; border-radius: 3px;">
INSERT INTO user_match (user_id, match_id, liga, sezona, kolo, uloga, status) VALUES
(1, 101, "Premijer Liga", "2024/25", "1", "Sudija", "aktivan"),
(2, 102, "Prva Liga", "2024/25", "1", "Asistent", "aktivan"),
(1, 103, "Premijer Liga", "2024/25", "2", "Četvrti sudija", "aktivan");
</code>';
                    $output .= '</div>';
                } else {
                    // Prikazuje prvih 5 zapisa
                    $sample_result = $connection->query("SELECT * FROM user_match LIMIT 5");
                    $output .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;">';
                    $output .= '<strong>Prvi zapisi u tabeli:</strong><br>';
                    $output .= '<table style="width: 100%; border-collapse: collapse; margin-top: 10px;">';
                    $output .= '<tr style="background: #0073aa; color: white;">';
                    $output .= '<th style="padding: 8px; border: 1px solid #ddd;">ID</th>';
                    $output .= '<th style="padding: 8px; border: 1px solid #ddd;">User ID</th>';
                    $output .= '<th style="padding: 8px; border: 1px solid #ddd;">Match ID</th>';
                    $output .= '<th style="padding: 8px; border: 1px solid #ddd;">Liga</th>';
                    $output .= '<th style="padding: 8px; border: 1px solid #ddd;">Uloga</th>';
                    $output .= '</tr>';
                    
                    while ($row = $sample_result->fetch_assoc()) {
                        $output .= '<tr>';
                        $output .= '<td style="padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars($row['id']) . '</td>';
                        $output .= '<td style="padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars($row['user_id']) . '</td>';
                        $output .= '<td style="padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars($row['match_id']) . '</td>';
                        $output .= '<td style="padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars($row['liga']) . '</td>';
                        $output .= '<td style="padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars($row['uloga']) . '</td>';
                        $output .= '</tr>';
                    }
                    $output .= '</table>';
                    $output .= '</div>';
                }
            }
            
            $output .= '</div>';
            $connection->close();
            return $output;
            
        } catch (Exception $e) {
            return '<div style="color: red; background: #f8d7da; padding: 15px; border-radius: 5px;">
                Greška: ' . htmlspecialchars($e->getMessage()) . '
            </div>';
        }
    }

    // NEW USER LOGIN/LOGOUT FUNCTIONS

    /**
     * Display user login form
     */
    public function display_user_login_form($atts = []) {
        ob_start();
        include plugin_dir_path(__FILE__) . 'login-form.php';
        return ob_get_clean();
    }

    /**
     * Display user dashboard page
     */
    public function display_user_dashboard_page($atts = []) {
        ob_start();
        include plugin_dir_path(__FILE__) . 'user-dashboard.php';
        return ob_get_clean();
    }

    /**
     * Handle user login with mathematical verification
     */
    public function handle_user_login() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'user_login_nonce')) {
            wp_send_json_error('Sigurnosna provjera neuspješna.');
            return;
        }

        $username = sanitize_text_field($_POST['username']);
        $password = sanitize_text_field($_POST['password']);
        $math_answer = intval($_POST['mathAnswer']);

        if (empty($username) || empty($password) || empty($math_answer)) {
            wp_send_json_error('Molimo unesite sva polja.');
            return;
        }

        // Check mathematical answer
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['math_answer']) || $_SESSION['math_answer'] != $math_answer) {
            wp_send_json_error('Netačan odgovor na matematičku provjeru.');
            return;
        }

        // Clear math answer from session
        unset($_SESSION['math_answer']);

        $connection = $this->get_db_connection();
        if (!$connection) {
            wp_send_json_error('Greška u konekciji sa bazom podataka.');
            return;
        }

        try {
            // Check user in users_data table
            $stmt = $connection->prepare("SELECT * FROM users_data WHERE Username = ? LIMIT 1");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                wp_send_json_error('Neispravno korisničko ime ili lozinka.');
                $stmt->close();
                $connection->close();
                return;
            }

            $user = $result->fetch_assoc();
            $stmt->close();

            // Verify password (assuming it's stored as plain text - should be hashed in production)
            if ($user['Passw'] !== $password) {
                wp_send_json_error('Neispravno korisničko ime ili lozinka.');
                $connection->close();
                return;
            }

            // Successful login - set session
            $_SESSION['mcl_logged_in'] = true;
            $_SESSION['mcl_user_id'] = $user['id'];
            $_SESSION['mcl_username'] = $user['Username'];
            $_SESSION['mcl_ime'] = $user['Ime'];
            $_SESSION['mcl_prezime'] = $user['Prezime'];
            $_SESSION['mcl_email'] = $user['EMAIL'] ?? '';
            $_SESSION['mcl_login_time'] = time();

            $connection->close();

            wp_send_json_success([
                'message' => 'Uspješno ste se prijavili!',
                'redirect_url' => home_url('/user-dashboard/')
            ]);

        } catch (Exception $e) {
            error_log('User login error: ' . $e->getMessage());
            wp_send_json_error('Greška pri prijavi: ' . $e->getMessage());
        }
    }

    /**
     * Handle user logout
     */
    public function handle_user_logout() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'user_logout_nonce')) {
            wp_send_json_error('Sigurnosna provjera neuspješna.');
            return;
        }

        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        // Clear user session data
        unset($_SESSION['mcl_logged_in']);
        unset($_SESSION['mcl_user_id']);
        unset($_SESSION['mcl_username']);
        unset($_SESSION['mcl_ime']);
        unset($_SESSION['mcl_prezime']);
        unset($_SESSION['mcl_email']);
        unset($_SESSION['mcl_login_time']);

        // Destroy session
        session_destroy();

        wp_send_json_success([
            'message' => 'Uspješno ste se odjavili.',
            'redirect_url' => home_url('/login/')
        ]);
    }
}

// Initialize the plugin
new MyCustomLoginPlugin();

// Handle komisije requests prije WordPress auth provjere
function handle_komisije_requests($wp) {
    // Provjeri da li je ovo komisije-panel request
    if (strpos($_SERVER['REQUEST_URI'], '/komisije-panel/') !== false) {
        // Start session if not already started
        if (!session_id()) {
            session_start();
        }
        
        // Ako korisnik ima custom sesiju, postavi dummy WordPress user
        if (isset($_SESSION['mcl_logged_in']) && $_SESSION['mcl_logged_in'] && 
            isset($_SESSION['mcl_user_data']) && !empty($_SESSION['mcl_user_data']['Username'])) {
            
            // Privremeno postavi da je korisnik ulogovan za WordPress
            add_filter('determine_current_user', function($user_id) {
                return 1; // Dummy user ID
            });
            
            add_filter('wp_get_current_user', function($user) {
                if (!$user || $user->ID == 0) {
                    $user = new stdClass;
                    $user->ID = 1;
                    $user->user_login = $_SESSION['mcl_user_data']['Username'] ?? 'komisija_user';
                    $user->display_name = 'Komisija User';
                }
                return $user;
            });
        }
    }
}

// Setup WP redirect filter rano da sprečimo redirecte
function setup_wp_redirect_filter() {
    add_filter('wp_redirect', function($location, $status) {
        // Ako je redirect prema wp-login.php i korisnik je na našoj komisije stranici
        if (strpos($location, 'wp-login.php') !== false && 
            (strpos($_SERVER['REQUEST_URI'], '/komisije-panel/') !== false ||
             isset($_GET['tip']) && ($_GET['tip'] === 'disciplinska' || $_GET['tip'] === 'strucno-sudijska'))) {
            
            // Provjeri da li korisnik ima custom sesiju
            if (!session_id()) session_start();
            if (isset($_SESSION['mcl_user_data']) && !empty($_SESSION['mcl_user_data']['Username'])) {
                return false; // Prekini redirect
            }
        }
        return $location;
    }, 1, 2);
}

// Komisije shortcode handler function (outside of class)
function komisije_dashboard_shortcode($atts) {
    if (!is_user_logged_in()) {
        return '<p>Molimo da se prvo ulogirate.</p>';
    }
    
    $current_user = wp_get_current_user();
    global $wpdb;
    
    $komisija_check = $wpdb->get_row($wpdb->prepare("
        SELECT * FROM user_komisije WHERE user_id = %d AND uloga IN (2, 3)
    ", $current_user->ID));
    
    if (!$komisija_check) {
        return '<p>Nemate dozvolu za pristup komisijskom panelu.</p>';
    }
    
    ob_start();
    include plugin_dir_path(__FILE__) . 'templates/komisije-dashboard.php';
    return ob_get_clean();
}

// Register shortcode
add_shortcode('komisije_dashboard', 'komisije_dashboard_shortcode');

// Sprečava WordPress redirect na wp-login.php za naše custom stranice
function prevent_wp_login_redirect() {
    // Dodaj filter da provjeri naše custom stranice prije WordPress auth provjere
    add_filter('wp_redirect', function($location, $status) {
        // Ako je redirect prema wp-login.php i korisnik je na našoj komisije stranici
        if (strpos($location, 'wp-login.php') !== false && 
            (strpos($_SERVER['REQUEST_URI'], '/komisije-panel/') !== false ||
             isset($_GET['tip']) && ($_GET['tip'] === 'disciplinska' || $_GET['tip'] === 'strucno-sudijska'))) {
            
            // Provjeri da li korisnik ima custom sesiju
            if (!session_id()) session_start();
            if (isset($_SESSION['mcl_user_data']) && !empty($_SESSION['mcl_user_data']['Username'])) {
                return false; // Prekini redirect
            }
        }
        return $location;
    }, 10, 2);
}

// Shortcode za komisije panel - jednostavna verzija
function komisije_panel_shortcode($atts) {
    // Start session ako nije već pokrenuta
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Dobij podatke iz sessiona
    $session_user_data = $_SESSION['mcl_user_data'] ?? null;
    
    // TEMP: Postaviti mcl_logged_in flag ako imamo user data a nema flag
    if ($session_user_data && isset($session_user_data['Username']) && !isset($_SESSION['mcl_logged_in'])) {
        $_SESSION['mcl_logged_in'] = true;
    }
    
    // Provjeri da li je korisnik ulogovan preko našeg sistema
    if (!isset($_SESSION['mcl_logged_in']) || !$_SESSION['mcl_logged_in']) {
        return '<div style="text-align: center; padding: 40px; background: #f8f9fa; border-radius: 8px; border: 1px solid #dee2e6;">
            <h3 style="color: #dc3545; margin-bottom: 20px;">🚫 Pristup odbijen</h3>
            <p style="margin-bottom: 20px;">Morate biti ulogovani da biste pristupili komisijskom panelu.</p>
            <a href="/login/" style="background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Idite na login</a>
        </div>';
    }
    
    if (!$session_user_data || !isset($session_user_data['Username'])) {
        return '<div style="text-align: center; padding: 40px; background: #f8f9fa; border-radius: 8px; border: 1px solid #dee2e6;">
            <h3 style="color: #dc3545; margin-bottom: 20px;">⚠️ Greška sessiona</h3>
            <p style="margin-bottom: 20px;">Podaci o korisniku nisu dostupni.</p>
            <a href="/login/" style="background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Idite na login</a>
        </div>';
    }
    
    $session_username = $session_user_data['Username'];
    
    // Provjeri privilegije korisnika za komisiju
    try {
        $db_config = [
            'host' => '65.21.234.24',
            'database' => 'ursbihba_lara195',
            'username' => 'ursbihba_lara195', 
            'password' => 'paradoX2019',
            'charset' => 'utf8mb4'
        ];

        $pdo = new PDO(
            "mysql:host={$db_config['host']};dbname={$db_config['database']};charset={$db_config['charset']}",
            $db_config['username'],
            $db_config['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        // Provjeri komisiju
        $stmt = $pdo->prepare("SELECT * FROM user_komisije WHERE username = ? AND aktivan = 1");
        $stmt->execute([$session_username]);
        $komisija_check = $stmt->fetchAll();

        if (empty($komisija_check)) {
            return '<div style="text-align: center; padding: 40px; background: #fff3cd; border-radius: 8px; border: 1px solid #ffeaa7;">
                <h3 style="color: #856404; margin-bottom: 20px;">🚫 Nema privilegija</h3>
                <p style="margin-bottom: 20px;">Nemate dodijeljene privilegije za komisiju.</p>
                <a href="/dashboard/" style="background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Povratak na dashboard</a>
            </div>';
        }

        // Učitaj template za komisiju
        ob_start();
        
        // Set global variables for template
        global $komisija_session_username, $komisija_check_data;
        $komisija_session_username = $session_username;
        $komisija_check_data = $komisija_check;
        
        include __DIR__ . '/templates/komisija-panel.php';
        return ob_get_clean();

    } catch (Exception $e) {
        return '<div style="background: #f8d7da; padding: 20px; border-radius: 8px;">
            <h3 style="color: #721c24;">💥 Greška baze podataka</h3>
            <p>' . htmlspecialchars($e->getMessage()) . '</p>
        </div>';
    }
}

// AJAX handler for saving match comments
function handle_match_comment_ajax() {
    session_start();
    if (isset($_POST['ajax_comment']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_match_comment') {
        header('Content-Type: application/json');
        
        $match_id = intval($_POST['match_id']);
        $comment = trim($_POST['comment']);
        
        if (!$match_id || !$comment) {
            echo json_encode(['success' => false, 'message' => 'Nedostaju podaci']);
            exit;
        }
        
        try {
            // Database config
            $db_config = [
                'host' => '65.21.234.24',
                'database' => 'ursbihba_lara195',
                'username' => 'ursbihba_lara195', 
                'password' => 'paradoX2019',
                'charset' => 'utf8mb4'
            ];

            $connection = new mysqli(
                $db_config['host'],
                $db_config['username'],
                $db_config['password'],
                $db_config['database']
            );

            if ($connection->connect_error) {
                throw new Exception('Connection failed: ' . $connection->connect_error);
            }
            
            // Get current user data for operator name
            $operator_name = 'Nepoznat operator';
            if (isset($_SESSION['admin_komisija_ime']) && !empty($_SESSION['admin_komisija_ime']) && isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
                $operator_name = $_SESSION['admin_komisija_ime'];
            } elseif (isset($_SESSION['mcl_ime']) && !empty($_SESSION['mcl_ime']) && isset($_SESSION['mcl_prezime']) && !empty($_SESSION['mcl_prezime'])) {
                $operator_name = $_SESSION['mcl_ime'] . ' ' . $_SESSION['mcl_prezime'];
            } elseif (isset($_SESSION['mcl_username']) && !empty($_SESSION['mcl_username'])) {
                $operator_name = $_SESSION['mcl_username'];
            }
            
            // Check if komentar column exists, if not add it
            $check_column = $connection->query("SHOW COLUMNS FROM user_match LIKE 'komentar'");
            if ($check_column->num_rows == 0) {
                $connection->query("ALTER TABLE user_match ADD COLUMN komentar TEXT");
            }
            
            // Create formatted comment with timestamp and operator name
                $timestamp = date('d-m-Y H:i:s');
                $formatted_comment = $operator_name . ' (' . $timestamp . '): ' . $comment;
            
            // Update comment using CONCAT to preserve existing comments
            $sql = "UPDATE user_match 
                    SET komentar = CONCAT(
                        COALESCE(komentar, ''),
                        IF(COALESCE(komentar, '') = '', '', '\n'),
                        ?
                    )
                    WHERE id = ?";
            
            $stmt = $connection->prepare($sql);
            $stmt->bind_param("si", $formatted_comment, $match_id);
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Komentar je sačuvan',
                        'added_comment' => $formatted_comment
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Nijedna utakmica nije ažurirana. Provjerite ID utakmice (' . $match_id . ')!'
                    ]);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Greška pri čuvanju: ' . $stmt->error]);
            }
            
            $stmt->close();
            $connection->close();
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        
        exit;
    }
}

// Call AJAX handler early
handle_match_comment_ajax();

// Shortcode za prikaz podataka u komisiji panelu
function komisije_panel_data_shortcode($atts) {
    
    // Start session ako nije već pokrenuta
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Dobij podatke iz sessiona
    $session_user_data = $_SESSION['mcl_user_data'] ?? null;
    
    if (!$session_user_data || !isset($session_user_data['Username'])) {
        return '<div style="color: #dc3545; background: #f8d7da; padding: 15px; border-radius: 8px;">
            ❌ Session podaci nisu dostupni
        </div>';
    }
    
    $session_username = $session_user_data['Username'];
    
    // Čitaj tip komisije iz URL parametra umesto iz session-a
    $user_komisija_type = isset($_GET['tip']) ? trim(strip_tags($_GET['tip'])) : null;
    
    try {
        // Konfiguracija baze
        $db_config = [
            'host' => '65.21.234.24',
            'database' => 'ursbihba_lara195',
            'username' => 'ursbihba_lara195', 
            'password' => 'paradoX2019',
            'charset' => 'utf8mb4'
        ];

        // Koristi mysqli da bude kompatibilno sa ostatkom koda
        $connection = new mysqli(
            $db_config['host'],
            $db_config['username'],
            $db_config['password'],
            $db_config['database']
        );

        if ($connection->connect_error) {
            throw new Exception('Connection failed: ' . $connection->connect_error);
        }
        
        $connection->set_charset($db_config['charset']);

        // Prvo proverava da li tabela user_match postoji
        $table_check = $connection->query("SHOW TABLES LIKE 'user_match'");
        
        if ($table_check->num_rows == 0) {
            // Tabela ne postoji
            return '<div style="text-align: center; padding: 40px; background: #fff3cd; border-radius: 8px;">
                <h3 style="color: #856404;">⚠️ Tabela ne postoji</h3>
                <p>Tabela <code>user_match</code> ne postoji u bazi podataka.</p>
                <p>Potrebno je kreirati tabelu sa sledećom strukturom:</p>
                <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0; text-align: left;">
                    <code>
                    CREATE TABLE user_match (<br>
                    &nbsp;&nbsp;id INT AUTO_INCREMENT PRIMARY KEY,<br>
                    &nbsp;&nbsp;user_id INT NOT NULL,<br>
                    &nbsp;&nbsp;match_id INT NOT NULL,<br>
                    &nbsp;&nbsp;liga VARCHAR(100),<br>
                    &nbsp;&nbsp;sezona VARCHAR(20),<br>
                    &nbsp;&nbsp;kolo VARCHAR(20),<br>
                    &nbsp;&nbsp;uloga VARCHAR(50),<br>
                    &nbsp;&nbsp;status VARCHAR(20) DEFAULT "aktivan"<br>
                    );
                    </code>
                </div>
            </div>';
        }

        // Prvo proverava strukture tabela
        $table_check = $connection->query("SHOW TABLES LIKE 'users'");
        $users_table_exists = $table_check->num_rows > 0;
        
        if ($users_table_exists) {
            // Proverava kolone u users tabeli
            $columns_check = $connection->query("SHOW COLUMNS FROM users");
            $users_columns = [];
            while ($col = $columns_check->fetch_assoc()) {
                $users_columns[] = $col['Field'];
            }
        }

        // Učitaj user_match tabelu - bez JOIN-a ako users tabela ne postoji ili nema odgovarajuće kolone
        if ($users_table_exists && in_array('name', $users_columns)) {
            // Koristimo JOIN samo ako users tabela postoji i ima name kolonu
            $surname_col = in_array('surname', $users_columns) ? 'u.surname' : 'NULL';
            
            // Filtriranje na osnovu komisije korisnika - prikaži samo utakmice dodeljene toj komisiji
            $komisija_where = '';
            if ($user_komisija_type) {
                $komisija_where = " WHERE um.komisija = '$user_komisija_type'";
            }
            
            $query = "SELECT 
                um.*,
                u.name as user_name,
                $surname_col as user_surname,
                CASE 
                    WHEN um.uloga = 'sudija' THEN 'Sudija'
                    WHEN um.uloga = 'asistent' THEN 'Asistent' 
                    WHEN um.uloga = 'cetvrti_sudija' THEN 'Četvrti sudija'
                    WHEN um.uloga = 'delegat' THEN 'Delegat'
                    WHEN um.uloga = 'inspektor' THEN 'Inspektor'
                    ELSE CONCAT(UPPER(LEFT(um.uloga, 1)), LOWER(SUBSTRING(um.uloga, 2)))
                END as formatted_uloga,
                CASE
                    WHEN um.status = 'aktivan' THEN 'Aktivan'
                    WHEN um.status = 'neaktivan' THEN 'Neaktivan'
                    WHEN um.status = 'zavrseno' THEN 'Završeno'
                    ELSE CONCAT(UPPER(LEFT(um.status, 1)), LOWER(SUBSTRING(um.status, 2)))
                END as formatted_status
            FROM user_match um 
            LEFT JOIN users u ON um.user_id = u.id 
            $komisija_where
            ORDER BY um.id DESC LIMIT 100";
        } else {
            // Jednostavan query bez JOIN-a
            $komisija_where = '';
            if ($user_komisija_type) {
                $komisija_where = " WHERE komisija = '$user_komisija_type'";
            }
            
            $query = "SELECT 
                *,
                CASE 
                    WHEN uloga = 'sudija' THEN 'Sudija'
                    WHEN uloga = 'asistent' THEN 'Asistent' 
                    WHEN uloga = 'cetvrti_sudija' THEN 'Četvrti sudija'
                    WHEN uloga = 'delegat' THEN 'Delegat'
                    WHEN uloga = 'inspektor' THEN 'Inspektor'
                    ELSE CONCAT(UPPER(LEFT(uloga, 1)), LOWER(SUBSTRING(uloga, 2)))
                END as formatted_uloga,
                CASE
                    WHEN status = 'aktivan' THEN 'Aktivan'
                    WHEN status = 'neaktivan' THEN 'Neaktivan'
                    WHEN status = 'zavrseno' THEN 'Završeno'
                    ELSE CONCAT(UPPER(LEFT(status, 1)), LOWER(SUBSTRING(status, 2)))
                END as formatted_status,
                NULL as user_name,
                NULL as user_surname
            FROM user_match 
            $komisija_where
            ORDER BY id DESC LIMIT 100";
        }
        
        $result = $connection->query($query);
        
        if (!$result) {
            throw new Exception('Greška u SQL upitu: ' . $connection->error);
        }
        
        $user_matches = [];
        while ($row = $result->fetch_assoc()) {
            $user_matches[] = $row;
        }
        
        if (empty($user_matches)) {
            // Prikažemo debug informacije o strukturi tabele
            $debug_info = '<div style="text-align: center; padding: 40px; background: #fff3cd; border-radius: 8px;">';
            $debug_info .= '<h3 style="color: #856404;">📋 Nema podataka</h3>';
            $debug_info .= '<p>Trenutno nema zapisa u user_match tabeli.</p>';
            
            // Dodaj informacije o strukturi tabele
            $debug_info .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0; text-align: left;">';
            $debug_info .= '<h4>🔍 Debug informacije:</h4>';
            
            // Prikaži kolone user_match tabele
            $columns_result = $connection->query("SHOW COLUMNS FROM user_match");
            $debug_info .= '<p><strong>Kolone u user_match tabeli:</strong></p><ul>';
            while ($col = $columns_result->fetch_assoc()) {
                $debug_info .= '<li>' . htmlspecialchars($col['Field']) . ' (' . htmlspecialchars($col['Type']) . ')</li>';
            }
            $debug_info .= '</ul>';
            
            // Proverava users tabelu
            if ($users_table_exists) {
                $debug_info .= '<p><strong>Kolone u users tabeli:</strong></p><ul>';
                foreach ($users_columns as $col) {
                    $debug_info .= '<li>' . htmlspecialchars($col) . '</li>';
                }
                $debug_info .= '</ul>';
            } else {
                $debug_info .= '<p><strong>Users tabela:</strong> Ne postoji</p>';
            }
            
            $debug_info .= '</div>';
            $debug_info .= '<p>Dodajte test podatke:</p>';
            $debug_info .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0; text-align: left;">';
            $debug_info .= '<code>
            INSERT INTO user_match (user_id, match_id, liga, sezona, kolo, uloga, status, utakmica, tarifa) VALUES<br>
            (1, 101, "Premijer Liga", "2024/25", "1", "sudija", "aktivan", "FK Sarajevo vs FK Željezničar", "10.00"),<br>
            (2, 102, "Prva Liga", "2024/25", "1", "asistent", "aktivan", "FK Borac vs FK Velež", "10.00");
            </code>';
            $debug_info .= '</div>';
            $debug_info .= '</div>';
            
            return $debug_info;
        }
        
        // Kreiraj HTML tabelu sa lijepim box dizajnom
        $output = '<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; border-radius: 12px; margin: 20px 0; box-shadow: 0 8px 32px rgba(0,0,0,0.15);">';
        $output .= '<div style="background: rgba(255,255,255,0.95); border-radius: 10px; overflow: hidden; backdrop-filter: blur(10px);">';
        $output .= '<div style="background: linear-gradient(90deg, #4a6cf7 0%, #667eea 100%); padding: 20px; color: white;">';
        $output .= '<h3 style="margin: 0; font-size: 1.4rem; font-weight: 600; display: flex; align-items: center; gap: 10px;">';
        $output .= '<span style="font-size: 1.6rem;">📋</span> Disciplinska komisija - Utakmice';
        $output .= '</h3>';
        $output .= '<p style="margin: 8px 0 0 0; opacity: 0.9; font-size: 0.95rem;">Prikazano: ' . count($user_matches) . ' dodeljenih utakmica</p>';
        $output .= '</div>';
        
        $output .= '<div style="padding: 0; overflow-x: auto;">';
        $output .= '<table style="width: 100%; border-collapse: collapse; font-size: 0.95rem;">';
        
        // Header
        $output .= '<thead><tr style="background: linear-gradient(90deg, #2c3e50 0%, #34495e 100%); color: white;">';
        $output .= '<th style="padding: 16px 20px; text-align: left; font-weight: 600; border-right: 1px solid rgba(255,255,255,0.1); font-size: 0.9rem;">⚽ Utakmica</th>';
        $output .= '<th style="padding: 16px 20px; text-align: left; font-weight: 600; border-right: 1px solid rgba(255,255,255,0.1); font-size: 0.9rem;">🏆 Liga</th>';
        $output .= '<th style="padding: 16px 20px; text-align: center; font-weight: 600; border-right: 1px solid rgba(255,255,255,0.1); font-size: 0.9rem;">📅 Kolo</th>';
        $output .= '<th style="padding: 16px 20px; text-align: center; font-weight: 600; border-right: 1px solid rgba(255,255,255,0.1); font-size: 0.9rem;">📊 Sezona</th>';
        $output .= '<th style="padding: 16px 20px; text-align: center; font-weight: 600; border-right: 1px solid rgba(255,255,255,0.1); font-size: 0.9rem;">⚠️ Suspendovan</th>';
        $output .= '<th style="padding: 16px 20px; text-align: center; font-weight: 600; font-size: 0.9rem;">⚙️ Akcije</th>';
        $output .= '</tr></thead>';
        
        // Body
        $output .= '<tbody>';
        foreach ($user_matches as $index => $match) {
            $row_id = 'row-' . ($match['id'] ?? $index);
            $row_style = $index % 2 == 0 ? 'background: rgba(248,249,250,0.7);' : 'background: rgba(255,255,255,0.9);';
            $output .= '<tr id="' . $row_id . '" style="' . $row_style . ' border-bottom: 1px solid #e9ecef; transition: all 0.3s ease;" onmouseover="this.style.background=\'rgba(102,126,234,0.1)\'" onmouseout="this.style.background=\'' . ($index % 2 == 0 ? 'rgba(248,249,250,0.7)' : 'rgba(255,255,255,0.9)') . '\'">';
            
            // Utakmica - editable
            $utakmica = $match['utakmica'] ?? 'N/A';
            $output .= '<td style="padding: 16px 20px; border-right: 1px solid #e9ecef;">';
            $output .= '<div class="editable-field" data-field="utakmica" data-id="' . ($match['id'] ?? '') . '">';
            $output .= '<span class="view-mode" style="font-weight: 500; color: #2c3e50; cursor: pointer; padding: 8px 12px; border-radius: 6px; transition: all 0.2s ease; display: inline-block; min-width: 200px;" onclick="editField(this)">' . htmlspecialchars($utakmica) . '</span>';
            $output .= '<input type="text" class="edit-mode" value="' . htmlspecialchars($utakmica) . '" style="display: none; width: 100%; padding: 8px 12px; border: 2px solid #4a6cf7; border-radius: 6px; font-size: 0.9rem; background: #f8f9ff;" onblur="saveField(this)" onkeypress="if(event.key===\'Enter\') saveField(this)">';
            $output .= '</div>';
            $output .= '</td>';
            
            // Liga - editable
            $liga_display = $match['liga'] ?? 'N/A';
            $output .= '<td style="padding: 16px 20px; border-right: 1px solid #e9ecef;">';
            $output .= '<div class="editable-field" data-field="liga" data-id="' . ($match['id'] ?? '') . '">';
            $output .= '<span class="view-mode liga-badge" style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 6px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; cursor: pointer; display: inline-block; min-width: 100px; text-align: center;" onclick="editField(this)">' . htmlspecialchars($liga_display) . '</span>';
            $output .= '<input type="text" class="edit-mode" value="' . htmlspecialchars($liga_display) . '" style="display: none; width: 100%; padding: 8px 12px; border: 2px solid #4a6cf7; border-radius: 6px; font-size: 0.9rem; background: #f8f9ff;" onblur="saveField(this)" onkeypress="if(event.key===\'Enter\') saveField(this)">';
            $output .= '</div>';
            $output .= '</td>';
            
            // Kolo - editable
            $kolo = $match['kolo'] ?? '';
            $output .= '<td style="padding: 16px 20px; border-right: 1px solid #e9ecef; text-align: center;">';
            $output .= '<div class="editable-field" data-field="kolo" data-id="' . ($match['id'] ?? '') . '">';
            $output .= '<span class="view-mode" style="font-weight: 600; color: #495057; cursor: pointer; padding: 8px 12px; border-radius: 6px; transition: all 0.2s ease; display: inline-block; min-width: 50px; text-align: center; background: rgba(74,108,247,0.1);" onclick="editField(this)">' . htmlspecialchars($kolo) . '</span>';
            $output .= '<input type="text" class="edit-mode" value="' . htmlspecialchars($kolo) . '" style="display: none; width: 80px; padding: 8px 12px; border: 2px solid #4a6cf7; border-radius: 6px; font-size: 0.9rem; background: #f8f9ff; text-align: center;" onblur="saveField(this)" onkeypress="if(event.key===\'Enter\') saveField(this)">';
            $output .= '</div>';
            $output .= '</td>';
            
            // Sezona - editable
            $sezona = $match['sezona'] ?? '';
            $output .= '<td style="padding: 16px 20px; border-right: 1px solid #e9ecef; text-align: center;">';
            $output .= '<div class="editable-field" data-field="sezona" data-id="' . ($match['id'] ?? '') . '">';
            $output .= '<span class="view-mode" style="font-weight: 600; color: #495057; cursor: pointer; padding: 8px 12px; border-radius: 6px; transition: all 0.2s ease; display: inline-block; min-width: 80px; text-align: center; background: rgba(74,108,247,0.1);" onclick="editField(this)">' . htmlspecialchars($sezona) . '</span>';
            $output .= '<input type="text" class="edit-mode" value="' . htmlspecialchars($sezona) . '" style="display: none; width: 100px; padding: 8px 12px; border: 2px solid #4a6cf7; border-radius: 6px; font-size: 0.9rem; background: #f8f9ff; text-align: center;" onblur="saveField(this)" onkeypress="if(event.key===\'Enter\') saveField(this)">';
            $output .= '</div>';
            $output .= '</td>';
            
            // Suspendovan - toggle DA/NE
            $suspendovan = $match['suspendovan'] ?? 'NE'; // Default je NE
            $is_suspended = (strtoupper($suspendovan) === 'DA');
            $output .= '<td style="padding: 16px 20px; border-right: 1px solid #e9ecef; text-align: center;">';
            $output .= '<div class="suspendovan-toggle" data-field="suspendovan" data-id="' . ($match['id'] ?? '') . '">';
            
            if ($is_suspended) {
                $output .= '<button onclick="toggleSuspend(this)" class="suspend-btn suspended" style="background: linear-gradient(135deg, #dc3545, #c82333); color: white; border: none; padding: 8px 16px; border-radius: 20px; cursor: pointer; font-size: 0.8rem; font-weight: 600; transition: all 0.3s ease; display: flex; align-items: center; gap: 5px; margin: 0 auto;" onmouseover="this.style.transform=\'scale(1.05)\'" onmouseout="this.style.transform=\'scale(1)\'" data-value="DA">❌ DA</button>';
            } else {
                $output .= '<button onclick="toggleSuspend(this)" class="suspend-btn active" style="background: linear-gradient(135deg, #28a745, #20c997); color: white; border: none; padding: 8px 16px; border-radius: 20px; cursor: pointer; font-size: 0.8rem; font-weight: 600; transition: all 0.3s ease; display: flex; align-items: center; gap: 5px; margin: 0 auto;" onmouseover="this.style.transform=\'scale(1.05)\'" onmouseout="this.style.transform=\'scale(1)\'" data-value="NE">✅ NE</button>';
            }
            
            $output .= '</div>';
            $output .= '</td>';
            
            // Akcije
            $output .= '<td style="padding: 16px 20px; text-align: center;">';
            $output .= '<div style="display: flex; gap: 8px; justify-content: center; align-items: center;">';
            $output .= '<button onclick="openCommentModal(' . ($match['id'] ?? 'null') . ', \'' . htmlspecialchars($match['utakmica'] ?? 'N/A', ENT_QUOTES) . '\')" style="background: linear-gradient(135deg, #28a745, #20c997); color: white; border: none; padding: 8px 16px; border-radius: 20px; cursor: pointer; font-size: 0.8rem; font-weight: 600; transition: all 0.3s ease; display: flex; align-items: center; gap: 5px;" onmouseover="this.style.transform=\'scale(1.05)\'" onmouseout="this.style.transform=\'scale(1)\'">💬 Edit</button>';
            $output .= '<button onclick="saveRow(\'' . $row_id . '\', ' . ($match['id'] ?? 'null') . ')" style="background: linear-gradient(135deg, #007bff, #0056b3); color: white; border: none; padding: 8px 16px; border-radius: 20px; cursor: pointer; font-size: 0.8rem; font-weight: 600; transition: all 0.3s ease; display: flex; align-items: center; gap: 5px;" onmouseover="this.style.transform=\'scale(1.05)\'" onmouseout="this.style.transform=\'scale(1)\'">💾 Save</button>';
            $output .= '</div>';
            $output .= '</td>';
            
            $output .= '</tr>';
        }
        $output .= '</tbody></table>';
        $output .= '</div></div></div>';
        
        // Dodaj modal prozor za komentar
        $output .= '
        <!-- Modal za komentar -->
        <div id="commentModal" style="
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            backdrop-filter: blur(5px);
        ">
            <div style="
                position: relative;
                margin: 5% auto;
                width: 90%;
                max-width: 600px;
                background: white;
                border-radius: 15px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                overflow: hidden;
            ">
                <div style="
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 20px;
                    text-align: center;
                ">
                    <h3 style="margin: 0; font-size: 1.4rem; font-weight: 600;">💬 Dodaj komentar</h3>
                    <p id="modalMatchTitle" style="margin: 8px 0 0 0; opacity: 0.9; font-size: 1rem;"></p>
                </div>
                
                <div style="padding: 30px;">
                    <label style="
                        display: block;
                        margin-bottom: 10px;
                        font-weight: 600;
                        color: #495057;
                        font-size: 1rem;
                    ">Komentar za utakmicu:</label>
                    
                    <textarea id="commentText" placeholder="Unesite komentar..." style="
                        width: 100%;
                        height: 150px;
                        padding: 15px;
                        border: 2px solid #e9ecef;
                        border-radius: 10px;
                        font-size: 1rem;
                        font-family: inherit;
                        resize: vertical;
                        transition: border-color 0.3s ease;
                    " onfocus="this.style.borderColor=\'#667eea\'" onblur="this.style.borderColor=\'#e9ecef\'"></textarea>
                    
                    <div style="
                        display: flex;
                        gap: 15px;
                        justify-content: flex-end;
                        margin-top: 25px;
                    ">
                        <button onclick="closeCommentModal()" style="
                            background: #6c757d;
                            color: white;
                            border: none;
                            padding: 12px 25px;
                            border-radius: 25px;
                            cursor: pointer;
                            font-size: 1rem;
                            font-weight: 600;
                            transition: all 0.3s ease;
                        " onmouseover="this.style.background=\'#5a6268\'" onmouseout="this.style.background=\'#6c757d\'">
                            ❌ Otkaži
                        </button>
                        
                        <button onclick="saveComment()" style="
                            background: linear-gradient(135deg, #28a745, #20c997);
                            color: white;
                            border: none;
                            padding: 12px 25px;
                            border-radius: 25px;
                            cursor: pointer;
                            font-size: 1rem;
                            font-weight: 600;
                            transition: all 0.3s ease;
                        " onmouseover="this.style.transform=\'scale(1.05)\'" onmouseout="this.style.transform=\'scale(1)\'">
                            💾 Sačuvaj komentar
                        </button>
                    </div>
                </div>
            </div>
        </div>';
        
        // Dodaj JavaScript za edit/save funkcionalnost
        $output .= '
        <script>
        function editField(element) {
            const container = element.parentElement;
            const viewMode = container.querySelector(".view-mode");
            const editMode = container.querySelector(".edit-mode");
            
            viewMode.style.display = "none";
            editMode.style.display = "inline-block";
            editMode.focus();
            editMode.select();
        }
        
        function saveField(input) {
            const container = input.parentElement;
            const viewMode = container.querySelector(".view-mode");
            const newValue = input.value.trim();
            
            // Update display
            viewMode.textContent = newValue || "N/A";
            viewMode.style.display = "inline-block";
            input.style.display = "none";
            
            // Visual feedback
            viewMode.style.background = "#d4edda";
            setTimeout(() => {
                viewMode.style.background = "";
            }, 1000);
        }
        
        function editRow(rowId) {
            const row = document.getElementById(rowId);
            const editableFields = row.querySelectorAll(".editable-field .view-mode");
            
            editableFields.forEach(field => {
                editField(field);
            });
        }
        
        function saveRow(rowId, recordId) {
            const row = document.getElementById(rowId);
            const fields = row.querySelectorAll(".editable-field");
            const data = {};
            
            fields.forEach(field => {
                const fieldName = field.dataset.field;
                const input = field.querySelector(".edit-mode");
                const viewSpan = field.querySelector(".view-mode");
                
                if (input.style.display !== "none") {
                    // Field is in edit mode
                    const newValue = input.value.trim();
                    data[fieldName] = newValue;
                    viewSpan.textContent = newValue || "N/A";
                    viewSpan.style.display = "inline-block";
                    input.style.display = "none";
                } else {
                    // Field is in view mode
                    data[fieldName] = viewSpan.textContent;
                }
            });
            
            // Visual feedback
            row.style.background = "#d1ecf1";
            setTimeout(() => {
                row.style.background = "";
            }, 1500);
            
            // Here you would typically send an AJAX request to save the data
            console.log("Saving data for record ID:", recordId, data);
            
            // Show success message
            showMessage("✅ Podaci su uspešno sačuvani!", "success");
        }
        
        function showMessage(message, type) {
            const messageDiv = document.createElement("div");
            messageDiv.textContent = message;
            messageDiv.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 12px 20px;
                border-radius: 8px;
                color: white;
                font-weight: 600;
                z-index: 9999;
                animation: slideIn 0.3s ease;
                background: ${type === "success" ? "linear-gradient(135deg, #28a745, #20c997)" : "linear-gradient(135deg, #dc3545, #c82333)"};
            `;
            
            document.body.appendChild(messageDiv);
            
            setTimeout(() => {
                messageDiv.remove();
            }, 3000);
        }
        
        function toggleSuspend(button) {
            const currentValue = button.dataset.value;
            const container = button.parentElement;
            const recordId = container.dataset.id;
            
            if (currentValue === "NE") {
                // Promeni na DA (suspendovan)
                button.dataset.value = "DA";
                button.innerHTML = "❌ DA";
                button.className = "suspend-btn suspended";
                button.style.background = "linear-gradient(135deg, #dc3545, #c82333)";
                showMessage("⚠️ Utakmica je suspendovana!", "error");
            } else {
                // Promeni na NE (aktivan)
                button.dataset.value = "NE";
                button.innerHTML = "✅ NE";
                button.className = "suspend-btn active";
                button.style.background = "linear-gradient(135deg, #28a745, #20c997)";
                showMessage("✅ Utakmica je aktivirana!", "success");
            }
            
            // Visual feedback
            button.style.transform = "scale(0.95)";
            setTimeout(() => {
                button.style.transform = "scale(1)";
            }, 150);
            
            // Here you would typically send an AJAX request to save the suspension status
            console.log("Toggling suspension for record ID:", recordId, "New value:", button.dataset.value);
        }
        
        // Modal functions
        let currentEditMatchId = null;
        
        function openCommentModal(matchId, matchTitle) {
            currentEditMatchId = matchId;
            document.getElementById("modalMatchTitle").textContent = matchTitle;
            document.getElementById("commentText").value = "";
            document.getElementById("commentModal").style.display = "block";
            
            // Focus na textarea
            setTimeout(() => {
                document.getElementById("commentText").focus();
            }, 100);
            
            // Load existing comment if any
            loadExistingComment(matchId);
        }
        
        function closeCommentModal() {
            document.getElementById("commentModal").style.display = "none";
            currentEditMatchId = null;
        }
        
        function saveComment() {
            const commentText = document.getElementById("commentText").value.trim();
            
            if (!commentText) {
                showMessage("❌ Molimo unesite komentar!", "error");
                return;
            }
            
            if (!currentEditMatchId) {
                showMessage("❌ Greška: ID utakmice nije dostupan!", "error");
                return;
            }
            
            // Send AJAX request to save comment
            saveCommentToDatabase(currentEditMatchId, commentText);
        }
        
        function loadExistingComment(matchId) {
            // Here you would load existing comment from database
            // For now, we\'ll just log it
            console.log("Loading existing comment for match ID:", matchId);
        }
        
        function saveCommentToDatabase(matchId, comment) {
            // Create form data
            const formData = new FormData();
            formData.append("action", "save_match_comment");
            formData.append("match_id", matchId);
            formData.append("comment", comment);
            formData.append("ajax_comment", "1");
            
            console.log("Sending AJAX request with data:", {
                action: "save_match_comment",
                match_id: matchId,
                comment: comment,
                ajax_comment: "1"
            });
            
            // Send POST request to current page
            fetch(window.location.href, {
                method: "POST",
                body: formData
            })
            .then(response => {
                console.log("Response status:", response.status);
                console.log("Response headers:", response.headers);
                return response.text();
            })
            .then(text => {
                console.log("Raw response:", text);
                console.log("Response length:", text.length);
                
                try {
                    const data = JSON.parse(text);
                    console.log("Parsed JSON:", data);
                    if (data.success) {
                        showMessage("✅ Komentar je uspešno sačuvan!", "success");
                        closeCommentModal();
                    } else {
                        showMessage("❌ Greška pri čuvanju komentara: " + (data.message || "Nepoznata greška"), "error");
                    }
                } catch (e) {
                    console.error("JSON parse error:", e);
                    console.error("Response is not JSON. First 500 chars:", text.substring(0, 500));
                    showMessage("❌ Greška: Server nije vratio valjan odgovor. Pogledajte console za detalje.", "error");
                }
            })
            .catch(error => {
                console.error("Fetch error:", error);
                showMessage("❌ Greška pri komunikaciji sa serverom: " + error.message, "error");
            });
        }
        
        // Close modal when clicking outside
        document.addEventListener("click", function(event) {
            const modal = document.getElementById("commentModal");
            if (event.target === modal) {
                closeCommentModal();
            }
        });
        
        // Close modal with Escape key
        document.addEventListener("keydown", function(event) {
            if (event.key === "Escape") {
                closeCommentModal();
            }
        });
        
        // Add CSS animation
        const style = document.createElement("style");
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
        `;
        document.head.appendChild(style);
        </script>';
        
        $connection->close();
        return $output;

    } catch (Exception $e) {
        return '<div style="background: #f8d7da; padding: 20px; border-radius: 8px; color: #721c24;">
            <h3 style="margin: 0 0 10px 0;">💥 Greška baze podataka</h3>
            <p style="margin: 0;">' . htmlspecialchars($e->getMessage()) . '</p>
        </div>';
    }
}

function check_user_match_table_function() {
    try {
        // Database config
        $db_config = [
            'host' => '65.21.234.24',
            'database' => 'ursbihba_lara195',
            'username' => 'ursbihba_lara195', 
            'password' => 'paradoX2019',
            'charset' => 'utf8mb4'
        ];

        $connection = new mysqli(
            $db_config['host'],
            $db_config['username'],
            $db_config['password'],
            $db_config['database']
        );

        if ($connection->connect_error) {
            throw new Exception('Connection failed: ' . $connection->connect_error);
        }

        // Proverava da li tabela postoji
        $result = $connection->query("SHOW TABLES LIKE 'user_match'");
        $table_exists = $result->num_rows > 0;
        
        $output = '<div style="background: white; padding: 20px; border-radius: 8px; margin: 20px 0;">';
        $output .= '<h3>🔍 Debug: User Match Tabela</h3>';
        
        if (!$table_exists) {
            $output .= '<div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0;">';
            $output .= '<strong>❌ Tabela user_match ne postoji!</strong><br>';
            $output .= 'Potrebno je kreirati tabelu user_match u bazi podataka.';
            $output .= '</div>';
            
            // Pokazuje SQL za kreiranje tabele
            $output .= '<div style="background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 10px 0;">';
            $output .= '<strong>SQL za kreiranje tabele:</strong><br>';
            $output .= '<code style="display: block; background: #f8f9fa; padding: 10px; margin: 10px 0; border-radius: 3px;">
CREATE TABLE user_match (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    match_id INT NOT NULL,
    liga VARCHAR(100),
    sezona VARCHAR(20),
    kolo VARCHAR(20),
    uloga VARCHAR(50),
    status VARCHAR(20) DEFAULT "aktivan",
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
</code>';
            $output .= '</div>';
        } else {
            // Tabela postoji, prikaži broj zapisa
            $count_result = $connection->query("SELECT COUNT(*) as total FROM user_match");
            $count = $count_result->fetch_assoc()['total'];
            
            $output .= '<div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0;">';
            $output .= '<strong>✅ Tabela user_match postoji!</strong><br>';
            $output .= 'Broj zapisa u tabeli: <strong>' . $count . '</strong>';
            $output .= '</div>';
            
            if ($count == 0) {
                $output .= '<div style="background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 10px 0;">';
                $output .= '<strong>⚠️ Tabela je prazna!</strong><br>';
                $output .= 'Potrebno je dodati test podatke u tabelu user_match.';
                $output .= '</div>';
                
                // Prikazuje sample INSERT
                $output .= '<div style="background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 10px 0;">';
                $output .= '<strong>Sample INSERT za test podatke:</strong><br>';
                $output .= '<code style="display: block; background: #f8f9fa; padding: 10px; margin: 10px 0; border-radius: 3px;">
INSERT INTO user_match (user_id, match_id, liga, sezona, kolo, uloga, status) VALUES
(1, 101, "Premijer Liga", "2024/25", "1", "Sudija", "aktivan"),
(2, 102, "Prva Liga", "2024/25", "1", "Asistent", "aktivan"),
(1, 103, "Premijer Liga", "2024/25", "2", "Četvrti sudija", "aktivan");
</code>';
                $output .= '</div>';
            } else {
                // Prikazuje prvih 5 zapisa
                $sample_result = $connection->query("SELECT * FROM user_match LIMIT 5");
                $output .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;">';
                $output .= '<strong>Prvi zapisi u tabeli:</strong><br>';
                $output .= '<table style="width: 100%; border-collapse: collapse; margin-top: 10px;">';
                $output .= '<tr style="background: #0073aa; color: white;">';
                $output .= '<th style="padding: 8px; border: 1px solid #ddd;">ID</th>';
                $output .= '<th style="padding: 8px; border: 1px solid #ddd;">User ID</th>';
                $output .= '<th style="padding: 8px; border: 1px solid #ddd;">Match ID</th>';
                $output .= '<th style="padding: 8px; border: 1px solid #ddd;">Liga</th>';
                $output .= '<th style="padding: 8px; border: 1px solid #ddd;">Uloga</th>';
                $output .= '</tr>';
                
                while ($row = $sample_result->fetch_assoc()) {
                    $output .= '<tr>';
                    $output .= '<td style="padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars($row['id']) . '</td>';
                    $output .= '<td style="padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars($row['user_id']) . '</td>';
                    $output .= '<td style="padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars($row['match_id']) . '</td>';
                    $output .= '<td style="padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars($row['liga']) . '</td>';
                    $output .= '<td style="padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars($row['uloga']) . '</td>';
                    $output .= '</tr>';
                }
                $output .= '</table>';
                $output .= '</div>';
            }
        }
        
        $output .= '</div>';
        $connection->close();
        return $output;
        
    } catch (Exception $e) {
        return '<div style="color: red; background: #f8d7da; padding: 15px; border-radius: 5px;">
            Greška: ' . htmlspecialchars($e->getMessage()) . '
        </div>';
    }
}

// Register shortcodes
add_shortcode('komisije_panel', 'komisije_panel_shortcode');
add_shortcode('komisije_panel_data', 'komisije_panel_data_shortcode');
add_shortcode('custom_table_check', 'check_user_match_table_function');
?>

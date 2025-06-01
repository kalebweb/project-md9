<?php
// config/database.php
class Database {
    private $host = 'localhost';
    private $db_name = 'local';
    private $username = 'root';
    private $password = 'root';
    private $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch(PDOException $e) {
            echo "Erro de conexão: " . $e->getMessage();
            die();
        }
        return $this->conn;
    }
}

// Configurações gerais do sistema
define('SITE_NAME', 'SaaS Orçamentos');
define('BASE_URL', 'http://orca-net.local:10099/');
define('ADMIN_EMAIL', 'admin@sistema.com');

// Configurações de sessão
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Mude para 1 em HTTPS
session_start();

// Configurações de erro (remover em produção)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('America/Sao_Paulo');
?>
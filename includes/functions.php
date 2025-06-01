<?php
// includes/functions.php

/**
 * Sanitizar string para evitar XSS
 */
function sanitize($string) {
    return htmlspecialchars(trim($string), ENT_QUOTES, 'UTF-8');
}

/**
 * Formatar CNPJ
 */
function formatCNPJ($cnpj) {
    $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
    if (strlen($cnpj) == 14) {
        return substr($cnpj, 0, 2) . '.' . 
               substr($cnpj, 2, 3) . '.' . 
               substr($cnpj, 5, 3) . '/' . 
               substr($cnpj, 8, 4) . '-' . 
               substr($cnpj, 12, 2);
    }
    return $cnpj;
}

/**
 * Formatar telefone
 */
function formatPhone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($phone) == 11) {
        return '(' . substr($phone, 0, 2) . ') ' . 
               substr($phone, 2, 5) . '-' . 
               substr($phone, 7, 4);
    } elseif (strlen($phone) == 10) {
        return '(' . substr($phone, 0, 2) . ') ' . 
               substr($phone, 2, 4) . '-' . 
               substr($phone, 6, 4);
    }
    return $phone;
}

/**
 * Formatar CEP
 */
function formatCEP($cep) {
    $cep = preg_replace('/[^0-9]/', '', $cep);
    if (strlen($cep) == 8) {
        return substr($cep, 0, 5) . '-' . substr($cep, 5, 3);
    }
    return $cep;
}

/**
 * Validar CNPJ completo
 */
function validarCNPJ($cnpj) {
    $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
    
    // Verifica se tem 14 dígitos
    if (strlen($cnpj) != 14) return false;
    
    // Verifica se não são todos números iguais
    if (preg_match('/(\d)\1{13}/', $cnpj)) return false;
    
    // Cálculo dos dígitos verificadores
    $soma = 0;
    $multiplicador = 5;
    
    for ($i = 0; $i < 12; $i++) {
        $soma += $cnpj[$i] * $multiplicador;
        $multiplicador = ($multiplicador == 2) ? 9 : $multiplicador - 1;
    }
    
    $resto = $soma % 11;
    $dv1 = ($resto < 2) ? 0 : 11 - $resto;
    
    if ($cnpj[12] != $dv1) return false;
    
    $soma = 0;
    $multiplicador = 6;
    
    for ($i = 0; $i < 13; $i++) {
        $soma += $cnpj[$i] * $multiplicador;
        $multiplicador = ($multiplicador == 2) ? 9 : $multiplicador - 1;
    }
    
    $resto = $soma % 11;
    $dv2 = ($resto < 2) ? 0 : 11 - $resto;
    
    return $cnpj[13] == $dv2;
}

/**
 * Validar CPF
 */
function validarCPF($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    
    if (strlen($cpf) != 11) return false;
    if (preg_match('/(\d)\1{10}/', $cpf)) return false;
    
    for ($t = 9; $t < 11; $t++) {
        $d = 0;
        for ($c = 0; $c < $t; $c++) {
            $d += $cpf[$c] * (($t + 1) - $c);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf[$c] != $d) return false;
    }
    
    return true;
}

/**
 * Formatar moeda brasileira
 */
function formatMoney($value) {
    return 'R$ ' . number_format($value, 2, ',', '.');
}

/**
 * Gerar número de orçamento único
 */
function gerarNumeroOrcamento($empresa_id) {
    $ano = date('Y');
    $mes = date('m');
    return $empresa_id . $ano . $mes . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

/**
 * Verificar se email é válido
 */
function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Gerar senha aleatória
 */
function gerarSenha($tamanho = 8) {
    $caracteres = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $senha = '';
    for ($i = 0; $i < $tamanho; $i++) {
        $senha .= $caracteres[rand(0, strlen($caracteres) - 1)];
    }
    return $senha;
}

/**
 * Converter data BR para MySQL
 */
function dateBRtoMySQL($date) {
    if (empty($date)) return null;
    $parts = explode('/', $date);
    if (count($parts) == 3) {
        return $parts[2] . '-' . $parts[1] . '-' . $parts[0];
    }
    return null;
}

/**
 * Converter data MySQL para BR
 */
function dateMySQLtoBR($date) {
    if (empty($date)) return '';
    $timestamp = strtotime($date);
    return date('d/m/Y', $timestamp);
}

/**
 * Converter datetime MySQL para BR
 */
function datetimeMySQLtoBR($datetime) {
    if (empty($datetime)) return '';
    $timestamp = strtotime($datetime);
    return date('d/m/Y H:i', $timestamp);
}

/**
 * Calcular diferença em dias
 */
function diferencaDias($data1, $data2) {
    $date1 = new DateTime($data1);
    $date2 = new DateTime($data2);
    $diff = $date1->diff($date2);
    return $diff->days;
}

/**
 * Verificar se a empresa pode criar mais orçamentos
 */
function podecriarOrcamento($empresa_id, $conn) {
    $query = "SELECT plano, limite_orcamentos, orcamentos_utilizados FROM empresas WHERE id = :empresa_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':empresa_id', $empresa_id);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $empresa = $stmt->fetch();
        
        // Se é premium, pode criar ilimitado
        if ($empresa['plano'] == 'premium') {
            return true;
        }
        
        // Se é gratuito, verificar limite
        return $empresa['orcamentos_utilizados'] < $empresa['limite_orcamentos'];
    }
    
    return false;
}

/**
 * Enviar email simples (para notificações)
 */
function enviarEmail($to, $subject, $message, $from = null) {
    if ($from === null) {
        $from = ADMIN_EMAIL;
    }
    
    $headers = "From: $from\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    return mail($to, $subject, $message, $headers);
}

/**
 * Log de erro personalizado
 */
function logError($message, $file = null, $line = null) {
    $log = date('Y-m-d H:i:s') . " - ";
    if ($file) $log .= basename($file) . ":$line - ";
    $log .= $message . PHP_EOL;
    
    error_log($log, 3, '../logs/error.log');
}

/**
 * Validar força da senha
 */
function validarForcaSenha($senha) {
    $pontos = 0;
    
    // Comprimento mínimo
    if (strlen($senha) >= 8) $pontos++;
    if (strlen($senha) >= 12) $pontos++;
    
    // Contém números
    if (preg_match('/[0-9]/', $senha)) $pontos++;
    
    // Contém letras minúsculas
    if (preg_match('/[a-z]/', $senha)) $pontos++;
    
    // Contém letras maiúsculas
    if (preg_match('/[A-Z]/', $senha)) $pontos++;
    
    // Contém símbolos
    if (preg_match('/[^a-zA-Z0-9]/', $senha)) $pontos++;
    
    return $pontos;
}

/**
 * Proteger contra CSRF
 */
function gerarTokenCSRF() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verificar token CSRF
 */
function verificarTokenCSRF($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Criar slug amigável
 */
function criarSlug($string) {
    $string = strtolower($string);
    $string = preg_replace('/[áàãâä]/u', 'a', $string);
    $string = preg_replace('/[éèêë]/u', 'e', $string);
    $string = preg_replace('/[íìîï]/u', 'i', $string);
    $string = preg_replace('/[óòõôö]/u', 'o', $string);
    $string = preg_replace('/[úùûü]/u', 'u', $string);
    $string = preg_replace('/[ç]/u', 'c', $string);
    $string = preg_replace('/[^a-z0-9\s]/', '', $string);
    $string = preg_replace('/\s+/', '-', $string);
    return trim($string, '-');
}

/**
 * Paginar resultados
 */
function paginar($query, $conn, $params = [], $por_pagina = 20, $pagina_atual = 1) {
    // Contar total de registros
    $count_query = "SELECT COUNT(*) as total FROM ($query) as count_table";
    $stmt = $conn->prepare($count_query);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    $total = $stmt->fetch()['total'];
    
    // Calcular offset
    $offset = ($pagina_atual - 1) * $por_pagina;
    
    // Query com LIMIT
    $query_paginada = $query . " LIMIT $por_pagina OFFSET $offset";
    
    $stmt = $conn->prepare($query_paginada);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    $resultados = $stmt->fetchAll();
    
    // Calcular informações de paginação
    $total_paginas = ceil($total / $por_pagina);
    
    return [
        'dados' => $resultados,
        'total' => $total,
        'pagina_atual' => $pagina_atual,
        'total_paginas' => $total_paginas,
        'por_pagina' => $por_pagina,
        'tem_anterior' => $pagina_atual > 1,
        'tem_proximo' => $pagina_atual < $total_paginas
    ];
}

/**
 * Gerar HTML para paginação
 */
function htmlPaginacao($paginacao, $url_base) {
    if ($paginacao['total_paginas'] <= 1) return '';
    
    $html = '<nav class="pagination-nav"><ul class="pagination">';
    
    // Botão anterior
    if ($paginacao['tem_anterior']) {
        $pagina_anterior = $paginacao['pagina_atual'] - 1;
        $html .= '<li><a href="' . $url_base . '?pagina=' . $pagina_anterior . '">&laquo; Anterior</a></li>';
    }
    
    // Números das páginas
    $inicio = max(1, $paginacao['pagina_atual'] - 2);
    $fim = min($paginacao['total_paginas'], $paginacao['pagina_atual'] + 2);
    
    if ($inicio > 1) {
        $html .= '<li><a href="' . $url_base . '?pagina=1">1</a></li>';
        if ($inicio > 2) {
            $html .= '<li><span>...</span></li>';
        }
    }
    
    for ($i = $inicio; $i <= $fim; $i++) {
        if ($i == $paginacao['pagina_atual']) {
            $html .= '<li><span class="current">' . $i . '</span></li>';
        } else {
            $html .= '<li><a href="' . $url_base . '?pagina=' . $i . '">' . $i . '</a></li>';
        }
    }
    
    if ($fim < $paginacao['total_paginas']) {
        if ($fim < $paginacao['total_paginas'] - 1) {
            $html .= '<li><span>...</span></li>';
        }
        $html .= '<li><a href="' . $url_base . '?pagina=' . $paginacao['total_paginas'] . '">' . $paginacao['total_paginas'] . '</a></li>';
    }
    
    // Botão próximo
    if ($paginacao['tem_proximo']) {
        $proxima_pagina = $paginacao['pagina_atual'] + 1;
        $html .= '<li><a href="' . $url_base . '?pagina=' . $proxima_pagina . '">Próximo &raquo;</a></li>';
    }
    
    $html .= '</ul></nav>';
    
    return $html;
}

/**
 * Redimensionar imagem (para futuras funcionalidades)
 */
function redimensionarImagem($origem, $destino, $largura_max, $altura_max) {
    $info = getimagesize($origem);
    if (!$info) return false;
    
    $largura_original = $info[0];
    $altura_original = $info[1];
    $tipo = $info[2];
    
    // Calcular novas dimensões
    $ratio = min($largura_max / $largura_original, $altura_max / $altura_original);
    $nova_largura = $largura_original * $ratio;
    $nova_altura = $altura_original * $ratio;
    
    // Criar imagem baseada no tipo
    switch ($tipo) {
        case IMAGETYPE_JPEG:
            $imagem_original = imagecreatefromjpeg($origem);
            break;
        case IMAGETYPE_PNG:
            $imagem_original = imagecreatefrompng($origem);
            break;
        case IMAGETYPE_GIF:
            $imagem_original = imagecreatefromgif($origem);
            break;
        default:
            return false;
    }
    
    // Criar nova imagem
    $nova_imagem = imagecreatetruecolor($nova_largura, $nova_altura);
    
    // Preservar transparência para PNG
    if ($tipo == IMAGETYPE_PNG) {
        imagealphablending($nova_imagem, false);
        imagesavealpha($nova_imagem, true);
    }
    
    // Redimensionar
    imagecopyresampled(
        $nova_imagem, $imagem_original,
        0, 0, 0, 0,
        $nova_largura, $nova_altura,
        $largura_original, $altura_original
    );
    
    // Salvar
    $resultado = false;
    switch ($tipo) {
        case IMAGETYPE_JPEG:
            $resultado = imagejpeg($nova_imagem, $destino, 85);
            break;
        case IMAGETYPE_PNG:
            $resultado = imagepng($nova_imagem, $destino);
            break;
        case IMAGETYPE_GIF:
            $resultado = imagegif($nova_imagem, $destino);
            break;
    }
    
    // Limpar memória
    imagedestroy($imagem_original);
    imagedestroy($nova_imagem);
    
    return $resultado;
}

/**
 * Fazer backup do banco de dados
 */
function backupBanco($host, $username, $password, $database, $arquivo_destino) {
    $comando = "mysqldump --host=$host --user=$username --password=$password $database > $arquivo_destino";
    $resultado = shell_exec($comando);
    return file_exists($arquivo_destino);
}

/**
 * Verificar espaço em disco
 */
function verificarEspacoDisco($diretorio = '.') {
    $bytes = disk_free_space($diretorio);
    $gb = $bytes / 1024 / 1024 / 1024;
    return round($gb, 2);
}

/**
 * Limpar cache (para futuras implementações)
 */
function limparCache($diretorio_cache = '../cache/') {
    if (!is_dir($diretorio_cache)) return false;
    
    $arquivos = glob($diretorio_cache . '*');
    $removidos = 0;
    
    foreach ($arquivos as $arquivo) {
        if (is_file($arquivo)) {
            unlink($arquivo);
            $removidos++;
        }
    }
    
    return $removidos;
}

/**
 * Detectar dispositivo móvel
 */
function isMobile() {
    return preg_match('/Mobile|Android|iPhone|iPad/', $_SERVER['HTTP_USER_AGENT'] ?? '');
}

/**
 * Gerar breadcrumb
 */
function gerarBreadcrumb($paginas) {
    $html = '<nav class="breadcrumb"><ol>';
    
    $total = count($paginas);
    $contador = 1;
    
    foreach ($paginas as $titulo => $url) {
        if ($contador == $total) {
            // Última página (atual)
            $html .= '<li class="current">' . htmlspecialchars($titulo) . '</li>';
        } else {
            $html .= '<li><a href="' . htmlspecialchars($url) . '">' . htmlspecialchars($titulo) . '</a></li>';
        }
        $contador++;
    }
    
    $html .= '</ol></nav>';
    return $html;
}

/**
 * Converter texto para formato de busca
 */
function prepararParaBusca($texto) {
    $texto = strtolower($texto);
    $texto = preg_replace('/[áàãâä]/u', 'a', $texto);
    $texto = preg_replace('/[éèêë]/u', 'e', $texto);
    $texto = preg_replace('/[íìîï]/u', 'i', $texto);
    $texto = preg_replace('/[óòõôö]/u', 'o', $texto);
    $texto = preg_replace('/[úùûü]/u', 'u', $texto);
    $texto = preg_replace('/[ç]/u', 'c', $texto);
    $texto = preg_replace('/[^a-z0-9\s]/', ' ', $texto);
    $texto = preg_replace('/\s+/', ' ', $texto);
    return trim($texto);
}

/**
 * Verificar se string contém palavras-chave
 */
function contemPalavras($texto, $palavras) {
    $texto = prepararParaBusca($texto);
    $palavras_array = explode(' ', prepararParaBusca($palavras));
    
    foreach ($palavras_array as $palavra) {
        if (strlen($palavra) >= 3 && strpos($texto, $palavra) !== false) {
            return true;
        }
    }
    
    return false;
}

/**
 * Formatar bytes para leitura humana
 */
function formatarBytes($bytes, $precisao = 2) {
    $unidades = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024 && $i < count($unidades) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precisao) . ' ' . $unidades[$i];
}

/**
 * Validar upload de arquivo
 */
function validarUpload($arquivo, $tipos_permitidos = ['jpg', 'jpeg', 'png', 'pdf'], $tamanho_max = 5242880) {
    $erros = [];
    
    // Verificar se arquivo foi enviado
    if (!isset($arquivo['tmp_name']) || !is_uploaded_file($arquivo['tmp_name'])) {
        $erros[] = 'Nenhum arquivo foi enviado.';
        return $erros;
    }
    
    // Verificar tamanho
    if ($arquivo['size'] > $tamanho_max) {
        $erros[] = 'Arquivo muito grande. Máximo: ' . formatarBytes($tamanho_max);
    }
    
    // Verificar extensão
    $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
    if (!in_array($extensao, $tipos_permitidos)) {
        $erros[] = 'Tipo de arquivo não permitido. Permitidos: ' . implode(', ', $tipos_permitidos);
    }
    
    // Verificar se é realmente uma imagem (para tipos de imagem)
    $tipos_imagem = ['jpg', 'jpeg', 'png', 'gif'];
    if (in_array($extensao, $tipos_imagem)) {
        $info_imagem = getimagesize($arquivo['tmp_name']);
        if (!$info_imagem) {
            $erros[] = 'Arquivo de imagem inválido.';
        }
    }
    
    return $erros;
}

/**
 * Gerar hash único para arquivo
 */
function gerarHashArquivo($arquivo_path) {
    return hash_file('sha256', $arquivo_path);
}

/**
 * Criar diretório se não existir
 */
function criarDiretorio($caminho, $permissoes = 0755) {
    if (!is_dir($caminho)) {
        return mkdir($caminho, $permissoes, true);
    }
    return true;
}

// Definir constantes úteis se não estiverem definidas
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__) . DS);
}

if (!defined('UPLOAD_PATH')) {
    define('UPLOAD_PATH', ROOT_PATH . 'uploads' . DS);
}
?>
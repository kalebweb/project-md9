<?php
// public/produtos/adicionar.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/Produto.php';

$auth = new Auth();

// Verificar se está logado e tem permissão
if (!$auth->isLoggedIn() || !$auth->hasPermission('user')) {
    header('Location: ../login.php');
    exit;
}

$produto = new Produto();
$error = '';
$success = '';

// Buscar categorias para o dropdown
$categorias = $produto->listarCategorias($_SESSION['empresa_id'], 1);

if ($_POST) {
    $dados = [
        'categoria_id' => !empty($_POST['categoria_id']) ? $_POST['categoria_id'] : null,
        'nome' => trim($_POST['nome']),
        'descricao' => trim($_POST['descricao']),
        'preco' => str_replace(',', '.', str_replace('.', '', $_POST['preco'])),
        'codigo' => trim($_POST['codigo']),
        'unidade' => trim($_POST['unidade']) ?: 'UN',
        'estoque_minimo' => (int)($_POST['estoque_minimo'] ?: 0),
        'estoque_atual' => (int)($_POST['estoque_atual'] ?: 0),
        'foto' => $_FILES['foto'] ?? null
    ];
    
    // Validações básicas
    if (empty($dados['nome']) || empty($dados['preco'])) {
        $error = 'Nome e preço são obrigatórios.';
    } elseif (!is_numeric($dados['preco']) || $dados['preco'] <= 0) {
        $error = 'Preço deve ser um valor válido maior que zero.';
    } else {
        $result = $produto->adicionarProduto($dados, $_SESSION['empresa_id']);
        if ($result['success']) {
            $success = $result['message'];
            // Limpar campos após sucesso
            $dados = array_fill_keys(array_keys($dados), '');
            $dados['unidade'] = 'UN';
            $dados['estoque_minimo'] = 0;
            $dados['estoque_atual'] = 0;
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adicionar Produto - <?php echo SITE_NAME; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            line-height: 1.6;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 1.5rem;
        }
        
        .user-info a {
            color: white;
            text-decoration: none;
            opacity: 0.9;
            transition: opacity 0.3s;
        }
        
        .user-info a:hover {
            opacity: 1;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .breadcrumb {
            margin-bottom: 2rem;
        }
        
        .breadcrumb a {
            color: #667eea;
            text-decoration: none;
        }
        
        .breadcrumb a:hover {
            text-decoration: underline;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .card-header h2 {
            margin: 0;
            font-size: 1.8rem;
        }
        
        .card-header p {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
        }
        
        .card-body {
            padding: 2rem;
        }
        
        .section {
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid #eee;
        }
        
        .section:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        
        .section h3 {
            color: #333;
            margin-bottom: 1rem;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .form-row.triple {
            grid-template-columns: 1fr 1fr 1fr;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group.full {
            grid-column: 1 / -1;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }
        
        .required {
            color: #e74c3c;
        }
        
        input[type="text"],
        input[type="number"],
        input[type="file"],
        select,
        textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        
        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .form-help {
            font-size: 0.875rem;
            color: #666;
            margin-top: 0.25rem;
        }
        
        .upload-area {
            border: 2px dashed #e1e5e9;
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            transition: border-color 0.3s, background-color 0.3s;
            cursor: pointer;
            position: relative;
        }
        
        .upload-area:hover {
            border-color: #667eea;
            background-color: #f8f9ff;
        }
        
        .upload-area.dragover {
            border-color: #667eea;
            background-color: #f0f4ff;
        }
        
        .upload-icon {
            font-size: 3rem;
            color: #ccc;
            margin-bottom: 1rem;
        }
        
        .upload-text {
            color: #666;
            margin-bottom: 0.5rem;
        }
        
        .upload-help {
            font-size: 0.8rem;
            color: #999;
        }
        
        .file-input {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }
        
        .preview-container {
            margin-top: 1rem;
            text-align: center;
        }
        
        .preview-image {
            max-width: 200px;
            max-height: 200px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .remove-image {
            display: inline-block;
            margin-top: 0.5rem;
            color: #dc3545;
            cursor: pointer;
            font-size: 0.9rem;
        }
        
        .remove-image:hover {
            text-decoration: underline;
        }
        
        .btn {
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: space-between;
            align-items: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #eee;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .price-input {
            position: relative;
        }
        
        .price-input::before {
            content: 'R$';
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            font-weight: 500;
            z-index: 1;
        }
        
        .price-input input {
            padding-left: 2.5rem;
        }
        
        .quick-category {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.5rem;
            flex-wrap: wrap;
        }
        
        .quick-category button {
            padding: 0.25rem 0.75rem;
            border: 1px solid #ddd;
            background: white;
            border-radius: 20px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .quick-category button:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            
            .form-row,
            .form-row.triple {
                grid-template-columns: 1fr;
            }
            
            .form-actions {
                flex-direction: column;
                gap: 1rem;
            }
            
            .card-body {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1><?php echo SITE_NAME; ?></h1>
            <div class="user-info">
                <a href="../dashboard.php">← Dashboard</a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="breadcrumb">
            <a href="../dashboard.php">Dashboard</a> / 
            <a href="lista.php">Produtos</a> / 
            <strong>Adicionar</strong>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2>Adicionar Novo Produto</h2>
                <p>Cadastre um produto para incluir em seus orçamentos</p>
            </div>
            
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data" id="formProduto">
                    <!-- Informações Básicas -->
                    <div class="section">
                        <h3>📦 Informações Básicas</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="nome">Nome do Produto <span class="required">*</span></label>
                                <input type="text" id="nome" name="nome" required 
                                       value="<?php echo isset($dados['nome']) ? htmlspecialchars($dados['nome']) : ''; ?>"
                                       placeholder="Digite o nome do produto">
                            </div>
                            
                            <div class="form-group">
                                <label for="categoria_id">Categoria</label>
                                <select id="categoria_id" name="categoria_id">
                                    <option value="">Selecione uma categoria</option>
                                    <?php foreach ($categorias as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>" 
                                                <?php echo (isset($dados['categoria_id']) && $dados['categoria_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['nome']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-help">
                                    <a href="categorias.php" target="_blank">Gerenciar categorias</a>
                                </div>
                                <div class="quick-category">
                                    <button type="button" onclick="criarCategoriaRapida('Produtos')">+ Produtos</button>
                                    <button type="button" onclick="criarCategoriaRapida('Serviços')">+ Serviços</button>
                                    <button type="button" onclick="criarCategoriaRapida('Consultoria')">+ Consultoria</button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="descricao">Descrição</label>
                            <textarea id="descricao" name="descricao" 
                                      placeholder="Descreva o produto, suas características, benefícios..."><?php echo isset($dados['descricao']) ? htmlspecialchars($dados['descricao']) : ''; ?></textarea>
                            <div class="form-help">Descrição que aparecerá nos orçamentos</div>
                        </div>
                    </div>
                    
                    <!-- Foto do Produto -->
                    <div class="section">
                        <h3>📸 Foto do Produto</h3>
                        
                        <div class="form-group">
                            <div class="upload-area" id="uploadArea">
                                <div class="upload-icon">📷</div>
                                <div class="upload-text">Clique ou arraste uma imagem aqui</div>
                                <div class="upload-help">JPG, PNG ou GIF - Máximo 5MB</div>
                                <input type="file" name="foto" id="foto" class="file-input" accept="image/*">
                            </div>
                            <div class="preview-container" id="previewContainer" style="display: none;">
                                <img id="previewImage" class="preview-image" alt="Preview">
                                <div class="remove-image" onclick="removerImagem()">🗑️ Remover imagem</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Preço e Código -->
                    <div class="section">
                        <h3>💰 Preço e Identificação</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="preco">Preço <span class="required">*</span></label>
                                <div class="price-input">
                                    <input type="text" id="preco" name="preco" required 
                                           value="<?php echo isset($dados['preco']) ? number_format($dados['preco'], 2, ',', '.') : ''; ?>"
                                           placeholder="0,00">
                                </div>
                                <div class="form-help">Preço base do produto</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="codigo">Código/SKU</label>
                                <input type="text" id="codigo" name="codigo" 
                                       value="<?php echo isset($dados['codigo']) ? htmlspecialchars($dados['codigo']) : ''; ?>"
                                       placeholder="Ex: PROD-001, SKU123">
                                <div class="form-help">Código interno para identificação</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Estoque e Unidade -->
                    <div class="section">
                        <h3>📊 Controle de Estoque</h3>
                        
                        <div class="form-row triple">
                            <div class="form-group">
                                <label for="unidade">Unidade</label>
                                <select id="unidade" name="unidade">
                                    <option value="UN" <?php echo (isset($dados['unidade']) && $dados['unidade'] == 'UN') ? 'selected' : ''; ?>>Unidade (UN)</option>
                                    <option value="KG" <?php echo (isset($dados['unidade']) && $dados['unidade'] == 'KG') ? 'selected' : ''; ?>>Quilograma (KG)</option>
                                    <option value="M" <?php echo (isset($dados['unidade']) && $dados['unidade'] == 'M') ? 'selected' : ''; ?>>Metro (M)</option>
                                    <option value="M2" <?php echo (isset($dados['unidade']) && $dados['unidade'] == 'M2') ? 'selected' : ''; ?>>Metro² (M²)</option>
                                    <option value="M3" <?php echo (isset($dados['unidade']) && $dados['unidade'] == 'M3') ? 'selected' : ''; ?>>Metro³ (M³)</option>
                                    <option value="L" <?php echo (isset($dados['unidade']) && $dados['unidade'] == 'L') ? 'selected' : ''; ?>>Litro (L)</option>
                                    <option value="CX" <?php echo (isset($dados['unidade']) && $dados['unidade'] == 'CX') ? 'selected' : ''; ?>>Caixa (CX)</option>
                                    <option value="HR" <?php echo (isset($dados['unidade']) && $dados['unidade'] == 'HR') ? 'selected' : ''; ?>>Hora (HR)</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="estoque_minimo">Estoque Mínimo</label>
                                <input type="number" id="estoque_minimo" name="estoque_minimo" min="0" 
                                       value="<?php echo isset($dados['estoque_minimo']) ? $dados['estoque_minimo'] : 0; ?>">
                                <div class="form-help">Quantidade mínima em estoque</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="estoque_atual">Estoque Atual</label>
                                <input type="number" id="estoque_atual" name="estoque_atual" min="0" 
                                       value="<?php echo isset($dados['estoque_atual']) ? $dados['estoque_atual'] : 0; ?>">
                                <div class="form-help">Quantidade atual disponível</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <a href="lista.php" class="btn btn-secondary">← Cancelar</a>
                        <div>
                            <button type="button" onclick="salvarContinuar()" class="btn btn-success">💾 Salvar e Adicionar Outro</button>
                            <button type="submit" class="btn btn-primary">✅ Salvar Produto</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Upload de imagem com drag & drop
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('foto');
        const previewContainer = document.getElementById('previewContainer');
        const previewImage = document.getElementById('previewImage');
        
        uploadArea.addEventListener('click', () => fileInput.click());
        
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });
        
        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });
        
        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                mostrarPreview(files[0]);
            }
        });
        
        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                mostrarPreview(e.target.files[0]);
            }
        });
        
        function mostrarPreview(file) {
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    previewImage.src = e.target.result;
                    previewContainer.style.display = 'block';
                    uploadArea.style.display = 'none';
                };
                reader.readAsDataURL(file);
            }
        }
        
        function removerImagem() {
            fileInput.value = '';
            previewContainer.style.display = 'none';
            uploadArea.style.display = 'block';
        }
        
        // Máscara para preço
        document.getElementById('preco').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = (value / 100).toFixed(2);
            value = value.replace('.', ',');
            value = value.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            e.target.value = value;
        });
        
        // Criar categoria rápida
        function criarCategoriaRapida(nome) {
            const select = document.getElementById('categoria_id');
            const option = new Option(nome, 'new_' + nome);
            select.add(option);
            select.value = option.value;
        }
        
        // Salvar e continuar
        function salvarContinuar() {
            const form = document.getElementById('formProduto');
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'continuar';
            input.value = '1';
            form.appendChild(input);
            form.submit();
        }
        
        // Auto-hide success alerts
        setTimeout(function() {
            const successAlerts = document.querySelectorAll('.alert-success');
            successAlerts.forEach(function(alert) {
                alert.style.opacity = '0.7';
                setTimeout(function() {
                    alert.style.display = 'none';
                }, 3000);
            });
        }, 3000);
        
        // Validação do formulário
        document.getElementById('formProduto').addEventListener('submit', function(e) {
            const nome = document.getElementById('nome').value.trim();
            const preco = document.getElementById('preco').value.trim();
            
            if (!nome) {
                e.preventDefault();
                alert('Nome do produto é obrigatório.');
                return;
            }
            
            if (!preco || preco === '0,00') {
                e.preventDefault();
                alert('Preço do produto é obrigatório e deve ser maior que zero.');
                return;
            }
        });
    </script>
</body>
</html>
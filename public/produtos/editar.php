<?php
// public/produtos/editar.php
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

// Verificar se ID foi fornecido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: lista.php');
    exit;
}

$produto_id = $_GET['id'];

// Buscar dados do produto
$result = $produto->buscarProduto($produto_id, $_SESSION['empresa_id']);
if (!$result['success']) {
    header('Location: lista.php');
    exit;
}

$dados = $result['produto'];

// Buscar categorias para o dropdown
$categorias = $produto->listarCategorias($_SESSION['empresa_id'], 1);

if ($_POST) {
    $dados_form = [
        'categoria_id' => !empty($_POST['categoria_id']) ? $_POST['categoria_id'] : null,
        'nome' => trim($_POST['nome']),
        'descricao' => trim($_POST['descricao']),
        'preco' => str_replace(',', '.', str_replace('.', '', $_POST['preco'])),
        'codigo' => trim($_POST['codigo']),
        'unidade' => trim($_POST['unidade']) ?: 'UN',
        'estoque_minimo' => (int)($_POST['estoque_minimo'] ?: 0),
        'estoque_atual' => (int)($_POST['estoque_atual'] ?: 0),
        'ativo' => isset($_POST['ativo']) ? 1 : 0,
        'foto' => $_FILES['foto'] ?? null
    ];

    // Lógica para categoria rápida
    if (!empty($dados_form['categoria_id']) && strpos($dados_form['categoria_id'], 'new_') === 0) {
        $nome_categoria = substr($dados_form['categoria_id'], 4); // Remove 'new_'
        $dados_categoria = [
            'nome' => $nome_categoria,
            'descricao' => '',
            'cor' => '#667eea' // cor padrão
        ];
        $cat_result = $produto->adicionarCategoria($dados_categoria, $_SESSION['empresa_id']);
        if ($cat_result['success']) {
            $dados_form['categoria_id'] = $cat_result['id'];
        } else {
            $error = 'Erro ao criar categoria: ' . $cat_result['message'];
        }
    }

    // Validações básicas
    if (empty($dados_form['nome']) || empty($dados_form['preco'])) {
        $error = 'Nome e preço são obrigatórios.';
    } elseif (!is_numeric($dados_form['preco']) || $dados_form['preco'] <= 0) {
        $error = 'Preço deve ser um valor válido maior que zero.';
    } elseif (empty($error)) { // só tenta editar se não houve erro na categoria
        $result = $produto->editarProduto($produto_id, $dados_form, $_SESSION['empresa_id']);
        if ($result['success']) {
            $success = $result['message'];
            // Atualizar dados exibidos
            $dados = array_merge($dados, $dados_form);
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
    <title>Editar Produto - <?php echo SITE_NAME; ?></title>

    <link rel="stylesheet" href="../../css/styles.css">
    
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
            <strong>Editar</strong>
        </div>
        <div class="card">
            <div class="card-header">
                <h2>Editar Produto</h2>
                <p>Altere as informações do produto</p>
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
                    <div class="section">
                        <h3>📦 Informações Básicas</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="nome">Nome do Produto <span class="required">*</span></label>
                                <input type="text" id="nome" name="nome" required value="<?php echo htmlspecialchars($dados['nome']); ?>" placeholder="Digite o nome do produto">
                            </div>
                            <div class="form-group">
                                <label for="categoria_id">Categoria</label>
                                <select id="categoria_id" name="categoria_id">
                                    <option value="">Selecione uma categoria</option>
                                    <?php foreach ($categorias as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>" <?php echo ($dados['categoria_id'] == $cat['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['nome']); ?></option>
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
                            <textarea id="descricao" name="descricao" placeholder="Descreva o produto, suas características, benefícios..."><?php echo htmlspecialchars($dados['descricao']); ?></textarea>
                            <div class="form-help">Descrição que aparecerá nos orçamentos</div>
                        </div>
                    </div>
                    <div class="section">
                        <h3>📸 Foto do Produto</h3>
                        <div class="form-group">
                            <div class="upload-area" id="uploadArea">
                                <div class="upload-icon">📷</div>
                                <div class="upload-text">Clique ou arraste uma imagem aqui</div>
                                <div class="upload-help">JPG, PNG ou GIF - Máximo 5MB</div>
                                <input type="file" name="foto" id="foto" class="file-input" accept="image/*">
                            </div>
                            <div class="preview-container" id="previewContainer" style="display: <?php echo !empty($dados['foto']) ? 'block' : 'none'; ?>;">
                                <?php if (!empty($dados['foto'])): ?>
                                    <img id="previewImage" class="preview-image" src="../../uploads/produtos/<?php echo $_SESSION['empresa_id']; ?>/<?php echo $dados['foto']; ?>" alt="Preview">
                                <?php else: ?>
                                    <img id="previewImage" class="preview-image" style="display:none;" alt="Preview">
                                <?php endif; ?>
                                <div class="remove-image" onclick="removerImagem()">🗑️ Remover imagem</div>
                            </div>
                        </div>
                    </div>
                    <div class="section">
                        <h3>💰 Preço e Identificação</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="preco">Preço <span class="required">*</span></label>
                                <div class="price-input">
                                    <input type="text" id="preco" name="preco" required value="<?php echo number_format($dados['preco'], 2, ',', '.'); ?>" placeholder="0,00">
                                </div>
                                <div class="form-help">Preço base do produto</div>
                            </div>
                            <div class="form-group">
                                <label for="codigo">Código/SKU</label>
                                <input type="text" id="codigo" name="codigo" value="<?php echo htmlspecialchars($dados['codigo']); ?>" placeholder="Ex: PROD-001, SKU123">
                                <div class="form-help">Código interno para identificação</div>
                            </div>
                        </div>
                    </div>
                    <div class="section">
                        <h3>📊 Controle de Estoque</h3>
                        <div class="form-row triple">
                            <div class="form-group">
                                <label for="unidade">Unidade</label>
                                <select id="unidade" name="unidade">
                                    <option value="UN" <?php echo ($dados['unidade'] == 'UN') ? 'selected' : ''; ?>>Unidade (UN)</option>
                                    <option value="KG" <?php echo ($dados['unidade'] == 'KG') ? 'selected' : ''; ?>>Quilograma (KG)</option>
                                    <option value="M" <?php echo ($dados['unidade'] == 'M') ? 'selected' : ''; ?>>Metro (M)</option>
                                    <option value="M2" <?php echo ($dados['unidade'] == 'M2') ? 'selected' : ''; ?>>Metro² (M²)</option>
                                    <option value="M3" <?php echo ($dados['unidade'] == 'M3') ? 'selected' : ''; ?>>Metro³ (M³)</option>
                                    <option value="L" <?php echo ($dados['unidade'] == 'L') ? 'selected' : ''; ?>>Litro (L)</option>
                                    <option value="CX" <?php echo ($dados['unidade'] == 'CX') ? 'selected' : ''; ?>>Caixa (CX)</option>
                                    <option value="HR" <?php echo ($dados['unidade'] == 'HR') ? 'selected' : ''; ?>>Hora (HR)</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="estoque_minimo">Estoque Mínimo</label>
                                <input type="number" id="estoque_minimo" name="estoque_minimo" min="0" value="<?php echo $dados['estoque_minimo']; ?>">
                                <div class="form-help">Quantidade mínima em estoque</div>
                            </div>
                            <div class="form-group">
                                <label for="estoque_atual">Estoque Atual</label>
                                <input type="number" id="estoque_atual" name="estoque_atual" min="0" value="<?php echo $dados['estoque_atual']; ?>">
                                <div class="form-help">Quantidade atual disponível</div>
                            </div>
                        </div>
                        <div class="checkbox-group">
                            <input type="checkbox" id="ativo" name="ativo" <?php echo $dados['ativo'] ? 'checked' : ''; ?>>
                            <label for="ativo">
                                <strong>Produto Ativo</strong> - Pode ser usado em orçamentos
                            </label>
                        </div>
                    </div>
                    <div class="form-actions">
                        <a href="lista.php" class="btn btn-secondary">← Voltar</a>
                        <button type="submit" class="btn btn-primary">💾 Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        // Use os mesmos scripts de adicionar.php para upload, preview, máscara de preço e categoria rápida
    </script>
</body>
</html>

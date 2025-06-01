<?php
// public/produtos/categorias.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/Produto.php';

$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->hasPermission('user')) {
    header('Location: ../login.php');
    exit;
}

$produto = new Produto();
$error = '';
$success = '';

// Adicionar categoria
if (isset($_POST['acao']) && $_POST['acao'] === 'adicionar') {
    $dados = [
        'nome' => trim($_POST['nome']),
        'descricao' => trim($_POST['descricao']),
        'cor' => $_POST['cor'] ?: '#667eea'
    ];
    if (empty($dados['nome'])) {
        $error = 'O nome da categoria é obrigatório.';
    } else {
        $result = $produto->adicionarCategoria($dados, $_SESSION['empresa_id']);
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['message'];
        }
    }
}

// Editar categoria
if (isset($_POST['acao']) && $_POST['acao'] === 'editar' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $dados = [
        'nome' => trim($_POST['nome']),
        'descricao' => trim($_POST['descricao']),
        'cor' => $_POST['cor'] ?: '#667eea'
    ];
    if (empty($dados['nome'])) {
        $error = 'O nome da categoria é obrigatório.';
    } else {
        $result = $produto->editarCategoria($id, $dados, $_SESSION['empresa_id']);
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['message'];
        }
    }
}

// Listar categorias
$categorias = $produto->listarCategorias($_SESSION['empresa_id']);

// Buscar categoria para edição
$categoria_editar = null;
if (isset($_GET['editar']) && is_numeric($_GET['editar'])) {
    foreach ($categorias as $cat) {
        if ($cat['id'] == $_GET['editar']) {
            $categoria_editar = $cat;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categorias de Produto - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../../css/styles.css">
    <style>
        .color-input { width: 50px; height: 32px; border: none; }
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
            <strong>Categorias</strong>
        </div>
        <div class="card">
            <div class="card-header">
                <h2>Categorias de Produto</h2>
                <p>Gerencie as categorias dos seus produtos</p>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                <div class="section">
                    <h3><?php echo $categoria_editar ? 'Editar Categoria' : 'Adicionar Nova Categoria'; ?></h3>
                    <form method="POST">
                        <input type="hidden" name="acao" value="<?php echo $categoria_editar ? 'editar' : 'adicionar'; ?>">
                        <?php if ($categoria_editar): ?>
                            <input type="hidden" name="id" value="<?php echo $categoria_editar['id']; ?>">
                        <?php endif; ?>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="nome">Nome <span class="required">*</span></label>
                                <input type="text" id="nome" name="nome" required value="<?php echo htmlspecialchars($categoria_editar['nome'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="cor">Cor</label>
                                <input type="color" id="cor" name="cor" class="color-input" value="<?php echo htmlspecialchars($categoria_editar['cor'] ?? '#667eea'); ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="descricao">Descrição</label>
                            <textarea id="descricao" name="descricao"><?php echo htmlspecialchars($categoria_editar['descricao'] ?? ''); ?></textarea>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">💾 <?php echo $categoria_editar ? 'Salvar Alterações' : 'Adicionar Categoria'; ?></button>
                            <?php if ($categoria_editar): ?>
                                <a href="categorias.php" class="btn btn-secondary">Cancelar</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
                <div class="section">
                    <h3>Lista de Categorias</h3>
                    <table style="width:100%;border-collapse:collapse;">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Cor</th>
                                <th>Descrição</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categorias as $cat): ?>
                                <tr>
                                    <td><?php echo $cat['id']; ?></td>
                                    <td><?php echo htmlspecialchars($cat['nome']); ?></td>
                                    <td><span style="display:inline-block;width:24px;height:24px;background:<?php echo htmlspecialchars($cat['cor']); ?>;border-radius:4px;"></span></td>
                                    <td><?php echo htmlspecialchars($cat['descricao']); ?></td>
                                    <td>
                                        <a href="categorias.php?editar=<?php echo $cat['id']; ?>" class="btn btn-sm btn-primary">✏️ Editar</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

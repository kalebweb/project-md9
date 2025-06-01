<?php
// public/admin/pagamentos.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/Auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->hasPermission('super_admin')) {
    header('Location: ../login.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();
$error = '';
$success = '';

// Adicionar novo plano
if ($_POST && isset($_POST['acao']) && $_POST['acao'] === 'adicionar_plano') {
    $dados = [
        'nome' => trim($_POST['nome']),
        'descricao' => trim($_POST['descricao']),
        'valor' => str_replace(',', '.', str_replace('.', '', $_POST['valor'])),
        'limite_orcamentos' => (int)$_POST['limite_orcamentos']
    ];
    if (empty($dados['nome']) || !is_numeric($dados['valor']) || $dados['valor'] < 0) {
        $error = 'Preencha o nome do plano e um valor válido.';
    } else {
        $query = "INSERT INTO planos (nome, descricao, valor, limite_orcamentos) VALUES (:nome, :descricao, :valor, :limite_orcamentos)";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':nome', $dados['nome']);
        $stmt->bindParam(':descricao', $dados['descricao']);
        $stmt->bindParam(':valor', $dados['valor']);
        $stmt->bindParam(':limite_orcamentos', $dados['limite_orcamentos']);
        if ($stmt->execute()) {
            $success = 'Plano cadastrado com sucesso!';
        } else {
            $error = 'Erro ao cadastrar plano.';
        }
    }
}

// Vincular plano a empresa
if ($_POST && isset($_POST['acao']) && $_POST['acao'] === 'vincular_plano') {
    $empresa_id = (int)$_POST['empresa_id'];
    $plano_id = (int)$_POST['plano_id'];
    if ($empresa_id && $plano_id) {
        // Buscar dados do plano
        $stmt = $conn->prepare('SELECT * FROM planos WHERE id = :id');
        $stmt->bindParam(':id', $plano_id);
        $stmt->execute();
        $plano = $stmt->fetch();
        if ($plano) {
            $query = "UPDATE empresas SET plano = :plano, limite_orcamentos = :limite_orcamentos WHERE id = :empresa_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':plano', $plano['nome']);
            $stmt->bindParam(':limite_orcamentos', $plano['limite_orcamentos']);
            $stmt->bindParam(':empresa_id', $empresa_id);
            if ($stmt->execute()) {
                $success = 'Plano vinculado à empresa com sucesso!';
            } else {
                $error = 'Erro ao vincular plano.';
            }
        } else {
            $error = 'Plano não encontrado.';
        }
    } else {
        $error = 'Selecione uma empresa e um plano.';
    }
}

// Editar plano
if (isset($_POST['acao']) && $_POST['acao'] === 'editar_plano' && isset($_POST['plano_id'])) {
    $plano_id = (int)$_POST['plano_id'];
    $dados = [
        'nome' => trim($_POST['nome']),
        'descricao' => trim($_POST['descricao']),
        'valor' => str_replace(',', '.', str_replace('.', '', $_POST['valor'])),
        'limite_orcamentos' => (int)$_POST['limite_orcamentos']
    ];
    if (empty($dados['nome']) || !is_numeric($dados['valor']) || $dados['valor'] < 0) {
        $error = 'Preencha o nome do plano e um valor válido.';
    } else {
        $query = "UPDATE planos SET nome = :nome, descricao = :descricao, valor = :valor, limite_orcamentos = :limite_orcamentos WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':nome', $dados['nome']);
        $stmt->bindParam(':descricao', $dados['descricao']);
        $stmt->bindParam(':valor', $dados['valor']);
        $stmt->bindParam(':limite_orcamentos', $dados['limite_orcamentos']);
        $stmt->bindParam(':id', $plano_id);
        if ($stmt->execute()) {
            $success = 'Plano editado com sucesso!';
        } else {
            $error = 'Erro ao editar plano.';
        }
    }
}
// Excluir plano
if (isset($_POST['acao']) && $_POST['acao'] === 'excluir_plano' && isset($_POST['plano_id'])) {
    $plano_id = (int)$_POST['plano_id'];
    // Verifica se alguma empresa está usando esse plano
    $stmt = $conn->prepare('SELECT COUNT(*) FROM empresas WHERE plano = (SELECT nome FROM planos WHERE id = :id)');
    $stmt->bindParam(':id', $plano_id);
    $stmt->execute();
    $usando = $stmt->fetchColumn();
    if ($usando > 0) {
        $error = 'Não é possível excluir: há empresas usando este plano.';
    } else {
        $stmt = $conn->prepare('DELETE FROM planos WHERE id = :id');
        $stmt->bindParam(':id', $plano_id);
        if ($stmt->execute()) {
            $success = 'Plano excluído com sucesso!';
        } else {
            $error = 'Erro ao excluir plano.';
        }
    }
}

// Alterar plano diretamente na tabela de empresas
if (isset($_POST['acao']) && $_POST['acao'] === 'alterar_plano_empresa' && isset($_POST['empresa_id'], $_POST['novo_plano_id'])) {
    $empresa_id = (int)$_POST['empresa_id'];
    $novo_plano_id = (int)$_POST['novo_plano_id'];
    // Buscar dados do plano
    $stmt = $conn->prepare('SELECT * FROM planos WHERE id = :id');
    $stmt->bindParam(':id', $novo_plano_id);
    $stmt->execute();
    $plano = $stmt->fetch();
    if ($plano) {
        $query = "UPDATE empresas SET plano = :plano, limite_orcamentos = :limite_orcamentos WHERE id = :empresa_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':plano', $plano['nome']);
        $stmt->bindParam(':limite_orcamentos', $plano['limite_orcamentos']);
        $stmt->bindParam(':empresa_id', $empresa_id);
        if ($stmt->execute()) {
            $success = 'Plano da empresa alterado com sucesso!';
        } else {
            $error = 'Erro ao alterar plano da empresa.';
        }
    } else {
        $error = 'Plano não encontrado.';
    }
}

// Listar planos
$stmt = $conn->query('SELECT * FROM planos ORDER BY valor ASC');
$planos = $stmt->fetchAll();
// Listar empresas
$stmt = $conn->query('SELECT id, razao_social, plano FROM empresas ORDER BY razao_social ASC');
$empresas = $stmt->fetchAll();

// Buscar plano para edição
$plano_editar = null;
if (isset($_GET['editar']) && is_numeric($_GET['editar'])) {
    $stmt = $conn->prepare('SELECT * FROM planos WHERE id = :id');
    $stmt->bindParam(':id', $_GET['editar']);
    $stmt->execute();
    $plano_editar = $stmt->fetch();
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planos e Pagamentos - Admin</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1><?php echo SITE_NAME; ?> - Admin</h1>
            <div class="user-info">
                <a href="../dashboard.php">← Dashboard</a>
            </div>
        </div>
    </div>
    <div class="container">
        <div class="breadcrumb">
            <a href="../dashboard.php">Dashboard</a> /
            <strong>Planos e Pagamentos</strong>
        </div>
        <div class="card">
            <div class="card-header">
                <h2>Cadastrar Novo Plano</h2>
                <p>Crie planos para empresas. Integração com Pagar.me pode ser feita futuramente.</p>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                <form method="POST">
                    <input type="hidden" name="acao" value="adicionar_plano">
                    <div class="section">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="nome">Nome do Plano <span class="required">*</span></label>
                                <input type="text" id="nome" name="nome" required>
                            </div>
                            <div class="form-group">
                                <label for="valor">Valor (R$) <span class="required">*</span></label>
                                <input type="text" id="valor" name="valor" required placeholder="0,00">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="limite_orcamentos">Limite de Orçamentos</label>
                                <input type="number" id="limite_orcamentos" name="limite_orcamentos" min="1" value="100">
                            </div>
                            <div class="form-group">
                                <label for="descricao">Descrição</label>
                                <textarea id="descricao" name="descricao"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">💾 Cadastrar Plano</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="card" style="margin-top:2rem;">
            <div class="card-header">
                <h2><?php echo $plano_editar ? 'Editar Plano' : 'Planos Cadastrados'; ?></h2>
            </div>
            <div class="card-body">
                <?php if ($plano_editar): ?>
                    <form method="POST">
                        <input type="hidden" name="acao" value="editar_plano">
                        <input type="hidden" name="plano_id" value="<?php echo $plano_editar['id']; ?>">
                        <div class="section">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="nome">Nome do Plano <span class="required">*</span></label>
                                    <input type="text" id="nome" name="nome" required value="<?php echo htmlspecialchars($plano_editar['nome']); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="valor">Valor (R$) <span class="required">*</span></label>
                                    <input type="text" id="valor" name="valor" required value="<?php echo number_format($plano_editar['valor'], 2, ',', '.'); ?>">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="limite_orcamentos">Limite de Orçamentos</label>
                                    <input type="number" id="limite_orcamentos" name="limite_orcamentos" min="1" value="<?php echo $plano_editar['limite_orcamentos']; ?>">
                                </div>
                                <div class="form-group">
                                    <label for="descricao">Descrição</label>
                                    <textarea id="descricao" name="descricao"><?php echo htmlspecialchars($plano_editar['descricao']); ?></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">💾 Salvar Alterações</button>
                            <a href="pagamentos.php" class="btn btn-secondary">Cancelar</a>
                        </div>
                    </form>
                <?php else: ?>
                    <table style="width:100%;border-collapse:collapse;">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Valor</th>
                                <th>Limite Orçamentos</th>
                                <th>Descrição</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($planos as $plano): ?>
                                <tr>
                                    <td><?php echo $plano['id']; ?></td>
                                    <td><?php echo htmlspecialchars($plano['nome']); ?></td>
                                    <td>R$ <?php echo number_format($plano['valor'], 2, ',', '.'); ?></td>
                                    <td><?php echo $plano['limite_orcamentos']; ?></td>
                                    <td><?php echo htmlspecialchars($plano['descricao']); ?></td>
                                    <td>
                                        <a href="pagamentos.php?editar=<?php echo $plano['id']; ?>" class="btn btn-sm btn-primary">✏️ Editar</a>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Tem certeza que deseja excluir este plano?');">
                                            <input type="hidden" name="acao" value="excluir_plano">
                                            <input type="hidden" name="plano_id" value="<?php echo $plano['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">🗑️ Excluir</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        <div class="card" style="margin-top:2rem;">
            <div class="card-header">
                <h2>Vincular Plano a Empresa</h2>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="acao" value="vincular_plano">
                    <div class="section">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="empresa_id">Empresa <span class="required">*</span></label>
                                <select id="empresa_id" name="empresa_id" required>
                                    <option value="">Selecione...</option>
                                    <?php foreach ($empresas as $emp): ?>
                                        <option value="<?php echo $emp['id']; ?>"><?php echo htmlspecialchars($emp['razao_social']); ?> (Plano atual: <?php echo htmlspecialchars($emp['plano']); ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="plano_id">Plano <span class="required">*</span></label>
                                <select id="plano_id" name="plano_id" required>
                                    <option value="">Selecione...</option>
                                    <?php foreach ($planos as $plano): ?>
                                        <option value="<?php echo $plano['id']; ?>"><?php echo htmlspecialchars($plano['nome']); ?> (R$ <?php echo number_format($plano['valor'], 2, ',', '.'); ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">💾 Vincular Plano</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="card" style="margin-top:2rem;">
            <div class="card-header">
                <h2>Empresas e Planos</h2>
            </div>
            <div class="card-body">
                <table style="width:100%;border-collapse:collapse;">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Empresa</th>
                            <th>Plano Atual</th>
                            <th>Alterar Plano</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($empresas as $emp): ?>
                            <tr>
                                <td><?php echo $emp['id']; ?></td>
                                <td><?php echo htmlspecialchars($emp['razao_social']); ?></td>
                                <td><?php echo htmlspecialchars($emp['plano']); ?></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="acao" value="alterar_plano_empresa">
                                        <input type="hidden" name="empresa_id" value="<?php echo $emp['id']; ?>">
                                        <select name="novo_plano_id" required>
                                            <option value="">Selecione...</option>
                                            <?php foreach ($planos as $plano): ?>
                                                <option value="<?php echo $plano['id']; ?>" <?php echo ($emp['plano'] == $plano['nome']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($plano['nome']); ?> (R$ <?php echo number_format($plano['valor'], 2, ',', '.'); ?>)</option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-primary">Alterar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>

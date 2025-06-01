<?php
// public/admin/editar_empresa.php
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

// Verificar se ID foi fornecido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: empresas.php');
    exit;
}
$empresa_id = (int)$_GET['id'];

// Buscar dados da empresa
$stmt = $conn->prepare('SELECT * FROM empresas WHERE id = :id');
$stmt->bindParam(':id', $empresa_id);
$stmt->execute();
$empresa = $stmt->fetch();
if (!$empresa) {
    header('Location: empresas.php');
    exit;
}

if ($_POST) {
    if (isset($_POST['acao']) && $_POST['acao'] === 'desativar') {
        $stmt = $conn->prepare('UPDATE empresas SET ativo = 0 WHERE id = :id');
        $stmt->bindParam(':id', $empresa_id);
        if ($stmt->execute()) {
            $success = 'Empresa desativada com sucesso!';
            $empresa['ativo'] = 0;
        } else {
            $error = 'Erro ao desativar empresa.';
        }
    } else {
        $dados = [
            'razao_social' => trim($_POST['razao_social']),
            'nome_fantasia' => trim($_POST['nome_fantasia']),
            'cnpj' => preg_replace('/[^0-9]/', '', $_POST['cnpj']),
            'email' => trim($_POST['email']),
            'telefone' => trim($_POST['telefone']),
            'endereco' => trim($_POST['endereco']),
            'cidade' => trim($_POST['cidade']),
            'estado' => trim($_POST['estado']),
            'cep' => preg_replace('/[^0-9]/', '', $_POST['cep'])
        ];
        if (empty($dados['razao_social']) || empty($dados['cnpj']) || empty($dados['email'])) {
            $error = 'Preencha todos os campos obrigatórios.';
        } else {
            $query = "UPDATE empresas SET razao_social = :razao_social, nome_fantasia = :nome_fantasia, cnpj = :cnpj, email = :email, telefone = :telefone, endereco = :endereco, cidade = :cidade, estado = :estado, cep = :cep WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':razao_social', $dados['razao_social']);
            $stmt->bindParam(':nome_fantasia', $dados['nome_fantasia']);
            $stmt->bindParam(':cnpj', $dados['cnpj']);
            $stmt->bindParam(':email', $dados['email']);
            $stmt->bindParam(':telefone', $dados['telefone']);
            $stmt->bindParam(':endereco', $dados['endereco']);
            $stmt->bindParam(':cidade', $dados['cidade']);
            $stmt->bindParam(':estado', $dados['estado']);
            $stmt->bindParam(':cep', $dados['cep']);
            $stmt->bindParam(':id', $empresa_id);
            if ($stmt->execute()) {
                $success = 'Dados da empresa atualizados com sucesso!';
                $empresa = array_merge($empresa, $dados);
            } else {
                $error = 'Erro ao atualizar dados da empresa.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Empresa - Admin</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1><?php echo SITE_NAME; ?> - Admin</h1>
            <div class="user-info">
                <a href="empresas.php">← Empresas</a>
            </div>
        </div>
    </div>
    <div class="container">
        <div class="breadcrumb">
            <a href="../dashboard.php">Dashboard</a> /
            <a href="empresas.php">Empresas</a> /
            <strong>Editar</strong>
        </div>
        <div class="card">
            <div class="card-header">
                <h2>Editar Empresa</h2>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                <form method="POST">
                    <div class="section">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="razao_social">Razão Social <span class="required">*</span></label>
                                <input type="text" id="razao_social" name="razao_social" required value="<?php echo htmlspecialchars($empresa['razao_social']); ?>">
                            </div>
                            <div class="form-group">
                                <label for="nome_fantasia">Nome Fantasia</label>
                                <input type="text" id="nome_fantasia" name="nome_fantasia" value="<?php echo htmlspecialchars($empresa['nome_fantasia']); ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="cnpj">CNPJ <span class="required">*</span></label>
                                <input type="text" id="cnpj" name="cnpj" required value="<?php echo htmlspecialchars($empresa['cnpj']); ?>">
                            </div>
                            <div class="form-group">
                                <label for="email">E-mail <span class="required">*</span></label>
                                <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($empresa['email']); ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="telefone">Telefone</label>
                                <input type="text" id="telefone" name="telefone" value="<?php echo htmlspecialchars($empresa['telefone']); ?>">
                            </div>
                            <div class="form-group">
                                <label for="cep">CEP</label>
                                <input type="text" id="cep" name="cep" value="<?php echo htmlspecialchars($empresa['cep']); ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="endereco">Endereço</label>
                                <input type="text" id="endereco" name="endereco" value="<?php echo htmlspecialchars($empresa['endereco']); ?>">
                            </div>
                            <div class="form-group">
                                <label for="cidade">Cidade</label>
                                <input type="text" id="cidade" name="cidade" value="<?php echo htmlspecialchars($empresa['cidade']); ?>">
                            </div>
                            <div class="form-group">
                                <label for="estado">Estado</label>
                                <input type="text" id="estado" name="estado" value="<?php echo htmlspecialchars($empresa['estado']); ?>">
                            </div>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">💾 Salvar Alterações</button>
                        <?php if (!empty($empresa['ativo']) || !isset($empresa['ativo'])): ?>
                            <button type="submit" name="acao" value="desativar" class="btn btn-danger" onclick="return confirm('Tem certeza que deseja desativar esta empresa?');">Desativar Empresa</button>
                        <?php endif; ?>
                        <a href="empresas.php" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>

<?php
// public/admin/empresas.php
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

// Adicionar empresa
if ($_POST && isset($_POST['acao']) && $_POST['acao'] === 'adicionar') {
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
        // Verifica se já existe CNPJ
        $stmt = $conn->prepare('SELECT id FROM empresas WHERE cnpj = :cnpj');
        $stmt->bindParam(':cnpj', $dados['cnpj']);
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            $error = 'CNPJ já cadastrado.';
        } else {
            $query = "INSERT INTO empresas (razao_social, nome_fantasia, cnpj, email, telefone, endereco, cidade, estado, cep, plano, limite_orcamentos, orcamentos_utilizados) VALUES (:razao_social, :nome_fantasia, :cnpj, :email, :telefone, :endereco, :cidade, :estado, :cep, 'premium', 99999, 0)";
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
            if ($stmt->execute()) {
                $success = 'Empresa cadastrada com sucesso!';
            } else {
                $error = 'Erro ao cadastrar empresa.';
            }
        }
    }
}

// Listar empresas
$stmt = $conn->query('SELECT * FROM empresas ORDER BY razao_social ASC');
$empresas = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Empresas - Admin</title>
    
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
            <strong>Empresas</strong>
        </div>
        <div class="card">
            <div class="card-header">
                <h2>Cadastrar Nova Empresa</h2>
                <p>Somente administradores podem cadastrar empresas</p>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                <form method="POST">
                    <input type="hidden" name="acao" value="adicionar">
                    <div class="section">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="razao_social">Razão Social <span class="required">*</span></label>
                                <input type="text" id="razao_social" name="razao_social" required>
                            </div>
                            <div class="form-group">
                                <label for="nome_fantasia">Nome Fantasia</label>
                                <input type="text" id="nome_fantasia" name="nome_fantasia">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="cnpj">CNPJ <span class="required">*</span></label>
                                <input type="text" id="cnpj" name="cnpj" required>
                            </div>
                            <div class="form-group">
                                <label for="email">E-mail <span class="required">*</span></label>
                                <input type="email" id="email" name="email" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="telefone">Telefone</label>
                                <input type="text" id="telefone" name="telefone">
                            </div>
                            <div class="form-group">
                                <label for="cep">CEP</label>
                                <input type="text" id="cep" name="cep">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="endereco">Endereço</label>
                                <input type="text" id="endereco" name="endereco">
                            </div>
                            <div class="form-group">
                                <label for="cidade">Cidade</label>
                                <input type="text" id="cidade" name="cidade">
                            </div>
                            <div class="form-group">
                                <label for="estado">Estado</label>
                                <input type="text" id="estado" name="estado">
                            </div>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">💾 Cadastrar Empresa</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="card" style="margin-top:2rem;">
            <div class="card-header">
                <h2>Empresas Cadastradas</h2>
            </div>
            <div class="card-body">
                <table style="width:100%;border-collapse:collapse;">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Razão Social</th>
                            <th>Nome Fantasia</th>
                            <th>CNPJ</th>
                            <th>E-mail</th>
                            <th>Telefone</th>
                            <th>Cidade</th>
                            <th>Estado</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($empresas as $emp): ?>
                            <tr>
                                <td><?php echo $emp['id']; ?></td>
                                <td><?php echo htmlspecialchars($emp['razao_social']); ?></td>
                                <td><?php echo htmlspecialchars($emp['nome_fantasia']); ?></td>
                                <td><?php echo htmlspecialchars($emp['cnpj']); ?></td>
                                <td><?php echo htmlspecialchars($emp['email']); ?></td>
                                <td><?php echo htmlspecialchars($emp['telefone']); ?></td>
                                <td><?php echo htmlspecialchars($emp['cidade']); ?></td>
                                <td><?php echo htmlspecialchars($emp['estado']); ?></td>
                                <td>
                                    <a href="editar_empresa.php?id=<?php echo $emp['id']; ?>" class="btn btn-sm btn-primary">✏️ Editar</a>
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

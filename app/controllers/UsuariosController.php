<?php

// Importa a conexão com o banco de dados.
require_once __DIR__ . '/../../config/database.php';

class UsuariosController
{
  // Armazena a conexão PDO.
  private PDO $pdo;

  public function __construct()
  {
    // Recupera a conexão criada em database.php.
    global $pdo;

    // Disponibiliza a conexão para os métodos da classe.
    $this->pdo = $pdo;
  }

  public function listar(): void
  {
    header('Content-Type: application/json; charset=utf-8');

    // Retorna todos os usuários ordenados do mais recente para o mais antigo.
    // A coluna senha é omitida intencionalmente — nunca trafegar hash pela API.
    $sql = 'SELECT id, nome, email, perfil, status, criado_em FROM usuarios ORDER BY id DESC';

    $stmt = $this->pdo->query($sql);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
  }

  public function buscarPorId(): void
  {
    header('Content-Type: application/json; charset=utf-8');

    // Valida o ID recebido via GET antes de consultar o banco.
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    if (!$id) {
      http_response_code(400);
      echo json_encode(['erro' => 'ID inválido.']);
      return;
    }

    // Consulta parametrizada evita SQL Injection.
    $sql = 'SELECT id, nome, email, perfil, status, criado_em FROM usuarios WHERE id = :id';
    $stmt = $this->pdo->prepare($sql);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
      http_response_code(404);
      echo json_encode(['erro' => 'Usuário não encontrado.']);
      return;
    }

    echo json_encode($usuario, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
  }

  public function criar(): void
  {
    header('Content-Type: application/json; charset=utf-8');

    // Coleta e sanitiza os dados enviados pelo formulário.
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $perfil = $_POST['perfil'] ?? 'atendente';
    $status = $_POST['status'] ?? 'ativo';

    // Verifica se os campos obrigatórios foram preenchidos.
    if ($nome === '' || $email === '' || $senha === '') {
      http_response_code(400);
      echo json_encode(['erro' => 'Nome, e-mail e senha são obrigatórios.']);
      return;
    }

    // Verifica se o e-mail possui formato válido.
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      http_response_code(400);
      echo json_encode(['erro' => 'E-mail inválido.']);
      return;
    }

    // Whitelist de valores válidos para o campo perfil.
    if (!in_array($perfil, ['admin', 'atendente', 'aluno'], true)) {
      http_response_code(400);
      echo json_encode(['erro' => 'Perfil inválido. Use: admin, atendente ou aluno.']);
      return;
    }

    // Whitelist de valores válidos para o campo status.
    if (!in_array($status, ['ativo', 'inativo'], true)) {
      http_response_code(400);
      echo json_encode(['erro' => 'Status inválido. Use: ativo ou inativo.']);
      return;
    }

    // Nunca armazenar senhas em texto puro — o hash protege mesmo em caso de vazamento do banco.
    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

    try {
      $sql = 'INSERT INTO usuarios (nome, email, senha, perfil, status) VALUES (:nome, :email, :senha, :perfil, :status)';
      $stmt = $this->pdo->prepare($sql);
      $stmt->bindValue(':nome', $nome);
      $stmt->bindValue(':email', $email);
      $stmt->bindValue(':senha', $senhaHash);
      $stmt->bindValue(':perfil', $perfil);
      $stmt->bindValue(':status', $status);
      $stmt->execute();

      http_response_code(201);
      echo json_encode(['sucesso' => 'Usuário criado com sucesso.', 'id' => $this->pdo->lastInsertId()], JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
      // Em produção, registre $e em log em vez de expor detalhes ao cliente.
      http_response_code(500);
      echo json_encode(['erro' => 'Erro ao cadastrar usuário.']);
    }
  }

  public function atualizar(): void
  {
    header('Content-Type: application/json; charset=utf-8');

    // ID vem no corpo do POST para operação de atualização.
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $perfil = $_POST['perfil'] ?? 'atendente';
    $status = $_POST['status'] ?? 'ativo';

    // Verifica se os campos obrigatórios foram preenchidos.
    if (!$id || $nome === '' || $email === '') {
      http_response_code(400);
      echo json_encode(['erro' => 'ID, nome e e-mail são obrigatórios.']);
      return;
    }

    // Verifica se o e-mail possui formato válido.
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      http_response_code(400);
      echo json_encode(['erro' => 'E-mail inválido.']);
      return;
    }

    // Whitelist de valores válidos para o campo perfil.
    if (!in_array($perfil, ['admin', 'atendente', 'aluno'], true)) {
      http_response_code(400);
      echo json_encode(['erro' => 'Perfil inválido. Use: admin, atendente ou aluno.']);
      return;
    }

    // Whitelist de valores válidos para o campo status.
    if (!in_array($status, ['ativo', 'inativo'], true)) {
      http_response_code(400);
      echo json_encode(['erro' => 'Status inválido. Use: ativo ou inativo.']);
      return;
    }

    try {
      // A senha não é alterada aqui — uma operação separada seria necessária para isso.
      $sql = 'UPDATE usuarios SET nome = :nome, email = :email, perfil = :perfil, status = :status WHERE id = :id';
      $stmt = $this->pdo->prepare($sql);
      $stmt->bindValue(':nome', $nome);
      $stmt->bindValue(':email', $email);
      $stmt->bindValue(':perfil', $perfil);
      $stmt->bindValue(':status', $status);
      $stmt->bindValue(':id', $id, PDO::PARAM_INT);
      $stmt->execute();

      echo json_encode(['mensagem' => 'Usuário atualizado com sucesso.'], JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
      http_response_code(500);
      echo json_encode(['erro' => 'Erro ao atualizar usuário.']);
    }
  }

  public function excluir(): void
  {
    header('Content-Type: application/json; charset=utf-8');

    // ID vem no corpo do POST para operação de exclusão.
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    if (!$id) {
      http_response_code(400);
      echo json_encode(['erro' => 'ID inválido.']);
      return;
    }

    try {
      $sql = 'DELETE FROM usuarios WHERE id = :id';
      $stmt = $this->pdo->prepare($sql);
      $stmt->bindValue(':id', $id, PDO::PARAM_INT);
      $stmt->execute();

      echo json_encode(['mensagem' => 'Usuário excluído com sucesso.'], JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
      http_response_code(500);
      echo json_encode(['erro' => 'Erro ao excluir usuário.']);
    }
  }
}

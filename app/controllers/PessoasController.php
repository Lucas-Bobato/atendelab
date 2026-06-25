<?php

// Importa a conexão com o banco de dados.
require_once __DIR__ . '/../../config/database.php';

class PessoasController
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

    // Retorna todas as pessoas ordenadas da mais recente para a mais antiga.
    $sql = 'SELECT id, nome, documento, email, telefone, curso, periodo, observacoes, status FROM pessoas ORDER BY id DESC';

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
    $sql = 'SELECT id, nome, documento, email, telefone, curso, periodo, observacoes, status FROM pessoas WHERE id = :id';
    $stmt = $this->pdo->prepare($sql);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    $pessoa = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pessoa) {
      http_response_code(404);
      echo json_encode(['erro' => 'Pessoa não encontrada.']);
      return;
    }

    echo json_encode($pessoa, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
  }

  public function criar(): void
  {
    header('Content-Type: application/json; charset=utf-8');

    // Coleta e sanitiza os dados enviados pelo formulário.
    $nome = trim($_POST['nome'] ?? '');
    $documento = trim($_POST['documento'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $curso = trim($_POST['curso'] ?? '');
    $periodo = trim($_POST['periodo'] ?? '');
    $observacoes = trim($_POST['observacoes'] ?? '');
    $status = trim($_POST['status'] ?? 'ativo');

    // Verifica se os campos obrigatórios foram preenchidos.
    if ($nome === '' || $documento === '' || $email === '') {
      http_response_code(400);
      echo json_encode(['erro' => 'Nome, documento e e-mail são obrigatórios.']);
      return;
    }

    // Verifica se o e-mail possui formato válido.
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      http_response_code(400);
      echo json_encode(['erro' => 'E-mail inválido.']);
      return;
    }

    // Whitelist de valores válidos para o campo status.
    if (!in_array($status, ['ativo', 'inativo'], true)) {
      http_response_code(400);
      echo json_encode(['erro' => 'Status inválido. Use: ativo ou inativo.']);
      return;
    }

    try {
      $sql = 'INSERT INTO pessoas (nome, documento, email, telefone, curso, periodo, observacoes, status) VALUES (:nome, :documento, :email, :telefone, :curso, :periodo, :observacoes, :status)';
      $stmt = $this->pdo->prepare($sql);
      $stmt->bindValue(':nome', $nome);
      $stmt->bindValue(':documento', $documento);
      $stmt->bindValue(':email', $email);

      // Campos opcionais são gravados como NULL quando vazios.
      $stmt->bindValue(':telefone', $telefone !== '' ? $telefone : null);
      $stmt->bindValue(':curso', $curso !== '' ? $curso : null);
      $stmt->bindValue(':periodo', $periodo !== '' ? $periodo : null);
      $stmt->bindValue(':observacoes', $observacoes !== '' ? $observacoes : null);
      $stmt->bindValue(':status', $status);
      $stmt->execute();

      http_response_code(201);
      echo json_encode(['sucesso' => 'Pessoa cadastrada com sucesso.', 'id' => $this->pdo->lastInsertId()], JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
      // Em produção, registre $e em log em vez de expor detalhes ao cliente.
      http_response_code(500);
      echo json_encode(['erro' => 'Erro ao cadastrar pessoa.']);
    }
  }

  public function atualizar(): void
  {
    header('Content-Type: application/json; charset=utf-8');

    // ID vem no corpo do POST para operação de atualização.
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $nome = trim($_POST['nome'] ?? '');
    $documento = trim($_POST['documento'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $curso = trim($_POST['curso'] ?? '');
    $periodo = trim($_POST['periodo'] ?? '');
    $observacoes = trim($_POST['observacoes'] ?? '');
    $status = trim($_POST['status'] ?? 'ativo');

    // Verifica se os campos obrigatórios foram preenchidos.
    if (!$id || $nome === '' || $documento === '' || $email === '') {
      http_response_code(400);
      echo json_encode(['erro' => 'ID, nome, documento e e-mail são obrigatórios.']);
      return;
    }

    // Verifica se o e-mail possui formato válido.
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      http_response_code(400);
      echo json_encode(['erro' => 'E-mail inválido.']);
      return;
    }

    // Whitelist de valores válidos para o campo status.
    if (!in_array($status, ['ativo', 'inativo'], true)) {
      http_response_code(400);
      echo json_encode(['erro' => 'Status inválido. Use: ativo ou inativo.']);
      return;
    }

    try {
      $sql = 'UPDATE pessoas SET nome = :nome, documento = :documento, email = :email, telefone = :telefone, curso = :curso, periodo = :periodo, observacoes = :observacoes, status = :status WHERE id = :id';
      $stmt = $this->pdo->prepare($sql);
      $stmt->bindValue(':nome', $nome);
      $stmt->bindValue(':documento', $documento);
      $stmt->bindValue(':email', $email);
      $stmt->bindValue(':telefone', $telefone !== '' ? $telefone : null);
      $stmt->bindValue(':curso', $curso !== '' ? $curso : null);
      $stmt->bindValue(':periodo', $periodo !== '' ? $periodo : null);
      $stmt->bindValue(':observacoes', $observacoes !== '' ? $observacoes : null);
      $stmt->bindValue(':status', $status);
      $stmt->bindValue(':id', $id, PDO::PARAM_INT);
      $stmt->execute();

      echo json_encode(['mensagem' => 'Pessoa atualizada com sucesso.'], JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
      http_response_code(500);
      echo json_encode(['erro' => 'Erro ao atualizar pessoa.']);
    }
  }

  public function inativar(): void
  {
    header('Content-Type: application/json; charset=utf-8');

    // ID vem no corpo do POST para operação de atualização.
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    if (!$id) {
      http_response_code(400);
      echo json_encode(['erro' => 'ID inválido.']);
      return;
    }

    try {
      // O registro permanece no banco; apenas o status muda para preservar o histórico.
      $sql = 'UPDATE pessoas SET status = :status WHERE id = :id';
      $stmt = $this->pdo->prepare($sql);
      $stmt->bindValue(':status', 'inativo');
      $stmt->bindValue(':id', $id, PDO::PARAM_INT);
      $stmt->execute();

      echo json_encode(['mensagem' => 'Pessoa inativada com sucesso.'], JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
      http_response_code(500);
      echo json_encode(['erro' => 'Erro ao inativar pessoa.']);
    }
  }

  public function ativar(): void
  {
    header('Content-Type: application/json; charset=utf-8');

    // ID vem no corpo do POST para operação de atualização.
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    if (!$id) {
      http_response_code(400);
      echo json_encode(['erro' => 'ID inválido.']);
      return;
    }

    try {
      $sql = 'UPDATE pessoas SET status = :status WHERE id = :id';
      $stmt = $this->pdo->prepare($sql);
      $stmt->bindValue(':status', 'ativo');
      $stmt->bindValue(':id', $id, PDO::PARAM_INT);
      $stmt->execute();

      echo json_encode(['mensagem' => 'Pessoa ativada com sucesso.'], JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
      http_response_code(500);
      echo json_encode(['erro' => 'Erro ao ativar pessoa.']);
    }
  }
}

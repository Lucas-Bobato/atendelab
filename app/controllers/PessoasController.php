<?php

class PessoasController
{
  private PDO $pdo;

  public function __construct()
  {
    require __DIR__ . '/../../config/database.php';
    $this->pdo = $pdo;
  }

  public function listar(): void
  {
    header('Content-Type: application/json; charset=utf-8');

    $sql = 'SELECT id, nome, documento, email, telefone, curso, periodo, observacoes, status FROM pessoas ORDER BY id DESC';

    $stmt = $this->pdo->query($sql);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
  }

  public function buscarPorId(): void
  {
    header('Content-Type: application/json; charset=utf-8');

    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    if (!$id) {
      http_response_code(400);
      echo json_encode(['erro' => 'ID inválido.']);
      return;
    }

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

    $nome = trim($_POST['nome'] ?? '');
    $documento = trim($_POST['documento'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $curso = trim($_POST['curso'] ?? '');
    $periodo = trim($_POST['periodo'] ?? '');
    $observacoes = trim($_POST['observacoes'] ?? '');
    $status = trim($_POST['status'] ?? 'ativo');

    if ($nome === '' || $documento === '' || $email === '') {
      http_response_code(400);
      echo json_encode(['erro' => 'Nome, documento e e-mail são obrigatórios.']);
      return;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      http_response_code(400);
      echo json_encode(['erro' => 'E-mail inválido.']);
      return;
    }

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
      $stmt->bindValue(':telefone', $telefone !== '' ? $telefone : null);
      $stmt->bindValue(':curso', $curso !== '' ? $curso : null);
      $stmt->bindValue(':periodo', $periodo !== '' ? $periodo : null);
      $stmt->bindValue(':observacoes', $observacoes !== '' ? $observacoes : null);
      $stmt->bindValue(':status', $status);
      $stmt->execute();

      http_response_code(201);
      echo json_encode(['sucesso' => 'Pessoa cadastrada com sucesso.', 'id' => $this->pdo->lastInsertId()], JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
      http_response_code(500);
      echo json_encode(['erro' => 'Erro ao cadastrar pessoa.']);
    }
  }

  public function atualizar(): void
  {
    header('Content-Type: application/json; charset=utf-8');

    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $nome = trim($_POST['nome'] ?? '');
    $documento = trim($_POST['documento'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $curso = trim($_POST['curso'] ?? '');
    $periodo = trim($_POST['periodo'] ?? '');
    $observacoes = trim($_POST['observacoes'] ?? '');
    $status = trim($_POST['status'] ?? 'ativo');

    if (!$id || $nome === '' || $documento === '' || $email === '') {
      http_response_code(400);
      echo json_encode(['erro' => 'ID, nome, documento e e-mail são obrigatórios.']);
      return;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      http_response_code(400);
      echo json_encode(['erro' => 'E-mail inválido.']);
      return;
    }

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

  public function excluir(): void
  {
    header('Content-Type: application/json; charset=utf-8');

    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    if (!$id) {
      http_response_code(400);
      echo json_encode(['erro' => 'ID inválido.']);
      return;
    }

    try {
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
}
<?php

class TiposAtendimentosController
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

    $sql = 'SELECT id, nome, descricao, status FROM tipos_atendimentos ORDER BY id DESC';
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

    $sql = 'SELECT id, nome, descricao, status FROM tipos_atendimentos WHERE id = :id';
    $stmt = $this->pdo->prepare($sql);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    $tipoAtendimento = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tipoAtendimento) {
      http_response_code(404);
      echo json_encode(['erro' => 'Tipo de atendimento não encontrado.']);
      return;
    }

    echo json_encode($tipoAtendimento, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
  }

  public function criar(): void
  {
    header('Content-Type: application/json; charset=utf-8');

    $nome = trim($_POST['nome'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $status = trim($_POST['status'] ?? 'ativo');

    if ($nome === '') {
      http_response_code(400);
      echo json_encode(['erro' => 'Nome é obrigatório.']);
      return;
    }

    if (!in_array($status, ['ativo', 'inativo'], true)) {
      http_response_code(400);
      echo json_encode(['erro' => 'Status inválido. Use: ativo ou inativo.']);
      return;
    }

    try {
      $sql = 'INSERT INTO tipos_atendimentos (nome, descricao, status) VALUES (:nome, :descricao, :status)';
      $stmt = $this->pdo->prepare($sql);
      $stmt->bindValue(':nome', $nome);
      $stmt->bindValue(':descricao', $descricao !== '' ? $descricao : null);
      $stmt->bindValue(':status', $status);
      $stmt->execute();

      http_response_code(201);
      echo json_encode(['sucesso' => 'Tipo de atendimento cadastrado com sucesso.', 'id' => $this->pdo->lastInsertId()], JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
      http_response_code(500);
      echo json_encode(['erro' => 'Erro ao cadastrar tipo de atendimento.']);
    }
  }

  public function atualizar(): void
  {
    header('Content-Type: application/json; charset=utf-8');

    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $nome = trim($_POST['nome'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $status = trim($_POST['status'] ?? 'ativo');

    if (!$id || $nome === '') {
      http_response_code(400);
      echo json_encode(['erro' => 'ID e nome são obrigatórios.']);
      return;
    }

    if (!in_array($status, ['ativo', 'inativo'], true)) {
      http_response_code(400);
      echo json_encode(['erro' => 'Status inválido. Use: ativo ou inativo.']);
      return;
    }

    try {
      $sql = 'UPDATE tipos_atendimentos SET nome = :nome, descricao = :descricao, status = :status WHERE id = :id';
      $stmt = $this->pdo->prepare($sql);
      $stmt->bindValue(':nome', $nome);
      $stmt->bindValue(':descricao', $descricao !== '' ? $descricao : null);
      $stmt->bindValue(':status', $status);
      $stmt->bindValue(':id', $id, PDO::PARAM_INT);
      $stmt->execute();

      echo json_encode(['mensagem' => 'Tipo de atendimento atualizado com sucesso.'], JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
      http_response_code(500);
      echo json_encode(['erro' => 'Erro ao atualizar tipo de atendimento.']);
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
      $sql = 'UPDATE tipos_atendimentos SET status = :status WHERE id = :id';
      $stmt = $this->pdo->prepare($sql);
      $stmt->bindValue(':status', 'inativo');
      $stmt->bindValue(':id', $id, PDO::PARAM_INT);
      $stmt->execute();

      echo json_encode(['mensagem' => 'Tipo de atendimento inativado com sucesso.'], JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
      http_response_code(500);
      echo json_encode(['erro' => 'Erro ao cancelar tipo de atendimento.']);
    }
  }
}
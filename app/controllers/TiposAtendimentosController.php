<?php

// Importa a conexão com o banco de dados.
require_once __DIR__ . '/../../config/database.php';

class TiposAtendimentosController
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

    // Retorna todos os tipos ordenados do mais recente para o mais antigo.
    $sql = 'SELECT id, nome, descricao, status FROM tipos_atendimentos ORDER BY id DESC';
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

    // Coleta e sanitiza os dados enviados pelo formulário.
    $nome = trim($_POST['nome'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $status = trim($_POST['status'] ?? 'ativo');

    // Verifica se o campo obrigatório foi preenchido.
    if ($nome === '') {
      http_response_code(400);
      echo json_encode(['erro' => 'Nome é obrigatório.']);
      return;
    }

    // Whitelist de valores válidos para o campo status.
    if (!in_array($status, ['ativo', 'inativo'], true)) {
      http_response_code(400);
      echo json_encode(['erro' => 'Status inválido. Use: ativo ou inativo.']);
      return;
    }

    try {
      $sql = 'INSERT INTO tipos_atendimentos (nome, descricao, status) VALUES (:nome, :descricao, :status)';
      $stmt = $this->pdo->prepare($sql);
      $stmt->bindValue(':nome', $nome);

      // Descrição é opcional; gravada como NULL quando vazia.
      $stmt->bindValue(':descricao', $descricao !== '' ? $descricao : null);
      $stmt->bindValue(':status', $status);
      $stmt->execute();

      http_response_code(201);
      echo json_encode(['sucesso' => 'Tipo de atendimento cadastrado com sucesso.', 'id' => $this->pdo->lastInsertId()], JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
      // Em produção, registre $e em log em vez de expor detalhes ao cliente.
      http_response_code(500);
      echo json_encode(['erro' => 'Erro ao cadastrar tipo de atendimento.']);
    }
  }

  public function atualizar(): void
  {
    header('Content-Type: application/json; charset=utf-8');

    // ID vem no corpo do POST para operação de atualização.
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $nome = trim($_POST['nome'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $status = trim($_POST['status'] ?? 'ativo');

    // Verifica se os campos obrigatórios foram preenchidos.
    if (!$id || $nome === '') {
      http_response_code(400);
      echo json_encode(['erro' => 'ID e nome são obrigatórios.']);
      return;
    }

    // Whitelist de valores válidos para o campo status.
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
      $sql = 'UPDATE tipos_atendimentos SET status = :status WHERE id = :id';
      $stmt = $this->pdo->prepare($sql);
      $stmt->bindValue(':status', 'inativo');
      $stmt->bindValue(':id', $id, PDO::PARAM_INT);
      $stmt->execute();

      echo json_encode(['mensagem' => 'Tipo de atendimento inativado com sucesso.'], JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
      http_response_code(500);
      echo json_encode(['erro' => 'Erro ao inativar tipo de atendimento.']);
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
      $sql = 'UPDATE tipos_atendimentos SET status = :status WHERE id = :id';
      $stmt = $this->pdo->prepare($sql);
      $stmt->bindValue(':status', 'ativo');
      $stmt->bindValue(':id', $id, PDO::PARAM_INT);
      $stmt->execute();

      echo json_encode(['mensagem' => 'Tipo de atendimento ativado com sucesso.'], JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
      http_response_code(500);
      echo json_encode(['erro' => 'Erro ao ativar tipo de atendimento.']);
    }
  }
}

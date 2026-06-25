<?php

// Importa a conexão com o banco de dados.
require_once __DIR__ . '/../../config/database.php';

class AtendimentosController
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

    // JOIN com pessoas, tipos e usuários para retornar nomes legíveis em vez de IDs.
    $sql = 'SELECT a.id, a.pessoa_id, p.nome AS pessoa_nome, a.tipo_atendimento_id, ta.nome AS tipo_atendimento_nome, a.usuario_id, u.nome AS usuario_nome, a.data_atendimento, a.horario_atendimento, a.descricao, a.observacao_final, a.status, a.criado_em FROM atendimentos a INNER JOIN pessoas p ON p.id = a.pessoa_id INNER JOIN tipos_atendimentos ta ON ta.id = a.tipo_atendimento_id INNER JOIN usuarios u ON u.id = a.usuario_id ORDER BY a.id DESC';
    $stmt = $this->pdo->query($sql);

    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
  }

  public function visualizar(): void
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
    $sql = 'SELECT a.id, a.pessoa_id, p.nome AS pessoa_nome, a.tipo_atendimento_id, ta.nome AS tipo_atendimento_nome, a.usuario_id, u.nome AS usuario_nome, a.data_atendimento, a.horario_atendimento, a.descricao, a.observacao_final, a.status, a.criado_em FROM atendimentos a INNER JOIN pessoas p ON p.id = a.pessoa_id INNER JOIN tipos_atendimentos ta ON ta.id = a.tipo_atendimento_id INNER JOIN usuarios u ON u.id = a.usuario_id WHERE a.id = :id';
    $stmt = $this->pdo->prepare($sql);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    $atendimento = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$atendimento) {
      http_response_code(404);
      echo json_encode(['erro' => 'Atendimento não encontrado.']);
      return;
    }

    echo json_encode($atendimento, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
  }

  public function criar(): void
  {
    header('Content-Type: application/json; charset=utf-8');

    // Coleta e valida os dados enviados pelo formulário.
    $pessoaId = filter_input(INPUT_POST, 'pessoa_id', FILTER_VALIDATE_INT);
    $tipoAtendimentoId = filter_input(INPUT_POST, 'tipo_atendimento_id', FILTER_VALIDATE_INT);

    // O usuário responsável vem da sessão, não do formulário, para evitar adulteração.
    $usuarioId = (int) ($_SESSION['usuario']['id'] ?? 0);
    $dataAtendimento = trim($_POST['data_atendimento'] ?? '');
    $horarioAtendimento = trim($_POST['horario_atendimento'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $observacaoFinal = trim($_POST['observacao_final'] ?? '');
    $status = trim($_POST['status'] ?? 'Aguardando');

    // Verifica se os campos obrigatórios foram preenchidos.
    if (!$pessoaId || !$tipoAtendimentoId || !$usuarioId || $dataAtendimento === '' || $horarioAtendimento === '' || $descricao === '') {
      http_response_code(400);
      echo json_encode(['erro' => 'Pessoa, tipo de atendimento, usuário autenticado, data, horário e descrição são obrigatórios.']);
      return;
    }

    // Valida o formato da data (YYYY-MM-DD).
    $dataValidada = \DateTime::createFromFormat('Y-m-d', $dataAtendimento);
    if (!$dataValidada || $dataValidada->format('Y-m-d') !== $dataAtendimento) {
      http_response_code(400);
      echo json_encode(['erro' => 'Data de atendimento inválida.']);
      return;
    }

    // Aceita horário com ou sem segundos (HH:MM:SS ou HH:MM).
    $horaValidada = \DateTime::createFromFormat('H:i:s', $horarioAtendimento) ?: \DateTime::createFromFormat('H:i', $horarioAtendimento);
    if (!$horaValidada) {
      http_response_code(400);
      echo json_encode(['erro' => 'Horário de atendimento inválido.']);
      return;
    }

    // Whitelist de valores válidos para o campo status.
    if (!in_array($status, ['Aguardando', 'Em Atendimento', 'Cancelado', 'Atendido'], true)) {
      http_response_code(400);
      echo json_encode(['erro' => 'Status inválido. Use: Aguardando, Em Atendimento, Cancelado ou Atendido.']);
      return;
    }

    try {
      $sql = 'INSERT INTO atendimentos (pessoa_id, tipo_atendimento_id, usuario_id, data_atendimento, horario_atendimento, descricao, observacao_final, status) VALUES (:pessoa_id, :tipo_atendimento_id, :usuario_id, :data_atendimento, :horario_atendimento, :descricao, :observacao_final, :status)';
      $stmt = $this->pdo->prepare($sql);
      $stmt->bindValue(':pessoa_id', $pessoaId, PDO::PARAM_INT);
      $stmt->bindValue(':tipo_atendimento_id', $tipoAtendimentoId, PDO::PARAM_INT);
      $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
      $stmt->bindValue(':data_atendimento', $dataAtendimento);
      $stmt->bindValue(':horario_atendimento', $horarioAtendimento);
      $stmt->bindValue(':descricao', $descricao);

      // Observação final é opcional no cadastro; obrigatória apenas ao concluir.
      $stmt->bindValue(':observacao_final', $observacaoFinal !== '' ? $observacaoFinal : null);
      $stmt->bindValue(':status', $status);
      $stmt->execute();

      http_response_code(201);
      echo json_encode(['sucesso' => 'Atendimento criado com sucesso.', 'id' => $this->pdo->lastInsertId()], JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
      // Em produção, registre $e em log em vez de expor detalhes ao cliente.
      http_response_code(500);
      echo json_encode(['erro' => 'Erro ao criar atendimento.']);
    }
  }

  public function atualizarStatus(): void
  {
    header('Content-Type: application/json; charset=utf-8');

    // ID e status vêm no corpo do POST para operação de atualização.
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $status = trim($_POST['status'] ?? '');
    $observacaoFinal = trim($_POST['observacao_final'] ?? '');

    // Verifica se os campos obrigatórios foram preenchidos.
    if (!$id || $status === '') {
      http_response_code(400);
      echo json_encode(['erro' => 'ID e status são obrigatórios.']);
      return;
    }

    // Whitelist de valores válidos para o campo status.
    if (!in_array($status, ['Aguardando', 'Em Atendimento', 'Cancelado', 'Atendido'], true)) {
      http_response_code(400);
      echo json_encode(['erro' => 'Status inválido. Use: Aguardando, Em Atendimento, Cancelado ou Atendido.']);
      return;
    }

    // Observação final é obrigatória ao concluir — validação no backend evita burlar o required do HTML.
    if ($status === 'Atendido' && $observacaoFinal === '') {
      http_response_code(400);
      echo json_encode(['erro' => 'A observação final é obrigatória ao concluir o atendimento.']);
      return;
    }

    try {
      // COALESCE mantém a observação anterior caso nenhuma nova seja enviada.
      $sql = 'UPDATE atendimentos SET status = :status, observacao_final = COALESCE(NULLIF(:observacao_final, ""), observacao_final) WHERE id = :id';
      $stmt = $this->pdo->prepare($sql);
      $stmt->bindValue(':status', $status);
      $stmt->bindValue(':observacao_final', $observacaoFinal !== '' ? $observacaoFinal : null);
      $stmt->bindValue(':id', $id, PDO::PARAM_INT);
      $stmt->execute();

      echo json_encode(['mensagem' => 'Status do atendimento atualizado com sucesso.'], JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
      http_response_code(500);
      echo json_encode(['erro' => 'Erro ao atualizar status do atendimento.']);
    }
  }
}

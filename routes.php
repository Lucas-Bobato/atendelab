<?php
// Carrega o controller responsável pelos endpoints de usuários.

require_once __DIR__ . '/app/controllers/UsuariosController.php';
require_once __DIR__ . '/app/controllers/PessoasController.php';
require_once __DIR__ . '/app/controllers/TiposAtendimentosController.php';
require_once __DIR__ . '/app/controllers/AtendimentosController.php';

// Define controller e action por query string.
// Exemplo: ?controller=usuario&action=listar
$controller = $_GET['controller'] ?? 'home';
$action = $_GET['action'] ?? 'index';

switch ($controller) {
  case 'usuarios':
    $usuariosController = new UsuariosController();

    switch ($action) {
      case 'listar':
        $usuariosController->listar();
        break;
      case 'buscar':
      case 'buscarPorId':
        $usuariosController->buscarPorId();
        break;
      case 'criar':
        $usuariosController->criar();
        break;
      case 'atualizar':
        $usuariosController->atualizar();
        break;
      case 'excluir':
        $usuariosController->excluir();
        break;
      default:
        echo 'Ação de usuários não encontrada.';
        break;
    }
    break;

  case 'pessoas':
    $pessoasController = new PessoasController();

    switch ($action) {
      case 'listar':
        $pessoasController->listar();
        break;
      case 'buscar':
      case 'buscarPorId':
        $pessoasController->buscarPorId();
        break;
      case 'criar':
        $pessoasController->criar();
        break;
      case 'atualizar':
        $pessoasController->atualizar();
        break;
      case 'excluir':
      case 'inativar':
        $pessoasController->excluir();
        break;
      default:
        echo 'Ação de pessoas não encontrada.';
        break;
    }
    break;

  case 'tipos_atendimentos':
    $tiposAtendimentosController = new TiposAtendimentosController();

    switch ($action) {
      case 'listar':
        $tiposAtendimentosController->listar();
        break;
      case 'buscar':
      case 'buscarPorId':
        $tiposAtendimentosController->buscarPorId();
        break;
      case 'criar':
        $tiposAtendimentosController->criar();
        break;
      case 'atualizar':
        $tiposAtendimentosController->atualizar();
        break;
      case 'excluir':
      case 'inativar':
        $tiposAtendimentosController->excluir();
        break;
      default:
        echo 'Ação de tipos de atendimentos não encontrada.';
        break;
    }
    break;

  case 'atendimentos':
    $atendimentosController = new AtendimentosController();

    switch ($action) {
      case 'listar':
        $atendimentosController->listar();
        break;
      case 'visualizar':
      case 'buscar':
      case 'buscarPorId':
        $atendimentosController->visualizar();
        break;
      case 'criar':
        $atendimentosController->criar();
        break;
      case 'atualizarStatus':
      case 'atualizar':
        $atendimentosController->atualizarStatus();
        break;
      default:
        echo 'Ação de atendimentos não encontrada.';
        break;
    }
    break;

  default:
    echo '<h1>AtendeLab</h1>';
    echo '<p>Projeto em execução. Use ?controller=usuarios&action=listar, ?controller=pessoas&action=listar, ?controller=tipos_atendimentos&action=listar ou ?controller=atendimentos&action=listar.</p>';
    break;
}
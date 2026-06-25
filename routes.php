<?php

require_once __DIR__ . '/app/controllers/AuthController.php';
require_once __DIR__ . '/app/controllers/DashboardController.php';
require_once __DIR__ . '/app/controllers/FrontendController.php';
require_once __DIR__ . '/app/controllers/UsuariosController.php';
require_once __DIR__ . '/app/controllers/PessoasController.php';
require_once __DIR__ . '/app/controllers/TiposAtendimentosController.php';
require_once __DIR__ . '/app/controllers/AtendimentosController.php';
require_once __DIR__ . '/app/controllers/RelatoriosController.php';
require_once __DIR__ . '/app/middleware/auth.php';

$controller = $_GET['controller'] ?? 'auth';
$action = $_GET['action'] ?? 'login';

switch ($controller) {
  case 'auth':
    $authController = new AuthController();

    switch ($action) {
      case 'login':
        $authController->exibirlogin();
        break;

      case 'entrar':
        $authController->entrar();
        break;

      case 'dashboard':
        exigirAutenticacao();
        $authController->dashboard();
        break;

      case 'logout':
        $authController->logout();
        break;

      default:
        http_response_code(404);
        echo 'Ação de autenticação não encontrada.';
    }
    break;

  case 'dashboard':
    exigirAutenticacao();
    $dashboardController = new DashboardController();
    switch ($action) {
      case 'resumo':
        $dashboardController->resumo();
        break;
      default:
        http_response_code(404);
        echo 'Ação de dashboard não encontrada.';
    }
    break;

  case 'frontend':
    exigirAutenticacao();
    $frontendController = new FrontendController();
    switch ($action) {
      case 'pessoas':
        $frontendController->pessoas();
        break;
      case 'tipos':
        $frontendController->tipos();
        break;
      case 'atendimentos':
        $frontendController->atendimentos();
        break;
      default:
        http_response_code(404);
        echo 'Página não encontrada.';
    }
    break;

  case 'usuarios':
    exigirAutenticacao();
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
        http_response_code(404);
        echo 'Ação de usuários não encontrada.';
    }
    break;

  case 'pessoas':
    exigirAutenticacao();
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
      case 'inativar':
        $pessoasController->inativar();
        break;
      case 'ativar':
        $pessoasController->ativar();
        break;
      default:
        http_response_code(404);
        echo 'Ação de pessoas não encontrada.';
    }
    break;

  case 'tipos':
  case 'tipos_atendimentos':
    exigirAutenticacao();
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
      case 'inativar':
        $tiposAtendimentosController->inativar();
        break;
      case 'ativar':
        $tiposAtendimentosController->ativar();
        break;
      default:
        http_response_code(404);
        echo 'Ação de tipos de atendimentos não encontrada.';
    }
    break;

  case 'atendimentos':
    exigirAutenticacao();
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
      case 'alterarStatus':
      case 'atualizarStatus':
      case 'atualizar':
        $atendimentosController->atualizarStatus();
        break;
      default:
        http_response_code(404);
        echo 'Ação de atendimentos não encontrada.';
    }
    break;

  case 'relatorios':
    exigirAutenticacao();
    $relatoriosController = new RelatoriosController();

    switch ($action) {
      case 'atendimentos':
        $relatoriosController->atendimentos();
        break;
      default:
        http_response_code(404);
        echo 'Ação de relatórios não encontrada.';
    }
    break;

  default:
    http_response_code(404);
    echo 'Controller não encontrado.';
}

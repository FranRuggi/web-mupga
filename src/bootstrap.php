<?php
/**
 * Bootstrap del sitio MuPGA.
 * Carga el .env, la conexión y todos los repositorios.
 * Incluir desde public/index.php o cualquier punto de entrada.
 */

define('SRC_ROOT', __DIR__);
define('PROJECT_ROOT', dirname(__DIR__));

require_once SRC_ROOT . '/config/env.php';
loadEnv(PROJECT_ROOT . '/.env');

require_once SRC_ROOT . '/config/database.php';

require_once SRC_ROOT . '/db/AccountRepository.php';
require_once SRC_ROOT . '/db/CharacterRepository.php';
require_once SRC_ROOT . '/db/RankingsRepository.php';
require_once SRC_ROOT . '/db/CreditsRepository.php';

require_once SRC_ROOT . '/lib/TokenService.php';
require_once SRC_ROOT . '/lib/Auth.php';

<?php
/**
 * WebEngine CMS
 * https://webenginecms.org/
 * 
 * @version 1.2.6
 * @author Lautaro Angelico <http://lautaroangelico.com/>
 * @copyright (c) 2013-2025 Lautaro Angelico, All Rights Reserved
 * 
 * Licensed under the MIT license
 * http://opensource.org/licenses/MIT
 */

if(!defined('access') or !access) die();
include('inc/template.functions.php');

$serverInfoCache = LoadCacheData('server_info.cache');
if(is_array($serverInfoCache)) {
	$srvInfo = explode("|", $serverInfoCache[1][0]);
}

$maxOnline = config('maximum_online', true);
$onlinePlayers = isset($srvInfo[3]) ? $srvInfo[3] : 0;
$onlinePlayersPercent = check_value($maxOnline) ? $onlinePlayers*100/$maxOnline : 0;

// Processar URL amigável para definir page
if(!isset($_REQUEST['page']) || empty($_REQUEST['page'])) {
	// Verificar se há uma URL amigável (ex: /privacy, /tos)
	$requestUri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
	$requestUri = parse_url($requestUri, PHP_URL_PATH);
	$requestUri = trim($requestUri, '/');
	
	// Se a URI não estiver vazia e não for apenas a raiz
	if(!empty($requestUri) && $requestUri != 'index.php') {
		// Remover query string se houver
		$pathParts = explode('?', $requestUri);
		$cleanPath = $pathParts[0];
		
		// Dividir o caminho em partes
		$pathSegments = explode('/', $cleanPath);
		
		// O primeiro segmento é a página
		if(!empty($pathSegments[0])) {
			$_REQUEST['page'] = $pathSegments[0];
		}
		
		// O segundo segmento é a subpágina (se houver)
		if(isset($pathSegments[1]) && !empty($pathSegments[1])) {
			$_REQUEST['subpage'] = $pathSegments[1];
		}
	}
	
	// Se ainda não foi definido, usar vazio
	if(!isset($_REQUEST['page'])) {
		$_REQUEST['page'] = '';
	}
}

if(!isset($_REQUEST['subpage'])) {
	$_REQUEST['subpage'] = '';
}

// Si page está vacío, lo tratamos como "home"
$tplPage = $_REQUEST['page'] == '' ? 'home' : $_REQUEST['page'];

if($tplPage == 'home') {
    include_once(__PATH_TEMPLATE_ROOT__ . 'index.home.php');
} else {
    include_once(__PATH_TEMPLATE_ROOT__ . 'index.default.php');
}

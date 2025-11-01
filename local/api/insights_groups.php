<?php
// /local/api/insights_groups.php
// CRUD групп инсайтов. Простая апишка без CSRF. Только для авторизованного пользователя.

define('NO_KEEP_STATISTIC', true);
define('NO_AGENT_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('DisableEventsCheck', true);

require $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php';

// --- гарантируем "чистый" JSON-вывод ---
if (function_exists('ob_get_level')) {
    while (ob_get_level() > 0) { ob_end_clean(); }
}
header('Content-Type: application/json; charset=UTF-8');

use Bitrix\Main\Context;
use Local\Insights\Repository;

function out($data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

global $USER;
if (!$USER || !$USER->IsAuthorized()) {
    out(['ok' => false, 'error' => 'unauthorized'], 401);
}

$uid = (int)$USER->GetID();
$req = Context::getCurrent()->getRequest();
$act = (string)$req->get('a');

try {
    switch ($act) {
        // GET список групп владельца
        case 'list': {
            $items = Repository::groupsListByOwner($uid);
            out(['ok' => true, 'items' => $items]);
        }

        // POST создать группу: name (req), color(opt '#888888'), sort(opt 500)
        case 'create': {
            $name  = trim((string)$req->getPost('name'));
            if ($name === '') out(['ok'=>false,'error'=>'name required'], 400);

            $color = trim((string)($req->getPost('color') ?: '#888888'));
            $sort  = (int)($req->getPost('sort') ?: 500);

            $id = Repository::groupCreate($uid, $name, $color, $sort);
            out(['ok' => true, 'id' => $id]);
        }

        // POST обновить группу владельца: id, fields[UF_NAME|UF_COLOR|UF_SORT]
        case 'update': {
            $id = (int)$req->getPost('id');
            if ($id <= 0) out(['ok'=>false,'error'=>'bad id'], 400);

            $fields = (array)$req->getPost('fields');
            Repository::groupUpdateOwned($id, $uid, $fields);
            out(['ok' => true]);
        }

        // POST удалить группу владельца: id (каскадно снимет группу у всех инсайтов владельца)
        case 'delete': {
            $id = (int)$req->getPost('id');
            if ($id <= 0) out(['ok'=>false,'error'=>'bad id'], 400);

            Repository::groupDeleteOwned($id, $uid, /*cascadeUnlink*/ true);
            out(['ok' => true]);
        }

        default:
            out(['ok' => false, 'error' => 'unknown action'], 400);
    }
} catch (\Throwable $e) {
    // На отладке можно вернуть подробности:
    // out(['ok'=>false,'error'=>$e->getMessage(),'file'=>$e->getFile(),'line'=>$e->getLine()], 500);
    out(['ok' => false, 'error' => $e->getMessage()], 500);
}

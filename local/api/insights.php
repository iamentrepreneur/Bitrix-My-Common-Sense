<?php
// /local/api/insights.php — простая апишка без CSRF/фильтров.
// Доступен только авторизованному пользователю (битрикс-кука).

const NO_KEEP_STATISTIC = true;
const NO_AGENT_STATISTIC = true;
const NOT_CHECK_PERMISSIONS = true;
const DisableEventsCheck = true;

require $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Context;
use Bitrix\Main\SystemException;
use Local\Insights\Repository;

header('Content-Type: application/json; charset=UTF-8');

function out($data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

global $USER;
if (!$USER || !$USER->IsAuthorized()) {
    out(['ok'=>false, 'error'=>'unauthorized'], 401);
}

$uid = (int)$USER->GetID();
$req = Context::getCurrent()->getRequest();
$action = (string)$req->get('a');

try {
    switch ($action) {
        /* === список инсайтов === */
        case 'list': {
            $page = max(1, (int)$req->get('page'));
            $pageSize = min(100, max(1, (int)($req->get('pageSize') ?: 20)));
            $q = trim((string)$req->get('q'));
            $groupId = ($req->get('groupId') !== null && $req->get('groupId') !== '')
                ? (int)$req->get('groupId')
                : null;

            $data = Repository::listByOwner($uid, $page, $pageSize, $q, $groupId);
            out(['ok'=>true] + $data);
        }

        /* === создать инсайт === */
        case 'create': {
            $text  = trim((string)$req->getPost('text'));
            $title = trim((string)$req->getPost('title'));
            $tags  = trim((string)$req->getPost('tags'));
            $groupId = ($req->getPost('groupId') !== null && $req->getPost('groupId') !== '')
                ? (int)$req->getPost('groupId')
                : null;

            if ($text === '') {
                out(['ok'=>false,'error'=>'text is required'], 400);
            }

            $id = Repository::create($uid, $text, $title, $tags, $groupId);
            out(['ok'=>true, 'id'=>$id]);
        }

        /* === обновить === */
        case 'update': {
            $id = (int)$req->getPost('id');
            if ($id <= 0) out(['ok'=>false,'error'=>'bad id'], 400);

            $fields = (array)$req->getPost('fields');
            Repository::updateOwned($id, $uid, $fields);
            out(['ok'=>true]);
        }

        /* === удалить === */
        case 'delete': {
            $id = (int)$req->getPost('id');
            if ($id <= 0) out(['ok'=>false,'error'=>'bad id'], 400);

            Repository::deleteOwned($id, $uid);
            out(['ok'=>true]);
        }

        /* === закрепить/открепить === */
        case 'togglePin': {
            $id = (int)$req->getPost('id');
            if ($id <= 0) out(['ok'=>false,'error'=>'bad id'], 400);

            $new = Repository::togglePinOwned($id, $uid);
            out(['ok'=>true,'isPinned'=>$new]);
        }

        /* === назначить/снять группу === */
        case 'setGroup': {
            $id = (int)$req->getPost('id');
            if ($id <= 0) out(['ok'=>false,'error'=>'bad id'], 400);

            $raw = $req->getPost('groupId');
            $groupId = ($raw === '' || $raw === null) ? null : (int)$raw;

            Repository::setGroupOwned($id, $uid, $groupId);
            out(['ok'=>true]);
        }

        default:
            out(['ok'=>false,'error'=>'unknown action'], 400);
    }
}
catch (SystemException $e) {
    out(['ok'=>false,'error'=>$e->getMessage()], 500);
}
catch (Throwable $e) {
    out(['ok'=>false,'error'=>'internal error'], 500);
}

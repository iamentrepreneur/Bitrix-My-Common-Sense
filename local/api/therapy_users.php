<?php
// /local/api/therapy_users.php
define("NO_KEEP_STATISTIC", true);
define("BX_SECURITY_SESSION_READONLY", true);

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

use Bitrix\Main\Context;
use Bitrix\Main\Engine\Response\Json;
use Bitrix\Main\Web\Json as WebJson;

global $USER;

header('Content-Type: application/json; charset=UTF-8');

if (!$USER->IsAuthorized() || !$USER->IsAdmin()) {
    http_response_code(403);
    echo WebJson::encode(['success'=>false,'message'=>'Forbidden']);
    die();
}

\Bitrix\Main\Loader::includeModule('main');

// Укажи свои STRING_ID групп
$THER_CODE = 'therapists';
$PAT_CODE  = 'patients';

function getGroupIdByCode($code): ?int {
    $by="c_sort"; $order="asc";
    $rs = CGroup::GetList($by,$order,["STRING_ID"=>$code]);
    if ($ar = $rs->Fetch()) return (int)$ar['ID'];
    return null;
}

$GID_THER = getGroupIdByCode($THER_CODE);
$GID_PAT  = getGroupIdByCode($PAT_CODE);

if (!$GID_THER || !$GID_PAT) {
    http_response_code(500);
    echo WebJson::encode(['success'=>false,'message'=>'Группы не найдены (STRING_ID therapists/patients)']);
    die();
}

// CSRF
$method = $_SERVER['REQUEST_METHOD'];
$req    = Context::getCurrent()->getRequest();

if ($method === 'GET') {
    $q = trim((string)$req->get('q'));
    $role = $req->get('role'); // therapists|patients (необяз.)
    $resp = [];

    $roles = ['therapists' => $GID_THER, 'patients' => $GID_PAT];
    $toFetch = ($role && isset($roles[$role])) ? [$role => $roles[$role]] : $roles;

    foreach ($toFetch as $key => $gid) {
        // Берём список по группе (до 100)
        $by = "id"; $order = "desc";
        $rs = CUser::GetList(
            $by, $order,
            ["GROUPS_ID" => [$gid]],
            ["SELECT" => ["ID","NAME","LAST_NAME","EMAIL","ACTIVE","LOGIN","PERSONAL_PHONE"], "NAV_PARAMS" => ["nPageSize" => 100]]
        );

        $rows = [];
        while ($u = $rs->Fetch()) {
            $rows[] = $u;
        }

        // Поиск в PHP: имя, фамилия, email, логин, телефон, ID
        if ($q !== '') {
            $qq = mb_strtolower($q);
            $rows = array_values(array_filter($rows, static function ($u) use ($qq) {
                $hay = [
                    $u['NAME'] ?? '',
                    $u['LAST_NAME'] ?? '',
                    $u['EMAIL'] ?? '',
                    $u['LOGIN'] ?? '',
                    $u['PERSONAL_PHONE'] ?? '',
                    (string)($u['ID'] ?? '')
                ];
                $hay = mb_strtolower(implode(' ', $hay));
                return mb_strpos($hay, $qq) !== false;
            }));
        }

        $resp[$key] = $rows;
    }

    echo WebJson::encode(['success' => true, 'data' => $resp]);
    die();
}


// читаем JSON заранее
$payloadRaw = file_get_contents('php://input');
$payload = [];
if ($payloadRaw) {
    try { $payload = WebJson::decode($payloadRaw); } catch (\Throwable $e) {}
}

// токен может прийти в разных местах
$tokenHeader = $req->getHeader('X-Bitrix-Csrf-Token');
$token = $req->getPost('sessid') ?:        // form-data
    $req->get('sessid')    ?:        // ?sessid=...
        ($payload['sessid'] ?? '') ?:    // JSON
            $tokenHeader;                    // заголовок

if (!hash_equals(bitrix_sessid(), (string)$token)) {
    http_response_code(400);
    echo WebJson::encode(['success'=>false,'message'=>'Bad CSRF']);
    die();
}


$payloadRaw = file_get_contents('php://input');
$payload = [];
if ($payloadRaw) {
    try { $payload = WebJson::decode($payloadRaw); } catch (\Throwable $e) {}
}
$action = $payload['action'] ?? $req->getPost('action') ?? '';

function attachUserToSingleRole(int $userId, int $roleGid, array $clearGids): ?string {
    $u = new CUser();
    $groups = CUser::GetUserGroup($userId);
    $groups = array_diff($groups, $clearGids);
    if (!in_array($roleGid, $groups, true)) $groups[]=$roleGid;
    $u->SetUserGroup($userId, $groups);
    return $u->LAST_ERROR ?: null;
}

if ($action === 'create_user') {
    $role  = $payload['role'] ?? 'patient'; // therapist|patient
    $gid   = $role === 'therapist' ? $GID_THER : $GID_PAT;

    $login = trim((string)($payload['login'] ?? ''));
    $email = trim((string)($payload['email'] ?? ''));
    $name  = trim((string)($payload['name'] ?? ''));
    $last  = trim((string)($payload['last_name'] ?? ''));
    $pass  = (string)($payload['password'] ?? '');
    $phone = trim((string)($payload['phone'] ?? ''));
    $active= ((string)($payload['active'] ?? 'Y'))==='Y'?'Y':'N';

    if ($login===''||$email===''||$name===''||$last===''||$pass==='') {
        http_response_code(422);
        echo WebJson::encode(['success'=>false,'message'=>'Заполните обязательные поля']);
        die();
    }

    $u = new CUser();
    $fields = [
        "LOGIN"=>$login,"EMAIL"=>$email,"NAME"=>$name,"LAST_NAME"=>$last,
        "PASSWORD"=>$pass,"CONFIRM_PASSWORD"=>$pass,"ACTIVE"=>$active,
        "PERSONAL_PHONE"=>$phone,"GROUP_ID"=>[$gid]
    ];
    $id = $u->Add($fields);
    if (!$id) {
        http_response_code(422);
        echo WebJson::encode(['success'=>false,'message'=>$u->LAST_ERROR ?: 'Ошибка']);
        die();
    }
    echo WebJson::encode(['success'=>true,'message'=>"Создан #$id"]);
    die();
}

if ($action === 'bind_existing') {
    $userId = (int)($payload['user_id'] ?? 0);
    $role   = $payload['role'] ?? 'patient';
    $gid    = $role === 'therapist' ? $GID_THER : $GID_PAT;

    if ($userId<=0) {
        http_response_code(422);
        echo WebJson::encode(['success'=>false,'message'=>'Неверный ID']);
        die();
    }
    $err = attachUserToSingleRole($userId,$gid,[$GID_THER,$GID_PAT]);
    if ($err) {
        http_response_code(422);
        echo WebJson::encode(['success'=>false,'message'=>$err]);
        die();
    }
    echo WebJson::encode(['success'=>true,'message'=>'Роль обновлена']);
    die();
}

if ($action === 'toggle_active') {
    $userId = (int)($payload['user_id'] ?? 0);
    $active = ((string)($payload['active'] ?? 'Y'))==='Y'?'Y':'N';
    if ($userId<=0) {
        http_response_code(422);
        echo WebJson::encode(['success'=>false,'message'=>'Неверный ID']);
        die();
    }
    $u = new CUser();
    if (!$u->Update($userId,["ACTIVE"=>$active])) {
        http_response_code(422);
        echo WebJson::encode(['success'=>false,'message'=>$u->LAST_ERROR ?: 'Ошибка']);
        die();
    }
    echo WebJson::encode(['success'=>true,'message'=>'Активность обновлена']);
    die();
}

http_response_code(400);
echo WebJson::encode(['success'=>false,'message'=>'Unknown action']);

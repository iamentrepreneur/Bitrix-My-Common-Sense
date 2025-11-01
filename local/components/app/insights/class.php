<?php
namespace Local\Components\App\Insights;

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;
use Bitrix\Main\Context;
use Bitrix\Main\SystemException;
use Bitrix\Main\UI\PageNavigation;
use CBitrixComponent;
use CComponentEngine;
use Local\Insights\Repository; // твой репозиторий (items + groups)

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

class Component extends CBitrixComponent
{
    protected int $userId;

    public function onPrepareComponentParams($params)
    {
        $params['SEF_MODE'] = $params['SEF_MODE'] === 'Y';
        return parent::onPrepareComponentParams($params);
    }

    protected function checkAuth(): void
    {
        global $USER;
        if (!$USER || !$USER->IsAuthorized()) {
            throw new SystemException('Требуется авторизация');
        }
        $this->userId = (int)$USER->GetID();
    }

    protected function resolveRoute(): array
    {
        $defaultUrlTemplates = [
            'list'  => '',
            'group' => 'group/#GROUP_ID#/',
        ];
        $componentVariables = ['GROUP_ID'];

        $engine = new CComponentEngine($this);
        $arUrlTemplates = CComponentEngine::MakeComponentUrlTemplates(
            $defaultUrlTemplates,
            $this->arParams['SEF_URL_TEMPLATES']
        );
        $arVariables = [];
        $componentPage = CComponentEngine::ParseComponentPath(
            $this->arParams['SEF_FOLDER'],
            $arUrlTemplates,
            $arVariables
        );
        if ($componentPage === false) {
            $componentPage = 'list';
        }
        CComponentEngine::InitComponentVariables(
            $componentPage, $componentVariables, [], $arVariables
        );

        return [$componentPage, $arUrlTemplates, $arVariables];
    }

    protected function handlePost(string $page, ?int $groupId): void
    {
        $request = \Bitrix\Main\Context::getCurrent()->getRequest();
        if (!$request->isPost() || !$request->getPost('INS_ACT')) return;

        if (!check_bitrix_sessid()) {
            throw new \Bitrix\Main\SystemException('Bad sessid');
        }

        $act = (string)$request->getPost('INS_ACT');

        // ——— СТРАНИЦА СПИСКОВ ———
        if ($page === 'list') {
            if ($act === 'GROUP_CREATE') {
                $name  = trim((string)$request->getPost('name'));
                $color = self::sanitizeHexColor((string)$request->getPost('color')) ?? '#cccccc';
                if ($name === '') throw new \Bitrix\Main\SystemException('Название обязательно');
                \Local\Insights\Repository::groupCreate($this->userId, $name, $color, 500);
            } elseif ($act === 'GROUP_RENAME') {
                $gid  = (int)$request->getPost('id');
                $name = trim((string)$request->getPost('name'));
                \Local\Insights\Repository::groupUpdateOwned($gid, $this->userId, ['UF_NAME' => $name]);
            } elseif ($act === 'GROUP_DELETE') {
                $gid = (int)$request->getPost('id');
                \Local\Insights\Repository::groupDeleteOwned($gid, $this->userId, true);
            } elseif ($act === 'GROUP_SET_COLOR') {
                $gid   = (int)$request->getPost('id');
                $color = self::sanitizeHexColor((string)$request->getPost('color')) ?? '#cccccc';
                \Local\Insights\Repository::groupUpdateOwned($gid, $this->userId, ['UF_COLOR' => $color]);
            }
            LocalRedirect($this->arResult['SEF_FOLDER']); // PRG
        }

        // ——— СТРАНИЦА КОНКРЕТНОЙ ГРУППЫ ———
        if ($page === 'group' && $groupId) {
            if ($act === 'ITEM_CREATE') {
                $title = trim((string)$request->getPost('title'));
                $text  = trim((string)$request->getPost('text'));
                $tags  = trim((string)$request->getPost('tags'));
                if ($text === '') throw new \Bitrix\Main\SystemException('Текст обязателен');
                \Local\Insights\Repository::create($this->userId, $text, $title, $tags, $groupId);
            } elseif ($act === 'ITEM_UPDATE') {
                $id = (int)$request->getPost('id');
                $fields = [
                    'UF_TITLE' => (string)$request->getPost('title'),
                    'UF_TEXT'  => (string)$request->getPost('text'),
                    'UF_TAGS'  => (string)$request->getPost('tags'),
                ];
                \Local\Insights\Repository::updateOwned($id, $this->userId, $fields);
            } elseif ($act === 'ITEM_DELETE') {
                $id = (int)$request->getPost('id');
                \Local\Insights\Repository::deleteOwned($id, $this->userId);
            } elseif ($act === 'ITEM_TOGGLE_PIN') {
                $id = (int)$request->getPost('id');
                \Local\Insights\Repository::togglePinOwned($id, $this->userId);
            } elseif ($act === 'GROUP_SET_COLOR') {
                $gid   = (int)$request->getPost('id');
                $color = self::sanitizeHexColor((string)$request->getPost('color')) ?? '#cccccc';
                \Local\Insights\Repository::groupUpdateOwned($gid, $this->userId, ['UF_COLOR' => $color]);
            }
            LocalRedirect($this->makeGroupUrl($groupId)); // PRG
        }
    }


    protected function makeGroupUrl(int $groupId): string
    {
        $tpl = $this->arParams['SEF_URL_TEMPLATES']['group'] ?? 'group/#GROUP_ID#/';
        return rtrim($this->arParams['SEF_FOLDER'], '/').'/'.str_replace('#GROUP_ID#', $groupId, $tpl);
    }

    public function executeComponent()
    {
        $this->checkAuth();
        [$page, $urlTemplates, $vars] = $this->resolveRoute();
        $groupId = isset($vars['GROUP_ID']) ? (int)$vars['GROUP_ID'] : null;

        // POST -> действия -> редирект
        $this->handlePost($page, $groupId);

        // Данные для шаблона
        $this->arResult['PAGE'] = $page;
        $this->arResult['URL_TEMPLATES'] = $urlTemplates;
        $this->arResult['SEF_FOLDER'] = $this->arParams['SEF_FOLDER'];
        $this->arResult['ERROR'] = null;

        try {
            if ($page === 'list') {
                $this->arResult['GROUPS'] = Repository::groupsListByOwnerWithCounters($this->userId);
            } else { // group
                if (!$groupId) throw new SystemException('Группа не указана');
                $this->arResult['GROUP_ID'] = $groupId;
                $this->arResult['GROUP'] = $this->findGroup($groupId);
                // пагинация
                $request = Context::getCurrent()->getRequest();
                $pageNum = max(1, (int)$request->get('page'));
                $pageSize = 20;
                $q = trim((string)$request->get('q'));

                $items = Repository::listByOwner($this->userId, $pageNum, $pageSize, $q, $groupId);
                $nav = new PageNavigation('ins_nav');
                $nav->setRecordCount($items['total']);
                $nav->setCurrentPage($pageNum);
                $nav->setPageSize($pageSize);
                $nav->initFromUri();

                $this->arResult['ITEMS'] = $items['items'];
                $this->arResult['TOTAL'] = $items['total'];
                $this->arResult['NAV']   = $nav;
                $this->arResult['Q']     = $q;
            }
        } catch (\Throwable $e) {
            $this->arResult['ERROR'] = $e->getMessage();
        }

        $this->includeComponentTemplate();
    }

    protected function findGroup(int $groupId): ?array
    {
        // быстрый поиск в списке владельца
        $groups = Repository::groupsListByOwner($this->userId);
        foreach ($groups as $g) {
            if ((int)$g['ID'] === $groupId) return $g;
        }
        return null;
    }

    private static function sanitizeHexColor(string $raw): ?string
    {
        $raw = trim($raw);
        // допустим только #RGB/#RRGGBB
        if (preg_match('/^#([0-9a-fA-F]{3}){1,2}$/', $raw)) {
            // нормализуем в верхний регистр
            return '#'.strtoupper(ltrim($raw, '#'));
        }
        return null;
    }
}

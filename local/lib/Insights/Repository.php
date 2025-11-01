<?php

namespace Local\Insights;

use Bitrix\Highloadblock as HL;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;

class Repository
{
    /** HL таблицы */
    private const ITEMS_TABLE = 'uf_insights_items';
    private const GROUPS_TABLE = 'uf_insights_groups';

    /** @var class-string<DataManager>|null */
    private static $itemsDataClass;
    /** @var class-string<DataManager>|null */
    private static $groupsDataClass;

    /**
     * DataClass для таблицы элементов.
     * @return class-string<DataManager>
     * @throws SystemException|LoaderException
     */
    public static function dataClass(): string
    {
        if (self::$itemsDataClass) {
            return self::$itemsDataClass;
        }
        if (!Loader::includeModule('highloadblock')) {
            throw new SystemException('Module highloadblock is not loaded');
        }
        $hl = HL\HighloadBlockTable::getList([
            'filter' => ['=TABLE_NAME' => self::ITEMS_TABLE],
            'limit' => 1,
        ])->fetch();
        if (!$hl) {
            throw new SystemException('HL not found: ' . self::ITEMS_TABLE);
        }
        $entity = HL\HighloadBlockTable::compileEntity($hl);
        /** @var class-string<DataManager> $dc */
        $dc = $entity->getDataClass();
        return self::$itemsDataClass = $dc;
    }

    /**
     * DataClass для таблицы групп.
     * @return class-string<DataManager>
     * @throws SystemException|LoaderException
     */
    public static function groupsDataClass(): string
    {
        if (self::$groupsDataClass) {
            return self::$groupsDataClass;
        }
        if (!Loader::includeModule('highloadblock')) {
            throw new SystemException('Module highloadblock is not loaded');
        }
        $hl = HL\HighloadBlockTable::getList([
            'filter' => ['=TABLE_NAME' => self::GROUPS_TABLE],
            'limit' => 1,
        ])->fetch();
        if (!$hl) {
            throw new SystemException('HL not found: ' . self::GROUPS_TABLE);
        }
        $entity = HL\HighloadBlockTable::compileEntity($hl);
        /** @var class-string<DataManager> $dc */
        $dc = $entity->getDataClass();
        return self::$groupsDataClass = $dc;
    }

    /**
     * Список инсайтов владельца с опциональным поиском и фильтром по группе.
     * @throws LoaderException|SystemException|ObjectPropertyException|ArgumentException
     */
    public static function listByOwner(
        int    $ownerId,
        int    $page = 1,
        int    $pageSize = 20,
        string $q = '',
        ?int   $groupId = null
    ): array
    {
        $DC = self::dataClass();

        $filter = ['=UF_OWNER_ID' => $ownerId];
        if ($groupId !== null) {
            $filter['=UF_GROUP_ID'] = $groupId;
        }
        if ($q !== '') {
            $filter[] = [
                'LOGIC' => 'OR',
                ['%UF_TITLE' => $q],
                ['%UF_TEXT' => $q],
                ['%UF_TAGS' => $q],
            ];
        }

        $res = $DC::getList([
            'filter' => $filter,
            'order' => ['UF_IS_PINNED' => 'DESC', 'UF_CREATED_AT' => 'DESC', 'ID' => 'DESC'],
            'select' => [
                'ID',
                'UF_OWNER_ID',
                'UF_TITLE',
                'UF_TEXT',
                'UF_TAGS',
                'UF_IS_PINNED',
                'UF_GROUP_ID',
                'UF_CREATED_AT',
                'UF_UPDATED_AT'
            ],
            'count_total' => true,
            'offset' => ($page - 1) * $pageSize,
            'limit' => $pageSize,
        ]);

        $items = [];
        while ($row = $res->fetch()) {
            $items[] = $row;
        }

        return ['items' => $items, 'total' => (int)$res->getCount()];
    }

    /**
     * Создать инсайт.
     * @throws LoaderException|SystemException
     */
    public static function create(
        int    $ownerId,
        string $text,
        string $title = '',
        string $tags = '',
        ?int   $groupId = null
    ): int
    {
        $DC = self::dataClass();
        $now = new DateTime();

        $fields = [
            'UF_OWNER_ID' => $ownerId,
            'UF_TITLE' => trim($title),
            'UF_TEXT' => trim($text),
            'UF_TAGS' => trim($tags),
            'UF_IS_PINNED' => 0,
            'UF_CREATED_AT' => $now,
            'UF_UPDATED_AT' => $now,
        ];
        if ($groupId !== null) {
            $fields['UF_GROUP_ID'] = $groupId;
        }

        $r = $DC::add($fields);
        if (!$r->isSuccess()) {
            throw new SystemException(implode('; ', $r->getErrorMessages()));
        }
        return (int)$r->getId();
    }

    /**
     * Получить инсайт по id (с проверкой владельца).
     * @throws LoaderException|SystemException|ObjectPropertyException|ArgumentException
     */
    public static function getOwned(int $id, int $ownerId): ?array
    {
        $DC = self::dataClass();

        $row = $DC::getByPrimary($id, [
            'select' => [
                'ID',
                'UF_OWNER_ID',
                'UF_TITLE',
                'UF_TEXT',
                'UF_TAGS',
                'UF_IS_PINNED',
                'UF_GROUP_ID',
                'UF_CREATED_AT',
                'UF_UPDATED_AT'
            ]
        ])->fetch();

        if (!$row || (int)$row['UF_OWNER_ID'] !== $ownerId) {
            return null;
        }
        return $row;
    }

    /**
     * Обновить разрешённые поля инсайта.
     * @throws LoaderException|SystemException|ObjectPropertyException|ArgumentException
     */
    public static function updateOwned(int $id, int $ownerId, array $fields): void
    {
        $DC = self::dataClass();
        $row = self::getOwned($id, $ownerId);
        if (!$row) {
            throw new SystemException('Not found');
        }

        $allowed = ['UF_TITLE', 'UF_TEXT', 'UF_TAGS', 'UF_SORT' ];
        $upd = [];
        foreach ($allowed as $k) {
            if (array_key_exists($k, $fields)) {
                $v = $fields[$k];
                if (is_string($v)) {
                    $v = trim($v);
                }
                $upd[$k] = $v;
            }
        }
        if (!$upd) {
            return;
        }

        $upd['UF_UPDATED_AT'] = new DateTime();

        $r = $DC::update($id, $upd);
        if (!$r->isSuccess()) {
            throw new SystemException(implode('; ', $r->getErrorMessages()));
        }
    }

    /**
     * Удалить инсайт владельца.
     * @throws LoaderException|SystemException|ObjectPropertyException|ArgumentException
     */
    public static function deleteOwned(int $id, int $ownerId): void
    {
        $DC = self::dataClass();
        $row = self::getOwned($id, $ownerId);
        if (!$row) {
            throw new SystemException('Not found');
        }

        $r = $DC::delete($id);
        if (!$r->isSuccess()) {
            throw new SystemException(implode('; ', $r->getErrorMessages()));
        }
    }

    /**
     * Переключить PIN у инсайта владельца.
     * @return int Новое значение UF_IS_PINNED (0|1)
     * @throws LoaderException|SystemException|ObjectPropertyException|ArgumentException
     */
    public static function togglePinOwned(int $id, int $ownerId): int
    {
        $DC = self::dataClass();
        $row = self::getOwned($id, $ownerId);
        if (!$row) {
            throw new SystemException('Not found');
        }

        $new = (int)!((int)$row['UF_IS_PINNED']);

        $r = $DC::update($id, [
            'UF_IS_PINNED' => $new,
            'UF_UPDATED_AT' => new DateTime(),
        ]);
        if (!$r->isSuccess()) {
            throw new SystemException(implode('; ', $r->getErrorMessages()));
        }

        return $new;
    }

    /**
     * Установить/снять группу у инсайта владельца.
     * Передай null в $groupId, чтобы снять группу.
     * @throws LoaderException|SystemException|ObjectPropertyException|ArgumentException
     */
    public static function setGroupOwned(int $id, int $ownerId, ?int $groupId): void
    {
        $DC = self::dataClass();
        $row = self::getOwned($id, $ownerId);
        if (!$row) {
            throw new SystemException('Not found');
        }

        // Если указана группа — убедимся, что она принадлежит владельцу
        if ($groupId !== null) {
            $GC = self::groupsDataClass();
            $g = $GC::getList([
                'filter' => ['=ID' => $groupId, '=UF_OWNER_ID' => $ownerId],
                'select' => ['ID']
            ])->fetch();
            if (!$g) {
                throw new SystemException('Group not found');
            }
        }

        $r = $DC::update($id, [
            'UF_GROUP_ID' => $groupId,
            'UF_UPDATED_AT' => new DateTime(),
        ]);
        if (!$r->isSuccess()) {
            throw new SystemException(implode('; ', $r->getErrorMessages()));
        }
    }

    /* ==================== Работа с группами ==================== */

    /**
     * Список групп владельца (для селектов и меню).
     * @throws LoaderException|SystemException|ObjectPropertyException|ArgumentException
     */
    public static function groupsListByOwner(int $ownerId): array
    {
        $GC = self::groupsDataClass();
        $res = $GC::getList([
            'filter' => ['=UF_OWNER_ID' => $ownerId],
            'order' => ['UF_SORT' => 'ASC', 'UF_NAME' => 'ASC'],
            'select' => ['ID', 'UF_NAME', 'UF_COLOR', 'UF_SORT'],
        ]);

        $out = [];
        while ($r = $res->fetch()) {
            $out[] = $r;
        }
        return $out;
    }

    /**
     * Создать группу.
     * @throws LoaderException|SystemException
     */
    public static function groupCreate(
        int    $ownerId,
        string $name,
        string $color = '#888888',
        int    $sort = 500
    ): int
    {
        $GC = self::groupsDataClass();
        $now = new DateTime();

        $r = $GC::add([
            'UF_OWNER_ID' => $ownerId,
            'UF_NAME' => trim($name),
            'UF_COLOR' => trim($color),
            'UF_SORT' => $sort,
            'UF_CREATED_AT' => $now,
            'UF_UPDATED_AT' => $now,
        ]);
        if (!$r->isSuccess()) {
            throw new SystemException(implode('; ', $r->getErrorMessages()));
        }
        return (int)$r->getId();
    }

    /**
     * Обновить свою группу.
     * @throws LoaderException|SystemException|ObjectPropertyException|ArgumentException
     */
    public static function groupUpdateOwned(int $groupId, int $ownerId, array $fields): void
    {
        $GC = self::groupsDataClass();
        $g = $GC::getByPrimary($groupId, ['select' => ['ID', 'UF_OWNER_ID']])->fetch();
        if (!$g || (int)$g['UF_OWNER_ID'] !== $ownerId) {
            throw new SystemException('Not found');
        }

        $upd = [];
        foreach (['UF_NAME', 'UF_COLOR', 'UF_SORT'] as $k) {
            if (array_key_exists($k, $fields)) {
                $v = $fields[$k];
                $upd[$k] = is_string($v) ? trim($v) : $v;
            }
        }
        if (!$upd) {
            return;
        }
        $upd['UF_UPDATED_AT'] = new DateTime();

        $r = $GC::update($groupId, $upd);
        if (!$r->isSuccess()) {
            throw new SystemException(implode('; ', $r->getErrorMessages()));
        }
    }

    /**
     * Удалить свою группу.
     * По умолчанию отвязывает группу у всех твоих инсайтов (cascade unlink).
     * @throws LoaderException|SystemException|ObjectPropertyException|ArgumentException
     */
    public static function groupDeleteOwned(int $groupId, int $ownerId, bool $cascadeUnlink = true): void
    {
        $GC = self::groupsDataClass();
        $g = $GC::getByPrimary($groupId, ['select' => ['ID', 'UF_OWNER_ID']])->fetch();
        if (!$g || (int)$g['UF_OWNER_ID'] !== $ownerId) {
            throw new SystemException('Not found');
        }

        if ($cascadeUnlink) {
            $DC = self::dataClass();
            $rs = $DC::getList([
                'filter' => ['=UF_OWNER_ID' => $ownerId, '=UF_GROUP_ID' => $groupId],
                'select' => ['ID']
            ]);
            while ($it = $rs->fetch()) {
                $DC::update($it['ID'], ['UF_GROUP_ID' => null, 'UF_UPDATED_AT' => new DateTime()]);
            }
        }

        $r = $GC::delete($groupId);
        if (!$r->isSuccess()) {
            throw new SystemException(implode('; ', $r->getErrorMessages()));
        }
    }


    /**
     * @throws LoaderException
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public static function itemsCountByGroup(int $ownerId): array
    {
        $DC = self::dataClass(); // это dataClass для ITEMS (uf_insights_items)
        // сгруппируем по UF_GROUP_ID
        $q = $DC::query()
            ->setSelect([
                'UF_GROUP_ID',
                new \Bitrix\Main\Entity\ExpressionField('CNT', 'COUNT(*)')
            ])
            ->where('UF_OWNER_ID', $ownerId)
            ->whereNotNull('UF_GROUP_ID')
            ->registerRuntimeField(new \Bitrix\Main\Entity\ExpressionField('CNT', 'COUNT(*)'))
            ->setGroup(['UF_GROUP_ID']);

        $map = [];
        foreach ($q->fetchAll() as $row) {
            $gid = (int)$row['UF_GROUP_ID'];
            if ($gid > 0) $map[$gid] = (int)$row['CNT'];
        }
        return $map;
    }


    /**
     * @throws LoaderException
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public static function groupsListByOwnerWithCounters(int $ownerId): array
    {
        // получаем группы так же, как в твоём groupsListByOwner (замени на свой код):
        $GC = self::groupsDataClass(); // dataClass для HL "группы" (например uf_insights_groups)
        $groups = [];
        $res = $GC::getList([
            'filter' => ['=UF_OWNER_ID' => $ownerId],
            'select' => ['ID','UF_NAME','UF_COLOR','UF_SORT'],
            'order'  => ['UF_SORT'=>'ASC','ID'=>'ASC'],
        ]);
        while ($r = $res->fetch()) {
            $r['CNT'] = 0;
            $groups[(int)$r['ID']] = $r;
        }

        // приклеим счётчики из items
        $cntMap = self::itemsCountByGroup($ownerId);
        foreach ($cntMap as $gid => $cnt) {
            if (isset($groups[$gid])) $groups[$gid]['CNT'] = $cnt;
        }

        return array_values($groups);
    }
}

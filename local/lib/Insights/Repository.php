<?php
namespace Local\Insights;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Highloadblock as HL;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\SystemException;

class Repository
{
    /** @var string HL таблица */
    private const TABLE = 'uf_insights_items';

    /** @var string DataManager classname */
    private static $dataClass;

    /**
     * Получить DataClass HL-блока по TABLE_NAME
     * @return class-string<DataManager>
     * @throws SystemException|LoaderException
     */
    public static function dataClass(): string
    {
        if (self::$dataClass) return self::$dataClass;

        if (!Loader::includeModule('highloadblock')) {
            throw new SystemException('Module highloadblock is not loaded');
        }

        $hl = HL\HighloadBlockTable::getList([
            'filter' => ['=TABLE_NAME' => self::TABLE],
            'limit'  => 1,
        ])->fetch();

        if (!$hl) {
            throw new SystemException('HL not found: '.self::TABLE);
        }

        $entity = HL\HighloadBlockTable::compileEntity($hl);
        /** @var class-string<DataManager> $dc */
        $dc = $entity->getDataClass();
        return self::$dataClass = $dc;
    }

    /** Список записей только владельца */
    public static function listByOwner(int $ownerId, int $page = 1, int $pageSize = 20, string $q = ''): array
    {
        try {
            $DC = self::dataClass();
        } catch (LoaderException|SystemException $e) {
            ShowError($e->getMessage());
        }
        $filter = ['=UF_OWNER_ID' => $ownerId];
        if ($q !== '') {
            $filter[] = [
                'LOGIC' => 'OR',
                ['%UF_TITLE' => $q],
                ['%UF_TEXT'  => $q],
                ['%UF_TAGS'  => $q],
            ];
        }

        try {
            $res = $DC::getList([
                'filter' => $filter,
                'order' => ['UF_IS_PINNED' => 'DESC', 'UF_CREATED_AT' => 'DESC', 'ID' => 'DESC'],
                'select' => ['ID', 'UF_OWNER_ID', 'UF_TITLE', 'UF_TEXT', 'UF_TAGS', 'UF_IS_PINNED', 'UF_CREATED_AT', 'UF_UPDATED_AT'],
                'count_total' => true,
                'offset' => ($page - 1) * $pageSize,
                'limit' => $pageSize,
            ]);
        } catch (ObjectPropertyException|ArgumentException|SystemException $e) {
            ShowError($e->getMessage());
        }

        $items = [];
        while ($row = $res->fetch()) { $items[] = $row; }
        return ['items' => $items, 'total' => (int)$res->getCount()];
    }

    /** Создать запись (владелец = ownerId)
     * @throws \Exception
     */
    public static function create(int $ownerId, string $text, string $title = '', string $tags = ''): int
    {
        try {
            $DC = self::dataClass();
        } catch (LoaderException|SystemException $e) {
            ShowError($e->getMessage());
        }
        $now = new DateTime();

        $r = $DC::add([
            'UF_OWNER_ID'  => $ownerId,
            'UF_TITLE'     => trim($title),
            'UF_TEXT'      => trim($text),
            'UF_TAGS'      => trim($tags),
            'UF_IS_PINNED' => 0,
            'UF_CREATED_AT'=> $now,
            'UF_UPDATED_AT'=> $now,
        ]);

        if (!$r->isSuccess()) {
            throw new SystemException(implode('; ', $r->getErrorMessages()));
        }
        return (int)$r->getId();
    }

    /** Получить запись и проверить владельца */
    public static function getOwned(int $id, int $ownerId): ?array
    {
        try {
            $DC = self::dataClass();
        } catch (LoaderException|SystemException $e) {
            ShowError($e->getMessage());
        }

        try {
            $row = $DC::getByPrimary($id, [
                'select' => ['ID', 'UF_OWNER_ID', 'UF_TITLE', 'UF_TEXT', 'UF_TAGS', 'UF_IS_PINNED', 'UF_CREATED_AT', 'UF_UPDATED_AT']
            ])->fetch();
        } catch (ObjectPropertyException|ArgumentException|SystemException $e) {
            ShowError($e->getMessage());
        }

        if (!$row || (int)$row['UF_OWNER_ID'] !== $ownerId) {
            return null;
        }
        return $row;
    }

    /** Обновить (только разрешённые поля)
     * @throws SystemException
     * @throws \Exception
     */
    public static function updateOwned(int $id, int $ownerId, array $fields): void
    {
        try {
            $DC = self::dataClass();
        } catch (LoaderException|SystemException $e) {
            ShowError($e->getMessage());
        }
        $row = self::getOwned($id, $ownerId);
        if (!$row) throw new SystemException('Not found');

        $allowed = ['UF_TITLE','UF_TEXT','UF_TAGS'];
        $upd = [];
        foreach ($allowed as $k) {
            if (array_key_exists($k, $fields)) {
                $v = $fields[$k];
                if (is_string($v)) $v = trim($v);
                $upd[$k] = $v;
            }
        }
        if (!$upd) return;

        $upd['UF_UPDATED_AT'] = new DateTime();
        $r = $DC::update($id, $upd);
        if (!$r->isSuccess()) {
            throw new SystemException(implode('; ', $r->getErrorMessages()));
        }
    }

    /** Удалить запись владельца
     * @throws \Exception
     */
    public static function deleteOwned(int $id, int $ownerId): void
    {
        try {
            $DC = self::dataClass();
        } catch (LoaderException|SystemException $e) {
            ShowError($e->getMessage());
        }
        $row = self::getOwned($id, $ownerId);
        if (!$row) throw new SystemException('Not found');

        $r = $DC::delete($id);
        if (!$r->isSuccess()) {
            throw new SystemException(implode('; ', $r->getErrorMessages()));
        }
    }

    /** Переключить PIN у записи владельца
     * @throws \Exception
     */
    public static function togglePinOwned(int $id, int $ownerId): int
    {
        try {
            $DC = self::dataClass();
        } catch (LoaderException|SystemException $e) {
            ShowError($e->getMessage());
        }
        $row = self::getOwned($id, $ownerId);
        if (!$row) throw new SystemException('Not found');

        $new = (int)!((int)$row['UF_IS_PINNED']);
        $r = $DC::update($id, [
            'UF_IS_PINNED' => $new,
            'UF_UPDATED_AT'=> new DateTime(),
        ]);
        if (!$r->isSuccess()) {
            throw new SystemException(implode('; ', $r->getErrorMessages()));
        }
        return $new;
    }
}

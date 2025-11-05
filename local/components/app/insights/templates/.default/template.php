<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var array $arResult */
/** @var array $arParams */

$folder = rtrim($arResult['SEF_FOLDER'] ?? '/', '/').'/';
$tpls   = $arResult['URL_TEMPLATES'] ?? ['group' => 'group/#GROUP_ID#/'];

$makeUrl = function(string $tpl, array $vars = []) use ($folder){
    foreach ($vars as $k=>$v) { $tpl = str_replace('#'.$k.'#', $v, $tpl); }
    return $folder . ltrim($tpl, '/');
};

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }
?>


<div class="wrap">
    <?php if ($arResult['ERROR']): ?>
        <div class="panel err"><?=h($arResult['ERROR'])?></div>
    <?php endif; ?>

    <?php if ($arResult['PAGE'] === 'list'): ?>
        <div class="panel">
            <div class="head">
                <h2 class="title">Мои списки</h2>
                <div class="muted">Создавай списки и заходи внутрь для работы с инсайтами</div>
            </div>

            <!-- Создание списка с цветом -->
            <form method="post" class="form-row">
                <?=bitrix_sessid_post()?>
                <input type="hidden" name="INS_ACT" value="GROUP_CREATE"/>
                <input class="input" type="text" name="name" placeholder="Название нового списка" required/>
                <input class="input" type="color" name="color" value="#cccccc" title="Цвет списка"/>
                <button class="btn btn--p" type="submit">Создать</button>
            </form>
        </div>

        <div class="items-panel">
            <?php if (empty($arResult['GROUPS'])): ?>
            <div class="list">
                <div class="item">Пока нет ни одного списка.</div>
            </div>
            <?php else: ?>
                <div class="list">
                    <?php foreach ($arResult['GROUPS'] as $g): ?>
                        <?php
                        $color = trim((string)($g['UF_COLOR'] ?? ''));
                        $dotStyle = $color ? 'style="background:'.h($color).'"' : '';
                        $cnt = (int)($g['CNT'] ?? 0);
                        ?>
                        <div class="item">
                            <div class="item-head-outer">
                                <div class="color-head">
                                    <span class="group-dot" <?=$dotStyle?>></span>
                                    <strong><?=h($g['UF_NAME'] ?: 'Без названия')?></strong>
<!--                                    <div class="count-badge" title="Количество инсайтов">-->
<!--                                        <span>--><?php //=$cnt?><!--</span>-->
<!--                                    </div>-->
                                </div>
<!--                                <div class="muted">ID: --><?php //= (int)$g['ID']?><!--</div>-->
                                <a class="btn" href="<?=h($makeUrl($tpls['group'] ?? 'group/#GROUP_ID#/', ['GROUP_ID'=>(int)$g['ID']]))?>">Открыть</a>
                            </div>
                            <div class="item-actions">

                                <!-- Переименование -->
                                <form method="post">
                                    <?=bitrix_sessid_post()?>
                                    <input type="hidden" name="INS_ACT" value="GROUP_RENAME"/>
                                    <input type="hidden" name="id" value="<?= (int)$g['ID'] ?>"/>
                                    <input class="input" type="text" name="name" placeholder="Новое имя" />
                                    <button class="btn" type="submit">Переименовать</button>
                                </form>

                                <!-- Удаление -->
                                <form method="post" onsubmit="return confirm('Удалить список? Инсайты останутся без группы.')">
                                    <?=bitrix_sessid_post()?>
                                    <input type="hidden" name="INS_ACT" value="GROUP_DELETE"/>
                                    <input type="hidden" name="id" value="<?= (int)$g['ID'] ?>"/>
                                    <button class="btn" type="submit">Удалить</button>
                                </form>

                                <!-- 🎨 Смена цвета (автосабмит) -->
                                <form method="post">
                                    <?=bitrix_sessid_post()?>
                                    <input type="hidden" name="INS_ACT" value="GROUP_SET_COLOR">
                                    <input type="hidden" name="id" value="<?= (int)$g['ID'] ?>">
                                    <input type="color" name="color" value="<?=h($color ?: '#cccccc')?>"
                                           title="Выбрать цвет" onchange="this.form.submit()">
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    <?php else: /* GROUP PAGE */ ?>
        <?php $g = $arResult['GROUP'] ?? null; ?>
        <?php
        $color = trim((string)($g['UF_COLOR'] ?? ''));
        $dotStyle = $color ? 'style="background:'.h($color).'"' : '';
        ?>

        <div class="under-panel">
            <a class="btn" href="<?=h($folder)?>">← К спискам</a>
        </div>

        <div class="panel">
            <div class="head">
                <div class="list-head">
                    <h2 class="title">
                        <span class="group-dot" <?=$dotStyle?>></span>
                        <?= h($g['UF_NAME'] ?? 'Список') ?>
                    </h2>
                    <div class="muted" style="display: none">
                        ID: <?= (int)($arResult['GROUP_ID']) ?> · всего: <?= (int)$arResult['TOTAL'] ?>
                        <?php if ($color): ?>
                            · цвет: <span class="count-badge" style="background:<?=h($color)?>;border-color:rgba(0,0,0,.1);color:#000"> </span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="list-actions">


                    <!-- Переименовать -->
                    <form method="post">
                        <?=bitrix_sessid_post()?>
                        <input type="hidden" name="INS_ACT" value="GROUP_RENAME"/>
                        <input type="hidden" name="id" value="<?= (int)$arResult['GROUP_ID'] ?>"/>
                        <input class="input" type="text" name="name" placeholder="Новое имя списка"/>
                        <button class="btn" type="submit">Переименовать</button>
                    </form>

                    <!-- Удалить -->
                    <form method="post" onsubmit="return confirm('Удалить список? Инсайты останутся без группы.')">
                        <?=bitrix_sessid_post()?>
                        <input type="hidden" name="INS_ACT" value="GROUP_DELETE"/>
                        <input type="hidden" name="id" value="<?= (int)$arResult['GROUP_ID'] ?>"/>
                        <button class="btn" type="submit">Удалить</button>
                    </form>

                    <!-- 🎨 Смена цвета внутри группы (автосабмит) -->
                    <form method="post">
                        <?=bitrix_sessid_post()?>
                        <input type="hidden" name="INS_ACT" value="GROUP_SET_COLOR">
                        <input type="hidden" name="id" value="<?= (int)$arResult['GROUP_ID'] ?>">
                        <input type="color" name="color" value="<?=h($color ?: '#cccccc')?>"
                               title="Цвет списка" onchange="this.form.submit()">
                    </form>
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="list-items-action">
                <form method="post" class="row">
                    <?=bitrix_sessid_post()?>
                    <input type="hidden" name="INS_ACT" value="ITEM_CREATE"/>
                    <input class="input" type="text" name="title" placeholder="Заголовок"/>
                    <textarea class="textarea" name="text" placeholder="Текст инсайта…" required></textarea>
                    <input class="input" type="text" name="tags" placeholder="Теги (через запятую)"/>
                    <button class="btn btn--p" type="submit">Добавить</button>
                </form>
            </div>
        </div>

        <div class="items-panel">
            <?php if (empty($arResult['ITEMS'])): ?>
            <div class="items-list">
                <div class="item">Пока пусто.</div>
            </div>
            <?php else: ?>
                <div class="items-list">
                    <?php foreach ($arResult['ITEMS'] as $it): ?>
                        <div class="item">
                            <div class="card">
                                <div class="card-in">
                                    <div class="card-top">
                                        <div class="card-top-title">
                                            <input class="input" type="text" form="form-<?= (int)$it['ID']?>" name="title" value="<?=h($it['UF_TITLE'] ?? '')?>" placeholder="Без названия"/>
                                        </div>
                                        <?php if ((int)$it['UF_IS_PINNED'] === 1): ?>
                                            <div class="badge">
                                                <span><i class="ri-flag-2-fill"></i></span>
                                            </div>
                                        <?else:?>
                                            <div class="badge">
                                                <span><i class="ri-flag-2-line"></i></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="card-middle">
                                        <textarea class="textarea" form="form-<?= (int)$it['ID']?>" name="text" placeholder="Текст…"><?=h($it['UF_TEXT'] ?? '')?></textarea>
                                    </div>

                                    <div class="card-footer">
                                        <input class="input" form="form-<?= (int)$it['ID']?>" type="text" name="tags" value="<?=h($it['UF_TAGS'][0] ?? '')?>" placeholder="теги…"/>
                                        <form class="card-footer-form-1" method="post" id="form-<?= (int)$it['ID']?>">
                                            <?=bitrix_sessid_post()?>
                                            <input type="hidden" name="INS_ACT" value="ITEM_UPDATE"/>
                                            <input type="hidden" name="id" value="<?= (int)$it['ID']?>"/>
                                            <button class="btn" type="submit">Сохранить</button>
                                        </form>
                                        <form class="card-footer-form-2" method="post" onsubmit="return confirm('Удалить инсайт?')">
                                            <?=bitrix_sessid_post()?>
                                            <input type="hidden" name="INS_ACT" value="ITEM_DELETE"/>
                                            <input type="hidden" name="id" value="<?= (int)$it['ID']?>"/>
                                            <button class="btn" type="submit">Удалить</button>
                                        </form>
                                    </div>

                                    <form method="post" style="display: none">
                                        <?=bitrix_sessid_post()?>
                                        <input type="hidden" name="INS_ACT" value="ITEM_TOGGLE_PIN"/>
                                        <input type="hidden" name="id" value="<?= (int)$it['ID']?>"/>
                                        <button class="btn" type="submit"><?= ((int)$it['UF_IS_PINNED'] === 1 ? 'Открепить' : 'Закрепить') ?></button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Пагинация -->
                <div class="pager">
                    <?php
                    /** @var \Bitrix\Main\UI\PageNavigation $nav */
                    $nav = $arResult['NAV'];
                    if ($nav && $nav->getRecordCount() > $nav->getLimit()):
                        for ($p=1; $p <= $nav->getPageCount(); $p++):
                            $u = $makeUrl($tpls['group'] ?? 'group/#GROUP_ID#/', ['GROUP_ID'=>(int)$arResult['GROUP_ID']]) . '?page='.$p;
                            if ($p == $nav->getCurrentPage()): ?>
                                <span class="badge"><?=$p?></span>
                            <?php else: ?>
                                <a class="btn" href="<?=h($u)?>"><?=$p?></a>
                            <?php endif;
                        endfor;
                    endif; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

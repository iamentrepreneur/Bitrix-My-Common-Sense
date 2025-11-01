<?
global $USER;
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("test");
?>


<?

use Bitrix\Main\Context;
use Bitrix\Main\SystemException;
use Local\Insights\Repository;

$uid = (int)$USER->GetID();
$req = Context::getCurrent()->getRequest();
$action = (string)$req->get('a');

$page = 1;
$pageSize = 10;
$q = trim((string)$req->get('q'));

$data = Repository::listByOwner($uid, $page, $pageSize, $q);

?>


<pre>
    <?print_r($data);?>
</pre>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
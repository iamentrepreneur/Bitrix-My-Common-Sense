<?
//AddEventHandler("main", "OnBeforeProlog", "MyOnBeforePrologHandler", 50);
function MyOnBeforePrologHandler(){
   global $USER;
   if(!is_object($USER)){
      $USER = new CUser();
   }
   if (!$USER->IsAdmin()){
      include($_SERVER["DOCUMENT_ROOT"]."/coming-soon/site_closed.php");
      die();
   }
};
?>
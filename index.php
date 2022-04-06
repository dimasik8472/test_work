<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetPageProperty("title", "Демонстрационная версия продукта «1С-Битрикс: Управление сайтом»");
$APPLICATION->SetPageProperty("NOT_SHOW_NAV_CHAIN", "Y");
$APPLICATION->SetTitle("Главная страница");
?>

<!-- тестовая задача -->
<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
CModule::IncludeModule('iblock');

// проверим установлен ли модуль и получим блок с кодом $BID и типом catalog
if (CModule::IncludeModule("iblock")) {
  // новый файл
  $fileNew = $_SERVER["DOCUMENT_ROOT"]."/upload/test.csv";

  // якобы получаем файл из свойства элемента инфоблока
  $fileOld = $_SERVER["DOCUMENT_ROOT"]."/upload/test2.csv";
  
  // сравниваем файлы как две строки
  if (file_get_contents($fileNew) === file_get_contents($fileOld)) {
    // если нет различий, ничего не делаем
    echo 'изменений нет';
  } else {
    // если различия есть, сохраняем новый файл
    $elem = new CIBlockElement;

    $PROP = array();

    // свойству с кодом file_code присваиваем файл
    $PROP['file_code'] = CFile::MakeFileArray($fileNew);

    $arLoadProductArray = Array(
      "MODIFIED_BY"    => $USER->GetID(), // элемент изменен текущим пользователем
      "IBLOCK_SECTION" => false, // элемент лежит в корне раздела
      "PROPERTY_VALUES"=> $PROP,
      "NAME"           => "Сохранённый тестовый файл",
      "ACTIVE"         => "Y", // активен
      "PREVIEW_TEXT"   => "",
      "DETAIL_TEXT"    => "",
      "DETAIL_PICTURE" => ""
    );
    
    $PRODUCT_ID = 8; // изменяем элемент с кодом (ID) 8
    $res = $elem->Update($PRODUCT_ID, $arLoadProductArray);

    echo 'изменения есть';
  }

  // парсинг файла
  ?>
  <table>
    <?php
    foreach (file($fileNew) as $lineNum => $line) {
        $arrLine = explode(";", $line);
        ?>
        <tr>
          <?php
          foreach ($arrLine as $td) {
          ?>
            <td><?php echo $td ?>&nbsp;</td>
          <?php
          }
          ?>
        </tr>
        <?php
    }
    ?>
  </table>
<?php
}
?>
<!-- /тестовая задача -->

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
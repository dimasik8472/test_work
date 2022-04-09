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

$elem = new CIBlockElement;

// id инфоблока
const INFOBLOCK_ID = 1;

// проверим установлен ли модуль и получим блок с кодом $BID и типом catalog
if (CModule::IncludeModule("iblock")) {

  // новый файл
  $fileNew = file($_SERVER["DOCUMENT_ROOT"]."/upload/test.csv");

  $arrDel = Array(); // массив id товаров для проверки удаления элемента

  foreach ($fileNew as $lineNum => $line) {
    if ($lineNum == 0) continue; // пропускаем строку с заголовками

    $arrLine = explode(";", $line);

    $arrProps = Array(
      'id'            => $arrLine[0],
      'name'          => $arrLine[1],
      'preview_text'  => $arrLine[2],
      'detail_text'   => $arrLine[3],
      'prop1'         => $arrLine[4],
      'prop2'         => $arrLine[5],
    );

    array_push($arrDel, $arrProps['id']); // добавляем id товара в массив для проверки удаления элемента

    $arrFilter = Array("IBLOCK_ID" => INFOBLOCK_ID, "PROPERTY_ID_PRODUCT" => $arrProps['id']);
    $arrSelect = Array('ID', 'IBLOCK_ID');
    $res = $elem->GetList(Array("SORT"=>"ASC"), $arrFilter, false, array(), $arrSelect);

    $arrFields = Array();

    while ($ob = $res->GetNextElement()) {
      $arrFields = $ob->GetFields();
    }

    if (empty($arrFields['ID'])) {
      // если элемента со соответствующим свойством id нет, создаём новый элемент
      $PROP = array();
      $PROP['ID_PRODUCT'] = $arrProps['id'];
      $PROP['PROP1'] = $arrProps['prop1'];
      $PROP['PROP2'] = $arrProps['prop2'];

      $arLoadProductArray = Array(
        "MODIFIED_BY"       => $USER->GetID(),
        "IBLOCK_SECTION_ID" => false,
        "IBLOCK_ID"         => INFOBLOCK_ID,
        "PROPERTY_VALUES"   => $PROP,
        "NAME"              => $arrProps['name'],
        "ACTIVE"            => "Y",
        "PREVIEW_TEXT"      => $arrProps['preview_text'],
        "DETAIL_TEXT"       => $arrProps['detail_text'],
        "DETAIL_PICTURE"    => ''
      );

      $elem->Add($arLoadProductArray);

      echo 'добавлен ' . $arrProps['name'];
    } else {
      // если товар уже есть, сравнить свойства
      
      $res = $elem->GetByID($arrFields['ID']);

      $PROPUPDATE = array();

      // id товара не меняется
      $idProduct = $elem->GetProperty(
        INFOBLOCK_ID,
        $arrFields['ID'],
        Array("sort"  => "asc"),
        Array("CODE"  => "ID_PRODUCT")
      );
      $arrIdProduct = $idProduct->Fetch();
      $PROPUPDATE['ID_PRODUCT'] = $arrIdProduct['VALUE'];
      if ($arrRes = $res->GetNext()) {

        $update = false;

        // проверяем имя
        $nameUpdate = $arrRes['NAME'];
        if ($nameUpdate != $arrProps['name']) {
          $nameUpdate = $arrProps['name'];
          $update = true;
        }

        // проверяем анонс
        $ptUpdate = $arrRes['PREVIEW_TEXT'];
        if ($ptUpdate != $arrProps['preview_text']) {
          $ptUpdate = $arrProps['preview_text'];
          $update = true;
        }

        // проверяем детальное описание
        $dtUpdate = $arrRes['DETAIL_TEXT'];
        if ($dtUpdate != $arrProps['detail_text']) {
          $dtUpdate = $arrProps['detail_text'];
          $update = true;
        }

        // проверяем первое свойство
        $prop1 = $elem->GetProperty(
          INFOBLOCK_ID,
          $arrFields['ID'],
          Array("sort"  => "asc"),
          Array("CODE"  => "PROP1")
        );
        
        $arrProp1 = $prop1->Fetch();

        if ($arrProp1["VALUE"] != $arrProps['prop1']) {
          $PROPUPDATE['PROP1'] = $arrProps['prop1'];
          $update = true;
        } else {
          $PROPUPDATE['PROP1'] = $arrProp1["VALUE"];
        }

        // проверяем второе свойство
        $prop2 = $elem->GetProperty(
          INFOBLOCK_ID,
          $arrFields['ID'],
          Array("sort"  => "asc"),
          Array("CODE"  => "PROP2")
        );
        
        $arrProp2 = $prop2->Fetch();

        if ($arrProp2["VALUE"] != $arrProps['prop2']) {
          $PROPUPDATE['PROP2'] = $arrProps['prop2'];
          $update = true;
        } else {
          $PROPUPDATE['PROP2'] = $arrProp2["VALUE"];
        }

        // если есть изменения редактируем свойство
        if ($update) {
          $arLoadProductArray = Array(
            "MODIFIED_BY"       => $USER->GetID(),
            "IBLOCK_SECTION"    => false,
            "IBLOCK_ID"         => INFOBLOCK_ID,
            "PROPERTY_VALUES"   => $PROPUPDATE,
            "NAME"              => $nameUpdate,
            "ACTIVE"            => "Y",
            "PREVIEW_TEXT"      => $ptUpdate,
            "DETAIL_TEXT"       => $dtUpdate,
            "DETAIL_PICTURE"    => ''
          );
    
          $elem->Update($arrFields['ID'], $arLoadProductArray);
          echo $nameUpdate . '-изменён<br>';
        }
      }
    }
  }

  // удаление товаров, не имеющихся в файле
  $res = $elem->GetList(
    Array("SORT"=>"ASC"),
    Array("IBLOCK_ID" => INFOBLOCK_ID),
    false,
    Array(),
    Array('ID', 'IBLOCK_ID')
  );

  $arrFields = Array();

  while ($ob = $res->GetNextElement()) {
    $arrFields = $ob->GetFields();

    // получаем id товара
    $idProduct = $elem->GetProperty(
      INFOBLOCK_ID,
      $arrFields['ID'],
      Array("sort"  => "asc"),
      Array("CODE"  => "ID_PRODUCT")
    );
    $arrIdProduct = $idProduct->Fetch();

    // проверяем, есть ли такой id в файле
    if (!in_array($arrIdProduct['VALUE'] ,$arrDel)) {
      $elem->Delete($arrFields['ID']);
      
      echo 'удалён ' . $arrFields['ID'] . '<br>';
    }
  }
}
?>
<!-- /тестовая задача -->

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
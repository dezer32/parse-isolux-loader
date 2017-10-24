<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
CModule::IncludeModule("iblock");
require __DIR__ . "/SectionSettings.php";
$sect = new CIBlockSection();
$arSectionIds = [];

foreach ($arSectionsUrl as $section => $children) {
    $arFilter = [
        "NAME" => $section
    ];
    $arSelect = [
        "ID",
        "IBLOCK_SECTION_ID",
        "ACTIVE",
        "NAME",
        "CODE",
        "SECTION_PAGE_URL"
    ];
    $rsSection = $sect->GetList(["SORT" => "ASC"], $arFilter, false, $arSelect);
    $arSection = $rsSection->GetNext();
    if ($arSection) {
        $arSectionIds[$arSection["NAME"]] = $arSection["ID"];
    } else {
        continue;
        $arFields = [
            "NAME" => $section,
            "IBLOCK_ID" => 1,
            "IBLOCK_SECTION_ID" => "",
            "ACTIVE" => "Y",
            "CODE" => CUtil::translit($section, "ru")
        ];
        $idNewSect = $sect->Add($arFields);
        if (!($idNewSect > 0)) {
            echo $sect->LAST_ERROR;
        }else {
            $arSectionIds[$section] = $idNewSect;
        }
    }

}

print_r($arSectionIds);

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_after.php");
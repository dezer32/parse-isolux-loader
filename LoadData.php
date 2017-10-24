<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
CModule::IncludeModule("iblock");
require __DIR__ . "/SectionSettings.php";
$sect = new CIBlockSection();
$arSectionIds = [];

$arSelect = [
    "ID",
    "IBLOCK_SECTION_ID",
    "ACTIVE",
    "NAME",
    "CODE",
    "SECTION_PAGE_URL"
];

$i = 0;

foreach ($arSectionsUrl as $section => $children) {
    $arFilter = [
        "NAME" => $section
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
    foreach ($children as $childSect => $childElem) {
        $isNotTruSection = false;
        $arFilter = [
            "NAME" => $childSect
        ];
        $rsSection = $sect->GetList(["SORT" => "ASC"], $arFilter, false, $arSelect);
        $arSection = $rsSection->GetNext();
        if ($arSection) {
            $arSectionIds[$arSection["NAME"]] = $arSection["ID"];
            if ($arSectionIds[$section] != $arSection["IBLOCK_SECTION_ID"] && $arSection["NAME"] == "Аквапанели") {
                $sect->Update($arSection["ID"], ["IBLOCK_SECTION_ID" => $arSectionIds[$section]]);
            } else {
                $isNotTruSection = true;
            }
        }
        if (!$arSection || $isNotTruSection) {
            continue;
            $arFields = [
                "NAME" => $childSect,
                "IBLOCK_ID" => 1,
                "IBLOCK_SECTION_ID" => $arSectionIds[$section],
                "ACTIVE" => "Y",
                "CODE" => CUtil::translit($childSect, "ru")
            ];
            $idNewSect = $sect->Add($arFields);
            if (!($idNewSect > 0)) {
                echo $sect->LAST_ERROR;
            }else {
                $arSectionIds[$childSect."_new"] = $idNewSect;
            }
        }
        if (is_array($childElem)) {
            
        } else {
            //Если ссылка, а не подсекция.
        }
    }
}

print_r($arSectionIds);

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_after.php");
<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
CModule::IncludeModule("iblock");
require __DIR__ . "/SectionSettings.php";
require __DIR__ . "/parse-isolux/IsoluxParser.php";
$sect = new CIBlockSection();
$arSectionIds = [];
$arUnicProp = [];

$arSelect = [
    "ID",
    "IBLOCK_SECTION_ID",
    "ACTIVE",
    "NAME",
    "CODE",
    "SECTION_PAGE_URL"
];
$isoLux = new \Isolux\IsoluxParser();
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
            foreach ($childElem as $childChildSect => $childChildElem) {
                $isNotTruSection = false;
                $arFilter = [
                    "NAME" => $childChildSect
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
                    $arFields = [
                        "NAME" => $childChildSect,
                        "IBLOCK_ID" => 1,
                        "IBLOCK_SECTION_ID" => $arSectionIds[$childSect],
                        "ACTIVE" => "Y",
                        "CODE" => CUtil::translit($childChildSect, "ru")
                    ];
                    $idNewSect = $sect->Add($arFields);
                    if (!($idNewSect > 0)) {
                        echo $sect->LAST_ERROR;
                    }else {
                        $arSectionIds[$childChildSect."_new"] = $idNewSect;
                    }
                }

                //Если ссылка
                    $pageItem = $isoLux->parseItemData($childChildElem);
                    foreach ($pageItem as $item) {
                        foreach ($item["characteristics"] as $characteristics) {
                            if (!in_array($characteristics["label"], $arUnicProp)) {
                                $arUnicProp[] = $characteristics["label"];
                            }
                        }
                    }
            }
        } else {
            //Если ссылка
            print_r($childElem);
        }
    }
}

//print_r($arSectionIds);
print_r($arUnicProp);
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_after.php");
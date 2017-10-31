<?
require __DIR__ . "/SectionSettings.php";
require __DIR__ . "/BXLoadData.php";

$bxLoadData = new BXLoadData();

foreach ($arSectionsUrl as $section => $child) {
    if ($bxLoadData->findSection($section) == false) {
        if (!$bxLoadData->createSection($section)) {
            die();
        }
    }
    if (is_array($child)) {
        foreach ($child as $childSection => $childElem) {
            $findSection = $bxLoadData->findSection($childSection, $section);

            if ($findSection == false && $childSection == "Аквапанели") {
                $findSection = $bxLoadData->findSection($childSection);
            }
            if ($findSection == false) {
                if (!$bxLoadData->createSection($childSection, $section)) {
                    die();
                }
            } else {
                if ($findSection["IBLOCK_SECTION_ID"] != $bxLoadData->getParentSectionByParentSectionName($section) && $childSection == "Аквапанели") {
                    $bxLoadData->transferringSection($section, $findSection);
                } elseif ($findSection["IBLOCK_SECTION_ID"] != $bxLoadData->getParentSectionByParentSectionName($section)) {
                    if (!$bxLoadData->createSection($childSection, $section)) {
                        die();
                    }
                }
            }
            if (is_array($childElem)) {
                foreach ($childElem as $childChildSect => $childChildElem) {
                    $findSection = $bxLoadData->findSection($childChildSect, $childSection);
                    if ($findSection == false) {
                        if (!$bxLoadData->createSection($childChildSect, $childSection)) {
                            die();
                        }
                    } else {
                        if ($findSection["IBLOCK_SECTION_ID"] != $bxLoadData->getParentSectionByParentSectionName($childSection)) {
                            if (!$bxLoadData->createSection($childChildSect, $childSection)) {
                                die();
                            }
                        }
                    }
                }
            }
        }
    }
}
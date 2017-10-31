<?
ini_set('max_execution_time', 300);

require __DIR__ . "/SectionProperties.php";
require __DIR__ . "/SectionSettings.php";
require __DIR__ . "/BXLoadData.php";
require __DIR__ . "/parse-isolux/IsoluxParser.php";

$isoLux = new \Isolux\IsoluxParser();

$bxLoadData = new BXLoadData(1, $arSectionProperties);

/*$step = abs(intval($_REQUEST["step"]));
$step = empty($step) ? 0 : $step;
$inStep = 0;
foreach ($arSectionsUrl as $sectionUrl => $subsection) {
    $arStepSection[$sectionUrl] = $subsection;
    if ($inStep == $step) {
        unset($arStepSection);
        $arStepSection[$sectionUrl] = $subsection;
        break;
    }
    $inStep++;
}*/

foreach ($arSectionsUrl as $section => $child) {
    if ($bxLoadData->findSection($section) == false) {
        die();
    }
    if (is_array($child)) {
        foreach ($child as $childSection => $childElem) {
            if ($bxLoadData->findSection($childSection, $section) == false) {
                die();
            }
            if (is_array($childElem)) {
                foreach ($childElem as $childChildSect => $childChildElem) {
                    if ($bxLoadData->findSection($childChildSect, $childSection) == false) {
                        die();
                    }
                    $pageDom = $isoLux->parseItemData($childChildElem);
                    foreach ($pageItem as $item) {
                        $findProduct = $bxLoadData->findProduct($item["name"]);
                        if ($findProduct == false) {
                            if (!$bxLoadData->createProduct($item, $childChildSect)) {
                                die();
                            }
                        } else {
                            $bxLoadData->addToSectionProduct($findProduct["ID"], $childChildSect);
                        }
                    }
                }
            } else {
                $pageItem = $isoLux->parseItemData($childElem);
                foreach ($pageItem as $item) {
                    $findProduct = $bxLoadData->findProduct($item["name"]);
                    if ($findProduct == false) {
                        if (!$bxLoadData->createProduct($item, $childSection)) {
                            die();
                        }
                    } else {
                        $bxLoadData->addToSectionProduct($findProduct["ID"], $childSection);
                    }
                }
            }
        }
    }
}
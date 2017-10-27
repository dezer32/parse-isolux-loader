<?
require __DIR__ . "/SectionSettings.php";
require __DIR__ . "/SectionProperties.php";
require __DIR__ . "/BXLoadData.php";

require __DIR__ . "/parse-isolux/IsoluxParser.php";

$isoLux = new \Isolux\IsoluxParser();

$bxLoadData = new BXLoadData(1);
$i = 0;

$step = abs(intval($_REQUEST["step"]));
$step = empty($step) ? 0 : $step;
$step = (count($arSectionsUrl) > $step) ? $step : 0;
$arStepSection = $arSectionsUrl[$step];



foreach ($arStepSection as $section => $children) {
    $arSection = $bxLoadData->findSection($section);
    $bxLoadData->log("Поиск секции ".$section);
    if (!$arSection) {
        $arSection = $bxLoadData->createSection($section);
        if (!$arSection) {
            $bxLoadData->log("Ошибка ".$bxLoadData->getLastError());
            die();
        } else {
            $bxLoadData->log("Создана секция ".$arSection);
            $bxLoadData->debug($arSection);
        }
    } else {
        $bxLoadData->log("Секция найдена");
        $bxLoadData->debug($arSection);
    }
    foreach ($children as $childSect => $childElem) {
        $arSection = $bxLoadData->findSection($childSect, $section);
        $bxLoadData->log("Поиск секции ".$childSect);
        if (!$arSection) {
            $arSection = $bxLoadData->createSection($childSect, $section);
            if (!$arSection) {
                $bxLoadData->log("Ошибка ".$bxLoadData->getLastError());
                die();
            } else {
                $bxLoadData->log("Создана секция ".$arSection);
                $bxLoadData->debug($arSection);
            }
        } else {
            $transferring = $bxLoadData->transferringSection($section, $arSection);
            if ($transferring) {
                $bxLoadData->log("Секция перенесена " . $childSect);
            } else {
                $arSection = $bxLoadData->createSection($childSect, $section);
                if (!$arSection) {
                    $bxLoadData->log("Ошибка ".$bxLoadData->getLastError());
                    die();
                } else {
                    $bxLoadData->log("Создана секция ".$arSection);
                    $bxLoadData->debug($arSection);
                }
            }
        }

        if (is_array($childElem)) {
            foreach ($childElem as $childChildSect => $childChildElem) {
                $arSection = $bxLoadData->findSection($childChildSect, $childSect);
                $bxLoadData->log("Поиск секции ".$childChildSect);
                if (!$arSection) {
                    $arSection = $bxLoadData->createSection($childChildSect, $childSect);
                    if (!$arSection) {
                        $bxLoadData->log("Ошибка ".$bxLoadData->getLastError());
                        die();
                    } else {
                        $bxLoadData->log("Создана секция ".$arSection);
                        $bxLoadData->debug($arSection);
                    }
                } else {
                    $transferring = $bxLoadData->transferringSection($childSect, $arSection);
                    if ($transferring) {
                        $bxLoadData->log("Секция перенесена " . $childChildSect);
                    } else {
                        $arSection = $bxLoadData->createSection($childSect, $section);
                        if (!$arSection) {
                            $bxLoadData->log("Ошибка ".$bxLoadData->getLastError());
                            die();
                        } else {
                            $bxLoadData->log("Создана секция ".$arSection);
                            $bxLoadData->debug($arSection);
                        }
                    }
                }
//              print_r($childElem);
                //Если ссылка
                $pageItem = $isoLux->parseItemData($childChildElem);
                foreach ($pageItem as $item) {
                    foreach ($item["characteristics"] as $characteristics) {
                        if (!in_array($characteristics["label"], $arSectionProperties)) {
                            $arSectionProperties[] = $characteristics["label"];
                        }
                    }
                }
            }
        } else {
            //Если ссылка
//            print_r($childElem);
            $pageItem = $isoLux->parseItemData($childElem);
            foreach ($pageItem as $item) {
                foreach ($item["characteristics"] as $characteristics) {
                    if (!in_array($characteristics["label"], $arSectionProperties)) {
                        $arSectionProperties[] = $characteristics["label"];
                    }
                }
            }
            $i++;
        }
    }
}

$bxLoadData->debug($arSectionProperties);
echo $i;
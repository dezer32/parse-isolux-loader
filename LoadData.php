<?
require __DIR__ . "/SectionSettings.php";
require __DIR__ . "/SectionProperties.php";
require __DIR__ . "/BXLoadData.php";
require __DIR__ . "/parse-isolux/IsoluxParser.php";

$isoLux = new \Isolux\IsoluxParser();

$bxLoadData = new BXLoadData(1, $arSectionProperties);
$test = 0;

//$name = "Влагостойкость";
//$bxLoadData->debug($bxLoadData->rusToTranslit($name));
//die();

$step = abs(intval($_REQUEST["step"]));
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
}
for ($i = 0; $i < count($arSectionsUrl); $i++) {
    echo "<a href='?step=" . $i . "'>" . ($i + 1) . "</a> ";
}
echo "<br />\r\n";
//$step = (count($arSectionsUrl) > $step) ? $step : 0;
//$arStepSection = $arSectionsUrl[$step];


foreach ($arStepSection as $section => $children) {
    $arSection = $bxLoadData->findSection($section);
    $bxLoadData->log("Поиск секции " . $section);
    if (!$arSection) {
        $arSection = $bxLoadData->createSection($section);
        if (!$arSection) {
            $bxLoadData->log("Ошибка " . $bxLoadData->getLastError());
            die();
        } else {
            $bxLoadData->log("Создана секция " . $arSection);
//            $bxLoadData->debug($arSection);
        }
    } else {
        $bxLoadData->log("Секция найдена");
//        $bxLoadData->debug($arSection);
    }
    foreach ($children as $childSect => $childElem) {
        $arSection = $bxLoadData->findSection($childSect, $section);
        $bxLoadData->log("Поиск секции " . $childSect);
        if (!$arSection) {
            $arSection = $bxLoadData->createSection($childSect, $section);
            if (!$arSection) {
                $bxLoadData->log("Ошибка " . $bxLoadData->getLastError());
                die();
            } else {
                $bxLoadData->log("Создана секция " . $arSection);
//                $bxLoadData->debug($arSection);
            }
        } else {
            $transferring = $bxLoadData->transferringSection($section, $arSection);
            if ($transferring) {
                $bxLoadData->log("Секция перенесена " . $childSect);
            } else {
                $arSection = $bxLoadData->createSection($childSect, $section);
                if (!$arSection) {
                    $bxLoadData->log("Ошибка " . $bxLoadData->getLastError());
                    die();
                } else {
                    $bxLoadData->log("Создана секция " . $arSection);
//                    $bxLoadData->debug($arSection);
                }
            }
        }

        if (is_array($childElem)) {
            foreach ($childElem as $childChildSect => $childChildElem) {
                $arSection = $bxLoadData->findSection($childChildSect, $childSect);
                $bxLoadData->log("Поиск секции " . $childChildSect);
                if (!$arSection) {
                    $arSection = $bxLoadData->createSection($childChildSect, $childSect);
                    if (!$arSection) {
                        $bxLoadData->log("Ошибка " . $bxLoadData->getLastError());
                        die();
                    } else {
                        $bxLoadData->log("Создана секция " . $arSection);
//                        $bxLoadData->debug($arSection);
                    }
                } else {
                    $transferring = $bxLoadData->transferringSection($childSect, $arSection);
                    if ($transferring) {
                        $bxLoadData->log("Секция перенесена " . $childChildSect);
                    } else {
                        $arSection = $bxLoadData->createSection($childSect, $section);
                        if (!$arSection) {
                            $bxLoadData->log("Ошибка " . $bxLoadData->getLastError());
                            die();
                        } else {
                            $bxLoadData->log("Создана секция " . $arSection);
//                            $bxLoadData->debug($arSection);
                        }
                    }
                }
//              print_r($childElem);
                //Если ссылка
                /*$pageItem = $isoLux->parseItemData($childChildElem);
                foreach ($pageItem as $item) {
                    foreach ($item["characteristics"] as $characteristics) {
                        if (!in_array($characteristics["label"], $arSectionProperties)) {
                            $arSectionProperties[] = $characteristics["label"];
                        }
                    }
                }
                $bxLoadData->debug($pageItem);
                $test++;*/
            }
        } else {
//            print_r($childElem);
            //Если ссылка
            if ($test < 1) {
                $pageItem = $isoLux->parseItemData($childElem);
                foreach ($pageItem as $item) {
                    $resCreateProduct = $bxLoadData->createProduct($item, $childSect);
                    if ($resCreateProduct == false) {
                        echo $bxLoadData->getLastError();
                        $bxLoadData->log("Ошибка создания продукта ".$item["name"]);
                    } else {
                        $bxLoadData->log("Продукт создан ".$item["name"]);
                        $bxLoadData->debug($resCreateProduct);
                    }
                }
                $bxLoadData->debug($pageItem);
                $test++;
            } else {
                break;
            }
        }
    }
}
for ($i = 0; $i < count($arSectionsUrl); $i++) {
    echo "<a href='?step=" . $i . "'>" . ($i + 1) . "</a> ";
}
echo "<br />\r\n" .
    "Выполненных операций: " . $test;

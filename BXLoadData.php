<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
CModule::IncludeModule("iblock");


class BXLoadData
{
    const DEBUG = false;

    private $sect;
    private $prop;
    private $elem;

    private $arSelect;

    private $sectionIds;

    private $iblockId;

    private $sectionProp;

    /**
     * BXLoadData constructor.
     * @param $iblockId
     * @param $sectionProp
     * @param $productsProp
     */
    public function __construct($iblockId = 1, $sectionProp = "")
    {
        $this->iblockId = $iblockId;
        $this->sectionProp = $sectionProp;
        $this->sect = new CIBlockSection();
        $this->prop = new CIBlockProperty();
        $this->elem = new CIBlockElement();
        $this->arSelect = [
            "ID",
            "IBLOCK_SECTION_ID",
            "ACTIVE",
            "NAME",
            "CODE",
            "SECTION_PAGE_URL"
        ];
        $this->sectionIds = [];
    }


    public function createProduct($arItem, $parentSectionName)
    {
        $this->log("Создаем товар " . $arItem["name"] . " в секции " . $parentSectionName);
        if (!empty($this->sectionIds[$parentSectionName])) {
            $sectionId = $this->sectionIds[$parentSectionName];
        } else {
            $sectionId = "";
        }

        $arProp = [
            $this->sectionProp["Цена"] => $arItem["price"],
            $this->sectionProp["Артикул"] => $arItem["article"],
        ];

        foreach ($arItem["characteristics"] as $characteristic) {
            $propName = $this->sectionProp[$characteristic["label"]];
            if (empty($this->sectionProp[$characteristic["label"]])) {
                $propName = $this->createOrFindProperties($characteristic["label"]);
            }
            $arProp[$propName] = $characteristic["data"];
        }

        $pictures = CFile::MakeFileArray($arItem["img"][0]);

        $arFieldsProducts = [
            "IBLOCK_ID" => $this->iblockId,
            "IBLOCK_SECTION_ID" => $sectionId,
            "PROPERTY_VALUES" => $arProp,
            "ACTIVE" => "Y",
            "NAME" => $arItem["name"],
            "CODE" => strtolower($this->rusToTranslit($arItem["name"])),
            "DETAIL_TEXT" => $arItem["description"],
            "DETAIL_PICTURE" => $pictures,
            "PREVIEW_PICTURE" => $pictures
        ];
        $idElem = $this->elem->Add($arFieldsProducts);
        if ($idElem > 0) {
            $this->log("Товар создан");
            return $arFieldsProducts;
        } else {
            $this->log("Ошибка создания товара");
            return false;
        }
    }

    public function addToSectionProduct($id, $parentSectionName)
    {
        $this->log("Перенос товара id=" . $id . " в секцию " . $parentSectionName);
        if (!empty($this->sectionIds[$parentSectionName])) {
            $sectionId = $this->sectionIds[$parentSectionName];
        } else {
            $sectionId = "";
        }
        $oldSection = CIBlockElement::GetElementGroups($id);
        $newSection = [$sectionId];
        while ($arGroup = $oldSection->Fetch()) {
            $newSection[] = $arGroup["ID"];
        }
        CIBlockElement::SetElementSection($id, $newSection);
    }

    public function findProduct($name)
    {
        $this->log("Поиск продукта " . $name);
        $arFilter = [
            "IBLOCK_ID" => $this->iblockId,
            "NAME" => $name
        ];
        $rsElem = $this->elem->GetList([], $arFilter);
        $arElem = $rsElem->Fetch();
        if ($arElem) {
            $this->log("Продукт найден");
            return $arElem;
        } else {
            $this->log("Продукт не найден");
            return false;
        }
    }

    public function createOrFindProperties($name)
    {
        $findProp = $this->findProperties($name);
        if ($findProp) {
            $this->sectionProp[$name] = $findProp["CODE"];
            return $findProp["CODE"];
        } else {
            $propLatName = strtoupper($this->rusToTranslit($name));
            $arFields = [
                "NAME" => $name,
                "ACTIVE" => "Y",
                "SORT" => "500",
                "CODE" => $propLatName,
                "PROPERTY_TYPE" => "S",
                "IBLOCK_ID" => $this->iblockId,
            ];
            $idProp = $this->prop->Add($arFields);
            $this->sectionProp[$name] = $propLatName;
            if ($idProp > 0) {
                return $propLatName;
            } else {
                return false;
            }
        }
    }

    public function findProperties($name)
    {
        $prop = CIBlockProperty::GetList([], ["NAME" => $name])->Fetch();
        return $prop;
    }

    public function transferringSection($parentSectionName, $arSection)
    {
        $this->log("Перенос секции " . $arSection["NAME"] . " в секцию " . $parentSectionName);
        $this->sect->Update($arSection["ID"], ["IBLOCK_SECTION_ID" => $this->sectionIds[$parentSectionName]]);
    }

    public function createSection($sectionName, $parentSectionName = "")
    {
        $this->log("Создание секции " . $sectionName . " в секции " . ($parentSectionName == "" ? "parent" : $parentSectionName));
        if (!empty($this->sectionIds[$parentSectionName])) {
            $sectionId = $this->sectionIds[$parentSectionName];
        } else {
            $sectionId = "";
        }
        $arFields = [
            "NAME" => $sectionName,
            "IBLOCK_ID" => $this->iblockId,
            "IBLOCK_SECTION_ID" => $sectionId,
            "ACTIVE" => "Y",
            "CODE" => $this->rusToTranslit($sectionName)
        ];
        $idNewSect = $this->sect->Add($arFields);
        if (!($idNewSect > 0)) {
            $this->log("Секция не создана (" . $this->getLastError() . ")");
            return false;
        } else {
            $this->sectionIds[$sectionName] = $idNewSect;
            $this->log("Секции создана в id=" . ($sectionId == "" ? "parent" : $sectionId) . " (" . $idNewSect . ")");
            return $idNewSect;
        }
    }


    public function findSection($sectionName, $parentSectionName = "")
    {
        $this->log("Поиск секции " . $sectionName . " в секции " . ($parentSectionName == "" ? "parent" : $parentSectionName));
        if (!empty($this->sectionIds[$parentSectionName])) {
            $sectionId = $this->sectionIds[$parentSectionName];
        } else {
            $sectionId = "";
        }
        $arFilter = [
            "IBLOCK_ID" => $this->iblockId,
            "SECTION_ID" => $sectionId,
            "NAME" => $sectionName
        ];
        $rsSection = $this->sect->GetList(["SORT" => "ASC"], $arFilter, false, $this->arSelect);
        $arSection = $rsSection->GetNext();
        if ($arSection) {
            $this->sectionIds[$arSection["NAME"]] = $arSection["ID"];
            $this->log("Секция найдена");
            $this->debug($arSection);
            return $arSection;
        } else {
            $this->log("Секция не найдена");
            return false;
        }
    }

    public function getLastError()
    {
        return $this->sect->LAST_ERROR;
    }

    public function log($log)
    {
//        echo $log . "</br>\r\n";
    }

    public function debug($var)
    {
        if (self::DEBUG) {
            echo "<pre>";
            print_r($var);
            echo "</pre>";
        }
    }

    /**
     * @return array
     */
    public function getSectionIds()
    {
        return $this->sectionIds;
    }

    public function getParentSectionByParentSectionName($parentSectionName)
    {
        return $this->sectionIds[$parentSectionName];
    }

    function rusToTranslit($string)
    {
        $converter = array(
            'а' => 'a', 'б' => 'b', 'в' => 'v',
            'г' => 'g', 'д' => 'd', 'е' => 'e',
            'ё' => 'e', 'ж' => 'zh', 'з' => 'z',
            'и' => 'i', 'й' => 'y', 'к' => 'k',
            'л' => 'l', 'м' => 'm', 'н' => 'n',
            'о' => 'o', 'п' => 'p', 'р' => 'r',
            'с' => 's', 'т' => 't', 'у' => 'u',
            'ф' => 'f', 'х' => 'h', 'ц' => 'c',
            'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch',
            'ь' => '', 'ы' => 'y', 'ъ' => '',
            'э' => 'e', 'ю' => 'yu', 'я' => 'ya',

            'А' => 'A', 'Б' => 'B', 'В' => 'V',
            'Г' => 'G', 'Д' => 'D', 'Е' => 'E',
            'Ё' => 'E', 'Ж' => 'Zh', 'З' => 'Z',
            'И' => 'I', 'Й' => 'Y', 'К' => 'K',
            'Л' => 'L', 'М' => 'M', 'Н' => 'N',
            'О' => 'O', 'П' => 'P', 'Р' => 'R',
            'С' => 'S', 'Т' => 'T', 'У' => 'U',
            'Ф' => 'F', 'Х' => 'H', 'Ц' => 'C',
            'Ч' => 'Ch', 'Ш' => 'Sh', 'Щ' => 'Sch',
            'Ь' => '\'', 'Ы' => 'Y', 'Ъ' => '\'',
            'Э' => 'E', 'Ю' => 'Yu', 'Я' => 'Ya',

            ' ' => '_'
        );
        return strtr($string, $converter);
    }

    /**
     * @return mixed
     */
    public function getSectionProp()
    {
        return $this->sectionProp;
    }
}

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_after.php");
<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
CModule::IncludeModule("iblock");


class BXLoadData
{

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
    public function __construct($iblockId, $sectionProp)
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

        if (!empty($this->sectionIds[$parentSectionName])) {
            $sectionId = $this->sectionIds[$parentSectionName];
        } else {
            $sectionId = "";
        }

        $findElem = $this->findProduct($arItem["name"]);
        if ($findElem && $findElem["IBLOCK_SECTION_ID"] != $sectionId) {
            return $this->addToSectionProduct($findElem["ID"], $sectionId);
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
            return $arFieldsProducts;
        } else {
            return false;
        }
    }

    public function addToSectionProduct($id, $sectionId) {
        $oldSection = CIBlockElement::GetElementGroups($id);
        $newSection = [$sectionId];
        while ($arGroup = $oldSection->Fetch()) {
            $newSection[] = $arGroup["ID"];
        }
        return CIBlockElement::SetElementSection($id, $newSection);
    }

    public function findProduct($name) {
        $arFilter = [
            "IBLOCK_ID" => $this->iblockId,
            "NAME" => $name
        ];
        $rsElem = $this->elem->GetList([], $arFilter);
        $arElem = $rsElem->Fetch();
        if ($arElem) {
            return $arElem;
        } else {
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
        if ($this->sectionIds[$parentSectionName] != $arSection["IBLOCK_SECTION_ID"] && $arSection["NAME"] == "Аквапанели") {
            $this->sect->Update($arSection["ID"], ["IBLOCK_SECTION_ID" => $this->sectionIds[$parentSectionName]]);
            return true;
        } else if ($this->sectionIds[$parentSectionName] == $arSection["IBLOCK_SECTION_ID"]) {
            return true;
        } else {
            return false;
        }
    }

    public function createSection($sectionName, $parentSectionName = "")
    {
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
            return false;
        } else {
            $this->sectionIds[$sectionName] = $idNewSect;
            return $idNewSect;
        }
    }


    public function findSection($sectionName, $parentSectionName = "")
    {
        if (!empty($this->sectionIds[$parentSectionName])) {
            $sectionId = $this->sectionIds[$parentSectionName];
        } else {
            $sectionId = "";
        }
        $arFilter = [
            "IBLOCK_ID" => $this->iblockId,
            "IBLOCK_SECTION_ID" => $sectionId,
            "NAME" => $sectionName
        ];

        $rsSection = $this->sect->GetList(["SORT" => "ASC"], $arFilter, false, $this->arSelect);
        $arSection = $rsSection->GetNext();
        if ($arSection) {
            $this->sectionIds[$arSection["NAME"]] = $arSection["ID"];
            return $arSection;
        } else {
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
//        echo "<pre>";
//        print_r($var);
//        echo "</pre>";
    }

    /**
     * @return array
     */
    public function getSectionIds()
    {
        return $this->sectionIds;
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
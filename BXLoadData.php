<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
CModule::IncludeModule("iblock");


class BXLoadData
{

    private $sect;
    private $prop;

    private $arSelect;

    private $sectionIds;

    private $iblockId;

    /**
     * BXLoadData constructor.
     * @param $iblockId
     */
    public function __construct($iblockId)
    {
        $this->iblockId = $iblockId;
        $this->sect = new CIBlockSection();
        $this->prop = new CIBlockProperty();
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

        $arProp = [];

        $arFieldsProducts = [
            "IBLOCK_ID" => $this->iblockId,
            "IBLOCK_SECTION_ID" => $sectionId,
            "PROPERTY_VALUE" => $arProp,
            "ACTIVE" => "Y",
            "NAME" => $arItem["name"],
            "DETAIL_TEXT" => $arItem["description"],

        ];
    }

    public function createProperties($name) {
        $arFields = [
            "NAME" => $name,
            "ACTIVE" => "Y",
            "SORT" => "500",
            "CODE" => strtoupper(CUtil::translit($name, "ru")),
            "PROPERTY_TYPE" => "S",
            "USER_TYPE" => "text",
            "IBLOCK_ID" => $this->iblockId,
        ];
        $idProp = $this->prop->Add($arFields);
        if ($idProp > 0) {
            return true;
        } else {
            return false;
        }
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
            echo $sectionId = $this->sectionIds[$parentSectionName];
        } else {
            $sectionId = "";
        }
        $arFields = [
            "NAME" => $sectionName,
            "IBLOCK_ID" => $this->iblockId,
            "IBLOCK_SECTION_ID" => $sectionId,
            "ACTIVE" => "Y",
            "CODE" => CUtil::translit($sectionName, "ru")
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
        echo $log . "</br>\r\n";
    }

    public function debug($var)
    {
        echo "<pre>";
        print_r($var);
        echo "</pre>";
    }

    /**
     * @return array
     */
    public function getSectionIds()
    {
        return $this->sectionIds;
    }


}

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_after.php");